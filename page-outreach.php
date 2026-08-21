<?php
/**
 * Template Name: Outreach Page
 */
get_header();

$programs = get_posts([
    'post_type'      => 'sjioc_outreach',
    'posts_per_page' => -1,
    'meta_key'       => 'outreach_order',
    'orderby'        => 'meta_value_num',
    'order'          => 'ASC',
    'post_status'    => 'publish',
]);

// Build data for rendering + JS JSON
$out_data = [];
foreach ($programs as $p) {
    $out_data[$p->ID] = [
        'title'      => $p->post_title,
        'tag'        => get_post_meta($p->ID, 'outreach_tag',        true) ?: '',
        'img'        => get_the_post_thumbnail_url($p->ID, 'large')        ?: '',
        'intro'      => wp_kses_post(wpautop(wptexturize($p->post_content))),
        'activities' => get_post_meta($p->ID, 'outreach_activities', true) ?: '',
    ];
}
?>
<div class="page-hero">
  <div class="container">
    <h1>Outreach</h1>
    <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › Outreach</p>
  </div>
</div>

<div class="bg-cream"><div class="sec container tc">
  <span class="stag">Serve &amp; Grow</span>
  <h2 class="stitle">Outreach Programs</h2>
  <div class="divider"></div>
  <p class="slead">St. John's serves the wider Delaware Valley community through outreach programs that share Christ's love beyond our parish walls.</p>
</div></div>

<div class="bg-ww"><div class="sec container">
  <?php if ($programs): ?>
  <div class="mdgrid">
    <?php foreach ($programs as $p):
        $d       = $out_data[$p->ID];
        $img     = $d['img'] ?: 'https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=500&q=70';
        $excerpt = wp_trim_words(wp_strip_all_tags($p->post_content), 28, '…') ?: 'Learn more about this program.';
    ?>
    <article class="mdcard">
      <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($p->post_title); ?>" loading="lazy">
      <div class="mdcard-body">
        <?php if ($d['tag']): ?>
        <span class="mcard-tag"><?php echo esc_html($d['tag']); ?></span>
        <?php endif; ?>
        <h3><?php echo esc_html($p->post_title); ?></h3>
        <p><?php echo esc_html($excerpt); ?></p>
        <button class="btn btn-cr" style="font-size:.76rem;padding:9px 20px"
          onclick="sjiocOpenOutreach(<?php echo (int) $p->ID; ?>)">Learn More →</button>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div style="text-align:center;padding:48px 0;color:#888">
    <p style="font-size:1.1rem;margin-bottom:12px">No outreach programs have been added yet.</p>
    <?php if (current_user_can('manage_options')): ?>
    <a href="<?php echo esc_url(admin_url('edit.php?post_type=sjioc_outreach')); ?>" class="btn btn-cr">+ Add Outreach Programs in Admin</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div></div>

<section class="times-band" style="text-align:center">
  <div class="container" style="position:relative">
    <h2 style="font-family:'Playfair Display',serif;color:#fff;font-size:2rem;margin-bottom:12px">Join Us in Outreach</h2>
    <div class="divider"></div>
    <p style="color:rgba(255,255,255,.74);line-height:1.8;margin-bottom:28px;position:relative;max-width:580px;margin-left:auto;margin-right:auto">Every act of service is a reflection of Christ's love. Prayerfully consider how you might help our community outreach programs.</p>
    <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="btn btn-ol">Contact Us to Get Involved</a>
  </div>
</section>

<!-- ═══════════════════════════════════════════════
     OUTREACH DETAIL MODAL
═══════════════════════════════════════════════ -->
<div class="min-modal" id="min-modal" onclick="sjiocCloseOutreach(event)" role="dialog" aria-modal="true" aria-label="Outreach Program Details">
  <div class="min-modal-box">
    <button class="min-close" onclick="sjiocCloseOutreach()" aria-label="Close">&times;</button>
    <div class="min-hero" id="min-hero">
      <div class="min-hero-overlay">
        <span id="min-tag" class="mcard-tag"></span>
        <h2 id="min-title"></h2>
      </div>
    </div>
    <div class="min-body">
      <div id="min-intro" class="min-intro"></div>
      <div id="min-activities" class="min-section" style="display:none">
        <h4 class="min-section-heading">Activities &amp; Programs</h4>
        <div id="min-activities-text" class="min-section-text"></div>
      </div>
    </div>
    <div class="min-footer">
      <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="btn btn-cr">Get Involved →</a>
      <button class="btn btn-ol" onclick="sjiocCloseOutreach()">Close</button>
    </div>
  </div>
</div>

<script>
var SJIOC_OUTREACH = <?php echo wp_json_encode($out_data); ?>;

function sjiocOpenOutreach(id) {
    var m = SJIOC_OUTREACH[id];
    if (!m) return;

    var hero = document.getElementById('min-hero');
    if (m.img) {
        hero.style.backgroundImage = 'url(' + m.img + ')';
        hero.classList.remove('no-img');
    } else {
        hero.style.backgroundImage = 'none';
        hero.classList.add('no-img');
    }

    document.getElementById('min-tag').textContent   = m.tag   || '';
    document.getElementById('min-title').textContent = m.title || '';
    document.getElementById('min-intro').innerHTML   = m.intro || '';

    var actWrap = document.getElementById('min-activities');
    if (m.activities) {
        document.getElementById('min-activities-text').textContent = m.activities;
        actWrap.style.display = '';
    } else { actWrap.style.display = 'none'; }

    var modal = document.getElementById('min-modal');
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    modal.querySelector('.min-close').focus();
}

function sjiocCloseOutreach(e) {
    var modal = document.getElementById('min-modal');
    if (!modal) return;
    if (e && e.target !== modal) return;
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
}
</script>

<?php sjioc_footer(); get_footer(); ?>
