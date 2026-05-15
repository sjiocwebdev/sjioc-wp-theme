<?php
/**
 * Template Name: Events Calendar
 */
get_header();

$gcal_subscribe = SJIOC_GCAL_ID  ? 'https://calendar.google.com/calendar/r?cid=' . rawurlencode(SJIOC_GCAL_ID)  : '';
$gcal_ics       = SJIOC_GCAL_ICS ?: '';
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

    <?php if ($gcal_subscribe || $gcal_ics) : ?>
    <div class="ev-subscribe" id="calendar">
      <span>Subscribe to our calendar:</span>
      <?php if ($gcal_subscribe) : ?>
      <a href="<?php echo esc_url($gcal_subscribe); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-ol btn-sm">&#128197; Google Calendar</a>
      <?php endif; ?>
      <?php if ($gcal_ics) : ?>
      <a href="<?php echo esc_url($gcal_ics); ?>" class="btn btn-ol btn-sm">&#128462; Download ICS</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>
</div>

<!-- Event modal -->
<div class="ev-modal" id="ev-modal" role="dialog" aria-modal="true" aria-label="Event details"
     onclick="if(event.target===this)evCloseModal()">
  <div class="ev-modal-inner">
    <button class="ev-modal-close" onclick="evCloseModal()" aria-label="Close">&times;</button>
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

<?php sjioc_footer(); get_footer(); ?>
