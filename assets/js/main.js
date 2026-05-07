/* АвтоЗапчасть — main.js */
(function () {
  'use strict';

  /* ──────────────────────────────────────────────────────
     Live search
  ────────────────────────────────────────────────────── */
  var searchInput = document.getElementById('az-search-input');
  var searchDrop  = document.getElementById('az-search-dropdown');

  if (searchInput && searchDrop) {
    var searchTimer = null;

    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimer);
      var q = this.value.trim();
      if (q.length < 2) { searchDrop.style.display = 'none'; return; }

      searchTimer = setTimeout(function () {
        fetch('/api/search.php?q=' + encodeURIComponent(q))
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data.results || !data.results.length) {
              searchDrop.style.display = 'none';
              return;
            }
            var html = '';
            data.results.forEach(function (p) {
              html += '<a class="search-result-item" href="/catalog/part.php?id=' + p.id + '">' +
                '<span class="sri-art">' + escHtml(p.part_number) + '</span>' +
                '<span class="sri-name">' + escHtml(p.name) + '</span>' +
                '<span class="sri-price">' + escHtml(p.price_formatted || '') + '</span>' +
                '</a>';
            });
            if (data.total > data.results.length) {
              html += '<a class="search-result-item search-result-all" href="/search/index.php?q=' + encodeURIComponent(q) + '">' +
                'Показать все результаты (' + data.total + ') →</a>';
            }
            searchDrop.innerHTML = html;
            searchDrop.style.display = 'block';
          })
          .catch(function () { searchDrop.style.display = 'none'; });
      }, 280);
    });

    document.addEventListener('click', function (e) {
      if (!searchInput.contains(e.target) && !searchDrop.contains(e.target)) {
        searchDrop.style.display = 'none';
      }
    });

    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { searchDrop.style.display = 'none'; }
      if (e.key === 'Enter') {
        searchDrop.style.display = 'none';
        this.closest('form').submit();
      }
    });
  }

  /* ──────────────────────────────────────────────────────
     Cart AJAX (add to cart)
  ────────────────────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.az-btn-add-cart');
    if (!btn) return;
    e.preventDefault();

    var partId = btn.dataset.partId;
    var qtyInput = document.getElementById('az-qty-input');
    var qty = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

    btn.disabled = true;
    btn.textContent = 'Добавляю...';

    fetch('/api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=add&part_id=' + encodeURIComponent(partId) + '&qty=' + qty
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          btn.textContent = '✓ В корзине';
          btn.classList.add('az-btn-success');
          updateCartBadge(data.cart_count);
          setTimeout(function () {
            btn.disabled = false;
            btn.textContent = 'В корзину';
            btn.classList.remove('az-btn-success');
          }, 2500);
        } else {
          btn.disabled = false;
          btn.textContent = 'В корзину';
          alert(data.error || 'Ошибка добавления в корзину.');
        }
      })
      .catch(function () {
        btn.disabled = false;
        btn.textContent = 'В корзину';
      });
  });

  /* Qty +/- on part detail page */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-qty]');
    if (!btn) return;
    var input = document.getElementById('az-qty-input');
    if (!input) return;
    var delta = parseInt(btn.dataset.qty);
    var cur   = parseInt(input.value) || 1;
    var min   = parseInt(input.min) || 1;
    var max   = parseInt(input.max) || 9999;
    input.value = Math.min(max, Math.max(min, cur + delta));
  });

  /* Cart page: update qty via AJAX */
  document.addEventListener('change', function (e) {
    var input = e.target.closest('.az-cart-qty-input');
    if (!input) return;
    var cartItemId = input.dataset.cartItem;
    var qty = parseInt(input.value) || 1;
    if (qty < 1) { qty = 1; input.value = 1; }

    fetch('/api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=update&cart_item_id=' + encodeURIComponent(cartItemId) + '&qty=' + qty
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          updateCartBadge(data.cart_count);
          if (data.cart_total_formatted) {
            var el = document.getElementById('az-cart-total');
            if (el) el.textContent = data.cart_total_formatted;
          }
          var row = input.closest('tr');
          if (row && data.item_total_formatted) {
            var totalCell = row.querySelector('.az-cart-item-total');
            if (totalCell) totalCell.textContent = data.item_total_formatted;
          }
        }
      });
  });

  /* Cart page: remove item */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.az-cart-remove-btn');
    if (!btn) return;
    if (!confirm('Удалить из корзины?')) return;
    var cartItemId = btn.dataset.cartItem;

    fetch('/api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=remove&cart_item_id=' + encodeURIComponent(cartItemId)
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          var row = btn.closest('tr');
          if (row) row.remove();
          updateCartBadge(data.cart_count);
          if (data.cart_count === 0) {
            location.reload();
          } else if (data.cart_total_formatted) {
            var el = document.getElementById('az-cart-total');
            if (el) el.textContent = data.cart_total_formatted;
          }
        }
      });
  });

  function updateCartBadge(count) {
    var badges = document.querySelectorAll('.az-cart-badge');
    badges.forEach(function (b) {
      b.textContent = count || 0;
      b.style.display = count > 0 ? '' : 'none';
    });
  }

  /* ──────────────────────────────────────────────────────
     Currency switcher
  ────────────────────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var link = e.target.closest('[data-set-currency]');
    if (!link) return;
    e.preventDefault();
    var cur = link.dataset.setCurrency;

    fetch('/api/currency.php?set=' + encodeURIComponent(cur))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) { location.reload(); }
      });
  });

  /* ──────────────────────────────────────────────────────
     Admin: order status AJAX update
  ────────────────────────────────────────────────────── */
  document.addEventListener('change', function (e) {
    var select = e.target.closest('[data-status-update]');
    if (!select) return;
    var orderId = select.dataset.statusUpdate;
    var csrf    = select.dataset.csrf;
    var status  = select.value;

    fetch(window.location.pathname, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=update_status&order_id=' + encodeURIComponent(orderId) +
            '&status=' + encodeURIComponent(status) +
            '&csrf_token=' + encodeURIComponent(csrf)
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          var row    = select.closest('tr');
          var badge  = row && row.querySelector('.az-status');
          if (badge) {
            badge.className = 'az-status az-status-' + status;
            badge.textContent = select.options[select.selectedIndex].text;
          }
          showToast('Статус заказа обновлён', 'success');
        } else {
          showToast('Ошибка: ' + (data.error || 'неизвестная ошибка'), 'danger');
        }
      })
      .catch(function () { showToast('Ошибка сети', 'danger'); });
  });

  /* ──────────────────────────────────────────────────────
     Toast notifications
  ────────────────────────────────────────────────────── */
  function showToast(msg, type) {
    var toast = document.createElement('div');
    toast.className = 'az-toast az-toast-' + (type || 'success');
    toast.textContent = msg;
    toast.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;' +
      'padding:12px 20px;border-radius:8px;font-size:13px;font-weight:500;' +
      'color:#fff;opacity:0;transition:opacity 0.3s;pointer-events:none;' +
      'background:' + (type === 'danger' ? '#dc3545' : '#28a745') + ';';
    document.body.appendChild(toast);
    setTimeout(function () { toast.style.opacity = '1'; }, 10);
    setTimeout(function () {
      toast.style.opacity = '0';
      setTimeout(function () { toast.remove(); }, 350);
    }, 3000);
  }

  /* ──────────────────────────────────────────────────────
     Newsletter popup
  ────────────────────────────────────────────────────── */
  var newsletterPopup = document.getElementById('az-newsletter-popup');
  if (newsletterPopup && !getCookie('nl_dismissed')) {
    setTimeout(function () {
      newsletterPopup.classList.add('az-popup-show');
    }, 8000);
  }

  document.addEventListener('click', function (e) {
    if (!newsletterPopup) return;
    if (e.target.closest('.az-newsletter-close') || e.target.id === 'az-newsletter-popup') {
      newsletterPopup.classList.remove('az-popup-show');
      setCookie('nl_dismissed', '1', 7);
    }
  });

  document.addEventListener('submit', function (e) {
    var form = e.target.closest('#az-newsletter-form');
    if (!form || !newsletterPopup) return;
    e.preventDefault();
    newsletterPopup.classList.remove('az-popup-show');
    setCookie('nl_dismissed', '1', 30);
    showToast('Спасибо за подписку!', 'success');
  });

  /* ──────────────────────────────────────────────────────
     Mobile menu toggle
  ────────────────────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-toggle="offcanvas"]');
    if (toggle) {
      var target = document.querySelector(toggle.dataset.target || '#az-offcanvas');
      if (target) target.classList.toggle('active');
    }
    var overlay = e.target.closest('.az-offcanvas-overlay');
    if (overlay) {
      var canvas = document.querySelector('.az-offcanvas');
      if (canvas) canvas.classList.remove('active');
    }
    // Legacy offcanvas (header.php uses canvas_open / canvas_close)
    if (e.target.closest('.canvas_open')) {
      document.querySelector('.offcanvas_menu')?.classList.add('active');
      document.querySelector('.off_canvars_overlay')?.classList.add('active');
    }
    if (e.target.closest('.canvas_close') || e.target.closest('.off_canvars_overlay')) {
      document.querySelector('.offcanvas_menu')?.classList.remove('active');
      document.querySelector('.off_canvars_overlay')?.classList.remove('active');
    }
  });

  /* ──────────────────────────────────────────────────────
     Owl Carousel init (if jQuery + Owl loaded)
  ────────────────────────────────────────────────────── */
  if (typeof jQuery !== 'undefined' && typeof jQuery.fn.owlCarousel !== 'undefined') {
    jQuery(document).ready(function ($) {
      // Hero slider
      $('.az-hero-slider').owlCarousel({
        items: 1, loop: true, autoplay: true, autoplayTimeout: 5000,
        animateOut: 'fadeOut', nav: true, dots: true, autoplayHoverPause: true,
        navText: ['<span>&#8249;</span>','<span>&#8250;</span>']
      });
      // Brand carousel
      $('.az-brand-carousel').owlCarousel({
        loop: true, autoplay: true, autoplayTimeout: 3000,
        autoplayHoverPause: true, nav: false, dots: false,
        responsive: { 0:{items:2}, 480:{items:3}, 768:{items:4}, 1024:{items:6} }
      });
      // Related products
      $('.az-related-carousel').owlCarousel({
        loop: false, nav: true, dots: false,
        navText: ['&#8249;','&#8250;'],
        responsive: { 0:{items:1}, 480:{items:2}, 768:{items:3}, 1024:{items:4} }
      });
    });
  }

  /* ──────────────────────────────────────────────────────
     Catalog: grid/list view toggle
  ────────────────────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-view]');
    if (!btn) return;
    var view = btn.dataset.view;
    var grid = document.getElementById('az-products-grid');
    if (!grid) return;
    grid.className = grid.className.replace(/\baz-shop-grid-\S+/g, '').trim();
    grid.classList.add(view === 'list' ? 'az-shop-list' : 'az-shop-grid-4');
    document.querySelectorAll('[data-view]').forEach(function (b) {
      b.classList.toggle('active', b.dataset.view === view);
    });
    setCookie('catalog_view', view, 30);
  });

  /* ──────────────────────────────────────────────────────
     Helpers
  ────────────────────────────────────────────────────── */
  function escHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function getCookie(name) {
    var m = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
    return m ? decodeURIComponent(m.pop()) : '';
  }

  function setCookie(name, value, days) {
    var d = new Date();
    d.setTime(d.getTime() + days * 864e5);
    document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + d.toUTCString() + ';path=/';
  }

  /* Initial cart badge count */
  fetch('/api/cart.php')
    .then(function (r) { return r.json(); })
    .then(function (data) { if (data.count !== undefined) updateCartBadge(data.count); })
    .catch(function () {});

})();
