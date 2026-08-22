<?php
/**
 * Template Name: About Section
 * Shared template for Our Parish / Our Diocese / Our Church / Our Vicar.
 * Content is admin-editable via WP Admin → About Sections, so it can be
 * updated (e.g. a new Vicar) without a code change. Falls back to this
 * Page's own title/content if no About Section is linked yet.
 */
get_header();

$page_key = get_post_meta(get_the_ID(), 'sjioc_about_page_key', true);
$section  = $page_key ? sjioc_get_about_section($page_key) : false;

$title   = $section ? $section['title']   : get_the_title();
$content = $section ? $section['content'] : '';
$image   = $section ? $section['image_url'] : '';
$link_label = $section ? $section['link_label'] : '';
$link_url   = $section ? $section['link_url']   : '';
$is_person  = $section ? !empty($section['is_person']) : false;

if (!$section && have_posts()) {
    while (have_posts()) { the_post(); $content = apply_filters('the_content', get_the_content()); }
}
?>
<div class="page-hero">
  <div class="container">
    <h1><?php echo esc_html($title); ?></h1>
    <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › About › <?php echo esc_html($title); ?></p>
  </div>
</div>

<div class="bg-cream"><div class="sec container">
  <div class="about-grid<?php echo $image ? '' : ' about-grid-noimg'; ?>">
    <div>
      <div class="entry-content"><?php echo wp_kses_post($content); ?></div>
      <?php if ($link_url): ?>
      <br><a href="<?php echo esc_url($link_url); ?>" class="btn btn-cr" target="_blank" rel="noopener"><?php echo esc_html($link_label ?: 'Learn More'); ?> →</a>
      <?php endif; ?>
    </div>
    <?php if ($image): ?>
    <div class="about-img<?php echo $is_person ? ' is-person' : ''; ?>">
      <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
    </div>
    <?php endif; ?>
  </div>
</div></div>

<?php sjioc_footer(); get_footer(); ?>
