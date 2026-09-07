<?php

function tomatito_create_pomodoros_table() {
    global $wpdb;
    $table           = $wpdb->prefix . 'tomatito_pomodoros';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
        id              BIGINT(20)   NOT NULL AUTO_INCREMENT,
        user_id         BIGINT(20)   NOT NULL,
        name            VARCHAR(100) NOT NULL,
        work            INT          NOT NULL DEFAULT 25,
        short_break     INT          NOT NULL DEFAULT 5,
        long_break      INT          NOT NULL DEFAULT 15,
        cycles          INT          NOT NULL DEFAULT 4,
        repetitions     INT                   DEFAULT NULL,
        auto_start      TINYINT(1)   NOT NULL DEFAULT 1,
        pause_on_end    TINYINT(1)   NOT NULL DEFAULT 0,
        sound           VARCHAR(50)  NOT NULL DEFAULT 'default',
        vibration       TINYINT(1)   NOT NULL DEFAULT 1,
        sync            TINYINT(1)   NOT NULL DEFAULT 1,
        proyecto_id     BIGINT(20)            DEFAULT NULL,
        tipo_sesion_id  BIGINT(20)            DEFAULT NULL,
        dispositivo_id  BIGINT(20)            DEFAULT NULL,
        last_used_at    DATETIME              DEFAULT NULL,
        created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY proyecto_id (proyecto_id)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
add_action( 'init', 'tomatito_create_pomodoros_table' );
