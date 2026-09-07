<?php
/**
 * Mi perfil - Tomatito Enfocado
 *
 * Primera funcionalidad añadida después de la distribución oficial 1.0.0.
 * Usa el usuario WordPress ya autenticado; no crea un sistema de usuarios propio.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dados da licença exibidos no perfil.
 *
 * A licença é fornecida pelo SDK público do Omkrom. O produto distribuído
 * como Tomatito tem a chave de produto "focus-tomatito" no Omkrom Hub; o filtro
 * permite alterá-la sem acoplar a página a detalhes da instalação.
 */
if ( ! function_exists( 'tomatito_get_license_info' ) ) {
function tomatito_get_license_info( $user_id ) {
    $info = array(
        'plan'                 => '—',
        'status'               => 'No disponible',
        'license_key'          => '—',
        'expires'              => '—',
        'pomodoros_limit'      => null,
        'temporizadores_limit' => null,
        'alarmas_limit'        => null,
    );

    $product_key = sanitize_key( (string) apply_filters( 'tomatito_omkrom_product_key', 'focus-tomatito', $user_id ) );

    /*
     * ohc_get_license() é o helper oficial: lê o bundle OAuth já sincronizado
     * para este utilizador e devolve apenas a licença deste produto.
     */
    if ( $product_key && function_exists( 'ohc_get_license' ) ) {
        $license = ohc_get_license( $product_key, $user_id );

        if ( is_array( $license ) ) {
            $raw = isset( $license['raw'] ) && is_array( $license['raw'] ) ? $license['raw'] : array();
            $status = sanitize_key( (string) ( $license['status'] ?? '' ) );
            $status_labels = array(
                'active'   => 'Activa',
                'valid'    => 'Activa',
                'trial'    => 'Periodo de prueba',
                'inactive' => 'Inactiva',
                'expired'  => 'Caducada',
                'revoked'  => 'Revocada',
            );
            $expires_at = (string) ( $license['expires_at'] ?? '' );

            $info = array_merge( $info, array(
                'plan'                 => tomatito_omkrom_plan_label( (string) ( $license['plan_key'] ?? '' ) ),
                'status'               => $status_labels[ $status ] ?? ( $status ? ucfirst( $status ) : ( ! empty( $license['active'] ) ? 'Activa' : 'No disponible' ) ),
                'license_key'          => (string) ( $license['license_key'] ?? '—' ) ?: '—',
                'expires'              => tomatito_omkrom_expiry_label( $expires_at ),
                'pomodoros_limit'      => tomatito_omkrom_license_limit( $raw, array( 'pomodoros', 'pomodoros_active', 'active_pomodoros', 'max_pomodoros' ) ),
                'temporizadores_limit' => tomatito_omkrom_license_limit( $raw, array( 'temporizadores', 'timers', 'timers_active', 'active_timers', 'max_timers' ) ),
                'alarmas_limit'        => tomatito_omkrom_license_limit( $raw, array( 'alarmas', 'alarms', 'max_alarmas', 'max_alarms' ) ),
            ) );
        }
    }

    /* Mantém a extensão prevista na versão anterior para integrações futuras. */
    $info = apply_filters( 'tomatito_license_info', $info, $user_id );

    return is_array( $info ) ? wp_parse_args( $info, array(
        'plan'                 => '—',
        'status'               => 'No disponible',
        'license_key'          => '—',
        'expires'              => '—',
        'pomodoros_limit'      => null,
        'temporizadores_limit' => null,
        'alarmas_limit'        => null,
    ) ) : array();
}
}

/** Converte a chave técnica do plano numa etiqueta legível, sem inventar planos. */
if ( ! function_exists( 'tomatito_omkrom_plan_label' ) ) {
function tomatito_omkrom_plan_label( $plan_key ) {
    $plan_key = sanitize_key( $plan_key );
    return $plan_key ? ucwords( str_replace( array( '-', '_' ), ' ', $plan_key ) ) : '—';
}
}

/** Apresenta o vencimento que veio do Hub; uma licença sem data não é assumida como perpétua. */
if ( ! function_exists( 'tomatito_omkrom_expiry_label' ) ) {
function tomatito_omkrom_expiry_label( $expires_at ) {
    $expires_at = sanitize_text_field( $expires_at );
    if ( '' === $expires_at ) {
        return 'Sin fecha de caducidad indicada';
    }

    $timestamp = strtotime( $expires_at );
    return $timestamp ? sprintf( 'Caduca: %s', wp_date( get_option( 'date_format' ), $timestamp ) ) : $expires_at;
}
}

/**
 * Lê quotas opcionais do bundle original. O SDK normaliza capacidades, mas
 * preserva o bundle em "raw" justamente para que cada App leia os seus
 * próprios limites. Se Omkrom não enviar uma quota, devolvemos null em vez de
 * assumir um número.
 */
if ( ! function_exists( 'tomatito_omkrom_license_limit' ) ) {
function tomatito_omkrom_license_limit( array $raw, array $keys ) {
    $containers = array( $raw );
    foreach ( array( 'limits', 'usage_limits', 'quotas', 'resources' ) as $container_key ) {
        if ( isset( $raw[ $container_key ] ) && is_array( $raw[ $container_key ] ) ) {
            $containers[] = $raw[ $container_key ];
        }
    }

    foreach ( $containers as $container ) {
        foreach ( $keys as $key ) {
            if ( ! array_key_exists( $key, $container ) ) {
                continue;
            }
            $value = $container[ $key ];
            if ( is_array( $value ) ) {
                foreach ( array( 'limit', 'max', 'value' ) as $value_key ) {
                    if ( array_key_exists( $value_key, $value ) ) {
                        $value = $value[ $value_key ];
                        break;
                    }
                }
            }
            if ( is_string( $value ) && in_array( strtolower( $value ), array( 'unlimited', 'infinite', 'ilimitado' ), true ) ) {
                return 'Ilimitados';
            }
            if ( is_numeric( $value ) ) {
                return (int) $value < 0 ? 'Ilimitados' : (int) $value;
            }
        }
    }

    return null;
}
}

