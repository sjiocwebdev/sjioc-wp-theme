<?php
/**
 * Template Name: Home Page
 * The home/front page for SJIOC Delaware Valley.
 */
get_header();
$hero_title   = sjioc_get('sjioc_hero_title',   "St. John's Indian Orthodox Church");
$hero_sub     = sjioc_get('sjioc_hero_sub',     'A Faith Community Rooted in Tradition · Delaware Valley');
 $hero_eyebrow = sjioc_get('sjioc_hero_eyebrow', '✦ Est. 2006 · Drexel Hill, PA ✦'); 
?>

<!-- ════ HERO ════ -->
<section class="home-hero" aria-label="Welcome banner">
  <div class="hero-bg" role="presentation"></div>
  <div class="hero-overlay" role="presentation"></div>
  <div class="hero-content">
    <span class="hero-eyebrow"><?php echo esc_html($hero_eyebrow); ?></span>
    <h1><?php echo esc_html($hero_title); ?></h1>
    <span class="hero-sub"><?php echo esc_html($hero_sub); ?></span>
    <div class="hero-btns">
      <a href="<?php echo esc_url(home_url('/worship-services/')); ?>" class="btn btn-cr">Worship &amp; Services</a>
      <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="btn btn-ol">Contact Us</a>
      <a href="<?php echo esc_url(home_url('/give/')); ?>" class="btn btn-go">Support Us</a>
    </div>
    <?php $hero_verse = sjioc_get_current_bible_verse(); if ($hero_verse): ?>
    <div class="hero-verse">
      <p class="hero-verse-text">&ldquo;<?php echo esc_html($hero_verse['text']); ?>&rdquo;</p>
      <span class="hero-verse-ref"><?php echo esc_html($hero_verse['ref']); ?></span>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ════ FLASH NEWS ════ -->
<?php
$today         = date('Y-m-d');
$all_ann       = get_posts(['post_type'=>'sjioc_announcement','posts_per_page'=>10,'post_status'=>'publish']);
$announcements = array_filter($all_ann, function($a) use ($today) {
    $start  = get_post_meta($a->ID, 'ann_start',  true);
    $expiry = get_post_meta($a->ID, 'ann_expiry', true);
    if ($start  && $start  > $today) return false;
    if ($expiry && $expiry < $today) return false;
    return true;
});

if ($announcements):
    $ribbon_items = []; // info / event / rental → sticky ribbon + modal
    $grid_items   = []; // urgent / sad → featured card + support grid
    foreach ($announcements as $a) {
        $type = get_post_meta($a->ID, 'ann_type', true) ?: 'info';
        $item = [
            'id'      => $a->ID,
            'text'    => $a->post_title,
            'type'    => $type,
            'message' => get_post_meta($a->ID, 'ann_message', true),
            'link'    => get_post_meta($a->ID, 'ann_link',    true),
            'img'     => get_the_post_thumbnail_url($a->ID, 'large') ?: '',
            'cards'   => json_decode(get_post_meta($a->ID, 'ann_support_cards', true) ?: '[]', true) ?: [],
        ];
        if (in_array($type, ['urgent','sad'], true)) $grid_items[]   = $item;
        else                                          $ribbon_items[] = $item;
    }
    $type_labels = ['info'=>'Info','urgent'=>'Urgent Notice','sad'=>'With Sympathy','rental'=>'Facility','event'=>'Event'];
    $type_icons  = ['info'=>'📢','urgent'=>'🔔','sad'=>'🕯️','rental'=>'🏛️','event'=>'📅'];
    $ribbon_key  = 'sjioc_ribbon_' . md5(implode(',', array_column($ribbon_items, 'id')));
?>

<?php /* ── Sticky Ribbon (info / event / rental) ── */ ?>
<?php if ($ribbon_items): ?>
<div class="ann-ribbon" id="ann-ribbon" data-key="<?php echo esc_attr($ribbon_key); ?>">
  <div class="ann-ribbon-inner">
    <span class="ann-ribbon-pulse"></span>
    <span class="ann-ribbon-msg" id="ann-ribbon-msg"></span>
    <button class="ann-ribbon-read" id="ann-ribbon-read" aria-haspopup="dialog">Read More</button>
    <button class="ann-ribbon-close" id="ann-ribbon-close" aria-label="Dismiss">✕</button>
  </div>
</div>

<!-- Ribbon Modal -->
<div class="ann-modal" id="ann-modal" role="dialog" aria-modal="true" aria-labelledby="ann-modal-title">
  <div class="ann-modal-box">
    <button class="ann-modal-close" id="ann-modal-close" aria-label="Close">✕</button>
    <span class="ann-modal-badge" id="ann-modal-badge"></span>
    <h3 class="ann-modal-title" id="ann-modal-title"></h3>
    <div class="ann-modal-body" id="ann-modal-body"></div>
    <div class="ann-modal-footer" id="ann-modal-footer"></div>
  </div>
