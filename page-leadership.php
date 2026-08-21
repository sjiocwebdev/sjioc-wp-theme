<?php
/**
 * Template Name: Leadership
 * Renders the sjioc_office CPT (group: leadership / joint-bearers).
 * Content is edited via WP Admin → Office Bearers, not this file.
 */
get_header();
?>
<div class="page-hero">
  <div class="container">
    <h1>Our Leadership</h1>
    <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › About › Leadership</p>
  </div>
</div>

<div class="bg-cream"><div class="sec container tc">
  <span class="stag">Meet the Team</span>
  <h2 class="stitle">Our Leadership</h2>
  <div class="divider"></div>
  <p class="slead">Dedicated servants of God guiding our parish with wisdom, love, and pastoral care.</p>

  <?php if (! sjioc_render_office_leadership()): ?>
  <div class="leadership-grid">
    <div class="leader-card">
      <img class="leader-avatar" src="https://sjioc.org/images/TojoBaby-1710825551.png" alt="Rev. Fr. Tojo Baby" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1560250097-0b93528c311a?w=250&q=70'">
      <h3>Rev. Fr. Tojo Baby</h3><span class="leader-role">Vicar — SJIOC Delaware Valley</span>
      <p>Leads our parish with deep pastoral care, presiding at every Holy Qurbana and sacramental celebration.</p>
    </div>
    <div class="leader-card">
      <img class="leader-avatar" src="https://sjioc.org/images/image_f2df599c.png" alt="Mr. Tijo Joseph" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=250&q=70'">
      <h3>Mr. Tijo Joseph</h3><span class="leader-role">Trustee</span>
      <p>Oversees the stewardship of our church's resources and community well-being with faithful dedication.</p>
    </div>
    <div class="leader-card">
      <img class="leader-avatar" src="https://sjioc.org/images/image_edb7e3d.png" alt="Mr. Tom Chacko" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=250&q=70'">
      <h3>Mr. Tom Chacko</h3><span class="leader-role">Secretary</span>
      <p>Coordinates the administrative life of our parish, keeping our community organized and connected.</p>
    </div>
  </div>

  <div class="jt-bearers">
    <div class="jt-pill"><span class="jt-role">Jt. Trustee</span><span class="jt-name">Mr. Subin John</span></div>
    <div class="jt-pill"><span class="jt-role">Jt. Secretary</span><span class="jt-name">Mr. Lijo P. Saji</span></div>
  </div>
  <?php endif; ?>

</div></div>

<?php sjioc_footer(); get_footer(); ?>
