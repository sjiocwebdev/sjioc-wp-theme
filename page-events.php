<?php /* Template Name: Events Page */

define('SJIOC_EV_MONTHS_PAST',     0);  // initially visible past months
define('SJIOC_EV_MONTHS_FUTURE',   0);  // initially visible future months
define('SJIOC_EV_MONTHS_MAX',     12);  // max pre-loaded in each direction

$today      = date('Y-m-d');
$this_month = date('Y-m');

// Query the full pre-loaded window
$start_date = date('Y-m-01', strtotime('-' . SJIOC_EV_MONTHS_MAX . ' months'));
$end_date   = date('Y-m-t',  strtotime('+' . SJIOC_EV_MONTHS_MAX . ' months'));

$events_q = new WP_Query([
    'post_type'      => 'sjioc_event',
    'posts_per_page' => -1,
    'meta_key'       => 'event_date',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
    'meta_query'     => [[
        'key'     => 'event_date',
        'value'   => [$start_date, $end_date],
        'compare' => 'BETWEEN',
        'type'    => 'DATE',
    ]],
]);

$grouped = [];
if ($events_q->have_posts()) {
    while ($events_q->have_posts()) {
        $events_q->the_post();
        $p  = get_post();
        $d  = get_post_meta($p->ID, 'event_date', true);
        $ym = $d ? substr($d, 0, 7) : '0000-00';
        $grouped[$ym][] = $p;
    }
    wp_reset_postdata();
}

// ── CALENDAR FEATURE: flat event list for JS calendar ──────────────────
$cal_events = [];
foreach ($grouped as $_ym => $_posts) {
    foreach ($_posts as $_p) {
        $cal_events[] = [
            'date'   => get_post_meta($_p->ID, 'event_date',     true) ?: '',
            'title'  => $_p->post_title,
            'cat'    => get_post_meta($_p->ID, 'event_category', true) ?: '',
            'time'   => get_post_meta($_p->ID, 'event_time',     true) ?: '',
            'end'    => get_post_meta($_p->ID, 'event_end_time', true) ?: '',
            'loc'    => get_post_meta($_p->ID, 'event_location', true) ?: '',
            'allday' => get_post_meta($_p->ID, 'event_all_day',  true) === '1',
        ];
    }
}
// ── END CALENDAR FEATURE ───────────────────────────────────────────────

// Build full month list with offset metadata
$month_list = [];
for ($i = -SJIOC_EV_MONTHS_MAX; $i <= SJIOC_EV_MONTHS_MAX; $i++) {
    $ts  = strtotime(date('Y-m-01') . " $i months");
    $ym  = date('Y-m', $ts);
    $hidden = ($i < -SJIOC_EV_MONTHS_PAST) || ($i > SJIOC_EV_MONTHS_FUTURE);
    $dir    = $i < 0 ? 'past' : ($i > 0 ? 'future' : 'current');
    $month_list[] = ['ym' => $ym, 'ts' => $ts, 'offset' => $i, 'hidden' => $hidden, 'dir' => $dir];
}