if ( ! function_exists( 'tomatito_mi_perfil_shortcode' ) ) {
function tomatito_mi_perfil_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p>Debes iniciar sesión.</p>';
    }

    $user_id       = get_current_user_id();
    $user          = wp_get_current_user();
    $display_name  = (string) $user->display_name;
    $first_name    = (string) $user->first_name;
    $last_name     = (string) $user->last_name;
    $email         = (string) $user->user_email;
    $birth_date    = (string) get_user_meta( $user_id, 'tomatito_fecha_nacimiento', true );
    $timezone      = (string) get_user_meta( $user_id, 'tomatito_zona_horaria', true );
    $license_info = tomatito_get_license_info( $user_id );

    if ( ! $timezone ) {
        $timezone = wp_timezone_string();
    }

    $custom_avatar = (string) get_user_meta( $user_id, 'tomatito_avatar_url', true );
    $avatar_url = $custom_avatar ? $custom_avatar : get_avatar_url(
        $user_id,
        array(
            'size' => 160,
            'default' => 'mystery',
        )
    );

    $logo_url = content_url( 'uploads/2026/08/tomatito-icon.png' );

    ob_start();
    ?>
    <style>
    /* ===== Reset del template WordPress: igual que Ajustes/Pomodoros ===== */
    .wp-site-blocks,
    .wp-block-group,
    .wp-block-post-content,
    main,
    .entry-content {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .entry-title,
    h1.wp-block-post-title {
        display: none !important;
    }

    /* ===== Mi perfil: mismo shell y convenciones visuales de Tomatito ===== */
    .tomatito-mi-perfil-page {
        width: 100%;
    }

    /* La página de WordPress debe comportarse como las páginas nativas de Tomatito. */
    .tomatito-mi-perfil-page .tomatito-content {
        padding-left: 36px;
        padding-right: 36px;
    }

    .tomatito-mi-perfil-page .tomatito-header {
        display: flex;
        align-items: center;
        height: 80px;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 20px;
        padding: 0;
    }

    .tomatito-mi-perfil-page .tomatito-header h1 {
        font-size: 28px;
        font-weight: 600;
        margin: 0;
    }

    /* Perfil: reutiliza exactamente el patrón visual de Ajustes. */
    .tomatito-mi-perfil-page .perfil-container {
        max-width: 100%;
        padding-bottom: 40px;
    }

    .tomatito-mi-perfil-page .perfil-section {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 16px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.04);
    }

    .tomatito-mi-perfil-page .perfil-section h2 {
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .tomatito-mi-perfil-page .perfil-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .tomatito-mi-perfil-page .perfil-row:last-child {
        border-bottom: none;
    }

    .tomatito-mi-perfil-page .perfil-label {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }

    .tomatito-mi-perfil-page .perfil-label .title {
        font-size: 14px;
        font-weight: 500;
        color: #111827;
    }

    .tomatito-mi-perfil-page .perfil-label .desc {
        font-size: 13px;
        color: #6b7280;
    }

    .tomatito-mi-perfil-page .perfil-control {
        min-width: 240px;
        max-width: 360px;
        flex: 0 1 360px;
    }

    .tomatito-mi-perfil-page .perfil-control input {
        width: 100%;
        box-sizing: border-box;
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        background: #fff;
        color: #111827;
    }

    .tomatito-mi-perfil-page .perfil-control input:focus {
        outline: none;
        border-color: #ff4d4d;
    }

    .tomatito-mi-perfil-page .perfil-control input[readonly] {
        background: #f9fafb;
        color: #6b7280;
    }

    .tomatito-mi-perfil-page .avatar-row {
        align-items: center;
    }

    .tomatito-mi-perfil-page .avatar-control {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 240px;
        max-width: 360px;
        flex: 0 1 360px;
    }

    .tomatito-mi-perfil-page .avatar-image {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
    }

    .tomatito-mi-perfil-page .avatar-file {
        display: none;
    }

    .tomatito-mi-perfil-page .avatar-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 9px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        color: #111827;
        font-size: 13px;
        font-family: inherit;
        cursor: pointer;
    }

    .tomatito-mi-perfil-page .avatar-button:hover {
        border-color: #ff4d4d;
    }

    .tomatito-mi-perfil-page .perfil-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 24px;
    }

    .tomatito-mi-perfil-page .btn-primary {
        background: #ff4d4d;
        color: #fff;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
    }

    .tomatito-mi-perfil-page .btn-primary:hover {
        background: #ff3333;
    }

    .tomatito-mi-perfil-page .btn-primary:disabled {
        opacity: 0.6;
        cursor: wait;
    }

    .tomatito-mi-perfil-page .perfil-message {
        display: none;
        margin-top: 12px;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 13px;
    }

    .tomatito-mi-perfil-page .perfil-message.visible {
        display: block;
    }

    .tomatito-mi-perfil-page .perfil-message.success {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }

    .tomatito-mi-perfil-page .perfil-message.error {
        background: #fee2e2;
        border: 1px solid #fca5a5;
        color: #991b1b;
    }

    /* ===== Licencia ===== */
    .tomatito-mi-perfil-page .tomatito-license-activation {
        padding-bottom: 12px;
        margin-bottom: 12px;
        border-bottom: 1px solid #f0f0f0;
    }

    /*
     * El componente [ohc_account_license] del Omkrom Hub SDK usa las clases
     * .ohc-license-mini__top / __meta / __editor pero el SDK no trae reglas
     * propias para ellas fuera de su framework completo [ohc_account]. Estas
     * reglas solo rellenan ese hueco visual; si el SDK las corrige más
     * adelante, estas reglas simplemente dejan de tener efecto visible.
     */
    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-license-mini__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 10px;
    }

    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-license-mini__top > div span {
        display: block;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        color: #9ca3af;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-license-mini__top > div strong {
        font-size: 18px;
        font-weight: 600;
        color: #111827;
    }

    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-license-mini__top i {
        font-style: normal;
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        background: #f3f4f6;
        color: #6b7280;
        white-space: nowrap;
    }

    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-license-mini__top i.is-active,
    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-license-mini__top i.is-valid,
    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-license-mini__top i.is-trial {
        background: #ecfdf5;
        color: #047857;
    }

    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-license-mini__meta {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
        margin-bottom: 14px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f0f0f0;
    }

    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-license-mini__meta span {
        font-size: 12px;
        color: #6b7280;
    }

    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-license-mini__meta b {
        display: block;
        margin-top: 2px;
        font-size: 14px;
        font-weight: 600;
        color: #111827;
    }

    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-license-mini__editor summary {
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        color: #ff4d4d;
        list-style: none;
        margin-bottom: 10px;
    }

    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-license-mini__editor summary::-webkit-details-marker {
        display: none;
    }

    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-account-message {
        padding: 10px 14px;
        margin-bottom: 12px;
        border-radius: 8px;
        font-size: 13px;
    }

    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-account-message.is-success {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }

    .tomatito-mi-perfil-page .tomatito-license-activation .ohc-account-message.is-error {
        background: #fee2e2;
        border: 1px solid #fca5a5;
        color: #991b1b;
    }

    .tomatito-mi-perfil-page .license-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #ecfdf5;
        color: #047857;
        font-size: 12px;
        font-weight: 600;
    }

    .tomatito-mi-perfil-page .license-status::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    .tomatito-mi-perfil-page .license-status.unavailable {
        background: #f3f4f6;
        color: #6b7280;
    }

    .tomatito-mi-perfil-page .license-metric {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 2px;
        min-width: 240px;
        max-width: 360px;
        flex: 0 1 360px;
    }

    .tomatito-mi-perfil-page .license-metric strong {
        font-size: 14px;
        color: #111827;
    }

    .tomatito-mi-perfil-page .license-metric small {
        font-size: 12px;
        color: #6b7280;
    }

    .tomatito-mi-perfil-page .license-key {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 13px;
        color: #374151;
        word-break: break-all;
        text-align: right;
    }

    /* ===== Editor de avatar ===== */
    .tomatito-avatar-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 999999;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(17, 24, 39, 0.58);
    }

    .tomatito-avatar-modal.is-open {
        display: flex;
    }

    .tomatito-avatar-dialog {
        width: min(760px, 100%);
        max-height: min(820px, calc(100vh - 48px));
        overflow: auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 24px 70px rgba(0,0,0,0.24);
        padding: 24px;
    }

    .tomatito-avatar-dialog-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .tomatito-avatar-dialog-header h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 600;
    }

    .tomatito-avatar-close {
        width: 36px;
        height: 36px;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        background: #fff;
        color: #374151;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
    }

    .tomatito-avatar-editor {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 180px;
        gap: 22px;
        align-items: start;
    }

    .tomatito-avatar-stage {
        position: relative;
        width: 100%;
        aspect-ratio: 1 / 1;
        max-height: 520px;
        overflow: hidden;
        border-radius: 12px;
        background: #111827;
        touch-action: none;
        user-select: none;
        cursor: grab;
    }

    .tomatito-avatar-stage.is-dragging {
        cursor: grabbing;
    }

    .tomatito-avatar-stage img {
        position: absolute;
        max-width: none;
        max-height: none;
        transform-origin: center center;
        pointer-events: none;
        user-select: none;
        will-change: transform, width, height, left, top;
    }

    .tomatito-avatar-mask {
        position: absolute;
        inset: 0;
        pointer-events: none;
        box-shadow: 0 0 0 9999px rgba(0,0,0,0.46);
    }

    .tomatito-avatar-mask::after {
        content: '';
        position: absolute;
        width: 76%;
        height: 76%;
        left: 12%;
        top: 12%;
        border-radius: 50%;
        border: 2px solid rgba(255,255,255,0.95);
        box-sizing: border-box;
    }

    .tomatito-avatar-controls {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .tomatito-avatar-controls .preview-label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }

    .tomatito-avatar-preview {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
    }

    .tomatito-avatar-zoom-label {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        color: #6b7280;
    }

    .tomatito-avatar-zoom {
        width: 100%;
        accent-color: #ff4d4d;
    }

    .tomatito-avatar-hint {
        margin: 0;
        font-size: 12px;
        line-height: 1.5;
        color: #6b7280;
    }

    .tomatito-avatar-dialog-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 22px;
        padding-top: 18px;
        border-top: 1px solid #eee;
    }

    .tomatito-avatar-secondary {
        padding: 10px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        background: #fff;
        color: #111827;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
    }

    @media (max-width: 680px) {
        .tomatito-avatar-modal { padding: 12px; }
        .tomatito-avatar-dialog { padding: 18px; max-height: calc(100vh - 24px); }
        .tomatito-avatar-editor { grid-template-columns: 1fr; }
        .tomatito-avatar-controls { display: grid; grid-template-columns: 96px 1fr; align-items: center; }
        .tomatito-avatar-controls .preview-label,
        .tomatito-avatar-hint { grid-column: 1 / -1; }
        .tomatito-avatar-dialog-actions { margin-top: 16px; }
    }

    @media (max-width: 760px) {
        .tomatito-mi-perfil-page .tomatito-content {
            padding-left: 20px;
            padding-right: 20px;
        }

        .tomatito-mi-perfil-page .perfil-row {
            align-items: stretch;
            flex-direction: column;
            gap: 10px;
        }

        .tomatito-mi-perfil-page .perfil-control,
        .tomatito-mi-perfil-page .avatar-control,
        .tomatito-mi-perfil-page .license-metric {
            min-width: 0;
            max-width: none;
            width: 100%;
            flex-basis: auto;
        }
    }
    </style>

    <div class="tomatito-dashboard tomatito-mi-perfil-page">
        <aside class="tomatito-sidebar">
            <div class="tomatito-logo">
                <img class="logo-emoji" src="<?php echo esc_url( $logo_url ); ?>" alt="🍅"> <strong>Tomatito</strong> Enfocado
            </div>

            <ul class="menu">
                <li><a href="/dashboard/">📊 Visión General</a></li>
                <li><a href="/pomodoros/"><img class="tomato-icon" src="<?php echo esc_url( $logo_url ); ?>" alt="🍅"> Pomodoros</a></li>
                <li><a href="/temporizadores/">⏱️ Temporizadores</a></li>
                <li><a href="/alarmas/">🔔 Alarmas</a></li>
                <li><a href="/ajustes/">⚙️ Ajustes</a></li>
                <li><a class="active" href="/mi-perfil/">👤 Mi perfil</a></li>
            </ul>

            <div class="sidebar-footer">
                <div class="status">🟢 Conectado a Omkrom</div>
                <a href="<?php echo esc_url( wp_logout_url( '/login/' ) ); ?>" class="logout">Cerrar sesión</a>
            </div>
        </aside>

        <main class="tomatito-content">
            <?php if ( function_exists( 'tomatito_render_ad_header' ) ) { tomatito_render_ad_header(); } ?>
            <div class="tomatito-header">
                <h1>Mi perfil</h1>
            </div>

            <div class="perfil-container">
                <div class="perfil-section">
                    <h2>👤 Información personal</h2>

                    <div class="perfil-row avatar-row">
                        <div class="perfil-label">
                            <span class="title">Avatar</span>
                            <span class="desc">Imagen de perfil.</span>
                        </div>
                        <div class="avatar-control">
                            <img class="avatar-image" id="tomatito-profile-avatar" src="<?php echo esc_url( $avatar_url ); ?>" alt="Avatar">
                            <label class="avatar-button" for="tomatito-profile-avatar-file">Cambiar avatar</label>
                            <input class="avatar-file" id="tomatito-profile-avatar-file" type="file" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif">
                        </div>
                    </div>

                    <div class="perfil-row">
                        <div class="perfil-label">
                            <span class="title">Nombre visible</span>
                            <span class="desc">El nombre que aparece en Tomatito.</span>
                        </div>
                        <div class="perfil-control">
                            <input id="tomatito-profile-display-name" type="text" value="<?php echo esc_attr( $display_name ); ?>" maxlength="100">
                        </div>
                    </div>

                    <div class="perfil-row">
                        <div class="perfil-label">
                            <span class="title">Correo electrónico</span>
                            <span class="desc">Se gestiona desde tu cuenta.</span>
                        </div>
                        <div class="perfil-control">
                            <input type="email" value="<?php echo esc_attr( $email ); ?>" readonly>
                        </div>
                    </div>

                    <div class="perfil-row">
                        <div class="perfil-label">
                            <span class="title">Nombre</span>
                        </div>
                        <div class="perfil-control">
                            <input id="tomatito-profile-first-name" type="text" value="<?php echo esc_attr( $first_name ); ?>" maxlength="100">
                        </div>
                    </div>

                    <div class="perfil-row">
                        <div class="perfil-label">
                            <span class="title">Apellidos</span>
                        </div>
                        <div class="perfil-control">
                            <input id="tomatito-profile-last-name" type="text" value="<?php echo esc_attr( $last_name ); ?>" maxlength="100">
                        </div>
                    </div>

                    <div class="perfil-row">
                        <div class="perfil-label">
                            <span class="title">Fecha de nacimiento</span>
                            <span class="desc">Opcional.</span>
                        </div>
                        <div class="perfil-control">
                            <input id="tomatito-profile-birth-date" type="date" value="<?php echo esc_attr( $birth_date ); ?>">
                        </div>
                    </div>

                    <div class="perfil-actions">
                        <button type="button" class="btn-primary" id="tomatito-profile-save">Guardar perfil</button>
                    </div>
                    <div class="perfil-message" id="tomatito-profile-message"></div>
                </div>

                <div class="perfil-section">
                    <h2>🌍 Preferencias</h2>

                    <div class="perfil-row">
                        <div class="perfil-label">
                            <span class="title">Zona horaria</span>
                            <span class="desc">Se utiliza para mostrar las horas de Tomatito.</span>
                        </div>
                        <div class="perfil-control">
                            <input id="tomatito-profile-timezone" type="text" value="<?php echo esc_attr( $timezone ); ?>" maxlength="100">
                        </div>
                    </div>
                </div>

                <div class="perfil-section">
                    <h2>🔑 Licencia</h2>

                    <?php if ( shortcode_exists( 'ohc_account_license' ) ) : ?>
                        <div class="tomatito-license-activation">
                            <?php
                            /*
                             * El formulario del SDK trae fijo el "volver a" apuntando a
                             * /mi-cuenta/ (la página del Hub), aunque el shortcode se use
                             * aquí. Reescribimos ese enlace para que, tras validar la
                             * licencia, el usuario vuelva a esta misma página de Tomatito
                             * en vez de saltar a /mi-cuenta/. Si el SDK cambia ese marcado,
                             * el str_replace simplemente no encuentra nada y no rompe nada.
                             */
                            $tomatito_license_html = do_shortcode( '[ohc_account_license]' );
                            if ( function_exists( 'ohc_account_url' ) ) {
                                $tomatito_license_html = str_replace(
                                    esc_url( ohc_account_url() . '#profile' ),
                                    esc_url( home_url( '/mi-perfil/' ) ),
                                    $tomatito_license_html
                                );
                            }
                            echo $tomatito_license_html;
                            ?>
                        </div>
                    <?php elseif ( current_user_can( 'manage_options' ) ) : ?>
                        <div class="perfil-row">
                            <div class="perfil-label">
                                <span class="title">Activar licencia</span>
                                <span class="desc">El shortcode [ohc_account_license] del Omkrom Hub SDK no está disponible en este entorno (mensaje visible solo para administradores).</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="perfil-row">
                        <div class="perfil-label">
                            <span class="title">Pomodoros activos</span>
                            <span class="desc">Máximo permitido por tu licencia.</span>
                        </div>
                        <div class="license-metric">
                            <strong><?php echo null === $license_info['pomodoros_limit'] ? '—' : esc_html( $license_info['pomodoros_limit'] ); ?></strong>
                            <small>límite de licencia</small>
                        </div>
                    </div>

                    <div class="perfil-row">
                        <div class="perfil-label">
                            <span class="title">Temporizadores activos</span>
                            <span class="desc">Máximo permitido por tu licencia.</span>
                        </div>
                        <div class="license-metric">
                            <strong><?php echo null === $license_info['temporizadores_limit'] ? '—' : esc_html( $license_info['temporizadores_limit'] ); ?></strong>
                            <small>límite de licencia</small>
                        </div>
                    </div>

                    <div class="perfil-row">
                        <div class="perfil-label">
                            <span class="title">Alarmas</span>
                            <span class="desc">Máximo permitido por tu licencia.</span>
                        </div>
                        <div class="license-metric">
                            <strong><?php echo null === $license_info['alarmas_limit'] ? '—' : esc_html( $license_info['alarmas_limit'] ); ?></strong>
                            <small>límite de licencia</small>
                        </div>
                    </div>

                </div>

                <div class="perfil-section">
                    <h2>🔐 Cuenta</h2>

                    <div class="perfil-row">
                        <div class="perfil-label">
                            <span class="title">Sesión actual</span>
                            <span class="desc">Estás conectado como <?php echo esc_html( $email ); ?>.</span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ( function_exists( 'tomatito_render_ad_footer' ) ) { tomatito_render_ad_footer(); } ?>

        </main>

        <div class="tomatito-avatar-modal" id="tomatito-avatar-modal" role="dialog" aria-modal="true" aria-labelledby="tomatito-avatar-title">
            <div class="tomatito-avatar-dialog">
                <div class="tomatito-avatar-dialog-header">
                    <h2 id="tomatito-avatar-title">Ajustar foto de perfil</h2>
                    <button type="button" class="tomatito-avatar-close" id="tomatito-avatar-close" aria-label="Cerrar">×</button>
                </div>

                <div class="tomatito-avatar-editor">
                    <div class="tomatito-avatar-stage" id="tomatito-avatar-stage">
                        <img id="tomatito-avatar-editor-image" src="" alt="Vista previa de la foto">
                        <div class="tomatito-avatar-mask" aria-hidden="true"></div>
                    </div>

                    <div class="tomatito-avatar-controls">
                        <span class="preview-label">Vista previa</span>
                        <img class="tomatito-avatar-preview" id="tomatito-avatar-preview" src="" alt="Vista previa del avatar">

                        <div>
                            <div class="tomatito-avatar-zoom-label">
                                <span>Zoom</span>
                                <span id="tomatito-avatar-zoom-value">100%</span>
                            </div>
                            <input class="tomatito-avatar-zoom" id="tomatito-avatar-zoom" type="range" min="1" max="3" step="0.01" value="1" aria-label="Zoom de la foto">
                        </div>

                        <p class="tomatito-avatar-hint">Arrastra la foto para colocarla. Usa el zoom para acercar o alejar y ajusta el encuadre dentro del círculo.</p>
                    </div>
                </div>

                <div class="tomatito-avatar-dialog-actions">
                    <button type="button" class="tomatito-avatar-secondary" id="tomatito-avatar-cancel">Cancelar</button>
                    <button type="button" class="btn-primary" id="tomatito-avatar-apply">Usar esta foto</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const root = document.querySelector('.tomatito-mi-perfil-page');
        if (!root) return;

        const ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
        const nonce = <?php echo wp_json_encode( wp_create_nonce( 'tomatito_mi_perfil' ) ); ?>;

        const avatarInput = root.querySelector('#tomatito-profile-avatar-file');
        const avatarImage = root.querySelector('#tomatito-profile-avatar');
        const saveButton = root.querySelector('#tomatito-profile-save');
        const message = root.querySelector('#tomatito-profile-message');

        function showMessage(text, type) {
            message.textContent = text;
            message.className = 'perfil-message visible ' + type;
        }

        const avatarModal = root.querySelector('#tomatito-avatar-modal');
        const avatarStage = root.querySelector('#tomatito-avatar-stage');
        const editorImage = root.querySelector('#tomatito-avatar-editor-image');
        const avatarPreview = root.querySelector('#tomatito-avatar-preview');
        const zoomInput = root.querySelector('#tomatito-avatar-zoom');
        const zoomValue = root.querySelector('#tomatito-avatar-zoom-value');
        const closeAvatarButton = root.querySelector('#tomatito-avatar-close');
        const cancelAvatarButton = root.querySelector('#tomatito-avatar-cancel');
        const applyAvatarButton = root.querySelector('#tomatito-avatar-apply');

        let avatarSourceFile = null;
        let avatarSourceUrl = '';
        let avatarSourceImage = null;
        let avatarOutputBlob = null;
        let cropState = { x: 0, y: 0, zoom: 1, baseWidth: 0, baseHeight: 0 };
        let dragState = null;

        function revokeAvatarSourceUrl() {
            if (avatarSourceUrl && avatarSourceUrl.indexOf('blob:') === 0) {
                URL.revokeObjectURL(avatarSourceUrl);
            }
            avatarSourceUrl = '';
        }

        function closeAvatarEditor() {
            avatarModal.classList.remove('is-open');
            avatarStage.classList.remove('is-dragging');
            dragState = null;
        }

        function setEditorImage(src) {
            return new Promise(function(resolve, reject) {
                editorImage.onload = function() {
                    avatarSourceImage = editorImage;
                    const stageSize = avatarStage.clientWidth;
                    const imageRatio = editorImage.naturalWidth / editorImage.naturalHeight;
                    if (imageRatio >= 1) {
                        cropState.baseHeight = stageSize * 0.76;
                        cropState.baseWidth = cropState.baseHeight * imageRatio;
                    } else {
                        cropState.baseWidth = stageSize * 0.76;
                        cropState.baseHeight = cropState.baseWidth / imageRatio;
                    }
                    cropState.x = (stageSize - cropState.baseWidth) / 2;
                    cropState.y = (stageSize - cropState.baseHeight) / 2;
                    cropState.zoom = 1;
                    zoomInput.value = '1';
                    renderCrop();
                    resolve();
                };
                editorImage.onerror = reject;
                editorImage.src = src;
            });
        }

        function clampCropPosition() {
            const stageSize = avatarStage.clientWidth;
            const cropLeft = stageSize * 0.12;
            const cropTop = stageSize * 0.12;
            const cropRight = stageSize * 0.88;
            const cropBottom = stageSize * 0.88;
            const width = cropState.baseWidth * cropState.zoom;
            const height = cropState.baseHeight * cropState.zoom;

            cropState.x = Math.min(cropLeft, Math.max(cropRight - width, cropState.x));
            cropState.y = Math.min(cropTop, Math.max(cropBottom - height, cropState.y));
        }

        function renderCrop() {
            if (!avatarSourceImage || !avatarSourceImage.naturalWidth) return;
            clampCropPosition();
            const stageSize = avatarStage.clientWidth;
            const width = cropState.baseWidth * cropState.zoom;
            const height = cropState.baseHeight * cropState.zoom;
            editorImage.style.width = width + 'px';
            editorImage.style.height = height + 'px';
            editorImage.style.left = cropState.x + 'px';
            editorImage.style.top = cropState.y + 'px';
            zoomValue.textContent = Math.round(cropState.zoom * 100) + '%';

            const cropSize = stageSize * 0.76;
            const sourceCropX = ((stageSize * 0.12) - cropState.x) / cropState.zoom;
            const sourceCropY = ((stageSize * 0.12) - cropState.y) / cropState.zoom;
            const sourceCropSize = cropSize / cropState.zoom;

            const previewCanvas = document.createElement('canvas');
            previewCanvas.width = 192;
            previewCanvas.height = 192;
            const previewContext = previewCanvas.getContext('2d');
            previewContext.imageSmoothingEnabled = true;
            previewContext.imageSmoothingQuality = 'high';
            previewContext.drawImage(
                avatarSourceImage,
                sourceCropX / cropState.baseWidth * avatarSourceImage.naturalWidth,
                sourceCropY / cropState.baseHeight * avatarSourceImage.naturalHeight,
                sourceCropSize / cropState.baseWidth * avatarSourceImage.naturalWidth,
                sourceCropSize / cropState.baseHeight * avatarSourceImage.naturalHeight,
                0, 0, 192, 192
            );
            avatarPreview.src = previewCanvas.toDataURL('image/jpeg', 0.92);
        }

        function createAvatarBlob() {
            return new Promise(function(resolve, reject) {
                if (!avatarSourceImage || !avatarSourceImage.naturalWidth) {
                    reject(new Error('No se ha seleccionado una imagen.'));
                    return;
                }

                clampCropPosition();
                const stageSize = avatarStage.clientWidth;
                const cropSize = stageSize * 0.76;
                const sourceCropX = ((stageSize * 0.12) - cropState.x) / cropState.zoom;
                const sourceCropY = ((stageSize * 0.12) - cropState.y) / cropState.zoom;
                const sourceCropSize = cropSize / cropState.zoom;
                const sourceX = sourceCropX / cropState.baseWidth * avatarSourceImage.naturalWidth;
                const sourceY = sourceCropY / cropState.baseHeight * avatarSourceImage.naturalHeight;
                const sourceSize = sourceCropSize / cropState.baseWidth * avatarSourceImage.naturalWidth;
                const safeX = Math.max(0, sourceX);
                const safeY = Math.max(0, sourceY);
                const safeSize = Math.min(sourceSize, avatarSourceImage.naturalWidth - safeX, avatarSourceImage.naturalHeight - safeY);
                const outputSize = Math.min(1200, Math.max(512, Math.round(safeSize)));
                const canvas = document.createElement('canvas');
                canvas.width = outputSize;
                canvas.height = outputSize;
                const context = canvas.getContext('2d');
                context.imageSmoothingEnabled = true;
                context.imageSmoothingQuality = 'high';
                context.drawImage(avatarSourceImage, safeX, safeY, safeSize, safeSize, 0, 0, outputSize, outputSize);

                function makeBlob(size, quality) {
                    if (canvas.width !== size) {
                        canvas.width = size;
                        canvas.height = size;
                        const ctx = canvas.getContext('2d');
                        ctx.imageSmoothingEnabled = true;
                        ctx.imageSmoothingQuality = 'high';
                        ctx.drawImage(avatarSourceImage, safeX, safeY, safeSize, safeSize, 0, 0, size, size);
                    }
                    return new Promise(function(blobResolve) {
                        canvas.toBlob(blobResolve, 'image/jpeg', quality);
                    });
                }

                (async function() {
                    let blob = await makeBlob(outputSize, 0.92);
                    if (blob && blob.size > 2 * 1024 * 1024) {
                        blob = await makeBlob(Math.min(1000, outputSize), 0.88);
                    }
                    if (blob && blob.size > 2 * 1024 * 1024) {
                        blob = await makeBlob(800, 0.84);
                    }
                    if (!blob || blob.size > 2 * 1024 * 1024) {
                        reject(new Error('La foto ajustada sigue superando 2 MB. Reduce un poco el tamaño de la imagen original.'));
                        return;
                    }
                    resolve(blob);
                })().catch(reject);
            });
        }

        async function openAvatarEditor(file) {
            avatarSourceFile = file;
            avatarOutputBlob = null;
            revokeAvatarSourceUrl();

            const fileName = (file.name || '').toLowerCase();
            const isHeic = file.type === 'image/heic' || file.type === 'image/heif' || fileName.endsWith('.heic') || fileName.endsWith('.heif');
            const isStandardImage = ['image/jpeg', 'image/png', 'image/webp'].includes(file.type);

            if (!isStandardImage && !isHeic) {
                avatarInput.value = '';
                showMessage('Selecciona una imagen JPG, PNG, WebP o HEIC/HEIF.', 'error');
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                avatarInput.value = '';
                showMessage('La imagen no puede superar 2 MB.', 'error');
                return;
            }

            avatarModal.classList.add('is-open');

            try {
                if (isHeic) {
                    const data = new FormData();
                    data.append('action', 'tomatito_previsualizar_avatar_heic');
                    data.append('nonce', nonce);
                    data.append('avatar', file);

                    const response = await fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data });
                    const result = await response.json();
                    if (!result.success || !result.data || !result.data.data_url) {
                        throw new Error(result.data && result.data.message ? result.data.message : 'No se pudo previsualizar la imagen HEIC/HEIF.');
                    }
                    await setEditorImage(result.data.data_url);
                } else {
                    avatarSourceUrl = URL.createObjectURL(file);
                    await setEditorImage(avatarSourceUrl);
                }
            } catch (error) {
                closeAvatarEditor();
                avatarInput.value = '';
                showMessage(error.message || 'No se pudo abrir la imagen para editarla.', 'error');
            }
        }

        avatarInput.addEventListener('change', function() {
            const file = avatarInput.files && avatarInput.files[0];
            if (file) openAvatarEditor(file);
        });

        zoomInput.addEventListener('input', function() {
            const oldZoom = cropState.zoom;
            const newZoom = parseFloat(zoomInput.value);
            const stageSize = avatarStage.clientWidth;
            const centerX = stageSize / 2;
            const centerY = stageSize / 2;
            cropState.x = centerX - (centerX - cropState.x) * (newZoom / oldZoom);
            cropState.y = centerY - (centerY - cropState.y) * (newZoom / oldZoom);
            cropState.zoom = newZoom;
            renderCrop();
        });

        avatarStage.addEventListener('pointerdown', function(event) {
            if (!avatarSourceImage) return;
            dragState = { startX: event.clientX, startY: event.clientY, originX: cropState.x, originY: cropState.y };
            avatarStage.classList.add('is-dragging');
            avatarStage.setPointerCapture(event.pointerId);
        });

        avatarStage.addEventListener('pointermove', function(event) {
            if (!dragState) return;
            cropState.x = dragState.originX + (event.clientX - dragState.startX);
            cropState.y = dragState.originY + (event.clientY - dragState.startY);
            renderCrop();
        });

        function stopDragging() {
            dragState = null;
            avatarStage.classList.remove('is-dragging');
        }

        avatarStage.addEventListener('pointerup', stopDragging);
        avatarStage.addEventListener('pointercancel', stopDragging);

        closeAvatarButton.addEventListener('click', function() {
            closeAvatarEditor();
            avatarInput.value = '';
            avatarSourceFile = null;
            avatarOutputBlob = null;
            revokeAvatarSourceUrl();
        });

        cancelAvatarButton.addEventListener('click', function() {
            closeAvatarEditor();
            avatarInput.value = '';
            avatarSourceFile = null;
            avatarOutputBlob = null;
            revokeAvatarSourceUrl();
        });

        applyAvatarButton.addEventListener('click', async function() {
            applyAvatarButton.disabled = true;
            try {
                avatarOutputBlob = await createAvatarBlob();
                avatarImage.src = URL.createObjectURL(avatarOutputBlob);
                closeAvatarEditor();
                showMessage('Foto ajustada. Pulsa «Guardar perfil» para aplicar el nuevo avatar.', 'success');
            } catch (error) {
                showMessage(error.message || 'No se pudo preparar la foto.', 'error');
            } finally {
                applyAvatarButton.disabled = false;
            }
        });

        saveButton.addEventListener('click', function() {
            saveButton.disabled = true;
            message.className = 'perfil-message';

            const data = new FormData();
            data.append('action', 'tomatito_guardar_mi_perfil');
            data.append('nonce', nonce);
            data.append('display_name', root.querySelector('#tomatito-profile-display-name').value);
            data.append('first_name', root.querySelector('#tomatito-profile-first-name').value);
            data.append('last_name', root.querySelector('#tomatito-profile-last-name').value);
            data.append('birth_date', root.querySelector('#tomatito-profile-birth-date').value);
            data.append('timezone', root.querySelector('#tomatito-profile-timezone').value);

            if (avatarOutputBlob) {
                data.append('avatar', avatarOutputBlob, 'avatar.jpg');
            }

            fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(result) {
                if (!result.success) {
                    throw new Error(result.data && result.data.message ? result.data.message : 'No se pudo guardar el perfil.');
                }

                if (result.data && result.data.avatar_url) {
                    avatarImage.src = result.data.avatar_url;
                    avatarInput.value = '';
                    avatarOutputBlob = null;
                    avatarSourceFile = null;
                    revokeAvatarSourceUrl();
                }

                showMessage('Perfil guardado correctamente.', 'success');
            })
            .catch(function(error) {
                showMessage(error.message || 'No se pudo guardar el perfil.', 'error');
            })
            .finally(function() {
                saveButton.disabled = false;
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}
}
if ( ! shortcode_exists( 'tomatito_mi_perfil' ) && function_exists( 'tomatito_mi_perfil_shortcode' ) ) {
    add_shortcode( 'tomatito_mi_perfil', 'tomatito_mi_perfil_shortcode' );
}

if ( ! function_exists( 'tomatito_previsualizar_avatar_heic_ajax' ) ) {
function tomatito_previsualizar_avatar_heic_ajax() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Debes iniciar sesión.' ), 401 );
    }

    check_ajax_referer( 'tomatito_mi_perfil', 'nonce' );

    if ( empty( $_FILES['avatar'] ) || empty( $_FILES['avatar']['tmp_name'] ) ) {
        wp_send_json_error( array( 'message' => 'No se recibió ninguna imagen.' ) );
    }

    $file = $_FILES['avatar'];
    if ( (int) $file['size'] > 2 * 1024 * 1024 ) {
        wp_send_json_error( array( 'message' => 'La imagen no puede superar 2 MB.' ) );
    }

    $file_name = strtolower( (string) $file['name'] );
    $is_heic = in_array( strtolower( (string) $file['type'] ), array( 'image/heic', 'image/heif' ), true )
        || preg_match( '/\\.(heic|heif)$/', $file_name );

    if ( ! $is_heic ) {
        wp_send_json_error( array( 'message' => 'El archivo no es HEIC/HEIF.' ) );
    }

    if ( ! class_exists( 'Imagick' ) ) {
        wp_send_json_error( array( 'message' => 'Este servidor no tiene soporte para convertir imágenes HEIC/HEIF. Instala/activa Imagick con soporte HEIC.' ) );
    }

    try {
        $image = new Imagick();
        $image->readImage( $file['tmp_name'] );
        if ( method_exists( $image, 'autoOrientImage' ) ) {
            $image->autoOrientImage();
        }
        $image->setIteratorIndex( 0 );
        $image->setImageFormat( 'jpeg' );
        $image->setImageCompression( Imagick::COMPRESSION_JPEG );
        $image->setImageCompressionQuality( 90 );
        $image->stripImage();
        $image->thumbnailImage( 1600, 1600, true, true );
        $blob = $image->getImageBlob();
        $image->clear();
        $image->destroy();
    } catch ( Throwable $e ) {
        wp_send_json_error( array( 'message' => 'No se pudo convertir la imagen HEIC/HEIF para la vista previa. Comprueba que Imagick tenga soporte HEIC.' ) );
    }

    if ( empty( $blob ) ) {
        wp_send_json_error( array( 'message' => 'No se pudo generar la vista previa.' ) );
    }

    wp_send_json_success(
        array(
            'data_url' => 'data:image/jpeg;base64,' . base64_encode( $blob ),
        )
    );
}
}
if ( function_exists( 'tomatito_previsualizar_avatar_heic_ajax' ) ) {
    add_action( 'wp_ajax_tomatito_previsualizar_avatar_heic', 'tomatito_previsualizar_avatar_heic_ajax' );
}

