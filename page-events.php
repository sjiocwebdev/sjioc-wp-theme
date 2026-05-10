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

    <!-- ── Category filter ── -->
    <div class="filter-bar" role="group" aria-label="Filter events">
      <button class="filter-btn is-active" data-cat="all">All Events</button>
      <button class="filter-btn" data-cat="worship">Worship</button>
      <button class="filter-btn" data-cat="fellowship">Fellowship</button>
      <button class="filter-btn" data-cat="education">Education</button>
      <button class="filter-btn" data-cat="outreach">Outreach</button>
      <button class="filter-btn" data-cat="special">Special</button>
    </div>

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

<?php sjioc_footer(); get_footer(); ?>
