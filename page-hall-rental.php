<?php
/**
 * Template Name: Hall Rental Page
 */
get_header();
$hall_name      = sjioc_get('sjioc_hall_name',           'Parish Hall');
$capacity       = sjioc_get('sjioc_hall_capacity',       '200');
$booking_amount = sjioc_get('sjioc_hall_booking_amount', '650');
$deposit_amount = sjioc_get('sjioc_hall_deposit_amount', '100');
$min_date       = date('Y-m-d', strtotime('+3 days'));
$min_setup_date = date('Y-m-d', strtotime('+2 days'));
?>

<div class="page-hero">
  <div class="container">
    <h1>SJIOC MBM Hall Rental</h1>
    <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › Hall Rental</p>
  </div>
</div>

<!-- ── Hall Showcase ── -->
<section class="bg-cream sec-sm">
  <div class="container tc">
    <span class="stag">Venue &amp; Events</span>
    <h2 class="stitle">Reserve Our <?php echo esc_html($hall_name); ?></h2>
    <div class="divider"></div>
    <p class="slead">A beautiful, versatile venue for celebrations, community gatherings, and special occasions — available to church members and the wider community.</p>
    <div class="hall-features-grid">
      <div class="hall-feat">
        <span class="hall-feat-icon">&#128101;</span>
        <h4>Up to <?php echo esc_html($capacity); ?> Guests</h4>
        <p>Spacious main hall with flexible seating arrangements to suit your event</p>
      </div>
      <div class="hall-feat">
        <span class="hall-feat-icon">&#10052;</span>
        <h4>Air Conditioned Hall</h4>
        <p>Fully air conditioned throughout for your guests' comfort year-round</p>
      </div>
      <div class="hall-feat">
        <span class="hall-feat-icon">&#128682;</span>
        <h4>Separate Hall Entrance</h4>
        <p>Dedicated private entrance to the hall for your event guests</p>
      </div>
      <div class="hall-feat">
        <span class="hall-feat-icon">&#128663;</span>
        <h4>Free Parking</h4>
        <p>Ample on-site parking for all your guests at no additional cost</p>
      </div>
      <div class="hall-feat">
        <span class="hall-feat-icon">&#129681;</span>
        <h4>Tables &amp; Chairs</h4>
        <p>Tables and chairs provided — proper table covering required per our guidelines</p>
      </div>
      <div class="hall-feat">
        <span class="hall-feat-icon">&#127908;</span>
        <h4>BYO Sound System</h4>
        <p>Your sound, your way — plug in your own speakers &amp; mic and own the room</p>
      </div>
    </div>
  </div>
</section>

