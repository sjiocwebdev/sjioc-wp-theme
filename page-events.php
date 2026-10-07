<?php
/**
 * Template Name: Events Calendar
 */
get_header();

?>
<div class="page-hero"><div class="container"><h1>Parish Events</h1><p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › Events</p></div></div>

<div class="bg-cream">
  <div class="sec container sjioc-events">

    <div class="tc" style="margin-bottom:42px">
      <span class="stag">What's On</span>
      <h2 class="stitle">Parish Calendar</h2>
      <div class="divider"></div>
      <p class="slead">Stay connected with worship, fellowship, and community events at <?php echo esc_html(sjioc_abbr()); ?>.</p>
    </div>

    <div class="ev-view-bar" id="ev-view-bar" role="group" aria-label="Switch view">
      <button class="ev-view-btn is-active" data-view="calendar" aria-pressed="true">&#128197; Calendar</button>
      <button class="ev-view-btn" data-view="list" aria-pressed="false">&#9776; List</button>
    </div>

    <!-- Calendar view -->
    <div id="ev-calendar-view" style="display:none">
      <div class="ev-cal-nav">
        <button class="btn btn-ol ev-cal-arrow" id="ev-cal-prev" aria-label="Previous month">&#10094;</button>
        <h3 id="ev-cal-month"></h3>
        <button class="btn btn-ol ev-cal-arrow" id="ev-cal-next" aria-label="Next month">&#10095;</button>
      </div>
      <div class="ev-cal-grid" id="ev-cal-grid" role="grid" aria-label="Event calendar"></div>
    </div>

    <!-- List view -->
    <div id="ev-list-view" style="display:none">
      <div id="ev-list-grid"></div>
      <p id="ev-list-empty" class="tc slead" style="display:none">No upcoming events found.</p>
    </div>

    <p id="ev-loading" class="tc" style="color:var(--tl);margin:48px 0">Loading events&hellip;</p>
    <p id="ev-error"   class="tc" style="display:none;color:var(--cr);margin:48px 0">Could not load events. Please try again later.</p>

    <?php
    $ev_ics_https  = rest_url('sjioc/v1/calendar.ics');
    $ev_ics_webcal = preg_replace('#^https?://#', 'webcal://', $ev_ics_https);
    ?>
    <div class="ev-subscribe" id="calendar">
      <span>Subscribe to our calendar:</span>
      <a href="<?php echo esc_url($ev_ics_webcal, ['http', 'https', 'webcal']); ?>" class="btn btn-ol btn-sm">&#128197; Subscribe to Calendar</a>
      <p class="ev-subscribe-note">Subscribe once per device — subscribing again adds a second copy of every event.
        <button type="button" class="ev-unsub-open" id="ev-unsub-open" aria-haspopup="dialog">How to remove a subscription</button></p>
    </div>

  </div>
</div>

<!-- Event modal -->
<div class="ev-modal" id="ev-modal" role="dialog" aria-modal="true" aria-label="Event details"
     onclick="if(event.target===this)evCloseModal()">
  <div class="ev-modal-inner">
    <button class="ev-modal-close" onclick="evCloseModal()" aria-label="Close">&times;</button>
    <img id="em-photo" class="ev-modal-photo" alt="" hidden>
    <div class="ev-modal-date-box">
      <span class="ev-mon" id="em-mon"></span>
      <span class="ev-day" id="em-day"></span>
    </div>
    <h2 id="em-title"></h2>
    <p id="em-time" class="ev-modal-meta"></p>
    <p id="em-loc"  class="ev-modal-meta"></p>
    <div id="em-desc"></div>
    <a id="em-gcal" href="#" target="_blank" rel="noopener noreferrer" class="btn btn-cr" style="display:none;margin-top:18px">Open in Google Calendar</a>
  </div>
</div>

<!-- Remove-subscription help modal -->
<div class="ev-modal" id="ev-unsub-modal" role="dialog" aria-modal="true" aria-labelledby="ev-unsub-title">
  <div class="ev-modal-inner ev-unsub-inner">
    <button type="button" class="ev-modal-close" id="ev-unsub-close" aria-label="Close">&times;</button>
    <h2 id="ev-unsub-title">Remove a Calendar Subscription</h2>
    <p class="ev-unsub-intro">Seeing every event twice? You've subscribed more than once — keep one copy and remove the others.
      Find your calendar app below and follow the steps.</p>

    <details class="ev-unsub-item">
      <summary>iPhone &amp; iPad</summary>
      <ol>
        <li>Open the <strong>Calendar</strong> app and tap <strong>Calendars</strong> at the bottom.</li>
        <li>Tap the <strong>&#9432;</strong> next to the <?php echo esc_html(sjioc_abbr()); ?> calendar.</li>
        <li>Scroll down and tap <strong>Unsubscribe</strong> (or <strong>Delete Calendar</strong>).</li>
      </ol>
      <p>Or: <strong>Settings → Apps → Calendar → Calendar Accounts → Subscribed Calendars</strong> (older iOS: <strong>Settings → Calendar → Accounts</strong>), tap the calendar, then <strong>Delete Account</strong>.</p>
    </details>

    <details class="ev-unsub-item">
      <summary>Mac (Calendar app)</summary>
      <ol>
        <li>Open <strong>Calendar</strong>. If the calendar list is hidden, choose <strong>View → Show Calendar List</strong>.</li>
        <li>Control-click (right-click) the <?php echo esc_html(sjioc_abbr()); ?> calendar.</li>
        <li>Choose <strong>Unsubscribe</strong>.</li>
      </ol>
    </details>

    <details class="ev-unsub-item">
      <summary>Google Calendar &amp; Android</summary>
      <ol>
        <li>On a computer, go to <strong>calendar.google.com</strong> (the Android app can only hide a subscription, not remove it).</li>
        <li>Under <strong>Other calendars</strong> on the left, point to the <?php echo esc_html(sjioc_abbr()); ?> calendar and click <strong>&#8942;</strong> → <strong>Settings</strong>.</li>
        <li>Scroll to <strong>Remove calendar</strong> and click <strong>Unsubscribe</strong>.</li>
      </ol>
    </details>

    <details class="ev-unsub-item">
      <summary>Outlook for Windows</summary>
      <ol>
        <li>Open the <strong>Calendar</strong> view.</li>
        <li>In the calendar list on the left, right-click the <?php echo esc_html(sjioc_abbr()); ?> calendar (usually under <strong>Other calendars</strong>).</li>
        <li>Choose <strong>Delete Calendar</strong> (new Outlook: <strong>Remove</strong>).</li>
      </ol>
    </details>

    <details class="ev-unsub-item">
      <summary>Outlook on the web &amp; Outlook.com</summary>
      <ol>
        <li>Open <strong>Calendar</strong>.</li>
        <li>Under <strong>Other calendars</strong> on the left, point to the <?php echo esc_html(sjioc_abbr()); ?> calendar and click <strong>&#8943;</strong> (More options).</li>
        <li>Choose <strong>Remove</strong>.</li>
      </ol>
    </details>

    <p class="ev-unsub-disclaimer">Calendar subscriptions are added and managed entirely within your own calendar app.
      <?php echo esc_html(sjioc_name()); ?> cannot add, change, or remove a subscription on your device and is not responsible
      for changes made in your calendar app. Menu names can vary slightly between app versions.</p>
  </div>
</div>

<?php sjioc_footer(); get_footer(); ?>