function sjioc_ev_card(WP_Post $p, bool $past = false): void {
    $d       = get_post_meta($p->ID, 'event_date',     true);
    $t       = get_post_meta($p->ID, 'event_time',     true);
    $end     = get_post_meta($p->ID, 'event_end_time', true);
    $allday  = get_post_meta($p->ID, 'event_all_day',  true);
    $loc     = get_post_meta($p->ID, 'event_location', true);
    $cat     = get_post_meta($p->ID, 'event_category', true);
    $ts      = $d ? strtotime($d) : null;
    $mon     = $ts ? strtoupper(date('M', $ts)) : 'TBD';
    $day     = $ts ? date('j', $ts)             : '—';
    $yr      = $ts ? date('Y', $ts)             : '';
    $tlabel  = $allday === '1' ? 'All Day' : ($t ? ($end ? $t . ' – ' . $end : $t) : '');
    $excerpt = $p->post_excerpt ?: wp_trim_words(wp_strip_all_tags($p->post_content), 16);
    ?>
    <article class="ev-card<?php echo $past ? ' ev-card--past' : ''; ?>"
             data-cat="<?php echo esc_attr($cat); ?>">
      <div class="ecard-date">
        <span class="ecd-mon"><?php echo esc_html($mon); ?></span>
        <span class="ecd-day"><?php echo esc_html($day); ?></span>
        <?php if ($yr): ?><span class="ecd-yr"><?php echo esc_html($yr); ?></span><?php endif; ?>
      </div>
      <div class="ecard-body">
        <?php if ($cat): ?><span class="ecard-cat"><?php echo esc_html($cat); ?></span><?php endif; ?>
        <h3><?php echo esc_html($p->post_title); ?></h3>
        <?php if ($excerpt): ?><p><?php echo esc_html($excerpt); ?></p><?php endif; ?>
        <div class="ecard-meta">
          <?php if ($tlabel): ?><span>🕐 <?php echo esc_html($tlabel); ?></span><?php endif; ?>
          <?php if ($loc): ?><span>📍 <?php echo esc_html($loc); ?></span><?php endif; ?>
        </div>
      </div>
    </article>
    <?php
}

get_header();
?>

<div class="page-hero">
  <div class="container">
    <h1>Events</h1>
    <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › Events</p>
  </div>
</div>

<div class="bg-cream">
  <div class="sec container">

    <div class="tc" style="margin-bottom:36px">
      <span class="stag">Parish Life</span>
      <h2 class="stitle">Parish Events</h2>
      <div class="divider"></div>
      <p class="slead">Stay connected with the vibrant life of our parish through worship, fellowship, and service events.</p>
    </div>

    <!-- ── CALENDAR FEATURE: view toggle ── -->
    <div class="ev-view-toggle" id="ev-view-toggle" role="group" aria-label="View mode">
      <button class="ev-vtab is-active" id="btn-list-view">≡ List</button>
      <button class="ev-vtab" id="btn-cal-view">📅 Calendar</button>
    </div>
    <!-- ── END CALENDAR FEATURE ── -->

    <!-- ── Category filter ── -->
    <div class="filter-bar" role="group" aria-label="Filter events">
      <button class="filter-btn is-active" data-cat="all">All Events</button>
      <button class="filter-btn" data-cat="worship">Worship</button>
      <button class="filter-btn" data-cat="fellowship">Fellowship</button>
      <button class="filter-btn" data-cat="education">Education</button>
      <button class="filter-btn" data-cat="outreach">Outreach</button>
      <button class="filter-btn" data-cat="special">Special</button>
    </div>

    <!-- ── CALENDAR FEATURE: calendar grid container ── -->
    <div id="ev-calendar-wrap" style="display:none" aria-hidden="true">
      <div class="ev-cal-nav">
        <button class="ev-cal-arrow" id="ev-cal-prev" aria-label="Previous month">&#8249;</button>
        <h3 class="ev-cal-heading" id="ev-cal-heading"></h3>
        <button class="ev-cal-arrow" id="ev-cal-next" aria-label="Next month">&#8250;</button>
        <button class="ev-cal-today-btn" id="ev-cal-today">Today</button>
      </div>
      <div class="ev-cal-grid" id="ev-cal-grid" role="grid" aria-label="Event calendar"></div>
      <div id="ev-cal-day-panel" class="ev-cal-day-panel" style="display:none">
        <h4 class="ev-cal-day-title" id="ev-cal-day-title"></h4>
        <div id="ev-cal-day-list"></div>
      </div>
    </div>
    <!-- ── END CALENDAR FEATURE ── -->

    <!-- ── Expand: older months ── -->
    <div class="ev-expand-wrap" id="ev-expand-past">
      <button class="ev-expand-btn" id="btn-older">← Older months</button>
    </div>

    <!-- ── Scrolling timeline ── -->
    <div class="ev-timeline" id="ev-timeline">
      <?php foreach ($month_list as $m):
        $ym      = $m['ym'];
        $ts      = $m['ts'];
        $offset  = $m['offset'];
        $is_past = $offset < 0;
        $is_curr = $offset === 0;
        $hidden  = $m['hidden'];
        $label   = date('F Y', $ts);

        $group_class = 'ev-month-group';
        if ($is_past) $group_class .= ' ev-month--past';
        if ($is_curr) $group_class .= ' ev-month--current';
        $style = $hidden ? ' style="display:none"' : '';
      ?>
      <div class="<?php echo $group_class; ?>"
           id="ev-month-<?php echo esc_attr($ym); ?>"
           data-offset="<?php echo $offset; ?>"
           data-dir="<?php echo $m['dir']; ?>"
           <?php echo $style; ?>>
        <div class="ev-month-header">
          <span class="ev-month-label"><?php echo esc_html($label); ?></span>
          <?php if ($is_curr): ?>
            <span class="ev-month-badge">This Month</span>
          <?php endif; ?>
          <span class="ev-month-line"></span>
        </div>
        <?php if (!empty($grouped[$ym])): ?>
        <div class="ev-month-grid">
          <?php foreach ($grouped[$ym] as $p): sjioc_ev_card($p, $is_past); endforeach; ?>
        </div>
        <?php else: ?>
        <p class="ev-month-empty">
          <?php echo $is_past ? 'No events recorded.' : 'Nothing scheduled yet — check back soon.'; ?>
        </p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ── Expand: future months ── -->
    <div class="ev-expand-wrap" id="ev-expand-future">
      <button class="ev-expand-btn" id="btn-future">More months →</button>
    </div>

  </div>
