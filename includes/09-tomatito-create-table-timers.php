<?php

function tomatito_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tomatito_timers';
	
    $charset_collate = $wpdb->get_charset_collate();
	
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) NOT NULL,
        type VARCHAR(50) NOT NULL,
        state VARCHAR(20) NOT NULL,
        duration INT NOT NULL,
        time_left INT NOT NULL,
        pomodoro_id BIGINT(20) DEFAULT NULL,
        started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
		
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
add_action('init', 'tomatito_create_table');
