<?php

function tomatito_create_alarmas_table() {
    global $wpdb;

    $table           = $wpdb->prefix . 'tomatito_alarmas';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id           BIGINT(20)   NOT NULL AUTO_INCREMENT,
        user_id      BIGINT(20)   NOT NULL,
        name         VARCHAR(100) NOT NULL,
        time         TIME         NOT NULL,
        is_active    TINYINT(1)   NOT NULL DEFAULT 1,
        repeat_mode  VARCHAR(20)  NOT NULL DEFAULT 'none',
        mon          TINYINT(1)   NOT NULL DEFAULT 0,
        tue          TINYINT(1)   NOT NULL DEFAULT 0,
        wed          TINYINT(1)   NOT NULL DEFAULT 0,
        thu          TINYINT(1)   NOT NULL DEFAULT 0,
        fri          TINYINT(1)   NOT NULL DEFAULT 0,
        sat          TINYINT(1)   NOT NULL DEFAULT 0,
        sun          TINYINT(1)   NOT NULL DEFAULT 0,
        start_date   DATE                  DEFAULT NULL,
        created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY is_active (is_active)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

add_action( 'init', 'tomatito_create_alarmas_table' );
