<?php

add_action('rest_api_init', function () {

    // ─── GET /ajustes ─────────────────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/ajustes', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () {
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }

            // Lê as preferências do utilizador (com defaults)
            $idioma = get_user_meta( $user_id, 'tomatito_idioma', true );
            if ( ! $idioma ) { $idioma = 'es_ES'; }

            $hora = get_user_meta( $user_id, 'tomatito_hora', true );
            if ( ! $hora ) { $hora = '24h'; }

            $tema = get_user_meta( $user_id, 'tomatito_tema', true );
            if ( ! $tema ) { $tema = 'claro'; }

            $notificaciones = get_user_meta( $user_id, 'tomatito_notificaciones', true );
            if ( ! $notificaciones ) { $notificaciones = 'on'; }

            // Sons — um por tipo, com defaults diferentes para distinguir
            $sonido_pomodoro = get_user_meta( $user_id, 'tomatito_sonido_pomodoro', true );
            if ( ! $sonido_pomodoro ) { $sonido_pomodoro = 'clasico'; }

            $sonido_temporizador = get_user_meta( $user_id, 'tomatito_sonido_temporizador', true );
            if ( ! $sonido_temporizador ) { $sonido_temporizador = 'digital'; }

            $sonido_alarma = get_user_meta( $user_id, 'tomatito_sonido_alarma', true );
            if ( ! $sonido_alarma ) { $sonido_alarma = 'campana'; }

            return array(
                'success' => true,
                'data'    => array(
                    'idioma'              => $idioma,
                    'hora'                => $hora,
                    'tema'                => $tema,
                    'notificaciones'      => $notificaciones,
                    'sonido_pomodoro'     => $sonido_pomodoro,
                    'sonido_temporizador' => $sonido_temporizador,
                    'sonido_alarma'       => $sonido_alarma,
                ),
            );
        },
    ));

    // ─── POST /ajustes ────────────────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/ajustes', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) {
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }

            $body = $request->get_json_params();

            // Idioma — por agora só permitimos es_ES
            if ( isset( $body['idioma'] ) ) {
                $idioma = sanitize_text_field( $body['idioma'] );
                if ( in_array( $idioma, array( 'es_ES' ), true ) ) {
                    update_user_meta( $user_id, 'tomatito_idioma', $idioma );
                }
            }

            // Formato de hora
            if ( isset( $body['hora'] ) ) {
                $hora = sanitize_text_field( $body['hora'] );
                if ( in_array( $hora, array( '24h', '12h' ), true ) ) {
                    update_user_meta( $user_id, 'tomatito_hora', $hora );
                }
            }

            // Tema
            if ( isset( $body['tema'] ) ) {
                $tema = sanitize_text_field( $body['tema'] );
                if ( in_array( $tema, array( 'claro', 'oscuro' ), true ) ) {
                    update_user_meta( $user_id, 'tomatito_tema', $tema );
                }
            }

            // Notificaciones
            if ( isset( $body['notificaciones'] ) ) {
                $notif = sanitize_text_field( $body['notificaciones'] );
                if ( in_array( $notif, array( 'on', 'off' ), true ) ) {
                    update_user_meta( $user_id, 'tomatito_notificaciones', $notif );
                }
            }

            // Sons — mesma lista de sons válidos para os 3 tipos
            $sonidos_permitidos = array( 'clasico', 'campana', 'digital', 'suave', 'silent', 'peeeem' );

            foreach ( array( 'sonido_pomodoro', 'sonido_temporizador', 'sonido_alarma' ) as $campo ) {
                if ( isset( $body[ $campo ] ) ) {
                    $valor = sanitize_text_field( $body[ $campo ] );
                    if ( in_array( $valor, $sonidos_permitidos, true ) ) {
                        update_user_meta( $user_id, 'tomatito_' . $campo, $valor );
                    }
                }
            }

            return array(
                'success' => true,
                'message' => 'Ajustes guardados',
            );
        },
    ));
});
