<?php
/**
 * Template Name: Our History
 * Renders the sjioc_milestone CPT as a timeline. Content is edited via
 * WP Admin → Parish Milestones, not this file.
 */
get_header();
$milestones = sjioc_get_milestones();
?>
<div class="page-hero">
  <div class="container">
    <h1>Our History</h1>
    <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › About › Our History</p>
  </div>
</div>

<div class="bg-ww"><div class="sec container tc">
  <span class="stag">Our History</span>
  <h2 class="stitle">Parish Milestones</h2>
  <div class="divider"></div>
  <div class="timeline" style="text-align:left">
    <?php foreach ($milestones as $m): ?>
    <div class="tl-row">
      <div class="tl-year"><span><?php echo esc_html($m['year']); ?></span></div>
      <div class="tl-content">
        <h4><?php echo esc_html($m['title']); ?></h4>
        <div><?php echo wp_kses_post($m['content']); ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div></div>

<?php sjioc_footer(); get_footer(); ?>