if ( ! function_exists( 'tomatito_guardar_mi_perfil_ajax' ) ) {
function tomatito_guardar_mi_perfil_ajax() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Debes iniciar sesión.' ), 401 );
    }

    check_ajax_referer( 'tomatito_mi_perfil', 'nonce' );

    $user_id = get_current_user_id();

    $display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
    $first_name   = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
    $last_name    = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
    $birth_date   = isset( $_POST['birth_date'] ) ? sanitize_text_field( wp_unslash( $_POST['birth_date'] ) ) : '';
    $timezone     = isset( $_POST['timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['timezone'] ) ) : '';

    if ( $display_name === '' ) {
        $display_name = trim( $first_name . ' ' . $last_name );
    }

    $result = wp_update_user(
        array(
            'ID' => $user_id,
            'display_name' => $display_name,
            'first_name' => $first_name,
            'last_name' => $last_name,
        )
    );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }

    update_user_meta( $user_id, 'tomatito_fecha_nacimiento', $birth_date );
    update_user_meta( $user_id, 'tomatito_zona_horaria', $timezone );

    $avatar_url = '';

    if ( ! empty( $_FILES['avatar'] ) && ! empty( $_FILES['avatar']['tmp_name'] ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file = $_FILES['avatar'];

        if ( (int) $file['size'] > 2 * 1024 * 1024 ) {
            wp_send_json_error( array( 'message' => 'La imagen no puede superar 2 MB.' ) );
        }

        $file_name = strtolower( (string) $file['name'] );
        $is_heic = in_array( strtolower( (string) $file['type'] ), array( 'image/heic', 'image/heif' ), true )
            || preg_match( '/\\.(heic|heif)$/', $file_name );

        if ( $is_heic ) {
            /*
             * iPhone usa HEIC/HEIF. WordPress/GD normalmente no puede procesarlo,
             * por lo que usamos Imagick cuando el servidor dispone del delegado HEIC.
             */
            if ( ! class_exists( 'Imagick' ) ) {
                wp_send_json_error( array( 'message' => 'Este servidor no tiene soporte para convertir imágenes HEIC/HEIF. Instala/activa Imagick con soporte HEIC.' ) );
            }

            $converted_path = wp_tempnam( 'tomatito-avatar-' );
            if ( ! $converted_path ) {
                wp_send_json_error( array( 'message' => 'No se pudo preparar la conversión de la imagen.' ) );
            }

            try {
                $image = new Imagick();
                $image->readImage( $file['tmp_name'] );
                if ( method_exists( $image, 'autoOrientImage' ) ) {
                    $image->autoOrientImage();
                }
                $image->setImageFormat( 'jpeg' );
                $image->setImageCompression( Imagick::COMPRESSION_JPEG );
                $image->setImageCompressionQuality( 88 );
                $image->stripImage();

                if ( ! $image->writeImage( $converted_path ) ) {
                    throw new Exception( 'write_failed' );
                }

                $image->clear();
                $image->destroy();
            } catch ( Throwable $e ) {
                if ( file_exists( $converted_path ) ) {
                    @unlink( $converted_path );
                }
                wp_send_json_error( array( 'message' => 'No se pudo convertir la imagen HEIC/HEIF a JPG. Comprueba que Imagick tenga soporte HEIC.' ) );
            }

            $upload_file = array(
                'name'     => pathinfo( $file['name'], PATHINFO_FILENAME ) . '.jpg',
                'type'     => 'image/jpeg',
                'tmp_name' => $converted_path,
                'error'    => 0,
                'size'     => (int) filesize( $converted_path ),
            );

            $upload = wp_handle_sideload(
                $upload_file,
                array(
                    'test_form' => false,
                    'mimes' => array(
                        'jpg|jpeg|jpe' => 'image/jpeg',
                    ),
                )
            );

            @unlink( $converted_path );
        } else {
            $mime = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
            $allowed = array(
                'image/jpeg' => true,
                'image/png'  => true,
                'image/webp' => true,
            );

            if ( empty( $mime['type'] ) || empty( $allowed[ $mime['type'] ] ) ) {
                wp_send_json_error( array( 'message' => 'Formato de imagen no válido. Usa JPG, PNG o WebP.' ) );
            }

            $upload = wp_handle_upload(
                $file,
                array(
                    'test_form' => false,
                    'mimes' => array(
                        'jpg|jpeg|jpe' => 'image/jpeg',
                        'png' => 'image/png',
                        'webp' => 'image/webp',
                    ),
                )
            );
        }

        if ( isset( $upload['error'] ) ) {
            wp_send_json_error( array( 'message' => $upload['error'] ) );
        }

        if ( ! empty( $upload['url'] ) ) {
            update_user_meta( $user_id, 'tomatito_avatar_url', esc_url_raw( $upload['url'] ) );
            $avatar_url = esc_url_raw( $upload['url'] );
        }
    }

    wp_send_json_success(
        array(
            'avatar_url' => $avatar_url,
        )
    );
}
}
if ( function_exists( 'tomatito_guardar_mi_perfil_ajax' ) ) {
    add_action( 'wp_ajax_tomatito_guardar_mi_perfil', 'tomatito_guardar_mi_perfil_ajax' );
}
