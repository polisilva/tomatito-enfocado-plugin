<?php
/**
 * Tomatito Anuncios — usa o sistema nativo de anúncios do Omkrom Hub
 * (ohc_ad()), não o Google AdSense.
 *
 * Regras (ver omkrom-sdk-ads-para-ia.md e omkrom-sdk-capabilities-para-ia.md):
 * 1. A elegibilidade (mostrar ou não anúncio) é decidida pela capacidade
 *    `show_ads` do Hub — não fazemos checagem manual de plano/licença.
 *    O plano que deve remover os anúncios só precisa não ter essa
 *    capacidade concedida, lá no painel do Omkrom Hub.
 * 2. Cada página chama dois blocos: um no topo (placement "header_banner",
 *    logo depois de abrir <main>) e um no rodapé (placement "footer_banner",
 *    uma linha antes de "</main>"). Todas as 7 páginas (Dashboard,
 *    Pomodoros, Temporizadores, Alarmas, Ajustes, Mi perfil e Histórico
 *    Completo) usam as mesmas duas funções.
 * 3. Se não houver campanha activa configurada no Hub para um placement,
 *    ohc_ad() devolve vazio — nesse caso não imprimimos nada (sem caixa
 *    vazia sobrando na página).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Decide se o utilizador atual deve ver anúncios.
 *
 * A capacidade `show_ads` só existe quando um PLANO a concede — um
 * utilizador sem nenhuma licença/plano atribuído não tem "show_ads", mas
 * também não tem nenhuma capacidade nenhuma (ohc_has_capability() dá
 * sempre false nesse caso). Isso é o oposto do que faz sentido: quem não
 * tem plano nenhum é precisamente quem é "conta gratuita" e DEVE ver
 * anúncios; só quem tem um plano pago sem `show_ads` é que não deve ver.
 *
 * Por isso: só usamos `show_ads` para decidir quando o utilizador TEM uma
 * licença/plano activo. Sem licença nenhuma, assumimos conta gratuita e
 * mostramos anúncios por padrão.
 */
if ( ! function_exists( 'tomatito_ads_enabled' ) ) {
function tomatito_ads_enabled() {
    if ( ! is_user_logged_in() ) {
        return true;
    }

    $user_id     = get_current_user_id();
    $product_key = sanitize_key( (string) apply_filters( 'tomatito_omkrom_product_key', 'focus-tomatito', $user_id ) );

    if ( ! $product_key || ! function_exists( 'ohc_has_capability' ) || ! function_exists( 'ohc_has_active_license' ) ) {
        // SDK Omkrom indisponível: assume mostrar anúncios (padrão seguro).
        return (bool) apply_filters( 'tomatito_ads_enabled', true );
    }

    if ( ! ohc_has_active_license( $product_key ) ) {
        // Sem licença/plano nenhum -> conta gratuita -> mostra anúncios.
        return true;
    }

    // Tem uma licença/plano activo: respeita exactamente a capacidade que
    // esse plano concede (ou não) no Omkrom Hub.
    return (bool) ohc_has_capability( $product_key, 'show_ads' );
}
}

/**
 * Imprime um anúncio de um placement do Hub, envolvido no wrapper indicado.
 * Função interna partilhada por tomatito_render_ad_header() e
 * tomatito_render_ad_footer() — para não duplicar a mesma lógica duas vezes.
 *
 * DIAGNÓSTICO: quando o anúncio não aparece, quem tem manage_options (admin
 * do site) vê uma caixa cinza explicando exactamente em que passo parou —
 * "usuário sem permissão", "SDK não carregado" ou "Hub sem campanha activa
 * para este placement". Isto é só visual (nunca aparece para utilizadores
 * normais) e pode ser removido depois de confirmarmos que os anúncios reais
 * já aparecem.
 */
if ( ! function_exists( 'tomatito_render_ad' ) ) {
function tomatito_render_ad( $placement, $wrapper_class ) {
    $can_debug = current_user_can( 'manage_options' );

    if ( ! tomatito_ads_enabled() ) {
        if ( $can_debug ) {
            tomatito_render_ad_debug_box( $placement, 'tomatito_ads_enabled() devolveu false — este usuário TEM uma licença/plano activo no Omkrom Hub, mas esse plano não concede a capacidade "show_ads" (ou seja, é um plano que remove anúncios).' );
        }
        return;
    }

    if ( ! function_exists( 'ohc_ad' ) ) {
        if ( $can_debug ) {
            tomatito_render_ad_debug_box( $placement, 'A função ohc_ad() não existe — o SDK do Omkrom Hub não está carregado neste site (verificar se o plugin/cliente Omkrom está activo).' );
        }
        return;
    }

    $ad_html = ohc_ad( $placement );
    if ( '' === trim( (string) $ad_html ) ) {
        if ( $can_debug ) {
            tomatito_render_ad_debug_box( $placement, 'ohc_ad() devolveu vazio — não há campanha activa no Hub para este placement (ou ela não está associada a este site/produto).' );
        }
        return; // sem campanha activa no Hub para este placement
    }
    ?>
    <div class="<?php echo esc_attr( $wrapper_class ); ?>">
        <?php echo $ad_html; // phpcs:ignore WordPress.Security.EscapeOutput -- markup confiável, gerado pelo próprio Omkrom Hub. ?>
    </div>
    <?php
}
}

if ( ! function_exists( 'tomatito_render_ad_debug_box' ) ) {
function tomatito_render_ad_debug_box( $placement, $reason ) {
    ?>
    <div class="tomatito-ad-debug">
        🔧 <strong>Anuncio "<?php echo esc_html( $placement ); ?>" no visible</strong> (solo tú lo ves, eres admin):
        <?php echo esc_html( $reason ); ?>
    </div>
    <?php
}
}

/**
 * Bloco de anúncio do topo (placement "header_banner"). Cada página chama
 * logo depois de abrir <main class="tomatito-content">.
 */
if ( ! function_exists( 'tomatito_render_ad_header' ) ) {
function tomatito_render_ad_header() {
    tomatito_render_ad( 'header_banner', 'tomatito-ad-header' );
}
}

/**
 * Bloco de anúncio do rodapé (placement "footer_banner"). Cada página chama
 * uma linha antes do "</main>".
 */
if ( ! function_exists( 'tomatito_render_ad_footer' ) ) {
function tomatito_render_ad_footer() {
    tomatito_render_ad( 'footer_banner', 'tomatito-ad-footer' );
}
}

/**
 * Espaçamento dos blocos de anúncio em relação ao conteúdo — carregado uma
 * única vez, em todas as páginas, via wp_head.
 */
if ( ! function_exists( 'tomatito_ads_inline_style' ) ) {
function tomatito_ads_inline_style() {
    ?>
    <style>
    body .tomatito-ad-header {
        margin-bottom: 20px;
    }
    body .tomatito-ad-footer {
        margin-top: 28px;
    }
    body .tomatito-ad-debug {
        margin: 12px 0;
        padding: 10px 14px;
        border: 1px dashed #f59e0b;
        border-radius: 8px;
        background: #fffbeb;
        color: #92400e;
        font-size: 12px;
        line-height: 1.5;
    }
    </style>
    <?php
}
}
add_action( 'wp_head', 'tomatito_ads_inline_style' );
