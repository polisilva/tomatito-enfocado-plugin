<?php

/* TOMATITO - API - MI PERFIL (móvil)
 *
 * Pantalla "Mi perfil" del app iOS: datos de cuenta + plan Omkrom (misma
 * información que ya muestra la web en /mi-perfil, vía tomatito_get_license_info()
 * definida en 31-tomatito-mi-perfil.php) y subida de foto de perfil propia.
 *
 * La foto se guarda como una URL simple en user_meta ('tomatito_avatar_url'),
 * no como un adjunto de la Biblioteca de medios — es un avatar personal, no
 * contenido del sitio, así que no necesita aparecer ahí.
 *
 * El cambio de contraseña NO se expone aquí a propósito: el móvil no usa la
 * contraseña principal de la cuenta para nada (usa una Application Password
 * separada), así que gestionar esa contraseña desde un app de temporizadores
 * sería una superficie de riesgo innecesaria. En su lugar, el móvil gestiona
 * sus propias Application Passwords contra el endpoint nativo de WordPress
 * (/wp-json/wp/v2/users/me/application-passwords) — ya habilitado para este
 * plugin en 30-habilitar-application-passwords.php.
 */

add_action('rest_api_init', function () {

    // ─── GET /mi-perfil ─────────────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/mi-perfil', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () {
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }

            $user    = get_userdata( $user_id );
            $license = function_exists( 'tomatito_get_license_info' )
                ? tomatito_get_license_info( $user_id )
                : array(
                    'plan' => '—', 'status' => 'No disponible', 'license_key' => '—',
                    'expires' => '—', 'pomodoros_limit' => null, 'temporizadores_limit' => null,
                    'alarmas_limit' => null,
                );

            $custom_avatar = get_user_meta( $user_id, 'tomatito_avatar_url', true );
            $avatar_url    = $custom_avatar ?: get_avatar_url( $user_id, array( 'size' => 200 ) );

            return array(
                'success' => true,
                'data'    => array(
                    'email'                => $user ? $user->user_email : '',
                    'display_name'         => $user ? $user->display_name : '',
                    'avatar_url'           => $avatar_url,
                    'plan'                 => $license['plan'],
                    'status'               => $license['status'],
                    'license_key'          => $license['license_key'],
                    'expires'              => $license['expires'],
                    'pomodoros_limit'      => $license['pomodoros_limit'],
                    'temporizadores_limit' => $license['temporizadores_limit'],
                    'alarmas_limit'        => $license['alarmas_limit'],
                ),
            );
        },
    ));

    // ─── POST /mi-perfil/avatar ─────────────────────────────────────────────
    register_rest_route('tomatito/v1', '/mi-perfil/avatar', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $request ) {
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }

            $files = $request->get_file_params();
            if ( empty( $files['avatar'] ) || empty( $files['avatar']['tmp_name'] ) ) {
                return array( 'success' => false, 'message' => 'No se recibió ninguna imagen' );
            }

            $allowed_types = array( 'image/jpeg', 'image/png', 'image/heic' );
            if ( ! in_array( $files['avatar']['type'], $allowed_types, true ) ) {
                return array( 'success' => false, 'message' => 'Formato de imagen no soportado' );
            }
            // Límite generoso pero real — evita que una subida enorme sature el hosting.
            if ( (int) $files['avatar']['size'] > 8 * 1024 * 1024 ) {
                return array( 'success' => false, 'message' => 'La imagen pesa demasiado (máx. 8 MB)' );
            }

            if ( ! function_exists( 'wp_handle_upload' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            $upload = wp_handle_upload( $files['avatar'], array( 'test_form' => false ) );
            if ( isset( $upload['error'] ) ) {
                return array( 'success' => false, 'message' => $upload['error'] );
            }

            // Borra la foto anterior del disco (si había una propia) para no
            // acumular archivos huérfanos en cada cambio de avatar.
            $previous_url = get_user_meta( $user_id, 'tomatito_avatar_url', true );
            if ( $previous_url ) {
                $upload_dir    = wp_upload_dir();
                $previous_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $previous_url );
                if ( strpos( $previous_path, $upload_dir['basedir'] ) === 0 && file_exists( $previous_path ) ) {
                    @unlink( $previous_path );
                }
            }

            update_user_meta( $user_id, 'tomatito_avatar_url', $upload['url'] );

            return array(
                'success' => true,
                'data'    => array( 'avatar_url' => $upload['url'] ),
                'message' => 'Foto actualizada',
            );
        },
    ));

});
