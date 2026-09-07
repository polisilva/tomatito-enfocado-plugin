<?php

function tomatito_ensure_alarma_end_date_column() {
    global $wpdb;
    $table = $wpdb->prefix . 'tomatito_alarmas';

    $column_exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'end_date'",
        DB_NAME,
        $table
    ) );

    if ( ! $column_exists ) {
        $wpdb->query( "ALTER TABLE $table ADD COLUMN end_date DATE NULL DEFAULT NULL" );
    }
}
add_action( 'init', 'tomatito_ensure_alarma_end_date_column' );

function tomatito_ensure_alarma_start_date_column() {
    global $wpdb;
    $table = $wpdb->prefix . 'tomatito_alarmas';

    $column_exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'start_date'",
        DB_NAME,
        $table
    ) );

    if ( ! $column_exists ) {
        $wpdb->query( "ALTER TABLE $table ADD COLUMN start_date DATE NULL DEFAULT NULL AFTER sun" );
    }
}
add_action( 'init', 'tomatito_ensure_alarma_start_date_column' );

// ─────────────────────────────────────────────────────────────────────────────
// MIGRAÇÃO: garante que a coluna 'sound' existe na tabela de alarmas.
// ─────────────────────────────────────────────────────────────────────────────
function tomatito_ensure_alarma_sound_column() {
    global $wpdb;
    $table = $wpdb->prefix . 'tomatito_alarmas';

    $column_exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'sound'",
        DB_NAME,
        $table
    ) );

    if ( ! $column_exists ) {
        $wpdb->query( "ALTER TABLE $table ADD COLUMN sound VARCHAR(20) NULL DEFAULT 'default'" );
    }
}
add_action( 'init', 'tomatito_ensure_alarma_sound_column' );


