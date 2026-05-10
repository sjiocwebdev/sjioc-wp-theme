<?php
/**
 * Template Name: Ministries Page
 */
get_header();

$ministries = get_posts([
    'post_type'      => 'sjioc_ministry',
    'posts_per_page' => -1,
    'meta_key'       => 'ministry_order',
    'orderby'        => 'meta_value_num',
    'order'          => 'ASC',
    'post_status'    => 'publish',
]);

// Build data for rendering + JS JSON
$min_data = [];
foreach ($ministries as $m) {
    $min_data[$m->ID] = [
        'title'        => $m->post_title,
        'tag'          => get_post_meta($m->ID, 'ministry_tag',             true) ?: '',
        'img'          => get_the_post_thumbnail_url($m->ID, 'large')             ?: '',
        'intro'        => wp_kses_post(wpautop(wptexturize($m->post_content))),
        'activities'   => get_post_meta($m->ID, 'ministry_activities', true) ?: '',
        'roles'        => json_decode(get_post_meta($m->ID, 'ministry_roles', true) ?: '[]', true) ?: [],
        'album_cat'    => get_post_meta($m->ID, 'ministry_album_cat',  true) ?: '',
        'album_name'   => get_post_meta($m->ID, 'ministry_album_name', true) ?: '',
    ];
}

// Dynamically resolve the Parish Life page URL by template
$_pl_pages = get_pages(['meta_key' => '_wp_page_template', 'meta_value' => 'page-photos.php']);
$_pl_url   = $_pl_pages ? get_permalink($_pl_pages[0]->ID) : home_url('/photos/');
?>
<div class="page-hero">
  <div class="container">
    <h1>Our Ministries</h1>
    <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › Ministries</p>
  </div>
</div>

<div class="bg-cream"><div class="sec container tc">
  <span class="stag">Serve &amp; Grow</span>
  <h2 class="stitle">Parish Ministries</h2>
  <div class="divider"></div>
  <p class="slead">St. John's nurtures the sacramental life of our parish through diverse and vibrant ministries for all ages and backgrounds.</p>
</div></div>

<div class="bg-ww"><div class="sec container">
  <?php if ($ministries): ?>
  <div class="mdgrid">
    <?php foreach ($ministries as $m):
        $d       = $min_data[$m->ID];
        $img     = $d['img'] ?: 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=500&q=70';
        $excerpt = wp_trim_words(wp_strip_all_tags($m->post_content), 28, '…') ?: 'Learn more about this ministry.';
    ?>
    <article class="mdcard">
      <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($m->post_title); ?>" loading="lazy">
      <div class="mdcard-body">
        <?php if ($d['tag']): ?>
        <span class="mcard-tag"><?php echo esc_html($d['tag']); ?></span>
        <?php endif; ?>
        <h3><?php echo esc_html($m->post_title); ?></h3>
        <p><?php echo esc_html($excerpt); ?></p>
        <button class="btn btn-cr" style="font-size:.76rem;padding:9px 20px"
          onclick="sjiocOpenMinistry(<?php echo (int) $m->ID; ?>)">Get Involved →</button>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div style="text-align:center;padding:48px 0;color:#888">
    <p style="font-size:1.1rem;margin-bottom:12px">No ministries have been added yet.</p>
    <?php if (current_user_can('manage_options')): ?>
    <a href="<?php echo esc_url(admin_url('edit.php?post_type=sjioc_ministry')); ?>" class="btn btn-cr">+ Add Ministries in Admin</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div></div>

<section class="times-band" style="text-align:center">
  <div class="container" style="position:relative">
    <h2 style="font-family:'Playfair Display',serif;color:#fff;font-size:2rem;margin-bottom:12px">Join a Ministry Today</h2>
    <div class="divider"></div>
    <p style="color:rgba(255,255,255,.74);line-height:1.8;margin-bottom:28px;position:relative;max-width:580px;margin-left:auto;margin-right:auto">Every member of our parish family has a gift to offer. Prayerfully consider how you might serve the body of Christ.</p>
    <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="btn btn-ol">Contact Us to Get Involved</a>
  </div>
</section>

<!-- ═══════════════════════════════════════════════
     MINISTRY DETAIL MODAL
═══════════════════════════════════════════════ -->
<div class="min-modal" id="min-modal" onclick="sjiocCloseMinistry(event)" role="dialog" aria-modal="true" aria-label="Ministry Details">
  <div class="min-modal-box">
    <button class="min-close" onclick="sjiocCloseMinistry()" aria-label="Close">&times;</button>
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
      <div id="min-leadership" class="min-section" style="display:none">
        <h4 class="min-section-heading">Leadership</h4>
        <div id="min-leaders-grid" class="min-leaders-grid"></div>
      </div>
      <div id="min-gallery-link" style="display:none;margin-top:20px">
        <a id="min-gallery-a" href="#" class="btn btn-cr" style="font-size:.82rem">📸 View in Parish Life Gallery →</a>
      </div>
    </div>
    <div class="min-footer">
      <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="btn btn-cr">Get Involved →</a>
      <button class="btn btn-ol" onclick="sjiocCloseMinistry()">Close</button>
    </div>
  </div>
</div>

<script>
var SJIOC_MINISTRIES    = <?php echo wp_json_encode($min_data); ?>;
var SJIOC_PARISH_LIFE_URL = <?php echo wp_json_encode(esc_url($_pl_url)); ?>;

function sjiocOpenMinistry(id) {
    var m = SJIOC_MINISTRIES[id];
    if (!m) return;

    // Hero: image as CSS background, fallback to cardinal colour
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

    var leadWrap = document.getElementById('min-leadership');
    var roles = m.roles || [];
    if (roles.length) {
        document.getElementById('min-leaders-grid').innerHTML = roles.map(function (r) {
            return '<div class="min-leader-pill">'
                + '<div class="ml-role">' + _mesc(r.role || '') + '</div>'
                + '<div class="ml-name">' + _mesc(r.name || '') + '</div>'
                + '</div>';
        }).join('');
        leadWrap.style.display = '';
    } else { leadWrap.style.display = 'none'; }

    var galLink = document.getElementById('min-gallery-link');
    if (m.album_cat) {
        var url = SJIOC_PARISH_LIFE_URL + '#' + m.album_cat;
        if (m.album_name) url += ':' + encodeURIComponent(m.album_name);
        document.getElementById('min-gallery-a').href = url;
        galLink.style.display = '';
    } else { galLink.style.display = 'none'; }

    var modal = document.getElementById('min-modal');
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    modal.querySelector('.min-close').focus();
}

function sjiocCloseMinistry(e) {
    var modal = document.getElementById('min-modal');
    if (!modal) return;
    if (e && e.target !== modal) return;
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
}

function _mesc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php sjioc_footer(); get_footer(); ?>
