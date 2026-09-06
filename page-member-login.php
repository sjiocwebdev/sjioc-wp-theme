<?php
/**
 * Template Name: Member Login
 *
 * Passwordless sign-in (magic link + email OTP). Logic lives in
 * inc/member-auth.php; this template only renders the current step.
 */

defined('ABSPATH') || exit;

get_header();

$post_url   = esc_url(admin_url('admin-post.php'));
$sent       = sanitize_key($_GET['sent'] ?? '');
$err        = sanitize_key($_GET['err'] ?? '');
$bye        = !empty($_GET['bye']);
$saved_mail = sanitize_email(wp_unslash($_COOKIE['sjioc_member_email'] ?? ''));

$errors = [
    'email'       => 'Please enter a valid email address.',
    'captcha'     => 'Security check failed. Please refresh the page and try again.',
    'expired'     => 'That form expired. Please try again.',
    'link'        => 'That sign-in link is invalid or has already been used. Request a new one below.',
    'otp_bad'     => 'That code is not correct. Please check your email and try again.',
    'otp_expired' => 'That code has expired. Request a new one below.',
    'otp_locked'  => 'Too many incorrect attempts. Please request a new code.',
    'auth'        => 'Please sign in to view the member area.',
];
?>
<div class="page-hero"><div class="container">
  <h1>Member Sign In</h1>
  <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &rsaquo; Member Sign In</p>
</div></div>

<div class="bg-cream"><div class="sec container">
  <div class="mlogin">

    <?php if ($bye): ?>
      <p class="mlogin-note mlogin-ok">You've been signed out.</p>
    <?php elseif ($err && isset($errors[$err])): ?>
      <p class="mlogin-note mlogin-err"><?php echo esc_html($errors[$err]); ?></p>
    <?php endif; ?>

    <?php if ($sent === 'link'): ?>
      <?php /* ---- Magic link sent ---- */ ?>
      <span class="stag">Check Your Email</span>
      <h2 class="stitle">Sign-in link sent</h2>
      <div class="divider"></div>
      <p class="slead">If that address is on our parish rolls, we've emailed a sign-in link. It works once and expires in 15&nbsp;minutes.</p>
      <p class="mlogin-back"><a href="<?php echo esc_url(get_permalink()); ?>">&larr; Use a different email</a></p>

    <?php elseif ($sent === 'otp' || $err === 'otp_bad'): ?>
      <?php /* ---- OTP entry ---- */ ?>
      <span class="stag">Check Your Email</span>
      <h2 class="stitle">Enter your code</h2>
      <div class="divider"></div>
      <p class="slead">We've emailed a 6-digit code. Enter it below to continue.</p>

      <form class="mlogin-form" method="post" action="<?php echo $post_url; ?>" novalidate>
        <input type="hidden" name="action" value="sjioc_member_otp">
        <?php wp_nonce_field('sjioc_member_otp', 'sjnonce'); ?>
        <label for="ml-code">6-digit code</label>
        <input id="ml-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
               pattern="[0-9]*" maxlength="6" required autofocus
               aria-describedby="ml-code-hint">
        <span id="ml-code-hint" class="mlogin-hint">It expires 15 minutes after it was sent.</span>
        <button type="submit" class="btn btn-cr">Sign in</button>
      </form>
      <p class="mlogin-back"><a href="<?php echo esc_url(get_permalink()); ?>">&larr; Start over</a></p>

    <?php else: ?>
      <?php /* ---- Email entry (default) ---- */ ?>
      <span class="stag">Parishioners</span>
      <h2 class="stitle">Sign in to the member area</h2>
      <div class="divider"></div>
      <p class="slead">Enter the email address the church has on file for you. We'll send a one-time sign-in link or code &mdash; no password needed.</p>

      <form class="mlogin-form sjioc-member-form" method="post" action="<?php echo $post_url; ?>" novalidate>
        <input type="hidden" name="action" value="sjioc_member_send">
        <input type="hidden" name="recaptcha_token" value="">
        <input type="hidden" name="sjioc_t" value="<?php echo esc_attr(sjioc_member_form_ts()); ?>">
        <?php wp_nonce_field('sjioc_member_send', 'sjnonce'); ?>

        <label for="ml-email">Email address</label>
        <input id="ml-email" name="email" type="email" inputmode="email" autocomplete="email"
               required autofocus value="<?php echo esc_attr($saved_mail); ?>">

        <label class="mlogin-check">
          <input type="checkbox" name="remember" value="1" <?php checked($saved_mail !== ''); ?>>
          Remember my email on this device
        </label>

        <?php /* Honeypot — bot-attractive name, hidden inline so a CSS-load failure can't expose it. */ ?>
        <div class="mlogin-hp" aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden">
          <label>Website
            <input type="text" name="website" tabindex="-1" autocomplete="off">
          </label>
        </div>

        <div class="mlogin-actions">
          <button type="submit" name="method" value="link" class="btn btn-cr">Email me a sign-in link</button>
          <button type="submit" name="method" value="otp" class="btn btn-ol">Email me a 6-digit code</button>
        </div>
      </form>

      <p class="mlogin-hint">Don't have an email on file, or not sure which one? Contact the church office and we'll add it.</p>
    <?php endif; ?>

    <?php
    // DEV ONLY — surface the link/code locally (admins only, never in production).
    if (sjioc_member_is_dev() && current_user_can('manage_options')):
        $dev = get_transient('sjioc_member_dev_last');
        if (is_array($dev)):
    ?>
      <div class="mlogin-dev">
        <strong>DEV delivery</strong> (<?php echo esc_html($dev['email']); ?>)
        <code><?php echo esc_html($dev['line']); ?></code>
      </div>
    <?php endif; endif; ?>

  </div>
</div></div>

<?php sjioc_footer(); get_footer();