</div>

<section class="times-band" style="text-align:center">
  <div class="container" style="position:relative">
    <h2 style="font-family:'Playfair Display',serif;color:#fff;font-size:1.9rem;margin-bottom:12px">Host a Parish Event?</h2>
    <div class="divider"></div>
    <p style="color:rgba(255,255,255,.74);margin-bottom:28px;position:relative;max-width:520px;margin-left:auto;margin-right:auto">
      Contact our parish office to schedule events at <?php echo esc_html(sjioc_address()); ?>.
    </p>
    <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="btn btn-ol">Contact the Parish Office</a>
  </div>
</section>

<style>
/* ── Timeline layout ── */
.ev-timeline { margin-top: 8px; }
.ev-month-group { margin-bottom: 48px; }

/* Month header row */
.ev-month-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 20px;
}
.ev-month-label {
  font-family: 'Playfair Display', serif;
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--cr);
  white-space: nowrap;
  flex-shrink: 0;
}
.ev-month-badge {
  background: var(--go);
  color: #fff;
  font-size: .62rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  padding: 3px 10px;
  border-radius: 20px;
  flex-shrink: 0;
}
.ev-month-line {
  flex: 1;
  height: 1px;
  background: var(--border);
}

/* Month card grid */
.ev-month-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 22px;
}
@media (max-width: 640px) {
  .ev-month-grid { grid-template-columns: 1fr; }
}

