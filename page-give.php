<?php
/**
 * Template Name: Support Us Page
 */
get_header();
?>

<!-- ════ PAGE HERO ════ -->
<div class="page-hero">
  <div class="container">
    <h1>Support Our Church</h1>
    <p class="breadcrumb">
      <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › Support Us
    </p>
  </div>
</div>

<!-- ════ INTRO ════ -->
<div class="bg-cream"><div class="sec container">
  <div class="tc" style="max-width:680px;margin:0 auto">
    <span class="stag">Your Generosity</span>
    <h2 class="stitle">Give to <?php echo esc_html(sjioc_abbr() ?: 'SJIOC'); ?></h2>
    <div class="divider"></div>
    <p class="slead">Your faithful giving sustains our worship, nurtures our community, and enables us to serve those in need. Every contribution — large or small — is a blessing to our parish family.</p>
  </div>
</div></div>

<!-- ════ ZELLE SECTION ════ -->
<div class="bg-ww"><div class="sec container">
  <div class="give-grid">

    <!-- Zelle Card -->
    <div class="give-card give-card-zelle">
      <div class="give-card-icon">
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" width="48" height="48" aria-hidden="true">
          <rect width="48" height="48" rx="12" fill="#6D1ED4"/>
          <path d="M14 32h13.5l-9-16H32M14 16h13.5l-9 16" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <h3 class="give-card-title">Give via Zelle</h3>
      <p class="give-card-sub">Fast, free, and secure — directly from your bank app.</p>
      <div class="give-zelle-detail">
        <span class="give-zelle-label">Send to</span>
        <span class="give-zelle-value"><?php echo esc_html(sjioc_email()); ?></span>
      </div>
      <div class="give-zelle-detail">
        <span class="give-zelle-label">Recipient Name</span>
        <span class="give-zelle-value"><?php echo esc_html(sjioc_name()); ?></span>
      </div>

      <?php
      $qr = get_theme_mod('sjioc_zelle_qr', '');
      $qr_url = $qr ? wp_get_attachment_image_url($qr, 'medium') : '';
      if ($qr_url): ?>
      <div class="give-qr-wrap">
        <img src="<?php echo esc_url($qr_url); ?>" alt="Zelle QR Code" class="give-qr">
        <p class="give-qr-caption">Scan with your bank app or Zelle app</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- How to Give -->
    <div class="give-steps">
      <span class="stag" style="text-align:left">How It Works</span>
      <h3 class="give-steps-title">Giving via Zelle</h3>
      <ol class="give-ol">
        <li>
          <span class="give-step-num">1</span>
          <div>
            <strong>Open your bank app</strong>
            <p>Most major US banks support Zelle natively — Chase, Bank of America, Wells Fargo, and more.</p>
          </div>
        </li>
        <li>
          <span class="give-step-num">2</span>
          <div>
            <strong>Select "Send Money" → Zelle</strong>
            <p>Or open the standalone Zelle app if your bank doesn't support it.</p>
          </div>
        </li>
        <li>
          <span class="give-step-num">3</span>
          <div>
            <strong>Enter the church email</strong>
            <p><strong style="color:var(--cr)"><?php echo esc_html(sjioc_email()); ?></strong> — confirm the recipient name matches <em><?php echo esc_html(sjioc_name()); ?></em>.</p>
          </div>
        </li>
        <li>
          <span class="give-step-num">4</span>
          <div>
            <strong>Enter your amount &amp; a note</strong>
            <p>Add a memo such as "General Offering", "Building Fund", or "Sunday School" so we can allocate it correctly.</p>
          </div>
        </li>
        <li>
          <span class="give-step-num">5</span>
          <div>
            <strong>Send — it arrives instantly</strong>
            <p>No fees. No waiting. Your generosity reaches the church immediately.</p>
          </div>
        </li>
      </ol>
    </div>

  </div>
</div></div>