add_action('rest_api_init', function () {

    $table = $GLOBALS['wpdb']->prefix . 'tomatito_alarmas';

    register_rest_route('tomatito/v1', '/alarmas', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () use ($table) {

            global $wpdb;
            $user_id = get_current_user_id();
			if ( ! $user_id ) {
				return array( 'success' => false, 'message' => 'No autorizado' );
			}

            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table
                     WHERE user_id = %d
                     ORDER BY time ASC",
                    $user_id
                ),
                ARRAY_A
            );

            foreach ( $rows as &$row ) {
                $row['time_label']   = tomatito_alarma_format_time( $row['time'] );
                $row['repeat_label'] = tomatito_alarma_format_repeat( $row );
            }

            return array( 'success' => true, 'data' => $rows );
        },
    ));

    register_rest_route('tomatito/v1', '/alarmas', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ($table) {

            global $wpdb;
            $user_id = get_current_user_id();
			if ( ! $user_id ) {
				return array( 'success' => false, 'message' => 'No autorizado' );
			}
            $body    = $request->get_json_params();

            if ( empty( $body['name'] ) ) {
                return array( 'success' => false, 'message' => 'El nombre es obligatorio' );
            }

            if ( empty( $body['time'] ) ) {
                return array( 'success' => false, 'message' => 'La hora es obligatoria' );
            }

            $start_date = null;
            if ( ! empty( $body['start_date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $body['start_date'] ) ) {
                $start_date = sanitize_text_field( $body['start_date'] );
            }

            $end_date = null;
            if ( ! empty( $body['end_date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $body['end_date'] ) ) {
                $end_date = sanitize_text_field( $body['end_date'] );
            }

            if ( $start_date && $end_date && $start_date > $end_date ) {
                return array( 'success' => false, 'message' => 'La fecha de inicio no puede ser posterior a la fecha límite' );
            }

            $sonidos_permitidos = array( 'default', 'clasico', 'campana', 'digital', 'suave', 'silent', 'vibracion' );
            $sound = ( isset( $body['sound'] ) && in_array( $body['sound'], $sonidos_permitidos, true ) )
                ? $body['sound']
                : 'default';

            $wpdb->insert( $table, array(
                'user_id'     => $user_id,
                'name'        => sanitize_text_field( $body['name'] ),
                'time'        => sanitize_text_field( $body['time'] ),
                'is_active'   => isset( $body['is_active'] ) ? (int) $body['is_active'] : 1,
                'repeat_mode' => sanitize_text_field( $body['repeat_mode'] ?? 'none' ),
                'start_date'  => $start_date,
                'end_date'    => $end_date,
                'sound'       => $sound,
                'mon'         => (int) ( $body['mon'] ?? 0 ),
                'tue'         => (int) ( $body['tue'] ?? 0 ),
                'wed'         => (int) ( $body['wed'] ?? 0 ),
                'thu'         => (int) ( $body['thu'] ?? 0 ),
                'fri'         => (int) ( $body['fri'] ?? 0 ),
                'sat'         => (int) ( $body['sat'] ?? 0 ),
                'sun'         => (int) ( $body['sun'] ?? 0 ),
            ));

            $id  = $wpdb->insert_id;
            $row = $wpdb->get_row(
                $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ),
                ARRAY_A
            );

            return array( 'success' => true, 'data' => $row, 'message' => 'Alarma creada' );
        },
    ));

    register_rest_route('tomatito/v1', '/alarmas/(?P<id>\d+)', array(
        'methods'             => 'PUT',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ($table) {

            global $wpdb;
            $user_id = get_current_user_id();
			if ( ! $user_id ) {
				return array( 'success' => false, 'message' => 'No autorizado' );
			}
            $id      = (int) $request->get_param('id');
            $body    = $request->get_json_params();

            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $table WHERE id = %d AND user_id = %d",
                    $id, $user_id
                )
            );

            if ( ! $exists ) {
                return array( 'success' => false, 'message' => 'Alarma no encontrada' );
            }

            $start_date = null;
            if ( ! empty( $body['start_date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $body['start_date'] ) ) {
                $start_date = sanitize_text_field( $body['start_date'] );
            }

            $end_date = null;
            if ( ! empty( $body['end_date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $body['end_date'] ) ) {
                $end_date = sanitize_text_field( $body['end_date'] );
            }

            if ( $start_date && $end_date && $start_date > $end_date ) {
                return array( 'success' => false, 'message' => 'La fecha de inicio no puede ser posterior a la fecha límite' );
            }

            $sonidos_permitidos = array( 'default', 'clasico', 'campana', 'digital', 'suave', 'silent', 'vibracion' );
            $sound = ( isset( $body['sound'] ) && in_array( $body['sound'], $sonidos_permitidos, true ) )
                ? $body['sound']
                : 'default';

            $wpdb->update( $table, array(
                'name'        => sanitize_text_field( $body['name'] ?? '' ),
                'time'        => sanitize_text_field( $body['time'] ?? '07:00:00' ),
                'is_active'   => (int) ( $body['is_active'] ?? 1 ),
                'repeat_mode' => sanitize_text_field( $body['repeat_mode'] ?? 'none' ),
                'start_date'  => $start_date,
                'end_date'    => $end_date,
                'sound'       => $sound,
                'mon'         => (int) ( $body['mon'] ?? 0 ),
                'tue'         => (int) ( $body['tue'] ?? 0 ),
                'wed'         => (int) ( $body['wed'] ?? 0 ),
                'thu'         => (int) ( $body['thu'] ?? 0 ),
                'fri'         => (int) ( $body['fri'] ?? 0 ),
                'sat'         => (int) ( $body['sat'] ?? 0 ),
                'sun'         => (int) ( $body['sun'] ?? 0 ),
            ), array( 'id' => $id ) );

            $row = $wpdb->get_row(
                $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ),
                ARRAY_A
            );

            return array( 'success' => true, 'data' => $row, 'message' => 'Alarma actualizada' );
        },
    ));

    register_rest_route('tomatito/v1', '/alarmas/(?P<id>\d+)', array(
        'methods'             => 'DELETE',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ($table) {

            global $wpdb;
            $user_id = get_current_user_id();
			if ( ! $user_id ) {
				return array( 'success' => false, 'message' => 'No autorizado' );
			}
            $id      = (int) $request->get_param('id');

            $deleted = $wpdb->delete( $table, array( 'id' => $id, 'user_id' => $user_id ) );

            if ( ! $deleted ) {
                return array( 'success' => false, 'message' => 'Alarma no encontrada' );
            }

            return array( 'success' => true, 'message' => 'Alarma eliminada' );
        },
    ));

    register_rest_route('tomatito/v1', '/alarmas/(?P<id>\d+)/toggle', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ($table) {

            global $wpdb;
            $user_id = get_current_user_id();
			if ( ! $user_id ) {
				return array( 'success' => false, 'message' => 'No autorizado' );
			}
            $id      = (int) $request->get_param('id');

            $alarma = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE id = %d AND user_id = %d",
                    $id, $user_id
                )
            );

            if ( ! $alarma ) {
                return array( 'success' => false, 'message' => 'Alarma no encontrada' );
            }

            $new_state = $alarma->is_active ? 0 : 1;

            $wpdb->update( $table,
                array( 'is_active' => $new_state ),
                array( 'id' => $id )
            );

            return array(
                'success'   => true,
                'is_active' => $new_state,
                'message'   => $new_state ? 'Alarma activada' : 'Alarma desactivada',
            );
        },
    ));
	
    register_rest_route('tomatito/v1', '/alarmas/upcoming', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () use ($table) {

            global $wpdb;
            $user_id = get_current_user_id();
			if ( ! $user_id ) {
				return array( 'success' => false, 'message' => 'No autorizado' );
			}
            $now      = current_time( 'H:i:s' );
            $today    = current_time( 'Y-m-d' );

            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT id, name, time, repeat_mode, sound
                 FROM $table
                 WHERE user_id   = %d
                   AND is_active = 1
                   AND ( start_date IS NULL OR start_date <= %s )
                   AND ( end_date IS NULL OR end_date >= %s )
                 ORDER BY 
                     CASE WHEN time >= %s THEN 0 ELSE 1 END,
                     time ASC
                 LIMIT 3",
                $user_id,
                $today,
                $today,
                $now
            ), ARRAY_A );

            $output = array();

            foreach ( $rows as $row ) {

                $hhmm = substr( $row['time'], 0, 5 );

				$output[] = array(
					'id'    => (int) $row['id'],
					'name'  => $row['name'],
					'time'  => $hhmm,
					'sound' => $row['sound'],
				);
					}

            return array( 'success' => true, 'data' => $output );
        },
    ));

});

