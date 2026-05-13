<?php
/**
 * Template Name: Parish Life Page
 */
get_header();

global $wpdb;
$table = $wpdb->prefix . 'sjioc_photos';

$all_photos = $wpdb->get_results(
    "SELECT * FROM {$table}
     ORDER BY category, album, title"
);

// Build album map: cat → album → [thumb, count, has_video, thumb_is_video]
$album_map  = [];
$cats_found = [];
foreach ($all_photos as $p) {
    $cat      = $p->category ?: 'other';
    $album    = $p->album    ?: '';
    $is_video = ($p->media_type ?? 'image') === 'video';
    if (!in_array($cat, $cats_found, true)) $cats_found[] = $cat;
    if (!isset($album_map[$cat][$album])) {
        $album_map[$cat][$album] = ['thumb' => rest_url('sjioc/v1/photo/' . $p->id), 'count' => 0, 'has_video' => false, 'thumb_is_video' => $is_video];
    } elseif ($album_map[$cat][$album]['thumb_is_video'] && !$is_video) {
        // Prefer an image as the album card thumbnail
        $album_map[$cat][$album]['thumb']          = rest_url('sjioc/v1/photo/' . $p->id);
        $album_map[$cat][$album]['thumb_is_video'] = false;
    }
    if ($is_video) $album_map[$cat][$album]['has_video'] = true;
    $album_map[$cat][$album]['count']++;
}

$cat_labels  = ['worship' => 'Worship', 'events' => 'Events', 'ministries' => 'Ministries', 'community' => 'Community'];
$sorted_cats = array_merge(
    array_filter(array_keys($cat_labels), fn($k) => isset($album_map[$k])),
    array_filter($cats_found, fn($c) => !isset($cat_labels[$c]))
);
?>
<div class="page-hero"><div class="container"><h1>Parish Life</h1><p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › Parish Life</p></div></div>
<div class="bg-cream"><div class="sec container">
  <div class="tc" style="margin-bottom:42px">
    <span class="stag">Our Parish Life</span>
    <h2 class="stitle">SJIOC Gallery</h2>
    <div class="divider"></div>
    <p class="slead">Glimpses of worship, fellowship, and community life at <?php echo esc_html(sjioc_abbr()); ?> — capturing the spirit of our parish family.</p>
  </div>

  <?php if ($album_map) : ?>

  <!-- Level 1: Category tabs -->
  <div class="filter-bar" role="group" aria-label="Filter by category" id="gal-cat-bar">
    <button class="filter-btn is-active" onclick="galCat(this,'all')">All</button>
    <?php foreach ($sorted_cats as $cat) : ?>
    <button class="filter-btn" onclick="galCat(this,'<?php echo esc_attr($cat); ?>')">
      <?php echo esc_html($cat_labels[$cat] ?? ucfirst($cat)); ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- Level 2: Album grid -->
  <div class="gal-album-grid" id="gal-albums">
    <?php foreach ($sorted_cats as $cat) : ?>
      <?php foreach ($album_map[$cat] as $album => $info) : ?>
      <?php
        $label      = $album ?: ucfirst($cat) . ' Photos';
        $safe_cat   = esc_attr($cat);
        $safe_album = esc_attr($album);
        $item_word  = $info['has_video'] ? 'item' : 'photo';
        $item_pl    = $info['has_video'] ? 'items' : 'photos';
        $count_word = $info['count'] !== 1 ? $item_pl : $item_word;
      ?>
      <div class="gal-album-card" data-cat="<?php echo $safe_cat; ?>"
           role="button" tabindex="0"
           aria-label="<?php echo esc_attr($label . ' — ' . $info['count'] . ' ' . $count_word); ?>"
           onclick="galOpen('<?php echo $safe_cat; ?>','<?php echo $safe_album; ?>')"
           onkeydown="if(event.key==='Enter')galOpen('<?php echo $safe_cat; ?>','<?php echo $safe_album; ?>')">
        <?php if (!$info['thumb_is_video']): ?>
        <img src="<?php echo esc_url($info['thumb']); ?>" alt="<?php echo esc_attr($label); ?>" loading="lazy">
        <?php else: ?>
        <div class="gal-album-video-only-thumb" aria-hidden="true">&#9654;</div>
        <?php endif; ?>
        <?php if ($info['has_video']): ?>
        <div class="gal-video-badge" aria-hidden="true">&#9654;</div>
        <?php endif; ?>
        <div class="gal-album-info">
          <h4><?php echo esc_html($label); ?></h4>
          <span><?php echo (int) $info['count'] . ' ' . esc_html($count_word); ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>

  <!-- Level 3: Photo grid (hidden until album clicked) -->
  <div id="gal-photos" style="display:none">
    <div class="gal-back">
      <button class="btn btn-ol" onclick="galBack()">← Back to Albums</button>
      <h3 id="gal-album-title"></h3>
    </div>
    <div class="gallery-grid" id="gal-grid"></div>
  </div>

  <?php else : ?>
  <!-- Fallback: no photos synced yet -->
  <p class="slead tc">No photos yet — go to <strong>SJIOC → Photos → Sync Now</strong> to pull photos from OneDrive.</p>
  <?php endif; ?>

</div></div>

<!-- Lightbox -->
<div class="lightbox" id="sjioc-lightbox" onclick="if(event.target===this)sjiocCloseLightbox()" role="dialog" aria-modal="true" aria-label="Media viewer">
  <button class="lb-close" id="lb-close" onclick="sjiocCloseLightbox()" aria-label="Close viewer">&times;</button>
  <div class="lb-inner">
    <button class="lb-prev" id="lb-prev" onclick="lbNav(-1)" aria-label="Previous" style="display:none">&#10094;</button>
    <div class="lb-center">
      <div id="lb-media"></div>
      <p id="lb-caption"></p>
    </div>
    <button class="lb-next" id="lb-next" onclick="lbNav(1)" aria-label="Next" style="display:none">&#10095;</button>
  </div>
