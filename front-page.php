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
    </div>
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
    $high   = []; // urgent / sad → card
    $low    = []; // info / event / rental → ticker
    foreach ($announcements as $a) {
        $type = get_post_meta($a->ID, 'ann_type', true) ?: 'info';
        $item = ['text'=>$a->post_title,'type'=>$type,'link'=>get_post_meta($a->ID,'ann_link',true),'id'=>$a->ID];
        if (in_array($type, ['urgent','sad'], true)) $high[] = $item;
        else $low[] = $item;
    }

    // Dismissal key changes whenever the set of announcements changes
    $all_ids    = array_column(array_merge($high,$low), 'id');
    $dismiss_key = 'sjioc_flash_' . md5(implode(',', $all_ids));

    $type_labels = ['info'=>'Info','urgent'=>'Urgent','sad'=>'Notice','rental'=>'Facility','event'=>'Event'];
    $type_icons  = ['info'=>'📢','urgent'=>'🔔','sad'=>'🕯️','rental'=>'🏛️','event'=>'📅'];
?>

<?php if ($high): foreach ($high as $hitem): ?>
<div class="ann-card ann-card-<?php echo esc_attr($hitem['type']); ?>" data-key="<?php echo esc_attr($dismiss_key.'-'.$hitem['id']); ?>">
  <div class="container">
    <div class="ann-card-inner">
      <span class="ann-card-icon"><?php echo $type_icons[$hitem['type']] ?? '📢'; ?></span>
      <div class="ann-card-body">
        <span class="ann-card-label"><?php echo esc_html($type_labels[$hitem['type']] ?? 'Notice'); ?></span>
        <p class="ann-card-text"><?php echo esc_html($hitem['text']); ?></p>
        <?php if ($hitem['link']): ?>
        <a href="<?php echo esc_url($hitem['link']); ?>" class="ann-card-link">Learn More →</a>
        <?php endif; ?>
      </div>
      <button class="ann-card-dismiss" aria-label="Dismiss">✕</button>
    </div>
  </div>
</div>
<?php endforeach; endif; ?>

<?php if ($low): ?>
<div class="flash-ticker" id="flash-ticker" data-key="<?php echo esc_attr($dismiss_key.'-ticker'); ?>">
  <div class="flash-ticker-inner">
    <span class="flash-ticker-label" id="flash-ticker-label"></span>
    <div class="flash-text-wrap"><p class="flash-text" id="flash-text"></p></div>
    <button class="flash-dismiss" id="flash-dismiss" aria-label="Dismiss">✕</button>
  </div>
</div>
<?php endif; ?>

<script>
(function(){
  // Announcement cards — dismiss individually
  document.querySelectorAll('.ann-card').forEach(function(card) {
    var key = card.dataset.key;
    if (localStorage.getItem(key)) { card.style.display='none'; return; }
    card.querySelector('.ann-card-dismiss').addEventListener('click', function(){
      localStorage.setItem(key, '1');
      card.classList.add('ann-card-hiding');
      setTimeout(function(){ card.style.display='none'; }, 350);
    });
  });

  // Ticker — cycle through low-priority items
  var ticker = document.getElementById('flash-ticker');
  if (ticker) {
    var key = ticker.dataset.key;
    if (localStorage.getItem(key)) { ticker.style.display='none'; }
    else {
      var items  = <?php echo wp_json_encode(array_values($low)); ?>;
      var labels = <?php echo wp_json_encode($type_labels); ?>;
      var label  = document.getElementById('flash-ticker-label');
      var text   = document.getElementById('flash-text');
      var idx    = 0;
      function showItem(i) {
        var it = items[i % items.length];
        label.className  = 'flash-ticker-label flash-lbl-' + it.type;
        label.textContent = labels[it.type] || 'News';
        text.style.opacity = 0;
        setTimeout(function(){
          text.innerHTML = it.link
            ? '<a href="'+_esc(it.link)+'">'+_esc(it.text)+'</a>'
            : _esc(it.text);
          text.style.opacity = 1;
        }, 200);
      }
      showItem(0);
      if (items.length > 1) setInterval(function(){ idx++; showItem(idx); }, 5000);
      document.getElementById('flash-dismiss').addEventListener('click', function(){
        localStorage.setItem(key, '1');
        ticker.classList.add('flash-hiding');
        setTimeout(function(){ ticker.style.display='none'; }, 350);
      });
    }
  }
  function _esc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
})();
</script>
<?php endif; ?>

