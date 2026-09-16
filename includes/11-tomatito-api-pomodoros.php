<?php

/* TOMATITO - API - POMODOROS (fases + ciclos + repetições) */

// ─────────────────────────────────────────────────────────────────────────────
// MIGRAÇÃO: garante as colunas 'phase', 'cycle_count' e 'repetition_count'
// na tabela de timers. Roda uma vez por carregamento, só altera se ainda
// não existirem — seguro repetir.
// ─────────────────────────────────────────────────────────────────────────────
function tomatito_ensure_pomodoro_phase_columns() {
    global $wpdb;
    $table = $wpdb->prefix . 'tomatito_timers';

    $columns_to_add = array(
        'phase'            => "VARCHAR(20) NULL DEFAULT NULL",
        'cycle_count'      => "INT NULL DEFAULT 0",
        'repetition_count' => "INT NULL DEFAULT 0",
    );

    foreach ( $columns_to_add as $column => $definition ) {
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME, $table, $column
        ) );
        if ( ! $exists ) {
            $wpdb->query( "ALTER TABLE $table ADD COLUMN $column $definition" );
        }
    }
}
add_action( 'init', 'tomatito_ensure_pomodoro_phase_columns' );


// ─────────────────────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────────────────────
function tomatito_phase_duration( $pomodoro, $phase ) {
    if ( $phase === 'work' )        return (int) $pomodoro->work * 60;
    if ( $phase === 'short_break' ) return (int) $pomodoro->short_break * 60;
    if ( $phase === 'long_break' )  return (int) $pomodoro->long_break * 60;
    return (int) $pomodoro->work * 60;
}

function tomatito_phase_label( $phase ) {
    if ( $phase === 'work' )        return 'Trabajo';
    if ( $phase === 'short_break' ) return 'Descanso corto';
    if ( $phase === 'long_break' )  return 'Descanso largo';
    return 'Trabajo';
}

// ⚠️ NOVO: nome do pomodoro pelo id — usado para que o histórico e as
// confirmações mostrem QUAL pomodoro específico está envolvido, em vez de
// um título genérico "Pomodoro".
function tomatito_get_pomodoro_name( $pomodoro_id ) {
    if ( empty( $pomodoro_id ) ) {
        return null;
    }
    global $wpdb;
    $table = $wpdb->prefix . 'tomatito_pomodoros';
    return $wpdb->get_var( $wpdb->prepare(
        "SELECT name FROM $table WHERE id = %d",
        (int) $pomodoro_id
    ) );
}


