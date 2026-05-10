<?php
/**
 * SJIOC Delaware Valley — WordPress Theme Functions
 * St. John's Indian Orthodox Church of Delaware Valley
 * 4400 State Road, Drexel Hill, PA 19026 | (610) 822-0033
 */

defined('ABSPATH') || exit;

define('SJIOC_VER', '2.0.1');
define('SJIOC_DIR', get_template_directory());
define('SJIOC_URI', get_template_directory_uri());

require_once SJIOC_DIR . '/inc/setup.php';
require_once SJIOC_DIR . '/inc/post-types.php';
require_once SJIOC_DIR . '/inc/contact-form.php';
require_once SJIOC_DIR . '/inc/chat.php';
require_once SJIOC_DIR . '/inc/admin.php';
require_once SJIOC_DIR . '/inc/members.php';
require_once SJIOC_DIR . '/inc/celebrations.php';
require_once SJIOC_DIR . '/inc/import.php';
require_once SJIOC_DIR . '/inc/events-admin.php';
require_once SJIOC_DIR . '/inc/sharepoint.php';
