<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'OKPR_Client_68e2cc37e73d', false ) ) {
final class OKPR_Client_68e2cc37e73d {
    private const REPOSITORY = 'https://app.omkrom.com';
    public const INTEGRATION_VERSION = '1.7.7';
    public const PROTOCOL_VERSION = '2.0';
    private const SLUG = 'focus';
    private const CHANNEL = 'stable';
    private const LICENSE_OPTION = 'okpr_license_focus';
    private const LICENSE_PROVIDER = 'none';
    private const AUTO_UPDATE_OPTION = 'okpr_auto_updates_focus';
    private const CHANNEL_OPTION = 'okpr_update_channel_focus';
    private const DELAY_OPTION = 'okpr_update_delay_focus';
    private const ROLLOUT_OPTION = 'okpr_rollout_mode_focus';
    private const ROLLOUT_PERCENT_OPTION = 'okpr_rollout_percent_focus';
    private const AUTO_ROLLBACK_OPTION = 'okpr_auto_rollback_focus';
    private const AUTO_UPDATE_CRON = 'okpr_auto_upgrade_68e2cc37e73d';
    private const AUTOMATIC_UPDATES = true;
    private const ROLLBACK_ALLOWED = true;
    private const SAFE_UPDATES = true;
    private const CHANNEL_BETA_ALLOWED = true;
    private const CHANNEL_ALPHA_ALLOWED = true;
    private const DEFAULT_AUTO_UPDATES = 'enabled';
    private const DEFAULT_DELAY = 0;
    private const DEFAULT_ROLLOUT = 'all';
    private const DEFAULT_ROLLOUT_PERCENT = 100;
    private const DEFAULT_AUTO_ROLLBACK = 'enabled';
    private static string $plugin_file = '';
    private static array $remote = [];

    public static function init(): void {
        self::$plugin_file = self::main_plugin_file();
        add_filter( 'pre_set_site_transient_update_plugins', [ __CLASS__, 'check' ] );
        add_filter( 'site_transient_update_plugins', [ __CLASS__, 'check' ] );
        $update_host = (string) wp_parse_url( self::REPOSITORY, PHP_URL_HOST );
        if ( $update_host !== '' ) {
            add_filter( 'update_plugins_' . $update_host, [ __CLASS__, 'native_update' ], 10, 4 );
        }
        add_filter( 'plugins_api', [ __CLASS__, 'information' ], 20, 3 );
        add_filter( 'upgrader_pre_download', [ __CLASS__, 'pre_download' ], 10, 4 );
        add_filter( 'plugin_action_links', [ __CLASS__, 'action_links' ], 10, 2 );
        self::register_settings_client();
        add_action( 'admin_menu', [ __CLASS__, 'license_page' ], 999 );
        add_action( 'admin_init', [ __CLASS__, 'save_license' ] );
        add_filter( 'auto_update_plugin', [ __CLASS__, 'automatic_update_policy' ], 10, 2 );
        add_action( self::AUTO_UPDATE_CRON, [ __CLASS__, 'run_automatic_update' ], 10, 1 );
        add_filter( 'upgrader_pre_install', [ __CLASS__, 'before_install' ], 10, 2 );
        add_action( 'admin_init', [ __CLASS__, 'ensure_watchdog' ], 5 );
        add_action( 'admin_post_okpr_client_rollback_' . md5( self::SLUG ), [ __CLASS__, 'manual_rollback' ] );
    }

    private static function main_plugin_file(): string {
        $dir = dirname( __DIR__ );
        foreach ( glob( $dir . '/*.php' ) ?: [] as $file ) {
            $data = get_file_data( $file, [ 'Name' => 'Plugin Name' ] );
            if ( ! empty( $data['Name'] ) ) return plugin_basename( $file );
        }
        return '';
    }

    private static function license_provider(): string {
        $provider = self::LICENSE_PROVIDER;
        $metadata_file = __DIR__ . '/metadata.json';
        if ( is_file( $metadata_file ) && is_readable( $metadata_file ) ) {
            $raw = file_get_contents( $metadata_file );
            $metadata = is_string( $raw ) ? json_decode( $raw, true ) : null;
            if ( is_array( $metadata ) ) {
                $candidate = sanitize_key( (string) ( $metadata['license_provider'] ?? '' ) );
                if ( in_array( $candidate, [ 'omkrom', 'plugin', 'none' ], true ) ) $provider = $candidate;
            }
        }
        return in_array( $provider, [ 'omkrom', 'plugin', 'none' ], true ) ? $provider : 'none';
    }

    private static function request( string $path, array $args = [] ): array {
        $license = self::license_provider() === 'omkrom' ? (string) get_option( self::LICENSE_OPTION, '' ) : '';
        $query = array_merge( [ 'channel' => self::selected_channel(), 'site_url' => home_url(), 'license_key' => $license ], $args );
        $response = wp_remote_get( self::REPOSITORY . '/wp-json/omkrom/v1/' . ltrim( $path, '/' ) . '?' . http_build_query( $query ), [ 'timeout' => 15 ] );
        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) return [];
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return is_array( $body ) ? $body : [];
    }

    private static function update_data(): array {
        $remote = self::request( 'plugin-update/' . self::SLUG );
        if ( empty( $remote['version'] ) ) {
            $remote = self::request( 'plugin-info/' . self::SLUG );
        }
        return is_array( $remote ) ? $remote : [];
    }

    private static function update_object( array $remote, string $plugin_file ): object {
        return (object) [
            'id' => self::REPOSITORY . '/omkrom-repository/' . self::SLUG,
            'slug' => self::SLUG,
            'plugin' => $plugin_file,
            'version' => (string) ( $remote['version'] ?? '' ),
            'new_version' => (string) ( $remote['version'] ?? '' ),
            'url' => self::REPOSITORY,
            'package' => 'okpr://' . self::SLUG . '/' . self::selected_channel() . '/' . ( $remote['version'] ?? '' ),
            'requires' => $remote['requires'] ?? '',
            'tested' => $remote['tested'] ?? '',
            'requires_php' => $remote['requires_php'] ?? '',
            'icons' => is_array( $remote['icons'] ?? null ) ? $remote['icons'] : [],
        ];
    }

    public static function native_update( $update, array $plugin_data, string $plugin_file, array $locales ) {
        $own_plugin_file = self::$plugin_file !== '' ? self::$plugin_file : self::main_plugin_file();
        if ( $own_plugin_file === '' || plugin_basename( $plugin_file ) !== plugin_basename( $own_plugin_file ) ) {
            return $update;
        }
        $current = (string) ( $plugin_data['Version'] ?? '' );
        if ( $current === '' ) return $update;
        $remote = self::update_data();
        if ( empty( $remote['version'] ) ) return $update;
        self::$remote = $remote;
        if ( version_compare( (string) $remote['version'], $current, '>' ) ) {
            return (array) self::update_object( $remote, $plugin_file );
        }
        return [
            'id' => self::REPOSITORY . '/omkrom-repository/' . self::SLUG,
            'slug' => self::SLUG,
            'plugin' => $plugin_file,
            'version' => $current,
            'new_version' => $current,
            'url' => self::REPOSITORY,
            'package' => '',
        ];
    }

    public static function check( $transient ) {
        if ( ! is_object( $transient ) ) $transient = new stdClass();
        if ( ! isset( $transient->checked ) || ! is_array( $transient->checked ) ) $transient->checked = [];
        if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) $transient->response = [];
        if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) $transient->no_update = [];

        $plugin_file = self::main_plugin_file();
        if ( ! $plugin_file ) return $transient;

        $current = (string) ( $transient->checked[$plugin_file] ?? '' );
        if ( $current === '' ) {
            $absolute = WP_PLUGIN_DIR . '/' . $plugin_file;
            if ( is_file( $absolute ) ) {
                $headers = get_file_data( $absolute, [ 'Version' => 'Version' ] );
                $current = (string) ( $headers['Version'] ?? '' );
                if ( $current !== '' ) $transient->checked[$plugin_file] = $current;
            }
        }
        if ( $current === '' ) return $transient;

        $remote = self::update_data();
        if ( empty( $remote['version'] ) ) return $transient;
        self::$remote = $remote;
        if ( version_compare( $remote['version'], $current, '>' ) ) {
            $transient->response[$plugin_file] = self::update_object( $remote, $plugin_file );

            if ( self::automatic_updates_enabled() && ! wp_next_scheduled( self::AUTO_UPDATE_CRON, [ $plugin_file ] ) ) {
                $delay = min(720, absint(get_option(self::DELAY_OPTION, self::DEFAULT_DELAY))) * HOUR_IN_SECONDS;
                $published = strtotime((string)($remote['last_updated'] ?? '')) ?: time();
                wp_schedule_single_event(max(time() + 60, $published + $delay), self::AUTO_UPDATE_CRON, [ $plugin_file ] );
            }
        }
        return $transient;
    }

    public static function information( $result, string $action, $args ) {
        if ( $action !== 'plugin_information' || empty( $args->slug ) || $args->slug !== self::SLUG ) return $result;
        $remote = self::request( 'plugin-info/' . self::SLUG );
        if ( ! $remote ) return $result;
        return (object) [
            'name' => $remote['name'] ?? self::SLUG,
            'slug' => self::SLUG,
            'version' => $remote['version'] ?? '',
            'requires' => $remote['requires'] ?? '',
            'tested' => $remote['tested'] ?? '',
            'requires_php' => $remote['requires_php'] ?? '',
            'last_updated' => $remote['last_updated'] ?? '',
            'sections' => $remote['sections'] ?? [],
            'icons' => is_array( $remote['icons'] ?? null ) ? $remote['icons'] : [],
            'download_link' => 'okpr://' . self::SLUG . '/' . self::CHANNEL . '/' . ( $remote['version'] ?? '' ),
        ];
    }

    public static function pre_download( $reply, string $package, $upgrader, array $hook_extra ) {
        if ( strpos( $package, 'okpr://' . self::SLUG . '/' ) !== 0 ) return $reply;
        $license = (string) get_option( self::LICENSE_OPTION, '' );
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $last_error = null;
        for ( $attempt = 1; $attempt <= 3; $attempt++ ) {
            $response = wp_remote_post( self::REPOSITORY . '/wp-json/omkrom/v1/plugin-download-authorize/' . self::SLUG, [
                'timeout' => 30,
                'redirection' => 3,
                'body' => [ 'channel' => self::selected_channel(), 'site_url' => home_url(), 'license_key' => $license ],
            ] );
            if ( is_wp_error( $response ) ) { $last_error = $response; continue; }
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            $status = wp_remote_retrieve_response_code( $response );
            if ( $status !== 200 || ! is_array( $data ) || empty( $data['download_url'] ) ) {
                $last_error = new WP_Error( 'okpr_download_denied', is_array($data) ? ($data['message'] ?? $data['error'] ?? 'El repositorio no autorizó la descarga.') : 'Respuesta de autorización no válida.' );
                continue;
            }
            $temporary = download_url( esc_url_raw( $data['download_url'] ), 300 );
            if ( is_wp_error( $temporary ) ) { $last_error = $temporary; continue; }
            $expected = strtolower( sanitize_text_field( (string) ( $data['sha256'] ?? '' ) ) );
            $actual = hash_file( 'sha256', $temporary ) ?: '';
            if ( $expected !== '' && ( $actual === '' || ! hash_equals( $expected, strtolower( $actual ) ) ) ) {
                @unlink( $temporary );
                $last_error = new WP_Error( 'okpr_download_hash_mismatch', 'El ZIP descargado no coincide con el SHA-256 autorizado.' );
                continue;
            }
            return $temporary;
        }
        return $last_error instanceof WP_Error ? $last_error : new WP_Error( 'okpr_download_failed', 'No se pudo descargar una copia válida del ZIP.' );
    }

    private static function selected_channel(): string {
        $channel = sanitize_key((string) get_option(self::CHANNEL_OPTION, self::CHANNEL));
        if ($channel === 'beta' && ! self::CHANNEL_BETA_ALLOWED) return 'stable';
        if ($channel === 'alpha' && ! self::CHANNEL_ALPHA_ALLOWED) return 'stable';
        return in_array($channel, ['stable','beta','alpha'], true) ? $channel : 'stable';
    }

    private static function rollout_eligible(): bool {
        $mode = sanitize_key((string) get_option(self::ROLLOUT_OPTION, self::DEFAULT_ROLLOUT));
        if ($mode === 'staging') return function_exists('wp_get_environment_type') && wp_get_environment_type() !== 'production';
        if ($mode === 'gradual') {
            $percent = max(1, min(100, absint(get_option(self::ROLLOUT_PERCENT_OPTION, self::DEFAULT_ROLLOUT_PERCENT))));
            $bucket = abs(crc32(strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST)) . '|' . self::SLUG)) % 100;
            return $bucket < $percent;
        }
        return true;
    }

    private static function automatic_updates_enabled(): bool {
        if ( ! self::AUTOMATIC_UPDATES ) return false;
        return get_option( self::AUTO_UPDATE_OPTION, self::DEFAULT_AUTO_UPDATES ) !== 'disabled' && self::rollout_eligible();
    }

    public static function automatic_update_policy( bool $update, $item ): bool {
        $plugin = is_object($item) ? (string)($item->plugin ?? '') : '';
        return $plugin === self::main_plugin_file() ? self::automatic_updates_enabled() : $update;
    }

    public static function ensure_watchdog(): void {
        if ( ! self::SAFE_UPDATES || ! defined( 'WPMU_PLUGIN_DIR' ) ) return;
        if ( ! is_dir( WPMU_PLUGIN_DIR ) && ! wp_mkdir_p( WPMU_PLUGIN_DIR ) ) return;
        $source = __DIR__ . '/recovery.php';
        $target = trailingslashit( WPMU_PLUGIN_DIR ) . 'omkrom-safe-update.php';
        if ( ! is_file( $source ) ) return;
        $source_hash = hash_file( 'sha256', $source );
        $target_hash = is_file( $target ) ? hash_file( 'sha256', $target ) : '';
        if ( ! $target_hash || ! hash_equals( (string) $source_hash, (string) $target_hash ) ) @copy( $source, $target );
        self::register_installation();
    }

    private static function register_installation(): void {
        if ( ! self::SAFE_UPDATES ) return;
        $secret = (string) get_option( 'okpr_recovery_secret', '' );
        if ( $secret === '' ) { $secret = wp_generate_password( 48, false, false ); update_option( 'okpr_recovery_secret', $secret, false ); }
        $last = (int) get_option( 'okpr_recovery_registered_' . md5( self::SLUG ), 0 );
        if ( time() - $last < DAY_IN_SECONDS ) return;
        $main = WP_PLUGIN_DIR . '/' . self::main_plugin_file(); $headers = is_file( $main ) ? get_file_data( $main, [ 'Version' => 'Version' ] ) : [];
        $response = wp_remote_post( self::REPOSITORY . '/wp-json/omkrom/v2/installations/register', [ 'timeout' => 10, 'body' => [ 'slug' => self::SLUG, 'site_url' => home_url(), 'version' => $headers['Version'] ?? '', 'license_key' => self::license_provider() === 'omkrom' ? (string) get_option( self::LICENSE_OPTION, '' ) : '', 'recovery_url' => rest_url( 'okpr-recovery/v1/rollback' ), 'recovery_token' => $secret ] ] );
        if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 300 ) update_option( 'okpr_recovery_registered_' . md5( self::SLUG ), time(), false );
    }

    public static function before_install( $response, array $hook_extra ) {
        $plugin = sanitize_text_field( (string) ( $hook_extra['plugin'] ?? '' ) );
        if ( ! self::SAFE_UPDATES || $plugin !== self::main_plugin_file() ) return $response;
        $updates = get_site_transient( 'update_plugins' );
        $target = is_object( $updates ) && ! empty( $updates->response[ $plugin ]->new_version ) ? (string) $updates->response[ $plugin ]->new_version : '';
        $backup = self::create_backup( $plugin, $target );
        return is_wp_error( $backup ) ? $backup : $response;
    }

    private static function create_backup( string $plugin_file, string $target_version = '' ) {
        self::ensure_watchdog();
        $source = WP_PLUGIN_DIR . '/' . dirname( $plugin_file );
        if ( dirname( $plugin_file ) === '.' ) $source = WP_PLUGIN_DIR . '/' . basename( $plugin_file );
        if ( ! file_exists( $source ) ) return new WP_Error( 'okpr_backup_source', 'No se encontró la versión instalada para crear el backup.' );
        $main = WP_PLUGIN_DIR . '/' . $plugin_file;
        $headers = is_file( $main ) ? get_file_data( $main, [ 'Version' => 'Version' ] ) : [];
        $current = sanitize_text_field( $headers['Version'] ?? '' );
        $slug = sanitize_key( dirname( $plugin_file ) === '.' ? basename( $plugin_file, '.php' ) : dirname( $plugin_file ) );
        $dest = trailingslashit( WP_CONTENT_DIR ) . 'omkrom-recovery/backups/' . $slug . '/' . sanitize_file_name( ( $current ?: 'unknown' ) . '-' . gmdate( 'Ymd-His' ) );
        if ( ! wp_mkdir_p( $dest ) ) return new WP_Error( 'okpr_backup_dir', 'No se pudo crear el directorio de recuperación.' );
        if ( ! self::copy_tree( $source, $dest ) ) return new WP_Error( 'okpr_backup_copy', 'No se pudo completar el backup previo.' );
        $entry = [ 'plugin_file' => $plugin_file, 'plugin_dir' => wp_normalize_path( $source ), 'backup_dir' => wp_normalize_path( $dest ), 'from_version' => $current, 'to_version' => sanitize_text_field( $target_version ), 'created_at' => time(), 'active' => is_plugin_active( $plugin_file ) ];
        $pending = get_option( 'okpr_recovery_pending', [] ); if ( ! is_array( $pending ) ) $pending = []; $pending[ $plugin_file ] = $entry; update_option( 'okpr_recovery_pending', $pending, false );
        $history = get_option( 'okpr_recovery_backups', [] ); if ( ! is_array( $history ) ) $history = []; $history[ $plugin_file ] = array_slice( array_merge( [ $entry ], $history[ $plugin_file ] ?? [] ), 0, 3 ); update_option( 'okpr_recovery_backups', $history, false );
        return true;
    }

    private static function copy_tree( string $source, string $dest ): bool {
        if ( is_file( $source ) ) return @copy( $source, trailingslashit( $dest ) . basename( $source ) );
        try {
            $it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::SELF_FIRST );
            foreach ( $it as $item ) {
                $relative = ltrim( str_replace( wp_normalize_path( $source ), '', wp_normalize_path( $item->getPathname() ) ), '/' );
                $target = trailingslashit( $dest ) . $relative;
                if ( $item->isDir() ) { if ( ! is_dir( $target ) && ! wp_mkdir_p( $target ) ) return false; }
                elseif ( ! @copy( $item->getPathname(), $target ) ) return false;
            }
        } catch ( Throwable $e ) { return false; }
        return true;
    }

    public static function manual_rollback(): void {
        if ( ! self::ROLLBACK_ALLOWED || ! current_user_can( 'activate_plugins' ) ) wp_die( 'No autorizado.' );
        check_admin_referer( 'okpr_client_rollback_' . self::SLUG );
        self::ensure_watchdog();
        $plugin = self::main_plugin_file();
        $history = get_option( 'okpr_recovery_backups', [] );
        $entry = $history[ $plugin ][0] ?? null;
        if ( ! is_array( $entry ) ) wp_die( 'No existe ningún punto de restauración.' );
        $pending = get_option( 'okpr_recovery_pending', [] ); if ( ! is_array( $pending ) ) $pending = []; $entry['force_restore'] = true; $pending[ $plugin ] = $entry; update_option( 'okpr_recovery_pending', $pending, false );
        wp_safe_redirect( add_query_arg( 'okpr_rollback_scheduled', '1', admin_url( 'plugins.php' ) ) ); exit;
    }

    public static function run_automatic_update( string $plugin_file = '' ): void {
        if ( ! self::automatic_updates_enabled() ) return;
        $plugin_file = $plugin_file ?: self::main_plugin_file();
        if ( ! $plugin_file ) return;

        $lock = 'okpr_auto_upgrade_lock_' . md5( self::SLUG );
        if ( get_transient( $lock ) ) return;
        set_transient( $lock, 1, 15 * MINUTE_IN_SECONDS );

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        wp_clean_plugins_cache( true );
        delete_site_transient( 'update_plugins' );
        wp_update_plugins();

        $updates = get_site_transient( 'update_plugins' );
        if ( ! is_object( $updates ) || empty( $updates->response[ $plugin_file ] ) ) {
            delete_transient( $lock );
            return;
        }

        $skin = new Automatic_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader( $skin );
        $upgrader->upgrade( $plugin_file );
        delete_transient( $lock );
        delete_site_transient( 'update_plugins' );
    }

    private static function register_settings_client(): void {
        if ( ! isset( $GLOBALS['okpr_client_settings_registry'] ) || ! is_array( $GLOBALS['okpr_client_settings_registry'] ) ) {
            $GLOBALS['okpr_client_settings_registry'] = [];
        }
        $GLOBALS['okpr_client_settings_registry'][ self::SLUG ] = __CLASS__;
    }

    private static function display_name(): string {
        $file = self::$plugin_file !== '' ? self::$plugin_file : self::main_plugin_file();
        if ( $file ) {
            $absolute = WP_PLUGIN_DIR . '/' . $file;
            if ( is_file( $absolute ) ) {
                $headers = get_file_data( $absolute, [ 'Name' => 'Plugin Name' ] );
                if ( ! empty( $headers['Name'] ) ) return (string) $headers['Name'];
            }
        }
        return ucwords( str_replace( [ '-', '_' ], ' ', self::SLUG ) );
    }

    public static function action_links( array $links, string $plugin_file ): array {
        if ( $plugin_file !== self::main_plugin_file() ) return $links;
        $url = admin_url( 'options-general.php?page=omkrom-updates#okpr-client-' . substr( md5( self::SLUG ), 0, 10 ) );
        array_unshift( $links, '<a href="' . esc_url( $url ) . '">Omkrom</a>' );
        return $links;
    }

    public static function license_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        global $submenu;
        if ( ! empty( $submenu['options-general.php'] ) && is_array( $submenu['options-general.php'] ) ) {
            foreach ( $submenu['options-general.php'] as $index => $item ) {
                $slug = (string) ( $item[2] ?? '' );
                if ( str_starts_with( $slug, 'okpr-license-' ) ) unset( $submenu['options-general.php'][ $index ] );
            }
        }
        if ( ! empty( $GLOBALS['okpr_client_settings_page_registered'] ) ) return;
        $GLOBALS['okpr_client_settings_page_registered'] = true;
        add_options_page( 'Actualizaciones Omkrom Repository', 'Actualizaciones Omkrom Repository', 'manage_options', 'omkrom-updates', [ __CLASS__, 'render_license' ] );
    }

    public static function save_license(): void {
        if ( empty( $_POST['okpr_client_action'] ) || $_POST['okpr_client_action'] !== self::SLUG ) return;
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'okpr_client_' . self::SLUG ) ) return;
        if ( self::license_provider() === 'omkrom' && array_key_exists( 'license_key', $_POST ) ) {
            update_option( self::LICENSE_OPTION, sanitize_text_field( wp_unslash( $_POST['license_key'] ) ), false );
        }
        if ( self::AUTOMATIC_UPDATES ) {
            update_option( self::AUTO_UPDATE_OPTION, ( isset( $_POST['automatic_updates'] ) && $_POST['automatic_updates'] === 'disabled' ) ? 'disabled' : 'enabled', false );
            $channel=sanitize_key($_POST['update_channel']??'stable'); if($channel==='beta'&&!self::CHANNEL_BETA_ALLOWED)$channel='stable'; if($channel==='alpha'&&!self::CHANNEL_ALPHA_ALLOWED)$channel='stable'; update_option(self::CHANNEL_OPTION,in_array($channel,['stable','beta','alpha'],true)?$channel:'stable',false);
            update_option(self::DELAY_OPTION,min(720,absint($_POST['update_delay']??0)),false);
            $rollout=sanitize_key($_POST['rollout_mode']??'all'); update_option(self::ROLLOUT_OPTION,in_array($rollout,['all','staging','gradual'],true)?$rollout:'all',false);
            $pct=absint($_POST['rollout_percent']??100); update_option(self::ROLLOUT_PERCENT_OPTION,in_array($pct,[1,5,10,25,50,100],true)?$pct:100,false);
            update_option(self::AUTO_ROLLBACK_OPTION,(self::ROLLBACK_ALLOWED&&!empty($_POST['auto_rollback']))?'enabled':'disabled',false);
            wp_clear_scheduled_hook(self::AUTO_UPDATE_CRON);
        }
        delete_site_transient( 'update_plugins' );
        wp_safe_redirect( add_query_arg( [ 'page' => 'omkrom-updates', 'updated' => 1 ], admin_url( 'options-general.php' ) ) . '#okpr-client-' . substr( md5( self::SLUG ), 0, 10 ) );
        exit;
    }

    public static function render_settings_card(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $name = self::display_name();
        $id = 'okpr-client-' . substr( md5( self::SLUG ), 0, 10 );
        echo '<section class="okpr-hub-card" id="' . esc_attr( $id ) . '"><div class="okpr-hub-card-head"><div class="okpr-hub-icon"><span class="dashicons dashicons-admin-plugins"></span></div><div><h2>' . esc_html( $name ) . '</h2><code>' . esc_html( self::SLUG ) . '</code></div><span class="okpr-hub-status">Conectado</span></div>';
        echo '<form method="post" class="okpr-hub-form">';
        wp_nonce_field( 'okpr_client_' . self::SLUG );
        echo '<input type="hidden" name="okpr_client_action" value="' . esc_attr( self::SLUG ) . '">';
        if ( self::license_provider() === 'omkrom' ) {
            echo '<div class="okpr-hub-field"><div><label for="okpr-license-' . esc_attr( md5( self::SLUG ) ) . '">Licencia Omkrom</label><p>Clave utilizada por Omkrom Hub para validar descargas y capacidades de este producto.</p></div><input type="text" id="okpr-license-' . esc_attr( md5( self::SLUG ) ) . '" name="license_key" value="' . esc_attr( get_option( self::LICENSE_OPTION, '' ) ) . '" placeholder="Clave de licencia"></div>';
        }
        if ( self::AUTOMATIC_UPDATES ) {
            $mode = get_option( self::AUTO_UPDATE_OPTION, self::DEFAULT_AUTO_UPDATES ); $channel=get_option(self::CHANNEL_OPTION,'stable'); $delay=absint(get_option(self::DELAY_OPTION,0)); $rollout=get_option(self::ROLLOUT_OPTION,'all'); $pct=absint(get_option(self::ROLLOUT_PERCENT_OPTION,100)); $rollback=get_option(self::AUTO_ROLLBACK_OPTION,'enabled');
            echo '<div class="okpr-hub-field"><div><label>Canal de actualizaciones</label><p>Beta también recibe cualquier Stable posterior; Alpha incluye Alpha, Beta y Stable.</p></div><div class="okpr-hub-segmented"><label><input type="radio" name="update_channel" value="stable" '.checked($channel,'stable',false).'><span>Stable</span></label><label class="'.(!self::CHANNEL_BETA_ALLOWED?'is-locked':'').'"><input type="radio" name="update_channel" value="beta" '.checked($channel,'beta',false).' '.disabled(!self::CHANNEL_BETA_ALLOWED,true,false).'><span>Beta</span></label><label class="'.(!self::CHANNEL_ALPHA_ALLOWED?'is-locked':'').'"><input type="radio" name="update_channel" value="alpha" '.checked($channel,'alpha',false).' '.disabled(!self::CHANNEL_ALPHA_ALLOWED,true,false).'><span>Alpha</span></label></div></div>';
            echo '<div class="okpr-hub-field"><div><label>Instalación</label><p>Decide si WordPress debe instalar automáticamente las nuevas versiones compatibles.</p></div><select name="automatic_updates"><option value="enabled" '.selected($mode,'enabled',false).'>Automática</option><option value="disabled" '.selected($mode,'disabled',false).'>Solo avisar</option></select></div>';
            echo '<div class="okpr-hub-field"><div><label>Retraso</label><p>Espera antes de instalar una release nueva.</p></div><select name="update_delay"><option value="0" '.selected($delay,0,false).'>Inmediatamente</option><option value="24" '.selected($delay,24,false).'>24 horas</option><option value="72" '.selected($delay,72,false).'>3 días</option><option value="168" '.selected($delay,168,false).'>7 días</option><option value="336" '.selected($delay,336,false).'>14 días</option></select></div>';
            // El despliegue lo controla exclusivamente Omkrom Plugin Repository.
            echo '<input type="hidden" name="rollout_mode" value="' . esc_attr( $rollout ) . '">';
            echo '<input type="hidden" name="rollout_percent" value="' . esc_attr( (string) $pct ) . '">';
            if(self::ROLLBACK_ALLOWED)echo '<div class="okpr-hub-field"><div><label>Protección</label><p>Restaura la copia anterior si una actualización provoca un error crítico.</p></div><label class="okpr-hub-switch"><input type="checkbox" name="auto_rollback" value="enabled" '.checked($rollback,'enabled',false).'><span></span><b>Rollback automático</b></label></div>';
        } else {
            echo '<div class="okpr-hub-note">Las actualizaciones automáticas no están disponibles para este producto o plan.</div>';
        }
        echo '<div class="okpr-hub-actions"><button type="submit" class="button button-primary">Guardar cambios</button></div></form></section>';
    }

    public static function render_license(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $registry = $GLOBALS['okpr_client_settings_registry'] ?? [];
        if ( ! is_array( $registry ) ) $registry = [];
        ksort( $registry, SORT_NATURAL | SORT_FLAG_CASE );
        echo '<div class="wrap okpr-hub-settings"><div class="okpr-hub-hero"><div><span class="okpr-hub-eyebrow">OMKROM</span><h1>Actualizaciones y licencias</h1><p>Un único lugar para gestionar todos los plugins distribuidos mediante Omkrom Plugin Repository.</p></div><div class="okpr-hub-count"><strong>' . count( $registry ) . '</strong><span>plugins conectados</span></div></div>';
        if ( ! empty( $_GET['updated'] ) ) echo '<div class="notice notice-success is-dismissible"><p>Cambios guardados correctamente.</p></div>';
        echo '<style>
        .okpr-hub-settings{max-width:1180px;margin-top:28px;color:#172033}.okpr-hub-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin:0 0 22px}.okpr-hub-eyebrow{font-size:11px;letter-spacing:.18em;font-weight:700;color:#64748b}.okpr-hub-hero h1{font-size:30px;line-height:1.15;margin:5px 0 7px;color:#0f172a;font-weight:650}.okpr-hub-hero p{margin:0;color:#64748b;font-size:14px}.okpr-hub-count{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:14px 18px;min-width:126px;text-align:center;box-shadow:0 8px 26px rgba(15,23,42,.04)}.okpr-hub-count strong{font-size:22px;display:block;color:#0f172a}.okpr-hub-count span{font-size:11px;color:#64748b}.okpr-hub-card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;margin:0 0 18px;box-shadow:0 8px 30px rgba(15,23,42,.035);overflow:hidden}.okpr-hub-card:target{border-color:#93c5fd;box-shadow:0 0 0 3px rgba(59,130,246,.08)}.okpr-hub-card-head{display:flex;align-items:center;gap:13px;padding:18px 20px;border-bottom:1px solid #eef2f7}.okpr-hub-icon{width:40px;height:40px;border-radius:12px;background:#f1f5f9;display:grid;place-items:center;color:#334155}.okpr-hub-card-head h2{font-size:16px;margin:0 0 2px;color:#0f172a}.okpr-hub-card-head code{background:none;padding:0;color:#94a3b8;font-size:11px}.okpr-hub-status{margin-left:auto;background:#ecfdf5;color:#047857;border:1px solid #d1fae5;border-radius:999px;padding:5px 9px;font-size:11px;font-weight:600}.okpr-hub-form{padding:2px 20px 18px}.okpr-hub-field{display:grid;grid-template-columns:minmax(210px,.85fr) minmax(300px,1.45fr);gap:24px;align-items:center;padding:16px 0;border-bottom:1px solid #f1f5f9}.okpr-hub-field label{font-weight:600;color:#1e293b}.okpr-hub-field p{margin:4px 0 0;color:#64748b;font-size:12px;line-height:1.45}.okpr-hub-field input[type=text],.okpr-hub-field select{width:100%;max-width:440px;min-height:38px;border:1px solid #dbe2ea;border-radius:9px;padding:0 11px;background:#fff;box-shadow:none}.okpr-hub-inline{display:flex;gap:8px;max-width:440px}.okpr-hub-segmented{display:flex;gap:7px;flex-wrap:wrap}.okpr-hub-segmented input{position:absolute;opacity:0;pointer-events:none}.okpr-hub-segmented span{display:block;padding:8px 13px;border:1px solid #dbe2ea;border-radius:9px;background:#fff;font-weight:600;font-size:12px;color:#475569;cursor:pointer}.okpr-hub-segmented input:checked+span{background:#eff6ff;border-color:#93c5fd;color:#1d4ed8;box-shadow:inset 0 0 0 1px #bfdbfe}.okpr-hub-segmented .is-locked span{opacity:.42;cursor:not-allowed}.okpr-hub-switch{display:flex;align-items:center;gap:9px}.okpr-hub-switch input{display:none}.okpr-hub-switch span{width:36px;height:20px;border-radius:999px;background:#cbd5e1;position:relative;transition:.2s}.okpr-hub-switch span:after{content:"";position:absolute;width:14px;height:14px;border-radius:50%;background:#fff;left:3px;top:3px;box-shadow:0 1px 3px rgba(0,0,0,.22);transition:.2s}.okpr-hub-switch input:checked+span{background:#2563eb}.okpr-hub-switch input:checked+span:after{transform:translateX(16px)}.okpr-hub-switch b{font-size:12px;color:#334155}.okpr-hub-actions{display:flex;justify-content:flex-end;padding-top:16px}.okpr-hub-actions .button-primary{border-radius:9px;padding:3px 14px;min-height:36px}.okpr-hub-note{margin:16px 0;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;color:#64748b}@media(max-width:782px){.okpr-hub-hero{align-items:flex-start;flex-direction:column}.okpr-hub-count{display:none}.okpr-hub-field{grid-template-columns:1fr;gap:9px}.okpr-hub-inline{flex-direction:column}.okpr-hub-field input[type=text],.okpr-hub-field select{max-width:none}}
        </style>';
        if ( ! $registry ) echo '<div class="okpr-hub-card"><div class="okpr-hub-note">No se han detectado plugins conectados a Omkrom Plugin Repository.</div></div>';
        foreach ( $registry as $class ) {
            if ( is_string( $class ) && class_exists( $class ) && is_callable( [ $class, 'render_settings_card' ] ) ) call_user_func( [ $class, 'render_settings_card' ] );
        }
        echo '</div>';
    }

}
OKPR_Client_68e2cc37e73d::init();
}