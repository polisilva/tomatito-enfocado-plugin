<?php

function tomatito_temporizadores_shortcode() {

    if ( ! is_user_logged_in() ) {
        return '<p>Debes iniciar sesión.</p>';
    }

    ob_start();
    ?>

    <style>
    /* ============================================== */
    /* PÁGINA TEMPORIZADORES - LIMPEZA DOS WRAPPERS   */
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

    .tomatito-temporizadores-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 80px;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 24px;
    }

    .tomatito-temporizadores-header h1 {
        font-size: 28px;
        font-weight: 600;
        margin: 0;
    }

    /* Botão "Nuevo Temporizador" */
    .btn-primary {
        background: #ff4d4d;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-primary:hover {
        background: #ff3333;
    }

    /* ============================================== */
    /* LAYOUT: TABELA + FORMULÁRIO LADO A LADO        */
    /* ============================================== */

    .temp-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        align-items: start;
    }

    .temp-layout.form-open {
        grid-template-columns: 1fr 360px;
    }

    .temp-main {
        min-width: 0;
    }

    /* ============================================== */
    /* BARRA DE FILTROS                               */
    /* ============================================== */

    .temp-filters {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        align-items: center;
    }

    .temp-filters input {
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        background: #fff;
        flex: 1;
    }

    .temp-filters input:focus {
        outline: none;
        border-color: #ff4d4d;
    }

    /* ============================================================ */
    /* SELECT PERSONALIZADO (estilo Omkrom Hub)                     */
    /* ============================================================ */
    /* Porquê não é só CSS: o painel que se abre num <select> nativo
       é desenhado pelo SISTEMA OPERATIVO (aquele cinzento com azul do
       macOS) e nenhum navegador o deixa estilizar. Por isso o <select>
       real continua no HTML — só fica invisível — e por cima dele
       desenhamos um botão + um painel em HTML, que já podemos vestir.
       O <select> escondido continua a ser a fonte de verdade: os
       filtros, o saveTemporizador() e o preview de som leem e escrevem
       nele exactamente como antes, sem alterações. */

    .tsel {
        position: relative;
        display: inline-block;
        min-width: 180px;
    }

    /* o <select> real: continua no DOM (guarda o valor), mas invisível */
    .tsel > select.tsel-native {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        pointer-events: none;
        margin: 0;
        border: 0;
    }

    /* a "caixinha" fechada */
    .tsel-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        width: 100%;
        padding: 11px 14px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        color: #111827;
        text-align: left;
        cursor: pointer;
        transition: border-color 0.15s, box-shadow 0.15s;
    }

    .tsel-btn:hover:not(:disabled) {
        border-color: #d1d5db;
    }

	.tsel.open .tsel-btn,
	.tsel-btn:focus,
	.tsel-btn:focus-visible {
		outline: none !important;
		border-color: #ff4d4d !important;
		box-shadow: 0 0 0 3px rgba(255,77,77,0.12) !important;
    }

    .tsel-btn:disabled {
        background: #f9fafb;
        color: #9ca3af;
        cursor: not-allowed;
    }

    .tsel-btn .tsel-label {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .tsel-btn .tsel-arrow {
        flex: 0 0 auto;
        color: #6b7280;
        transition: transform 0.2s;
    }

    .tsel.open .tsel-btn .tsel-arrow {
        transform: rotate(180deg);
    }

    /* o painel aberto */
    .tsel-panel {
        display: none;
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        z-index: 50;
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 12px;
        box-shadow: 0 16px 40px rgba(15,23,42,0.16);
        padding: 6px;
        max-height: 280px;
        overflow-y: auto;
    }

    .tsel.open .tsel-panel {
        display: block;
    }

    /* abre para CIMA quando não há espaço em baixo */
    .tsel.drop-up .tsel-panel {
        top: auto;
        bottom: calc(100% + 6px);
    }

    .tsel-option {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding: 9px 12px;
        border: none;
        background: transparent;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        color: #111827;
        text-align: left;
        cursor: pointer;
        transition: background 0.12s;
    }

    .tsel-option:hover {
        background: #f3f4f6;
    }

    .tsel-option.is-selected {
        background: #ff4d4d;
        color: #fff;
        font-weight: 500;
    }

    .tsel-option.is-selected:hover {
        background: #ff3d3d;
    }

    .tsel-option .tsel-check {
        width: 14px;
        flex: 0 0 auto;
        opacity: 0;
        font-size: 13px;
    }

    .tsel-option.is-selected .tsel-check {
        opacity: 1;
    }

    /* ============================================== */
    /* TABELA DE TEMPORIZADORES                       */
    /* ============================================== */

    .temp-table {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.04);
    }

    .temp-table-header {
        display: grid;
        grid-template-columns: 1fr 2fr 1fr 1fr;
        padding: 14px 20px;
        background: #f8fafc;
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
        border-bottom: 1px solid #eee;
    }

    .temp-table .temp-row {
        display: grid;
        grid-template-columns: 1fr 2fr 1fr 1fr;
        align-items: center;
        padding: 14px 20px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }

    .temp-table .temp-row:last-child {
        border-bottom: none;
    }

    .temp-table .temp-row .duration-icon {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }

    .temp-table .temp-row .name {
        font-weight: 500;
    }

    .temp-table .temp-row .last-used {
        color: #6b7280;
        font-size: 13px;
    }

    .temp-table .loading,
    .temp-table .empty {
        text-align: center;
        color: #9ca3af;
        padding: 30px;
        font-size: 14px;
    }

    /* ============================================== */
    /* CONTADOR */
    /* ============================================== */

    .temp-counter {
        color: #6b7280;
        font-size: 13px;
        margin: 16px 0 0;
    }

    /* ============================================== */
    /* AÇÕES NA LINHA                                 */
    /* ============================================== */

    .temp-row .actions {
        display: flex;
        gap: 6px;
        align-items: center;
    }

	/* ⚠️ ALTERADO: font-size maior + line-height:1 para o triângulo ficar
	   grande e sem "quadrado azul" (evita a apresentação emoji do ▶) */
	.btn-iniciar,
	.btn-edit,
	.btn-delete {
		width: 36px;
		height: 36px;
		padding: 0;
		border-radius: 8px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		font-size: 18px;
		line-height: 1;
		cursor: pointer;
		transition: 0.2s;
	}

	.btn-iniciar {
		background: #ff4d4d;
		color: white;
		border: none;
	}

	.btn-iniciar:hover {
		background: #ff3333;
	}

	/* S\u00F3 o tri\u00E2ngulo fica maior, dentro da mesma caixa de 36x36 */
	.btn-iniciar .play-icon {
		font-size: 22px;
		line-height: 1;
	}

	.btn-edit,
	.btn-delete {
		background: transparent;
		border: 1px solid #e5e7eb;
	}

	.btn-edit:hover {
		background: #f3f4f6;
		border-color: #ff4d4d;      
	}

    .btn-delete:hover {
        background: #fee2e2;
        border-color: #ef4444;
    }

    /* ============================================== */
    /* BOTÃO DUPLICADO ABAIXO                         */
    /* ============================================== */

    .btn-new-secondary {
        display: inline-block;
        margin: 16px 0 0;
    }

    /* ============================================== */
    /* FORMULÁRIO LATERAL                             */
    /* ============================================== */

    .temp-form {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.04);
    }

    /* ============================================================ */
    /* DRAWER DE TEMPORIZADORES — mesmo UX já aprovado em Alarmas   */
    /* Mantém as cores do Tomatito; altera apenas a experiência.    */
    /* ============================================================ */
    .temp-drawer-backdrop {
        position: fixed;
        inset: 0;
        z-index: 99998;
        background: rgba(20,24,35,0.38);
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.22s ease, visibility 0.22s ease;
    }

    .temp-drawer-backdrop.is-open {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .temp-form {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        z-index: 99999;
        width: min(460px, 100vw);
        height: 100vh;
        box-sizing: border-box;
        margin: 0;
        border: 0;
        border-radius: 0;
        padding: 24px;
        background: #fff;
        box-shadow: -20px 0 60px rgba(20,24,35,0.18);
        overflow-y: auto;
        transform: translateX(100%);
        visibility: hidden;
        pointer-events: none;
        transition: transform 0.22s ease, visibility 0.22s ease;
    }

    .temp-form.is-open {
        transform: translateX(0);
        visibility: visible;
        pointer-events: auto;
    }

    body.tomatito-temp-drawer-open {
        overflow: hidden;
    }

    .temp-form .drawer-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 22px;
    }

    .temp-form .drawer-head h2 {
        margin: 0;
        font-size: 20px;
    }

    .temp-form .drawer-close {
        width: 36px;
        height: 36px;
        padding: 0;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        color: #6b7280;
        font-size: 24px;
        line-height: 1;
        cursor: pointer;
        flex: 0 0 auto;
    }

    .temp-form .drawer-close:hover {
        background: #f9fafb;
        color: #111827;
    }

    .temp-form h2 {
        font-size: 20px;
        margin: 0 0 20px;
    }

    .temp-form h3 {
        font-size: 14px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 24px 0 12px;
    }

    .temp-form .form-group {
        margin-bottom: 16px;
    }

    .temp-form .form-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 12px;
    }

    .temp-form label {
        font-size: 14px;
        color: #374151;
        flex: 1;
    }

    .temp-form input[type="text"],
    .temp-form input[type="number"] {
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        background: #fff;
        width: 100%;
    }

    .temp-form input:focus {
        outline: none;
        border-color: #ff4d4d;
    }

    .temp-form .input-with-unit {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .temp-form .input-with-unit input {
        width: 80px !important;
        text-align: center;
    }

    .temp-form .input-with-unit .unit {
        font-size: 13px;
        color: #6b7280;
    }

    /* ============================================== */
    /* DURAÇÃO EM HH : MM : SS                        */
    /* ============================================== */
    .duration-hms {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        margin-bottom: 16px;
    }
    .duration-hms .hms-field {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .duration-hms .hms-field input {
        width: 60px !important;
        text-align: center;
        padding: 10px 6px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 16px;
        font-family: inherit;
    }
    .duration-hms .hms-field input:focus {
        outline: none;
        border-color: #ff4d4d;
    }
    .duration-hms .hms-label {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .duration-hms .hms-sep {
        font-size: 20px;
        font-weight: 600;
        color: #9ca3af;
        padding-top: 8px;
    }

    /* Controle de som: select + botão de preview lado a lado */
    /* ⚠️ A label desta linha NÃO pode crescer (flex:1), senão empurra o
       select + botão para fora do cartão. Por isso a linha do som usa a
       classe .sound-row, onde a label fica do tamanho do texto e é o
       conjunto select+botão que ocupa o espaço restante. */
    .temp-form .form-row.sound-row {
        gap: 12px;
    }
    .temp-form .form-row.sound-row label {
        flex: 0 0 auto;
    }
    .sound-control {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1 1 auto;
        min-width: 0;
    }
    .sound-control .tsel {
        flex: 1 1 auto;
        min-width: 0;
    }
    .btn-preview-sound {
        flex: 0 0 auto;
        width: 44px;
        height: 44px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-preview-sound:hover {
        background: #f3f4f6;
        border-color: #ff4d4d;
    }

    .temp-form .form-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }

    .temp-form .form-actions button {
        width: 100%;
    }

    .btn-launch {
        background: #2ecc71;
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-launch:hover {
        background: #27ae60;
    }

    .btn-secondary {
        background: #ef4444;
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-secondary:hover {
        background: #dc2626;
    }

    .btn-warning {
        background: #f5b942;
        color: #1f2937;
        border: none;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-warning:hover {
        background: #e0a52e;
    }

    /* ============================================== */
    /* PAGINAÇÃO                                      */
    /* ============================================== */
    .temp-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin-top: 16px;
        flex-wrap: wrap;
    }
    .temp-pagination button {
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
    .temp-pagination button:hover:not(:disabled):not(.active) {
        background: #f3f4f6;
        border-color: #d1d5db;
    }
    .temp-pagination button.active {
        background: #ff4d4d;
        color: #fff;
        border-color: #ff4d4d;
        font-weight: 600;
    }
    .temp-pagination button:disabled {
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
                <li><a class="active" href="/temporizadores/">⏱️ Temporizadores</a></li>
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
            <div class="tomatito-temporizadores-header">
                <h1>Temporizadores</h1>
                <button class="btn-primary" id="btn-new-temp">+ Nuevo Temporizador</button>
            </div>

            <!-- LAYOUT -->
            <div class="temp-layout">

                <div class="temp-main">

                    <!-- BARRA DE FILTROS -->
                    <div class="temp-filters">
                        <input type="text" id="filter-search" placeholder="🔍 Buscar...">
                        <select id="filter-type">
                            <option value="todos">Todos</option>
                            <option value="activos">Activos</option>
                            <option value="inactivos">Inactivos</option>
                        </select>
                        <select id="filter-sort">
                            <option value="recientes">Ordenar por Recientes</option>
                            <option value="duracion">Ordenar por Duración</option>
                            <option value="nombre">Ordenar por Nombre</option>
                        </select>
                    </div>

                    <!-- TABELA -->
                    <div class="temp-table">

                        <div class="temp-table-header">
                            <span>Duración</span>
                            <span>Nombre</span>
                            <span>Último uso</span>
                            <span>Acciones</span>
                        </div>

                        <div id="temp-list">
                            <p class="loading">Cargando temporizadores...</p>
                        </div>

                    </div>

                    <!-- PAGINAÇÃO -->
                    <nav id="temp-pagination" class="temp-pagination" aria-label="Paginación de temporizadores"></nav>

                    <!-- BOTÃO DUPLICADO -->
                    <button class="btn-primary btn-new-secondary" id="btn-new-temp-2">+ Nuevo Temporizador</button>

                    <!-- CONTADOR -->
                    <p class="temp-counter" id="temp-counter"></p>

                </div>

                <!-- DRAWER DE CRIAÇÃO/EDIÇÃO -->
                <div class="temp-drawer-backdrop" id="temp-drawer-backdrop"></div>

                <aside class="temp-form" id="temp-form" aria-hidden="true">

                    <div class="drawer-head">
                        <div>
                            <h2 id="form-title">Crear Temporizador</h2>
                        </div>
                        <button type="button" class="drawer-close" id="btn-close-temp" aria-label="Cerrar">×</button>
                    </div>

                    <input type="hidden" id="field-id" value="">

                    <div class="form-group">
                        <input type="text" id="field-name" placeholder="Trabajo profundo">
                    </div>

                    <h3>Configuración</h3>

                    <div class="form-row">
                        <label>Duración</label>
                    </div>
                    <div class="duration-hms">
                        <div class="hms-field">
                            <input type="number" id="field-hours" value="0" min="0" max="23">
                            <span class="hms-label">HH</span>
                        </div>
                        <span class="hms-sep">:</span>
                        <div class="hms-field">
                            <input type="number" id="field-minutes" value="20" min="0" max="59">
                            <span class="hms-label">MM</span>
                        </div>
                        <span class="hms-sep">:</span>
                        <div class="hms-field">
                            <input type="number" id="field-seconds" value="0" min="0" max="59">
                            <span class="hms-label">SS</span>
                        </div>
                    </div>

                    <div class="form-row sound-row">
                        <label>Sonido</label>
                        <div class="sound-control">
                            <select id="field-sound" class="sound-select">
                                <option value="default">Predeterminado (según Ajustes)</option>
                                <option value="clasico">Clásico</option>
                                <option value="campana">Campana</option>
                                <option value="digital">Digital</option>
                                <option value="suave">Suave</option>
                                <option value="silent">Silencioso</option>
                                <option value="vibracion">Vibración</option>
                            </select>
                            <button type="button" class="btn-preview-sound" data-target="field-sound" title="Escuchar">&#9654;</button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn-warning" id="btn-save-temp">Guardar</button>
                        <button class="btn-launch" id="btn-save-launch-temp">▶ Guardar y lanzar</button>
                        <button class="btn-secondary" id="btn-cancel-temp">Cancelar</button>
                    </div>

                </aside>

            </div>

            <?php if ( function_exists( 'tomatito_render_ad_footer' ) ) { tomatito_render_ad_footer(); } ?>

        </main>

    </div>

    <script>

// ============================================
// URL BASE DOS SONS (pra tocar o preview no formulário)
// ============================================
var TOMATITO_SOUNDS_URL = '<?php echo esc_url( content_url( 'uploads/2026/07/' ) ); ?>';

// ============================================================
// SELECT PERSONALIZADO — "veste" todos os <select> desta página
// ============================================================
// Regra de ouro: NÃO substitui o <select>; envolve-o. O elemento
// nativo continua no DOM (invisível) e continua a ser quem guarda o
// valor — por isso todo o código de baixo (filtros, guardar, editar,
// preview de som) funciona exactamente como antes.
// Ao escolher uma opção disparamos um evento 'change' no <select>,
// que é o mesmo evento que os filtros já escutavam — não foi preciso
// mudar nenhum listener.

var TSEL_ARROW =
    '<svg class="tsel-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" ' +
    'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ' +
    'aria-hidden="true" focusable="false"><polyline points="6 9 12 15 18 9"></polyline></svg>';

function tselCloseAll(exceto) {
    document.querySelectorAll('.tsel.open').forEach(function(w) {
        if (w !== exceto) w.classList.remove('open');
    });
}

function enhanceSelect(select) {
    if (select.dataset.tselDone) return;
    select.dataset.tselDone = '1';

    var wrap = document.createElement('div');
    wrap.className = 'tsel';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
    select.classList.add('tsel-native');
    select.setAttribute('tabindex', '-1');

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'tsel-btn';
    btn.innerHTML = '<span class="tsel-label"></span>' + TSEL_ARROW;
    btn.disabled = select.disabled;
    wrap.appendChild(btn);

    var panel = document.createElement('div');
    panel.className = 'tsel-panel';
    panel.setAttribute('role', 'listbox');
    Array.prototype.forEach.call(select.options, function(opt) {
        var item = document.createElement('button');
        item.type = 'button';
        item.className = 'tsel-option';
        item.dataset.value = opt.value;
        item.setAttribute('role', 'option');
        var check = document.createElement('span');
        check.className = 'tsel-check';
        check.textContent = '\u2713';
        var text = document.createElement('span');
        text.textContent = opt.textContent;
        item.appendChild(check);
        item.appendChild(text);
        panel.appendChild(item);
    });
    wrap.appendChild(panel);

    function refresh() {
        var opt = select.options[select.selectedIndex];
        wrap.querySelector('.tsel-label').textContent = opt ? opt.textContent : '';
        panel.querySelectorAll('.tsel-option').forEach(function(el) {
            var sel = (el.dataset.value === select.value);
            el.classList.toggle('is-selected', sel);
            el.setAttribute('aria-selected', sel ? 'true' : 'false');
        });
    }
    select._tselRefresh = refresh;
    refresh();

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        var vaiAbrir = !wrap.classList.contains('open');
        tselCloseAll(wrap);
        wrap.classList.toggle('open', vaiAbrir);
        if (vaiAbrir) {
            // se não houver espaço em baixo, abre para cima
            var espacoAbaixo = window.innerHeight - btn.getBoundingClientRect().bottom;
            wrap.classList.toggle('drop-up', espacoAbaixo < 300);
        }
    });

    panel.addEventListener('click', function(e) {
        var item = e.target.closest('.tsel-option');
        if (!item) return;
        select.value = item.dataset.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        refresh();
        wrap.classList.remove('open');
    });

    wrap.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') wrap.classList.remove('open');
    });
}

// Chamar sempre que valores forem mudados por código (ex.: abrir o
// formulário em modo Editar), senão o botão visível continuaria a
// mostrar o valor anterior.
function syncCustomSelects() {
    document.querySelectorAll('select.tsel-native').forEach(function(s) {
        if (typeof s._tselRefresh === 'function') s._tselRefresh();
    });
}

function initCustomSelects() {
    document.querySelectorAll('.temp-layout select').forEach(enhanceSelect);
}

// clicar fora fecha qualquer painel aberto
document.addEventListener('click', function() { tselCloseAll(null); });

// ============================================
    // VARIÁVEL GLOBAL
    // ============================================
    let allTemporizadores = [];

    // Paginação
    const PER_PAGE_TEMP = 10;
    let currentTempPage = 1;

    // ============================================
    // CARREGAR / RENDERIZAR
    // ============================================

async function loadTemporizadores() {
        try {
            const res  = await fetch('/wp-json/tomatito/v1/temporizadores?nocache=' + Date.now(), {
				cache: 'no-store',
				credentials: 'include',
				headers: { 'X-WP-Nonce': wpApiSettings.nonce }
			});
            const data = await res.json();

            console.log('TEMPORIZADORES:', data);

            if (!data.success) return;

            allTemporizadores = data.data;
            renderTemporizadores();

        } catch (error) {
            console.error('Erro ao carregar temporizadores:', error);
            const list = document.getElementById('temp-list');
            if (list) list.innerHTML = '<p class="empty">Error al cargar temporizadores.</p>';
        }
    }

    function renderTemporizadores() {
        const list       = document.getElementById('temp-list');
        const counter    = document.getElementById('temp-counter');
        const search     = document.getElementById('filter-search').value.toLowerCase().trim();
        const sortBy     = document.getElementById('filter-sort').value;

        if (!list) return;

        // Filtrar pela pesquisa
        let filtered = allTemporizadores.filter(t => {
            if (search && !t.name.toLowerCase().includes(search)) return false;
            return true;
        });

        // Ordenar
        filtered.sort((a, b) => {
            if (sortBy === 'nombre') {
                return a.name.localeCompare(b.name);
            }
            if (sortBy === 'duracion') {
                return (parseInt(b.duration) || 0) - (parseInt(a.duration) || 0);
            }
            const dateA = a.last_used_at ? new Date(a.last_used_at).getTime() : 0;
            const dateB = b.last_used_at ? new Date(b.last_used_at).getTime() : 0;
            return dateB - dateA;
        });

        // Atualizar contador
        counter.innerText = `Mostrando ${filtered.length} de ${allTemporizadores.length} temporizadores guardados`;

        // Paginar
        const totalTempPages = Math.max(1, Math.ceil(filtered.length / PER_PAGE_TEMP));
        if (currentTempPage > totalTempPages) currentTempPage = totalTempPages;
        const tempStart = (currentTempPage - 1) * PER_PAGE_TEMP;
        const pageItems = filtered.slice(tempStart, tempStart + PER_PAGE_TEMP);

        // Mostrar na tabela
        list.innerHTML = '';

        if (filtered.length === 0) {
            list.innerHTML = '<p class="empty">No se han encontrado temporizadores.</p>';
            renderTempPagination(0);
            return;
        }

        pageItems.forEach(item => {
            const row = document.createElement('div');
            row.classList.add('temp-row');

            row.innerHTML = `
                <span class="duration-icon">⏱️ ${item.duration_label}</span>
                <span class="name">${item.name}</span>
                <span class="last-used">${item.last_used_label}</span>
                <span class="actions">
                    <button class="btn-iniciar" data-id="${item.id}" title="Iniciar"><span class="play-icon">\u25B6\uFE0E</span></button>
                    <button class="btn-edit"    data-id="${item.id}" title="Editar">✏️</button>
                    <button class="btn-delete"  data-id="${item.id}" title="Eliminar">🗑️</button>
                </span>
            `;

            list.appendChild(row);
        });

        // Renderizar paginação
        renderTempPagination(totalTempPages);
    }

    // ============================================
    // PAGINAÇÃO — renderizar botões
    // ============================================
    function renderTempPagination(totalPages) {
        const nav = document.getElementById('temp-pagination');
        if (!nav) return;
        nav.innerHTML = '';
        if (totalPages <= 1) return;

        const prev = document.createElement('button');
        prev.innerText = '‹';
        prev.disabled = currentTempPage <= 1;
        prev.addEventListener('click', function() {
            currentTempPage--;
            renderTemporizadores();
        });
        nav.appendChild(prev);

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.innerText = i;
            if (i === currentTempPage) btn.classList.add('active');
            btn.addEventListener('click', function() {
                currentTempPage = i;
                renderTemporizadores();
            });
            nav.appendChild(btn);
        }

        const next = document.createElement('button');
        next.innerText = '›';
        next.disabled = currentTempPage >= totalPages;
        next.addEventListener('click', function() {
            currentTempPage++;
            renderTemporizadores();
        });
        nav.appendChild(next);
    }

    // Reagir a mudanças nos filtros (volta para página 1)
    function onTempFilterChange() {
        currentTempPage = 1;
        renderTemporizadores();
    }
    document.getElementById('filter-search').addEventListener('input', onTempFilterChange);
    document.getElementById('filter-type').addEventListener('change', onTempFilterChange);
    document.getElementById('filter-sort').addEventListener('change', onTempFilterChange);

    // ============================================
    // FORMULÁRIO: ABRIR / FECHAR
    // ============================================

    function openTempForm( temp = null ) {
        const form     = document.getElementById('temp-form');
        const backdrop = document.getElementById('temp-drawer-backdrop');
        const title    = document.getElementById('form-title');

        form.classList.add('is-open');
        form.setAttribute('aria-hidden', 'false');
        form.inert = false;
        backdrop.classList.add('is-open');
        document.body.classList.add('tomatito-temp-drawer-open');

        if ( temp ) {
            // Modo EDITAR
            title.innerText = 'Editar Temporizador';
            document.getElementById('field-id').value       = temp.id;
            document.getElementById('field-name').value     = temp.name;
            document.getElementById('field-sound').value    = temp.sound || 'default';

            // Converte segundos totais em HH / MM / SS para mostrar no formulário
            const totalSeconds = parseInt(temp.duration, 10) || 0;
            document.getElementById('field-hours').value   = Math.floor(totalSeconds / 3600);
            document.getElementById('field-minutes').value = Math.floor((totalSeconds % 3600) / 60);
            document.getElementById('field-seconds').value = totalSeconds % 60;
        } else {
            // Modo CRIAR
            title.innerText = 'Crear Temporizador';
            document.getElementById('field-id').value       = '';
            document.getElementById('field-name').value     = '';
            document.getElementById('field-sound').value    = 'default';
            document.getElementById('field-hours').value    = 0;
            document.getElementById('field-minutes').value  = 20;
            document.getElementById('field-seconds').value  = 0;
        }

        // Atualiza o texto do selector personalizado.
        syncCustomSelects();
    }

    function closeTempForm() {
        const form     = document.getElementById('temp-form');
        const backdrop = document.getElementById('temp-drawer-backdrop');

        form.classList.remove('is-open');
        form.setAttribute('aria-hidden', 'true');
        form.inert = true;
        backdrop.classList.remove('is-open');
        document.body.classList.remove('tomatito-temp-drawer-open');
    }

    function handleNewTempClick() {
        openTempForm();
    }

    document.getElementById('btn-new-temp').addEventListener('click', handleNewTempClick);
    document.getElementById('btn-new-temp-2').addEventListener('click', handleNewTempClick);

    document.getElementById('btn-cancel-temp').addEventListener('click', closeTempForm);
    document.getElementById('btn-close-temp').addEventListener('click', closeTempForm);
    document.getElementById('temp-drawer-backdrop').addEventListener('click', closeTempForm);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const form = document.getElementById('temp-form');
            if (form && form.classList.contains('is-open')) closeTempForm();
        }
    });

    document.getElementById('temp-form').inert = true;

    // ============================================
    // PREVIEW DE SOM (botão "Escuchar" no formulário)
    // ============================================
    let previewAudio = null;

    function playPreview(soundName) {
        // 'default' n\u00E3o \u00E9 um arquivo de som \u2014 resolve pro que est\u00E1 configurado
        // em Ajustes pra temporizadores antes de tocar
        if (soundName === 'default') {
            try {
                const ajustes = JSON.parse(localStorage.getItem('tomatito_ajustes') || '{}');
                soundName = ajustes['sonido_temporizador'] || 'digital';
            } catch (e) {
                soundName = 'digital';
            }
        }

        if (soundName === 'silent') return;

        if (soundName === 'vibracion') {
            if (navigator.vibrate) {
                navigator.vibrate([200, 100, 200]);
            } else {
                window.tomatitoAlert('Este dispositivo/navegador no soporta vibraci\u00f3n');
            }
            return;
        }

        if (previewAudio) {
            previewAudio.pause();
            previewAudio.currentTime = 0;
        }

        previewAudio = new Audio(TOMATITO_SOUNDS_URL + soundName + '.mp3');
        previewAudio.play().catch(err => {
            console.error('No se pudo reproducir el sonido:', err);
        });
    }

    document.querySelectorAll('.btn-preview-sound').forEach(btn => {
        btn.addEventListener('click', function() {
            const select = document.getElementById(btn.dataset.target);
            if (select) playPreview(select.value);
        });
    });

    // ============================================
    // FORMULÁRIO: GUARDAR
    // ============================================

    async function saveTemporizador() {

        const id = document.getElementById('field-id').value;

        // Lê HH / MM / SS e converte para segundos totais antes de enviar
        const hours   = parseInt( document.getElementById('field-hours').value,   10 ) || 0;
        const minutes = parseInt( document.getElementById('field-minutes').value, 10 ) || 0;
        const seconds = parseInt( document.getElementById('field-seconds').value, 10 ) || 0;
        const totalSeconds = ( hours * 3600 ) + ( minutes * 60 ) + seconds;

        const data = {
            name:     document.getElementById('field-name').value.trim(),
            duration: totalSeconds,
            sound:    document.getElementById('field-sound').value,
        };

        if ( ! data.name ) {
            await window.tomatitoAlert('El nombre es obligatorio');
            return;
        }

        if ( totalSeconds <= 0 ) {
            await window.tomatitoAlert('La duración debe ser mayor que 0');
            return;
        }

        const isEdit = !!id;
        const url    = isEdit
            ? `/wp-json/tomatito/v1/temporizadores/${id}`
            : `/wp-json/tomatito/v1/temporizadores`;
        const method = isEdit ? 'PUT' : 'POST';

        try {
           const res    = await fetch(url, {
    method:  method,
    credentials: 'include',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body:    JSON.stringify(data),
});
            const result = await res.json();

            console.log( isEdit ? 'PUT result:' : 'POST result:', result );

            if ( ! result.success ) {
                await window.tomatitoAlert('Error: ' + (result.message || 'No se pudo guardar el temporizador'));
                return;
            }

            await loadTemporizadores();

            return result.data ? result.data.id : id;

        } catch (error) {
            console.error('Erro ao guardar temporizador:', error);
            await window.tomatitoAlert('Error de conexión al guardar el temporizador');
        }
    }

    // Botão "Guardar"
    document.getElementById('btn-save-temp').addEventListener('click', async function() {
        const id = await saveTemporizador();
        if (id) {
            closeTempForm();
        }
    });

    // Botão "Guardar y lanzar"
    document.getElementById('btn-save-launch-temp').addEventListener('click', async function() {
        const id = await saveTemporizador();
        if (id) {
            try {
               const res    = await fetch(`/wp-json/tomatito/v1/temporizadores/${id}/start`, {
					method: 'POST',
					credentials: 'include',
					headers: { 'X-WP-Nonce': wpApiSettings.nonce }
				});
                const result = await res.json();

                if (result.success) {
                    window.location.href = '/dashboard/';
                } else {
                    await window.tomatitoAlert('Temporizador guardado, pero no se pudo iniciar: ' + (result.message || ''));
                    closeTempForm();
                }
            } catch (error) {
                console.error('Erro ao iniciar:', error);
                await window.tomatitoAlert('Temporizador guardado, pero error de conexión al iniciar');
                closeTempForm();
            }
        }
    });

    // ============================================
    // AÇÕES NAS LINHAS: INICIAR / EDITAR / ELIMINAR
    // ============================================

    document.getElementById('temp-list').addEventListener('click', async function(event) {

        const target = event.target.closest('button');
        if (!target) return;

        const id = target.dataset.id;
        if (!id) return;

        // ─── INICIAR ───
        if ( target.matches('.btn-iniciar') ) {
            try {
				const res    = await fetch(`/wp-json/tomatito/v1/temporizadores/${id}/start`, {
					method: 'POST',
					credentials: 'include',
					headers: { 'X-WP-Nonce': wpApiSettings.nonce }
				});
                const result = await res.json();

                console.log('START result:', result);

                if ( result.success ) {
                    window.location.href = '/dashboard/';
                } else {
                    await window.tomatitoAlert('Error: ' + (result.message || 'No se pudo iniciar el temporizador'));
                }

            } catch (error) {
                console.error('Erro ao iniciar:', error);
                await window.tomatitoAlert('Error de conexión al iniciar el temporizador');
            }
        }

        // ─── EDITAR ───
        if ( target.matches('.btn-edit') ) {
            const temp = allTemporizadores.find( t => t.id == id );
            if ( temp ) {
                openTempForm( temp );
            }
        }

        // ─── ELIMINAR ───
        if ( target.matches('.btn-delete') ) {

		const delTemp = allTemporizadores.find( t => t.id == id );
		const delName = delTemp ? delTemp.name : 'este temporizador';
		if ( ! (await window.tomatitoConfirm('¿Estás seguro de que quieres eliminar "' + delName + '"?')) ) return;

            try {
                const res    = await fetch(`/wp-json/tomatito/v1/temporizadores/${id}`, {
					method: 'DELETE',
					credentials: 'include',
					headers: { 'X-WP-Nonce': wpApiSettings.nonce }
				});
                const result = await res.json();

                console.log('DELETE result:', result);

                if ( result.success ) {
                    await loadTemporizadores();
                } else {
                    await window.tomatitoAlert('Error: ' + (result.message || 'No se pudo eliminar'));
                }

            } catch (error) {
                console.error('Erro ao eliminar:', error);
                await window.tomatitoAlert('Error de conexión al eliminar');
            }
        }
    });

    // ============================================
    // INICIALIZAÇÃO
    // ============================================

    document.addEventListener('DOMContentLoaded', function() {
        initCustomSelects();   // ⚠️ NOVO: veste os selects (filtros + formulário)
        loadTemporizadores();

        // Se vieram do dashboard com ?new=temporizador
        const params = new URLSearchParams(window.location.search);
        if ( params.get('new') === 'temporizador' ) {
            openTempForm();
        }
    });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode( 'tomatito_temporizadores', 'tomatito_temporizadores_shortcode' );
