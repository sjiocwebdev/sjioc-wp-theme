<?php
/**
 * Template Name: Committees
 * Renders the sjioc_office CPT (group: managing-committee, auditors, etc).
 * Content is edited via WP Admin → Office Bearers, not this file.
 * Tab/accordion behavior lives in assets/js/main.js; styles in style.css.
 */
get_header();
?>
<div class="page-hero">
  <div class="container">
    <h1>Committees &amp; Organizations</h1>
    <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › About › Committees</p>
  </div>
</div>

<div class="bg-ww"><div class="sec container">
  <div class="tc" style="margin-bottom:36px">
    <span class="stag">Parish Governance</span>
    <h2 class="stitle">Committees &amp; Organizations</h2>
    <div class="divider"></div>
    <p class="slead">The dedicated members who serve our parish community through administration, ministry, and outreach.</p>
  </div>

  <?php if (! sjioc_render_office_committees()): ?>
  <div class="cmte-tabs" role="tablist">
    <button class="cmte-tab is-active" role="tab" data-panel="cmte-admin">Parish Administration</button>
    <button class="cmte-tab" role="tab" data-panel="cmte-spiritual">Spiritual Organizations</button>
  </div>

  <!-- ── Panel 1: Parish Administration ── -->
  <div class="cmte-panel" id="cmte-admin">

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
  <?php endif; ?>

</div></div>

<?php sjioc_footer(); get_footer(); ?>
