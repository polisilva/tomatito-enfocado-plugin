<?php

/*
 * Habilita Application Passwords (autenticación para el app móvil).
 *
 * El plugin de Hostinger también engancha este mismo filtro para permitir
 * desactivar Application Passwords desde un ajuste de seguridad del panel
 * de hosting. Usamos una prioridad muy alta para asegurarnos de que nuestro
 * "true" se aplique siempre el último, sin depender del orden de carga de
 * los plugins ni de si ese ajuste de Hostinger está activado o no.
 */
add_filter( 'wp_is_application_passwords_available', '__return_true', 999 );