</div>
<?php endif; ?>

<?php /* ── Card Grid (urgent / sad) ── */ ?>
<?php foreach ($grid_items as $gi):
    $grid_key = 'sjioc_grid_' . $gi['id'];
    $has_img  = !empty($gi['img']);
    $has_cards = !empty(array_filter($gi['cards'], fn($c) => !empty($c['title'])));
?>
<div class="ann-grid-block ann-grid-<?php echo esc_attr($gi['type']); ?>" data-key="<?php echo esc_attr($grid_key); ?>">
  <div class="container">

    <!-- Featured Card -->
    <div class="ann-featured<?php echo $has_img ? '' : ' ann-featured-noimg'; ?>">
      <?php if ($has_img): ?>
      <div class="ann-featured-img">
        <img src="<?php echo esc_url($gi['img']); ?>" alt="<?php echo esc_attr($gi['text']); ?>">
      </div>
      <?php endif; ?>
      <div class="ann-featured-content">
        <span class="ann-featured-badge">
          <?php echo $type_icons[$gi['type']] ?? ''; ?> <?php echo esc_html($type_labels[$gi['type']] ?? ''); ?>
        </span>
        <h2 class="ann-featured-title"><?php echo esc_html($gi['text']); ?></h2>
        <?php if ($gi['message']): ?>
        <div class="ann-featured-msg"><?php echo wp_kses_post($gi['message']); ?></div>
        <?php endif; ?>
        <?php if ($gi['link']): ?>
        <a href="<?php echo esc_url($gi['link']); ?>" class="btn btn-ol" style="margin-top:20px">Learn More →</a>
        <?php endif; ?>
      </div>
      <button class="ann-grid-dismiss" data-key="<?php echo esc_attr($grid_key); ?>" aria-label="Dismiss">✕</button>
    </div>

    <!-- Support Cards Grid -->
    <?php if ($has_cards):
      $visible_cards = array_filter($gi['cards'], fn($c) => !empty($c['title']));
    ?>
    <div class="ann-support-grid ann-sup-<?php echo esc_attr($gi['type']); ?>">
      <?php foreach ($visible_cards as $sc): ?>
      <div class="ann-support-card">
        <h4 class="ann-sup-title"><?php echo esc_html($sc['title']); ?></h4>
        <?php if ($sc['desc']): ?><p class="ann-sup-desc"><?php echo esc_html($sc['desc']); ?></p><?php endif; ?>
        <?php if ($sc['link']): ?><a href="<?php echo esc_url($sc['link']); ?>" class="ann-sup-link">Details →</a><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</div>
<?php endforeach; ?>

<script>
(function(){
  function esc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

  /* ── Sticky Ribbon ── */
  var ribbon = document.getElementById('ann-ribbon');
  if (ribbon) {
    var rkey = ribbon.dataset.key;
    if (localStorage.getItem(rkey)) { ribbon.style.display='none'; }
    else {
      var items   = <?php echo wp_json_encode(array_values($ribbon_items)); ?>;
      var labels  = <?php echo wp_json_encode($type_labels); ?>;
      var icons   = <?php echo wp_json_encode($type_icons); ?>;
      var msgEl   = document.getElementById('ann-ribbon-msg');
      var idx = 0, cur = 0;

      function setRibbon(i) {
        cur = i % items.length;
        var it = items[cur];
        msgEl.innerHTML = '<span class="ann-ribbon-icon">' + (icons[it.type]||'📢') + '</span> ' + esc(it.text);
      }
      setRibbon(0);
      if (items.length > 1) setInterval(function(){ idx++; setRibbon(idx); }, 6000);

      // Read More → open modal
      document.getElementById('ann-ribbon-read').addEventListener('click', function() {
        var it = items[cur];
        document.getElementById('ann-modal-badge').textContent  = (icons[it.type]||'') + ' ' + (labels[it.type]||'');
        document.getElementById('ann-modal-title').textContent  = it.text;
        document.getElementById('ann-modal-body').innerHTML     = it.message || '';
        var footer = document.getElementById('ann-modal-footer');
        footer.innerHTML = it.link
          ? '<a href="'+esc(it.link)+'" class="btn btn-cr">Learn More →</a>' : '';
        document.getElementById('ann-modal').classList.add('is-open');
        document.body.style.overflow = 'hidden';
        document.getElementById('ann-modal-close').focus();
      });

      // Close modal
      function closeModal() {
        document.getElementById('ann-modal').classList.remove('is-open');
        document.body.style.overflow = '';
      }
      document.getElementById('ann-modal-close').addEventListener('click', closeModal);
      document.getElementById('ann-modal').addEventListener('click', function(e){
        if (e.target === this) closeModal();
      });

      // Dismiss ribbon
      document.getElementById('ann-ribbon-close').addEventListener('click', function(){
        localStorage.setItem(rkey, '1');
        ribbon.classList.add('ann-ribbon-hiding');
        setTimeout(function(){ ribbon.style.display='none'; }, 350);
      });
    }
  }

  /* ── Card Grid dismiss ── */
  document.querySelectorAll('.ann-grid-dismiss').forEach(function(btn) {
    var key = btn.dataset.key;
    var block = btn.closest('.ann-grid-block');
    if (localStorage.getItem(key)) { block.style.display='none'; return; }
    btn.addEventListener('click', function(){
      localStorage.setItem(key, '1');
      block.classList.add('ann-grid-hiding');
      setTimeout(function(){ block.style.display='none'; }, 400);
    });
  });

  // ESC closes modal
  document.addEventListener('keydown', function(e){
    if (e.key==='Escape') {
      var m = document.getElementById('ann-modal');
      if (m && m.classList.contains('is-open')) {
        m.classList.remove('is-open');
        document.body.style.overflow='';
      }
    }
  });
})();
</script>
<?php endif; ?>

