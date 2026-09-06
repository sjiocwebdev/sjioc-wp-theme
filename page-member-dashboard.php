<?php
/**
 * Template Name: Member Dashboard
 *
 * Access is enforced in inc/member-auth.php (sjioc_member_gate on
 * template_redirect) — a signed-out visitor is redirected before this
 * template renders. The guard below is a defence-in-depth backstop.
 */

defined('ABSPATH') || exit;

$member = sjioc_current_member();
if (!$member) {
    wp_safe_redirect(sjioc_member_page_url('login'));
    exit;
}

$greeting = sjioc_member_greeting($member);

get_header();
?>
<div class="page-hero"><div class="container">
  <h1>Member Area</h1>
  <p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &rsaquo; Member Area</p>
</div></div>

<div class="bg-cream"><div class="sec container">
  <div class="mdash">
    <span class="stag">Welcome</span>
    <h2 class="stitle">Welcome, <?php echo esc_html($greeting); ?></h2>
    <div class="divider"></div>
    <p class="slead">You're signed in. More will appear here soon &mdash; the parish directory, member documents, and giving records.</p>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mdash-logout">
      <input type="hidden" name="action" value="sjioc_member_logout">
      <?php wp_nonce_field('sjioc_member_logout', 'sjnonce'); ?>
      <button type="submit" class="btn btn-ol">Sign out</button>
    </form>
  </div>
</div></div>

<?php sjioc_footer(); get_footer();
