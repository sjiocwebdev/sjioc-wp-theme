<?php
/**
 * Template Name: Worship & Services Page
 */
get_header();
?>
<div class="page-hero"><div class="container"><h1>Worship &amp; Services</h1><p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › Worship &amp; Services</p></div></div>
<div class="bg-cream"><div class="sec container">
  <div class="tc" style="margin-bottom:52px">
    <span class="stag">Sacred Liturgy</span><h2 class="stitle">Our Worship &amp; Services</h2><div class="divider"></div>
    <p class="slead">Experience ancient Orthodox Christian worship — beautiful, timeless, and alive every Sunday in Drexel Hill.</p>
  </div>
  <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:54px;align-items:start">
    <div>
      <div style="background:var(--ww);border:1px solid var(--border);padding:34px;margin-bottom:22px">
        <h3 style="font-family:'Playfair Display',serif;color:var(--cr);font-size:1.4rem;margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--border)">📅 Worship Schedule</h3>
        <?php
        $sched = array_map(fn($wt) => [$wt['label'], $wt['time']], sjioc_get_worship_times());
        foreach ($sched as $s): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid rgba(201,168,76,.12)">
          <span style="display:flex;align-items:center;gap:9px;font-weight:600;color:var(--tm)"><span style="width:8px;height:8px;border-radius:50%;background:var(--go);display:inline-block;flex-shrink:0"></span><?php echo esc_html($s[0]); ?></span>
          <span style="color:var(--cr);font-weight:700;font-size:.9rem;white-space:nowrap"><?php echo esc_html($s[1]); ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <img src="https://sjioc.org/images/20250419_123136.jpg" alt="Church worship" style="width:100%;height:220px;object-fit:cover;border:3px solid var(--border)" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1548625149-720754956904?w=900&q=70'">
    </div>
    <div>
      <h3 style="font-family:'Playfair Display',serif;color:var(--cr);font-size:1.45rem;margin-bottom:16px">Feast of the Patron Saint</h3>
      <div class="feast-patron-layout">
        <div class="feast-patron-text">
          <h4 style="font-family:'Playfair Display',serif;color:var(--cr);font-size:1.15rem;margin-bottom:10px">St. John the Baptist</h4>
          <p style="color:var(--tl);line-height:1.82;margin-bottom:14px;font-size:.96rem">In the Malankara Orthodox liturgical calendar, the martyrdom of St. John the Baptist is commemorated on January 7th. Since this falls during the winter season, our parish celebrates the feast on the last Saturday and Sunday of August each year.</p>
          <p style="color:var(--tl);line-height:1.82;margin-bottom:14px;font-size:.96rem">As part of the celebration, we arrange two days of Bible Convention and Gospel Choir, preparing our hearts to honor the Forerunner of Christ.</p>
          <p style="color:var(--tl);line-height:1.82;margin-bottom:18px;font-size:.96rem">Our community continues to witness the powerful intercession of St. John the Baptist in our daily lives, and this grace is beautifully reflected in the joy, devotion, and participation we experience during our annual feast.</p>
          <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="btn btn-cr">Contact for Details</a>
        </div>
        <?php
        $patron_icon_id  = get_theme_mod('sjioc_patron_saint_icon');
        $patron_icon_url = $patron_icon_id ? wp_get_attachment_image_url($patron_icon_id, 'medium') : '';
        ?>
        <?php if ($patron_icon_url): ?>
        <div class="feast-patron-icon">
          <img src="<?php echo esc_url($patron_icon_url); ?>" alt="St. John the Baptist" loading="lazy">
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
  <div class="entry-content" style="margin-top:3rem"><?php the_content(); ?></div>
  <?php endwhile; endif; ?>
</div></div>
<?php sjioc_footer(); get_footer(); ?>