</div>

<script>
var SJIOC_PHOTOS = <?php echo wp_json_encode(array_map(function ($p) {
    return [
        'cat'        => $p->category,
        'album'      => $p->album,
        'url'        => rest_url('sjioc/v1/photo/' . $p->id),
        'title'      => $p->title ?: pathinfo($p->file_name, PATHINFO_FILENAME),
        'media_type' => $p->media_type ?? 'image',
    ];
}, $all_photos)); ?>;

var _currentPhotos = [];
var _lbIdx = 0;

function lbEsc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function galCat(btn, cat) {
    document.querySelectorAll('#gal-cat-bar .filter-btn').forEach(function(b) { b.classList.remove('is-active'); });
    btn.classList.add('is-active');
    document.querySelectorAll('.gal-album-card').forEach(function(card) {
        card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
    });
}

function galOpen(cat, album) {
    _currentPhotos = SJIOC_PHOTOS.filter(function(p) { return p.cat === cat && p.album === album; });
    var label = album || (cat.charAt(0).toUpperCase() + cat.slice(1) + ' Photos');
    document.getElementById('gal-album-title').textContent = label;
    var grid = document.getElementById('gal-grid');
    grid.innerHTML = _currentPhotos.map(function(p, idx) {
        var isVideo = p.media_type === 'video';
        var thumb   = isVideo
            ? '<div class="gallery-item-video-thumb" aria-hidden="true">&#9654;</div>'
            : '<img src="' + lbEsc(p.url) + '" alt="' + lbEsc(p.title) + '" loading="lazy">';
        return '<div class="gallery-item" role="button" tabindex="0" data-idx="' + idx + '"'
             + ' aria-label="' + (isVideo ? 'Play ' : 'View ') + lbEsc(p.title) + '">'
             + thumb
             + '<div class="gallery-overlay"><span>' + lbEsc(p.title) + '</span></div>'
             + '</div>';
    }).join('');
    document.getElementById('gal-albums').style.display  = 'none';
    document.getElementById('gal-cat-bar').style.display = 'none';
    document.getElementById('gal-photos').style.display  = '';
}

// Event delegation for dynamically rendered photo grid
document.addEventListener('click', function(e) {
    var item = e.target.closest('#gal-grid .gallery-item');
    if (!item) return;
    sjiocOpenLightbox(_currentPhotos, parseInt(item.dataset.idx, 10));
});
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Enter') return;
    var item = e.target.closest('#gal-grid .gallery-item');
    if (!item) return;
    sjiocOpenLightbox(_currentPhotos, parseInt(item.dataset.idx, 10));
});

function galBack() {
    document.getElementById('gal-photos').style.display  = 'none';
    document.getElementById('gal-albums').style.display  = '';
    document.getElementById('gal-cat-bar').style.display = '';
}

// ── Lightbox overrides (extends main.js sjiocOpenLightbox) ─────────
window.sjiocOpenLightbox = function(photos, idx) {
    _currentPhotos = Array.isArray(photos) ? photos : [];
    _lbIdx = typeof idx === 'number' ? idx : 0;
    lbRender();
    var lb = document.getElementById('sjioc-lightbox');
    if (!lb) return;
    lb.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    document.getElementById('lb-close').focus();
};

function lbRender() {
    var p       = _currentPhotos[_lbIdx];
    var media   = document.getElementById('lb-media');
    var caption = document.getElementById('lb-caption');
    var prev    = document.getElementById('lb-prev');
    var next    = document.getElementById('lb-next');
    if (!p || !media) return;
    var old = media.querySelector('video');
    if (old) old.pause();
    if (p.media_type === 'video') {
        media.innerHTML = '<video controls autoplay playsinline>'
                        + '<source src="' + lbEsc(p.url) + '">'
                        + 'Your browser does not support video playback.'
                        + '</video>';
    } else {
        media.innerHTML = '<img src="' + lbEsc(p.url) + '" alt="' + lbEsc(p.title) + '">';
    }
    if (caption) caption.textContent = p.title || '';
    if (prev) prev.style.display = _lbIdx > 0 ? '' : 'none';
    if (next) next.style.display = _lbIdx < _currentPhotos.length - 1 ? '' : 'none';
}

window.lbNav = function(dir) {
    var n = _lbIdx + dir;
    if (n < 0 || n >= _currentPhotos.length) return;
    _lbIdx = n;
    lbRender();
};

window.sjiocCloseLightbox = function() {
    var lb = document.getElementById('sjioc-lightbox');
    if (!lb) return;
    var video = lb.querySelector('video');
    if (video) video.pause();
    lb.classList.remove('is-open');
    document.body.style.overflow = '';
};

// Arrow key nav + ESC video-stop (ESC class removal handled by main.js)
document.addEventListener('keydown', function(e) {
    var lb = document.getElementById('sjioc-lightbox');
    if (!lb || !lb.classList.contains('is-open')) return;
    if (e.key === 'ArrowLeft')  { e.preventDefault(); lbNav(-1); }
    if (e.key === 'ArrowRight') { e.preventDefault(); lbNav(1); }
    if (e.key === 'Escape') {
        var video = lb.querySelector('video');
        if (video) video.pause();
        document.body.style.overflow = '';
    }
});
</script>

<?php sjioc_footer(); get_footer(); ?>
