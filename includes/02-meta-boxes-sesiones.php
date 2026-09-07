<?php

function tomatito_add_meta_boxes(){
	
	add_meta_box(
		'tomatito_sesion_relaciones',
		'Relaciones de la sesión',
		'tomatito_relaciones_meta_box',
		'sesion',
		'normal',
		'default'
	);
}

add_action('add_meta_boxes', 'tomatito_add_meta_boxes');


function tomatito_relaciones_meta_box($post){
	
	$proyectos = get_posts(array(
		'post_type' => 'proyectos',
		'numberposts' => -1
	));
	
	$tipos = get_posts(array(
		'post_type' => 'tipo_sesion',
		'numberposts' => -1
	));
	
	$dispositivos = get_posts(array(
		'post_type' => 'dispositivo',
		'numberposts' => -1
	));
	
	?>
	
	<p>
		<label>Proyecto:</label><br>
		<select name="proyecto_id">
			<?php foreach($proyectos as $proyecto){ ?>
				<option value="<?php echo $proyecto->ID; ?>">
					<?php echo $proyecto->post_title; ?>
				</option>
			<?php } ?>
		</select>
	</p>
	
	
	<p>
		<label>Tipo de sesión</label><br>
		<select name="tipo_sesion">
			<?php foreach($tipos as $tipo){ ?>
				<option value="<?php echo $tipo->ID; ?>">
					<?php echo $tipo->post_title; ?>
				</option>
			<?php } ?>
		</select>
	</p>
	
	
	<p>
		<label>Dispositivo</label><br>
		<select name="dispositivo">
			<?php foreach($dispositivos as $dispositivo){ ?>
				<option value="<?php echo $dispositivo->ID; ?>">
					<?php echo $dispositivo->post_title; ?>
				</option>
			<?php } ?>
		</select>
	</p>

	<?php
	
}

function tomatito_guardar_relaciones_sesion($post_id){
	
	//evitar autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	
	//verificar permiso
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}
	
	//guardar proyecto
	if (isset($_POST['proyecto_id'])){
		update_post_meta(
			$post_id,
			'proyecto_id',
			sanitize_text_field($_POST['proyecto_id'])
			
		);
		
	}
	
	//guardar tipo
	if (isset($_POST['tipo_sesion'])){
		update_post_meta(
			$post_id,
			'tipo_sesion',
			sanitize_text_field($_POST['tipo_sesion'])
			
		);
		
	}
	
	//guardar dispositivo
	if (isset($_POST['dispositivo'])){
		update_post_meta(
			$post_id,
			'dispositivo',
			sanitize_text_field($_POST['dispositivo'])
			
		);
		
	}
	
}

add_action('save_post_sesion','tomatito_guardar_relaciones_sesion');
