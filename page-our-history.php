<?php
/**
 * Template Name: Our History
 * Renders the sjioc_milestone CPT as a timeline. Content is edited via
 * WP Admin → Parish Milestones, not this file.
 */
get_header();
$milestones     = sjioc_get_milestones();
$vicar_timeline = sjioc_get_vicar_history();
?>
<div class="page-hero">
  <div class="container">
    <h1>Our History</h1>
    <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › <a href="<?php echo esc_url(sjioc_get_about_hub_url()); ?>">About</a> › Our History</p>
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

<?php if ($vicar_timeline): ?>
<div class="bg-cream"><div class="sec container tc">
  <span class="stag">Parish Leadership</span>
  <h2 class="stitle">Vicar Leadership Timeline</h2>
  <div class="divider"></div>
  <div style="max-width:560px;margin:0 auto;text-align:left">
    <?php sjioc_render_vicar_history_timeline($vicar_timeline); ?>
  </div>
</div></div>
<?php endif; ?>

<?php sjioc_footer(); get_footer(); ?>