add_action('rest_api_init', function () {

    $table        = $GLOBALS['wpdb']->prefix . 'tomatito_pomodoros';
    $table_timers = $GLOBALS['wpdb']->prefix . 'tomatito_timers';

    // ─── LISTAR ───────────────────────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/pomodoros', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () use ($table, $table_timers) {
            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }

            $rows = $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d ORDER BY id DESC", $user_id ),
                ARRAY_A
            );

            foreach ( $rows as &$row ) {
                $row['duration_label'] = $row['work'] . ' / ' . $row['short_break'] . ' / ' . $row['long_break'] . ' min';

                // ⚠️ CORRIGIDO: cálculo do "Último uso" (estava faltando)
                $last_used = $wpdb->get_var( $wpdb->prepare(
                    "SELECT MAX(started_at) FROM $table_timers
                     WHERE user_id = %d AND type = 'pomodoro' AND pomodoro_id = %d",
                    $user_id, $row['id']
                ));

                if ( $last_used ) {
                    $row['last_used_label'] = 'Hace ' . human_time_diff( strtotime( $last_used ), current_time( 'timestamp' ) );
                } else {
                    $row['last_used_label'] = 'Nunca';
                }
            }

            return array( 'success' => true, 'data' => $rows );
        },
    ));

    // ─── CRIAR ────────────────────────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/pomodoros', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ($table) {
            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $body = $request->get_json_params();

            if ( empty( $body['name'] ) ) {
                return array( 'success' => false, 'message' => 'El nombre es obligatorio' );
            }

            $wpdb->insert( $table, array(
                'user_id'        => $user_id,
                'name'           => sanitize_text_field( $body['name'] ),
                'work'           => (int) ( $body['work'] ?? 25 ),
                'short_break'    => (int) ( $body['short_break'] ?? 5 ),
                'long_break'     => (int) ( $body['long_break'] ?? 15 ),
                'cycles'         => (int) ( $body['cycles'] ?? 4 ),
                'repetitions'    => isset( $body['repetitions'] ) && $body['repetitions'] !== null ? (int) $body['repetitions'] : null,
                'auto_start'     => (int) ( $body['auto_start'] ?? 1 ),
                'pause_on_end'   => (int) ( $body['pause_on_end'] ?? 0 ),
                'sound'          => sanitize_text_field( $body['sound'] ?? 'default' ),
                'vibration'      => (int) ( $body['vibration'] ?? 1 ),
                'sync'           => (int) ( $body['sync'] ?? 1 ),
                'proyecto_id'    => $body['proyecto_id']    ?? null,
                'tipo_sesion_id' => $body['tipo_sesion_id'] ?? null,
            ));

            $id  = $wpdb->insert_id;
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A );

            return array( 'success' => true, 'data' => $row, 'message' => 'Pomodoro creado' );
        },
    ));

    // ─── EDITAR ───────────────────────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/pomodoros/(?P<id>\d+)', array(
        'methods'             => 'PUT',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ($table) {
            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $id   = (int) $request->get_param('id');
            $body = $request->get_json_params();

            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $table WHERE id = %d AND user_id = %d", $id, $user_id
            ));
            if ( ! $exists ) {
                return array( 'success' => false, 'message' => 'Pomodoro no encontrado' );
            }

            $wpdb->update( $table, array(
                'name'           => sanitize_text_field( $body['name'] ?? '' ),
                'work'           => (int) ( $body['work'] ?? 25 ),
                'short_break'    => (int) ( $body['short_break'] ?? 5 ),
                'long_break'     => (int) ( $body['long_break'] ?? 15 ),
                'cycles'         => (int) ( $body['cycles'] ?? 4 ),
                'repetitions'    => isset( $body['repetitions'] ) && $body['repetitions'] !== null ? (int) $body['repetitions'] : null,
                'auto_start'     => (int) ( $body['auto_start'] ?? 1 ),
                'pause_on_end'   => (int) ( $body['pause_on_end'] ?? 0 ),
                'sound'          => sanitize_text_field( $body['sound'] ?? 'default' ),
                'vibration'      => (int) ( $body['vibration'] ?? 1 ),
                'sync'           => (int) ( $body['sync'] ?? 1 ),
                'proyecto_id'    => $body['proyecto_id']    ?? null,
                'tipo_sesion_id' => $body['tipo_sesion_id'] ?? null,
            ), array( 'id' => $id ) );

            $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A );

            return array( 'success' => true, 'data' => $row, 'message' => 'Pomodoro actualizado' );
        },
    ));

    // ─── ELIMINAR ─────────────────────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/pomodoros/(?P<id>\d+)', array(
        'methods'             => 'DELETE',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ($table) {
            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $id = (int) $request->get_param('id');

            $deleted = $wpdb->delete( $table, array( 'id' => $id, 'user_id' => $user_id ) );
            if ( ! $deleted ) {
                return array( 'success' => false, 'message' => 'Pomodoro no encontrado' );
            }

            return array( 'success' => true, 'message' => 'Pomodoro eliminado' );
        },
    ));

    // ─── INICIAR (fase 'work', ciclo 0, repetição 0) ───────────────────────────
    register_rest_route('tomatito/v1', '/pomodoros/(?P<id>\d+)/start', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ($table, $table_timers) {
            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $id = (int) $request->get_param('id');

            $pomodoro = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d AND user_id = %d", $id, $user_id
            ));
            if ( ! $pomodoro ) {
                return array( 'success' => false, 'message' => 'Pomodoro no encontrado' );
            }

            // ⚠️ EVITA DUPLICADOS: si ESTE pomodoro específico ya tiene un
            // timer en marcha (running o paused), no crear otro — devuelve
            // el existente tal cual. Varios pomodoros DISTINTOS sí pueden
            // coexistir (esa es la función de "principal"/⭐), pero repetir
            // el mismo no debe apilar registros nuevos.
            $existing = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $table_timers
                 WHERE user_id = %d AND type = 'pomodoro' AND pomodoro_id = %d
                   AND state IN ('running','paused')
                 ORDER BY id DESC LIMIT 1",
                $user_id, $id
            ));
            if ( $existing ) {
                return array(
                    'success' => true,
                    'message' => 'Ya estaba en marcha',
                    'data'    => array(
                        'timer_id' => (int) $existing->id,
                        'phase'    => $existing->phase ?: 'work',
                        'duration' => (int) $existing->duration,
                    ),
                );
            }

            $duration = tomatito_phase_duration( $pomodoro, 'work' );

            $wpdb->insert( $table_timers, array(
                'user_id'          => $user_id,
                'type'             => 'pomodoro',
                'pomodoro_id'      => $id,
                'state'            => 'running',
                'duration'         => $duration,
                'time_left'        => $duration,
                'phase'            => 'work',
                'cycle_count'      => 0,
                'repetition_count' => 0,
                'started_at'       => current_time( 'mysql' ),
                'updated_at'       => current_time( 'mysql' ),
            ));

            return array(
                'success' => true,
                'message' => 'START OK',
                'data'    => array(
                    'timer_id' => $wpdb->insert_id,
                    'phase'    => 'work',
                    'duration' => $duration,
                ),
            );
        },
    ));

    // ─── PAUSAR (por id específico do pomodoro) ────────────────────────────────
    register_rest_route('tomatito/v1', '/pomodoros/(?P<id>\d+)/pause', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ($table_timers) {
            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $pomodoro_id = (int) $request->get_param('id');

            $timer = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $table_timers
                 WHERE user_id = %d AND type = 'pomodoro' AND pomodoro_id = %d AND state = 'running'
                 ORDER BY id DESC LIMIT 1",
                $user_id, $pomodoro_id
            ));
            if ( ! $timer ) {
                return array( 'success' => false, 'message' => 'No hay timer activo' );
            }

            $time_left = tomatito_calc_time_left( $timer );

            $wpdb->update( $table_timers, array(
                'state'      => 'paused',
                'time_left'  => $time_left,
                'updated_at' => current_time( 'mysql' ),
            ), array( 'id' => $timer->id ) );

            return array( 'success' => true, 'time_left' => $time_left, 'message' => 'PAUSE OK' );
        },
    ));

    // ─── RETOMAR (por id específico do pomodoro) ───────────────────────────────
    register_rest_route('tomatito/v1', '/pomodoros/(?P<id>\d+)/resume', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ($table_timers) {
            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $pomodoro_id = (int) $request->get_param('id');

            $timer = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $table_timers
                 WHERE user_id = %d AND type = 'pomodoro' AND pomodoro_id = %d AND state = 'paused'
                 ORDER BY id DESC LIMIT 1",
                $user_id, $pomodoro_id
            ));
            if ( ! $timer ) {
                return array( 'success' => false, 'message' => 'No hay timer pausado' );
            }

            $wpdb->update( $table_timers, array(
                'state'      => 'running',
                'duration'   => (int) $timer->time_left,
                'started_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ), array( 'id' => $timer->id ) );

            return array( 'success' => true, 'time_left' => (int) $timer->time_left, 'message' => 'RESUME OK' );
        },
    ));

    // ─── DETENER (por id específico do pomodoro) ───────────────────────────────
    register_rest_route('tomatito/v1', '/pomodoros/(?P<id>\d+)/stop', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ($table_timers) {
            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $pomodoro_id = (int) $request->get_param('id');

            $timer = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $table_timers
                 WHERE user_id = %d AND type = 'pomodoro' AND pomodoro_id = %d AND state IN ('running','paused')
                 ORDER BY id DESC LIMIT 1",
                $user_id, $pomodoro_id
            ));
            if ( ! $timer ) {
                return array( 'success' => false, 'message' => 'No hay timer activo' );
            }

            $time_left = tomatito_calc_time_left( $timer );

            $wpdb->update( $table_timers, array(
                'state'      => 'stopped',
                'time_left'  => $time_left,
                'updated_at' => current_time( 'mysql' ),
            ), array( 'id' => $timer->id ) );

            // Registra "Cancelado" no histórico (mantém o mesmo comportamento de antes)
            // ⚠️ CORRIGIDO: o título agora leva o nome do pomodoro específico
            // (ex: "TESTES FASES"), não mais um "Pomodoro" genérico — assim
            // o histórico mostra qual pomodoro foi cancelado.
            $elapsed    = max( 1, (int) ( $timer->duration - $time_left ) );
            $pomo_name  = tomatito_get_pomodoro_name( $pomodoro_id );
            $post_title = $pomo_name ? $pomo_name : 'Pomodoro';
            $sesion_id  = wp_insert_post( array(
                'post_type'   => 'sesion',
                'post_title'  => $post_title,
                'post_status' => 'publish',
                'post_author' => $user_id,
            ));
            if ( $sesion_id && ! is_wp_error( $sesion_id ) ) {
                update_post_meta( $sesion_id, 'duration', $elapsed );
                update_post_meta( $sesion_id, 'state', 'cancelled' );
            }

            return array( 'success' => true, 'message' => 'STOP OK' );
        },
    ));

    // ─── AVANÇAR DE FASE / REPETIÇÃO ────────────────────────────────────────────
    // Chamado quando uma fase termina — seja automaticamente (se auto_start=1
    // no pomodoro) ou por clique do usuário no botão "Empezar Descanso" /
    // "Volver al Trabajo" / "Empezar próxima repetición" (se auto_start=0
    // ou pause_on_end=1). A API não decide QUEM chama — só aplica a transição.
    //
    // Regras:
    //   work         -> short_break (ou long_break se completou os ciclos)
    //   short_break  -> work (mesmo ciclo de repetição)
    //   long_break   -> nova repetição (volta a 'work', cycle=0) OU is_final=true
    //                   se já completou todas as repetições configuradas
    //                   (o front-end deve então chamar /complete)
    register_rest_route('tomatito/v1', '/pomodoros/(?P<id>\d+)/advance-phase', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ($table, $table_timers) {
            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $id = (int) $request->get_param('id');

            $pomodoro = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d AND user_id = %d", $id, $user_id
            ));
            if ( ! $pomodoro ) {
                return array( 'success' => false, 'message' => 'Pomodoro no encontrado' );
            }

            $timer = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $table_timers
                 WHERE user_id = %d AND type = 'pomodoro' AND pomodoro_id = %d
                 ORDER BY id DESC LIMIT 1",
                $user_id, $id
            ));
            if ( ! $timer ) {
                return array( 'success' => false, 'message' => 'No hay temporizador activo para este pomodoro' );
            }

            $current_phase = $timer->phase ?: 'work';
            $cycle         = (int) $timer->cycle_count;
            $repetition    = (int) $timer->repetition_count;
            $total_cycles  = max( 1, (int) $pomodoro->cycles );
            $total_reps    = ! empty( $pomodoro->repetitions ) ? (int) $pomodoro->repetitions : 1;

            // ── Caso especial: estava em 'long_break' → decide repetição ──
            if ( $current_phase === 'long_break' ) {

                $repetition++;

                if ( $repetition >= $total_reps ) {
                    // Completou todas as repetições: registra o contador final
                    // mas NÃO reinicia nada — o front-end deve chamar /complete.
                    $wpdb->update( $table_timers,
                        array( 'repetition_count' => $repetition, 'updated_at' => current_time( 'mysql' ) ),
                        array( 'id' => $timer->id )
                    );

                    return array(
                        'success' => true,
                        'message' => 'Pomodoro completo — listo para finalizar',
                        'data'    => array(
                            'is_final'          => true,
                            'repetition'        => $repetition,
                            'repetitions_total' => $total_reps,
                        ),
                    );
                }

                // Ainda há mais repetições: reinicia o ciclo, começando em 'work'
                $next_phase = 'work';
                $cycle      = 0;

                $duration = tomatito_phase_duration( $pomodoro, $next_phase );

                $wpdb->update( $table_timers, array(
                    'phase'            => $next_phase,
                    'cycle_count'      => $cycle,
                    'repetition_count' => $repetition,
                    'state'            => 'running',
                    'duration'         => $duration,
                    'time_left'        => $duration,
                    'started_at'       => current_time( 'mysql' ),
                    'updated_at'       => current_time( 'mysql' ),
                ), array( 'id' => $timer->id ) );

                return array(
                    'success' => true,
                    'message' => 'NUEVA REPETICIÓN',
                    'data'    => array(
                        'timer_id'          => (int) $timer->id,
                        'phase'             => $next_phase,
                        'phase_label'       => tomatito_phase_label( $next_phase ),
                        'cycle'             => $cycle,
                        'cycles_total'      => $total_cycles,
                        'repetition'        => $repetition,
                        'repetitions_total' => $total_reps,
                        'new_repetition'    => true,
                        'duration'          => $duration,
                        'is_final'          => false,
                    ),
                );
            }

            // ── work → short_break (ou long_break se completou os ciclos) ──
            if ( $current_phase === 'work' ) {
                $cycle++;
                $next_phase = ( $cycle >= $total_cycles ) ? 'long_break' : 'short_break';

            // ── short_break → work ──
            } elseif ( $current_phase === 'short_break' ) {
                $next_phase = 'work';

            } else {
                return array( 'success' => false, 'message' => 'Fase desconocida' );
            }

            $duration = tomatito_phase_duration( $pomodoro, $next_phase );

            $wpdb->update( $table_timers, array(
                'phase'       => $next_phase,
                'cycle_count' => $cycle,
                'state'       => 'running',
                'duration'    => $duration,
                'time_left'   => $duration,
                'started_at'  => current_time( 'mysql' ),
                'updated_at'  => current_time( 'mysql' ),
            ), array( 'id' => $timer->id ) );

            return array(
                'success' => true,
                'message' => 'PHASE ADVANCED',
                'data'    => array(
                    'timer_id'          => (int) $timer->id,
                    'phase'             => $next_phase,
                    'phase_label'       => tomatito_phase_label( $next_phase ),
                    'cycle'             => $cycle,
                    'cycles_total'      => $total_cycles,
                    'repetition'        => $repetition,
                    'repetitions_total' => $total_reps,
                    'new_repetition'    => false,
                    'duration'          => $duration,
                    'is_final'          => false,
                ),
            );
        },
    ));

    // ─── DASHBOARD: pomodoros ativos (com fase/ciclo/repetição) ────────────────
    register_rest_route('tomatito/v1', '/pomodoros/active', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () use ($table, $table_timers) {
            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }

            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT t.*, p.name AS pomo_name, p.work, p.short_break, p.long_break, p.cycles, p.repetitions, p.auto_start, p.pause_on_end, p.sound
                 FROM $table_timers t
                 INNER JOIN $table p ON p.id = t.pomodoro_id
                 WHERE t.user_id = %d AND t.type = 'pomodoro' AND t.state IN ('running','paused')
                 ORDER BY t.id DESC",
                $user_id
            ));

            $output = array();
            foreach ( $rows as $row ) {

                $duration = (int) $row->duration;
                if ( $row->state === 'running' ) {
                    $tz      = new DateTimeZone( wp_timezone_string() );
                    $started = new DateTime( $row->started_at, $tz );
                    $now     = new DateTime( 'now', $tz );
                    $elapsed = $now->getTimestamp() - $started->getTimestamp();
                    $remaining = max( 0, $duration - $elapsed );
                } else {
                    $remaining = (int) $row->time_left;
                }

                $total_reps = ! empty( $row->repetitions ) ? (int) $row->repetitions : 1;

                $output[] = array(
                    'timer_id'          => (int) $row->id,
                    'pomodoro_id'       => (int) $row->pomodoro_id,
                    'name'              => $row->pomo_name,
                    'remaining'         => $remaining,
                    'duration'          => $duration,
                    'state'             => $row->state,
                    'phase'             => $row->phase ?: 'work',
                    'phase_label'       => tomatito_phase_label( $row->phase ?: 'work' ),
                    'cycle'             => (int) $row->cycle_count,
                    'cycles_total'      => (int) $row->cycles,
                    'repetition'        => (int) $row->repetition_count,
                    'repetitions_total' => $total_reps,
                    'auto_start'        => (int) $row->auto_start,
                    'pause_on_end'      => (int) $row->pause_on_end,
                    'sound'             => $row->sound,
                );
            }

            return array( 'success' => true, 'data' => $output );
        },
    ));

});
