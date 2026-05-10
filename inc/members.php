<?php
defined('ABSPATH') || exit;

/* ─────────────────────────────────────
   CREATE TABLE on theme activation
   Column names match Excel export headers:
     phone_number, zip_code
   marital_status codes: M=Married S=Single W=Widowed D=Divorced
───────────────────────────────────── */
function sjioc_create_members_table() {
    global $wpdb;
    $table   = $wpdb->prefix . 'sjioc_members';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id             INT UNSIGNED          NOT NULL AUTO_INCREMENT,
        cardex_no      VARCHAR(20)           NOT NULL,
        member_seq     TINYINT UNSIGNED      NOT NULL DEFAULT 1,
        first_name     VARCHAR(50)           NOT NULL,
        middle_name    VARCHAR(50)                    DEFAULT NULL,
        last_name      VARCHAR(50)           NOT NULL,
        date_of_birth  DATE                           DEFAULT NULL,
        marital_status ENUM('M','S','W','D')          DEFAULT NULL,
        wedding_date   DATE                           DEFAULT NULL,
        gender         ENUM('M','F','O')              DEFAULT NULL,
        phone_number   VARCHAR(30)                    DEFAULT NULL,
        email          VARCHAR(100)                   DEFAULT NULL,
        address        VARCHAR(150)                   DEFAULT NULL,
        city           VARCHAR(80)                    DEFAULT NULL,
        state          VARCHAR(50)                    DEFAULT NULL,
        zip_code       VARCHAR(20)                    DEFAULT NULL,
        country        VARCHAR(60)                    DEFAULT 'USA',
        is_active      TINYINT(1)            NOT NULL DEFAULT 1,
        created_at     TIMESTAMP             NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at     TIMESTAMP             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY uq_cardex_seq (cardex_no, member_seq),
        KEY idx_dob    (date_of_birth),
        KEY idx_wed    (wedding_date),
        KEY idx_active (is_active)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
add_action('after_switch_theme', 'sjioc_create_members_table');
