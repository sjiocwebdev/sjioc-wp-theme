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
<section id="core-values" class="times-band" aria-labelledby="values-heading">
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
<div id="leadership" class="bg-cream"><div class="sec container tc">
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

  <!-- Jt. Office Bearers -->
  <div class="jt-bearers">
    <div class="jt-pill"><span class="jt-role">Jt. Trustee</span><span class="jt-name">Mr. Subin John</span></div>
    <div class="jt-pill"><span class="jt-role">Jt. Secretary</span><span class="jt-name">Mr. Lijo P. Saji</span></div>
  </div>

</div></div>

<!-- Parish Committees -->
<div id="committees" class="bg-ww"><div class="sec container">
  <div class="tc" style="margin-bottom:36px">
    <span class="stag">Parish Governance</span>
    <h2 class="stitle">Committees &amp; Organizations</h2>
    <div class="divider"></div>
    <p class="slead">The dedicated members who serve our parish community through administration, ministry, and outreach.</p>
  </div>

  <!-- Tab bar -->
  <div class="cmte-tabs" role="tablist">
    <button class="cmte-tab is-active" role="tab" data-panel="cmte-admin">Parish Administration</button>
    <button class="cmte-tab" role="tab" data-panel="cmte-spiritual">Spiritual Organizations</button>
  </div>

  <!-- ── Panel 1: Parish Administration ── -->
  <div class="cmte-panel" id="cmte-admin">

    <!-- Managing Committee -->
    <div class="acc-item is-open">
      <button class="acc-header" aria-expanded="true">
        <span>Managing Committee</span><span class="acc-chevron">&#8964;</span>
      </button>
      <div class="acc-body">
        <ul class="acc-grid">
          <li>Jinoy Reji</li>
          <li>Glee Joseph Abraham</li>
          <li>Kuriakose John</li>
          <li>Kuruvilla Abraham</li>
          <li>Mathew Kurian</li>
          <li>Thomas Thommen</li>
          <li>Varughese George (Pinto)</li>
        </ul>
      </div>
    </div>

    <!-- Auditors & Association -->
    <div class="acc-item">
      <button class="acc-header" aria-expanded="false">
        <span>Auditors &amp; Association Members</span><span class="acc-chevron">&#8964;</span>
      </button>
      <div class="acc-body" style="display:none">
        <p class="acc-sub-label">Auditors</p>
        <ul class="acc-grid">
          <li>Alex Manappallil Joy</li>
          <li>Joseph George</li>
        </ul>
        <p class="acc-sub-label" style="margin-top:16px">Association Members</p>
        <ul class="acc-role-list">
          <li><span class="acc-role">Malankara Association</span><span class="acc-name">George Mathew</span></li>
          <li><span class="acc-role">Diocese Association</span><span class="acc-name">Vargheese Baby</span></li>
        </ul>
      </div>
    </div>

    <!-- Event Coordinators -->
    <div class="acc-item">
      <button class="acc-header" aria-expanded="false">
        <span>Event Coordinators</span><span class="acc-chevron">&#8964;</span>
      </button>
      <div class="acc-body" style="display:none">
        <ul class="acc-role-list">
          <li><span class="acc-role">Prayer Meeting</span><span class="acc-name">Agi Bensen</span></li>
          <li><span class="acc-role">Christmas</span><span class="acc-name">Ninan J. Poovathoor, Varughese George (Pinto)</span></li>
          <li><span class="acc-role">Picnic</span><span class="acc-name">Kuruvilla Abraham, Jijoy Reji</span></li>
          <li><span class="acc-role">Perunnal &amp; Reception</span><span class="acc-name">Letha Varghese, Shibu Thomas</span></li>
          <li><span class="acc-role">Family Day</span><span class="acc-name">Kuriakose John, Letha Varghese</span></li>
        </ul>
      </div>
    </div>

    <!-- Ecumenical -->
    <div class="acc-item">
      <button class="acc-header" aria-expanded="false">
        <span>Ecumenical Members</span><span class="acc-chevron">&#8964;</span>
      </button>
      <div class="acc-body" style="display:none">
        <ul class="acc-grid">
          <li>George Mathew</li>
          <li>Vargheese Baby</li>
        </ul>
      </div>
    </div>

  </div><!-- /cmte-admin -->

  <!-- ── Panel 2: Spiritual Organizations ── -->
  <div class="cmte-panel" id="cmte-spiritual" style="display:none">

    <!-- Sunday School -->
    <div class="acc-item is-open">
      <button class="acc-header" aria-expanded="true">
        <span>Sunday School</span><span class="acc-chevron">&#8964;</span>
      </button>
      <div class="acc-body">
        <ul class="acc-role-list">
          <li><span class="acc-role">Principal</span><span class="acc-name">Glee Joseph Abraham</span></li>
          <li><span class="acc-role">Vice Principal</span><span class="acc-name">Ambily Abraham</span></li>
        </ul>
      </div>
    </div>

    <!-- MMVS -->
    <div class="acc-item">
      <button class="acc-header" aria-expanded="false">
        <span>MMVS</span><span class="acc-chevron">&#8964;</span>
      </button>
      <div class="acc-body" style="display:none">
        <ul class="acc-role-list">
          <li><span class="acc-role">Treasurer</span><span class="acc-name">Suja Monzy</span></li>
          <li><span class="acc-role">Secretary</span><span class="acc-name">Elizabeth Chacko</span></li>
          <li><span class="acc-role">Joint Secretary</span><span class="acc-name">Tina Biju</span></li>
          <li><span class="acc-role">Diocesan Delegate</span><span class="acc-name">Annamma Varghese</span></li>
        </ul>
      </div>
    </div>

    <!-- MGOCSM -->
    <div class="acc-item">
      <button class="acc-header" aria-expanded="false">
        <span>MGOCSM</span><span class="acc-chevron">&#8964;</span>
      </button>
      <div class="acc-body" style="display:none">
        <ul class="acc-role-list">
          <li><span class="acc-role">Treasurer</span><span class="acc-name">Nikhil Joseph</span></li>
          <li><span class="acc-role">Secretary</span><span class="acc-name">Ruben Varghese</span></li>
          <li><span class="acc-role">Joint Secretary</span><span class="acc-name">Jonathan Jins</span></li>
          <li><span class="acc-role">Area Representative</span><span class="acc-name">William Pothen</span></li>
        </ul>
      </div>
    </div>

    <!-- FOCUS -->
    <div class="acc-item">
      <button class="acc-header" aria-expanded="false">
        <span>FOCUS</span><span class="acc-chevron">&#8964;</span>
      </button>
      <div class="acc-body" style="display:none">
        <ul class="acc-role-list">
          <li><span class="acc-role">Treasurer</span><span class="acc-name">Cherian Sabu Kulangara</span></li>
          <li><span class="acc-role">Secretary</span><span class="acc-name">Saju Thomas</span></li>
          <li><span class="acc-role">Joint Secretary</span><span class="acc-name">Roshan George</span></li>
        </ul>
      </div>
    </div>

    <!-- GROW -->
    <div class="acc-item">
      <button class="acc-header" aria-expanded="false">
        <span>GROW</span><span class="acc-chevron">&#8964;</span>
      </button>
      <div class="acc-body" style="display:none">
        <ul class="acc-role-list">
          <li><span class="acc-role">Treasurer</span><span class="acc-name">Abiya Raju</span></li>
          <li><span class="acc-role">Secretary</span><span class="acc-name">Divya Joseph</span></li>
          <li><span class="acc-role">Joint Secretary</span><span class="acc-name">Terrina Daniel</span></li>
          <li><span class="acc-role">Area Representative</span><span class="acc-name">Agi Bensen</span></li>
        </ul>
      </div>
    </div>

    <!-- Men's Forum -->
    <div class="acc-item">
      <button class="acc-header" aria-expanded="false">
        <span>Men's Forum</span><span class="acc-chevron">&#8964;</span>
      </button>
      <div class="acc-body" style="display:none">
        <ul class="acc-role-list">
          <li><span class="acc-role">Secretary</span><span class="acc-name">Daniel P. George</span></li>
          <li><span class="acc-role">Jt. Secretary</span><span class="acc-name">Sujoy Abraham</span></li>
        </ul>
      </div>
    </div>

  </div><!-- /cmte-spiritual -->

