<?php


/**
 * TEMPORIZADORES — Endpoints de controlo: PAUSE / RESUME / STOP
 *
 * Operam sobre a tabela tomatito_timers (linhas com type = 'temporizador').
 * Como os temporizadores correm em paralelo, cada ação age sobre UM timer
 * específico, identificado pelo id da linha em tomatito_timers (timer_id).
 *
 * Lógica de tempo:
 *  - Enquanto 'running', o tempo restante calcula-se por started_at + time_left.
 *  - Ao PAUSAR, congelamos o tempo restante em time_left e pomos state='paused'.
 *  - Ao RETOMAR, repomos started_at = agora e state='running' (conta a partir do time_left).
 *  - Ao PARAR, pomos state='stopped' (sai da lista de ativos).
 */
add_action('rest_api_init', function () {

    $timers_t = $GLOBALS['wpdb']->prefix . 'tomatito_timers';

    // Função auxiliar: calcula o tempo restante de um timer 'running'
    // a partir de started_at + time_left guardado.
    $calc_remaining = function ( $row ) {
        // $row->time_left é o tempo que faltava quando (re)começou a correr.
        // started_at é o instante em que (re)começou.
        try {
            $tz      = new DateTimeZone( wp_timezone_string() );
            $started = ( new DateTime( $row->started_at, $tz ) )->getTimestamp();
            $now     = ( new DateTime( 'now', $tz ) )->getTimestamp();
            $elapsed = $now - $started;
            return max( 0, (int) $row->time_left - $elapsed );
        } catch ( Exception $e ) {
            return (int) $row->time_left;
        }
    };

    // ─── PAUSAR ───────────────────────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/temporizadores/(?P<id>\d+)/pause', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ( $timers_t, $calc_remaining ) {

            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $timer_id = (int) $request->get_param('id');

            $row = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $timers_t
                 WHERE id = %d AND user_id = %d AND type = 'temporizador'",
                $timer_id, $user_id
            ));

            if ( ! $row ) {
                return array( 'success' => false, 'message' => 'Temporizador no encontrado' );
            }
            if ( $row->state !== 'running' ) {
                return array( 'success' => false, 'message' => 'El temporizador no está en marcha' );
            }

            // Congela o tempo restante
            $remaining = $calc_remaining( $row );

            $wpdb->update( $timers_t, array(
                'state'      => 'paused',
                'time_left'  => $remaining,
                'updated_at' => current_time('mysql'),
            ), array( 'id' => $timer_id ) );

            return array( 'success' => true, 'message' => 'Temporizador pausado', 'remaining' => $remaining );
        },
    ));

    // ─── RETOMAR ──────────────────────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/temporizadores/(?P<id>\d+)/resume', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ( $timers_t ) {

            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $timer_id = (int) $request->get_param('id');

            $row = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $timers_t
                 WHERE id = %d AND user_id = %d AND type = 'temporizador'",
                $timer_id, $user_id
            ));

            if ( ! $row ) {
                return array( 'success' => false, 'message' => 'Temporizador no encontrado' );
            }
            if ( $row->state !== 'paused' ) {
                return array( 'success' => false, 'message' => 'El temporizador no está pausado' );
            }

            // Recomeça a contar a partir do time_left guardado
            $wpdb->update( $timers_t, array(
                'state'      => 'running',
                'started_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ), array( 'id' => $timer_id ) );

            return array( 'success' => true, 'message' => 'Temporizador reanudado', 'remaining' => (int) $row->time_left );
        },
    ));

    // ─── REINICIAR ────────────────────────────────────────────────────────────
    // Recomeça o MESMO temporizador do tempo cheio (não o apaga da lista).
    // Repõe time_left = duration, started_at = agora, state = running.
    register_rest_route('tomatito/v1', '/temporizadores/(?P<id>\d+)/restart', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ( $timers_t ) {

            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $timer_id = (int) $request->get_param('id');

            $row = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $timers_t
                 WHERE id = %d AND user_id = %d AND type = 'temporizador'",
                $timer_id, $user_id
            ));

            if ( ! $row ) {
                return array( 'success' => false, 'message' => 'Temporizador no encontrado' );
            }

            $duration = (int) $row->duration;

            $wpdb->update( $timers_t, array(
                'state'      => 'running',
                'time_left'  => $duration,
                'started_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ), array( 'id' => $timer_id ) );

            return array( 'success' => true, 'message' => 'Temporizador reiniciado', 'remaining' => $duration );
        },
    ));

    // ─── PARAR ────────────────────────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/temporizadores/(?P<id>\d+)/stop', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ( $timers_t ) {

            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $timer_id = (int) $request->get_param('id');

            $row = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $timers_t
                 WHERE id = %d AND user_id = %d AND type = 'temporizador'",
                $timer_id, $user_id
            ));

            if ( ! $row ) {
                return array( 'success' => false, 'message' => 'Temporizador no encontrado' );
            }

            $wpdb->update( $timers_t, array(
                'state'      => 'stopped',
                'time_left'  => 0,
                'updated_at' => current_time('mysql'),
            ), array( 'id' => $timer_id ) );

            return array( 'success' => true, 'message' => 'Temporizador detenido' );
        },
    ));

});
