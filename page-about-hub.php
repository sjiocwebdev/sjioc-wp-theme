<?php
/**
 * Template Name: About Us Hub
 * Landing page for the About dropdown. Intro text is admin-editable via
 * WP Admin → About Sections → About Us. Card links resolve to whichever
 * real pages are assigned the corresponding About templates.
 */
get_header();

$section = sjioc_get_about_section('about-us');
$title   = $section ? $section['title']   : 'About Us';
$content = $section ? $section['content'] : '';
$image   = $section ? $section['image_url'] : '';

$nav = sjioc_get_about_nav_map();
$cards = [
    'our-parish'  => ['label' => 'Our Parish',  'desc' => 'Our story, mission, and community.'],
    'our-diocese' => ['label' => 'Our Diocese', 'desc' => 'The North East American Diocese.'],
    'our-church'  => ['label' => 'Our Church',  'desc' => 'The Malankara Orthodox Syrian Church.'],
    'our-vicar'   => ['label' => 'Our Vicar',   'desc' => 'Meet our parish priest.'],
    'leadership'  => ['label' => 'Leadership',  'desc' => 'Our Trustee, Secretary &amp; officers.'],
    'committees'  => ['label' => 'Committees',  'desc' => 'Parish administration &amp; organizations.'],
    'our-history' => ['label' => 'Our History', 'desc' => 'Milestones since our founding in 2006.'],
];
?>
<div class="page-hero">
  <div class="container">
    <h1><?php echo esc_html($title); ?></h1>
    <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › About</p>
  </div>
</div>

<div class="bg-cream"><div class="sec container">
  <div class="about-grid<?php echo $image ? '' : ' about-grid-noimg'; ?>">
    <div class="entry-content"><?php echo wp_kses_post($content); ?></div>
    <?php if ($image): ?>
    <div class="about-img">
      <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
    </div>
    <?php endif; ?>
  </div>
</div></div>

<div class="bg-ww"><div class="sec container">
  <div class="about-hub-grid">
    <?php foreach ($cards as $key => $c):
      $url = $nav[$key] ?? '';
      if (!$url) continue; // page not built/assigned yet — skip rather than link nowhere
    ?>
    <a class="about-hub-card" href="<?php echo esc_url($url); ?>">
      <h3><?php echo esc_html($c['label']); ?></h3>
      <p><?php echo wp_kses($c['desc'], ['strong' => [], 'em' => []]); ?></p>
      <span class="about-hub-link">Learn More →</span>
    </a>
    <?php endforeach; ?>
  </div>
</div></div>

<?php sjioc_footer(); get_footer(); ?>
