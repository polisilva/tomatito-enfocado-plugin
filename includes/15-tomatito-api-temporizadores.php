<?php

// ─────────────────────────────────────────────────────────────────────────────
// MIGRAÇÃO: garante que a coluna 'sound' existe na tabela de temporizadores.
// Roda uma vez por carregamento, só faz o ALTER TABLE se a coluna ainda não
// existir (seguro repetir). Mesmo padrão já usado para 'end_date' em Alarmas.
// ─────────────────────────────────────────────────────────────────────────────
function tomatito_ensure_temporizador_sound_column() {
    global $wpdb;
    $table = $wpdb->prefix . 'tomatito_temporizadores';

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
add_action( 'init', 'tomatito_ensure_temporizador_sound_column' );


add_action('rest_api_init', function () {

    $table = $GLOBALS['wpdb']->prefix . 'tomatito_temporizadores';

    // ─── LISTAR ───────────────────────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/temporizadores', array(
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
                     ORDER BY last_used_at DESC, created_at DESC",
                    $user_id
                ),
                ARRAY_A
            );

            // Adiciona labels formatados para o frontend
            foreach ( $rows as &$row ) {
                $row['last_used_label'] = tomatito_temp_format_last_used( $row['last_used_at'] );
                $row['duration_label'] = tomatito_temp_format_duration( (int) $row['duration'] );
            }

            return array( 'success' => true, 'data' => $rows );
        },
    ));

    // ─── CRIAR ────────────────────────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/temporizadores', array(
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

            $sonidos_permitidos = array( 'default', 'clasico', 'campana', 'digital', 'suave', 'silent', 'vibracion' );
            $sound = ( isset( $body['sound'] ) && in_array( $body['sound'], $sonidos_permitidos, true ) )
                ? $body['sound']
                : 'default';

            $wpdb->insert( $table, array(
                'user_id'  => $user_id,
                'name'     => sanitize_text_field( $body['name'] ),
                'duration' => (int) ( $body['duration'] ?? 600 ),
                'sound'    => $sound,
            ));

            $id  = $wpdb->insert_id;
            $row = $wpdb->get_row(
                $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ),
                ARRAY_A
            );

            return array( 'success' => true, 'data' => $row, 'message' => 'Temporizador creado' );
        },
    ));

    // ─── EDITAR ───────────────────────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/temporizadores/(?P<id>\d+)', array(
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

            // Verifica que o temporizador pertence ao utilizador
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $table WHERE id = %d AND user_id = %d",
                    $id, $user_id
                )
            );

            if ( ! $exists ) {
                return array( 'success' => false, 'message' => 'Temporizador no encontrado' );
            }

            $sonidos_permitidos = array( 'default', 'clasico', 'campana', 'digital', 'suave', 'silent', 'vibracion' );
            $sound = ( isset( $body['sound'] ) && in_array( $body['sound'], $sonidos_permitidos, true ) )
                ? $body['sound']
                : 'default';

            $wpdb->update( $table, array(
                'name'     => sanitize_text_field( $body['name'] ?? '' ),
                'duration' => (int) ( $body['duration'] ?? 600 ),
                'sound'    => $sound,
            ), array( 'id' => $id ) );

            $row = $wpdb->get_row(
                $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ),
                ARRAY_A
            );

            return array( 'success' => true, 'data' => $row, 'message' => 'Temporizador actualizado' );
        },
    ));

    // ─── ELIMINAR ─────────────────────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/temporizadores/(?P<id>\d+)', array(
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
                return array( 'success' => false, 'message' => 'Temporizador no encontrado' );
            }

            return array( 'success' => true, 'message' => 'Temporizador eliminado' );
        },
    ));

    // ─── INICIAR ──────────────────────────────────────────────────────────────
    // Cria uma sessão em tomatito_timers a partir da configuração do temporizador.
    // ⚠️ IMPORTANTE: ao contrário dos pomodoros, NÃO paramos outros timers ativos.
    // Os temporizadores podem correr em paralelo (várias contas atrás simultâneas).
    // ⚠️ NOTA: aqui '{id}' na URL é o id da CONFIGURAÇÃO do temporizador (tabela
    // tomatito_temporizadores). Já pause/resume/stop/restart abaixo usam '{id}'
    // como o timer_id (a linha ativa em tomatito_timers) — é o que o front-end
    // já envia hoje (data-tid = item.timer_id).
    register_rest_route('tomatito/v1', '/temporizadores/(?P<id>\d+)/start', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ($table) {

            global $wpdb;
            $user_id = get_current_user_id();
			if ( ! $user_id ) {
				return array( 'success' => false, 'message' => 'No autorizado' );
			}
            $id       = (int) $request->get_param('id');
            $timers_t = $wpdb->prefix . 'tomatito_timers';

            $temporizador = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE id = %d AND user_id = %d",
                    $id, $user_id
                )
            );

            if ( ! $temporizador ) {
                return array( 'success' => false, 'message' => 'Temporizador no encontrado' );
            }

            $duration = (int) $temporizador->duration;

            $wpdb->insert( $timers_t, array(
                'user_id'    => $user_id,
                'type'       => 'temporizador',
                'state'      => 'running',
                'duration'   => $duration,
                'time_left'  => $duration,
				'pomodoro_id' => $id,
                'started_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ));

            // Atualiza o último uso
            $wpdb->update( $table,
                array( 'last_used_at' => current_time('mysql') ),
                array( 'id' => $id )
            );

            return array(
                'success'  => true,
                'message'  => 'Temporizador iniciado',
                'duration' => $duration,
                'timer_id' => $wpdb->insert_id,
            );
        },
    ));

    // ─── PAUSAR (por timer_id) ──────────────────────────────────────────────────
    // ⚠️ NOVO: rota que faltava. O Dashboard já chamava isto (botão ⏸ nos
    // temporizadores ativos), mas a rota nunca tinha sido criada — o clique
    // dava 404 silenciosamente. Reusa tomatito_calc_time_left(), definida
    // em "Tomatito API - Start Timer".
    register_rest_route('tomatito/v1', '/temporizadores/(?P<id>\d+)/pause', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) {
            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $timer_id = (int) $request->get_param('id');
            $timers_t = $wpdb->prefix . 'tomatito_timers';

            $timer = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $timers_t
                 WHERE id = %d AND user_id = %d AND type = 'temporizador' AND state = 'running'",
                $timer_id, $user_id
            ));
            if ( ! $timer ) {
                return array( 'success' => false, 'message' => 'No hay temporizador activo' );
            }

            $time_left = tomatito_calc_time_left( $timer );

            $wpdb->update( $timers_t, array(
                'state'      => 'paused',
                'time_left'  => $time_left,
                'updated_at' => current_time( 'mysql' ),
            ), array( 'id' => $timer->id ) );

            return array( 'success' => true, 'time_left' => $time_left, 'message' => 'PAUSE OK' );
        },
    ));

    // ─── RETOMAR (por timer_id) ─────────────────────────────────────────────────
    // ⚠️ NOVO: rota que faltava (mesma situação da pause acima).
    register_rest_route('tomatito/v1', '/temporizadores/(?P<id>\d+)/resume', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) {
            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $timer_id = (int) $request->get_param('id');
            $timers_t = $wpdb->prefix . 'tomatito_timers';

            $timer = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $timers_t
                 WHERE id = %d AND user_id = %d AND type = 'temporizador' AND state = 'paused'",
                $timer_id, $user_id
            ));
            if ( ! $timer ) {
                return array( 'success' => false, 'message' => 'No hay temporizador pausado' );
            }

            $wpdb->update( $timers_t, array(
                'state'      => 'running',
                'duration'   => (int) $timer->time_left,
                'started_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ), array( 'id' => $timer->id ) );

            return array( 'success' => true, 'time_left' => (int) $timer->time_left, 'message' => 'RESUME OK' );
        },
    ));

    // ─── DETENER (por timer_id) ─────────────────────────────────────────────────
    // ⚠️ NOVO: rota que faltava (mesma situação da pause acima). Ao contrário
    // dos pomodoros, temporizadores não geram sessão no histórico ao parar
    // (comportamento existente antes desta correção — não mudamos isso aqui).
    register_rest_route('tomatito/v1', '/temporizadores/(?P<id>\d+)/stop', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) {
            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $timer_id = (int) $request->get_param('id');
            $timers_t = $wpdb->prefix . 'tomatito_timers';

            $timer = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $timers_t
                 WHERE id = %d AND user_id = %d AND type = 'temporizador' AND state IN ('running','paused')",
                $timer_id, $user_id
            ));
            if ( ! $timer ) {
                return array( 'success' => false, 'message' => 'No hay temporizador activo' );
            }

            $time_left = tomatito_calc_time_left( $timer );

            $wpdb->update( $timers_t, array(
                'state'      => 'stopped',
                'time_left'  => $time_left,
                'updated_at' => current_time( 'mysql' ),
            ), array( 'id' => $timer->id ) );

            return array( 'success' => true, 'message' => 'STOP OK' );
        },
    ));

    // ─── REINICIAR (por timer_id) ───────────────────────────────────────────────
    // ⚠️ NOVO: rota que faltava. Diferente dos pomodoros (que reiniciam via
    // stop+start em 2 chamadas), o front-end de temporizadores já chama um
    // único endpoint /restart — então implementamos como uma operação atômica
    // aqui. IMPORTANTE: não usamos $timer->duration para saber a duração
    // "cheia" original, porque esse campo é sobrescrito pelo /resume (vira
    // "quanto restava" na última retomada). Buscamos a duração original de
    // verdade na tabela de configuração (tomatito_temporizadores), via o
    // pomodoro_id salvo no timer (que aqui guarda o id da configuração).
    register_rest_route('tomatito/v1', '/temporizadores/(?P<id>\d+)/restart', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) use ($table) {
            global $wpdb;
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $timer_id = (int) $request->get_param('id');
            $timers_t = $wpdb->prefix . 'tomatito_timers';

            $timer = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $timers_t WHERE id = %d AND user_id = %d AND type = 'temporizador'",
                $timer_id, $user_id
            ));
            if ( ! $timer ) {
                return array( 'success' => false, 'message' => 'Temporizador no encontrado' );
            }

            $config_id          = (int) $timer->pomodoro_id;
            $original_duration  = $config_id ? $wpdb->get_var( $wpdb->prepare(
                "SELECT duration FROM $table WHERE id = %d", $config_id
            ) ) : null;
            $duration = ( $original_duration !== null ) ? (int) $original_duration : (int) $timer->duration;

            $wpdb->update( $timers_t, array(
                'state'      => 'running',
                'duration'   => $duration,
                'time_left'  => $duration,
                'started_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ), array( 'id' => $timer->id ) );

            if ( $config_id ) {
                $wpdb->update( $table,
                    array( 'last_used_at' => current_time( 'mysql' ) ),
                    array( 'id' => $config_id )
                );
            }

            return array( 'success' => true, 'message' => 'RESTART OK', 'duration' => $duration );
        },
    ));


	// ─── DASHBOARD WIDGET (ATUALIZADO) ─────────────────────────────────────────
    // GET /tomatito/v1/temporizadores/dashboard
    // Devolve temporizadores ativos (running E paused), com timer_id e state,
    // e calcula o tempo restante respeitando as pausas (usa time_left + started_at).
    // Se não houver ativos, devolve os últimos usados (modo 'recent').
    register_rest_route('tomatito/v1', '/temporizadores/dashboard', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () use ($table) {

            global $wpdb;
            $user_id = get_current_user_id();
			if ( ! $user_id ) {
				return array( 'success' => false, 'message' => 'No autorizado' );
			}
            $timers_t = $wpdb->prefix . 'tomatito_timers';

            // 1. Procurar temporizadores ativos agora (running OU paused)
            $active = $wpdb->get_results( $wpdb->prepare(
                "SELECT t.id AS timer_id,
                        t.duration,
                        t.time_left,
                        t.state,
                        t.started_at,
                        tmp.name,
                        tmp.sound
                 FROM $timers_t t
                 INNER JOIN $table tmp
                         ON tmp.id = t.pomodoro_id
                 WHERE t.user_id = %d
                   AND t.type    = 'temporizador'
                   AND t.state  IN ('running','paused')
                 ORDER BY t.started_at DESC",
                $user_id
            ), ARRAY_A );

            if ( ! empty( $active ) ) {

                $output = array();
                $tz     = new DateTimeZone( wp_timezone_string() );

                foreach ( $active as $r ) {

                    if ( $r['state'] === 'paused' ) {
                        // Pausado: o tempo restante está congelado em time_left
                        $remaining = max( 0, (int) $r['time_left'] );
                    } else {
                        // Running: tempo restante = time_left - (agora - started_at)
                        try {
                            $started   = ( new DateTime( $r['started_at'], $tz ) )->getTimestamp();
                            $now       = ( new DateTime( 'now', $tz ) )->getTimestamp();
                            $elapsed   = $now - $started;
                            $remaining = max( 0, (int) $r['time_left'] - $elapsed );
                        } catch ( Exception $e ) {
                            $remaining = (int) $r['time_left'];
                        }
                    }

                    $output[] = array(
                        'timer_id'  => (int) $r['timer_id'],
                        'name'      => $r['name'],
                        'remaining' => $remaining,
                        'state'     => $r['state'],
                        'mode'      => 'running',
                        'sound'     => $r['sound'],
                    );
                }

                return array( 'success' => true, 'mode' => 'running', 'data' => $output );
            }

            // 2. Senão, devolver os últimos 3 usados (modo recente)
            $latest = $wpdb->get_results( $wpdb->prepare(
                "SELECT id, name, duration, last_used_at
                 FROM $table
                 WHERE user_id = %d
                   AND last_used_at IS NOT NULL
                 ORDER BY last_used_at DESC
                 LIMIT 3",
                $user_id
            ), ARRAY_A );

            $output = array();

            foreach ( $latest as $l ) {
                $output[] = array(
                    'timer_id'  => null,
                    'name'      => $l['name'],
                    'remaining' => (int) $l['duration'],
                    'state'     => 'recent',
                    'mode'      => 'recent',
                );
            }

            return array( 'success' => true, 'mode' => 'recent', 'data' => $output );
        },
    ));

});