<!-- ── Booking Form ── -->
<section class="bg-ww sec">
  <div class="container" style="max-width:840px">
    <span class="stag">Book Your Date</span>
    <h2 class="stitle tc">Rental Request Form</h2>
    <div class="divider"></div>
    <p class="slead">Complete the form below. Our team will review your request and respond within 2–3 business days.</p>

    <!-- Progress Indicator -->
    <div class="rental-progress" id="rental-progress" aria-label="Form progress">
      <div class="rp-step rp-active" data-step="1">
        <div class="rp-num">1</div>
        <span>About You</span>
      </div>
      <div class="rp-line"></div>
      <div class="rp-step" data-step="2">
        <div class="rp-num">2</div>
        <span>Event Details</span>
      </div>
      <div class="rp-line"></div>
      <div class="rp-step" data-step="3">
        <div class="rp-num">3</div>
        <span>Facilities</span>
      </div>
      <div class="rp-line"></div>
      <div class="rp-step" data-step="4">
        <div class="rp-num">4</div>
        <span>Review &amp; Submit</span>
      </div>
    </div>

    <div class="rental-form-wrap">
      <?php wp_nonce_field('sjioc_ajax', 'sjioc_nonce'); ?>
      <div style="display:none" aria-hidden="true"><input type="text" id="rf-hp" tabindex="-1" autocomplete="off"></div>

      <!-- ── Step 1: About You ── -->
      <div class="rf-step is-active" id="rf-step-1">
        <h3 class="rf-step-title">Tell us about yourself</h3>
        <div class="form-row-2">
          <div class="form-group">
            <label for="rf-fname">First Name <span class="req">*</span></label>
            <input type="text" id="rf-fname" placeholder="John" autocomplete="given-name">
          </div>
          <div class="form-group">
            <label for="rf-lname">Last Name <span class="req">*</span></label>
            <input type="text" id="rf-lname" placeholder="Thomas" autocomplete="family-name">
          </div>
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label for="rf-email">Email Address <span class="req">*</span></label>
            <input type="email" id="rf-email" placeholder="john@example.com" autocomplete="email">
          </div>
          <div class="form-group">
            <label for="rf-phone">Phone Number <span class="req">*</span></label>
            <input type="tel" id="rf-phone" placeholder="(610) 000-0000" autocomplete="tel">
          </div>
        </div>
        <div class="form-group">
          <label for="rf-address">Home Address</label>
          <input type="text" id="rf-address" placeholder="123 Main St, City, State ZIP" autocomplete="street-address">
        </div>
        <div class="form-group">
          <label for="rf-org-name">Name of Organization <span class="rf-optional">(if applicable)</span></label>
          <input type="text" id="rf-org-name" placeholder="e.g. St. Thomas Fellowship, ABC Corporation">
        </div>
        <div class="form-group">
          <label for="rf-recommended-by">Recommended By <span class="req">*</span></label>
          <input type="text" id="rf-recommended-by" placeholder="Full name of the parish member recommending you">
          <span class="rf-field-hint">A parish member must recommend all applicants (see Terms &amp; Conditions clause 7)</span>
        </div>
        <div class="form-group">
          <label>Are you a parish member?</label>
          <div class="rf-radio-group">
            <label class="rf-radio" id="rl-nonmember">
              <input type="radio" name="rf-member" value="non-member" checked>
              <span class="rf-radio-box"></span>
              No — I am not a member
            </label>
            <label class="rf-radio" id="rl-member">
              <input type="radio" name="rf-member" value="member">
              <span class="rf-radio-box"></span>
              Yes — I am a parish member
            </label>
          </div>
        </div>
        <div class="rf-nav-row">
          <span></span>
          <button class="btn btn-cr rf-next-btn" data-step="1">Next: Event Details <span style="font-size:.9em">&#8594;</span></button>
        </div>
      </div>

      <!-- ── Step 2: Event Details ── -->
      <div class="rf-step" id="rf-step-2">
        <h3 class="rf-step-title">Tell us about your event</h3>
        <div class="form-group">
          <label for="rf-event-type">Type of Event <span class="req">*</span></label>
          <select id="rf-event-type">
            <option value="">Select event type&hellip;</option>
            <option>Birthday / Anniversary Celebration</option>
            <option>Wedding Reception</option>
            <option>Baby Shower / Christening</option>
            <option>Graduation Party</option>
            <option>Community Meeting</option>
            <option>Cultural / Religious Program</option>
            <option>Funeral Reception / Memorial</option>
            <option>Corporate / Professional Event</option>
            <option>Other</option>
          </select>
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label for="rf-event-date">Event Date <span class="req">*</span></label>
            <input type="date" id="rf-event-date" min="<?php echo esc_attr($min_date); ?>">
            <span class="rf-field-hint">Minimum 3 days advance notice required</span>
          </div>
          <div class="form-group">
            <label for="rf-guests">Expected Number of Guests <span class="req">*</span></label>
            <input type="number" id="rf-guests" placeholder="e.g. 80" min="1" max="<?php echo esc_attr($capacity); ?>">
            <span class="rf-field-hint">Maximum capacity: <?php echo esc_html($capacity); ?></span>
          </div>
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label for="rf-start-time">Start Time <span class="req">*</span></label>
            <select id="rf-start-time" class="rf-time-select">
              <option value="">— select start time —</option>
              <option value="08:00">8:00 AM</option>
              <option value="08:30">8:30 AM</option>
              <option value="09:00">9:00 AM</option>
              <option value="09:30">9:30 AM</option>
              <option value="10:00">10:00 AM</option>
              <option value="10:30">10:30 AM</option>
              <option value="11:00">11:00 AM</option>
              <option value="11:30">11:30 AM</option>
              <option value="12:00">12:00 PM</option>
              <option value="12:30">12:30 PM</option>
              <option value="13:00">1:00 PM</option>
              <option value="13:30">1:30 PM</option>
              <option value="14:00">2:00 PM</option>
              <option value="14:30">2:30 PM</option>
              <option value="15:00">3:00 PM</option>
            </select>
          </div>
          <div class="form-group">
            <label for="rf-end-time">End Time <span class="req">*</span></label>
            <select id="rf-end-time" class="rf-time-select">
              <option value="">— select end time —</option>
              <option value="08:30">8:30 AM</option>
              <option value="09:00">9:00 AM</option>
              <option value="09:30">9:30 AM</option>
              <option value="10:00">10:00 AM</option>
              <option value="10:30">10:30 AM</option>
              <option value="11:00">11:00 AM</option>
              <option value="11:30">11:30 AM</option>
              <option value="12:00">12:00 PM</option>
              <option value="12:30">12:30 PM</option>
              <option value="13:00">1:00 PM</option>
              <option value="13:30">1:30 PM</option>
              <option value="14:00">2:00 PM</option>
              <option value="14:30">2:30 PM</option>
              <option value="15:00">3:00 PM</option>
              <option value="15:30">3:30 PM</option>
            </select>
            <span class="rf-field-hint">Events must conclude by 3:30 PM on Saturdays</span>
          </div>
        </div>

        <div class="rf-setup-box">
          <div class="rf-setup-header">&#127912; Hall Setup / Decoration Day <span class="rf-optional">(optional — typically the Friday before)</span></div>
          <p class="rf-hint" style="margin:6px 0 14px">Decoration access is available on Fridays from 6:30 PM – 10:00 PM, subject to availability of an Executive Committee member.</p>
          <div class="form-row-2" style="align-items:start">
            <div class="form-group">
              <label for="rf-setup-date">Setup Date</label>
              <input type="date" id="rf-setup-date" min="<?php echo esc_attr($min_setup_date); ?>">
            </div>
            <div class="form-group" style="display:flex;gap:10px;align-items:flex-start">
              <div style="flex:1">
                <label for="rf-setup-start">From Time</label>
                <select id="rf-setup-start" class="rf-time-select">
                  <option value="18:30">6:30 PM</option>
                  <option value="19:00">7:00 PM</option>
                  <option value="19:30">7:30 PM</option>
                  <option value="20:00">8:00 PM</option>
                  <option value="20:30">8:30 PM</option>
                  <option value="21:00">9:00 PM</option>
                  <option value="21:30">9:30 PM</option>
                </select>
              </div>
              <div style="flex:1">
                <label for="rf-setup-end">To Time</label>
                <select id="rf-setup-end" class="rf-time-select">
                  <option value="19:00">7:00 PM</option>
                  <option value="19:30">7:30 PM</option>
                  <option value="20:00">8:00 PM</option>
                  <option value="20:30">8:30 PM</option>
                  <option value="21:00">9:00 PM</option>
                  <option value="21:30">9:30 PM</option>
                  <option value="22:00" selected>10:00 PM</option>
                </select>
                <span class="rf-field-hint">Must not exceed 10:00 PM</span>
              </div>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label for="rf-purpose">Brief Description of Your Event <span class="req">*</span></label>
          <textarea id="rf-purpose" rows="3" placeholder="Briefly describe your event — purpose, theme, and any key details that would help us prepare for your booking&hellip;"></textarea>
        </div>
        <div class="rf-nav-row">
          <button class="btn btn-ol rf-prev-btn" data-step="2"><span style="font-size:.9em">&#8592;</span> Back</button>
          <button class="btn btn-cr rf-next-btn" data-step="2">Next: Facilities <span style="font-size:.9em">&#8594;</span></button>
        </div>
      </div>

      <!-- ── Step 3: Catering & Notes ── -->
      <div class="rf-step" id="rf-step-3">
        <h3 class="rf-step-title">Catering &amp; Special Requests</h3>
        <div class="rf-included-box">
          <div class="rf-included-title">&#10003; What's Included With Your Booking</div>
          <div class="rf-included-grid">
            <div class="rf-inc-item"><span>&#129681;</span> Tables &amp; Chairs</div>
            <div class="rf-inc-item"><span>&#10052;</span> Air Conditioning</div>
            <div class="rf-inc-item"><span>&#128682;</span> Separate Hall Entrance</div>
            <div class="rf-inc-item"><span>&#128663;</span> Free On-site Parking</div>
            <div class="rf-inc-item"><span>&#127908;</span> BYO Sound System Welcome</div>
          </div>
        </div>
        <div class="form-group" style="margin-top:28px">
          <label for="rf-catering">Catering Arrangement</label>
          <select id="rf-catering">
            <option value="none">No catering needed</option>
            <option value="self">Self-catered (bringing own food)</option>
            <option value="outside">Outside caterer / vendor</option>
          </select>
        </div>
        <div class="form-group">
          <label for="rf-special">Special Requests or Notes</label>
          <textarea id="rf-special" rows="3" placeholder="Any additional requirements, accessibility needs, decoration plans, or questions for our team&hellip;"></textarea>
        </div>
        <div class="rf-nav-row">
          <button class="btn btn-ol rf-prev-btn" data-step="3"><span style="font-size:.9em">&#8592;</span> Back</button>
          <button class="btn btn-cr rf-next-btn" data-step="3">Review My Request <span style="font-size:.9em">&#8594;</span></button>
        </div>
      </div>

      <!-- ── Step 4: Review & Submit ── -->
      <div class="rf-step" id="rf-step-4">
        <h3 class="rf-step-title">Review your request</h3>
        <div class="rf-review-box" id="rf-summary" aria-live="polite"></div>

        <div class="rf-caveat-box">
          <div class="rf-caveat-icon">&#9432;</div>
          <div>
            <strong>This is a Rental Request Only — Not a Booking Confirmation</strong>
            <p>Submitting this form places your request in our review queue. <strong>Rental fees, refundable security deposit, insurance requirements, and all payment arrangements are not handled through this form.</strong> Once the Parish Council reviews your request, our <strong>Parish Secretary or Trustee</strong> will contact you within 2–3 business days to discuss fees and finalize the booking.</p>
            <p style="margin-top:8px">Questions before submitting? <a href="<?php echo esc_url(home_url('/contact-us/?to=secretary')); ?>">Contact the Parish Secretary &rarr;</a></p>
          </div>
        </div>

        <div class="rf-terms-box">
          <div class="rf-terms-title">
            <span class="rf-terms-cross">&#10013;</span>
            Terms &amp; Conditions for Use of SJIOC Church Facility
          </div>
          <div class="rf-terms-scroll">

            <div class="rtc-item">
              <span class="rtc-num">1</span>
              <div>Service charges for use of the facility is <strong>$<?php echo esc_html($booking_amount); ?></strong> and shall be from <strong>9:00 AM – 3:30 PM</strong>, which includes set-up and trash removal. All events (including cleaning) must conclude by 3:30 PM on Saturdays unless arrangements are made <em>in writing</em> with SJIOC.</div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">2</span>
              <div>Hall decoration may be done before a booking (on Fridays) starting at <strong>6:30 PM</strong>. All hall set-up must not exceed <strong>10:00 PM</strong>. This is subject to the availability of an Executive Committee member.</div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">3</span>
              <div>A refundable security deposit of <strong>$<?php echo esc_html($deposit_amount); ?></strong> is required upon signing of contract to guarantee hall reservation.</div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">4</span>
              <div>A contract must be signed <strong>2 weeks prior</strong> to the day of the event, which includes the full service charge of <strong>$<?php echo esc_html($booking_amount); ?></strong> in addition to the refundable security deposit of <strong>$<?php echo esc_html($deposit_amount); ?></strong>.</div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">5</span>
              <div>In case of cancellation, a full refund may be provided if the applicant informs SJIOC at least <strong>seven (7) days</strong> prior to the reservation date. <strong>No refunds</strong> will be made for cancellations less than 7 days before confirmed reservations.</div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">6</span>
              <div><strong>Cooking inside the facility is strictly prohibited.</strong></div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">7</span>
              <div>
                SJIOC reserves the right to bill the applicant (in addition to the security deposit) for any damage to the facility or property caused by the applicant or their guests during the event.
                <div class="rtc-sub"><span class="rtc-sub-label">A.</span> In the event that the applicant is unresponsive, SJIOC will contact the <strong>parish member who recommended the applicant</strong> to mediate a resolution for settling the damages to the hall or property.</div>
              </div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">8</span>
              <div>SJIOC is not responsible for any damage or loss to personal property left at the facility before, during, or after the event.</div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">9</span>
              <div>SJIOC is not responsible for any personal injuries that may happen during the event on SJIOC property.</div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">10</span>
              <div>
                The applicant understands that use of the parking lot is at the vehicle owner's risk. SJIOC is not responsible for any loss or damage to vehicles. All entrance and exit to the hall are to be through the <strong>doors facing the playground area only</strong>.
                <div class="rtc-sub"><span class="rtc-sub-label">A.</span> Toys in the play area outside the double doors are not to be used, and no food items or trash may be left in that area.</div>
                <div class="rtc-sub"><span class="rtc-sub-label">B.</span> Toys inside the hall may not be used.</div>
              </div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">11</span>
              <div>The applicant is responsible for removing all trash and placing it at the designated dumpster. Failure to do so may result in <strong>withholding $50 from the security deposit</strong>.</div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">12</span>
              <div>Tables and chairs provided by SJIOC may be used. Proper table covering must be used for all tables. Any table or chair used must be cleaned and returned to its original location upon completion of the event. Failure to do so may result in <strong>withholding $50 from the security deposit</strong>.</div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">13</span>
              <div>Applicants may bring their own sound system. Speakers may <strong>not</strong> be used outside the hall or in the parking lot. Volume must not be excessive so as to avoid disturbance in the neighborhood.</div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">14</span>
              <div><strong>Weapons, alcohol, gambling, drugs, and smoking of any kind are strictly prohibited on SJIOC's property.</strong></div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">15</span>
              <div>While submitting this application, a copy of the applicant's ID must be attached. Please bring a valid government-issued photo ID to the office when signing the contract.</div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">16</span>
              <div>In case of a medical emergency, please contact <strong>9-1-1</strong>. For other emergencies, please contact the church <?php
                $sec_email = sjioc_get('sjioc_email_secretary', '');
                $tru_email = sjioc_get('sjioc_email_trustee', '');
                echo $sec_email ? '<strong>Secretary</strong> at <a href="mailto:' . esc_attr($sec_email) . '">' . esc_html($sec_email) . '</a>' : '<strong>Secretary</strong>';
                echo ' or the ';
                echo $tru_email ? '<strong>Trustee</strong> at <a href="mailto:' . esc_attr($tru_email) . '">' . esc_html($tru_email) . '</a>' : '<strong>Trustee</strong>';
              ?>.</div>
            </div>

            <div class="rtc-item">
              <span class="rtc-num">17</span>
              <div>Confirmation of reservation is subject to <strong>final approval by the SJIOC Executive Committee</strong>. Submission of this form does not constitute a confirmed booking.</div>
            </div>

            <div class="rtc-sig-block">
              <p>By entering your full name below and checking the agreement box, you acknowledge that you have read, understood, and agree to the terms of this agreement and promise to adhere to all hall rules and restrictions.</p>
            </div>

          </div><!-- /.rf-terms-scroll -->
          <label class="rf-agree-check" for="rf-agree">
            <input type="checkbox" id="rf-agree">
            <span>I have read and agree to the above rental terms and conditions</span>
          </label>
        </div>

        <div class="form-group" style="margin-top:20px">
          <label for="rf-signature">Full Name — Digital Signature <span class="req">*</span></label>
          <input type="text" id="rf-signature" placeholder="Type your full legal name to sign digitally" autocomplete="name">
          <span class="rf-field-hint">By entering your name you confirm agreement to the terms above</span>
        </div>

        <div id="rf-form-success" class="form-success" style="display:none">
          &#10003; Your rental request has been submitted successfully! Our team will contact you at your provided email within 2–3 business days. Please check your inbox for a confirmation email with your reference number.
        </div>
        <div id="rf-form-error" class="rf-error-msg" style="display:none"></div>

        <div class="rf-nav-row" id="rf-step4-nav">
          <button class="btn btn-ol rf-prev-btn" data-step="4"><span style="font-size:.9em">&#8592;</span> Edit Request</button>
          <button class="btn btn-cr" id="rf-submit-btn">Submit Rental Request &#10003;</button>
        </div>
      </div>

    </div><!-- /.rental-form-wrap -->
  </div>
