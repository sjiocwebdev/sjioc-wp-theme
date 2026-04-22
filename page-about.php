<?php
/**
 * Template Name: About Us Page
 */
get_header();
?>
<article>
<div class="page-hero">
  <div class="container">
    <h1>About Us</h1>
    <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › About Us</p>
  </div>
</div>

<!-- Story + Image -->
<div class="bg-cream"><div class="sec container">
  <div class="about-grid">
    <div>
      <span class="stag">Our Story</span>
      <h2 class="stitle" style="text-align:left"><?php echo esc_html(sjioc_name()); ?></h2>
      <div class="divider divider-l"></div>
      <p>We warmly welcome you to <?php echo esc_html(sjioc_name()); ?>. Our church is a place of faith, fellowship, and tradition for all who seek the living God.</p>
      <p>The worshipping community of the Malankara Orthodox Church around the Delaware Valley area in Pennsylvania had been cherishing a dream of forming a parish. By the Grace of God, the Diocesan Metropolitan announced the new parish via <em>Kalpana No. K81/2006</em>.</p>
      <p>Father Geevarghese Erakkath was appointed first Vicar. His Grace Mathews Mar Barnabas, Diocesan Metropolitan, blessed the church and celebrated the first Holy Qurbana on <strong>November 25, 2006</strong>, declaring the formation of the congregation.</p>
      <?php if (have_posts()): while (have_posts()): the_post(); ?>
        <div class="entry-content" style="margin-top:1rem"><?php the_content(); ?></div>
      <?php endwhile; endif; ?>
      <h3 style="font-family:'Playfair Display',serif;color:var(--cr);margin:24px 0 10px;font-size:1.25rem">Our Mission</h3>
      <p>To glorify God, proclaim the Gospel of Jesus Christ, nurture our parish family in holiness, and serve our community with love — rooted in the ancient apostolic faith.</p>
      <h3 style="font-family:'Playfair Display',serif;color:var(--cr);margin:20px 0 10px;font-size:1.25rem">Our Vicar</h3>
      <p>Our parish is led by <strong>Rev. Fr. Tojo Baby</strong>, who shepherds our community with deep pastoral love and theological wisdom.</p>
      <br><a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="btn btn-cr">Get In Touch</a>
    </div>
    <div class="about-img">
      <img src="https://sjioc.org/images/20250419_123136.jpg" alt="<?php echo esc_attr(sjioc_name()); ?>" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1548625149-720754956904?w=800&q=80'">
    </div>
  </div>
</div></div>

<!-- Values Band -->
<section class="times-band" aria-labelledby="values-heading">
  <div class="container" style="position:relative">
    <h2 id="values-heading">Our Core Values</h2>
    <div class="divider"></div>
    <p style="color:rgba(255,255,255,.68);max-width:560px;margin:0 auto 42px;line-height:1.78;font-size:.95rem;position:relative">Everything we do flows from these deeply held convictions about God, the Church, and one another.</p>
    <div class="values-grid" style="position:relative">
      <div class="vcard"><span class="vcard-icon">✝</span><h3>Authentic Worship</h3><p>Rooted in 2,000 years of Orthodox liturgical tradition connecting us to the universal Church across all time.</p></div>
      <div class="vcard"><span class="vcard-icon">❤</span><h3>Loving Community</h3><p>The Church is a family. We care for one another and practice hospitality as a spiritual discipline.</p></div>
      <div class="vcard"><span class="vcard-icon">📖</span><h3>Faithful Teaching</h3><p>We hand on the apostolic faith intact through preaching, catechism, Sunday School, and adult formation.</p></div>
      <div class="vcard"><span class="vcard-icon">🌍</span><h3>Compassionate Service</h3><p>Following Christ's example, we serve the poor and marginalized in Drexel Hill and across the world.</p></div>
      <div class="vcard"><span class="vcard-icon">🕊</span><h3>Spiritual Formation</h3><p>We nurture the inner life through prayer, fasting, scripture, and the sacraments — growing in holiness together.</p></div>
      <div class="vcard"><span class="vcard-icon">🤝</span><h3>Unity in Diversity</h3><p>All generations and backgrounds are welcome. We celebrate our Indian heritage while embracing all into God's family.</p></div>
    </div>
  </div>
</section>

<!-- Leadership -->
<div class="bg-cream"><div class="sec container tc">
  <span class="stag">Meet the Team</span>
  <h2 class="stitle">Our Leadership</h2>
  <div class="divider"></div>
  <p class="slead">Dedicated servants of God guiding our parish with wisdom, love, and pastoral care.</p>
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
</div></div>

<!-- Timeline -->
<div class="bg-ww"><div class="sec container tc">
  <span class="stag">Our History</span>
  <h2 class="stitle">Parish Milestones</h2>
  <div class="divider"></div>
  <div class="timeline" style="text-align:left">
    <div class="tl-row"><div class="tl-year"><span>2006</span></div><div class="tl-content"><h4>Parish Founded</h4><p>By Kalpana No. K81/2006, His Grace Mathews Mar Barnabas declared the formation of St. John's congregation. First Holy Qurbana November 25, 2006. Fr. Geevarghese Erakkath appointed first Vicar.</p></div></div>
    <div class="tl-row"><div class="tl-year"><span>2008</span></div><div class="tl-content"><h4>Growing Congregation</h4><p>The parish grew significantly, welcoming families from across Delaware Valley into our Orthodox Christian community.</p></div></div>
    <div class="tl-row"><div class="tl-year"><span>2012</span></div><div class="tl-content"><h4>MGOCSM Chapter</h4><p>The MGOCSM chapter was formally established, energizing youth and young adult participation in parish life.</p></div></div>
    <div class="tl-row"><div class="tl-year"><span>2019</span></div><div class="tl-content"><h4>Home at Drexel Hill</h4><p>The parish settled at 4400 State Road, Drexel Hill, PA 19026 — our permanent home in the heart of Delaware Valley.</p></div></div>
    <div class="tl-row"><div class="tl-year"><span>2026</span></div><div class="tl-content"><h4>Serving Today</h4><p>Under Rev. Fr. Tojo Baby, our parish continues to grow in faith, numbers, and community engagement.</p></div></div>
  </div>
</div></div>
</article>
<?php sjioc_footer(); get_footer(); ?>