function tomatito_alarma_format_time( $time ) {
    $hhmm = substr( $time, 0, 5 );
    $hour = (int) substr( $time, 0, 2 );

    if ( $hour >= 5 && $hour < 12 ) {
        $context = 'mañana';
    } elseif ( $hour >= 12 && $hour < 14 ) {
        $context = 'mediodía';
    } elseif ( $hour >= 14 && $hour < 19 ) {
        $context = 'tarde';
    } elseif ( $hour >= 19 && $hour < 22 ) {
        $context = 'noche';
    } else {
        $context = 'madrugada';
    }

    return "A las $hhmm ($context)";
}

function tomatito_alarma_format_repeat( $row ) {
    if ( $row['repeat_mode'] === 'none' )     return 'Sin repetición';
    if ( $row['repeat_mode'] === 'daily' )    return 'Diariamente';
    if ( $row['repeat_mode'] === 'weekdays' ) return 'Lunes a Viernes';

    if ( $row['repeat_mode'] === 'custom' ) {
        $days       = array();
        $day_labels = array(
            'mon' => 'L', 'tue' => 'M', 'wed' => 'X',
            'thu' => 'J', 'fri' => 'V', 'sat' => 'S', 'sun' => 'D',
        );

        foreach ( $day_labels as $key => $label ) {
            if ( $row[$key] ) $days[] = $label;
        }

        return empty( $days ) ? 'Sin repetición' : implode( ', ', $days );
    }

    return 'Sin repetición';
}