/* Past month treatment */
.ev-month--past .ev-month-label        { color: #999; }
.ev-month--past .ev-month-line         { background: #e0e0e0; }
.ev-month--past .ev-card--past         { opacity: .68; }
.ev-month--past .ev-card--past .ecard-date { background: #9aabb3; }
.ev-month--past .ev-card--past:hover   { opacity: 1; transform: translateY(-2px); }

/* Empty month placeholder */
.ev-month-empty {
  font-size: .82rem;
  color: #bbb;
  font-style: italic;
  padding: 10px 0 4px;
}

/* Expand buttons */
.ev-expand-wrap {
  text-align: center;
  margin: 4px 0 32px;
}
.ev-expand-btn {
  padding: 9px 28px;
  font-size: .74rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  border: 2px solid var(--border);
  background: transparent;
  color: var(--tm);
  cursor: pointer;
  transition: all .22s;
}
.ev-expand-btn:hover {
  border-color: var(--go);
  color: var(--go);
}
.ev-expand-wrap.ev-expand-done { display: none; }

/* Hidden month / filter */
.ev-month-group.ev-hidden { display: none; }

/* Empty state */
.ev-empty {
  text-align: center;
  color: #888;
  padding: 48px 0;
}

/* ── CALENDAR FEATURE styles ─────────────────────────────────── */
.ev-view-toggle {
  display:flex; width:fit-content; margin-bottom:18px;
  border:1px solid var(--border); border-radius:6px; overflow:hidden;
}
.ev-vtab {
  padding:8px 22px; font-size:.76rem; font-weight:700;
  letter-spacing:.07em; text-transform:uppercase;
  background:transparent; border:none; cursor:pointer;
  color:var(--tm); transition:background .18s,color .18s;
}
.ev-vtab.is-active { background:var(--cr); color:#fff; }
.ev-vtab:not(.is-active):hover { background:var(--bg-cream,#fdf8f0); }

.ev-cal-nav {
  display:flex; align-items:center; gap:10px; margin-bottom:14px;
}
.ev-cal-heading {
  flex:1; text-align:center; font-family:'Playfair Display',serif;
  font-size:1.18rem; color:var(--cr); margin:0;
}
.ev-cal-arrow {
  background:none; border:1px solid var(--border); border-radius:5px;
  width:34px; height:34px; font-size:1.4rem; line-height:1;
  cursor:pointer; color:var(--cr); display:flex;
  align-items:center; justify-content:center; transition:all .18s;
}
.ev-cal-arrow:hover { background:var(--cr); color:#fff; border-color:var(--cr); }
.ev-cal-today-btn {
  font-size:.7rem; font-weight:700; text-transform:uppercase;
  letter-spacing:.07em; padding:6px 14px;
  border:1px solid var(--border); border-radius:5px;
  background:none; cursor:pointer; color:var(--tm); transition:all .18s;
}
.ev-cal-today-btn:hover { border-color:var(--go); color:var(--go); }

.ev-cal-grid {
  display:grid; grid-template-columns:repeat(7,1fr); gap:5px;
  margin-bottom:22px;
}
.ev-cal-dow {
  text-align:center; font-size:.65rem; font-weight:700;
  color:#aaa; text-transform:uppercase; letter-spacing:.05em; padding:5px 0;
}
.ev-cal-cell {
  min-height:60px; border-radius:6px; border:1px solid var(--border);
  padding:6px 7px; cursor:pointer; position:relative;
  background:var(--ww); display:flex; flex-direction:column;
  transition:border-color .18s,background .18s;
}
.ev-cal-cell:focus { outline:2px solid var(--cr); outline-offset:1px; }
.ev-cal-empty { background:transparent; border:none; cursor:default; }
.ev-cal-cell:not(.ev-cal-empty):hover { border-color:var(--cr); }
.ev-cal-today-cell { background:#fdf0f0; border-color:var(--cr) !important; }
.ev-cal-selected { background:var(--cr) !important; border-color:var(--cr) !important; }
.ev-cal-selected .ev-cal-daynum { color:#fff !important; }
.ev-cal-daynum { font-size:.84rem; font-weight:600; color:var(--tb); }
.ev-cal-has-events .ev-cal-daynum { color:var(--cr); font-weight:700; }
.ev-cal-dots { display:flex; gap:3px; flex-wrap:wrap; margin-top:4px; }
.ev-cal-dot {
  width:6px; height:6px; border-radius:50%; background:var(--go); flex-shrink:0;
}
.ev-cal-selected .ev-cal-dot { background:rgba(255,255,255,.85); }

.ev-cal-day-panel {
  border-top:2px solid var(--cr); padding-top:20px; margin-top:4px;
  animation:fadeUp .25s ease;
}
@keyframes fadeUp { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }
.ev-cal-day-title {
  font-family:'Playfair Display',serif; font-size:1rem;
  color:var(--cr); margin:0 0 14px;
}
.ev-cal-event-row {
  background:var(--bg-cream,#fdf8f0); border:1px solid var(--border);
  border-radius:8px; padding:12px 16px; margin-bottom:10px;
  display:flex; flex-direction:column; gap:4px;
}
.ev-cal-event-row strong { font-size:.93rem; color:var(--tb); }
.ev-cal-ev-meta { font-size:.78rem; color:var(--tl); }
.ev-cal-no-events { color:#bbb; font-style:italic; font-size:.86rem; padding:6px 0; }

@media(max-width:640px) {
  .ev-cal-cell { min-height:44px; padding:4px 5px; }
  .ev-cal-daynum { font-size:.76rem; }
  .ev-cal-dot { width:5px; height:5px; }
}
/* ── END CALENDAR FEATURE styles ─────────────────────────────── */
</style>

<script>
(function () {
  var activeCat = 'all';
  var thisMonth = '<?php echo $this_month; ?>';
  var STEP      = 3; // months revealed per click
  var MAX       = <?php echo SJIOC_EV_MONTHS_MAX; ?>;

  // Current visible boundaries (offset values)
  var pastShown   = <?php echo SJIOC_EV_MONTHS_PAST; ?>;
  var futureShown = <?php echo SJIOC_EV_MONTHS_FUTURE; ?>;

  var btnOlder  = document.getElementById('btn-older');
  var btnFuture = document.getElementById('btn-future');
  var wrapOlder = document.getElementById('ev-expand-past');
  var wrapFuture = document.getElementById('ev-expand-future');

  function allGroups() {
    return document.querySelectorAll('.ev-month-group');
  }

  // ── Show / hide expand buttons ────────────────────
  function syncExpandBtns() {
    if (pastShown   >= MAX) wrapOlder.classList.add('ev-expand-done');
    else                    wrapOlder.classList.remove('ev-expand-done');
    if (futureShown >= MAX) wrapFuture.classList.add('ev-expand-done');
    else                    wrapFuture.classList.remove('ev-expand-done');
  }

  // ── Reveal older months ───────────────────────────
  btnOlder.addEventListener('click', function () {
    var newLimit = Math.min(pastShown + STEP, MAX);
    allGroups().forEach(function (g) {
      var off = parseInt(g.dataset.offset, 10);
      if (off < 0 && Math.abs(off) <= newLimit) {
        g.style.display = '';
        applyFilterToGroup(g);
      }
    });
    pastShown = newLimit;
    syncExpandBtns();
    // Scroll to the newly revealed top group
    var topGroup = document.querySelector(
      '.ev-month-group[data-offset="-' + newLimit + '"]'
    );
    if (topGroup) {
      window.scrollTo({
        top: topGroup.getBoundingClientRect().top + window.pageYOffset - 100,
        behavior: 'smooth'
      });
    }
  });

  // ── Reveal future months ──────────────────────────
  btnFuture.addEventListener('click', function () {
    var newLimit = Math.min(futureShown + STEP, MAX);
    allGroups().forEach(function (g) {
      var off = parseInt(g.dataset.offset, 10);
      if (off > 0 && off <= newLimit) {
        g.style.display = '';
        applyFilterToGroup(g);
      }
    });
    futureShown = newLimit;
    syncExpandBtns();
  });

  // ── Category filter ───────────────────────────────
  document.querySelectorAll('.filter-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.filter-btn').forEach(function (b) { b.classList.remove('is-active'); });
      this.classList.add('is-active');
      activeCat = this.dataset.cat;
      allGroups().forEach(function (g) { applyFilterToGroup(g); });
    });
  });

  function applyFilterToGroup(group) {
    // Don't touch groups still hidden by the expansion system
    if (group.style.display === 'none') {
      var off = parseInt(group.dataset.offset, 10);
      var hiddenByExpand = (off < 0 && Math.abs(off) > pastShown) ||
                           (off > 0 && off > futureShown);
      if (hiddenByExpand) return;
    }
    var cards = group.querySelectorAll('.ev-card');
    if (cards.length === 0) { group.classList.remove('ev-hidden'); return; }
    var visible = 0;
    cards.forEach(function (card) {
      var match = activeCat === 'all' || card.dataset.cat === activeCat;
      card.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    group.classList.toggle('ev-hidden', visible === 0);
  }

  // ── Auto-scroll to current month ─────────────────
  var target = document.getElementById('ev-month-' + thisMonth) ||
               document.querySelector('.ev-month-group:not(.ev-month--past)');
  if (target) {
    setTimeout(function () {
      window.scrollTo({
        top: target.getBoundingClientRect().top + window.pageYOffset - 100,
        behavior: 'smooth'
      });
    }, 300);
  }

  // ── Init ─────────────────────────────────────────
  syncExpandBtns();
})();
</script>

<!-- ── CALENDAR FEATURE script — remove this block to revert ── -->
<script>
(function () {
  var EVENTS   = <?php echo wp_json_encode(array_values($cal_events)); ?>;
  var today    = new Date();
  var curYear  = today.getFullYear();
  var curMonth = today.getMonth();
  var selDate  = null;
  var MONTHS   = ['January','February','March','April','May','June',
                  'July','August','September','October','November','December'];

  var listView    = document.getElementById('ev-timeline');
  var calWrap     = document.getElementById('ev-calendar-wrap');
  var filterBar   = document.querySelector('.filter-bar[aria-label="Filter events"]');
  var expandPast  = document.getElementById('ev-expand-past');
  var expandFut   = document.getElementById('ev-expand-future');
  var btnList     = document.getElementById('btn-list-view');
  var btnCal      = document.getElementById('btn-cal-view');

  // ── View toggle ─────────────────────────────────────
  function setView(v) {
    var isCal = v === 'cal';
    listView.style.display   = isCal ? 'none' : '';
    filterBar.style.display  = isCal ? 'none' : '';
    expandPast.style.display = isCal ? 'none' : '';
    expandFut.style.display  = isCal ? 'none' : '';
    calWrap.style.display    = isCal ? '' : 'none';
    calWrap.setAttribute('aria-hidden', isCal ? 'false' : 'true');
    btnCal.classList.toggle('is-active', isCal);
    btnList.classList.toggle('is-active', !isCal);
    if (isCal) {
      renderCal(curYear, curMonth);
      history.replaceState(null, '', '#calendar');
    } else {
      clearDayPanel();
      history.replaceState(null, '', location.pathname);
    }
  }
  btnList.addEventListener('click', function () { setView('list'); });
  btnCal.addEventListener('click',  function () { setView('cal'); });

  // ── Build date → events index ────────────────────────
  var idx = {};
  EVENTS.forEach(function (e) {
    if (!e.date) return;
    if (!idx[e.date]) idx[e.date] = [];
    idx[e.date].push(e);
  });

  // ── Render a calendar month ──────────────────────────
  function renderCal(year, month) {
    curYear = year; curMonth = month;
    document.getElementById('ev-cal-heading').textContent = MONTHS[month] + ' ' + year;
    var grid = document.getElementById('ev-cal-grid');
    grid.innerHTML = '';

    // Day-of-week headers
    ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(function (d) {
      var h = document.createElement('div');
      h.className = 'ev-cal-dow'; h.textContent = d;
      grid.appendChild(h);
    });

    var firstDay    = new Date(year, month, 1).getDay();
    var daysInMonth = new Date(year, month + 1, 0).getDate();
    var todayStr    = pad(today.getFullYear(), today.getMonth() + 1, today.getDate());

    for (var i = 0; i < firstDay; i++) {
      var empty = document.createElement('div');
      empty.className = 'ev-cal-cell ev-cal-empty';
      grid.appendChild(empty);
    }
    for (var d = 1; d <= daysInMonth; d++) {
      var ds   = pad(year, month + 1, d);
      var cell = document.createElement('div');
      cell.className = 'ev-cal-cell';
      cell.setAttribute('role', 'gridcell');
      cell.setAttribute('tabindex', '0');
      cell.dataset.date = ds;
      if (ds === todayStr) cell.classList.add('ev-cal-today-cell');
      if (ds === selDate)  cell.classList.add('ev-cal-selected');

      var num = document.createElement('span');
      num.className = 'ev-cal-daynum'; num.textContent = d;
      cell.appendChild(num);

      if (idx[ds] && idx[ds].length) {
        cell.classList.add('ev-cal-has-events');
        var dots = document.createElement('div');
        dots.className = 'ev-cal-dots';
        var n = Math.min(idx[ds].length, 3);
        for (var k = 0; k < n; k++) {
          var dot = document.createElement('span'); dot.className = 'ev-cal-dot'; dots.appendChild(dot);
        }
        cell.appendChild(dots);
      }
      cell.addEventListener('click', (function (s) { return function () { dayClick(s); }; })(ds));
      cell.addEventListener('keydown', (function (s) { return function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); dayClick(s); }
      }; })(ds));
      grid.appendChild(cell);
    }
  }

  function pad(y, m, d) {
    return y + '-' + String(m).padStart(2,'0') + '-' + String(d).padStart(2,'0');
  }

  // ── Day click ────────────────────────────────────────
  function dayClick(ds) {
    selDate = ds;
    document.querySelectorAll('#ev-cal-grid .ev-cal-cell').forEach(function (c) {
      c.classList.toggle('ev-cal-selected', c.dataset.date === ds);
    });
    var evs   = idx[ds] || [];
    var panel = document.getElementById('ev-cal-day-panel');
    var parts = ds.split('-');
    var dt    = new Date(+parts[0], +parts[1] - 1, +parts[2]);
    document.getElementById('ev-cal-day-title').textContent =
      dt.toLocaleDateString('en-US', {weekday:'long',year:'numeric',month:'long',day:'numeric'});

    var list = document.getElementById('ev-cal-day-list');
    if (!evs.length) {
      list.innerHTML = '<p class="ev-cal-no-events">No events on this day.</p>';
    } else {
      list.innerHTML = evs.map(function (e) {
        var tl = e.allday ? 'All Day' : (e.time ? e.time + (e.end ? ' – ' + e.end : '') : '');
        return '<div class="ev-cal-event-row">'
          + (e.cat ? '<span class="ecard-cat" style="font-size:.65rem;margin-bottom:2px">' + esc(e.cat) + '</span>' : '')
          + '<strong>' + esc(e.title) + '</strong>'
          + (tl    ? '<span class="ev-cal-ev-meta">🕐 ' + esc(tl)    + '</span>' : '')
          + (e.loc ? '<span class="ev-cal-ev-meta">📍 ' + esc(e.loc) + '</span>' : '')
          + '</div>';
      }).join('');
    }
    panel.style.display = '';
    panel.scrollIntoView({behavior:'smooth', block:'nearest'});
  }

  function clearDayPanel() {
    selDate = null;
    document.getElementById('ev-cal-day-panel').style.display = 'none';
  }

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Prev / Next / Today ──────────────────────────────
  document.getElementById('ev-cal-prev').addEventListener('click', function () {
    var m = curMonth - 1, y = curYear;
    if (m < 0) { m = 11; y--; }
    renderCal(y, m); clearDayPanel();
  });
  document.getElementById('ev-cal-next').addEventListener('click', function () {
    var m = curMonth + 1, y = curYear;
    if (m > 11) { m = 0; y++; }
    renderCal(y, m); clearDayPanel();
  });
  document.getElementById('ev-cal-today').addEventListener('click', function () {
    renderCal(today.getFullYear(), today.getMonth()); clearDayPanel();
  });

  // ── Auto-activate from #calendar hash (e.g. from home page button) ──
  if (location.hash === '#calendar') { setView('cal'); }
}());
</script>
<!-- ── END CALENDAR FEATURE script ── -->

<?php sjioc_footer(); get_footer(); ?>
