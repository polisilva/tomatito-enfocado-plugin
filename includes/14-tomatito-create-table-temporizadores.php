<?php

function tomatito_create_temporizadores_table() {
    global $wpdb;

    $table           = $wpdb->prefix . 'tomatito_temporizadores';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id            BIGINT(20)   NOT NULL AUTO_INCREMENT,
        user_id       BIGINT(20)   NOT NULL,
        name          VARCHAR(100) NOT NULL,
        duration      INT          NOT NULL DEFAULT 600,
        last_used_at  DATETIME              DEFAULT NULL,
        created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

add_action( 'init', 'tomatito_create_temporizadores_table' );
