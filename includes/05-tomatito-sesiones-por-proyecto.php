<?php

function tomatito_sesiones_por_proyecto(){

	
    $sesiones = get_posts(array(
        'post_type' => 'sesion',
        'numberposts' => -1,
	
    ));

    $output = '<h2>Sesiones del proyecto</h2>';

    if($sesiones){

        foreach($sesiones as $sesion){
			
			$proyecto_id = get_post_meta($sesion->ID, 'proyecto_id', true);
			$proyecto_nombre = get_the_title($proyecto_id);

            $output .= '<p><strong>'.$sesion->post_title.'</strong><br>';
			$output .='Proyecto: '.$proyecto_nombre.'</p>';

        }

    } else {

        $output .= '<p>No hay sesiones.</p>';

    }

    return $output;

}

add_shortcode('tomatito_proyecto','tomatito_sesiones_por_proyecto');
