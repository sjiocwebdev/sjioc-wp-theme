 <?php
/**
 * Template Name: Contact Us Page
 */
get_header();
?>
<div class="page-hero"><div class="container"><h1>Contact Us</h1><p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › Contact</p></div></div>
<div class="bg-cream"><div class="sec container">
  <div class="contact-layout">

    <!-- Info Column -->
    <div>
      <span class="stag">Reach Out</span>
      <h2 class="stitle" style="text-align:left;font-size:2rem;margin-bottom:8px">Get In Touch</h2>
      <div class="divider divider-l" style="margin-bottom:30px"></div>

      <div class="cdet">
        <div class="cicon">📍</div>
        <div>
          <h4>Our Address</h4>
          <p><?php echo esc_html(sjioc_name()); ?><br><?php echo esc_html(sjioc_address()); ?></p>
        </div>
      </div>
      <div class="cdet">
        <div class="cicon">📞</div>
        <div><h4>Phone</h4><a href="tel:<?php echo preg_replace('/\D/','',sjioc_phone()); ?>"><?php echo esc_html(sjioc_phone()); ?></a></div>
      </div>
      <div class="cdet">
        <div class="cicon">✉</div>
        <div><h4>Email</h4><a href="mailto:<?php echo esc_attr(sjioc_email()); ?>"><?php echo esc_html(sjioc_email()); ?></a></div>
      </div>
      <div class="cdet">
        <div class="cicon">🕐</div>
        <div><h4>Office Hours</h4><p>Saturday: 5:00 PM – 8:00 PM<br>Sunday: After Holy Qurbana</p></div>
      </div>
      <div class="cdet">
        <div class="cicon">🛐</div>
        <div><h4>Sunday Services</h4><p>Holy Qurbana: <?php echo esc_html(sjioc_qurbana()); ?><br>Sunday School: <?php echo esc_html(sjioc_school()); ?></p></div>
      </div>

      <!-- Map -->
      <div style="margin-top:28px;border:1px solid var(--border);overflow:hidden">
        <iframe
          src="https://maps.google.com/maps?q=4400+State+Road,+Drexel+Hill,+PA+19026&t=&z=15&ie=UTF8&iwloc=&output=embed"
          width="100%" height="240" style="border:0;display:block" loading="lazy"
          allowfullscreen referrerpolicy="no-referrer-when-downgrade"
          title="St. John's Indian Orthodox Church — 4400 State Road, Drexel Hill PA"></iframe>
        <div style="padding:10px 14px;background:var(--ww);border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
          <span style="font-size:.82rem;color:var(--tl)"><?php echo esc_html(sjioc_address()); ?></span>
          <a href="<?php echo esc_url(sjioc_maps()); ?>" target="_blank" rel="noopener"
             style="font-size:.78rem;font-weight:700;color:var(--cr);white-space:nowrap;margin-left:12px">Open in Maps →</a>
        </div>
      </div>
    </div>

    <!-- Contact Form -->
    <div class="cform-wrap">
      <h3>Send Us a Message</h3>
      <div id="cf-success" class="form-success">✅ Thank you! Your message has been sent. We'll be in touch soon.</div>
      <?php wp_nonce_field('sjioc_ajax','sjioc_nonce'); ?>
      <div class="form-row-2">
        <div class="form-group"><label for="cf-fname">First Name <span style="color:var(--cr)">*</span></label><input type="text" id="cf-fname" placeholder="John" required></div>
        <div class="form-group"><label for="cf-lname">Last Name</label><input type="text" id="cf-lname" placeholder="Thomas"></div>
      </div>
      <div class="form-group"><label for="cf-email">Email Address <span style="color:var(--cr)">*</span></label><input type="email" id="cf-email" placeholder="john@example.com" required></div>
      <div class="form-group"><label for="cf-phone">Phone</label><input type="tel" id="cf-phone" placeholder="(610) 000-0000"></div>
      <div class="form-group">
        <label for="cf-subject">Subject</label>
        <select id="cf-subject">
          <option value="">Select a subject…</option>
          <option value="Contact the Vicar">Contact the Vicar</option>
          <option value="Contact the Trustee">Contact the Trustee</option>
          <option value="Contact the Secretary">Contact the Secretary</option>
          <option value="General Inquiry">General Inquiry</option>
          <option value="Prayer Request">Prayer Request</option>
          <option value="Baptism / Marriage">Baptism / Marriage</option>
          <option value="Ministry Information">Ministry Information</option>
          <option value="Pastoral Counseling">Pastoral Counseling</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="form-group"><label for="cf-message">Message <span style="color:var(--cr)">*</span></label><textarea id="cf-message" placeholder="How can we help you?" required></textarea></div>
      <button class="form-submit" id="cf-submit" type="button" onclick="sjiocSubmitForm()">Send Message ✉</button>
      <script>
        (function () {
          var map = { vicar: 'Contact the Vicar', trustee: 'Contact the Trustee', secretary: 'Contact the Secretary' };
          var to  = new URLSearchParams(window.location.search).get('to');
          if (to && map[to]) {
            var sel = document.getElementById('cf-subject');
            if (sel) sel.value = map[to];
          }
        })();
      </script>
    </div>

  </div>
</div></div>

<section class="times-band" style="text-align:center">
  <div class="container" style="position:relative">
    <h2 style="font-family:'Playfair Display',serif;color:#fff;font-size:2rem;margin-bottom:12px">Find Us on Sunday</h2>
    <div class="divider"></div>
    <div class="times-row">
      <div class="time-col"><span class="time-label">Holy Qurbana</span><span class="time-val"><?php echo esc_html(sjioc_qurbana()); ?></span></div>
      <div class="time-col"><span class="time-label">Sunday School</span><span class="time-val"><?php echo esc_html(sjioc_school()); ?></span></div>
      <div class="time-col"><span class="time-label">Parish Office</span><span class="time-val">Sat 5:00–7:30 PM</span></div>
    </div>
  </div>
</section>

<?php sjioc_footer(); get_footer(); ?>
