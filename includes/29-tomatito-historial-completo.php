<?php

function tomatito_historial_completo_shortcode() {

    if ( ! is_user_logged_in() ) {
        return '<p>Debes iniciar sesión.</p>';
    }

    ob_start();
    ?>

    <style>
    /* ============================================== */
    /* PÁGINA HISTORIAL COMPLETO - LIMPEZA WRAPPERS   */
    /* ============================================== */

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

    /* ============================================== */
    /* HEADER DA PÁGINA                               */
    /* ============================================== */

    .tomatito-historial-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 80px;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 24px;
    }

    .tomatito-historial-header h1 {
        font-size: 28px;
        font-weight: 600;
        margin: 0;
    }

    /* Botão "Volver al dashboard" */
    .btn-volver {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 18px;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        text-decoration: none;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-volver:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }

    /* Botão "Volver" no fundo da página */
    .historial-footer-volver {
        display: flex;
        justify-content: flex-start;
        margin: 28px 0 16px;
    }

    /* ============================================== */
    /* CONTROLOS DA BUSCA                             */
    /* ============================================== */
    /* ⚠️ Esta página não tem nenhum <select> — a granularidade são botões
       e a data é um input[type=date]. Por isso aqui não entra o componente
       de dropdown personalizado das outras páginas; o que se faz é alinhar
       estes controlos à MESMA linguagem visual: altura de 44px, cantos de
       10px, a mesma borda #e5e7eb, o mesmo anel de foco vermelho suave e o
       mesmo vermelho no estado seleccionado. */
    .history-search-controls {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .history-search-date {
        display: flex;
        align-items: center;
        gap: 8px;
        height: 44px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 0 14px;
        box-sizing: border-box;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    /* mesmo anel de foco dos selects das outras páginas */
    .history-search-date:focus-within {
        border-color: #ff4d4d;
        box-shadow: 0 0 0 3px rgba(255,77,77,0.12);
    }
    .history-search-date input[type="date"] {
        border: none;
        background: transparent;
        font-size: 14px;
        font-family: inherit;
        color: #111827;
        outline: none;
        padding: 0;
    }
    .history-search-granularity {
        display: flex;
        align-items: center;
        gap: 4px;
        height: 44px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 0 4px;
        box-sizing: border-box;
    }
    .history-search-granularity button {
        border: none;
        background: transparent;
        color: #374151;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 500;
        font-family: inherit;
        cursor: pointer;
        transition: 0.2s;
    }
    /* estado seleccionado — mesmo vermelho das opções escolhidas nos
       dropdowns de Ajustes / Pomodoros / Temporizadores / Alarmas */
    .history-search-granularity button.active {
        background: #ff4d4d;
        color: #fff;
        font-weight: 600;
    }
    .history-search-granularity button:hover:not(.active) {
        background: #f3f4f6;
    }
    .history-search-nav {
        display: flex;
        gap: 6px;
        margin-left: auto;
    }
    .history-search-nav button {
        width: 44px;
        height: 44px;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 10px;
        color: #374151;
        cursor: pointer;
        font-size: 16px;
        transition: 0.2s;
    }
    .history-search-nav button:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }
    .history-search-range-label {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 16px;
    }
    .history-search-summary {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 16px;
    }
    .history-search-summary .summary-card {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 16px 20px;
    }
    .history-search-summary .summary-card .summary-label {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 6px;
    }
    .history-search-summary .summary-card .summary-value {
        font-size: 26px;
        font-weight: 600;
        color: #111827;
    }
    .history-search-summary .summary-card.accent .summary-value {
        color: #ff4d4d;
    }
    .history-search-results {
        background: #fff;
        border-radius: 10px;
        border: 1px solid #eee;
        overflow: hidden;
    }
    .history-search-results .row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        padding: 14px 16px;
        border-bottom: 1px solid #eee;
        position: relative;
        padding-right: 44px;
    }
    .history-search-results .row:last-child { border-bottom: none; }
    .history-search-empty {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 24px 20px;
        text-align: center;
        color: #9ca3af;
        font-size: 14px;
    }

    .btn-delete-sesion {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 6px 10px;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-delete-sesion:hover {
        background: #fee2e2;
        border-color: #ef4444;
    }

    /* status colors */
    .ok       { color: #2ecc71; font-weight: 600; }
    .cancel   { color: #e74c3c; font-weight: 600; }
    .progress { color: #27ae60; font-weight: 600; }

    /* ============================================== */
    /* PAGINAÇÃO                                      */
    /* ============================================== */
    .history-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin-top: 16px;
        flex-wrap: wrap;
    }
    .history-pagination button {
        min-width: 36px;
        height: 36px;
        padding: 0 10px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        color: #374151;
        font-size: 14px;
        cursor: pointer;
        transition: 0.2s;
    }
    .history-pagination button:hover:not(:disabled):not(.active) {
        background: #f3f4f6;
        border-color: #d1d5db;
    }
    .history-pagination button.active {
        background: #ff4d4d;
        color: #fff;
        border-color: #ff4d4d;
        font-weight: 600;
    }
    .history-pagination button:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    </style>

    <div class="tomatito-dashboard">

        <!-- SIDEBAR -->
        <aside class="tomatito-sidebar">

            <div class="tomatito-logo">
                <img class="logo-emoji" src="<?php echo esc_url( content_url( 'uploads/2026/08/tomatito-icon.png' ) ); ?>" alt="🍅"> <strong>Tomatito</strong> Enfocado
            </div>

            <ul class="menu">
                <li><a href="/dashboard/">📊 Visión General</a></li>
                <li><a href="/pomodoros/"><img class="tomato-icon" src="<?php echo esc_url( content_url( 'uploads/2026/08/tomatito-icon.png' ) ); ?>" alt="🍅"> Pomodoros</a></li>
                <li><a href="/temporizadores/">⏱️ Temporizadores</a></li>
                <li><a href="/alarmas/">🔔 Alarmas</a></li>
                <li><a href="/ajustes/">⚙️ Ajustes</a></li>
                <li><a href="/mi-perfil/">👤 Mi perfil</a></li>
            </ul>

            <div class="sidebar-footer">
                <div class="status">🟢 Conectado a Omkrom</div>
                <a href="<?php echo wp_logout_url( '/login/' ); ?>" class="logout">Cerrar sesión</a>
            </div>

        </aside>

        <!-- CONTENT -->
        <main class="tomatito-content">
            <?php if ( function_exists( 'tomatito_render_ad_header' ) ) { tomatito_render_ad_header(); } ?>

            <!-- HEADER -->
            <div class="tomatito-historial-header">
                <h1>Historial Completo</h1>
                <a href="/dashboard/" class="btn-volver">← Volver al dashboard</a>
            </div>

            <!-- CONTROLOS DA BUSCA -->
            <div class="history-search-controls">
                <div class="history-search-date">
                    🗓️
                    <input type="date" id="history-search-input">
                </div>
                <div class="history-search-granularity" id="history-search-granularity">
                    <button data-granularity="day" class="active">Día</button>
                    <button data-granularity="week">Semana</button>
                    <button data-granularity="month">Mes</button>
                    <button data-granularity="year">Año</button>
                </div>
                <div class="history-search-nav">
                    <button id="history-search-prev" aria-label="Anterior">‹</button>
                    <button id="history-search-next" aria-label="Siguiente">›</button>
                </div>
            </div>

            <!-- ETIQUETA DO INTERVALO -->
            <div class="history-search-range-label" id="history-search-range-label" style="display:none;"></div>

            <!-- RESUMO -->
            <div class="history-search-summary" id="history-search-summary" style="display:none;">
                <div class="summary-card">
                    <div class="summary-label">Sesiones completadas</div>
                    <div class="summary-value" id="summary-completadas">0</div>
                </div>
                <div class="summary-card accent">
                    <div class="summary-label">Minutos enfocado</div>
                    <div class="summary-value" id="summary-minutos">0 min</div>
                </div>
            </div>

            <!-- RESULTADOS -->
            <div id="history-search-body">
                <div class="history-search-empty">Cargando historial...</div>
            </div>

            <!-- PAGINAÇÃO -->
            <nav id="history-search-pagination" class="history-pagination" aria-label="Paginación de la búsqueda"></nav>

            <!-- BOTÃO VOLVER (em baixo) -->
            <div class="historial-footer-volver">
                <a href="/dashboard/" class="btn-volver">← Volver al dashboard</a>
            </div>

            <?php if ( function_exists( 'tomatito_render_ad_footer' ) ) { tomatito_render_ad_footer(); } ?>

        </main>

    </div>

    <?php
    // O JS vai para o rodape (wp_footer) para o WordPress NAO codificar os '&' (&& -> &#038;)
    if ( ! has_action( 'wp_footer', 'tomatito_historial_print_js' ) ) {
        add_action( 'wp_footer', 'tomatito_historial_print_js' );
    }
    return ob_get_clean();
}

function tomatito_historial_print_js() {
    ?>
    <script>

    // ============================================
    // BUSCAR EN EL HISTORIAL (p\u00E1gina dedicada)
    // ============================================
    let searchGranularity = 'day';      // 'day' | 'week' | 'month' | 'year'
    let searchBaseDate     = null;       // Date selecionada
    let searchPage         = 1;

    // Calcula o intervalo [from, to] em YYYY-MM-DD a partir da data base + granularidade
    function getSearchRange() {
        if ( ! searchBaseDate ) return null;

        const d = new Date( searchBaseDate.getTime() );
        let from, to;

        if ( searchGranularity === 'day' ) {
            from = new Date( d );
            to   = new Date( d );

        } else if ( searchGranularity === 'week' ) {
            const day = d.getDay();
            const diffToMonday = ( day === 0 ) ? 6 : day - 1;
            from = new Date( d );
            from.setDate( d.getDate() - diffToMonday );
            to = new Date( from );
            to.setDate( from.getDate() + 6 );

        } else if ( searchGranularity === 'month' ) {
            from = new Date( d.getFullYear(), d.getMonth(), 1 );
            to   = new Date( d.getFullYear(), d.getMonth() + 1, 0 );

        } else {
            from = new Date( d.getFullYear(), 0, 1 );
            to   = new Date( d.getFullYear(), 11, 31 );
        }

        return { from: fmtDate( from ), to: fmtDate( to ) };
    }

    function fmtDate( date ) {
        const y = date.getFullYear();
        const m = String( date.getMonth() + 1 ).padStart( 2, '0' );
        const dd = String( date.getDate() ).padStart( 2, '0' );
        return y + '-' + m + '-' + dd;
    }

    function getRangeLabel( range ) {
        if ( searchGranularity === 'day' ) {
            return 'Mostrando: ' + range.from;
        }
        return 'Mostrando: ' + range.from + '  \u2192  ' + range.to;
    }

    function shiftSearchDate( direction ) {
        if ( ! searchBaseDate ) return;
        const d = new Date( searchBaseDate.getTime() );

        if ( searchGranularity === 'day' ) {
            d.setDate( d.getDate() + direction );
        } else if ( searchGranularity === 'week' ) {
            d.setDate( d.getDate() + ( 7 * direction ) );
        } else if ( searchGranularity === 'month' ) {
            d.setMonth( d.getMonth() + direction );
        } else {
            d.setFullYear( d.getFullYear() + direction );
        }

        searchBaseDate = d;
        const input = document.getElementById('history-search-input');
        if ( input ) input.value = fmtDate( d );

        searchPage = 1;
        runHistorySearch();
    }

    async function runHistorySearch() {
        const range = getSearchRange();
        if ( ! range ) return;

        const summaryBox = document.getElementById('history-search-summary');
        const rangeLabel = document.getElementById('history-search-range-label');
        const body       = document.getElementById('history-search-body');

        if ( rangeLabel ) {
            rangeLabel.style.display = 'block';
            rangeLabel.innerText = getRangeLabel( range );
        }

        try {
            const url = '/wp-json/tomatito/v1/history-sesiones'
                + '?date_from=' + range.from
                + '&date_to='   + range.to
                + '&page='      + searchPage
                + '&per_page=8'
                + '&nocache='   + Date.now();

            const res  = await fetch( url, {
                cache: 'no-store',
                credentials: 'include',
                headers: { 'X-WP-Nonce': wpApiSettings.nonce }
            });
            const data = await res.json();

            console.log('HISTORY SEARCH:', data);

            if ( ! data.success ) return;

            if ( summaryBox && data.summary ) {
                summaryBox.style.display = 'grid';
                document.getElementById('summary-completadas').innerText = data.summary.completadas;
                document.getElementById('summary-minutos').innerText     = data.summary.minutos + ' min';
            }

            if ( body ) {
                if ( data.data.length === 0 ) {
                    body.innerHTML = '<div class="history-search-empty">No hay sesiones en este per\u00EDodo.</div>';
                } else {
                    let html = '<div class="history-search-results">';
                    data.data.forEach( item => {
                        const minutes = Math.round( item.duration / 60 );
                        let stateClass = 'cancel';
                        let stateLabel = 'Cancelado';
                        if ( item.state === 'completed' ) { stateClass = 'ok';       stateLabel = 'Completado'; }
                        if ( item.state === 'paused' )    { stateClass = 'progress'; stateLabel = 'Pausado'; }
                        const dateObj = new Date( item.started_at );
                        const dateStr = fmtDate( dateObj );
                        const timeStr = dateObj.toTimeString().slice( 0, 5 );
                        const extra = [];
                        if ( item.proyecto_name ) extra.push( item.proyecto_name );
                        if ( item.tipo_name )     extra.push( item.tipo_name );
                        const extraText = extra.length ? ' \u00B7 ' + extra.join(' \u00B7 ') : '';
                        html +=
                            '<div class="row">' +
                                '<span>' + item.name + extraText + '</span>' +
                                '<span>' + minutes + ' min</span>' +
                                '<span class="' + stateClass + '">' + stateLabel + '</span>' +
                                '<span>' + dateStr + ' ' + timeStr + '</span>' +
                                '<button class="btn-delete-sesion" data-id="' + item.id + '" data-name="' + item.name.replace(/"/g, '&quot;') + '" title="Eliminar">\uD83D\uDDD1</button>' +
                            '</div>';
                    });
                    html += '</div>';
                    body.innerHTML = html;
                }
            }

            renderSearchPagination( data.pagination );

        } catch ( error ) {
            console.error( 'Erro na busca de hist\u00F3rico:', error );
            if ( body ) {
                body.innerHTML = '<div class="history-search-empty">Error al buscar en el historial.</div>';
            }
        }
    }

    function renderSearchPagination( pagination ) {
        const nav = document.getElementById('history-search-pagination');
        if ( ! nav ) return;
        nav.innerHTML = '';

        if ( ! pagination || pagination.total_pages <= 1 ) return;

        const page  = pagination.page;
        const total = pagination.total_pages;

        const prev = document.createElement('button');
        prev.innerText = '\u2039';
        prev.disabled = ( page <= 1 );
        prev.addEventListener('click', function() { searchPage = page - 1; runHistorySearch(); });
        nav.appendChild( prev );

        for ( let i = 1; i <= total; i++ ) {
            const btn = document.createElement('button');
            btn.innerText = i;
            if ( i === page ) btn.classList.add('active');
            btn.addEventListener('click', function() { searchPage = i; runHistorySearch(); });
            nav.appendChild( btn );
        }

        const next = document.createElement('button');
        next.innerText = '\u203A';
        next.disabled = ( page >= total );
        next.addEventListener('click', function() { searchPage = page + 1; runHistorySearch(); });
        nav.appendChild( next );
    }

    function initHistorySearch() {
        const input        = document.getElementById('history-search-input');
        const granularity  = document.getElementById('history-search-granularity');
        const prevBtn      = document.getElementById('history-search-prev');
        const nextBtn      = document.getElementById('history-search-next');

        if ( ! input ) return;

        input.addEventListener('change', function() {
            if ( ! input.value ) return;
            const parts = input.value.split('-');
            searchBaseDate = new Date( parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]) );
            searchPage = 1;
            runHistorySearch();
        });

        if ( granularity ) {
            granularity.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', function() {
                    granularity.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    searchGranularity = btn.dataset.granularity;
                    searchPage = 1;
                    if ( searchBaseDate ) runHistorySearch();
                });
            });
        }

        if ( prevBtn ) prevBtn.addEventListener('click', function() {
            if ( ! searchBaseDate ) return;
            shiftSearchDate( -1 );
        });
        if ( nextBtn ) nextBtn.addEventListener('click', function() {
            if ( ! searchBaseDate ) return;
            shiftSearchDate( +1 );
        });