// ─── HELPERS ──────────────────────────────────────────────────────────────────

function tomatito_temp_format_last_used( $datetime ) {
    if ( ! $datetime ) return 'Nunca';

    $diff = current_time('timestamp') - strtotime( $datetime );

    if ( $diff < 3600 )      return 'Hace ' . round( $diff / 60 ) . ' minutos';
    if ( $diff < 86400 )     return 'Hoy';
    if ( $diff < 86400 * 2 ) return 'Ayer';
    if ( $diff < 86400 * 7 ) return 'Hace ' . round( $diff / 86400 ) . ' días';

    return date( 'd/m/Y', strtotime( $datetime ) );
}

function tomatito_temp_format_duration( $seconds ) {
    // Converte segundos em formato humano: "45 min" ou "1 hora" ou "1 h 30 min"
    if ( $seconds < 60 ) {
        return $seconds . ' s';
    }

    $minutes = (int) floor( $seconds / 60 );

    if ( $minutes < 60 ) {
        return $minutes . ' min';
    }

    $hours = (int) floor( $minutes / 60 );
    $rest  = $minutes % 60;

    if ( $rest === 0 ) {
        return $hours . ' hora' . ( $hours > 1 ? 's' : '' );
    }

    return $hours . ' h ' . $rest . ' min';
}
