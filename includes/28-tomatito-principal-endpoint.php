<?php

/* ===================================================================== */
/* TOMATITO API - TIMER PRINCIPAL (qual corre no card vermelho)          */
/* Guarda a escolha como preferência do utilizador (user_meta).          */
/* Sincroniza entre dispositivos. NÃO mexe em tabelas nem noutros        */
/* snippets. Guarda apenas { kind, source_id }.                          */
/*   kind      = 'pomodoro' | 'temporizador'                             */
/*   source_id = id do pomodoro (tabela pomodoros) OU                    */
/*               id do temporizador (tabela temporizadores)              */
/* ===================================================================== */
add_action( 'rest_api_init', function () {
    // GET /principal — devolve o timer principal escolhido (ou null)
    register_rest_route( 'tomatito/v1', '/principal', array(
        'methods'             => 'GET',
        'callback'            => 'tomatito_get_principal',
        'permission_callback' => '__return_true',
    ) );
    // POST /principal — define (ou limpa) o timer principal
    register_rest_route( 'tomatito/v1', '/principal', array(
        'methods'             => 'POST',
        'callback'            => 'tomatito_set_principal',
        'permission_callback' => '__return_true',
    ) );
} );
function tomatito_get_principal( $request ) {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return array( 'success' => false, 'message' => 'No autorizado' );
    }
    $raw     = get_user_meta( $user_id, 'tomatito_principal', true );
    if ( empty( $raw ) ) {
        return array( 'success' => true, 'data' => null );
    }
    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) || empty( $data['kind'] ) || empty( $data['source_id'] ) ) {
        return array( 'success' => true, 'data' => null );
    }
    return array(
        'success' => true,
        'data'    => array(
            'kind'      => $data['kind'],
            'source_id' => (int) $data['source_id'],
        ),
    );
}
function tomatito_set_principal( $request ) {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return array( 'success' => false, 'message' => 'No autorizado' );
    }
    $params  = $request->get_json_params();
    $kind      = isset( $params['kind'] )      ? sanitize_text_field( $params['kind'] ) : '';
    $source_id = isset( $params['source_id'] ) ? (int) $params['source_id'] : 0;
    // Limpar o principal (quando source_id é 0 ou kind inválido)
    if ( $source_id <= 0 || ! in_array( $kind, array( 'pomodoro', 'temporizador' ), true ) ) {
        delete_user_meta( $user_id, 'tomatito_principal' );
        return array( 'success' => true, 'data' => null );
    }
    $value = wp_json_encode( array(
        'kind'      => $kind,
        'source_id' => $source_id,
    ) );
    update_user_meta( $user_id, 'tomatito_principal', $value );
    return array(
        'success' => true,
        'data'    => array(
            'kind'      => $kind,
            'source_id' => $source_id,
        ),
    );
}
