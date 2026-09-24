// assets/js/main.js
'use strict';

document.addEventListener('DOMContentLoaded', () => {

  /* ══════════════════════════════════════════════════════════
     1. MOBILE NAV TOGGLE
  ══════════════════════════════════════════════════════════ */
  const navToggle = document.querySelector('.nav-toggle');
  const navMenu   = document.querySelector('.nav-menu');

  if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => {
      const open = navMenu.classList.toggle('open');
      navToggle.classList.toggle('open', open);
      navToggle.setAttribute('aria-expanded', open);
    });

    navMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        navMenu.classList.remove('open');
        navToggle.classList.remove('open');
        navToggle.setAttribute('aria-expanded', false);
      });
    });

    document.addEventListener('click', e => {
      if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
        navMenu.classList.remove('open');
        navToggle.classList.remove('open');
        navToggle.setAttribute('aria-expanded', false);
      }
    });
  }

  /* ══════════════════════════════════════════════════════════
     2. HERO CAROUSEL  (arrows + dots + auto-play + touch)
  ══════════════════════════════════════════════════════════ */
  const heroCarousel = document.querySelector('.hero-carousel');

  if (heroCarousel) {
    const inner    = heroCarousel.querySelector('.carousel-inner');
    const items    = heroCarousel.querySelectorAll('.carousel-item');
    const prevBtn  = heroCarousel.querySelector('#carousel-prev');
    const nextBtn  = heroCarousel.querySelector('#carousel-next');
    const dotsWrap = heroCarousel.querySelector('.carousel-dots');

    if (inner && items.length > 0) {
      let current  = 0;
      let autoPlay = null;
      const DURATION = 5000;

      if (dotsWrap) {
        items.forEach((_, i) => {
          const dot = document.createElement('button');
          dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
          dot.setAttribute('aria-label', `Slide ${i + 1}`);
          dot.addEventListener('click', () => goTo(i));
          dotsWrap.appendChild(dot);
        });
      }

      function goTo(index) {
        if (index < 0) index = items.length - 1;
        if (index >= items.length) index = 0;
        current = index;
        inner.style.transform = `translateX(-${current * 100}%)`;
        if (dotsWrap) {
          dotsWrap.querySelectorAll('.carousel-dot').forEach((d, i) => {
            d.classList.toggle('active', i === current);
          });
        }
      }

      function startAuto() {
        stopAuto();
        autoPlay = setInterval(() => goTo(current + 1), DURATION);
      }
      function stopAuto() {
        if (autoPlay) { clearInterval(autoPlay); autoPlay = null; }
      }

      if (prevBtn) prevBtn.addEventListener('click', () => { goTo(current - 1); stopAuto(); startAuto(); });
      if (nextBtn) nextBtn.addEventListener('click', () => { goTo(current + 1); stopAuto(); startAuto(); });

      heroCarousel.addEventListener('mouseenter', stopAuto);
      heroCarousel.addEventListener('mouseleave', startAuto);

      let touchStartX = 0;
      heroCarousel.addEventListener('touchstart', e => {
        touchStartX = e.changedTouches[0].screenX;
        stopAuto();
      }, { passive: true });
      heroCarousel.addEventListener('touchend', e => {
        const diff = touchStartX - e.changedTouches[0].screenX;
        if (Math.abs(diff) > 40) goTo(diff > 0 ? current + 1 : current - 1);
        startAuto();
      }, { passive: true });

      startAuto();
    }
  }

  /* ══════════════════════════════════════════════════════════
     3. NETFLIX SLIDERS  (horizontal strip arrows + drag)
  ══════════════════════════════════════════════════════════ */
  document.querySelectorAll('.slider-wrapper').forEach(wrapper => {
    const track      = wrapper.querySelector('.product-slider');
    const leftArrow  = wrapper.querySelector('.slider-arrow.left');
    const rightArrow = wrapper.querySelector('.slider-arrow.right');
    if (!track) return;

    const scrollAmount = () => {
      const card = track.querySelector('.product-card');
      return card ? card.offsetWidth * 3 + 16 * 3 : 480;
    };

    if (leftArrow)  leftArrow.addEventListener('click',  () => track.scrollBy({ left: -scrollAmount(), behavior: 'smooth' }));
    if (rightArrow) rightArrow.addEventListener('click', () => track.scrollBy({ left:  scrollAmount(), behavior: 'smooth' }));

    function updateArrows() {
      if (leftArrow)  leftArrow.style.visibility  = track.scrollLeft <= 4 ? 'hidden' : 'visible';
      if (rightArrow) rightArrow.style.visibility =
        track.scrollLeft + track.clientWidth >= track.scrollWidth - 4 ? 'hidden' : 'visible';
    }
    track.addEventListener('scroll', updateArrows, { passive: true });
    if (document.readyState === 'complete') requestAnimationFrame(updateArrows);
    else window.addEventListener('load', () => requestAnimationFrame(updateArrows), { once: true });

    let isDown = false, startX = 0, scrollLeft = 0;
    track.addEventListener('mousedown', e => { isDown = true; track.style.cursor = 'grabbing'; startX = e.pageX - track.offsetLeft; scrollLeft = track.scrollLeft; });
    track.addEventListener('mouseleave', () => { isDown = false; track.style.cursor = ''; });
    track.addEventListener('mouseup',    () => { isDown = false; track.style.cursor = ''; });
    track.addEventListener('mousemove', e => {
      if (!isDown) return;
      e.preventDefault();
      track.scrollLeft = scrollLeft - (e.pageX - track.offsetLeft - startX) * 1.4;
    });
  });

  /* ══════════════════════════════════════════════════════════
     4. ADD TO CART (async) — shared by feed cards + legacy forms
  ══════════════════════════════════════════════════════════ */
  function addToCart(productId, btn) {
    const original = btn ? btn.textContent : '';
    if (btn) { btn.textContent = '…'; btn.disabled = true; }

    const fd = new FormData();
    fd.append('product_id', productId);
    fd.append('quantity', 1);

    fetch('cart_api.php?action=add', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          ['cart-count', 'cart-count-desktop'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = data.cartCount;
          });
          showToast('Added to cart ✓', 'success');
        } else {
          showToast(data.message || 'Could not add to cart', 'error');
        }
      })
      .catch(() => showToast('Network error. Please try again.', 'error'))
      .finally(() => { if (btn) { btn.textContent = original; btn.disabled = false; } });
  }

  // Legacy forms (shop.php, product.php, etc.)
  document.querySelectorAll('.add-to-cart-form').forEach(form => {
    form.addEventListener('submit', e => {
      e.preventDefault();
      const pid = form.querySelector('[name="product_id"]')?.value;
      if (pid) addToCart(pid, form.querySelector('button[type="submit"]'));
    });
  });

  /* ══════════════════════════════════════════════════════════
     5. HOMEPAGE PRODUCT FEED — infinite scroll + category filter
     Uses an IIFE so early exits don't affect sections 1-4 above.
  ══════════════════════════════════════════════════════════ */
  (function initFeed() {
    const feedGrid   = document.getElementById('product-feed');
    const sentinel   = document.getElementById('feed-sentinel');
    const feedLoader = document.getElementById('feed-loader');
    const chipsBar   = document.getElementById('category-chips');

    // Not on the homepage — bail out of this IIFE only
    if (!feedGrid || !sentinel) return;

    const API       = 'products_api.php';
    const PAGE_SIZE = 20;

    let state = {
      category : 'all',
      page     : 1,
      loading  : false,
      done     : false,
    };

    // Scroll observer is created once and started AFTER first batch loads
    let scrollObserver = null;

    /* ── Helpers ── */
    function escHtml(str) {
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function fmt(n) {
      return Number(n).toLocaleString('en-KE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    }

    /* ── Encode image path: replace spaces so browser loads it correctly ── */
    function encodeImgPath(path) {
      // Only encode spaces (the only problematic char in these filenames)
      // Leave the rest of the relative path (../ slashes) intact
      return path.split('/').map(seg => seg.replace(/ /g, '%20')).join('/');
    }

    /* ── Lazy image loader ── */
    const imgObserver = ('IntersectionObserver' in window)
      ? new IntersectionObserver((entries, obs) => {
          entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const img = entry.target;
            const src = img.dataset.src;
            if (src) {
              img.src = encodeImgPath(src);
              img.addEventListener('load',  () => img.classList.add('loaded'), { once: true });
              img.addEventListener('error', () => {
                img.src = 'https://via.placeholder.com/300x300?text=No+Image';
                img.classList.add('loaded');
              }, { once: true });
            }
            obs.unobserve(img);
          });
        }, { rootMargin: '300px' })
      : null;

    function lazyLoad(img) {
      if (imgObserver) {
        imgObserver.observe(img);
      } else {
        // Fallback: load immediately
        const src = img.dataset.src || '';
        img.src = encodeImgPath(src);
        img.classList.add('loaded');
      }
    }

    /* ── Build one card ── */
    function buildCard(p) {
      const a = document.createElement('a');
      a.href      = `product.php?id=${p.id}`;
      a.className = 'feed-card';
      a.setAttribute('aria-label', p.name);

      const hasDiscount = p.discount_percentage > 0;

      a.innerHTML = `
        <div class="feed-card-img">
          <img
            data-src="${escHtml(p.image || '')}"
            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
            alt="${escHtml(p.name)}"
            width="300" height="300">
          ${p.is_flash_sale
            ? '<span class="ribbon flash">⚡ FLASH</span>'
            : hasDiscount
              ? `<span class="ribbon">${p.discount_percentage}% OFF</span>`
              : ''}
          <button
            class="feed-card-cart"
            aria-label="Add ${escHtml(p.name)} to cart"
            data-id="${p.id}"
            title="Add to cart">🛒</button>
        </div>
        <div class="feed-card-body">
          <p class="feed-card-name">${escHtml(p.name)}</p>
          <p class="feed-card-price">
            ${hasDiscount ? `<span class="was">Ksh ${fmt(p.price)}</span>` : ''}
            Ksh ${fmt(p.sale_price)}
          </p>
        </div>`;

      a.querySelector('.feed-card-cart').addEventListener('click', e => {
        e.preventDefault();
        e.stopPropagation();
        addToCart(p.id, e.currentTarget);
      });

      lazyLoad(a.querySelector('img'));
      return a;
    }

    /* ── Skeletons ── */
    function clearSkeletons() {
      feedGrid.querySelectorAll('.feed-card.skeleton').forEach(s => s.remove());
    }

    function showSkeletons(count) {
      for (let i = 0; i < count; i++) {
        const s = document.createElement('div');
        s.className = 'feed-card skeleton';
        s.setAttribute('aria-hidden', 'true');
        s.innerHTML = `
          <div class="feed-card-img skeleton-img"></div>
          <div class="feed-card-body">
            <div class="skeleton-line wide"></div>
            <div class="skeleton-line short"></div>
            <div class="skeleton-line price"></div>
          </div>`;
        feedGrid.appendChild(s);
      }
    }

    /* ── Fetch one page ── */
    async function fetchPage() {
      if (state.loading || state.done) return;
      state.loading = true;

      if (feedLoader) feedLoader.hidden = false;

      const params = new URLSearchParams({
        page  : state.page,
        limit : PAGE_SIZE,
      });
      if (state.category !== 'all') params.set('category_id', state.category);

      try {
        const res  = await fetch(`${API}?${params}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        clearSkeletons();

        if (data.success && Array.isArray(data.products) && data.products.length > 0) {
          const frag = document.createDocumentFragment();
          data.products.forEach(p => frag.appendChild(buildCard(p)));
          feedGrid.appendChild(frag);
          state.page++;
        }

        if (!data.has_more) {
          state.done = true;
          if (sentinel) sentinel.style.display = 'none';
        } else {
          // Only attach the scroll observer after the first batch is in the DOM
          attachScrollObserver();
        }

      } catch (err) {
        clearSkeletons();
        console.error('Feed fetch error:', err);
        // Show a friendly retry message
        if (feedGrid.children.length === 0) {
          feedGrid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#888;padding:32px 0">Could not load products. Please refresh.</p>';
        }
      } finally {
        state.loading = false;
        if (feedLoader) feedLoader.hidden = true;
      }
    }

    /* ── Scroll observer — attached only after first batch ── */
    function attachScrollObserver() {
      if (scrollObserver) return; // already attached
      scrollObserver = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting) fetchPage();
      }, { rootMargin: '400px' });
      scrollObserver.observe(sentinel);
    }

    /* ── Reset feed for category change ── */
    function resetFeed(category) {
      // Detach old scroll observer so it doesn't trigger mid-reset
      if (scrollObserver) {
        scrollObserver.disconnect();
        scrollObserver = null;
      }

      state = { category, page: 1, loading: false, done: false };

      feedGrid.innerHTML = '';
      if (sentinel) sentinel.style.display = '';

      showSkeletons(10);
      fetchPage();
    }

    /* ── Category chip clicks ── */
    if (chipsBar) {
      chipsBar.addEventListener('click', e => {
        const chip = e.target.closest('.chip');
        if (!chip) return;

        chipsBar.querySelectorAll('.chip').forEach(c => {
          c.classList.remove('active');
          c.setAttribute('aria-selected', 'false');
        });
        chip.classList.add('active');
        chip.setAttribute('aria-selected', 'true');

        resetFeed(chip.dataset.cat);
        chip.scrollIntoView({ inline: 'nearest', block: 'nearest', behavior: 'smooth' });
      });
    }

    // Initial load — skeletons are already rendered by PHP
    fetchPage();

  })(); // end initFeed IIFE

  /* ══════════════════════════════════════════════════════════
     6. TOAST NOTIFICATIONS
  ══════════════════════════════════════════════════════════ */
  function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      Object.assign(container.style, {
        position: 'fixed', bottom: '90px', left: '50%',
        transform: 'translateX(-50%)', display: 'flex',
        flexDirection: 'column', alignItems: 'center',
        gap: '8px', zIndex: '9999', pointerEvents: 'none',
      });
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const bg = type === 'success' ? '#2ecc71' : type === 'error' ? '#e74c3c' : '#3498db';
    Object.assign(toast.style, {
      background: bg, color: '#fff', padding: '10px 22px',
      borderRadius: '99px', fontWeight: '700', fontSize: '.9rem',
      boxShadow: '0 4px 16px rgba(0,0,0,.2)', opacity: '0',
      transform: 'translateY(12px)',
      transition: 'opacity .3s ease, transform .3s ease', whiteSpace: 'nowrap',
    });
    toast.textContent = message;
    container.appendChild(toast);

    requestAnimationFrame(() => {
      toast.style.opacity   = '1';
      toast.style.transform = 'translateY(0)';
    });

    setTimeout(() => {
      toast.style.opacity   = '0';
      toast.style.transform = 'translateY(-8px)';
      setTimeout(() => toast.remove(), 350);
    }, 2800);
  }

}); // end DOMContentLoaded
