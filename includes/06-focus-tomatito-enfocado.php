<?php

function tomatito_admin_menu(){
	
	add_menu_page(
		'Focus Tomatito Enfocado',
		'Focus Tomatito',
		'manage_options',
		'tomatito_menu',
		'tomatito_dashboard_admin',
		'dashicons-clock',
		3
	);
	
}

function tomatito_dashboard_admin(){
	echo '<h1>Focus Tomatito Enfocado</h1>';
	echo '<p>Panel del sistema Tomatito.</p>';
}

add_action('admin_menu','tomatito_admin_menu');
