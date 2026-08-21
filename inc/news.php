<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   CPT: SJIOC News
   Powers the "Updates → News" page. Uses the native content editor for
   the article body and native publish date for sorting — no custom
   meta box needed, admin uses the same editor as any WP post.
───────────────────────────────────── */
function sjioc_register_news() {
    register_post_type('sjioc_news', [
        'labels'        => [
            'name'          => __('News',           'sjioc'),
            'singular_name' => __('News Article',   'sjioc'),
            'add_new_item'  => __('Add News Article', 'sjioc'),
            'edit_item'     => __('Edit News Article', 'sjioc'),
            'menu_name'     => __('News',            'sjioc'),
        ],
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => 'sjioc',
        'menu_icon'     => 'dashicons-megaphone',
        'supports'      => ['title', 'editor', 'thumbnail', 'excerpt'],
        'show_in_rest'  => false,
    ]);
}
add_action('init', 'sjioc_register_news');
