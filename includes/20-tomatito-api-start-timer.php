<?php

/* TOMATITO API - START TIMER */

// ─────────────────────────────────────────────────────────────────────────────
// HELPER: calcular tempo restante baseado no estado real do timer
// ─────────────────────────────────────────────────────────────────────────────
function tomatito_calc_time_left( $timer ) {

    if ( ! $timer ) {
        return 0;
    }

    $duration = (int) $timer->duration;

    // Usa a timezone do WordPress para converter started_at (que está em hora local)
    // para um timestamp que possamos comparar corretamente
    $tz_string = wp_timezone_string();
    $tz        = new DateTimeZone( $tz_string );

    try {
        $started_dt = new DateTime( $timer->started_at, $tz );
        $now_dt     = new DateTime( 'now', $tz );
        $started    = $started_dt->getTimestamp();
        $now        = $now_dt->getTimestamp();
    } catch ( Exception $e ) {
        // Se algo correr mal, devolve duration como fallback seguro
        return $duration;
    }

    // Se está a correr, calcula com base no tempo decorrido
    if ( $timer->state === 'running' ) {
        $elapsed = $now - $started;
        return max( 0, $duration - $elapsed );
    }

    // Se está pausado ou parado, devolve o que ficou guardado
    return (int) $timer->time_left;
}


// ─────────────────────────────────────────────────────────────────────────────
// REGISTO DE ENDPOINTS REST
// ─────────────────────────────────────────────────────────────────────────────
add_action( 'rest_api_init', function () {

    // ─── PAUSE ────────────────────────────────────────────────────────────────
    register_rest_route( 'tomatito/v1', '/pause', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'tomatito_api_pause',
    ));

    // ─── RESUME ───────────────────────────────────────────────────────────────
    register_rest_route( 'tomatito/v1', '/resume', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'tomatito_api_resume',
    ));

    // ─── STOP ─────────────────────────────────────────────────────────────────
    register_rest_route( 'tomatito/v1', '/stop', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'tomatito_api_stop',
    ));

    // ─── COMPLETE ─────────────────────────────────────────────────────────────
    register_rest_route( 'tomatito/v1', '/complete', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'tomatito_api_complete',
    ));

    // ─── CURRENT ──────────────────────────────────────────────────────────────
    register_rest_route( 'tomatito/v1', '/current', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => 'tomatito_api_current',
    ));

    // ─── STATUS (debug) ───────────────────────────────────────────────────────
    register_rest_route( 'tomatito/v1', '/status', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => 'tomatito_api_status',
    ));

    // ─── HISTORY (a partir da tabela timers) ──────────────────────────────────
    register_rest_route( 'tomatito/v1', '/history', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => 'tomatito_api_history',
    ));

    // ─── HISTORY-SESIONES (a partir do CPT 'sesion') ──────────────────────────
    register_rest_route( 'tomatito/v1', '/history-sesiones', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => 'tomatito_api_history_sesiones',
    ));
	
    // ─── HISTORY-OLDEST-DATE ──────────────────────────

	register_rest_route( 'tomatito/v1', '/history-oldest-date', array(
    'methods'             => 'GET',
    'permission_callback' => '__return_true',
    'callback'            => 'tomatito_api_history_oldest_date',
));

    // ─── DELETE SESION ────────────────────────────────────────────────────────
    register_rest_route( 'tomatito/v1', '/sesiones/(?P<id>\d+)', array(
        'methods'             => 'DELETE',
        'permission_callback' => '__return_true',
        'callback'            => function( $request ) {
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }
            $id   = (int) $request->get_param( 'id' );
            $post = get_post( $id );
            if ( ! $post || $post->post_type !== 'sesion' || (int) $post->post_author !== $user_id ) {
                return array( 'success' => false, 'message' => 'No encontrado' );
            }
            wp_delete_post( $id, true );
            return array( 'success' => true, 'message' => 'DELETE OK' );
        },
    ));

});