</section>

<!-- ── CTA Strip ── -->
<section class="times-band" style="text-align:center">
  <div class="container" style="position:relative">
    <h2 style="font-family:'Playfair Display',serif;color:#fff;font-size:2rem;margin-bottom:12px">Have Questions?</h2>
    <div class="divider"></div>
    <p style="color:rgba(255,255,255,.78);max-width:560px;margin:0 auto 28px;font-size:1rem;line-height:1.7">
      For pricing, availability, or any other rental enquiries, contact our Parish Secretary directly.
    </p>
    <a href="<?php echo esc_url(home_url('/contact-us/?to=secretary')); ?>" class="btn btn-go">Contact the Secretary</a>
  </div>
</section>

<?php sjioc_footer(); get_footer(); ?>

<script>
(function () {
  'use strict';

  var currentStep = 1;

  /* ── Helpers ── */
  function q(id)  { return document.getElementById(id); }
  function escH(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function val(id) { var el = q(id); return el ? el.value.trim() : ''; }
  function checked(id) { var el = q(id); return el ? el.checked : false; }

  /* ── Progress ── */
  function updateProgress(step) {
    document.querySelectorAll('.rp-step').forEach(function (el) {
      var s = parseInt(el.dataset.step, 10);
      el.classList.toggle('rp-active', s === step);
      el.classList.toggle('rp-done',   s < step);
    });
    document.querySelectorAll('.rp-line').forEach(function (el, i) {
      el.classList.toggle('rp-line-done', i < step - 1);
    });
  }

  /* ── Show step ── */
  function showStep(step) {
    document.querySelectorAll('.rf-step').forEach(function (el) {
      el.classList.remove('is-active');
    });
    var target = q('rf-step-' + step);
    if (target) {
      target.classList.add('is-active');
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    updateProgress(step);
    currentStep = step;
    if (step === 4) buildSummary();
  }

  /* ── Field error ── */
  function fmt24to12(t) {
    if (!t) return '';
    var p = t.split(':'), h = parseInt(p[0], 10), m = p[1];
    var period = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return h + ':' + m + ' ' + period;
  }

  function markError(el, msg) {
    el.style.borderColor = 'var(--cr)';
    el.setAttribute('aria-invalid', 'true');
    var hint = el.parentElement.querySelector('.rf-field-error');
    if (!hint) {
      hint = document.createElement('span');
      hint.className = 'rf-field-error';
      el.parentElement.appendChild(hint);
    }
    hint.textContent = msg;
  }
  function clearErrors(container) {
    container.querySelectorAll('[style*="border-color"]').forEach(function (el) {
      el.style.borderColor = '';
      el.removeAttribute('aria-invalid');
    });
    container.querySelectorAll('.rf-field-error').forEach(function (el) { el.remove(); });
  }

  /* ── Validate step ── */
  function validateStep(step) {
    var container = q('rf-step-' + step);
    clearErrors(container);
    var ok = true;

    function require(id, msg) {
      var el = q(id);
      if (!el || !el.value.trim()) { markError(el, msg || 'This field is required.'); ok = false; }
      return el;
    }

    if (step === 1) {
      require('rf-fname', 'First name is required.');
      require('rf-lname', 'Last name is required.');
      var emailEl = require('rf-email', 'Email is required.');
      if (emailEl && emailEl.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value)) {
        markError(emailEl, 'Please enter a valid email address.'); ok = false;
      }
      require('rf-phone', 'Phone number is required.');
      require('rf-recommended-by', 'Please enter the name of the parish member recommending you.');
    }
    if (step === 2) {
      require('rf-event-type', 'Please select an event type.');
      require('rf-event-date', 'Please select a date.');
      require('rf-guests',     'Please enter expected guests.');
      require('rf-start-time', 'Start time is required.');
      require('rf-end-time',   'End time is required.');
      require('rf-purpose',    'Please describe your event.');

      var st = q('rf-start-time'), et = q('rf-end-time');
      if (st && et && st.value && et.value && et.value <= st.value) {
        markError(et, 'End time must be after start time.'); ok = false;
      }
    }
    return ok;
  }

  /* ── Build review summary ── */
  function buildSummary() {
    var memberEl = document.querySelector('input[name="rf-member"]:checked');
    var memberLabel = memberEl && memberEl.value === 'member' ? 'Yes — Parish Member' : 'No — Non-Member';
    var catEl = q('rf-catering');
    var catMap = { none: 'None', self: 'Self-catered', outside: 'Outside caterer' };
    var dateVal = val('rf-event-date');
    var dateStr = dateVal
      ? new Date(dateVal + 'T12:00:00').toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' })
      : '—';

    function row(label, value) {
      return '<tr><td class="rfs-label">' + escH(label) + '</td><td class="rfs-value">' + (value || '&mdash;') + '</td></tr>';
    }

    var setupDateVal = val('rf-setup-date');
    var setupDateStr = setupDateVal
      ? new Date(setupDateVal + 'T12:00:00').toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' })
      : '';
    var setupStr = setupDateStr
      ? setupDateStr + ' · ' + (fmt24to12(val('rf-setup-start')) || '6:30 PM') + ' – ' + (fmt24to12(val('rf-setup-end')) || '10:00 PM')
      : 'None requested';

    var html = '<div class="rfs-grid">';
    html += '<div class="rfs-section"><h5>Applicant</h5><table>';
    html += row('Name',             escH(val('rf-fname') + ' ' + val('rf-lname')));
    html += row('Email',            escH(val('rf-email')));
    html += row('Phone',            escH(val('rf-phone')) || '&mdash;');
    html += row('Address',          escH(val('rf-address')) || '&mdash;');
    html += row('Organization',     escH(val('rf-org-name')) || '&mdash;');
    html += row('Recommended By',   escH(val('rf-recommended-by')));
    html += row('Member Status',    escH(memberLabel));
    html += '</table></div>';

    html += '<div class="rfs-section"><h5>Event Details</h5><table>';
    html += row('Event Type',       escH(val('rf-event-type')));
    html += row('Event Date',       escH(dateStr));
    html += row('Event Time',       escH(fmt24to12(val('rf-start-time')) + ' – ' + fmt24to12(val('rf-end-time'))));
    html += row('Setup / Deco Day', escH(setupStr));
    html += row('Expected Guests',  escH(val('rf-guests')));
    html += row('Description',      escH(val('rf-purpose')));
    html += '</table></div>';

    html += '<div class="rfs-section rfs-full"><h5>Additional Details</h5><table>';
    html += row('Catering',      escH(catMap[catEl ? catEl.value : 'none'] || '—'));
    html += row('Special Notes', escH(val('rf-special')) || '&mdash;');
    html += '</table></div>';
    html += '</div>';

    q('rf-summary').innerHTML = html;
  }

  /* ── Wire up navigation ── */
  document.querySelectorAll('.rf-next-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var step = parseInt(btn.dataset.step, 10);
      if (validateStep(step)) showStep(step + 1);
    });
  });
  document.querySelectorAll('.rf-prev-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var step = parseInt(btn.dataset.step, 10);
      showStep(step - 1);
    });
  });

  /* ── Submit ── */
  var submitBtn = q('rf-submit-btn');
  if (submitBtn) {
    submitBtn.addEventListener('click', function () {
      var errorBox = q('rf-form-error');
      errorBox.style.display = 'none';

      if (!checked('rf-agree')) {
        errorBox.textContent = 'Please read and agree to the rental terms and conditions.';
        errorBox.style.display = 'block';
        errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        return;
      }
      if (!val('rf-signature')) {
        var sigEl = q('rf-signature');
        markError(sigEl, 'Please enter your full name as a digital signature.');
        sigEl.focus();
        return;
      }

      // Honeypot check
      var hpField = q('rf-hp');
      if (hpField && hpField.value.trim()) return;

      submitBtn.disabled    = true;
      submitBtn.textContent = '⏳ Submitting…';

      var memberEl = document.querySelector('input[name="rf-member"]:checked');
      var nonce    = document.querySelector('input[name="sjioc_nonce"]');

      function doRentalSubmit(rcToken) {
        fetch(typeof sjioData !== 'undefined' ? sjioData.ajaxUrl : '/wp-admin/admin-ajax.php', {
          method: 'POST',
          body: new URLSearchParams({
            action:           'sjioc_rental_request',
            nonce:            nonce ? nonce.value : '',
            fname:            val('rf-fname'),
            lname:            val('rf-lname'),
            email:            val('rf-email'),
            phone:            val('rf-phone'),
            address:          val('rf-address'),
            org_name:         val('rf-org-name'),
            recommended_by:   val('rf-recommended-by'),
            member_status:    memberEl ? memberEl.value : 'non-member',
            event_type:       val('rf-event-type'),
            event_date:       val('rf-event-date'),
            start_time:       val('rf-start-time'),
            end_time:         val('rf-end-time'),
            setup_date:       val('rf-setup-date'),
            setup_start_time: val('rf-setup-start'),
            setup_end_time:   val('rf-setup-end'),
            guests:           val('rf-guests'),
            event_purpose:    val('rf-purpose'),
            catering:         q('rf-catering') ? q('rf-catering').value : 'none',
            special_req:      val('rf-special'),
            signature:        val('rf-signature'),
            recaptcha_token:  rcToken || '',
          })
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d.success) {
            q('rf-form-success').style.display = 'block';
            q('rf-step4-nav').style.display    = 'none';
            q('rf-form-success').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          } else {
            errorBox.textContent    = (d.data && d.data.msg) ? d.data.msg : 'An error occurred. Please try again.';
            errorBox.style.display  = 'block';
            submitBtn.disabled      = false;
            submitBtn.textContent   = 'Submit Rental Request ✓';
            errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          }
        })
        .catch(function () {
          errorBox.textContent   = 'Network error. Please check your connection and try again, or call us directly.';
          errorBox.style.display = 'block';
          submitBtn.disabled     = false;
          submitBtn.textContent  = 'Submit Rental Request ✓';
        });
      }

      var rcKey = typeof sjioData !== 'undefined' ? sjioData.recaptchaKey : '';
      if (rcKey && typeof grecaptcha !== 'undefined') {
        grecaptcha.ready(function () {
          grecaptcha.execute(rcKey, { action: 'hall_rental' })
            .then(doRentalSubmit)
            .catch(function () { doRentalSubmit(''); });
        });
      } else {
        doRentalSubmit('');
      }
    });
  }

  /* ── Radio visual ── */
  document.querySelectorAll('.rf-radio input').forEach(function (r) {
    r.addEventListener('change', function () {
      document.querySelectorAll('.rf-radio').forEach(function (l) { l.classList.remove('is-selected'); });
      if (r.checked) r.closest('.rf-radio').classList.add('is-selected');
    });
    if (r.checked) r.closest('.rf-radio').classList.add('is-selected');
  });

  /* ── Checkbox card visual ── */
  document.querySelectorAll('.hcl-item input').forEach(function (cb) {
    cb.addEventListener('change', function () {
      cb.closest('.hcl-item').classList.toggle('is-checked', cb.checked);
    });
  });

})();
</script>
