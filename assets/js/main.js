/**
 * SJIOC Delaware Valley — Main JavaScript
 * Handles: Navigation, Widget Bar, Panels, Chat, Gallery, Events Filter
 */
(function () {
  'use strict';

  var PANELS = ['contacts', 'celeb', 'chat'];

  /* ─── Sticky Nav Shadow ─── */
  var header = document.getElementById('site-header');
  if (header) {
    window.addEventListener('scroll', function () {
      header.classList.toggle('scrolled', window.scrollY > 20);
    }, { passive: true });
  }

  /* ─── Mobile Menu Toggle ─── */
  var hamBtn = document.getElementById('menuToggle');
  var navMenu = document.getElementById('primary-menu');
  if (hamBtn && navMenu) {
    hamBtn.addEventListener('click', function () {
      var open = navMenu.classList.toggle('is-open');
      hamBtn.classList.toggle('is-open', open);
      hamBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (!hamBtn.contains(e.target) && !navMenu.contains(e.target)) {
        navMenu.classList.remove('is-open');
        hamBtn.classList.remove('is-open');
        hamBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* ─── Scroll Reveal ─── */
  if ('IntersectionObserver' in window) {
    var revealSels = ['.mcard', '.ev-card', '.leader-card', '.vcard', '.gallery-item', '.ev-item', '.mdcard'];
    var allReveal = [];
    revealSels.forEach(function (sel) {
      document.querySelectorAll(sel).forEach(function (el) { allReveal.push(el); });
    });
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    allReveal.forEach(function (el) {
      el.style.opacity = '0';
      el.style.transform = 'translateY(18px)';
      el.style.transition = 'opacity .55s ease, transform .55s ease';
      observer.observe(el);
    });
  }

  /* ─────────────────────────────────────────
     WIDGET BAR PANELS
  ───────────────────────────────────────── */
  window.sjiocTogglePanel = function (name) {
    var panel = document.getElementById('panel-' + name);
    var tab   = document.getElementById('tab-' + name);
    if (!panel || !tab) return;
    var isOpen = panel.classList.contains('is-open');
    sjiocCloseAllPanels();
    if (!isOpen) {
      sjiocPositionPanel(name);
      panel.classList.add('is-open');
      tab.classList.add('is-open');
      tab.setAttribute('aria-expanded', 'true');
      // focus first focusable
      setTimeout(function () {
        var first = panel.querySelector('button, input, a, [tabindex]');
        if (first) first.focus();
      }, 120);
    }
  };

  window.sjiocClosePanel = function (name) {
    var panel = document.getElementById('panel-' + name);
    var tab   = document.getElementById('tab-' + name);
    if (panel) panel.classList.remove('is-open');
    if (tab)   { tab.classList.remove('is-open'); tab.setAttribute('aria-expanded','false'); }
  };

  function sjiocCloseAllPanels() {
    PANELS.forEach(function (n) { window.sjiocClosePanel(n); });
  }

  function sjiocPositionPanel(name) {
    var panel = document.getElementById('panel-' + name);
    var tab   = document.getElementById('tab-' + name);
    var W = window.innerWidth, PW = 360;
    if (!panel || !tab) return;
    if (W <= 560) {
      panel.style.cssText = 'left:0;right:0;width:100%;max-height:70vh';
      return;
    }
    if (name === 'chat') {
      panel.style.right = '0'; panel.style.left = 'auto'; panel.style.width = PW + 'px';
      return;
    }
    var rect = tab.getBoundingClientRect();
    // celeb: offset to right of contacts
    if (name === 'celeb') {
      var cTab = document.getElementById('tab-contacts');
      if (cTab) {
        var cRect = cTab.getBoundingClientRect();
        var left = Math.min(cRect.right + 4, W - PW - 6);
        panel.style.left = Math.max(4, left) + 'px';
      } else {
        panel.style.left = Math.min(rect.left, W - PW - 6) + 'px';
      }
      panel.style.right = 'auto'; panel.style.width = PW + 'px';
      return;
    }
    panel.style.left = Math.max(0, Math.min(rect.left, W - PW - 6)) + 'px';
    panel.style.right = 'auto'; panel.style.width = PW + 'px';
  }

  window.addEventListener('resize', function () {
    PANELS.forEach(function (n) {
      if (document.getElementById('panel-' + n).classList.contains('is-open')) sjiocPositionPanel(n);
    });
  });

  // ESC key closes panels
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      sjiocCloseAllPanels();
      var lb = document.getElementById('sjioc-lightbox');
      if (lb) lb.classList.remove('is-open');
    }
  });

  /* ─── Contacts Filter ─── */
  window.sjiocFilterContacts = function (q) {
    q = (q || '').toLowerCase();
    document.querySelectorAll('#contacts-list .c-item').forEach(function (item) {
      item.style.display = (item.dataset.name || '').includes(q) ? 'flex' : 'none';
    });
  };

  /* ─── Celebrations Filter ─── */
  window.sjiocFilterCeleb = function (type, btn) {
    document.querySelectorAll('.cel-tab').forEach(function (b) { b.classList.remove('is-active'); });
    btn.classList.add('is-active');
    document.querySelectorAll('#celeb-list .cel-row').forEach(function (row) {
      row.style.display = (type === 'all' || row.dataset.t === type) ? 'flex' : 'none';
    });
  };

  /* ─── Add Celebration ─── */
  window.sjiocToggleAddForm = function () {
    var f = document.getElementById('addForm');
    var b = document.getElementById('addToggle');
    if (!f) return;
    var open = f.style.display === 'block';
    f.style.display = open ? 'none' : 'block';
    if (b) b.textContent = open ? '+ Add Birthday / Anniversary' : '✕ Cancel';
  };

  window.sjiocAddCeleb = function () {
    var name = (document.getElementById('af-name') || {}).value.trim();
    var type = (document.getElementById('af-type') || {}).value;
    var day  = (document.getElementById('af-day')  || {}).value;
    var mon  = (document.getElementById('af-mon')  || {}).value;
    if (!name || !day) { sjiocToast('⚠ Please enter a name and day.'); return; }

    var list = document.getElementById('celeb-list');
    var div  = document.createElement('div');
    div.className  = 'cel-row';
    div.dataset.t  = type;
    var icon = type === 'anniv' ? '💍' : '🎂';
    var cls  = type === 'anniv' ? 'anniv' : 'bday';
    var label = type === 'anniv' ? 'Anniversary 💍' : 'Birthday 🎂';
    div.innerHTML =
      '<div class="cel-badge ' + cls + '"><span class="cmon">' + escHtml(mon) + '</span><span class="cday">' + escHtml(day) + '</span></div>' +
      '<div class="cel-info"><span class="cel-type">' + label + '</span><h4>' + escHtml(name) + '</h4><p>Added by you</p></div>' +
      '<button class="cel-wish" onclick="sjiocWishCeleb(\'' + escHtml(name) + '\',\'' + escHtml(type) + '\')">Wish ✉</button>';
    list.appendChild(div);

    document.getElementById('af-name').value = '';
    document.getElementById('af-day').value  = '';
    window.sjiocToggleAddForm();

    var badge = document.getElementById('badge-celeb');
    if (badge) badge.textContent = parseInt(badge.textContent || 0) + 1;
    sjiocToast('✅ ' + name + ' added to celebrations!');
  };

  /* ─── Wish → opens chat ─── */
  window.sjiocWishCeleb = function (name, type) {
    var msg = type === 'anniv' || type === 'anniversary'
      ? 'Happy Wedding Anniversary to ' + name + '! 💍❤️ May God bless your marriage abundantly!'
      : 'Happy Birthday to ' + name + '! 🎂🎉 Wishing you many blessings this year!';
    sjiocClosePanel('celeb');
    setTimeout(function () {
      sjiocTogglePanel('chat');
      setTimeout(function () {
        var input = document.getElementById('chatInput');
        if (input) { input.value = msg; sjiocSendChat(); }
      }, 180);
    }, 120);
  };

  /* ─────────────────────────────────────────
     CHAT ENGINE
  ───────────────────────────────────────── */
  window.sjiocQuickSend = function (text) {
    var qr = document.getElementById('quickReplies');
    if (qr) qr.style.display = 'none';
    var input = document.getElementById('chatInput');
    if (input) input.value = text;
    sjiocSendChat();
  };

  window.sjiocSendChat = function () {
    var input = document.getElementById('chatInput');
    if (!input) return;
    var text = input.value.trim();
    if (!text) return;
    appendMsg(text, 'usr');
    input.value = '';

    // Typing indicator
    var msgs = document.getElementById('chatMessages');
    var ty = document.createElement('div');
    ty.className = 'typing-ind'; ty.id = 'typingInd';
    ty.innerHTML = '<div class="tdot"></div><div class="tdot"></div><div class="tdot"></div>';
    msgs.appendChild(ty);
    msgs.scrollTop = msgs.scrollHeight;

    var data = new FormData();
    data.append('action',  'sjioc_chat');
    data.append('nonce',   (window.sjioData || {}).nonce  || '');
    data.append('message', text);

    var ajax = (window.sjioData || {}).ajaxUrl || '/wp-admin/admin-ajax.php';
    fetch(ajax, { method: 'POST', body: data })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        var ind = document.getElementById('typingInd');
        if (ind) msgs.removeChild(ind);
        var html = (res.success && res.data && res.data.html)
          ? res.data.html
          : 'Sorry, something went wrong. Please call us directly. &#128222;';
        appendMsg(html, 'bot');
      })
      .catch(function () {
        var ind = document.getElementById('typingInd');
        if (ind) msgs.removeChild(ind);
        appendMsg('Network error. Please call us at <strong>' + ((window.sjioData || {}).phone || '(610) 822-0033') + '</strong>.', 'bot');
      });
  };

  function appendMsg(html, who) {
    var msgs = document.getElementById('chatMessages');
    if (!msgs) return;
    var wrap = document.createElement('div');
    wrap.className = 'cmsg ' + who;
    var now = new Date();
    var t = pad(now.getHours()) + ':' + pad(now.getMinutes());
    wrap.innerHTML = '<div class="bubble">' + html + '</div><span class="ctime">' + t + '</span>';
    msgs.appendChild(wrap);
    msgs.scrollTop = msgs.scrollHeight;
  }

  /* ─────────────────────────────────────────
     GALLERY LIGHTBOX
  ───────────────────────────────────────── */
  window.sjiocOpenLightbox = function (src, alt) {
    var lb  = document.getElementById('sjioc-lightbox');
    var img = document.getElementById('lb-img');
    if (!lb || !img) return;
    img.src = src; img.alt = alt || '';
    lb.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    document.getElementById('lb-close').focus();
  };

  window.sjiocCloseLightbox = function (e) {
    if (!e || e.target !== document.getElementById('lb-img')) {
      var lb = document.getElementById('sjioc-lightbox');
      if (lb) lb.classList.remove('is-open');
      document.body.style.overflow = '';
    }
  };

  /* ─────────────────────────────────────────
     FILTER BARS (Events & Gallery)
  ───────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.filter-btn');
    if (!btn) return;
    var bar = btn.closest('.filter-bar');
    if (!bar) return;
    bar.querySelectorAll('.filter-btn').forEach(function (b) { b.classList.remove('is-active'); });
    btn.classList.add('is-active');

    var cat = btn.dataset.cat || btn.dataset.filter;
    // Events grid
    var evGrid = document.getElementById('ev-grid');
    if (evGrid) {
      evGrid.querySelectorAll('.ev-card').forEach(function (c) {
        c.style.display = (cat === 'all' || c.dataset.cat === cat) ? 'flex' : 'none';
      });
    }
    // Gallery grid
    var galGrid = document.getElementById('gallery-grid');
    if (galGrid) {
      galGrid.querySelectorAll('.gallery-item').forEach(function (item) {
        item.style.display = (cat === 'all' || item.dataset.cat === cat) ? '' : 'none';
      });
    }
  });

  /* ─────────────────────────────────────────
     CONTACT FORM (AJAX)
  ───────────────────────────────────────── */
  window.sjiocSubmitForm = function () {
    var fn  = (document.getElementById('cf-fname')   || {value:''}).value.trim();
    var em  = (document.getElementById('cf-email')   || {value:''}).value.trim();
    var msg = (document.getElementById('cf-message') || {value:''}).value.trim();
    if (!fn || !em || !msg) { sjiocToast('⚠ Please fill in your name, email, and message.'); return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { sjiocToast('⚠ Please enter a valid email.'); return; }

    var btn = document.getElementById('cf-submit');
    if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }

    var data = new FormData();
    data.append('action',  'sjioc_contact');
    data.append('nonce',   (window.sjioData || {}).nonce || '');
    data.append('fname',   fn);
    data.append('lname',   (document.getElementById('cf-lname')   || {value:''}).value.trim());
    data.append('email',   em);
    data.append('phone',   (document.getElementById('cf-phone')   || {value:''}).value.trim());
    data.append('subject', (document.getElementById('cf-subject') || {value:''}).value);
    data.append('message', msg);

    var ajax = (window.sjioData || {}).ajaxUrl || '/wp-admin/admin-ajax.php';
    fetch(ajax, { method:'POST', body:data })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          var suc = document.getElementById('cf-success');
          if (suc) suc.style.display = 'block';
          sjiocToast('✅ ' + (res.data.msg || 'Message sent!'));
          ['cf-fname','cf-lname','cf-email','cf-phone','cf-message'].forEach(function(id){
            var el=document.getElementById(id); if(el) el.value='';
          });
          var sub = document.getElementById('cf-subject'); if(sub) sub.value='';
        } else {
          sjiocToast('⚠ ' + ((res.data || {}).msg || 'Error. Please try again.'));
        }
      })
      .catch(function () { sjiocToast('⚠ Network error. Please call us directly.'); })
      .finally(function () {
        if (btn) { btn.disabled = false; btn.textContent = 'Send Message ✉'; }
      });
  };

  /* ─── Toast ─── */
  window.sjiocToast = function (msg) {
    var t = document.getElementById('siteToast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('is-visible');
    setTimeout(function () { t.classList.remove('is-visible'); }, 4200);
  };

  /* ─── Helpers ─── */
  function pad(n) { return n.toString().padStart(2, '0'); }
  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

})();