<!-- ════ WELCOME ════ -->
<div class="bg-cream">
  <div class="sec container">
    <div class="welcome-grid">
      <div class="welcome-text">
        <span class="stag">Our Community</span>
        <h2>Welcome to St. John's Indian Orthodox Church of Delaware Valley</h2>
        <div class="divider divider-l"></div>
        <p>Welcome to St. John's Indian Orthodox Church of Delaware Valley, a vibrant sanctuary of faith, fellowship, and timeless Orthodox tradition. Located at 4400 State Road in Drexel Hill, Pennsylvania, our parish has been proudly serving families across Delaware Valley and Greater Philadelphia regions since November 2006.</p>
        <p>Our community is deeply rooted in the Malankara Orthodox Syrian Church, carrying forward the ancient apostolic faith established in India by St. Thomas the Apostle in 52 AD. Whether you are looking for a spiritual home, seeking to explore your faith, or wanting to connect with a welcoming community, we invite you to experience the rich liturgy and warmth of our parish.</p>
        <br>
        <?php $vsb_id = sjioc_youtube_id(get_theme_mod('sjioc_story_video_url', '')); ?>
        <div class="welcome-cta-row">
          <a href="<?php echo esc_url(home_url('/about-us/')); ?>" class="btn btn-cr">Learn More About Us</a>
          <?php if ($vsb_id):
              $vsb_thumb = 'https://img.youtube.com/vi/' . $vsb_id . '/hqdefault.jpg';
          ?>
          <button type="button" class="welcome-video-badge" id="welcome-video-badge" aria-haspopup="dialog" aria-controls="vsb-modal" aria-label="Watch our welcome video">
            <span class="welcome-video-badge-ring">
              <span class="welcome-video-badge-inner" style="background-image:url('<?php echo esc_url($vsb_thumb); ?>')">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
              </span>
            </span>
            <span class="welcome-video-badge-label">Watch Our Story</span>
          </button>
          <?php endif; ?>
        </div>
      </div>
      <div class="welcome-img">
        <?php
        $img_id  = get_theme_mod('sjioc_welcome_img');
        $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'large') : 'https://sjioc.org/images/20250419_123136.jpg';
        ?>
        <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr(sjioc_name()); ?>"
             loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1548625149-720754956904?w=800&q=80'">
      </div>
    </div>
  </div>
</div>

<?php if ($vsb_id): ?>
<div class="vsb-modal" id="vsb-modal" role="dialog" aria-modal="true" aria-label="Welcome Video">
  <div class="vsb-modal-box">
    <button class="vsb-close" id="vsb-close" aria-label="Close">&times;</button>
    <div class="vsb-video-wrap" id="vsb-video-wrap"></div>
  </div>
</div>