// ─────────────────────────────────────────────────────────────────────────────
// CALLBACK: PAUSE
// Pausa o timer ativo e cria sessão "Pausado" no histórico
// ⚠️ NOTA: mantida por compatibilidade. O card vermelho do Dashboard já não
// usa esta rota genérica — usa /pomodoros/{id}/pause (rotas por-id, definidas
// no snippet "Tomatito - API - Pomodoros"), que preserva phase/cycle/repetition.
// ─────────────────────────────────────────────────────────────────────────────
function tomatito_api_pause() {

    global $wpdb;
    $table   = $wpdb->prefix . 'tomatito_timers';
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return array( 'success' => false, 'message' => 'No autorizado' );
    }

    $timer = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table
         WHERE user_id = %d AND type = 'pomodoro'
         ORDER BY id DESC LIMIT 1",
        $user_id
    ));
    if ( ! $timer ) {
        return array( 'success' => false, 'message' => 'Nenhum timer ativo' );
    }

    $time_left = tomatito_calc_time_left( $timer );

    $wpdb->update( $table, array(
        'state'      => 'paused',
        'time_left'  => $time_left,
        'updated_at' => current_time( 'mysql' ),
    ), array( 'id' => $timer->id ) );

    // Cria sessão pausada no CPT
    // ⚠️ CORRIGIDO: título agora leva o nome do pomodoro específico, não um
    // "Pomodoro" genérico — assim o histórico mostra qual pomodoro foi pausado.
    $pomo_name  = function_exists( 'tomatito_get_pomodoro_name' ) ? tomatito_get_pomodoro_name( $timer->pomodoro_id ) : null;
    $post_title = $pomo_name ? $pomo_name : 'Pomodoro';
    $sesion_id  = wp_insert_post( array(
        'post_type'   => 'sesion',
        'post_title'  => $post_title,
        'post_status' => 'publish',
        'post_author' => $user_id,
    ));

    if ( $sesion_id && ! is_wp_error( $sesion_id ) ) {
        $elapsed = max( 1, (int) ( $timer->duration - $time_left ) );
        update_post_meta( $sesion_id, 'duration', $elapsed );
        update_post_meta( $sesion_id, 'state', 'paused' );
    }

    return array(
        'success'   => true,
        'time_left' => $time_left,
        'message'   => 'PAUSE OK',
    );
}


// ─────────────────────────────────────────────────────────────────────────────
// CALLBACK: RESUME
// Retoma um timer pausado
// ⚠️ NOTA: mantida por compatibilidade — ver nota em tomatito_api_pause().
// ─────────────────────────────────────────────────────────────────────────────
function tomatito_api_resume() {

    global $wpdb;
    $table   = $wpdb->prefix . 'tomatito_timers';
    $user_id = get_current_user_id();
	if ( ! $user_id ) {
    return array( 'success' => false, 'message' => 'No autorizado' );
}

    $timer = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table
         WHERE user_id = %d AND type = 'pomodoro' AND state = 'paused'
         ORDER BY id DESC LIMIT 1",
        $user_id
    ));

    if ( ! $timer ) {
        return array( 'success' => false, 'message' => 'No hay timer pausado' );
    }

    $wpdb->update( $table, array(
        'state'      => 'running',
        'duration'   => (int) $timer->time_left, // recomeça a partir do que restava
        'started_at' => current_time( 'mysql' ),
        'updated_at' => current_time( 'mysql' ),
    ), array( 'id' => $timer->id ) );

    return array(
        'success'   => true,
        'time_left' => (int) $timer->time_left,
        'message'   => 'RESUME OK',
    );
}


// ─────────────────────────────────────────────────────────────────────────────
// CALLBACK: STOP
// Para um timer ativo (cancelamento manual)
// ⚠️ NOTA: mantida por compatibilidade — ver nota em tomatito_api_pause().
// ─────────────────────────────────────────────────────────────────────────────
function tomatito_api_stop() {

    global $wpdb;
    $table   = $wpdb->prefix . 'tomatito_timers';
    $user_id = get_current_user_id();
if ( ! $user_id ) {
    return array( 'success' => false, 'message' => 'No autorizado' );
}

    $timer = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table
         WHERE user_id = %d AND type = 'pomodoro' AND state IN ('running','paused')
         ORDER BY id DESC LIMIT 1",
        $user_id
    ));

    if ( ! $timer ) {
        return array( 'success' => false, 'message' => 'No hay timer activo' );
    }

    $time_left = tomatito_calc_time_left( $timer );

    $wpdb->update( $table, array(
        'state'      => 'stopped',
        'time_left'  => $time_left,
        'updated_at' => current_time( 'mysql' ),
    ), array( 'id' => $timer->id ) );

    // Cria sessão "Cancelado" no CPT
    // ⚠️ CORRIGIDO: título agora leva o nome do pomodoro específico.
    $elapsed    = max( 1, (int) ( $timer->duration - $time_left ) );
    $pomo_name  = function_exists( 'tomatito_get_pomodoro_name' ) ? tomatito_get_pomodoro_name( $timer->pomodoro_id ) : null;
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
}


