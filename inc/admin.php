<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   ADMIN MENU — SJIOC
───────────────────────────────────── */
function sjioc_admin_menu() {
    add_menu_page(
        'SJIOC Settings', 'SJIOC', 'manage_options',
        'sjioc-chat', 'sjioc_chat_settings_page',
        'dashicons-church', 58
    );
    add_submenu_page(
        'sjioc-chat', 'Chat & Knowledge Base', 'Chat Settings',
        'manage_options', 'sjioc-chat', 'sjioc_chat_settings_page'
    );
}
add_action('admin_menu', 'sjioc_admin_menu');

function sjioc_chat_settings_page() {
    if (!current_user_can('manage_options')) return;
    if (isset($_POST['sjioc_kb_save']) && check_admin_referer('sjioc_kb_nonce')) {
        update_option('sjioc_kb_text', sanitize_textarea_field(wp_unslash($_POST['sjioc_kb_text'] ?? '')));
        echo '<div class="notice notice-success is-dismissible"><p>Knowledge base saved.</p></div>';
    }
    $kb = get_option('sjioc_kb_text', '');
    ?>
    <div class="wrap">
        <h1>SJIOC Chat — Knowledge Base</h1>
        <p>Open your church PDF, copy all the text, and paste it below. The AI assistant uses this to answer parish questions.</p>
        <form method="post">
            <?php wp_nonce_field('sjioc_kb_nonce'); ?>
            <textarea name="sjioc_kb_text" rows="22"
                style="width:100%;font-family:monospace;font-size:13px"><?php echo esc_textarea($kb); ?></textarea>
            <br><br>
            <input type="submit" name="sjioc_kb_save" class="button button-primary" value="Save Knowledge Base">
        </form>
    </div>
    <?php
}
