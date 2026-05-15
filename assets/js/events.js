/* global SJIOC_EVENTS */
(function () {
  'use strict';

  if (typeof SJIOC_EVENTS === 'undefined') return;

  var allEvents   = [];
  var calYear     = new Date().getFullYear();
  var calMonth    = new Date().getMonth();  // 0-indexed
  var currentView = localStorage.getItem('sjioc_ev_view') || 'calendar';

  var DAY    = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
  var MFULL  = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  var MSHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  /* ── Bootstrap ──────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    fetch(SJIOC_EVENTS.restUrl + '?months=6', {
      headers: { 'X-WP-Nonce': SJIOC_EVENTS.nonce }
    })
    .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
    .then(function (data) {
      document.getElementById('ev-loading').style.display = 'none';
      allEvents = Array.isArray(data) ? data : [];
      setView(currentView, false);
    })
    .catch(function () {
      document.getElementById('ev-loading').style.display = 'none';
      document.getElementById('ev-error').style.display   = '';
    });

    // View toggle
    document.getElementById('ev-view-bar').addEventListener('click', function (e) {
      var btn = e.target.closest('.ev-view-btn');
      if (btn) setView(btn.dataset.view, true);
    });

    // Month navigation
    document.getElementById('ev-cal-prev').addEventListener('click', function () { shiftMonth(-1); });
    document.getElementById('ev-cal-next').addEventListener('click', function () { shiftMonth(1); });

    // Calendar grid delegation (parent persists across re-renders)
    document.getElementById('ev-calendar-view').addEventListener('click', function (e) {
      var label = e.target.closest('.ev-cal-event-label');
      if (label) { openById(label.dataset.evid); return; }
      var more = e.target.closest('.ev-cal-more');
      if (more) switchToListForDate(more.dataset.date);
    });
    document.getElementById('ev-calendar-view').addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      var label = e.target.closest('.ev-cal-event-label');
      if (label) openById(label.dataset.evid);
    });

    // List grid delegation
    document.getElementById('ev-list-view').addEventListener('click', function (e) {
      var card = e.target.closest('.ev-list-card');
      if (card) openById(card.dataset.evid);
    });
    document.getElementById('ev-list-view').addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      var card = e.target.closest('.ev-list-card');
      if (card) openById(card.dataset.evid);
    });

    // Escape closes modal
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') evCloseModal();
    });
  });

  function openById(id) {
    var ev = allEvents.find(function (x) { return x.id === id; });
    if (ev) evOpenModal(ev);
  }

  function switchToListForDate(date) {
    setView('list', false);
    setTimeout(function () {
      var found = document.querySelector('[data-date="' + date + '"]');
      if (found) found.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 80);
  }

  /* ── View management ────────────────────────────────────────── */
  function setView(view, save) {
    currentView = view;
    if (save) localStorage.setItem('sjioc_ev_view', view);
    document.querySelectorAll('.ev-view-btn').forEach(function (b) {
      var active = b.dataset.view === view;
      b.classList.toggle('is-active', active);
      b.setAttribute('aria-pressed', String(active));
    });
    var calEl  = document.getElementById('ev-calendar-view');
    var listEl = document.getElementById('ev-list-view');
    if (view === 'calendar') {
      calEl.style.display  = '';
      listEl.style.display = 'none';
      renderCalendar();
    } else {
      calEl.style.display  = 'none';
      listEl.style.display = '';
      renderList();
    }
  }

  /* ── Calendar ───────────────────────────────────────────────── */
  function shiftMonth(dir) {
    calMonth += dir;
    if (calMonth > 11) { calMonth = 0; calYear++; }
    if (calMonth < 0)  { calMonth = 11; calYear--; }
    renderCalendar();
  }

  function eventsOnDate(y, m, d) {
    var ds = String(y) + '-' + pad2(m + 1) + '-' + pad2(d);
    return allEvents.filter(function (ev) {
      if (!ev.start) return false;
      var sd = ev.start.slice(0, 10);
      if (ev.all_day) {
        var ed = ev.end ? ev.end.slice(0, 10) : sd;
        return ds >= sd && ds < ed;
      }
      return sd === ds;
    });
  }

  function renderCalendar() {
    document.getElementById('ev-cal-month').textContent = MFULL[calMonth] + ' ' + calYear;
    var today = new Date();
    var html  = '';

    DAY.forEach(function (d) {
      html += '<div class="ev-cal-head" role="columnheader">' + d + '</div>';
    });

    var firstDay  = new Date(calYear, calMonth, 1).getDay();
    var daysInMon = new Date(calYear, calMonth + 1, 0).getDate();
    var prevDays  = new Date(calYear, calMonth, 0).getDate();

    // Leading blank cells from previous month
    for (var i = firstDay - 1; i >= 0; i--) {
      html += '<div class="ev-cal-day other-month" role="gridcell"><span class="ev-cal-num">' + (prevDays - i) + '</span></div>';
    }

    // Current month
    for (var d = 1; d <= daysInMon; d++) {
      var isToday = (today.getFullYear() === calYear && today.getMonth() === calMonth && today.getDate() === d);
      var evs     = eventsOnDate(calYear, calMonth, d);
      var ariaLbl = MSHORT[calMonth] + ' ' + d + ', ' + calYear + (evs.length ? ', ' + evs.length + ' event' + (evs.length !== 1 ? 's' : '') : '');
      html += '<div class="ev-cal-day' + (isToday ? ' is-today' : '') + (evs.length ? ' has-events' : '')
            + '" role="gridcell" aria-label="' + esc(ariaLbl) + '">'
            + '<span class="ev-cal-num">' + d + '</span>';
      evs.slice(0, 2).forEach(function (ev) {
        html += '<span class="ev-cal-event-label' + (ev.all_day ? ' is-allday' : '')
              + '" data-evid="' + esc(ev.id) + '" tabindex="0" role="button" aria-label="' + esc(ev.title) + '">'
              + esc(ev.title) + '</span>';
      });
      if (evs.length > 2) {
        html += '<span class="ev-cal-more" data-date="' + String(calYear) + '-' + pad2(calMonth + 1) + '-' + pad2(d) + '">+'
              + (evs.length - 2) + ' more</span>';
      }
      html += '</div>';
    }

    // Trailing blank cells
    var totalCells = firstDay + daysInMon;
    var trailing   = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
    for (var t = 1; t <= trailing; t++) {
      html += '<div class="ev-cal-day other-month" role="gridcell"><span class="ev-cal-num">' + t + '</span></div>';
    }

    document.getElementById('ev-cal-grid').innerHTML = html;
  }

  /* ── List ───────────────────────────────────────────────────── */
  function renderList() {
    var now    = new Date().toISOString().slice(0, 10);
    var future = allEvents.filter(function (ev) { return ev.start && ev.start.slice(0, 10) >= now; });
    var emptyEl = document.getElementById('ev-list-empty');
    var gridEl  = document.getElementById('ev-list-grid');

    emptyEl.style.display = future.length ? 'none' : '';
    if (!future.length) { gridEl.innerHTML = ''; return; }

    gridEl.innerHTML = future.map(function (ev) {
      var ts    = ev.start ? new Date(ev.start) : null;
      var mon   = ts ? MSHORT[ts.getMonth()] : '';
      var day   = ts ? ts.getDate()          : '';
      var yr    = ts ? ts.getFullYear()       : '';
      var time  = (!ev.all_day && ts) ? ts.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : 'All day';
      var rawDesc = ev.description ? ev.description.replace(/<[^>]*>/g, '') : '';
      var desc    = rawDesc.slice(0, 110);
      return '<div class="ev-list-card" data-evid="' + esc(ev.id)
           + '" data-date="' + esc(ev.start ? ev.start.slice(0, 10) : '')
           + '" tabindex="0" role="button" aria-label="' + esc(ev.title) + '">'
           + '<div class="ev-list-date"><span class="ev-mon">' + esc(mon) + '</span>'
           + '<span class="ev-day">' + day + '</span><span class="ev-yr">' + yr + '</span></div>'
           + '<div class="ev-list-body"><h4>' + esc(ev.title) + '</h4>'
           + '<div class="ev-list-meta"><span>&#128336; ' + esc(time) + '</span>'
           + (ev.location ? '<span>&#128205; ' + esc(ev.location) + '</span>' : '') + '</div>'
           + (desc ? '<p>' + esc(desc) + (rawDesc.length > 110 ? '&hellip;' : '') + '</p>' : '')
           + '</div></div>';
    }).join('');
  }

  /* ── Modal ──────────────────────────────────────────────────── */
  function evOpenModal(ev) {
    var ts  = ev.start ? new Date(ev.start) : null;
    document.getElementById('em-mon').textContent   = ts ? MSHORT[ts.getMonth()] : '';
    document.getElementById('em-day').textContent   = ts ? ts.getDate()          : '';
    document.getElementById('em-title').textContent = ev.title || '';

    var timeStr = '';
    if (ts) {
      var dateStr = ts.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
      if (ev.all_day) {
        timeStr = dateStr + ' · All day';
      } else {
        var endTs = ev.end ? new Date(ev.end) : null;
        timeStr = dateStr + ' · ' + ts.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
                + (endTs ? ' – ' + endTs.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : '');
      }
    }
    var timeEl = document.getElementById('em-time');
    timeEl.textContent   = timeStr;
    timeEl.style.display = timeStr ? '' : 'none';

    var locEl = document.getElementById('em-loc');
    if (ev.location) {
      locEl.innerHTML    = '&#128205; ' + esc(ev.location);
      locEl.style.display = '';
    } else {
      locEl.style.display = 'none';
    }

    document.getElementById('em-desc').innerHTML = ev.description
      ? ev.description.replace(/\n/g, '<br>').replace(/<(?!\/?br\b)[^>]*>/gi, '')
      : '';

    var gcalEl = document.getElementById('em-gcal');
    gcalEl.href          = ev.url || '#';
    gcalEl.style.display = ev.url ? '' : 'none';

    var modal = document.getElementById('ev-modal');
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    modal.querySelector('.ev-modal-close').focus();
  }

  window.evCloseModal = function () {
    var modal = document.getElementById('ev-modal');
    if (!modal) return;
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  };

  /* ── Helpers ────────────────────────────────────────────────── */
  function pad2(n) { return String(n).padStart(2, '0'); }
  function esc(s)  {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }
})();
