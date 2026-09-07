<?php
/**
 * Description: Recuperación independiente para actualizaciones gestionadas por Omkrom.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function okpr_recovery_delete_tree( string $path ): void {
    if ( ! file_exists( $path ) ) return;
    if ( is_file( $path ) || is_link( $path ) ) { @unlink( $path ); return; }
    try { $it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST ); foreach ( $it as $item ) { $item->isDir() ? @rmdir( $item->getPathname() ) : @unlink( $item->getPathname() ); } } catch ( Throwable $e ) {}
    @rmdir( $path );
}
function okpr_recovery_copy_tree( string $source, string $dest ): bool {
    if ( ! is_dir( $source ) ) return false;
    if ( ! is_dir( $dest ) && ! wp_mkdir_p( $dest ) ) return false;
    try { $it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::SELF_FIRST ); foreach ( $it as $item ) { $relative = ltrim( str_replace( wp_normalize_path( $source ), '', wp_normalize_path( $item->getPathname() ) ), '/' ); $target = trailingslashit( $dest ) . $relative; if ( $item->isDir() ) { if ( ! is_dir( $target ) && ! wp_mkdir_p( $target ) ) return false; } elseif ( ! @copy( $item->getPathname(), $target ) ) return false; } } catch ( Throwable $e ) { return false; }
    return true;
}
function okpr_recovery_restore_entry( array $entry, string $reason ): bool {
    $plugin_dir = (string) ( $entry['plugin_dir'] ?? '' ); $backup = (string) ( $entry['backup_dir'] ?? '' );
    if ( ! $plugin_dir || ! is_dir( $backup ) ) return false;
    okpr_recovery_delete_tree( $plugin_dir );
    $ok = okpr_recovery_copy_tree( $backup, $plugin_dir );
    update_option( 'okpr_recovery_last_result', [ 'status' => $ok ? 'restored' : 'failed', 'message' => $ok ? sprintf( 'Se restauró automáticamente %s tras %s.', $entry['from_version'] ?? 'la versión anterior', $reason ) : 'No se pudo restaurar automáticamente la versión anterior.', 'time' => time(), 'plugin_file' => $entry['plugin_file'] ?? '' ], false );
    return $ok;
}
function okpr_recovery_process_forced(): void {
    $pending = get_option( 'okpr_recovery_pending', [] ); if ( ! is_array( $pending ) ) return;
    $changed = false; foreach ( $pending as $plugin => $entry ) { if ( ! empty( $entry['force_restore'] ) ) { okpr_recovery_restore_entry( $entry, 'una solicitud de rollback manual' ); unset( $pending[ $plugin ] ); $changed = true; } }
    if ( $changed ) update_option( 'okpr_recovery_pending', $pending, false );
}
add_action( 'muplugins_loaded', 'okpr_recovery_process_forced', 1 );
function okpr_recovery_mark_healthy(): void {
    $pending = get_option( 'okpr_recovery_pending', [] ); if ( ! is_array( $pending ) || ! $pending ) return;
    foreach ( $pending as $plugin => $entry ) { if ( time() - (int) ( $entry['created_at'] ?? 0 ) > 900 ) continue; update_option( 'okpr_recovery_last_result', [ 'status' => 'healthy', 'message' => sprintf( 'La actualización de %s a %s se validó correctamente.', $entry['from_version'] ?? '', $entry['to_version'] ?? '' ), 'time' => time(), 'plugin_file' => $plugin ], false ); unset( $pending[ $plugin ] ); }
    update_option( 'okpr_recovery_pending', $pending, false );
}
add_action( 'wp_loaded', 'okpr_recovery_mark_healthy', PHP_INT_MAX );
add_action( 'rest_api_init', static function() {
    register_rest_route( 'okpr-recovery/v1', '/status', [ 'methods' => 'GET', 'permission_callback' => function( WP_REST_Request $r ) { $expected = (string) get_option( 'okpr_recovery_secret', '' ); return $expected !== '' && hash_equals( $expected, (string) $r->get_header( 'X-OKPR-Recovery' ) ); }, 'callback' => function() { return new WP_REST_Response( [ 'ok' => true, 'pending' => get_option( 'okpr_recovery_pending', [] ), 'last_result' => get_option( 'okpr_recovery_last_result', [] ) ], 200 ); } ] );
    register_rest_route( 'okpr-recovery/v1', '/rollback', [ 'methods' => 'POST', 'permission_callback' => function( WP_REST_Request $r ) { $expected = (string) get_option( 'okpr_recovery_secret', '' ); return $expected !== '' && hash_equals( $expected, (string) $r->get_header( 'X-OKPR-Recovery' ) ); }, 'callback' => function( WP_REST_Request $r ) { $plugin = sanitize_text_field( (string) $r->get_param( 'plugin_file' ) ); $history = get_option( 'okpr_recovery_backups', [] ); if ( $plugin === '' && is_array( $history ) && count( $history ) === 1 ) $plugin = (string) array_key_first( $history ); $entry = $history[ $plugin ][0] ?? null; if ( ! is_array( $entry ) ) return new WP_REST_Response( [ 'error' => 'backup_not_found' ], 404 ); $ok = okpr_recovery_restore_entry( $entry, 'una orden remota firmada' ); return new WP_REST_Response( [ 'restored' => $ok, 'version' => $entry['from_version'] ?? '' ], $ok ? 200 : 500 ); } ] );
} );
register_shutdown_function( static function() {
    $error = error_get_last(); $fatal = $error && in_array( $error['type'] ?? 0, [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ], true ); if ( ! $fatal ) return;
    $pending = get_option( 'okpr_recovery_pending', [] ); if ( ! is_array( $pending ) ) return;
    foreach ( $pending as $plugin => $entry ) { if ( time() - (int) ( $entry['created_at'] ?? 0 ) > 900 ) continue; okpr_recovery_restore_entry( $entry, 'un error crítico durante la validación posterior' ); unset( $pending[ $plugin ] ); }
    update_option( 'okpr_recovery_pending', $pending, false );
} );