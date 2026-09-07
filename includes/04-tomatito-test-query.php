<?php

function tomatito_test_query(){

    $sesiones = get_posts(array(
        'post_type' => 'sesion',
        'numberposts' => -1
    ));

    $output = '';

    if($sesiones){

        $output .= '<h2>Sesiones registradas</h2>';

        foreach($sesiones as $sesion){

            $output .= '<p>'.$sesion->post_title.'</p>';

        }

    } else {

        $output .= '<p>No hay sesiones todavía.</p>';

    }

    return $output;

}

add_shortcode('tomatito_test','tomatito_test_query');
