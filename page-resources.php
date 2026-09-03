<?php
/**
 * Template Name: Resources Page
 */
get_header();
$resources = sjioc_get_resources();
?>
<div class="page-hero">
  <div class="container">
    <h1>Resources</h1>
    <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › <a href="<?php echo esc_url(home_url('/worship-services/')); ?>">Worship &amp; Services</a> › Resources</p>
  </div>
</div>

<div class="bg-cream"><div class="sec container tc">
  <span class="stag">Explore &amp; Connect</span>
  <h2 class="stitle">Helpful Resources</h2>
  <div class="divider"></div>
  <p class="slead">Links to our Diocese, sister parishes, and other resources that support our life of faith beyond our own parish.</p>
</div></div>

<div class="bg-ww"><div class="sec container">
  <?php if ($resources): ?>
  <div class="res-grid">
    <?php foreach ($resources as $r): ?>
    <a class="res-card" href="<?php echo esc_url($r['url']); ?>" target="_blank" rel="noopener noreferrer">
      <div class="res-card-top">
        <?php if ($r['favicon']): ?>
        <span class="res-favicon"><img src="<?php echo esc_url($r['favicon']); ?>" alt="" loading="lazy" width="28" height="28"></span>
        <?php endif; ?>
        <?php if ($r['tag']): ?><span class="res-tag"><?php echo esc_html($r['tag']); ?></span><?php endif; ?>
      </div>
      <h3><?php echo esc_html($r['title']); ?></h3>
      <?php if ($r['desc']): ?><p><?php echo esc_html($r['desc']); ?></p><?php endif; ?>
      <span class="res-visit">Visit Site <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path d="M7 17L17 7M17 7H9M17 7V15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div style="text-align:center;padding:48px 0;color:#888">
    <p style="font-size:1.1rem;margin-bottom:12px">No resources have been added yet.</p>
    <?php if (current_user_can('manage_options')): ?>
    <a href="<?php echo esc_url(admin_url('edit.php?post_type=sjioc_resource')); ?>" class="btn btn-cr">+ Add Resources in Admin</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div></div>

<?php sjioc_footer(); get_footer(); ?>
