<?php

/**
 * Tomatito - REST Nonce
 * Disponibiliza wpApiSettings.nonce no JavaScript para autenticar chamadas REST
 */

add_action( 'wp_enqueue_scripts', function() {
    // Só aplica para utilizadores logados
    if ( ! is_user_logged_in() ) {
        return;
    }
    
    // Cria um script handle virtual e adiciona dados
    wp_register_script( 'tomatito-rest-helper', '', array(), null, false );
    wp_enqueue_script( 'tomatito-rest-helper' );
    
    wp_localize_script( 'tomatito-rest-helper', 'wpApiSettings', array(
        'root'  => esc_url_raw( rest_url() ),
        'nonce' => wp_create_nonce( 'wp_rest' ),
    ) );
});
