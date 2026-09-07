<?php



function tomatito_filtrar_posts_por_usuario($query){
	
	// Ejecuta solo en el admin
	if(!is_admin()){
		return;
	}
	
	// Ejecutar solo en la consulta principal de la página
	if(!$query->is_main_query()){
		return;
	}
	
	// CPTs de Tomatito
	$tomatito_cpts = array(
		'proyectos',
		'sesion',
		'tipo_sesion',
		'dispositivo',
	);
	
	// Verificamos si el post_type actual pertenece a Tomatito
	if(in_array($query->get('post_type'), $tomatito_cpts)){
		
		// Si el usuario NO es administrador
		if(!current_user_can('administrator')){
			
			// Filtrar los posts para mostrar solo los del usuario actual
			$query->set('author', get_current_user_id());
			
		}
		
	}
	
}

add_action('pre_get_posts','tomatito_filtrar_posts_por_usuario');