// ─────────────────────────────────────────────────────────────────────────────
// CALLBACK: COMPLETE
// Marca o timer como completado. Chamado quando a ÚLTIMA repetição (long_break
// final) termina — ou seja, depois que /pomodoros/{id}/advance-phase devolve
// is_final=true. Aceita um 'timer_id' opcional para saber EXATAMENTE qual timer
// terminou, evitando confundir dois pomodoros ativos simultâneos entre si.
// ─────────────────────────────────────────────────────────────────────────────
function tomatito_api_complete( $request = null ) {

    global $wpdb;
    $table   = $wpdb->prefix . 'tomatito_timers';
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return array( 'success' => false, 'message' => 'No autorizado' );
    }

    $timer_id = $request ? (int) $request->get_param( 'timer_id' ) : 0;

    if ( $timer_id > 0 ) {
        // Sabemos EXATAMENTE qual timer terminou
        $timer = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table
             WHERE id = %d AND user_id = %d AND type = 'pomodoro' AND state = 'running'
             LIMIT 1",
            $timer_id,
            $user_id
        ));
    } else {
        // Fallback antigo (compatibilidade, caso o JS não mande o id)
        $timer = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table
             WHERE user_id = %d AND type = 'pomodoro' AND state = 'running'
             ORDER BY id DESC LIMIT 1",
            $user_id
        ));
    }

    if ( ! $timer ) {
        return array( 'success' => false, 'message' => 'No hay timer activo' );
    }

    // Marca como completado
    $wpdb->update( $table, array(
        'state'      => 'stopped',
        'time_left'  => 0,
        'updated_at' => current_time( 'mysql' ),
    ), array( 'id' => $timer->id ) );

    // Procura uma sessão "paused" criada DEPOIS do início deste timer
    // (ou seja, uma pausa que faz parte deste mesmo Pomodoro)
    $sesion_pausada = get_posts( array(
        'post_type'      => 'sesion',
        'author'         => $user_id,
        'numberposts'    => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'date_query'     => array(
            array(
                'after'     => $timer->started_at,
                'inclusive' => true,
            ),
        ),
        'meta_query'     => array(
            array(
                'key'   => 'state',
                'value' => 'paused',
            ),
        ),
    ));

    if ( ! empty( $sesion_pausada ) ) {

        // Promove a sessão pausada para completed
        update_post_meta( $sesion_pausada[0]->ID, 'state', 'completed' );

    } else {

        // Cria nova sessão completed
        // ⚠️ CORRIGIDO: título agora leva o nome do pomodoro específico.
        $pomo_name  = function_exists( 'tomatito_get_pomodoro_name' ) ? tomatito_get_pomodoro_name( $timer->pomodoro_id ) : null;
        $post_title = $pomo_name ? $pomo_name : 'Pomodoro';
        $sesion_id  = wp_insert_post( array(
            'post_type'   => 'sesion',
            'post_title'  => $post_title,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ));

        if ( $sesion_id && ! is_wp_error( $sesion_id ) ) {
            update_post_meta( $sesion_id, 'duration', (int) $timer->duration );
            update_post_meta( $sesion_id, 'state', 'completed' );
        }
    }

    return array( 'success' => true, 'message' => 'COMPLETE OK' );
}

