<?php

/* TOMATITO - API - CUENTA (móvil)
 *
 * Expone email + estado de la licencia Omkrom para la cabecera de "Mi cuenta"
 * en la app iOS (especificación "13. Móvil"). Reusa tomatito_get_license_info()
 * ya definida en 31-tomatito-mi-perfil.php.
 */

add_action('rest_api_init', function () {

    register_rest_route('tomatito/v1', '/mi-cuenta', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function () {
            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return array( 'success' => false, 'message' => 'No autorizado' );
            }

            $user = get_userdata( $user_id );

            $license           = function_exists( 'tomatito_get_license_info' )
                ? tomatito_get_license_info( $user_id )
                : array( 'status' => 'No disponible' );
            $omkrom_connected  = in_array( $license['status'] ?? '', array( 'Activa', 'Periodo de prueba' ), true );

            // Foto propia (subida desde "Mi perfil" en el móvil) tiene prioridad;
            // si no hay, cae al Gravatar de siempre — misma función que usa WP
            // en cualquier otro lado para mostrar el avatar del usuario.
            $custom_avatar = get_user_meta( $user_id, 'tomatito_avatar_url', true );
            $avatar_url    = $custom_avatar ?: get_avatar_url( $user_id, array( 'size' => 200 ) );

            return array(
                'success' => true,
                'data'    => array(
                    'email'               => $user ? $user->user_email : '',
                    'display_name'        => $user ? $user->display_name : '',
                    'avatar_url'          => $avatar_url,
                    'omkrom_connected'    => $omkrom_connected,
                    'omkrom_status_label' => $license['status'] ?? 'No disponible',
                ),
            );
        },
    ));

});