<!-- ════ WELCOME ════ -->
<div class="bg-cream">
  <div class="sec container">
    <div class="welcome-grid">
      <div class="welcome-text">
        <span class="stag">Our Community</span>
        <h2>Welcome to <?php echo esc_html(sjioc_name()); ?></h2>
        <div class="divider divider-l"></div>
        <p>We warmly welcome you to <?php echo esc_html(sjioc_name()); ?>. Our church at <?php echo esc_html(sjioc_address()); ?> is a place of faith, fellowship, and ancient Orthodox tradition.</p>
        <p>Serving the communities of Exton, Downingtown, West Chester, Upper Darby, King of Prussia, Springfield, Broomall, Drexel Hill, Glen Mills, and all of Delaware Valley since November 2006.</p>
        <p>Rooted in the Malankara Orthodox Syrian Church — the ancient apostolic faith brought to India by St. Thomas the Apostle in 52 AD.</p>
        <br>
        <a href="<?php echo esc_url(home_url('/about-us/')); ?>" class="btn btn-cr">Learn More About Us</a>
      </div>
      <div class="welcome-img">
        <?php
        $img_url = get_theme_mod('sjioc_welcome_img', 'https://sjioc.org/images/20250419_123136.jpg');
        ?>
        <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr(sjioc_name()); ?>"
             loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1548625149-720754956904?w=800&q=80'">
      </div>
    </div>
  </div>
</div>

<!-- ════ SERVICE TIMES ════ -->
<section class="times-band" aria-labelledby="times-heading">
  <div class="container" style="position:relative">
    <h2 id="times-heading">Worship &amp; Service Times</h2>
    <div class="divider"></div>
    <div class="times-row">
      <div class="time-col">
        <span class="time-label">Sunday Holy Qurbana</span>
        <span class="time-val"><?php echo esc_html(sjioc_qurbana()); ?></span>
      </div>
      <div class="time-col">
        <span class="time-label">Sunday School</span>
        <span class="time-val"><?php echo esc_html(sjioc_school()); ?></span>
      </div>
      <div class="time-col">
        <span class="time-label">Saturday Office Hours</span>
        <span class="time-val"><?php echo esc_html(sjioc_get('sjioc_saturday','5:00 – 7:30 PM')); ?></span>
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
      ?>
        <article class="mcard">
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
        <?php
        $events = new WP_Query([
          'post_type'      => 'sjioc_event',
          'posts_per_page' => 3,
          'orderby'        => 'meta_value',
          'meta_key'       => 'event_date',
          'order'          => 'ASC',
          'meta_query'     => [[
            'key'     => 'event_date',
            'value'   => date('Y-m-d'),
            'compare' => '>=',
            'type'    => 'DATE',
          ]],
        ]);
        if ($events->have_posts()) :
          while ($events->have_posts()) : $events->the_post();
            $date  = get_post_meta(get_the_ID(),'event_date',true);
            $mon   = $date ? date('M',strtotime($date)) : 'TBD';
            $day   = $date ? date('j',strtotime($date)) : '—';
        ?>
        <div class="ev-item">
          <div class="ev-date-box"><span class="ev-mon"><?php echo esc_html($mon); ?></span><span class="ev-day"><?php echo esc_html($day); ?></span></div>
          <div class="ev-info"><h4><?php the_title(); ?></h4><p><?php echo wp_trim_words(get_the_excerpt(),14); ?></p></div>
        </div>
        <?php endwhile; wp_reset_postdata();
        else:
          $sample = [
            ['May','15','Parish Picnic','Annual outdoor gathering — food, games, and fellowship at the church grounds.'],
            ['May','20','Bible Study','Weekly adult Bible study exploring the Epistles of St. Paul. All welcome.'],
            ['Jun','5', 'Family Retreat','Annual parish spiritual retreat. Limited spots — register early!'],
          ];
          foreach ($sample as $e): ?>
        <div class="ev-item">
          <div class="ev-date-box"><span class="ev-mon"><?php echo esc_html($e[0]); ?></span><span class="ev-day"><?php echo esc_html($e[1]); ?></span></div>
          <div class="ev-info"><h4><?php echo esc_html($e[2]); ?></h4><p><?php echo esc_html($e[3]); ?></p></div>
        </div>
        <?php endforeach; endif; ?>
        <br>
        <a href="<?php echo esc_url(home_url('/events/')); ?>" class="btn btn-cr">View All Events</a>
        <a href="<?php echo esc_url(home_url('/events/')); ?>#calendar" class="btn btn-ol" style="margin-left:10px">📅 Calendar</a>
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

<?php sjioc_footer(); get_footer(); ?>