// ─────────────────────────────────────────────────────────────────────────────
// CALLBACK: CURRENT
// Devolve o estado atual do timer do utilizador para o card vermelho.
// ⚠️ FILTRA type = 'pomodoro' — o card vermelho mostra SÓ pomodoros.
// ⭐ PRIORIDADE AO PRINCIPAL: se há um pomodoro marcado como principal e
//    a correr/pausado, devolve ESSE. Caso contrário, comporta-se como sempre
//    (o último pomodoro).
// ⚠️ Devolve 'timer_id' (para /complete) e agora também 'pomodoro_id' +
//    phase/cycle/repetition/auto_start/pause_on_end, para o card vermelho
//    poder usar as rotas por-id (/pomodoros/{id}/pause|resume|stop|advance-phase)
//    e mostrar em que fase está — mesmo padrão já usado na lista
//    "Pomodoros Activos" e na página de Pomodoros.
// ─────────────────────────────────────────────────────────────────────────────
function tomatito_api_current() {

    global $wpdb;
    $table         = $wpdb->prefix . 'tomatito_timers';
    $pomodoros_t   = $wpdb->prefix . 'tomatito_pomodoros';
    $user_id       = get_current_user_id();
    if ( ! $user_id ) {
        return array( 'success' => false, 'message' => 'No autorizado' );
    }

    // Procurar último pomodoro usado HOJE (qualquer estado)
    $today        = current_time( 'Y-m-d' );
    $last_today   = null;
    $last_pomodoro_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT pomodoro_id FROM $table
         WHERE user_id = %d
           AND type    = 'pomodoro'
           AND pomodoro_id IS NOT NULL
           AND DATE(started_at) = %s
         ORDER BY id DESC LIMIT 1",
        $user_id,
        $today
    ));

    if ( $last_pomodoro_id ) {
        $pomodoro = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, name FROM $pomodoros_t WHERE id = %d",
            (int) $last_pomodoro_id
        ));
        if ( $pomodoro ) {
            $last_today = array(
                'id'   => (int) $pomodoro->id,
                'name' => $pomodoro->name,
            );
        }
    }

// ── ⭐ Dá prioridade ao pomodoro marcado como PRINCIPAL ──
    $timer            = null;
    $principal_raw    = get_user_meta( $user_id, 'tomatito_principal', true );
    $principal_valido = false;

    if ( ! empty( $principal_raw ) ) {
        $principal = json_decode( $principal_raw, true );
        if ( is_array( $principal )
             && isset( $principal['kind'] ) && $principal['kind'] === 'pomodoro'
             && ! empty( $principal['source_id'] ) ) {

            $timer = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM $table
                 WHERE user_id     = %d
                   AND type        = 'pomodoro'
                   AND pomodoro_id = %d
                   AND state IN ('running','paused')
                 ORDER BY id DESC LIMIT 1",
                $user_id,
                (int) $principal['source_id']
            ));

            if ( $timer ) {
                $principal_valido = true;
            }
        }
    }

    // ── Sem principal válido: verifica quantos pomodoros estão ativos ──
    if ( ! $principal_valido ) {

        $ativos = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table
             WHERE user_id = %d
               AND type    = 'pomodoro'
               AND state IN ('running','paused')
             ORDER BY id DESC",
            $user_id
        ));

        if ( count( $ativos ) === 1 ) {
            // ⭐ Só sobrou um ativo: torna-se principal automaticamente
            // (persiste no user meta para a estrela também refletir isso)
            $timer = $ativos[0];
            if ( ! empty( $timer->pomodoro_id ) ) {
                update_user_meta( $user_id, 'tomatito_principal', wp_json_encode( array(
                    'kind'      => 'pomodoro',
                    'source_id' => (int) $timer->pomodoro_id,
                ) ) );
            }
        } elseif ( count( $ativos ) > 1 ) {
            // Vários ativos, nenhum principal escolhido: mostra o mais recente
            $timer = $ativos[0];
        }
        // Se count === 0, $timer continua null e cai no fallback abaixo
    }

    // ── Se não há nenhum a correr: mostra o último (para 'Terminado' / 'Reiniciar el último') ──
    if ( ! $timer ) {
        $timer = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table
             WHERE user_id = %d
               AND type    = 'pomodoro'
             ORDER BY id DESC LIMIT 1",
            $user_id
        ));
    }

    if ( ! $timer ) {
        return array(
            'success'    => false,
            'message'    => 'No hay timer',
            'last_today' => $last_today,
        );
    }

    // Configuração do pomodoro (nome + fases) que está a ser mostrado
    $timer_name   = null;
    $pomodoro_cfg = null;
    if ( ! empty( $timer->pomodoro_id ) ) {
        $pomodoro_cfg = $wpdb->get_row( $wpdb->prepare(
            "SELECT name, cycles, repetitions, auto_start, pause_on_end FROM $pomodoros_t WHERE id = %d",
            (int) $timer->pomodoro_id
        ));
        if ( $pomodoro_cfg ) {
            $timer_name = $pomodoro_cfg->name;
        }
    }

    $phase      = $timer->phase ?: 'work';
    $total_reps = ( $pomodoro_cfg && ! empty( $pomodoro_cfg->repetitions ) ) ? (int) $pomodoro_cfg->repetitions : 1;

    // tomatito_phase_label() vem do snippet "Tomatito - API - Pomodoros"
    $phase_label = function_exists( 'tomatito_phase_label' ) ? tomatito_phase_label( $phase ) : $phase;

    return array(
        'success'    => true,
        'data'       => array(
            'timer_id'          => (int) $timer->id,
            'pomodoro_id'       => (int) $timer->pomodoro_id,
            'state'             => $timer->state,
            'duration'          => (int) $timer->duration,
            'remaining'         => tomatito_calc_time_left( $timer ),
            'name'              => $timer_name,
            'phase'             => $phase,
            'phase_label'       => $phase_label,
            'cycle'             => (int) $timer->cycle_count,
            'cycles_total'      => $pomodoro_cfg ? (int) $pomodoro_cfg->cycles : 1,
            'repetition'        => (int) $timer->repetition_count,
            'repetitions_total' => $total_reps,
            'auto_start'        => $pomodoro_cfg ? (int) $pomodoro_cfg->auto_start   : 1,
            'pause_on_end'      => $pomodoro_cfg ? (int) $pomodoro_cfg->pause_on_end : 0,
        ),
        'last_today' => $last_today,
    );
}


