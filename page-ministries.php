<?php
/**
 * Template Name: Ministries Page
 */
get_header();
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
  <div class="mdgrid">
    <?php
    $mins = [
      ['https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=500&q=70','Active Ministry','Youth Ministry','We engage young people in regular faith formation, worship, and service. Our youth are the future of our parish and we invest deeply in their spiritual growth.'],
      ['https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=500&q=70','Education','Sunday School','Sunday school ministry helps children encounter God through interactive lessons every Sunday from '.esc_html(sjioc_school()).' onward at our Drexel Hill campus.'],
      ['https://images.unsplash.com/photo-1573497620053-ea5300f94f21?w=500&q=70','Fellowship',"Women's Fellowship","Our women's ministry gathers monthly for prayer, Bible study, and outreach. We support one another in faith and life."],
      ['https://images.unsplash.com/photo-1544535830-9df3f56fff6a?w=500&q=70','Fellowship',"Men's Fellowship",'The men of our parish gather for prayer, scripture reflection, and service — supporting one another as husbands, fathers, and Christian men.'],
      ['https://images.unsplash.com/photo-1559027615-cd4628902d4a?w=500&q=70','Liturgical','FOCUS Ministry','Our parish choir leads the congregation in sacred music at every Divine Liturgy. We always welcome new voices of all ability levels.'],
      ['https://images.unsplash.com/photo-1593113598332-cd288d649433?w=500&q=70','MGOCSM','Community Outreach','Serving our neighbors through food drives, hospital visitation, and partnerships with local charities. Love in action is our calling.'],
    ];
    foreach ($mins as $m): ?>
    <article class="mdcard">
      <img src="<?php echo esc_url($m[0]); ?>" alt="<?php echo esc_attr($m[2]); ?>" loading="lazy">
      <div class="mdcard-body">
        <span class="mcard-tag"><?php echo esc_html($m[1]); ?></span>
        <h3><?php echo esc_html($m[2]); ?></h3>
        <p><?php echo esc_html($m[3]); ?></p>
        <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="btn btn-cr" style="font-size:.76rem;padding:9px 20px">Get Involved →</a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</div></div>

<section class="times-band" style="text-align:center">
  <div class="container" style="position:relative">
    <h2 style="font-family:'Playfair Display',serif;color:#fff;font-size:2rem;margin-bottom:12px">Join a Ministry Today</h2>
    <div class="divider"></div>
    <p style="color:rgba(255,255,255,.74);line-height:1.8;margin-bottom:28px;position:relative;max-width:580px;margin-left:auto;margin-right:auto">Every member of our parish family has a gift to offer. Prayerfully consider how you might serve the body of Christ.</p>
    <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="btn btn-ol">Contact Us to Get Involved</a>
  </div>
</section>

<?php sjioc_footer(); get_footer(); ?>
