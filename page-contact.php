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
        <div><h4>Phone</h4><a href="tel:<?php echo preg_replace('/\D','',sjioc_phone()); ?>"><?php echo esc_html(sjioc_phone()); ?></a></div>
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
      <div class="map-placeholder">
        <div>
          <div style="font-size:2.2rem;margin-bottom:8px">📍</div>
          <strong><?php echo esc_html(sjioc_address()); ?></strong><br>
          <a href="<?php echo esc_url(sjioc_maps()); ?>" target="_blank" rel="noopener" style="color:var(--cr);font-size:.82rem;margin-top:8px;display:inline-block;font-weight:700">Open in Google Maps →</a>
        </div>
      </div>
      <!-- Replace above with actual Google Maps iframe: -->
      <!-- <iframe src="https://www.google.com/maps/embed?..." width="100%" height="220" style="border:0;" loading="lazy"></iframe> -->
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
          <option>General Inquiry</option>
          <option>Prayer Request</option>
          <option>Baptism / Marriage</option>
          <option>Ministry Information</option>
          <option>Pastoral Counseling</option>
          <option>Other</option>
        </select>
      </div>
      <div class="form-group"><label for="cf-message">Message <span style="color:var(--cr)">*</span></label><textarea id="cf-message" placeholder="How can we help you?" required></textarea></div>
      <button class="form-submit" id="cf-submit" type="button" onclick="sjiocSubmitForm()">Send Message ✉</button>
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
