<?php
/**
 * Template Name: News Page
 */
get_header();

$per_page = 6;
$page     = max(1, absint($_GET['pg'] ?? 1));

// Single query (matches the site's +1-query-per-page budget — see AZURE_PERF.md);
// paginate in PHP rather than a second COUNT query.
$all_articles = get_posts([
    'post_type'      => 'sjioc_news',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

$total_pages = max(1, (int) ceil(count($all_articles) / $per_page));
$page        = min($page, $total_pages);
$articles    = array_slice($all_articles, ($page - 1) * $per_page, $per_page);

// Build data for the popup modal
$news_data = [];
foreach ($articles as $a) {
    $news_data[$a->ID] = [
        'title' => $a->post_title,
        'date'  => get_the_date('F j, Y', $a),
        'img'   => get_the_post_thumbnail_url($a->ID, 'large') ?: '',
        'body'  => wp_kses_post(apply_filters('the_content', $a->post_content)),
    ];
}
?>
<div class="page-hero">
  <div class="container">
    <h1>SJIOC News</h1>
    <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › Updates › News</p>
  </div>
</div>

<div class="bg-cream"><div class="sec container tc">
  <span class="stag">Stay Informed</span>
  <h2 class="stitle">Parish News &amp; Updates</h2>
  <div class="divider"></div>
  <p class="slead">The latest news, announcements, and updates from St. John's Indian Orthodox Church of Delaware Valley.</p>
</div></div>

<div class="bg-ww"><div class="sec container">
  <?php if ($articles): ?>
  <div class="mdgrid">
    <?php foreach ($articles as $a):
        $d       = $news_data[$a->ID];
        $img     = $d['img'] ?: 'https://images.unsplash.com/photo-1495020689067-958852a7765e?w=500&q=70';
        $excerpt = $a->post_excerpt ? $a->post_excerpt : wp_trim_words(wp_strip_all_tags($a->post_content), 28, '…');
    ?>
    <article class="mdcard">
      <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($a->post_title); ?>" loading="lazy">
      <div class="mdcard-body">
        <span class="mcard-tag"><?php echo esc_html($d['date']); ?></span>
        <h3><?php echo esc_html($a->post_title); ?></h3>
        <p><?php echo esc_html($excerpt); ?></p>
        <button class="btn btn-cr" style="font-size:.76rem;padding:9px 20px"
          onclick="sjiocOpenNews(<?php echo (int) $a->ID; ?>)">Read More →</button>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <?php if ($total_pages > 1): ?>
  <div class="news-pagination">
    <?php if ($page > 1): ?>
      <a href="<?php echo esc_url(add_query_arg('pg', $page - 1)); ?>" class="btn btn-ol">← Previous</a>
    <?php endif; ?>
    <span class="news-page-info">Page <?php echo (int) $page; ?> of <?php echo (int) $total_pages; ?></span>
    <?php if ($page < $total_pages): ?>
      <a href="<?php echo esc_url(add_query_arg('pg', $page + 1)); ?>" class="btn btn-cr">Next →</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div style="text-align:center;padding:48px 0;color:#888">
    <p style="font-size:1.1rem;margin-bottom:12px">No news articles have been added yet.</p>
    <?php if (current_user_can('manage_options')): ?>
    <a href="<?php echo esc_url(admin_url('edit.php?post_type=sjioc_news')); ?>" class="btn btn-cr">+ Add News in Admin</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div></div>

<!-- ═══════════════════════════════════════════════
     NEWS ARTICLE MODAL
═══════════════════════════════════════════════ -->
<div class="min-modal" id="min-modal" onclick="sjiocCloseNews(event)" role="dialog" aria-modal="true" aria-label="News Article">
  <div class="min-modal-box">
    <button class="min-close" onclick="sjiocCloseNews()" aria-label="Close">&times;</button>
    <div class="min-hero" id="min-hero">
      <div class="min-hero-overlay">
        <span id="min-tag" class="mcard-tag"></span>
        <h2 id="min-title"></h2>
      </div>
    </div>
    <div class="min-body">
      <div id="min-intro" class="min-intro"></div>
    </div>
    <div class="min-footer">
      <button class="btn btn-ol" onclick="sjiocCloseNews()">Close</button>
    </div>
  </div>
</div>

<script>
var SJIOC_NEWS = <?php echo wp_json_encode($news_data); ?>;

function sjiocOpenNews(id) {
    var n = SJIOC_NEWS[id];
    if (!n) return;

    var hero = document.getElementById('min-hero');
    if (n.img) {
        hero.style.backgroundImage = 'url(' + n.img + ')';
        hero.classList.remove('no-img');
    } else {
        hero.style.backgroundImage = 'none';
        hero.classList.add('no-img');
    }

    document.getElementById('min-tag').textContent   = n.date  || '';
    document.getElementById('min-title').textContent = n.title || '';
    document.getElementById('min-intro').innerHTML   = n.body  || '';

    var modal = document.getElementById('min-modal');
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    modal.querySelector('.min-close').focus();
}

function sjiocCloseNews(e) {
    var modal = document.getElementById('min-modal');
    if (!modal) return;
    if (e && e.target !== modal) return;
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
}
</script>

<?php sjioc_footer(); get_footer(); ?>
