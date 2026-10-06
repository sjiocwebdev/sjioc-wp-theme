<?php
/**
 * Template Name: Lectionary Page
 */
defined('ABSPATH') || exit;

get_header();

$lect  = sjioc_get_lectionary();
$today = current_time('Y-m-d');
$years = array_values(array_unique(array_map(fn($d) => substr($d, 0, 4), array_keys($lect))));

$next = null;
foreach (array_keys($lect) as $d) {
    if ($d >= $today) { $next = $d; break; }
}
$year = isset($_GET['yr']) ? (string) absint($_GET['yr']) : '';
if (!in_array($year, $years, true)) {
    $year = $next ? substr($next, 0, 4) : (end($years) ?: '');
}
$days   = array_filter($lect, fn($d) => substr($d, 0, 4) === $year, ARRAY_FILTER_USE_KEY);
$credit = get_option('sjioc_lect_credit', '');
?>
<div class="page-hero">
  <div class="container">
    <h1>Lectionary</h1>
    <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › Lectionary</p>
  </div>
</div>

<div class="bg-cream"><div class="sec container tc">
  <span class="stag">Scripture Readings</span>
  <h2 class="stitle">Indian Orthodox Lectionary<?php echo $year ? ' ' . esc_html($year) : ''; ?></h2>
  <div class="divider"></div>
  <p class="slead">Readings for Sundays and feast days. Tap a reading to open it in English or Malayalam.</p>
  <?php if (count($years) > 1 || ($next && isset($days[$next]))) : ?>
  <div class="lect-nav">
    <?php foreach ($years as $y) : ?>
      <a class="lect-year<?php echo $y === $year ? ' is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg('yr', $y, get_permalink())); ?>"><?php echo esc_html($y); ?></a>
    <?php endforeach; ?>
    <?php if ($next && isset($days[$next])) : ?>
      <a class="btn btn-cr lect-jump" href="#now">Upcoming Readings</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div></div>

<div class="bg-ww"><div class="sec container lect-wrap">
  <?php if ($days) :
    $month = '';
    foreach ($days as $date => $day) :
      $ts = strtotime($date);
      if (date('F', $ts) !== $month) :
        $month = date('F', $ts); ?>
        <h3 class="lect-month"><?php echo esc_html($month); ?></h3>
      <?php endif; ?>
      <article class="lect-day<?php echo $date === $next ? ' is-next' : ''; ?><?php echo $date < $today ? ' is-past' : ''; ?>"<?php echo $date === $next ? ' id="now"' : ''; ?>>
        <div class="lect-date">
          <span class="lect-dow"><?php echo esc_html(date('D', $ts)); ?></span>
          <span class="lect-dnum"><?php echo esc_html(date('j', $ts)); ?></span>
          <span class="lect-mon"><?php echo esc_html(date('M', $ts)); ?></span>
        </div>
        <div class="lect-body">
          <?php if ($date === $next) : ?><span class="lect-badge">Upcoming</span><?php endif; ?>
          <h4><?php echo esc_html($day['title']); ?></h4>
          <?php if ($day['note']) : ?><p class="lect-note"><?php echo esc_html($day['note']); ?></p><?php endif; ?>
          <div class="lect-sections">
            <?php foreach (SJIOC_LECT_SECTIONS as $key => $label) :
              if (empty($day[$key])) continue; ?>
              <div class="lect-sec">
                <h5><?php echo esc_html($label); ?></h5>
                <ul>
                  <?php foreach ($day[$key] as $ref) :
                    $links = sjioc_lect_links($ref); ?>
                    <li>
                      <span class="lect-ref"><?php echo esc_html($ref); ?></span>
                      <?php if ($links) : ?>
                        <a href="<?php echo esc_url($links['en']); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($ref . ' in English'); ?>">English</a>
                        <?php if ($links['ml']) : ?>
                          <a href="<?php echo esc_url($links['ml']); ?>" target="_blank" rel="noopener noreferrer" lang="ml" aria-label="<?php echo esc_attr($ref . ' in Malayalam'); ?>">മലയാളം</a>
                        <?php endif; ?>
                      <?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if ($credit) : ?><p class="lect-credit"><?php echo esc_html($credit); ?></p><?php endif; ?>
  <?php else : ?>
    <p class="lect-empty">This year's lectionary is coming soon.</p>
    <?php if (current_user_can('manage_options')) : ?>
      <p class="tc"><a href="<?php echo esc_url(admin_url('admin.php?page=sjioc-lectionary')); ?>" class="btn btn-cr">Upload Lectionary in Admin</a></p>
    <?php endif; ?>
  <?php endif; ?>
</div></div>

<?php sjioc_footer(); get_footer(); ?>
