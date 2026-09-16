<?php
/**
 * Plugin Name: Tomatito Enfocado
 * Description: Migração de compatibilidade do Tomatito Enfocado a partir dos snippets atuais. Mantém a lógica e a ordem de carregamento dos snippets.
 * Version: 1.0.60
 * Author: Tomatito Enfocado
 * Requires at least: 6.0
 * Requires PHP: 8.2
  * Update URI: https://app.omkrom.com/omkrom-repository/focus
 */

/* OMKROM REPOSITORY INTEGRATION:BEGIN */
if ( is_file( __DIR__ . '/omkrom-repository/updater.php' ) ) {
    require_once __DIR__ . '/omkrom-repository/updater.php';
}
/* OMKROM REPOSITORY INTEGRATION:END */


defined('ABSPATH') || exit;

/*
 * IMPORTANTE:
 * Esta versão é uma migração fiel dos snippets ativos atuais.
 * Não refatorar nem alterar comportamento nesta versão.
 * Os arquivos abaixo são carregados na mesma ordem do export original.
 */

$tomatito_enfocado_files = array(
    '01-cpt-tomatito.php',
    '02-meta-boxes-sesiones.php',
    '03-tomatito-dashboard.php',
    '04-tomatito-test-query.php',
    '05-tomatito-sesiones-por-proyecto.php',
    '06-focus-tomatito-enfocado.php',
    '07-tomatito-seguridad-usuarios.php',
    '08-tomatito-redirects.php',
    '09-tomatito-create-table-timers.php',
    '10-tomatito-create-table-pomodoros.php',
    '11-tomatito-api-pomodoros.php',
    '12-tomatito-pomodoros-page.php',
    '13-tomatito-landing-css.php',
    '14-tomatito-create-table-temporizadores.php',
    '15-tomatito-api-temporizadores.php',
    '16-tomatito-temporizadores-page.php',
    '17-tomatito-create-table-alarmas.php',
    '18-tomatito-api-alarmas.php',
    '19-tomatito-alarmas-page.php',
    '20-tomatito-api-start-timer.php',
    '21-tomatito-notifications.php',
    '22-tomatito-login-fake.php',
    '23-tomatito-rest-nonce.php',
    '24-tomatito-api-ajustes.php',
    '25-tomatito-ajustes-page.php',
    '26-tomatito-aplicar-tema-global.php',
    '27-temporizadores-control-endpoints.php',
    '28-tomatito-principal-endpoint.php',
    '29-tomatito-historial-completo.php',
    '30-habilitar-application-passwords.php',
    '31-tomatito-mi-perfil.php',
    '32-tomatito-anuncios.php',
    '33-tomatito-api-cuenta-movil.php'
);

foreach ($tomatito_enfocado_files as $tomatito_file) {
    require_once __DIR__ . '/includes/' . $tomatito_file;
}

unset($tomatito_file, $tomatito_enfocado_files);