<script>
(function () {
  var fab   = document.getElementById('welcome-video-badge');
  var modal = document.getElementById('vsb-modal');
  var close = document.getElementById('vsb-close');
  var wrap  = document.getElementById('vsb-video-wrap');
  if (!fab || !modal || !wrap) return;

  var videoId = <?php echo wp_json_encode($vsb_id); ?>;

  function open() {
    var iframe = document.createElement('iframe');
    iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(videoId) + '?autoplay=1&rel=0&modestbranding=1';
    iframe.setAttribute('allow', 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture');
    iframe.setAttribute('allowfullscreen', '');
    iframe.setAttribute('frameborder', '0');
    wrap.innerHTML = '';
    wrap.appendChild(iframe);
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    close.focus();
  }
  function hide() {
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
    wrap.innerHTML = '';
  }

  fab.addEventListener('click', open);
  close.addEventListener('click', hide);
  modal.addEventListener('click', function (e) { if (e.target === modal) hide(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') hide(); });
})();
</script>
<?php endif; ?>

<!-- ════ SERVICE TIMES ════ -->
<section class="times-band" aria-labelledby="times-heading">
  <div class="container" style="position:relative">
    <h2 id="times-heading">Worship &amp; Service Times</h2>
    <div class="divider"></div>
    <div class="times-row">
      <div class="time-col">
        <span class="time-label">Saturday</span>
        <span class="time-val">Evening Prayer — <?php echo esc_html(sjioc_sat_evening()); ?></span>
      </div>
      <div class="time-col">
        <span class="time-label">Sunday</span>
        <span class="time-val">Morning Prayer — <?php echo esc_html(sjioc_morning_prayer()); ?> &nbsp;|&nbsp; Holy Qurbana — <?php echo esc_html(sjioc_qurbana()); ?></span>
      </div>
      <div class="time-col">
        <span class="time-label">First Wednesday</span>
        <span class="time-val">Holy Qurbana — <?php echo esc_html(sjioc_first_wed_qurbana()); ?></span>
      </div>
    </div>
  </div>
</section>

<!-- ════ MINISTRIES PREVIEW ════ -->
<div class="bg-cream">
  <div class="sec container tc">
    <span class="stag">Serve &amp; Grow</span>
    <h2 class="stitle">Our Ministries</h2>
    <div class="divider"></div>
    <p class="slead">Connecting every member of our parish through faith, service, and fellowship in Christ.</p>
    <?php
    $min_posts  = get_posts([
      'post_type'      => 'sjioc_ministry',
      'posts_per_page' => 6,
      'meta_key'       => 'ministry_order',
      'orderby'        => 'meta_value_num',
      'order'          => 'ASC',
      'post_status'    => 'publish',
    ]);
    $min_total  = wp_count_posts('sjioc_ministry')->publish;
    ?>
    <div class="min-scroll-wrap" id="min-scroll-wrap">
      <button class="min-scroll-arrow min-sa-prev is-hidden" id="min-sa-prev" aria-label="Previous ministries">&#8249;</button>
      <div class="min-scroll-track" id="min-scroll-track">
      <?php if ($min_posts):
        foreach ($min_posts as $mp):
          $mp_img  = get_the_post_thumbnail_url($mp->ID, 'large') ?: 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=600&q=70';
          $mp_tag  = get_post_meta($mp->ID, 'ministry_tag', true);
          $mp_desc = wp_trim_words(wp_strip_all_tags($mp->post_content), 22, '…') ?: 'Learn more about this ministry.';
          $mp_is_logo = (bool) get_post_meta($mp->ID, 'ministry_is_logo', true);
      ?>
        <article class="mcard<?php echo $mp_is_logo ? ' is-logo' : ''; ?>">
          <img src="<?php echo esc_url($mp_img); ?>" alt="<?php echo esc_attr($mp->post_title); ?>" loading="lazy">
          <div class="mcard-body">
            <?php if ($mp_tag): ?><span class="mcard-tag"><?php echo esc_html($mp_tag); ?></span><?php endif; ?>
            <h3><?php echo esc_html($mp->post_title); ?></h3>
            <p><?php echo esc_html($mp_desc); ?></p>
            <a class="mlink" href="<?php echo esc_url(home_url('/ministries/')); ?>">Learn More →</a>
          </div>
        </article>
      <?php endforeach;
      else:
        $fallback = [
          ['tag'=>'Youth',      'title'=>'Youth Ministry',    'desc'=>'Nurturing faith in our young parishioners through worship, scripture study, and community service.'],
          ['tag'=>'Education',  'title'=>'Sunday School',     'desc'=>'Helping children and youth encounter God through interactive lessons every Sunday at '.esc_html(sjioc_school()).'.'],
          ['tag'=>'Fellowship', 'title'=>"Women's Fellowship",'desc'=>"A vibrant community of women gathering monthly for prayer, fellowship, and outreach in Christ's love."],
        ];
        foreach ($fallback as $f): ?>
        <article class="mcard">
          <div class="mcard-body">
            <span class="mcard-tag"><?php echo esc_html($f['tag']); ?></span>
            <h3><?php echo esc_html($f['title']); ?></h3>
            <p><?php echo esc_html($f['desc']); ?></p>
            <a class="mlink" href="<?php echo esc_url(home_url('/ministries/')); ?>">Learn More →</a>
          </div>
        </article>
      <?php endforeach; endif; ?>
      </div>
      <button class="min-scroll-arrow min-sa-next" id="min-sa-next" aria-label="Next ministries">&#8250;</button>
    </div>
    <div style="margin-top:36px">
      <a href="<?php echo esc_url(home_url('/ministries/')); ?>" class="btn btn-cr">
        See All<?php if ($min_total > 6): ?> <?php echo (int)$min_total; ?><?php endif; ?> Ministries →
      </a>
    </div>
    <script>
    (function(){
      var track   = document.getElementById('min-scroll-track');
      var prev    = document.getElementById('min-sa-prev');
      var next    = document.getElementById('min-sa-next');
      if (!track || !prev || !next) return;
      function step() {
        var c = track.querySelector('.mcard');
        return c ? c.offsetWidth + 24 : 320;
      }
      function sync() {
        prev.classList.toggle('is-hidden', track.scrollLeft < 8);
        next.classList.toggle('is-hidden', track.scrollLeft >= track.scrollWidth - track.clientWidth - 8);
      }
      prev.addEventListener('click', function(){ stopAuto(); track.scrollBy({ left:-step(), behavior:'smooth' }); });
      next.addEventListener('click', function(){ stopAuto(); track.scrollBy({ left: step(), behavior:'smooth' }); });
      track.addEventListener('scroll', sync, { passive:true });
      sync();

      // Auto-advance every 4 seconds; pause on hover or touch
      var timer;
      function advance() {
        if (track.scrollLeft >= track.scrollWidth - track.clientWidth - 8) {
          track.scrollTo({ left:0, behavior:'smooth' });
        } else {
          track.scrollBy({ left:step(), behavior:'smooth' });
        }
      }
      function startAuto() { timer = setInterval(advance, 2000); }
      function stopAuto()  { clearInterval(timer); }
      track.parentElement.addEventListener('mouseenter', stopAuto);
      track.parentElement.addEventListener('mouseleave', startAuto);
      track.addEventListener('touchstart', stopAuto, { passive:true });
      startAuto();
    })();
    </script>
    </div>
  </div>
</div>

<!-- ════ EVENTS + FELLOWSHIP CTA ════ -->
<div class="bg-ww">
  <div class="sec container">
    <div class="ev-home-grid">

      <!-- Upcoming Events -->
      <div>
        <span class="stag">What's Coming</span>
        <h2 class="stitle">Upcoming Events</h2>
        <div class="divider divider-l"></div>
        <?php foreach (sjioc_front_page_events() as $e) : ?>
        <div class="ev-item">
          <div class="ev-date-box"><span class="ev-mon"><?php echo esc_html($e['mon']); ?></span><span class="ev-day"><?php echo esc_html($e['day']); ?></span></div>
          <div class="ev-info"><h4><?php echo esc_html($e['title']); ?></h4><p><?php echo esc_html($e['excerpt']); ?></p></div>
        </div>
        <?php endforeach; ?>
        <br>
        <a href="<?php echo esc_url(home_url('/events/')); ?>" class="btn btn-cr">View All Events</a>
        <a href="<?php echo esc_url(home_url('/events/')); ?>#calendar" class="btn btn-ol" style="margin-left:10px">&#128197; Calendar</a>
      </div>

      <!-- Fellowship CTA -->
      <div class="fellowship-box">
        <h3>Join Us in Worship<br>and Fellowship</h3>
        <p>Every Sunday at <?php echo esc_html(sjioc_address()); ?>. We'd love to have you join our parish family.</p>
        <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="btn btn-cr">Contact Us</a>
      </div>
    </div>
  </div>
</div>

<!-- ════ LATEST EVENT VIDEO (hidden entirely when no link is set) ════ -->
<?php
$lv_id = sjioc_youtube_id(get_theme_mod('sjioc_latest_video_url', ''));
if ($lv_id):
    $lv_thumb = 'https://img.youtube.com/vi/' . $lv_id . '/hqdefault.jpg';
?>
<div class="bg-cream">
  <div class="sec container tc">
    <span class="stag">Watch &amp; Relive</span>
    <h2 class="stitle">Latest Event Video</h2>
    <div class="divider"></div>
    <div class="lv-wrap">
      <button type="button" class="lv-embed lv-facade" id="lv-embed" aria-label="Play latest event video" style="background-image:url('<?php echo esc_url($lv_thumb); ?>')">
        <span class="lv-play-ring"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg></span>
      </button>
    </div>
  </div>
</div>
<script>
(function(){
  var facade = document.getElementById('lv-embed');
  if (!facade) return;
  var videoId = <?php echo wp_json_encode($lv_id); ?>;
  facade.addEventListener('click', function(){
    var iframe = document.createElement('iframe');
    iframe.className = 'lv-embed';
    iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(videoId) + '?autoplay=1&rel=0&modestbranding=1';
    iframe.setAttribute('allow', 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture');
    iframe.setAttribute('allowfullscreen', '');
    iframe.setAttribute('frameborder', '0');
    facade.replaceWith(iframe);
  });
})();
</script>
<?php endif; ?>

<!-- ════ NEW TO SJIOC ════ -->
<section class="ntc-band" aria-labelledby="ntc-heading">
  <div class="ntc-inner container">
    <div class="ntc-cross"><svg viewBox="0 0 24 24" width="38" height="38" aria-hidden="true"><rect x="10" y="2" width="4" height="20" fill="var(--go)"/><rect x="3" y="9" width="18" height="4" fill="var(--go)"/></svg></div>
    <h2 id="ntc-heading">New to SJIOC?</h2>
    <p>Whether you've recently moved to the Greater Philadelphia region or are simply seeking a spiritual home,<br>we'd love to welcome you and your family into our parish community.</p>
    <button class="btn ntc-cta-btn" id="ntc-open-btn" type="button"> I'm New Here — Connect With Us</button>
  </div>
</section>

<!-- ════ NEW TO SJIOC MODAL ════ -->
<div class="ntc-overlay" id="ntc-overlay" role="dialog" aria-modal="true" aria-labelledby="ntc-modal-title" hidden>
  <div class="ntc-modal">
    <button class="ntc-close" id="ntc-close" aria-label="Close">&times;</button>

    <!-- Left: church info -->
    <div class="ntc-panel ntc-info">
      <div class="ntc-info-cross"><svg viewBox="0 0 24 24" width="32" height="32" aria-hidden="true"><rect x="10" y="2" width="4" height="20" fill="var(--go)"/><rect x="3" y="9" width="18" height="4" fill="var(--go)"/></svg></div>
      <h2 id="ntc-modal-title"><?php echo esc_html(sjioc_name()); ?></h2>
      <p class="ntc-tagline">A Keralite Orthodox Christian community in Drexell Hill, PA — a home away from home.</p>
      <p class="ntc-desc">We are a vibrant parish of the Malankara Orthodox Syrian Church, bringing together families from Kerala and the wider community in worship, fellowship, and service. Our doors are always open to newcomers seeking faith and community in Christ.</p>
      <ul class="ntc-info-list">
        <li><span>&#128336;</span> Holy Qurbana: <?php echo esc_html(sjioc_qurbana()); ?></li>
        <li><span>&#128218;</span> Sunday School: <?php echo esc_html(sjioc_school()); ?></li>
        <li><span>&#128205;</span> <?php echo esc_html(sjioc_address()); ?></li>
        <li><span>&#128222;</span> <?php echo esc_html(sjioc_phone()); ?></li>
      </ul>
    </div>

    <!-- Right: form -->
    <div class="ntc-panel ntc-form-panel">
      <h3>Tell Us a Little About Yourself</h3>
      <p class="ntc-form-sub">We'll have someone from our team reach out personally — no spam, just a warm welcome.</p>

      <div id="ntc-success" class="ntc-success" hidden></div>
      <div id="ntc-error"   class="ntc-err"     hidden></div>

      <form id="ntc-form" novalidate>
        <?php wp_nonce_field('sjioc_ntc', 'sjioc_ntc_nonce'); ?>
        <input type="hidden" name="recaptcha_token" id="ntc_recaptcha_token">
        <div style="display:none" aria-hidden="true"><input type="text" id="ntc-hp" name="ntc_hp" tabindex="-1" autocomplete="off"></div>

        <div class="ntc-row-2">
          <div class="ntc-group">
            <label for="ntc-fname">First Name <span class="req">*</span></label>
            <input type="text" id="ntc-fname" name="ntc_fname" autocomplete="given-name" required>
          </div>
          <div class="ntc-group">
            <label for="ntc-lname">Last Name <span class="req">*</span></label>
            <input type="text" id="ntc-lname" name="ntc_lname" autocomplete="family-name" required>
          </div>
        </div>

        <div class="ntc-row-2">
          <div class="ntc-group">
            <label for="ntc-phone">Phone Number <span class="req">*</span></label>
            <input type="tel" id="ntc-phone" name="ntc_phone" autocomplete="tel" placeholder="(904) 555-0100">
          </div>
          <div class="ntc-group">
            <label for="ntc-family">Total Family Members</label>
            <input type="number" id="ntc-family" name="ntc_family" min="1" max="20" placeholder="e.g. 4">
          </div>
        </div>

        <div class="ntc-group">
          <label for="ntc-address">Home Address</label>
          <input type="text" id="ntc-address" name="ntc_address" autocomplete="street-address" placeholder="Street, City, ZIP">
        </div>

        <div class="ntc-row-2">
          <div class="ntc-group">
            <label for="ntc-kerala">Where in Kerala?</label>
            <select id="ntc-kerala" name="ntc_kerala">
              <option value="">— select district —</option>
              <option>Thiruvananthapuram</option><option>Kollam</option>
              <option>Pathanamthitta</option><option>Alappuzha</option>
              <option>Kottayam</option><option>Idukki</option>
              <option>Ernakulam</option><option>Thrissur</option>
              <option>Palakkad</option><option>Malappuram</option>
              <option>Kozhikode</option><option>Wayanad</option>
              <option>Kannur</option><option>Kasaragod</option>
              <option>Outside Kerala</option>
            </select>
          </div>
          <div class="ntc-group">
            <label for="ntc-parish">Kerala Parish Name</label>
            <input type="text" id="ntc-parish" name="ntc_parish" placeholder="e.g. St. George's, Kottayam">
          </div>
        </div>

        <div class="ntc-group">
          <label for="ntc-visit">Planning to Visit Church? (Date)</label>
          <input type="date" id="ntc-visit" name="ntc_visit" min="<?php echo esc_attr(date('Y-m-d', strtotime('+1 day'))); ?>">
        </div>

        <div class="ntc-group">
          <label>Best Time for Us to Call You</label>
          <div class="ntc-call-chips" id="ntc-call-chips">
            <?php
            $windows = [
              'early'     => ['&#127747;', 'Early Morning', '7:00 – 9:00 AM'],
              'morning'   => ['&#9728;',   'Morning',       '9:00 – 11:00 AM'],
              'midday'    => ['&#127748;', 'Midday',        '11:00 AM – 1:00 PM'],
              'afternoon' => ['&#127749;', 'Afternoon',     '1:00 – 4:00 PM'],
              'evening'   => ['&#127750;', 'Evening',       '4:00 – 7:00 PM'],
              'after'     => ['&#127769;', 'After Dinner',  '7:00 – 9:00 PM'],
            ];
            foreach ($windows as $val => [$icon, $label, $time]): ?>
            <button type="button" class="ntc-chip" data-val="<?php echo esc_attr($label . ' (' . $time . ')'); ?>">
              <span class="ntc-chip-icon"><?php echo $icon; ?></span>
              <span class="ntc-chip-label"><?php echo esc_html($label); ?></span>
              <span class="ntc-chip-time"><?php echo esc_html($time); ?></span>
            </button>
            <?php endforeach; ?>
          </div>
          <input type="hidden" id="ntc-call" name="ntc_call">
        </div>

        <button type="submit" class="btn btn-cr ntc-submit" id="ntc-submit">Send — We'd Love to Meet You &#10139;</button>
      </form>
    </div><!-- /ntc-form-panel -->
  </div><!-- /ntc-modal -->
</div><!-- /ntc-overlay -->

<?php $rc_site_key = sjioc_recaptcha_site_key(); ?>
<?php if ($rc_site_key): ?>
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr($rc_site_key); ?>"></script>
<?php endif; ?>
<script>
(function () {
  var rcKey    = <?php echo wp_json_encode($rc_site_key); ?>;
  var overlay  = document.getElementById('ntc-overlay');
  var openBtn  = document.getElementById('ntc-open-btn');
  var closeBtn = document.getElementById('ntc-close');
  var form     = document.getElementById('ntc-form');
  var chips    = document.querySelectorAll('.ntc-chip');
  var callHid  = document.getElementById('ntc-call');
  var success  = document.getElementById('ntc-success');
  var errBox   = document.getElementById('ntc-error');
  var submit   = document.getElementById('ntc-submit');

  function openModal() { overlay.hidden = false; document.body.style.overflow = 'hidden'; }
  function closeModal() { overlay.hidden = true; document.body.style.overflow = ''; history.replaceState(null, '', window.location.pathname); }

  if (openBtn)  openBtn.addEventListener('click', openModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

  // Hash trigger (nav link — works on page load and same-page hash change)
  function checkHash() { if (window.location.hash === '#new-to-sjioc') openModal(); }
  checkHash();
  window.addEventListener('hashchange', checkHash);

  // Call window chips
  chips.forEach(function (chip) {
    chip.addEventListener('click', function () {
      chips.forEach(function (c) { c.classList.remove('selected'); });
      chip.classList.add('selected');
      callHid.value = chip.dataset.val;
    });
  });

  // Submit
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      errBox.hidden = true;
      var fname = document.getElementById('ntc-fname').value.trim();
      var lname = document.getElementById('ntc-lname').value.trim();
      var phone = document.getElementById('ntc-phone').value.trim();
      if (!fname || !lname || !phone) {
        errBox.textContent = 'Please enter your name and phone number.';
        errBox.hidden = false; return;
      }

      // Honeypot — bots fill hidden fields, real users never interact with them
      var ntcHp = document.getElementById('ntc-hp');
      if (ntcHp && ntcHp.value.trim()) return;

      submit.disabled = true; submit.textContent = '⏳ Sending…';

      function doSubmit(rcToken) {
        var nonce = document.querySelector('input[name="sjioc_ntc_nonce"]');
        var body  = new URLSearchParams({
          action:          'sjioc_new_to_church',
          nonce:           nonce ? nonce.value : '',
          recaptcha_token: rcToken || '',
          ntc_fname:       fname,
          ntc_lname:       lname,
          ntc_phone:       phone,
          ntc_address:     document.getElementById('ntc-address').value,
          ntc_visit:       document.getElementById('ntc-visit').value,
          ntc_family:      document.getElementById('ntc-family').value,
          ntc_kerala:      document.getElementById('ntc-kerala').value,
          ntc_parish:      document.getElementById('ntc-parish').value,
          ntc_call:        callHid.value,
        });
        fetch(typeof sjioData !== 'undefined' ? sjioData.ajaxUrl : '/wp-admin/admin-ajax.php', {
          method: 'POST', body: body
        }).then(function (r) { return r.json(); })
          .then(function (d) {
            if (d.success) {
              form.hidden    = true;
              success.textContent = d.data.msg;
              success.hidden = false;
            } else {
              errBox.textContent = (d.data && d.data.msg) ? d.data.msg : 'Something went wrong. Please try again.';
              errBox.hidden = false;
              submit.disabled = false;
              submit.textContent = 'Send — We\'d Love to Meet You ➤';
            }
          }).catch(function () {
            errBox.textContent = 'Network error. Please try again.';
            errBox.hidden = false;
            submit.disabled = false;
            submit.textContent = 'Send — We\'d Love to Meet You ➤';
          });
      }

      if (rcKey && typeof grecaptcha !== 'undefined') {
        grecaptcha.ready(function () {
          grecaptcha.execute(rcKey, { action: 'new_to_church' }).then(doSubmit);
        });
      } else {
        doSubmit('');
      }
    });
  }
})();
</script>

<?php
$vm_text = get_theme_mod('sjioc_vicar_msg_text', '');
if ($vm_text):
    $vm_photo_id = get_theme_mod('sjioc_vicar_msg_photo');
    $vm_photo    = $vm_photo_id ? wp_get_attachment_image_url($vm_photo_id, 'medium_large') : '';
    $vm_name     = get_theme_mod('sjioc_vicar_msg_name', '');
    $vm_title    = get_theme_mod('sjioc_vicar_msg_title', __('Vicar', 'sjioc'));
?>
<!-- ════ VICAR'S MESSAGE — floating button + popup ════ -->
<button type="button" class="vm-fab" id="vm-fab" aria-haspopup="dialog" aria-controls="vm-modal" aria-label="A Message from Our Vicar">
  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23 5.5c-.8.4-1.7.6-2.6.8.9-.6 1.6-1.5 2-2.6-.9.5-1.8.9-2.8 1.1C18.8 3.9 17.6 3.3 16.3 3.3c-2.4 0-4.4 2-4.4 4.4 0 .3 0 .7.1 1C8.4 8.5 5 6.7 2.7 3.9c-.4.6-.6 1.4-.6 2.1 0 1.5.8 2.8 1.9 3.6-.7 0-1.4-.2-2-.5v.1c0 2.1 1.5 3.9 3.5 4.3-.4.1-.7.1-1.1.1-.3 0-.5 0-.8-.1.5 1.7 2.1 3 4 3-1.5 1.2-3.3 1.9-5.3 1.9-.3 0-.7 0-1-.1C3.4 19.5 5.6 20 8 20c8.4 0 13-7 13-13v-.6c.9-.6 1.7-1.4 2.3-2.3z"/></svg>
</button>

<div class="vm-modal" id="vm-modal" role="dialog" aria-modal="true" aria-label="A Message from Our Vicar">
  <div class="vm-modal-box">
    <button class="vm-close" id="vm-close" aria-label="Close">&times;</button>
    <?php if ($vm_photo): ?>
    <div class="vm-photo"><img src="<?php echo esc_url($vm_photo); ?>" alt="<?php echo esc_attr($vm_name); ?>" loading="lazy"></div>
    <?php endif; ?>
    <div class="vm-text">
      <span class="stag">A Message from Our Vicar</span>
      <?php if ($vm_name): ?><h3><?php echo esc_html($vm_name); ?></h3><?php endif; ?>
      <?php if ($vm_title): ?><p class="vm-role"><?php echo esc_html($vm_title); ?></p><?php endif; ?>
      <div class="vm-msg"><?php echo wp_kses_post(wpautop($vm_text)); ?></div>
    </div>
  </div>
</div>

<script>
(function () {
  var fab   = document.getElementById('vm-fab');
  var modal = document.getElementById('vm-modal');
  var close = document.getElementById('vm-close');
  if (!fab || !modal) return;

  function open() {
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    close.focus();
  }
  function hide() {
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  fab.addEventListener('click', open);
  close.addEventListener('click', hide);
  modal.addEventListener('click', function (e) { if (e.target === modal) hide(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') hide(); });
})();
</script>
<?php endif; ?>

<?php sjioc_footer(); get_footer(); ?>
