<?php
/**
 * Template Name: Parish Life Page
 */
get_header();

global $wpdb;
$table = $wpdb->prefix . 'sjioc_photos';

$all_photos = $wpdb->get_results(
    "SELECT * FROM {$table}
     WHERE download_url IS NOT NULL AND download_url != ''
     ORDER BY category, album, title"
);

// Build album map: cat → album → [thumb, count]
$album_map  = [];
$cats_found = [];
foreach ($all_photos as $p) {
    $cat   = $p->category ?: 'other';
    $album = $p->album    ?: '';
    if (!in_array($cat, $cats_found, true)) $cats_found[] = $cat;
    if (!isset($album_map[$cat][$album])) {
        $album_map[$cat][$album] = ['thumb' => $p->download_url, 'count' => 0];
    }
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
      ?>
      <div class="gal-album-card" data-cat="<?php echo $safe_cat; ?>"
           role="button" tabindex="0"
           aria-label="<?php echo esc_attr($label . ' — ' . $info['count'] . ' photos'); ?>"
           onclick="galOpen('<?php echo $safe_cat; ?>','<?php echo $safe_album; ?>')"
           onkeydown="if(event.key==='Enter')galOpen('<?php echo $safe_cat; ?>','<?php echo $safe_album; ?>')">
        <img src="<?php echo esc_url($info['thumb']); ?>" alt="<?php echo esc_attr($label); ?>" loading="lazy">
        <div class="gal-album-info">
          <h4><?php echo esc_html($label); ?></h4>
          <span><?php echo (int) $info['count']; ?> photo<?php echo $info['count'] !== 1 ? 's' : ''; ?></span>
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
<div class="lightbox" id="sjioc-lightbox" onclick="sjiocCloseLightbox(event)" role="dialog" aria-modal="true" aria-label="Photo viewer">
  <button class="lb-close" id="lb-close" onclick="sjiocCloseLightbox()" aria-label="Close photo viewer">&times;</button>
  <img id="lb-img" src="" alt="">
</div>

<script>
var SJIOC_PHOTOS = <?php echo wp_json_encode(array_map(function ($p) {
    return [
        'cat'   => $p->category,
        'album' => $p->album,
        'url'   => $p->download_url,
        'title' => $p->title ?: pathinfo($p->file_name, PATHINFO_FILENAME),
    ];
}, $all_photos)); ?>;

function galCat(btn, cat) {
    document.querySelectorAll('#gal-cat-bar .filter-btn').forEach(function(b) { b.classList.remove('is-active'); });
    btn.classList.add('is-active');
    document.querySelectorAll('.gal-album-card').forEach(function(card) {
        card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
    });
}

function galOpen(cat, album) {
    var photos = SJIOC_PHOTOS.filter(function(p) { return p.cat === cat && p.album === album; });
    var label  = album || (cat.charAt(0).toUpperCase() + cat.slice(1) + ' Photos');
    document.getElementById('gal-album-title').textContent = label;
    var grid = document.getElementById('gal-grid');
    grid.innerHTML = photos.map(function(p) {
        var esc = function(s){ return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); };
        return '<div class="gallery-item" role="button" tabindex="0"'
             + ' data-url="' + esc(p.url) + '" data-title="' + esc(p.title) + '"'
             + ' aria-label="View ' + esc(p.title) + '">'
             + '<img src="' + esc(p.url) + '" alt="' + esc(p.title) + '" loading="lazy">'
             + '<div class="gallery-overlay"><span>' + esc(p.title) + '</span></div>'
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
    sjiocOpenLightbox(item.dataset.url, item.dataset.title);
});
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Enter') return;
    var item = e.target.closest('#gal-grid .gallery-item');
    if (!item) return;
    sjiocOpenLightbox(item.dataset.url, item.dataset.title);
});

function galBack() {
    document.getElementById('gal-photos').style.display  = 'none';
    document.getElementById('gal-albums').style.display  = '';
    document.getElementById('gal-cat-bar').style.display = '';
}
</script>

<?php sjioc_footer(); get_footer(); ?>