// ─────────────────────────────────────────────────────────────────────────────
// CALLBACK: STATUS (debug — devolve a linha crua da BD)
// ─────────────────────────────────────────────────────────────────────────────
function tomatito_api_status() {

    global $wpdb;
    $table   = $wpdb->prefix . 'tomatito_timers';
    $user_id = get_current_user_id();
	if ( ! $user_id ) {
		return array( 'success' => false, 'message' => 'No autorizado' );
	}

    $timer = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d ORDER BY id DESC LIMIT 1",
        $user_id
    ));

    if ( ! $timer ) {
        return array( 'success' => false, 'message' => 'Nenhum timer encontrado' );
    }

    return array( 'success' => true, 'data' => $timer );
}


// ─────────────────────────────────────────────────────────────────────────────
// CALLBACK: HISTORY (a partir da tabela timers)
// ─────────────────────────────────────────────────────────────────────────────
function tomatito_api_history() {

    global $wpdb;
    $table   = $wpdb->prefix . 'tomatito_timers';
    $user_id = get_current_user_id();
	if ( ! $user_id ) {
		return array( 'success' => false, 'message' => 'No autorizado' );
	}
    $today   = date( 'Y-m-d' );

    $timers = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table
         WHERE user_id = %d AND DATE(started_at) = %s
         ORDER BY id DESC",
        $user_id,
        $today
    ), ARRAY_A );

    foreach ( $timers as &$t ) {

        if ( $t['state'] === 'stopped' && (int) $t['time_left'] === 0 ) {
            $t['status_label'] = 'Completado';
        } elseif ( $t['state'] === 'stopped' ) {
            $t['status_label'] = 'Cancelado';
        } elseif ( $t['state'] === 'running' ) {
            $t['status_label'] = 'En progreso';
        } else {
            $t['status_label'] = 'Pausado';
        }
    }

    return array( 'success' => true, 'data' => $timers );
}


