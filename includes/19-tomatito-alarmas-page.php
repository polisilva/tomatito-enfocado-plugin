<?php

function tomatito_alarmas_shortcode() {

    if ( ! is_user_logged_in() ) {
        return '<p>Debes iniciar sesión.</p>';
    }

    ob_start();
    ?>

    <style>
    /* ============================================== */
    /* PÁGINA ALARMAS - LIMPEZA DOS WRAPPERS          */
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

    .tomatito-alarmas-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 80px;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 24px;
    }

    .tomatito-alarmas-header h1 {
        font-size: 28px;
        font-weight: 600;
        margin: 0;
    }

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
    /* LAYOUT                                         */
    /* ============================================== */

    .alarmas-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        align-items: start;
    }

    .alarmas-main {
        min-width: 0;
    }

    /* ============================================== */
    /* DRAWER DE ALARMAS — UX REFERÊNCIA OMKROM HUB  */
    /* Mantém as cores do Tomatito; altera apenas UX. */
    /* ============================================== */

    .alarmas-drawer-backdrop {
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

    .alarmas-drawer-backdrop.is-open {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .alarmas-form {
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

    .alarmas-form.is-open {
        transform: translateX(0);
        visibility: visible;
        pointer-events: auto;
    }

    body.tomatito-alarmas-drawer-open {
        overflow: hidden;
    }

    .alarmas-form .drawer-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 22px;
    }

    .alarmas-form .drawer-head h2 {
        margin: 0;
        font-size: 20px;
    }

    .alarmas-form .drawer-close {
        flex: 0 0 auto;
        width: 38px;
        height: 38px;
        padding: 0;
        display: grid;
        place-items: center;
        border: 1px solid #e5e7eb;
        border-radius: 11px;
        background: #fff;
        color: #374151;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        transition: 0.2s;
    }

    .alarmas-form .drawer-close:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }

    /* ============================================== */
    /* BARRA DE FILTROS                               */
    /* ============================================== */

    .alarmas-filters {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        align-items: center;
    }

    .alarmas-filters input {
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        background: #fff;
        flex: 1;
    }

    .alarmas-filters input:focus {
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
       O <select> escondido continua a ser a fonte de verdade: o
       renderAlarmas(), o saveAlarma() e o preview de som leem e escrevem
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
    /* TABELA DE ALARMAS                              */
    /* ============================================== */

    .alarmas-table {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.04);
    }

    .alarmas-table-header {
        display: grid;
        grid-template-columns: 1.5fr 1.2fr 1.5fr 1fr 1fr;
        padding: 14px 20px;
        background: #f8fafc;
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
        border-bottom: 1px solid #eee;
    }

    .alarmas-table .alarma-row {
        display: grid;
        grid-template-columns: 1.5fr 1.2fr 1.5fr 1fr 1fr;
        align-items: center;
        padding: 14px 20px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }

    .alarmas-table .alarma-row:last-child {
        border-bottom: none;
    }

    .alarmas-table .alarma-row.inactive .time,
    .alarmas-table .alarma-row.inactive .name,
    .alarmas-table .alarma-row.inactive .repeat {
        color: #9ca3af;
    }

    .alarmas-table .name {
        font-weight: 500;
    }

    .alarmas-table .repeat {
        color: #6b7280;
        font-size: 13px;
    }

    .alarmas-table .loading,
    .alarmas-table .empty {
        text-align: center;
        color: #9ca3af;
        padding: 30px;
        font-size: 14px;
    }

    /* ============================================== */
    /* TOGGLE SWITCH                                  */
    /* ============================================== */

    .toggle-switch {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .toggle-switch input[type="checkbox"] {
        appearance: none;
        -webkit-appearance: none;
        width: 44px;
        height: 24px;
        background: #d1d5db;
        border-radius: 12px;
        position: relative;
        cursor: pointer;
        transition: background 0.2s;
        margin: 0;
    }

    .toggle-switch input[type="checkbox"]::before {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 20px;
        height: 20px;
        background: #fff;
        border-radius: 50%;
        transition: transform 0.2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .toggle-switch input[type="checkbox"]:checked {
        background: #2ecc71;
    }

    .toggle-switch input[type="checkbox"]:checked::before {
        transform: translateX(20px);
    }

    .toggle-switch .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #d1d5db;
    }

    .toggle-switch input[type="checkbox"]:checked + .status-dot {
        background: #2ecc71;
    }

    /* ============================================== */
    /* AÇÕES NA LINHA                                 */
    /* ============================================== */

    .alarma-row .actions {
        display: flex;
        gap: 6px;
        align-items: center;
    }

	.btn-edit,
	.btn-delete {
		background: transparent;
		border: 1px solid #e5e7eb;
		padding: 5px 10px;          
		border-radius: 8px;
		font-size: 18px;           
		line-height: 1;             
		cursor: pointer;
		transition: 0.2s;
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
    /* BOTÃO DUPLICADO + CONTADOR + MENSAGEM          */
    /* ============================================== */

    .btn-new-secondary {
        display: inline-block;
        margin: 16px 0 0;
    }

    .alarmas-counter {
        color: #6b7280;
        font-size: 13px;
        margin: 16px 0 0;
    }



    /* ============================================== */
    /* FORMULÁRIO LATERAL                             */
    /* ============================================== */

    /* A aparência base do formulário continua a do Tomatito;
       posicionamento/abertura são controlados pelo drawer acima. */
    .alarmas-form h2 {
        font-size: 20px;
        margin: 0 0 20px;
    }

    .alarmas-form h3 {
        font-size: 14px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 24px 0 12px;
    }

    .alarmas-form .form-group {
        margin-bottom: 16px;
    }

    .alarmas-form label {
        display: block;
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 6px;
    }

    .alarmas-form input[type="text"],
    .alarmas-form input[type="time"],
    .alarmas-form input[type="date"] {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        background: #fff;
    }

    .alarmas-form input:focus {
        outline: none;
        border-color: #ff4d4d;
    }

    /* o select personalizado ocupa a largura toda dentro do formulário */
    .alarmas-form .tsel {
        display: block;
        width: 100%;
        min-width: 0;
    }

    /* Selector de dias da semana */
    .days-selector {
        display: flex;
        gap: 6px;
        margin-top: 8px;
    }

    .days-selector .day-btn {
        flex: 1;
        padding: 8px 0;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        transition: 0.2s;
    }

    .days-selector .day-btn.active {
        background: #ff4d4d;
        border-color: #ff4d4d;
        color: white;
    }

    .days-selector .day-btn:hover:not(.active) {
        background: #f3f4f6;
    }

    /* Controle de som: select + botão de preview lado a lado */
    .sound-control {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .sound-control .tsel {
        flex: 1;
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

    .alarmas-form .form-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }

    .alarmas-form .form-actions button {
        width: 100%;
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

    /* ============================================== */
    /* PAGINAÇÃO                                      */
    /* ============================================== */
    .alarmas-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin-top: 16px;
        flex-wrap: wrap;
    }
    .alarmas-pagination button {
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
    .alarmas-pagination button:hover:not(:disabled):not(.active) {
        background: #f3f4f6;
        border-color: #d1d5db;
    }
    .alarmas-pagination button.active {
        background: #ff4d4d;
        color: #fff;
        border-color: #ff4d4d;
        font-weight: 600;
    }
    .alarmas-pagination button:disabled {
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
                <li><a class="active" href="/alarmas/">🔔 Alarmas</a></li>
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

            <div class="tomatito-alarmas-header">
                <h1>Alarmas</h1>
                <button class="btn-primary" id="btn-new-alarma">+ Nueva Alarma</button>
            </div>

            <div class="alarmas-layout">

                <div class="alarmas-main">

                    <div class="alarmas-filters">
                        <input type="text" id="filter-search" placeholder="🔍 Buscar...">
                        <select id="filter-type">
                            <option value="todas">Todas</option>
                            <option value="activas">Activas</option>
                            <option value="desactivadas">Desactivadas</option>
                        </select>
                        <select id="filter-sort">
                            <option value="hora">Ordenar por Hora</option>
                            <option value="recientes">Ordenar por Recientes</option>
                            <option value="nombre">Ordenar por Nombre</option>
                        </select>
                    </div>

                    <div class="alarmas-table">

                        <div class="alarmas-table-header">
                            <span>Hora</span>
                            <span>Nombre</span>
                            <span>Repetir</span>
                            <span>Estado</span>
                            <span>Acciones</span>
                        </div>

                        <div id="alarmas-list">
                            <p class="loading">Cargando alarmas...</p>
                        </div>

                    </div>

                    <!-- PAGINAÇÃO -->
                    <nav id="alarmas-pagination" class="alarmas-pagination" aria-label="Paginación de alarmas"></nav>

                    <button class="btn-primary btn-new-secondary" id="btn-new-alarma-2">+ Nueva Alarma</button>

                    <p class="alarmas-counter" id="alarmas-counter"></p>

                </div>

                <!-- DRAWER DE CRIAÇÃO/EDIÇÃO -->
                <div class="alarmas-drawer-backdrop" id="alarma-drawer-backdrop"></div>

                <aside class="alarmas-form" id="alarma-form" aria-hidden="true">

                    <div class="drawer-head">
                        <div>
                            <h2 id="form-title">Nueva Alarma</h2>
                        </div>
                        <button type="button" class="drawer-close" id="btn-close-alarma" aria-label="Cerrar">×</button>
                    </div>

                    <input type="hidden" id="field-id" value="">

                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" id="field-name" placeholder="Despertador">
                    </div>

                    <div class="form-group">
                        <label>Hora</label>
                        <input type="time" id="field-time" value="07:00">
                    </div>

                    <div class="form-group">
                        <label>Repetición</label>
                        <select id="field-repeat-mode">
                            <option value="none">Sin repetición</option>
                            <option value="daily">Diariamente</option>
                            <option value="weekdays">Lunes a Viernes</option>
                            <option value="custom">Días concretos</option>
                        </select>
                    </div>

                    <!-- Selector de dias (só aparece quando repeat_mode = 'custom') -->
                    <div class="form-group" id="days-selector-group" style="display: none;">
                        <label>Días</label>
                        <div class="days-selector">
                            <button type="button" class="day-btn" data-day="mon">L</button>
                            <button type="button" class="day-btn" data-day="tue">M</button>
                            <button type="button" class="day-btn" data-day="wed">X</button>
                            <button type="button" class="day-btn" data-day="thu">J</button>
                            <button type="button" class="day-btn" data-day="fri">V</button>
                            <button type="button" class="day-btn" data-day="sat">S</button>
                            <button type="button" class="day-btn" data-day="sun">D</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Fecha de inicio <small style="font-weight:400;color:#9ca3af;">(opcional)</small></label>
                        <input type="date" id="field-start-date">
                    </div>

                    <div class="form-group">
                        <label>Fecha límite <small style="font-weight:400;color:#9ca3af;">(opcional)</small></label>
                        <input type="date" id="field-end-date">
                    </div>

                    <div class="form-group">
                        <label>Sonido</label>
                        <div class="sound-control">
                            <select id="field-sound" class="sound-select">
                                <option value="default">Predeterminado (según Ajustes)</option>
                                <option value="clasico">Clásico</option>
                                <option value="campana">Campana</option>
                                <option value="digital">Digital</option>
                                <option value="suave">Suave</option>
                                <option value="peeeem">Alarma insistente</option>
                                <option value="silent">Silencioso</option>
                                <option value="vibracion">Vibración</option>
                            </select>
                            <button type="button" class="btn-preview-sound" data-target="field-sound" title="Escuchar">&#9654;</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Recordar cada <small style="font-weight:400;color:#9ca3af;">(mientras no la detengas)</small></label>
                        <select id="field-reminder-minutes">
                            <option value="5">5 minutos</option>
                            <option value="10">10 minutos</option>
                            <option value="15">15 minutos</option>
                            <option value="20">20 minutos</option>
                            <option value="30">30 minutos</option>
                            <option value="60">60 minutos</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button class="btn-launch" id="btn-save-alarma">Guardar Alarma</button>
                        <button class="btn-secondary" id="btn-cancel-alarma">Cancelar</button>
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
    var TOMATITO_PEEEEM_URL = '<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../sounds/peeeem.mp3' ); ?>';

    // ============================================================
    // SELECT PERSONALIZADO — "veste" todos os <select> desta página
    // ============================================================
    // Regra de ouro: NÃO substitui o <select>; envolve-o. O elemento
    // nativo continua no DOM (invisível) e continua a ser quem guarda o
    // valor — por isso todo o código de baixo (filtros, guardar, editar,
    // preview de som) funciona exactamente como antes.
    // Ao escolher uma opção disparamos um evento 'change' no <select>,
    // que é o mesmo evento que os filtros e o toggleDaysSelector já
    // escutavam — não foi preciso mudar nenhum listener.

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
        document.querySelectorAll('.alarmas-layout select').forEach(enhanceSelect);
    }

    // clicar fora fecha qualquer painel aberto
    document.addEventListener('click', function() { tselCloseAll(null); });

    // ============================================
    // VARIÁVEL GLOBAL
    // ============================================
    let allAlarmas = [];

    // Paginação
    const PER_PAGE_ALARMAS = 10;
    let currentAlarmasPage = 1;

    // ============================================
    // CARREGAR / RENDERIZAR
    // ============================================

async function loadAlarmas() {
        try {
            const res  = await fetch('/wp-json/tomatito/v1/alarmas?nocache=' + Date.now(), {
				cache: 'no-store',
				credentials: 'include',
				headers: { 'X-WP-Nonce': wpApiSettings.nonce }
			});
            const data = await res.json();

            console.log('ALARMAS:', data);

            if (!data.success) return;

            allAlarmas = data.data;
            renderAlarmas();

        } catch (error) {
            console.error('Erro ao carregar alarmas:', error);
            const list = document.getElementById('alarmas-list');
            if (list) list.innerHTML = '<p class="empty">Error al cargar alarmas.</p>';
        }
    }

    function renderAlarmas() {
        const list       = document.getElementById('alarmas-list');
        const counter    = document.getElementById('alarmas-counter');
        const search     = document.getElementById('filter-search').value.toLowerCase().trim();
        const filterType = document.getElementById('filter-type').value;
        const sortBy     = document.getElementById('filter-sort').value;

        if (!list) return;

        // Filtrar pela pesquisa
        let filtered = allAlarmas.filter(a => {
            if (search && !a.name.toLowerCase().includes(search)) return false;
            return true;
        });

        // Filtrar por estado
        if (filterType === 'activas') {
            filtered = filtered.filter(a => a.is_active == 1);
        } else if (filterType === 'desactivadas') {
            filtered = filtered.filter(a => a.is_active == 0);
        }

        // Ordenar
        filtered.sort((a, b) => {
            if (sortBy === 'nombre') {
                return a.name.localeCompare(b.name);
            }
            if (sortBy === 'recientes') {
                return new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime();
            }
            // Default: por hora
            return a.time.localeCompare(b.time);
        });

        // Atualizar contador
        counter.innerText = `Mostrando ${filtered.length} de ${allAlarmas.length} alarmas guardadas`;

        // Paginar
        const totalAlarmasPages = Math.max(1, Math.ceil(filtered.length / PER_PAGE_ALARMAS));
        if (currentAlarmasPage > totalAlarmasPages) currentAlarmasPage = totalAlarmasPages;
        const alarmasStart = (currentAlarmasPage - 1) * PER_PAGE_ALARMAS;
        const pageItems = filtered.slice(alarmasStart, alarmasStart + PER_PAGE_ALARMAS);

        // Mostrar na tabela
        list.innerHTML = '';

        if (filtered.length === 0) {
            list.innerHTML = '<p class="empty">No se han encontrado alarmas.</p>';
            renderAlarmasPagination(0);
            return;
        }

        pageItems.forEach(item => {
            const row = document.createElement('div');
            row.classList.add('alarma-row');
            if (item.is_active != 1) row.classList.add('inactive');

            const checked = item.is_active == 1 ? 'checked' : '';

            row.innerHTML = `
                <span class="time">${item.time_label}</span>
                <span class="name">${item.name}</span>
                <span class="repeat">${item.repeat_label}</span>
                <span class="state">
                    <label class="toggle-switch">
                        <input type="checkbox" data-id="${item.id}" class="toggle-active" ${checked}>
                        <span class="status-dot"></span>
                    </label>
                </span>
                <span class="actions">
                    <button class="btn-edit"   data-id="${item.id}" title="Editar">✏️</button>
                    <button class="btn-delete" data-id="${item.id}" title="Eliminar">🗑️</button>
                </span>
            `;

            list.appendChild(row);
        });

        // Renderizar paginação
        renderAlarmasPagination(totalAlarmasPages);
    }

    // ============================================
    // PAGINAÇÃO — renderizar botões
    // ============================================
    function renderAlarmasPagination(totalPages) {
        const nav = document.getElementById('alarmas-pagination');
        if (!nav) return;
        nav.innerHTML = '';
        if (totalPages <= 1) return;

        const prev = document.createElement('button');
        prev.innerText = '‹';
        prev.disabled = currentAlarmasPage <= 1;
        prev.addEventListener('click', function() {
            currentAlarmasPage--;
            renderAlarmas();
        });
        nav.appendChild(prev);

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.innerText = i;
            if (i === currentAlarmasPage) btn.classList.add('active');
            btn.addEventListener('click', function() {
                currentAlarmasPage = i;
                renderAlarmas();
            });
            nav.appendChild(btn);
        }

        const next = document.createElement('button');
        next.innerText = '›';
        next.disabled = currentAlarmasPage >= totalPages;
        next.addEventListener('click', function() {
            currentAlarmasPage++;
            renderAlarmas();
        });
        nav.appendChild(next);
    }

    // Filtros (voltam para página 1)
    function onAlarmasFilterChange() {
        currentAlarmasPage = 1;
        renderAlarmas();
    }
    document.getElementById('filter-search').addEventListener('input', onAlarmasFilterChange);
    document.getElementById('filter-type').addEventListener('change', onAlarmasFilterChange);
    document.getElementById('filter-sort').addEventListener('change', onAlarmasFilterChange);

    // ============================================
    // FORMULÁRIO: ABRIR / FECHAR
    // ============================================

    function openAlarmaForm( alarma = null ) {
        const form     = document.getElementById('alarma-form');
        const backdrop = document.getElementById('alarma-drawer-backdrop');
        const title    = document.getElementById('form-title');

        // Abre como drawer, sem alterar o layout da página por baixo.
        form.classList.add('is-open');
        form.setAttribute('aria-hidden', 'false');
        form.inert = false;
        backdrop.classList.add('is-open');
        document.body.classList.add('tomatito-alarmas-drawer-open');

        if ( alarma ) {
            title.innerText = 'Editar Alarma';
            document.getElementById('field-id').value          = alarma.id;
            document.getElementById('field-name').value        = alarma.name;
            document.getElementById('field-time').value        = alarma.time.substring(0, 5); // "07:00:00" → "07:00"
            document.getElementById('field-repeat-mode').value = alarma.repeat_mode;
            document.getElementById('field-start-date').value  = alarma.start_date || '';
            document.getElementById('field-end-date').value    = alarma.end_date || '';
            document.getElementById('field-sound').value       = alarma.sound || 'default';
            document.getElementById('field-reminder-minutes').value = alarma.reminder_minutes || 5;

            // Marca os dias custom
            ['mon','tue','wed','thu','fri','sat','sun'].forEach(day => {
                const btn = document.querySelector(`.day-btn[data-day="${day}"]`);
                if (btn) {
                    if (alarma[day] == 1) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                }
            });
        } else {
            title.innerText = 'Nueva Alarma';
            document.getElementById('field-id').value          = '';
            document.getElementById('field-name').value        = '';
            document.getElementById('field-time').value        = '07:00';
            document.getElementById('field-repeat-mode').value = 'none';
            document.getElementById('field-start-date').value  = '';
            document.getElementById('field-end-date').value    = '';
            document.getElementById('field-sound').value       = 'default';
            document.getElementById('field-reminder-minutes').value = 5;

            // Limpa os dias
            document.querySelectorAll('.day-btn').forEach(btn => {
                btn.classList.remove('active');
            });
        }

        // ⚠️ NOVO: os valores acima foram postos nos <select> escondidos —
        // isto actualiza o texto mostrado nos botões personalizados.
        syncCustomSelects();

        toggleDaysSelector();
    }

    function closeAlarmaForm() {
        const form     = document.getElementById('alarma-form');
        const backdrop = document.getElementById('alarma-drawer-backdrop');

        form.classList.remove('is-open');
        form.setAttribute('aria-hidden', 'true');
        form.inert = true;
        backdrop.classList.remove('is-open');
        document.body.classList.remove('tomatito-alarmas-drawer-open');
    }

    // Mostra/esconde o selector de dias consoante o modo de repetição
    function toggleDaysSelector() {
        const mode  = document.getElementById('field-repeat-mode').value;
        const group = document.getElementById('days-selector-group');
        group.style.display = (mode === 'custom') ? 'block' : 'none';
    }

    document.getElementById('field-repeat-mode').addEventListener('change', toggleDaysSelector);

    // Botões de dias da semana (toggle individual)
    document.querySelectorAll('.day-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            btn.classList.toggle('active');
        });
    });

    function handleNewAlarmaClick() {
        openAlarmaForm();
    }

    document.getElementById('btn-new-alarma').addEventListener('click', handleNewAlarmaClick);
    document.getElementById('btn-new-alarma-2').addEventListener('click', handleNewAlarmaClick);
    document.getElementById('btn-cancel-alarma').addEventListener('click', closeAlarmaForm);
    document.getElementById('btn-close-alarma').addEventListener('click', closeAlarmaForm);
    document.getElementById('alarma-drawer-backdrop').addEventListener('click', closeAlarmaForm);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const form = document.getElementById('alarma-form');
            if (form && form.classList.contains('is-open')) closeAlarmaForm();
        }
    });

    // O drawer começa fechado e não deve participar da navegação por teclado.
    document.getElementById('alarma-form').inert = true;

    // ============================================
    // PREVIEW DE SOM (botão "Escuchar" no formulário)
    // ============================================
    let previewAudio = null;

    function playPreview(soundName) {
        // 'default' n\u00E3o \u00E9 um arquivo de som \u2014 resolve pro que est\u00E1 configurado
        // em Ajustes pra alarmas antes de tocar
        if (soundName === 'default') {
            try {
                const ajustes = JSON.parse(localStorage.getItem('tomatito_ajustes') || '{}');
                soundName = ajustes['sonido_alarma'] || 'campana';
            } catch (e) {
                soundName = 'campana';
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

        previewAudio = new Audio(soundName === 'peeeem' ? TOMATITO_PEEEEM_URL : TOMATITO_SOUNDS_URL + soundName + '.mp3');
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
    // GUARDAR
    // ============================================

    async function saveAlarma() {

        const id = document.getElementById('field-id').value;

        // Lê os dias selecionados
        const days = {};
        ['mon','tue','wed','thu','fri','sat','sun'].forEach(day => {
            const btn = document.querySelector(`.day-btn[data-day="${day}"]`);
            days[day] = btn && btn.classList.contains('active') ? 1 : 0;
        });

        const data = {
            name:             document.getElementById('field-name').value.trim(),
            time:             document.getElementById('field-time').value + ':00', // adiciona segundos
            repeat_mode:      document.getElementById('field-repeat-mode').value,
            start_date:       document.getElementById('field-start-date').value || null,
            end_date:         document.getElementById('field-end-date').value || null,
            sound:            document.getElementById('field-sound').value,
            reminder_minutes: parseInt(document.getElementById('field-reminder-minutes').value, 10) || 5,
            is_active:        1,
            ...days,
        };

        if ( ! data.name ) {
            await window.tomatitoAlert('El nombre es obligatorio');
            return;
        }

        if ( data.start_date && data.end_date && data.start_date > data.end_date ) {
            await window.tomatitoAlert('La fecha de inicio no puede ser posterior a la fecha límite');
            return;
        }

        const isEdit = !!id;
        const url    = isEdit
            ? `/wp-json/tomatito/v1/alarmas/${id}`
            : `/wp-json/tomatito/v1/alarmas`;
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
                await window.tomatitoAlert('Error: ' + (result.message || 'No se pudo guardar la alarma'));
                return;
            }

            await loadAlarmas();
            closeAlarmaForm();

        } catch (error) {
            console.error('Erro ao guardar alarma:', error);
            await window.tomatitoAlert('Error de conexión al guardar la alarma');
        }
    }

    document.getElementById('btn-save-alarma').addEventListener('click', saveAlarma);

    // ============================================
    // AÇÕES NAS LINHAS: TOGGLE / EDITAR / ELIMINAR
    // ============================================

    document.getElementById('alarmas-list').addEventListener('click', async function(event) {

        const target = event.target;

        // ─── TOGGLE ON/OFF ───
        if ( target.matches('.toggle-active') ) {
            const id = target.dataset.id;
            try {
                const res    = await fetch(`/wp-json/tomatito/v1/alarmas/${id}/toggle`, {
					method: 'POST',
					credentials: 'include',
					headers: { 'X-WP-Nonce': wpApiSettings.nonce }
				});
                const result = await res.json();

                console.log('TOGGLE result:', result);

                if ( result.success ) {
                    // Atualiza a lista local sem ir buscar tudo de novo
                    const alarma = allAlarmas.find(a => a.id == id);
                    if (alarma) {
                        alarma.is_active = result.is_active;
                        renderAlarmas();
                    }
                } else {
                    target.checked = !target.checked; // reverte o toggle
                    await window.tomatitoAlert('Error: ' + (result.message || ''));
                }

            } catch (error) {
                console.error('Erro ao toggle:', error);
                target.checked = !target.checked;
                await window.tomatitoAlert('Error de conexión');
            }
            return;
        }

        // Para os botões, encontra o button mais próximo
        const btn = target.closest('button');
        if (!btn) return;

        const id = btn.dataset.id;
        if (!id) return;

        // ─── EDITAR ───
        if ( btn.matches('.btn-edit') ) {
            const alarma = allAlarmas.find( a => a.id == id );
            if ( alarma ) {
                openAlarmaForm( alarma );
            }
        }

        // ─── ELIMINAR ───
        if ( btn.matches('.btn-delete') ) {

		const delAlarma = allAlarmas.find( a => a.id == id );
		const delName   = delAlarma ? delAlarma.name : 'esta alarma';
		if ( ! (await window.tomatitoConfirm('¿Estás seguro de que quieres eliminar "' + delName + '"?')) ) return;

            try {
                const res    = await fetch(`/wp-json/tomatito/v1/alarmas/${id}`, {
					method: 'DELETE',
					credentials: 'include',
					headers: { 'X-WP-Nonce': wpApiSettings.nonce }
				});
                const result = await res.json();

                console.log('DELETE result:', result);

                if ( result.success ) {
                    await loadAlarmas();
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
        loadAlarmas();

        // Se vieram do dashboard com ?new=alarma
        const params = new URLSearchParams(window.location.search);
        if ( params.get('new') === 'alarma' ) {
            openAlarmaForm();
        }
    });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode( 'tomatito_alarmas', 'tomatito_alarmas_shortcode' );