// ─── ELIMINAR SESI\u00D3N ───
// ⚠️ CORRIGIDO: a confirmação agora mostra qual sessão específica está
// sendo apagada (nome vem do próprio data-name do botão, preenchido a
// partir de item.name no momento em que a linha é renderizada).
        const body = document.getElementById('history-search-body');
        if ( body ) {
            body.addEventListener('click', async function(event) {
                const btn = event.target.closest('.btn-delete-sesion');
                if ( ! btn ) return;
                const delName = btn.dataset.name || 'esta sesión';
                if ( ! (await window.tomatitoConfirm('\u00BFEst\u00E1s seguro de que quieres eliminar "' + delName + '"?')) ) return;
                const id = btn.dataset.id;
                try {
                    const res = await fetch('/wp-json/tomatito/v1/sesiones/' + id, {
                        method: 'DELETE',
                        credentials: 'include',
                        headers: { 'X-WP-Nonce': wpApiSettings.nonce }
                    });
                    const result = await res.json();
                    if ( result.success ) {
                        runHistorySearch();
                    } else {
                        alert('Error: ' + (result.message || 'No se pudo eliminar'));
                    }
                } catch (error) {
                    console.error('Erro ao eliminar sesi\u00F3n:', error);
                    alert('Error de conexi\u00F3n');
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        initHistorySearch();

        // Por defeito: abre no dia de HOJE e mostra logo os resultados
        const hoje  = new Date();
        searchBaseDate = new Date( hoje.getFullYear(), hoje.getMonth(), hoje.getDate() );
        const input = document.getElementById('history-search-input');
        if ( input ) input.value = fmtDate( searchBaseDate );
        runHistorySearch();
    });

    </script>
    <?php
}
add_shortcode( 'tomatito_historial_completo', 'tomatito_historial_completo_shortcode' );
