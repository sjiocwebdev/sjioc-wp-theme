<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#ffffff">
<meta name="color-scheme" content="light">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="profile" href="https://gmpg.org/xfn/11">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- ════════════ SKIP LINK ════════════ -->
<a class="screen-reader-text" href="#main-content">Skip to content</a>

<!-- ════════════ SITE HEADER ════════════ -->
<header id="site-header" role="banner">
  <div class="nav-inner">

    <!-- Logo -->
    <?php if (has_custom_logo()): ?>
      <span class="site-logo" aria-label="<?php echo esc_attr(sjioc_name()); ?>">
        <?php the_custom_logo(); ?>
        <span class="logo-name"><?php echo esc_html(sjioc_abbr()); ?></span>
      </span>
    <?php else: ?>
      <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo" rel="home" aria-label="<?php echo esc_attr(sjioc_name()); ?>">
        <svg viewBox="0 0 46 46" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
          <circle cx="23" cy="23" r="21" fill="none" stroke="#C9A84C" stroke-width="1.5"/>
          <line x1="23" y1="4"  x2="23" y2="42" stroke="#C9A84C" stroke-width="2.6"/>
          <line x1="8"  y1="15" x2="38" y2="15" stroke="#C9A84C" stroke-width="2.6"/>
          <line x1="12" y1="25" x2="34" y2="25" stroke="#C9A84C" stroke-width="1.5"/>
          <circle cx="23" cy="23" r="2.6" fill="#C9A84C" opacity=".5"/>
        </svg>
        <div>
          <span class="logo-name"><?php echo esc_html(sjioc_abbr()); ?></span>
        </div>
      </a>
    <?php endif; ?>

    <!-- Hamburger (mobile) -->
    <button class="menu-toggle-btn" id="menuToggle" aria-controls="primary-menu" aria-expanded="false" aria-label="<?php esc_attr_e('Toggle navigation','sjioc'); ?>">
      <span></span><span></span><span></span>
    </button>

    <!-- Primary Nav -->
    <?php
    wp_nav_menu([
        'theme_location' => 'primary',
        'menu_id'        => 'primary-menu',
        'container'      => false,
        'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
        'fallback_cb'    => 'sjioc_primary_nav_fallback',
    ]);
    ?>

    <!-- Phone chip -->
    <a href="tel:<?php echo preg_replace('/\D/', '', sjioc_phone()); ?>" class="nav-phone-chip" aria-label="Call <?php echo esc_attr(sjioc_phone()); ?>">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.86 11a19.79 19.79 0 01-3.07-8.67A2 2 0 012.77 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.91 8.59a16 16 0 006.5 6.5l1.95-1.35a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
      <?php echo esc_html(sjioc_phone()); ?>
    </a>

  </div>
</header>
<!-- /#site-header -->

<main id="main-content" role="main">