</div></div>

<!-- Timeline -->
<div id="history" class="bg-ww"><div class="sec container tc">
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
<style>
/* ── Jt. Office Bearers ── */
.jt-bearers {
  display: flex;
  justify-content: center;
  gap: 16px;
  flex-wrap: wrap;
  margin-top: 28px;
}
.jt-pill {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--ww);
  border: 1px solid var(--border);
  border-left: 3px solid var(--go);
  padding: 10px 20px;
  border-radius: 2px;
}
.jt-role {
  font-size: .67rem;
  font-weight: 700;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--go);
}
.jt-name {
  font-size: .88rem;
  font-weight: 600;
  color: var(--cr);
}

/* ── Committee Tabs ── */
.cmte-tabs {
  display: flex;
  justify-content: center;
  gap: 0;
  margin-bottom: 32px;
  border-bottom: 2px solid #e5e7eb;
}
.cmte-tab {
  padding: 11px 32px;
  font-size: .84rem;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: uppercase;
  border: none;
  background: none;
  cursor: pointer;
  color: var(--tl);
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
  transition: color .2s, border-color .2s;
}
.cmte-tab:hover   { color: var(--go); }
.cmte-tab.is-active { color: var(--go); border-bottom-color: var(--go); }

/* ── Accordion ── */
.acc-item {
  border: 1px solid var(--border);
  border-radius: 2px;
  margin-bottom: 8px;
  overflow: hidden;
}
.acc-header {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background: var(--ww);
  border: none;
  cursor: pointer;
  font-size: .9rem;
  font-weight: 700;
  color: var(--cr);
  text-align: left;
  transition: background .18s;
}
.acc-header:hover { background: #fdf6e8; }
.acc-item.is-open .acc-header { background: #fdf6e8; border-bottom: 1px solid var(--border); }
.acc-chevron {
  font-size: 1.2rem;
  color: var(--go);
  transition: transform .25s;
  flex-shrink: 0;
}
.acc-item.is-open .acc-chevron { transform: rotate(180deg); }

.acc-body {
  padding: 20px;
  background: #fff;
  animation: accFade .2s ease;
}
@keyframes accFade { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }

/* Member name grid */
.acc-grid {
  list-style: none;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px 16px;
  margin: 0; padding: 0;
}
.acc-grid li {
  font-size: .87rem;
  color: var(--tm);
  padding: 6px 10px;
  background: var(--ww);
  border-left: 2px solid var(--go);
}
@media (max-width: 640px) { .acc-grid { grid-template-columns: 1fr 1fr; } }

/* Role + name rows */
.acc-role-list {
  list-style: none;
  margin: 0; padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.acc-role-list li {
  display: flex;
  align-items: baseline;
  gap: 12px;
  padding: 6px 0;
  border-bottom: 1px solid #f0ece4;
}
.acc-role-list li:last-child { border-bottom: none; }
.acc-role {
  font-size: .67rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--go);
  min-width: 140px;
  flex-shrink: 0;
}
.acc-name {
  font-size: .88rem;
  color: var(--tm);
}
.acc-sub-label {
  font-size: .67rem;
  font-weight: 700;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: #999;
  margin: 0 0 8px;
}
</style>

<script>
(function () {
  // ── Tab switching ─────────────────────────────────
  document.querySelectorAll('.cmte-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
      document.querySelectorAll('.cmte-tab').forEach(function (t) { t.classList.remove('is-active'); });
      document.querySelectorAll('.cmte-panel').forEach(function (p) { p.style.display = 'none'; });
      this.classList.add('is-active');
      var panel = document.getElementById(this.dataset.panel);
      if (panel) panel.style.display = '';
    });
  });

  // ── Accordion ────────────────────────────────────
  document.querySelectorAll('.acc-header').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item = this.closest('.acc-item');
      var body = item.querySelector('.acc-body');
      var open = item.classList.contains('is-open');

      // Close all in same panel
      var panel = item.closest('.cmte-panel');
      panel.querySelectorAll('.acc-item').forEach(function (i) {
        i.classList.remove('is-open');
        i.querySelector('.acc-header').setAttribute('aria-expanded', 'false');
        i.querySelector('.acc-body').style.display = 'none';
      });

      // Toggle clicked
      if (!open) {
        item.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        body.style.display = '';
      }
    });
  });
})();
</script>

<?php sjioc_footer(); get_footer(); ?>