// ─────────────────────────────────────────────────────────────────────────────
// CALLBACK: HISTORY-SESIONES (a partir do CPT 'sesion')
// ─────────────────────────────────────────────────────────────────────────────
function tomatito_api_history_sesiones( $request ) {

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return array( 'success' => false, 'message' => 'No autorizado' );
    }

    // Lê parâmetros de paginação
    $page     = $request ? (int) $request->get_param( 'page' ) : 1;
    $per_page = $request ? (int) $request->get_param( 'per_page' ) : 6;
    if ( $page < 1 )       $page = 1;
    if ( $per_page < 1 )   $per_page = 6;
    if ( $per_page > 100 ) $per_page = 100; // limite de segurança

    // Lê parâmetros de data
    $date_param = $request ? $request->get_param( 'date' )      : null;
    $date_from  = $request ? $request->get_param( 'date_from' ) : null;
    $date_to    = $request ? $request->get_param( 'date_to' )   : null;

    // Constrói o date_query consoante o que veio
    $date_query = array();

    if ( $date_from && $date_to
         && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from )
         && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {

        // ── INTERVALO DE DATAS (novo) ──
        $date_query = array(
            array(
                'after'     => $date_from . ' 00:00:00',
                'before'    => $date_to   . ' 23:59:59',
                'inclusive' => true,
            ),
        );

    } elseif ( $date_param && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_param ) ) {

        // ── UM DIA ESPECÍFICO (comportamento antigo) ──
        $date_query = array(
            array(
                'year'  => substr( $date_param, 0, 4 ),
                'month' => substr( $date_param, 5, 2 ),
                'day'   => substr( $date_param, 8, 2 ),
            ),
        );

    } else {

        // ── SEM PARÂMETRO: usa hoje (timezone do WP) ──
        $tz_string = wp_timezone_string();
        $tz        = new DateTimeZone( $tz_string );
        $now       = new DateTime( 'now', $tz );
        $date_query = array(
            array(
                'year'  => $now->format( 'Y' ),
                'month' => $now->format( 'm' ),
                'day'   => $now->format( 'd' ),
            ),
        );
    }

    // Primeiro: buscar TODAS as sessões do período (para contar total + calcular resumo)
    $all_ids = get_posts( array(
        'post_type'   => 'sesion',
        'author'      => $user_id,
        'numberposts' => -1,
        'fields'      => 'ids',
        'date_query'  => $date_query,
    ));

    $total = count( $all_ids );

    // Calcular o resumo: nº de completadas + minutos focados (só completadas)
    $total_completadas = 0;
    $total_segundos    = 0;

    foreach ( $all_ids as $sid ) {
        $estado = get_post_meta( $sid, 'state', true ) ?: 'completed';
        if ( $estado === 'completed' ) {
            $total_completadas++;
            $total_segundos += (int) get_post_meta( $sid, 'duration', true );
        }
    }

    $total_minutos = (int) round( $total_segundos / 60 );

    // Depois: buscar só a página pedida
    $sesiones = get_posts( array(
        'post_type'   => 'sesion',
        'author'      => $user_id,
        'numberposts' => $per_page,
        'offset'      => ( $page - 1 ) * $per_page,
        'date_query'  => $date_query,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ));

    $output = array();

    foreach ( $sesiones as $sesion ) {

        $proyecto_id    = get_post_meta( $sesion->ID, 'proyecto_id', true );
        $tipo_id        = get_post_meta( $sesion->ID, 'tipo_sesion', true );
        $dispositivo_id = get_post_meta( $sesion->ID, 'dispositivo', true );

        $output[] = array(
            'id'               => $sesion->ID,
            'name'             => $sesion->post_title,
            'duration'         => (int) get_post_meta( $sesion->ID, 'duration', true ),
            'state'            => get_post_meta( $sesion->ID, 'state', true ) ?: 'completed',
            'proyecto_name'    => $proyecto_id    ? get_the_title( $proyecto_id )    : '',
            'tipo_name'        => $tipo_id        ? get_the_title( $tipo_id )        : '',
            'dispositivo_name' => $dispositivo_id ? get_the_title( $dispositivo_id ) : '',
            'started_at'       => $sesion->post_date,
        );
    }

    return array(
        'success'    => true,
        'data'       => $output,
        'summary'    => array(
            'completadas' => $total_completadas,
            'minutos'     => $total_minutos,
        ),
        'pagination' => array(
            'page'        => $page,
            'per_page'    => $per_page,
            'total'       => $total,
            'total_pages' => (int) ceil( $total / $per_page ),
        ),
    );
}

function tomatito_api_history_oldest_date() {

    $user_id = get_current_user_id();
	if ( ! $user_id ) {
		return array( 'success' => false, 'message' => 'No autorizado' );
	}

    // Procura a sessão mais antiga deste utilizador
    $sesiones = get_posts( array(
        'post_type'   => 'sesion',
        'author'      => $user_id,
        'numberposts' => 1,
        'orderby'     => 'date',
        'order'       => 'ASC',
    ));
	

    if ( empty( $sesiones ) ) {
        return array( 'success' => true, 'oldest' => null );
    }

    // Formato YYYY-MM-DD
    $oldest_date = mysql2date( 'Y-m-d', $sesiones[0]->post_date );

    return array( 'success' => true, 'oldest' => $oldest_date );
}
