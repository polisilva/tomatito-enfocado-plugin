<?php

//CPT PROYECTOS
function tomatito_cpt_proyectos(){
	
	$labels = array(
		'name' => 'Proyectos',
		'singular_name' => 'Proyecto',
	);
	
	$args = array(
		'labels' => $labels,
		'public' => true,
		'menu_icon' => 'dashicons-portfolio',
		'supports' => array('title','editor'),
		'show_in_rest'=> true,
		'show_in_menu'=> 'tomatito_menu'
		
	);
	
	register_post_type('proyectos', $args);
}

add_action('init', 'tomatito_cpt_proyectos');


//CPT SESIONES
function tomatito_cpt_sesiones() {

    $labels = array(
        'name' => 'Sesiones',
        'singular_name' => 'Sesión',
        'menu_name' => 'Sesiones Pomodoro',
        'add_new' => 'Añadir sesión',
        'add_new_item' => 'Nueva sesión',
        'edit_item' => 'Editar sesión',
        'all_items' => 'Sesiones Pomodoro'
	
    );

   $args = array(
    'labels' => $labels,
    'public' => true,
    'menu_icon' => 'dashicons-clock',
    'supports' => array('title','editor','author'),
    'has_archive' => true,
    'hierarchical' => true,
    'show_in_rest' => true,
	'show_in_menu'=> 'tomatito_menu'
);
	
	
    register_post_type('sesion', $args);
}

add_action('init', 'tomatito_cpt_sesiones');


//CPT TIPOS SESIONES
function tomatito_cpt_tipos_sesion(){
	
	$labels = array(
		'name' => 'Tipos de sesión',
		'singular_name' => 'Tipo de sesión',
	);
	
	$args = array(
		'labels' => $labels,
		'public' => true,
		'menu_icon' => 'dashicons-tag',
		'menu_position' => 32, 
		'supports' => array ('title'),
		'show_in_rest' => true,
		'show_in_menu'=> 'tomatito_menu'
	);
		
	register_post_type('tipo_sesion', $args);
}

add_action('init','tomatito_cpt_tipos_sesion');


// CPT DISPOSITIVOS
function tomatito_cpt_dispositivos(){

    $labels = array(
        'name' => 'Dispositivos',
        'singular_name' => 'Dispositivo',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'menu_icon' => 'dashicons-smartphone',
        'menu_position' => 33,
        'supports' => array('title'),
        'show_in_rest' => true,
		'show_in_menu'=> 'tomatito_menu'
    );

    register_post_type('dispositivo', $args);
}

add_action('init', 'tomatito_cpt_dispositivos');
