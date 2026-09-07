<?php

function tomatito_ajustes_shortcode() {

    if ( ! is_user_logged_in() ) {
        return '<p>Debes iniciar sesión.</p>';
    }

    ob_start();
    ?>

    <style>
    /* ============================================== */
    /* PÁGINA AJUSTES                                 */
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

    .tomatito-ajustes-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 80px;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 24px;
    }

    .tomatito-ajustes-header h1 {
        font-size: 28px;
        font-weight: 600;
        margin: 0;
    }

    .ajustes-container {
        max-width: 100%;
        padding-bottom: 40px;
    }

    .ajustes-section {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 16px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.04);
    }

    .ajustes-section h2 {
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .ajustes-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .ajustes-row:last-child {
        border-bottom: none;
    }

    .ajustes-row .label {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .ajustes-row .label .title {
        font-size: 14px;
        font-weight: 500;
        color: #111827;
    }

    .ajustes-row .label .desc {
        font-size: 13px;
        color: #6b7280;
    }

    .ajustes-row input {
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        background: #fff;
        min-width: 200px;
    }

    .ajustes-row input:focus {
        outline: none;
        border-color: #ff4d4d;
    }

    .ajustes-row input:disabled {
        background: #f9fafb;
        color: #9ca3af;
        cursor: not-allowed;
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
       loadAjustes()/saveAjustes() e o preview de som leem e escrevem
       nele exatamente como antes, sem alterações. */

    .tsel {
        position: relative;
        display: inline-block;
        min-width: 220px;
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

    /* Controles de som: select + botão de preview lado a lado */
    .sound-control {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sound-control .tsel {
        min-width: 220px;
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

    .coming-soon-badge {
        display: inline-block;
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 999px;
        background: #fef3c7;
        color: #92400e;
        font-weight: 500;
        margin-left: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .ajustes-row.coming-soon .label .title {
        color: #6b7280;
    }

    .ajustes-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 24px;
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

    .btn-primary:disabled {
        opacity: 0.6;
        cursor: wait;
    }

    .ajustes-saved-message {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 13px;
        margin-top: 12px;
        display: none;
    }

    .ajustes-saved-message.visible {
        display: block;
    }

    .ajustes-saved-message.error {
        background: #fee2e2;
        border-color: #fca5a5;
        color: #991b1b;
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
                <li><a class="active" href="/ajustes/">⚙️ Ajustes</a></li>
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

            <div class="tomatito-ajustes-header">
                <h1>Ajustes</h1>
            </div>

            <div class="ajustes-container">

                <!-- ─── SECCIÓN: IDIOMA Y REGIÓN ─── -->
                <div class="ajustes-section">
                    <h2>🌍 Idioma y región</h2>

                    <div class="ajustes-row coming-soon">
                        <div class="label">
                            <span class="title">Idioma <span class="coming-soon-badge">Próximamente</span></span>
                            <span class="desc">Idioma de la interfaz</span>
                        </div>
                        <select id="field-idioma" disabled>
                            <option value="es_ES">Español (España)</option>
                        </select>
                    </div>

                    <div class="ajustes-row coming-soon">
                        <div class="label">
                            <span class="title">Zona horaria <span class="coming-soon-badge">Próximamente</span></span>
                            <span class="desc">Para cálculos de horario</span>
                        </div>
                        <select disabled>
                            <option>Europe/Madrid</option>
                        </select>
                    </div>

                    <div class="ajustes-row">
                        <div class="label">
                            <span class="title">Formato de hora</span>
                            <span class="desc">12h o 24h</span>
                        </div>
                        <select id="field-hora">
                            <option value="24h">24h (14:30)</option>
                            <option value="12h">12h (2:30 PM)</option>
                        </select>
                    </div>
                </div>

                <!-- ─── SECCIÓN: APARIENCIA ─── -->
                <div class="ajustes-section">
                    <h2>🎨 Apariencia</h2>

                    <div class="ajustes-row">
                        <div class="label">
                            <span class="title">Tema</span>
                            <span class="desc">Claro u oscuro</span>
                        </div>
                        <select id="field-tema">
                            <option value="claro">Claro</option>
                            <option value="oscuro">Oscuro</option>
                        </select>
                    </div>
                </div>

                <!-- ─── SECCIÓN: NOTIFICACIONES ─── -->
                <div class="ajustes-section">
                    <h2>🔔 Notificaciones</h2>

                    <div class="ajustes-row">
                        <div class="label">
                            <span class="title">Notificaciones de escritorio</span>
                            <span class="desc">Avisos cuando termina un pomodoro</span>
                        </div>
                        <select id="field-notificaciones">
                            <option value="on">Activadas</option>
                            <option value="off">Desactivadas</option>
                        </select>
                    </div>
                </div>

                <!-- ─── SECCIÓN: SONIDOS ─── -->
                <div class="ajustes-section">
                    <h2>🔊 Sonidos Predeterminados</h2>

                    <div class="ajustes-row">
                        <div class="label">
                            <span class="title">Sonido de Pomodoro</span>
                            <span class="desc">Al terminar una fase de pomodoro</span>
                        </div>
                        <div class="sound-control">
                            <select id="field-sonido-pomodoro" class="sound-select">
                                <option value="clasico">Clásico</option>
                                <option value="campana">Campana</option>
                                <option value="digital">Digital</option>
                                <option value="suave">Suave</option>
                                <option value="silent">Silencioso</option>
                                <option value="vibracion">Vibración</option>
                            </select>
                            <button type="button" class="btn-preview-sound" data-target="field-sonido-pomodoro" title="Escuchar">&#9654;</button>
                        </div>
                    </div>

                    <div class="ajustes-row">
                        <div class="label">
                            <span class="title">Sonido de Temporizador</span>
                            <span class="desc">Al terminar un temporizador</span>
                        </div>
                        <div class="sound-control">
                            <select id="field-sonido-temporizador" class="sound-select">
                                <option value="clasico">Clásico</option>
                                <option value="campana">Campana</option>
                                <option value="digital">Digital</option>
                                <option value="suave">Suave</option>
                                <option value="silent">Silencioso</option>
                                <option value="vibracion">Vibración</option>
                            </select>
                            <button type="button" class="btn-preview-sound" data-target="field-sonido-temporizador" title="Escuchar">&#9654;</button>
                        </div>
                    </div>

                    <div class="ajustes-row">
                        <div class="label">
                            <span class="title">Sonido de Alarma</span>
                            <span class="desc">Cuando suena una alarma</span>
                        </div>
                        <div class="sound-control">
                            <select id="field-sonido-alarma" class="sound-select">
                                <option value="clasico">Clásico</option>
                                <option value="campana">Campana</option>
                                <option value="digital">Digital</option>
                                <option value="suave">Suave</option>
                                <option value="peeeem">Alarma insistente</option>
                                <option value="silent">Silencioso</option>
                                <option value="vibracion">Vibración</option>
                            </select>
                            <button type="button" class="btn-preview-sound" data-target="field-sonido-alarma" title="Escuchar">&#9654;</button>
                        </div>
                    </div>
                </div>

                <div class="ajustes-actions">
                    <button class="btn-primary" id="btn-save-ajustes">Guardar cambios</button>
                </div>

                <div class="ajustes-saved-message" id="saved-message"></div>

            </div>

            <?php if ( function_exists( 'tomatito_render_ad_footer' ) ) { tomatito_render_ad_footer(); } ?>

        </main>

    </div>

    <script>
    // ============================================
    // URL BASE DOS SONS
    // ============================================
	var TOMATITO_SOUNDS_URL = '<?php echo esc_url( content_url( 'uploads/2026/07/' ) ); ?>';
    var TOMATITO_PEEEEM_URL = '<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../sounds/peeeem.mp3' ); ?>';	

    // ============================================================
    // SELECT PERSONALIZADO — "veste" os <select> desta página
    // ============================================================
    // Regra de ouro: NÃO substitui o <select>; envolve-o. O elemento
    // nativo continua no DOM (invisível) e continua a ser quem guarda o
    // valor — por isso todo o código de baixo (loadAjustes, saveAjustes,
    // playPreview) funciona exatamente como antes, sem uma linha alterada.
    // O botão e o painel são só a "pele" por cima.

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
            text.textContent = opt.textContent;   // textContent = imune a HTML no nome
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
            // dispara 'change' para que qualquer listener existente reaja
            select.dispatchEvent(new Event('change', { bubbles: true }));
            refresh();
            wrap.classList.remove('open');
        });

        // fecha com Escape
        wrap.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') wrap.classList.remove('open');
        });
    }

    // Chamar depois de mudar valores por código (ex.: loadAjustes)
    function syncCustomSelects() {
        document.querySelectorAll('select.tsel-native').forEach(function(s) {
            if (typeof s._tselRefresh === 'function') s._tselRefresh();
        });
    }

    function initCustomSelects() {
        document.querySelectorAll('.ajustes-container select').forEach(enhanceSelect);
    }

    // clicar fora fecha qualquer painel aberto
    document.addEventListener('click', function() { tselCloseAll(null); });

    // ============================================
    // CARREGAR PREFERÊNCIAS DA API
    // ============================================

    async function loadAjustes() {
        try {
            const res  = await fetch('/wp-json/tomatito/v1/ajustes?nocache=' + Date.now(), {
                cache: 'no-store',
                credentials: 'include',
                headers: { 'X-WP-Nonce': wpApiSettings.nonce }
            });
            const data = await res.json();

            console.log('AJUSTES (GET):', data);

            if (!data.success) return;

            const a = data.data;

            document.getElementById('field-hora').value                = a.hora                || '24h';
            document.getElementById('field-tema').value                = a.tema                || 'claro';
            document.getElementById('field-notificaciones').value      = a.notificaciones      || 'on';
            document.getElementById('field-sonido-pomodoro').value     = a.sonido_pomodoro     || 'clasico';
            document.getElementById('field-sonido-temporizador').value = a.sonido_temporizador || 'digital';
            document.getElementById('field-sonido-alarma').value       = a.sonido_alarma       || 'campana';

            // ⚠️ NOVO: os valores acima foram postos no <select> escondido —
            // isto actualiza o texto mostrado nos botões personalizados.
            syncCustomSelects();

            // Espelha no localStorage (cache para as outras páginas: formatTime, etc.)
            localStorage.setItem('tomatito_ajustes', JSON.stringify(a));

            applyTheme(a.tema || 'claro');

        } catch (error) {
            console.error('Erro ao carregar ajustes:', error);
            showMessage('Error al cargar los ajustes', true);
        }
    }

    // ============================================
    // GUARDAR PREFERÊNCIAS NA API
    // ============================================

    async function saveAjustes() {
        const btn = document.getElementById('btn-save-ajustes');
        btn.disabled = true;

        const ajustes = {
            hora:                document.getElementById('field-hora').value,
            tema:                document.getElementById('field-tema').value,
            notificaciones:      document.getElementById('field-notificaciones').value,
            sonido_pomodoro:     document.getElementById('field-sonido-pomodoro').value,
            sonido_temporizador: document.getElementById('field-sonido-temporizador').value,
            sonido_alarma:       document.getElementById('field-sonido-alarma').value,
        };

        try {
            const res = await fetch('/wp-json/tomatito/v1/ajustes?nocache=' + Date.now(), {
                method: 'POST',
                cache: 'no-store',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiSettings.nonce
                },
                body: JSON.stringify(ajustes),
            });
            const result = await res.json();

            console.log('AJUSTES (POST):', result);

            if (!result.success) {
                showMessage('Error: ' + (result.message || 'No se pudieron guardar los ajustes'), true);
                btn.disabled = false;
                return;
            }

            // Espelha no localStorage (cache para as outras páginas)
            localStorage.setItem('tomatito_ajustes', JSON.stringify(ajustes));

            applyTheme(ajustes.tema);
            showMessage('\u2705 Ajustes guardados correctamente', false);

        } catch (error) {
            console.error('Erro ao guardar ajustes:', error);
            showMessage('Error de conexión al guardar', true);
        }

        btn.disabled = false;
    }

    // ============================================
    // MENSAGEM DE FEEDBACK
    // ============================================

    function showMessage(text, isError) {
        const msg = document.getElementById('saved-message');
        msg.innerText = text;
        msg.classList.toggle('error', !!isError);
        msg.classList.add('visible');
        setTimeout(() => msg.classList.remove('visible'), 3000);
    }

    // ============================================
    // PREVIEW DE SONS (agora tamb\u00E9m suporta 'vibracion')
    // ============================================

    let previewAudio = null;

    function playPreview(soundName) {
        // 'silent' n\u00E3o toca nada
        if (soundName === 'silent') return;

        // 'vibracion': tenta vibrar o dispositivo (s\u00F3 funciona em
        // mobile com Chrome/Android \u2014 desktop e Safari/iOS n\u00E3o suportam)
        if (soundName === 'vibracion') {
            if (navigator.vibrate) {
                navigator.vibrate([200, 100, 200]);
            } else {
                showMessage('Este dispositivo/navegador no soporta vibraci\u00F3n', true);
            }
            return;
        }

        // Para o som anterior se ainda estiver tocando
        if (previewAudio) {
            previewAudio.pause();
            previewAudio.currentTime = 0;
        }

        previewAudio = new Audio(soundName === 'peeeem' ? TOMATITO_PEEEEM_URL : TOMATITO_SOUNDS_URL + soundName + '.mp3');
        previewAudio.play().catch(err => {
            console.error('No se pudo reproducir el sonido:', err);
            showMessage('No se encontr\u00f3 el archivo de sonido: ' + soundName + '.mp3', true);
        });
    }

    document.querySelectorAll('.btn-preview-sound').forEach(btn => {
        btn.addEventListener('click', function() {
            const select = document.getElementById(btn.dataset.target);
            if (select) playPreview(select.value);
        });
    });

    // ============================================
    // APLICAR TEMA (claro/oscuro)
    // ============================================

    function applyTheme( tema ) {
        if ( tema === 'oscuro' ) {
            document.body.classList.add('tomatito-dark');
        } else {
            document.body.classList.remove('tomatito-dark');
        }
    }

    document.getElementById('btn-save-ajustes').addEventListener('click', saveAjustes);

    // ============================================
    // INICIALIZAÇÃO
    // ============================================

    document.addEventListener('DOMContentLoaded', function() {
        initCustomSelects();   // ⚠️ NOVO: veste os selects antes de carregar
        loadAjustes();
    });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode( 'tomatito_ajustes', 'tomatito_ajustes_shortcode' );