<!-- ════ OTHER WAYS TO GIVE ════ -->
<div class="bg-cream"><div class="sec-sm container">
  <div class="tc" style="margin-bottom:36px">
    <span class="stag">Other Ways to Give</span>
    <h2 class="stitle">Additional Options</h2>
    <div class="divider"></div>
  </div>
  <div class="give-other-grid">
    <div class="give-other-card">
      <span class="give-other-icon">✉</span>
      <h4>By Mail / In Person</h4>
      <p>Cheques payable to <strong><?php echo esc_html(sjioc_name()); ?></strong> can be handed to the Trustee after Sunday service or mailed to our address.</p>
      <p style="margin-top:8px;font-size:.82rem;color:var(--cr)"><?php echo esc_html(sjioc_address()); ?></p>
    </div>
    <div class="give-other-card">
      <span class="give-other-icon">📞</span>
      <h4>Questions?</h4>
      <p>Contact our Trustee or Secretary for any giving-related questions. We're happy to help.</p>
      <p style="margin-top:12px">
        <a href="tel:<?php echo preg_replace('/\D/', '', sjioc_phone()); ?>" class="btn btn-cr" style="font-size:.78rem;padding:10px 20px"><?php echo esc_html(sjioc_phone()); ?></a>
      </p>
    </div>
    <div class="give-other-card">
      <span class="give-other-icon">🙏</span>
      <h4>Why We Give</h4>
      <p>"Each of you should give what you have decided in your heart to give, not reluctantly or under compulsion, for God loves a cheerful giver."</p>
      <p style="margin-top:8px;font-size:.8rem;color:var(--go);font-style:italic">— 2 Corinthians 9:7</p>
    </div>
  </div>
</div></div>

<style>
/* ── Give Page Layout ── */
.give-grid {
  display:grid; grid-template-columns:1fr 1fr; gap:56px; align-items:start;
}

/* Zelle Card */
.give-card {
  background:var(--ww); border:1px solid #e8e0d5;
  border-top:4px solid var(--go); border-radius:4px;
  padding:36px 32px; text-align:center;
}
.give-card-icon { margin-bottom:16px; }
.give-card-title {
  font-family:'Playfair Display',serif; color:var(--cr);
  font-size:1.5rem; margin:0 0 8px;
}
.give-card-sub { color:var(--tm); font-size:.88rem; margin:0 0 28px; }
.give-zelle-detail {
  display:flex; flex-direction:column; gap:4px;
  background:var(--cream); border-left:3px solid var(--go);
  padding:12px 16px; margin-bottom:12px; text-align:left;
}
.give-zelle-label {
  font-size:.6rem; font-weight:700; letter-spacing:.14em;
  text-transform:uppercase; color:var(--go);
}
.give-zelle-value {
  font-size:.95rem; font-weight:600; color:var(--cr-dk);
  word-break:break-all;
}
.give-qr-wrap { margin-top:24px; }
.give-qr { width:160px; height:160px; object-fit:contain; margin:0 auto; display:block; }
.give-qr-caption { font-size:.75rem; color:rgba(0,0,0,.45); margin-top:8px; }

/* Steps */
.give-steps-title {
  font-family:'Playfair Display',serif; color:var(--cr);
  font-size:1.4rem; margin:8px 0 28px;
}
.give-ol { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:20px; }
.give-ol li { display:flex; gap:16px; align-items:flex-start; }
.give-step-num {
  flex-shrink:0; width:32px; height:32px; border-radius:50%;
  background:var(--cr); color:#fff; font-weight:700; font-size:.85rem;
  display:flex; align-items:center; justify-content:center;
}
.give-ol li strong { font-size:.92rem; color:var(--cr-dk); display:block; margin-bottom:4px; }
.give-ol li p { font-size:.84rem; color:var(--tm); margin:0; line-height:1.65; }

/* Other ways */
.give-other-grid {
  display:grid; grid-template-columns:repeat(3,1fr); gap:24px;
}
.give-other-card {
  background:var(--ww); border:1px solid #e8e0d5;
  border-top:3px solid var(--cr); padding:28px 24px;
}
.give-other-icon { font-size:1.8rem; display:block; margin-bottom:12px; }
.give-other-card h4 {
  font-family:'Playfair Display',serif; color:var(--cr);
  font-size:1.05rem; margin:0 0 10px;
}
.give-other-card p { font-size:.86rem; color:var(--tm); line-height:1.7; margin:0; }

@media (max-width:860px) {
  .give-grid { grid-template-columns:1fr; gap:36px; }
  .give-other-grid { grid-template-columns:1fr 1fr; }
}
@media (max-width:560px) {
  .give-other-grid { grid-template-columns:1fr; }
  .give-card { padding:24px 18px; }
}
</style>

<?php sjioc_footer(); get_footer(); ?>
