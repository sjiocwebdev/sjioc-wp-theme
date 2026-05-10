<?php
/**
 * Template Name: Worship & Services Page
 */
get_header();
?>
<div class="page-hero"><div class="container"><h1>Worship &amp; Services</h1><p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › Worship &amp; Services</p></div></div>
<div class="bg-cream"><div class="sec container">
  <div class="tc" style="margin-bottom:52px">
    <span class="stag">Sacred Liturgy</span><h2 class="stitle">Our Worship &amp; Services</h2><div class="divider"></div>
    <p class="slead">Experience 2,000 years of Orthodox Christian worship — ancient, beautiful, and alive every Sunday in Drexel Hill.</p>
  </div>
  <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:54px;align-items:start">
    <div>
      <div style="background:var(--ww);border:1px solid var(--border);padding:34px;margin-bottom:22px">
        <h3 style="font-family:'Playfair Display',serif;color:var(--cr);font-size:1.4rem;margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--border)">📅 Sunday Schedule</h3>
        <?php
        $sched = [['Holy Qurbana (Divine Liturgy)',sjioc_qurbana()],['Sunday School',sjioc_school()],['Saturday Office / Evening Prayer','5:00–7:30 PM']];
        foreach ($sched as $s): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid rgba(201,168,76,.12)">
          <span style="display:flex;align-items:center;gap:9px;font-weight:600;color:var(--tm)"><span style="width:8px;height:8px;border-radius:50%;background:var(--go);display:inline-block;flex-shrink:0"></span><?php echo esc_html($s[0]); ?></span>
          <span style="color:var(--cr);font-weight:700;font-size:.9rem;white-space:nowrap"><?php echo esc_html($s[1]); ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <img src="https://sjioc.org/images/20250419_123136.jpg" alt="Church worship" style="width:100%;height:220px;object-fit:cover;border:3px solid var(--border)" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1548625149-720754956904?w=900&q=70'">
    </div>
    <div>
      <h3 style="font-family:'Playfair Display',serif;color:var(--cr);font-size:1.45rem;margin-bottom:16px">Feast Days</h3>
      <p style="color:var(--tl);line-height:1.82;margin-bottom:18px;font-size:.96rem">Our Orthodox liturgical calendar is rich with feast days celebrated with solemn Holy Qurbana, processions, and parish gatherings.</p>
      <ul style="list-style:none">
        <?php
        $feasts=[['Feast of St. John the Apostle','Parish feast day with special Qurbana and community meal.'],['Christmas — Nativity of Christ','Celebrated January 7th in the Orthodox tradition.'],['Sleeba — Holy Cross Day','Special liturgy and procession honouring the Holy Cross.'],['Easter — Resurrection of Christ','The greatest feast, preceded by Great Lent and Holy Week.'],['Pentecost &amp; Assumption','Celebrated with special services and parish fellowship.']];
        foreach ($feasts as $f): ?>
        <li style="padding:13px 0 13px 20px;border-bottom:1px solid var(--border);position:relative">
          <span style="color:var(--go);font-size:.58rem;position:absolute;left:0;top:17px">✦</span>
          <strong style="color:var(--cr);display:block;font-family:'Playfair Display',serif;margin-bottom:3px"><?php echo $f[0]; ?></strong>
          <span style="color:var(--tl);font-size:.9rem"><?php echo esc_html($f[1]); ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <br><a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="btn btn-cr">Contact for Details</a>
    </div>
  </div>
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
  <div class="entry-content" style="margin-top:3rem"><?php the_content(); ?></div>
  <?php endwhile; endif; ?>
</div></div>
<?php sjioc_footer(); get_footer(); ?>
