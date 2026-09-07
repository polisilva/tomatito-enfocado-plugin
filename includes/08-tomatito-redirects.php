<?php

add_action('template_redirect', function(){
	
	//bloquea el login si ya está logado
	//si el usuario está en la página de login y ya ha iniciado sesión, lo redirige a "mi cuenta"
	if(is_page('login') && is_user_logged_in()){
		wp_redirect('/dashboard/');
		exit;
	}
	
	//bloquea el panel si no está logado
	//si el usuario intenta entrar en la cuenta pero no está logado, lo redirijo al login
	if(is_page('mi-cuenta') && !is_user_logged_in()){
		wp_redirect('/login/');
		exit;
	}
	
});
