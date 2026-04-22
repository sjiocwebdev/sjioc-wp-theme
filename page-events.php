<?php /* Template Name: Events Page */ get_header(); ?>
<div class="page-hero"><div class="container"><h1>Events</h1><p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › Events</p></div></div>
<div class="bg-cream"><div class="sec container">
  <div class="tc" style="margin-bottom:42px"><span class="stag">Parish Life</span><h2 class="stitle">Upcoming &amp; Past Events</h2><div class="divider"></div><p class="slead">Stay connected with the vibrant life of our parish through worship, fellowship, and service events.</p></div>
  <div class="filter-bar" role="group" aria-label="Filter events">
    <button class="filter-btn is-active" data-cat="all">All Events</button>
    <button class="filter-btn" data-cat="worship">Worship</button>
    <button class="filter-btn" data-cat="fellowship">Fellowship</button>
    <button class="filter-btn" data-cat="education">Education</button>
    <button class="filter-btn" data-cat="outreach">Outreach</button>
  </div>
  <div class="ev-full-grid" id="ev-grid">
    <?php
    $ev_q = new WP_Query(['post_type'=>'sjioc_event','posts_per_page'=>12,'orderby'=>'meta_value','meta_key'=>'event_date','order'=>'ASC']);
    if ($ev_q->have_posts()) :
      while ($ev_q->have_posts()) : $ev_q->the_post();
        $d   = get_post_meta(get_the_ID(),'event_date',true);
        $t   = get_post_meta(get_the_ID(),'event_time',true);
        $loc = get_post_meta(get_the_ID(),'event_location',true);
        $cat = get_post_meta(get_the_ID(),'event_category',true);
        $mon = $d ? date('M',strtotime($d)) : 'TBD'; $day = $d ? date('j',strtotime($d)) : '—'; $yr = $d ? date('Y',strtotime($d)) : '';
    ?>
    <article class="ev-card" data-cat="<?php echo esc_attr($cat); ?>">
      <div class="ecard-date"><span class="ecd-mon"><?php echo esc_html($mon); ?></span><span class="ecd-day"><?php echo esc_html($day); ?></span><?php if($yr):?><span class="ecd-yr"><?php echo esc_html($yr); ?></span><?php endif;?></div>
      <div class="ecard-body"><span class="ecard-cat"><?php echo esc_html($cat); ?></span><h3><?php the_title(); ?></h3><p><?php echo wp_trim_words(get_the_excerpt(),16); ?></p><div class="ecard-meta"><?php if($t):?><span>🕐 <?php echo esc_html($t); ?></span><?php endif;?><?php if($loc):?><span>📍 <?php echo esc_html($loc); ?></span><?php endif;?></div></div>
    </article>
    <?php endwhile; wp_reset_postdata();
    else:
      $evs=[['May','15','2026','fellowship','Parish Picnic','Annual gathering — food, games, and fellowship.','10:00 AM','Church Grounds'],['May','20','2026','education','Bible Study','Weekly adult Bible study exploring St. Paul\'s Epistles.','7:30 PM','Parish Hall'],['Jun','5','2026','fellowship','Family Retreat','Annual spiritual retreat. Limited spots!','All Day','Cedar Falls'],['Jun','15','2026','worship','Youth Prayer Vigil','Overnight vigil for youth, led by Fr. Tojo Baby.','8:00 PM','Church'],['Jul','4','2026','outreach','Community Outreach Day','Serving meals at the local shelter with MGOCSM.','9:00 AM','Drexel Hill Shelter'],['Jul','20','2026','worship','Feast Day — St. John','Special Qurbana, procession, and community feast.','8:30 AM','Church'],['Aug','10','2026','education','Sunday School Orientation','Welcome for students and teachers for the new year.','12:00 PM','Parish Hall'],['Aug','25','2026','fellowship',"Women's Fellowship Meeting",'Monthly prayer, reflection, and outreach planning.','3:00 PM','Parish Hall']];
      foreach ($evs as $e): ?>
    <article class="ev-card" data-cat="<?php echo esc_attr($e[3]); ?>">
      <div class="ecard-date"><span class="ecd-mon"><?php echo esc_html($e[0]); ?></span><span class="ecd-day"><?php echo esc_html($e[1]); ?></span><span class="ecd-yr"><?php echo esc_html($e[2]); ?></span></div>
      <div class="ecard-body"><span class="ecard-cat"><?php echo esc_html($e[3]); ?></span><h3><?php echo esc_html($e[4]); ?></h3><p><?php echo esc_html($e[5]); ?></p><div class="ecard-meta"><span>🕐 <?php echo esc_html($e[6]); ?></span><span>📍 <?php echo esc_html($e[7]); ?></span></div></div>
    </article>
    <?php endforeach; endif; ?>
  </div>
</div></div>
<section class="times-band" style="text-align:center"><div class="container" style="position:relative"><h2 style="font-family:'Playfair Display',serif;color:#fff;font-size:1.9rem;margin-bottom:12px">Host a Parish Event?</h2><div class="divider"></div><p style="color:rgba(255,255,255,.74);margin-bottom:28px;position:relative;max-width:520px;margin-left:auto;margin-right:auto">Contact our parish office to schedule events at <?php echo esc_html(sjioc_address()); ?>.</p><a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="btn btn-ol">Contact the Parish Office</a></div></section>
<?php sjioc_footer(); get_footer(); ?>
