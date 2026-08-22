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
        <div class="cicon"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6.5a2.5 2.5 0 010 5z" fill="currentColor"/></svg></div>
        <div>
          <h4>Our Address</h4>
          <p><?php echo esc_html(sjioc_name()); ?><br><?php echo esc_html(sjioc_address()); ?></p>
        </div>
      </div>
      <div class="cdet">
        <div class="cicon"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.86 11a19.79 19.79 0 01-3.07-8.67A2 2 0 012.77 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.91 8.59a16 16 0 006.5 6.5l1.95-1.35a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" fill="currentColor"/></svg></div>
        <div><h4>Phone</h4><a href="tel:<?php echo preg_replace('/\D/','',sjioc_phone()); ?>"><?php echo esc_html(sjioc_phone()); ?></a></div>
      </div>
      <div class="cdet">
        <div class="cicon"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" fill="currentColor"/></svg></div>
        <div><h4>Email</h4><a href="mailto:<?php echo esc_attr(sjioc_email()); ?>"><?php echo esc_html(sjioc_email()); ?></a></div>
      </div>
      <div class="cdet">
        <div class="cicon"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3.5 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <div>
          <h4>Worship Times</h4>
          <p>
            <?php foreach (sjioc_get_worship_times() as $i => $wt): ?>
              <?php echo esc_html($wt['label']); ?>: <?php echo esc_html($wt['time']); ?><?php echo $i < 4 ? '<br>' : ''; ?>
            <?php endforeach; ?>
          </p>
        </div>
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
      <div style="display:none" aria-hidden="true"><input type="text" id="cf-hp" tabindex="-1" autocomplete="off"></div>
      <div class="form-row-2">
        <div class="form-group"><label for="cf-fname">First Name <span style="color:var(--cr)">*</span></label><input type="text" id="cf-fname" placeholder="John" required></div>
        <div class="form-group"><label for="cf-lname">Last Name</label><input type="text" id="cf-lname" placeholder="Thomas"></div>
      </div>
      <div class="form-group"><label for="cf-email">Email Address <span style="color:var(--cr)">*</span></label><input type="email" id="cf-email" placeholder="john@example.com" required></div>
      <div class="form-group"><label for="cf-phone">Phone</label><input type="tel" id="cf-phone" placeholder="(610) 000-0000"></div>
      <div class="form-group">
        <label for="cf-subject">Subject</label>
        <select id="cf-subject"><?php echo sjioc_contact_subject_options_html(); ?></select>
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
      <div class="time-col"><span class="time-label">Saturday</span><span class="time-val">Evening Prayer <?php echo esc_html(sjioc_sat_evening()); ?></span></div>
    </div>
  </div>
</section>

<?php sjioc_footer(); get_footer(); ?>
