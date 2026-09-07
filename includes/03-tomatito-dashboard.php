<?php

/**
 * Tomatito Dashboard
 */
function tomatito_dashboard_shortcode(){
    if( ! is_user_logged_in() ){
        // Rede de seguran\u00e7a: normalmente o utilizador nunca chega aqui, porque
        // tomatito_forzar_login() (no fim deste snippet) j\u00e1 o redirecionou para
        // /login/ antes de a p\u00e1gina come\u00e7ar a ser desenhada. Esta mensagem s\u00f3
        // aparece se, por alguma raz\u00e3o, esse hook n\u00e3o disparar \u2014 assim o
        // dashboard nunca fica vis\u00edvel para quem n\u00e3o tem sess\u00e3o.
        return '<p>Debes iniciar sesión.</p>';
    }
    $view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'general';

    // \u00cdCONE DO TOMATE \u2014 definido UMA vez, usado em todos os s\u00edtios abaixo.
    // Para trocar a imagem, basta mudar este caminho (e o mesmo no in\u00edcio de
    // tomatito_dashboard_print_js(), que \u00e9 uma fun\u00e7\u00e3o separada e n\u00e3o v\u00ea esta
    // vari\u00e1vel).
    $tomato_url = esc_url( content_url( 'uploads/2026/08/tomatito-icon.png' ) );

    ob_start();
    ?>
			<style>
			/* ========================= */
			/* CSS ESPECÍFICO DO DASHBOARD */
			/* ========================= */
			/* Garante que TODO botão é clicável por inteiro, mesmo quando tem
			   um emoji/ícone dentro — sem isto, clicar bem em cima do emoji
			   registra o clique nesse emoji (não no botão), e a ação não
			   dispara. Mesma regra já usada na página de Pomodoros. */
			button * {
				pointer-events: none;
			}
			/* PADDING ENTRE SIDEBAR E CONTENT */
			body .tomatito-content {
				padding-left: 36px;
				padding-right: 36px;
			}
			body .tomatito-content.has-fixed-top {
				display: flex;
				flex-direction: column;
				overflow: hidden;
				padding-bottom: 0;
			}
			body .dashboard-fixed-top {
				flex: 0 0 auto;
			}
			body .dashboard-scroll-area {
				flex: 1 1 auto;
				overflow-y: auto;
				min-height: 200px;
				padding-right: 4px;
				padding-bottom: 32px;
			}
			body .tomatito-header {
				display: flex;
				align-items: center;
				height: 80px;
				border-bottom: 1px solid #e5e7eb;
				margin-bottom: 20px;
				padding: 0px;
			}
			body .tomatito-header h1 {
				font-size: 28px;
				font-weight: 600;
				margin: 0;
			}
			body .tomatito-hero {
				display: flex;
				gap: 20px;
				margin-top: 20px;
			}
			body .tomatito-main-card {
				flex: 2;
				background: linear-gradient(180deg, #ff6b6b, #ff4d4d);
				color: white;
				border-radius: 12px;
				overflow: hidden;
				box-shadow: 0 12px 30px rgba(0,0,0,0.18);
				display: flex;
				flex-direction: column;
				position: relative;
				transition: outline 0.15s ease;
			}
			body .tomatito-main-card.drag-over {
				outline: 3px dashed rgba(255,255,255,0.9);
				outline-offset: -6px;
			}
			/* V1.0.14: card principal mais compacto e equilibrado. O layout
			   continua a ser o mesmo; apenas evitamos que a altura seja esticada
			   pela coluna lateral quando há espaço vertical sobrando. */
			body .tomatito-hero {
				align-items: stretch;
			}
			body .tomatito-main-card {
				align-self: stretch;
				min-height: 262px;
				background: linear-gradient(135deg, #ff6868 0%, #ff5058 58%, #f24752 100%);
			}
			body .tomatito-main-card .timer-ring-wrap {
				width: 160px;
				height: 160px;
			}
			body .tomatito-main-card .tomatito-status {
				padding: 8px;
				font-size: 12px;
				background: rgba(0,0,0,0.10);
			}
			/* O layout do .top / .card-left / .card-right / .card-title-row / h2 /
			   .bottom vive todo junto, mais abaixo, na secção "LAYOUT DO CARD"
			   — é a única fonte de verdade para essa composição (título, badge
			   de fase e botões, todos centrados no mesmo eixo). */
			body .tomatito-main-card::after {
				content: "";
				position: absolute;
				top: 0;
				left: 0;
				height: 45%;
				width: 100%;
				background: linear-gradient(to bottom, rgba(255,255,255,0.08), transparent);
				pointer-events: none;
			}
			/* Ícone do título do card vermelho — imagem (tomato.png), maior
			   que o texto ao redor, com sombra suave */
			body .card-title-emoji {
				display: inline-block;
				width: 46px;
				height: 46px;
				object-fit: contain;
				filter: drop-shadow(0 3px 5px rgba(0,0,0,0.3));
			}
			/* Botão de trocar o pomodoro destacado — fica fixo no canto do card,
			   não empurra nem repete o nome que já aparece no título */
			/* Botão de trocar o pomodoro destacado — agora fica em linha com o
			   título (antes era absoluto no canto superior direito, onde
			   passaria a chocar com o anel de progresso). */
			body .favorito-switcher-btn {
				-webkit-appearance: none !important;
				appearance: none !important;
				outline: none !important;
				position: static;
				flex: 0 0 auto;
				width: 30px;
				height: 30px;
				margin-left: 4px;
				border: 0;
				border-radius: 0;
				background: transparent;
				color: rgba(255,255,255,0.9);
				font-size: 14px;
				box-shadow: none;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				cursor: pointer;
				transition: opacity 0.2s, transform 0.2s;
				z-index: 2;
				padding: 0;
			}
			body .favorito-switcher-btn:hover {
				background: transparent;
				border-color: transparent;
				color: #fff;
				opacity: 0.8;
				transform: scale(1.05);
			}
			body .favorito-switcher-btn svg {
				width: 20px;
				height: 20px;
				display: block;
			}
			body .favorito-switcher-btn:focus,
			body .favorito-switcher-btn:focus-visible,
			body .favorito-switcher-btn:active,
			body .favorito-switcher-btn:focus-within {
				outline: none !important;
				border: 0 !important;
				box-shadow: none !important;
				background: transparent !important;
				-webkit-appearance: none !important;
				appearance: none !important;
			}
			body .favorito-switcher-btn::-moz-focus-inner {
				border: 0;
			}

			body .favorito-switcher-menu {
				position: absolute;
				top: calc(100% + 8px);
				right: 0;
				background: #fff;
				border-radius: 10px;
				box-shadow: 0 12px 30px rgba(0,0,0,0.2);
				overflow: hidden;
				min-width: 180px;
				max-width: 260px;
				z-index: 3;
			}
			body .favorito-switcher-menu .switcher-item {
				display: block;
				width: 100%;
				text-align: left;
				padding: 10px 14px;
				border: none;
				border-top: 1px solid #f0f0f0;
				background: #fff;
				color: #111827;
				font-size: 13px;
				font-weight: 500;
				font-family: inherit;
				cursor: pointer;
				transition: 0.15s;
			}
			body .favorito-switcher-menu .switcher-item:first-child {
				border-top: none;
			}
			body .favorito-switcher-menu .switcher-item:hover {
				background: #f3f4f6;
			}
			body .favorito-switcher-menu .switcher-item.is-selected {
				color: #ff4d4d;
				font-weight: 700;
			}
			body .tomatito-timer {
				font-size: 52px;
				font-weight: 700;
				text-align: center;
				margin: 4px 0 0;
				line-height: 1.1;
			}

			/* ============================================== */
			/* ANEL DE PROGRESSO À VOLTA DO CRONÓMETRO         */
			/* ============================================== */
			/* O círculo vai esvaziando à medida que o tempo desce.
			   Técnica: o traço do <circle> tem um comprimento fixo
			   (stroke-dasharray = perímetro = 2 · π · 54 ≈ 339.292) e o
			   stroke-dashoffset controla quanto desse traço fica escondido.
			   offset 0 = anel cheio; offset 339.292 = anel vazio.
			   O anel NÃO tem relógio próprio: é redesenhado dentro de
			   updateUI(), a partir do mesmo timeLeft/duration que já
			   alimenta o texto — mesma regra de "uma só fonte de verdade"
			   usada em syncCardFromCache(). */
			body .timer-ring-wrap {
				position: relative;
				width: 170px;
				height: 170px;
				margin: 8px auto 0;
			}
			body .timer-ring {
				width: 100%;
				height: 100%;
				/* faz o anel começar no topo (12h) em vez de à direita (3h) */
				transform: rotate(-90deg);
			}
			body .timer-ring circle {
				fill: none;
				stroke-width: 8;
				stroke-linecap: round;
			}
			body .timer-ring .ring-bg {
				stroke: rgba(255,255,255,0.22);
			}
			body .timer-ring .ring-progress {
				stroke: #fff;
				stroke-dasharray: 339.292;
				stroke-dashoffset: 0;
				/* 1s linear = mesma cadência do tick, movimento contínuo */
				transition: stroke-dashoffset 1s linear;
			}
			body .timer-ring-center {
				position: absolute;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
			}
			/* dentro do anel o número precisa de ser um pouco menor */
			body .timer-ring-wrap .tomatito-timer {
				font-size: 42px;
				margin: 0;
			}
			body .timer-total {
				font-size: 12px;
				opacity: 0.85;
				margin-top: 2px;
			}
			/* respeita quem pediu menos movimento no sistema operativo */
			@media (prefers-reduced-motion: reduce) {
				body .timer-ring .ring-progress {
					transition: none;
				}
			}

			body .tomatito-status {
				background: rgba(0,0,0,0.12);
				padding: 10px;
				text-align: center;
				font-size: 13px;
				width: 100%;
				margin: 0;
			}
			body .tomatito-main-card .bottom {
				padding: 14px 0 0;
				display: flex;
				justify-content: center;
				gap: 24px;
				width: 100%;
			}
			/* BOTÕES DO CARD VERMELHO — redondos e minimalistas.
			   Só o ícone: o significado vai no atributo title (tooltip) e no
			   aria-label, definidos no HTML/updateUI(). Os botões que levam
			   texto (avançar de fase, reiniciar o último) usam .is-wide. */
			body .tomatito-actions button {
				width: 46px;
				height: 46px;
				padding: 0;
				border-radius: 50%;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				background: rgba(255,255,255,0.18);
				color: #fff;
				border: 1.5px solid rgba(255,255,255,0.55);
				/* os glifos ↻ e ⇄ desenham mais pequenos que os emojis */
				font-size: 21px;
				font-weight: 600;
				line-height: 1;
				cursor: pointer;
				transition: 0.2s;
				box-shadow: 0 4px 12px rgba(0,0,0,0.15);
				font-family: inherit, "Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji";
			}
			/* botões com texto: voltam a ser "pílulas" */
			body .tomatito-actions button.is-wide {
				width: auto;
				height: auto;
				padding: 12px 22px;
				border-radius: 999px;
				font-size: 15px;
			}
			body .tomatito-actions button:hover {
				background: rgba(255,255,255,0.30);
				border-color: #fff;
				transform: translateY(-1px);
				box-shadow: 0 6px 16px rgba(0,0,0,0.22);
			}
			/* O "+" simples precisa de uma escala própria para se alinhar
			   visualmente aos demais ícones do card. */
			body .tomatito-actions button.is-create-pomodoro {
				font-size: 30px;
				font-family: inherit;
				font-weight: 500;
			}
			body .card-phase-badge {
				font-size: 15px;
				font-weight: 600;
				margin-top: 6px;
				opacity: 0.92;
			}
			body .tomatito-actions button.btn-advance-card {
				background: #2ecc71;
				border-color: #2ecc71;
			}
			body .tomatito-actions button.btn-advance-card:hover {
				background: #27ae60;
				border-color: #27ae60;
			}
			body .tomatito-side {
				flex: 1;
				display: flex;
				flex-direction: column;
				gap: 15px;
			}
			body .tomatito-box {
				background: #fff;
				padding: 15px;
				border-radius: 10px;
				border: 1px solid #eee;
				box-shadow: 0 5px 15px rgba(0,0,0,0.05);
			}
			/* AÇÕES RÁPIDAS */
			body .tomatito-quick {
				display: flex;
				gap: 15px;
				margin-top: 25px;
			}
			body .quick-btn {
				padding: 18px 32px;
				border-radius: 10px;
				border: none;
				font-size: 16px;
				font-weight: 600;
				color: white;
				cursor: pointer;
				display: inline-flex;
				align-items: center;
				gap: 10px;
			}
			/* Ícone maior dentro dos botões de ações rápidas */
			body .quick-btn .qicon {
				font-size: 24px;
				line-height: 1;
				color: #fff;
			}
			body .quick-btn .qicon-plus {
				width: 32px;
				height: 32px;
				flex: 0 0 32px;
				display: block;
			}
			/* Relógio do botão cinza um pouco maior (o glifo ⏱️ renderiza pequeno) */
			body .quick-btn.dark .qicon {
				font-size: 30px;
			}
			body .quick-btn.primary { background: #ff5a5a; }
			body .quick-btn.dark    { background: #7c8a99; }
			body .quick-btn.alert   { background: #ff7a3d; }
			body .history-table {
				background: #fff;
				border-radius: 10px;
				border: 1px solid #eee;
				overflow: hidden;
				margin-top: 10px;
			}
			body .history-table .row {
				display: grid;
				grid-template-columns: 2fr 1fr 1fr 1fr;
				padding: 14px 16px;
				border-bottom: 1px solid #eee;
			}
			body .history-table .row:last-child { border-bottom: none; }
			/* Estado vazio com o mesmo respiro visual das linhas do histórico. */
			body .history-table .history-empty {
				margin: 0;
				padding: 14px 16px;
				box-sizing: border-box;
				color: #111827;
				font-size: 16px;
			}
			/* O tema pode zerar o padding dos parágrafos; este seletor específico
			   garante o mesmo alinhamento interno das linhas do histórico. */
			body .tomatito-history .history-table #history-list > p.history-empty {
				display: block;
				margin: 0 !important;
				padding: 14px 16px !important;
				box-sizing: border-box;
			}
			/* Área própria para o foco do link, evitando que a borda fique
			   colada ao texto ou pareça cortada no lado esquerdo. */
			body .tomatito-history a.btn-primary {
				display: inline-block;
				padding: 2px 4px;
				margin: 0;
				border-radius: 4px;
			}
			/* O link inferior usa uma classe própria; garante a mesma caixa
			   de foco sem depender dos estilos do tema. */
			body .tomatito-history a.btn-new-secondary {
				display: inline-block;
				box-sizing: border-box;
				padding: 2px 4px;
				margin: 0;
				border-radius: 4px;
			}
			/* Mantém toda a marca de foco dentro da caixa, inclusive no
			   primeiro link quando o contêiner de rolagem corta a margem externa. */
			body .tomatito-history a.btn-primary:focus,
			body .tomatito-history a.btn-primary:focus-visible {
				outline: 2px solid #ff4d4d !important;
				outline-offset: -2px !important;
				box-shadow: none !important;
			}
			body .ok       { color: #2ecc71; font-weight: 600; }
			body .cancel   { color: #e74c3c; font-weight: 600; }
			body .progress { color: #27ae60; font-weight: 600; }

		/* PAGINAÇÃO HISTÓRICO */
			body .history-pagination {
				display: flex;
				justify-content: center;
				align-items: center;
				gap: 6px;
				margin-top: 16px;
				flex-wrap: wrap;
			}
			body .history-pagination button {
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
			body .history-pagination button:hover:not(:disabled):not(.active) {
				background: #f3f4f6;
				border-color: #d1d5db;
			}
			body .history-pagination button.active {
				background: #ff4d4d;
				color: #fff;
				border-color: #ff4d4d;
				font-weight: 600;
			}
			body .history-pagination button:disabled {
				opacity: 0.4;
				cursor: not-allowed;
			}

			/* LINHA: paginação do histórico + toggle da busca */
			body .history-bottom-row {
				display: flex;
				align-items: center;
				margin-top: 16px;
				position: relative;
			}
			body .history-bottom-row .history-pagination {
				margin: 0 auto;
			}
			/* Toggle "Buscar historial completo" no canto direito */
			body .history-search-toggle {
				position: absolute;
				right: 0;
				display: inline-flex;
				align-items: center;
				gap: 6px;
				background: #fff;
				border: 1px solid #e5e7eb;
				border-radius: 8px;
				padding: 8px 14px;
				font-size: 13px;
				font-weight: 500;
				color: #374151;
				cursor: pointer;
				transition: 0.2s;
			}
			body .history-search-toggle:hover {
				background: #f3f4f6;
				border-color: #d1d5db;
			}
			body .history-search-toggle .toggle-arrow {
				transition: transform 0.2s;
				font-size: 12px;
			}
			body .history-search-toggle.open .toggle-arrow {
				transform: rotate(180deg);
			}
							
			/* CABEÇALHO DO HISTÓRICO (título + botão) */
			body .tomatito-history-header {
				display: flex;
				align-items: center;
				justify-content: space-between;
				margin-bottom: 14px;
			}
			body .tomatito-history-header h3 {
				margin: 0;
			}

			/* POMODOROS ACTIVOS */
			body .active-pomodoros-section {
				margin-top: 20px;
			}
			body .active-pomodoros-section h3 {
				font-size: 18px;
				font-weight: 600;
				line-height: 1.2;
				margin: 0;
				display: flex;
				align-items: center;
				gap: 8px;
			}
			body .active-pomodoros-list {
				display: flex;
				flex-direction: column;
				gap: 8px;
			}
			body .active-pomodoro-card {
				display: flex;
				align-items: center;
				justify-content: space-between;
				background: #fff;
				border: 1px solid #eee;
				border-left: 4px solid #ff4d4d;
				border-radius: 10px;
				padding: 14px 18px;
				box-shadow: 0 5px 15px rgba(0,0,0,0.04);
				cursor: grab;
				transition: opacity 0.15s ease;
			}
			body .active-pomodoro-card.dragging {
				opacity: 0.4;
			}
			body .active-pomodoro-card.paused {
				border-left-color: #f59e0b;
				opacity: 0.85;
			}
			body .active-pomodoro-card .name {
				font-size: 15px;
				font-weight: 500;
				color: #111827;
			}
			body .active-pomodoro-card .time {
				font-size: 18px;
				font-weight: 600;
				color: #ff4d4d;
				font-variant-numeric: tabular-nums;
			}
			body .active-pomodoro-card.paused .time {
				color: #f59e0b;
			}
			body .active-pomodoro-card .state-label {
    font-size: 12px;
    color: #6b7280;
    margin-left: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

body .active-pomodoro-card .left-side {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

body .active-pomodoro-card .right-side {
    display: flex;
    align-items: center;
    gap: 12px;
}

body .active-pomodoro-card .card-buttons {
    display: flex;
    gap: 6px;
}

body .active-pomodoro-card .card-btn {
    background: transparent;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    transition: 0.2s;
    color: #374151;
}

body .active-pomodoro-card .card-btn:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

body .active-pomodoro-card .card-btn.btn-pause:hover,
body .active-pomodoro-card .card-btn.btn-resume:hover {
    border-color: #f59e0b;
    color: #f59e0b;
}

body .active-pomodoro-card .card-btn.btn-stop:hover {
    border-color: #ef4444;
    color: #ef4444;
    background: #fee2e2;
}

body .active-pomodoro-card .card-btn.btn-reiniciar:hover {
    border-color: #2ecc71;
    color: #2ecc71;
}

/* Botão estrela "Favorito" (só nos pomodoros) */
body .card-btn.btn-principal {
    color: #cbd5e1;
}
body .card-btn.btn-principal:hover {
    border-color: #f5b942;
    color: #f5b942;
}
body .card-btn.btn-principal.is-principal {
    color: #f5b942;
    border-color: #f5b942;
}

/* Badge de fase (Pomodoros Activos) */
body .active-pomodoro-card .phase-badge {
    font-size: 11px;
    color: #ff4d4d;
    font-weight: 600;
    white-space: nowrap;
    display: block;
    margin-top: 2px;
}
body .active-pomodoro-card.paused .phase-badge {
    color: #f59e0b;
}
body .btn-advance-phase {
    background: #2ecc71;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
    white-space: nowrap;
}
body .btn-advance-phase:hover {
    background: #27ae60;
}
body .btn-advance-phase:disabled {
    opacity: 0.6;
    cursor: wait;
}

/* Temporizadores ativos no card lateral — item com botões compactos */
body #temporizadores-list .temp-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 6px 0;
    border-bottom: 1px solid #f3f4f6;
}
body #temporizadores-list .temp-item:last-child {
    border-bottom: none;
}
body #temporizadores-list .temp-item .temp-info {
    font-size: 16px;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
body #temporizadores-list .temp-item.paused .temp-info {
    color: #f59e0b;
}
body #temporizadores-list .temp-item .temp-btns {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
}
body #temporizadores-list .temp-item .temp-btn {
    background: transparent;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 4px 9px;
    font-size: 16px;
    cursor: pointer;
    transition: 0.2s;
    color: #374151;
    line-height: 1;
}
body #temporizadores-list .temp-item .temp-btn:hover {
    background: #f3f4f6;
}
body #temporizadores-list .temp-item .temp-btn.t-pause:hover,
body #temporizadores-list .temp-item .temp-btn.t-resume:hover {
    border-color: #f59e0b;
    color: #f59e0b;
}
body #temporizadores-list .temp-item .temp-btn.t-stop:hover {
    border-color: #ef4444;
    color: #ef4444;
    background: #fee2e2;
}
body #temporizadores-list .temp-item .temp-btn.t-reiniciar:hover {
    border-color: #2ecc71;
    color: #2ecc71;
}

			/* ============================================== */
			/* BUSCAR EN EL HISTORIAL (nova seção)            */
			/* ============================================== */
			body .history-search-section {
				margin-top: 16px;
				padding-top: 20px;
				border-top: 1px dashed #d1d5db;
			}
			/* recolhida por defeito */
			body .history-search-section.collapsed {
				display: none;
			}
			body .history-search-section h3 {
				font-size: 18px;
				font-weight: 600;
				margin: 0 0 14px;
			}
			body .history-search-controls {
				display: flex;
				gap: 10px;
				align-items: center;
				flex-wrap: wrap;
				margin-bottom: 16px;
			}
			body .history-search-date {
				display: flex;
				align-items: center;
				gap: 8px;
				background: #fff;
				border: 1px solid #e5e7eb;
				border-radius: 10px;
				padding: 8px 12px;
			}
			body .history-search-date input[type="date"] {
				border: none;
				background: transparent;
				font-size: 14px;
				font-family: inherit;
				color: #111827;
				outline: none;
				padding: 0;
			}
			body .history-search-granularity {
				display: flex;
				gap: 4px;
				background: #fff;
				border: 1px solid #e5e7eb;
				border-radius: 10px;
				padding: 4px;
			}
			body .history-search-granularity button {
				border: none;
				background: transparent;
				color: #374151;
				border-radius: 7px;
				padding: 7px 16px;
				font-size: 13px;
				font-weight: 500;
				cursor: pointer;
				transition: 0.2s;
			}
			body .history-search-granularity button.active {
				background: #ff4d4d;
				color: #fff;
			}
			body .history-search-granularity button:hover:not(.active) {
				background: #f3f4f6;
			}
			body .history-search-nav {
				display: flex;
				gap: 4px;
				margin-left: auto;
			}
			body .history-search-nav button {
				width: 36px;
				height: 36px;
				border: 1px solid #e5e7eb;
				background: #fff;
				border-radius: 8px;
				color: #374151;
				cursor: pointer;
				font-size: 16px;
				transition: 0.2s;
			}
			body .history-search-nav button:hover {
				background: #f3f4f6;
				border-color: #d1d5db;
			}
			body .history-search-range-label {
				font-size: 13px;
				color: #6b7280;
				margin-bottom: 16px;
			}
			body .history-search-summary {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 12px;
				margin-bottom: 16px;
			}
			body .history-search-summary .summary-card {
				background: #fff;
				border: 1px solid #eee;
				border-radius: 12px;
				padding: 16px 20px;
			}
			body .history-search-summary .summary-card .summary-label {
				font-size: 13px;
				color: #6b7280;
				margin-bottom: 6px;
			}
			body .history-search-summary .summary-card .summary-value {
				font-size: 26px;
				font-weight: 600;
				color: #111827;
			}
			body .history-search-summary .summary-card.accent .summary-value {
				color: #ff4d4d;
			}
			body .history-search-results {
				background: #fff;
				border-radius: 10px;
				border: 1px solid #eee;
				overflow: hidden;
			}
			body .history-search-results .row {
				display: grid;
				grid-template-columns: 2fr 1fr 1fr 1fr;
				padding: 14px 16px;
				border-bottom: 1px solid #eee;
			}
			body .history-search-results .row:last-child { border-bottom: none; }
			body .history-search-empty {
				background: #fff;
				border: 1px solid #eee;
				border-radius: 12px;
				padding: 24px 20px;
				text-align: center;
				color: #9ca3af;
				font-size: 14px;
			}

			/* ESPAÇO ENTRE OS BOTÕES RÁPIDOS E O HISTÓRICO */
			body .tomatito-history {
				margin-top: 28px;
			}

			/* ============================================== */
			/* LAYOUT DO CARD — fonte única de verdade.
			   Título, badge de fase e fileira de botões ficam dentro de
			   .card-left, cada um com largura encolhida ao próprio
			   conteúdo (não 100%) e centralizados entre si — por isso
			   acompanham sempre o mesmo eixo vertical, seja no caso sem
			   pomodoro (só o "+"), com um activo (3 botões) ou com vários
			   (3 botões + seta de troca). .top centraliza esse bloco
			   inteiro junto ao anel de progresso, com um gap fixo e
			   moderado entre os dois em vez do vazio grande de antes. */
			/* ============================================== */
			body .tomatito-hero > .tomatito-main-card {
				/* Era 1.75 (quase o dobro do painel lateral) — o card ficava
				   tão largo que o título/botões, mesmo centralizados,
				   sobravam com vãos enormes nas laterais. Reduzido para dar
				   um meio-termo: menos vazio, sem exigir uma largura mínima
				   fixa que poderia cortar o anel em telas mais estreitas. */
				flex: 1.3 1 0;
				min-height: 262px;
				background: linear-gradient(135deg, #ff6868 0%, #ff5058 58%, #f24752 100%);
			}
			body .tomatito-hero > .tomatito-side { flex: 1 1 0; }
			body .tomatito-main-card .timer-ring-wrap { width: 160px; height: 160px; }
			body .tomatito-main-card .tomatito-status {
				padding: 8px;
				font-size: 12px;
				background: rgba(0,0,0,0.10);
			}
			body .tomatito-main-card .top {
				/* flex:1 é essencial: sem isto o .top só ocupa a altura do
				   próprio conteúdo, e toda a sobra (min-height do card menos
				   esse conteúdo) fica presa como espaço morto ABAIXO da
				   barra "En espera/En marcha" — em vez de ser distribuída
				   como respiro em volta do título/botões, empurrando a
				   barra para junto da borda inferior do card, onde ela
				   deve ficar. */
				flex: 1;
				display: flex;
				flex-wrap: wrap;
				align-items: center;
				justify-content: center;
				gap: 24px 68px;
				width: 100%;
				/* border-box: sem isto, width:100% + padding somam-se e o
				   .top fica MAIOR que o próprio card — o excesso ficava
				   escondido pelo overflow:hidden do card, mas empurrava o
				   cálculo de "cabe/não cabe" do flex-wrap para um valor
				   errado (maior do que o espaço realmente visível). */
				box-sizing: border-box;
				padding: 24px 36px 18px;
			}
			body .tomatito-main-card .card-left {
				display: flex;
				flex-direction: column;
				align-items: center;
				flex: 0 1 auto;
				/* Sem min-width: quem resolve o vão nas laterais é o card ter
				   uma fatia menor da largura total (ver flex do
				   .tomatito-hero > .tomatito-main-card abaixo) — não um
				   "chão" fixo em px, que arriscaria cortar o anel (o card
				   tem overflow:hidden) em janelas mais estreitas. */
				min-width: 0;
				max-width: 440px;
			}
			body .tomatito-main-card .card-right {
				flex: 0 0 auto;
			}
			body .tomatito-main-card .card-title-row {
				display: flex;
				align-items: center;
				gap: 10px;
				width: auto;
				max-width: 100%;
				position: relative;
			}
			body .tomatito-main-card h2 {
				width: auto;
				font-size: 21px;
				display: flex;
				align-items: center;
				gap: 10px;
				margin: 0;
				min-width: 0;
			}
			body .tomatito-main-card .card-title-row .favorito-switcher-btn {
				flex: 0 0 30px;
				margin-left: 0;
			}
			body .tomatito-main-card .bottom {
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 24px;
				width: auto;
				margin: 0;
				padding-top: 14px;
			}
			/* Sem media query de largura fixa: como .top já tem
			   flex-wrap:wrap, é o próprio navegador que empilha o anel
			   por baixo do título assim que faltar espaço — funciona em
			   qualquer largura de sidebar/tema, sem adivinhar um valor
			   de breakpoint que poderia cortar o anel (overflow:hidden). */
			@media (max-width: 640px) {
				body .tomatito-main-card .top {
					padding: 20px 24px;
				}
			}
			</style>
    <div class="tomatito-dashboard">
        <!-- SIDEBAR -->
        <aside class="tomatito-sidebar">
            <div class="tomatito-logo">
                <img class="logo-emoji" src="<?php echo $tomato_url; ?>" alt="🍅"> <strong>Tomatito</strong> Enfocado
            </div>
            <ul class="menu">
    <li><a class="<?php echo ( $view == 'general' ) ? 'active' : ''; ?>" href="?view=general">📊 Visión General</a></li>
    <li><a href="/pomodoros/"><img class="tomato-icon" src="<?php echo $tomato_url; ?>" alt="🍅"> Pomodoros</a></li>
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
        <main class="tomatito-content<?php echo ( $view == 'general' ) ? ' has-fixed-top' : ''; ?>">
            <?php if ( function_exists( 'tomatito_render_ad_header' ) ) { tomatito_render_ad_header(); } ?>
            <?php if( $view == 'pomodoros' ): ?>
                <h1>Pomodoros</h1>
                <p>Aquí se gestionarán los pomodoros.</p>
            <?php elseif( $view == 'temporizadores' ): ?>
                <h1>Temporizadores</h1>
                <p>Aquí se gestionarán los temporizadores.</p>
            <?php elseif( $view == 'alarmas' ): ?>
                <h1>Alarmas</h1>
                <p>Aquí se gestionarán las alarmas.</p>
            <?php elseif( $view == 'ajustes' ): ?>
                <h1>Ajustes</h1>
                <p>Aquí irán las configuraciones.</p>
            <?php else: ?>
                <!-- ZONA FIXA: header + hero + ações -->
                <div class="dashboard-fixed-top">
                <!-- HEADER -->
                <div class="tomatito-header">
                    <h1>Ahora mismo</h1>
                </div>
                <!-- HERO -->
                <div class="tomatito-hero">
                    <div class="tomatito-main-card">
                        <div class="top">
                            <!-- COLUNA ESQUERDA: nome, fase e ações -->
                            <div class="card-left">
                                <div class="card-title-row">
                                    <h2 id="card-title"><img class="card-title-emoji" src="<?php echo $tomato_url; ?>" alt="🍅"> <span id="card-title-text">Pomodoro en marcha</span></h2>
                                    <button type="button" id="favorito-switcher-btn" class="favorito-switcher-btn" style="display:none;" title="Cambiar pomodoro destacado" aria-label="Cambiar pomodoro destacado"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 8.5h13"/><path d="M13.5 5l3.5 3.5-3.5 3.5"/><path d="M20 15.5H7"/><path d="M10.5 12L7 15.5l3.5 3.5"/></svg></button>
                                    <div id="favorito-switcher-menu" class="favorito-switcher-menu" style="display:none;"></div>
                                </div>
                                <div id="card-phase-badge" class="card-phase-badge" style="display:none;"></div>
                                <div class="bottom tomatito-actions">
                                    <button id="pause-btn" onclick="handlePause()" title="Pausar" aria-label="Pausar">⏸</button>
									<button id="stop-btn" onclick="handleStop()" title="Detener" aria-label="Detener">■</button>
									<button id="reset-btn" onclick="resetTimer()" title="Reiniciar" aria-label="Reiniciar">↻</button>
									<button id="reiniciar-last-btn" class="is-wide" onclick="reiniciarUltimo()" style="display:none;">↻ Reiniciar el último</button>
									<button id="advance-btn" class="btn-advance-card is-wide" onclick="handleCardAdvanceClick()" style="display:none;">▶ ¿Empezar Descanso?</button>
                                </div>
                            </div>
                            <!-- COLUNA DIREITA: anel de progresso + cronómetro -->
                            <div class="card-right">
                                <div class="timer-ring-wrap">
                                    <svg class="timer-ring" viewBox="0 0 120 120" aria-hidden="true" focusable="false">
                                        <circle class="ring-bg"       cx="60" cy="60" r="54"></circle>
                                        <circle class="ring-progress" id="ring-progress" cx="60" cy="60" r="54"></circle>
                                    </svg>
                                    <div class="timer-ring-center">
                                        <div id="timer" class="tomatito-timer">--:--</div>
                                        <div id="timer-total" class="timer-total"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p id="timer-status" class="tomatito-status">En espera</p>
                    </div>
                    <div class="tomatito-side">
                        <div class="tomatito-box">
							<h4 id="temporizadores-title">Temporizadores Activos</h4>
							<div id="temporizadores-list">
								<div class="item" style="color:#9ca3af;">Cargando...</div>
							</div>
						</div>
                       <div class="tomatito-box">
							<h4>Próximas Alarmas</h4>
							<div id="alarmas-list">
								<div class="item" style="color:#9ca3af;">Cargando...</div>
							</div>
						</div>
                    </div>
                </div>


				<!-- AÇÕES RÁPIDAS -->
				<div class="tomatito-quick">
					<button class="quick-btn primary" onclick="window.location.href='/pomodoros/'"><svg class="qicon-plus" viewBox="0 0 32 32" aria-hidden="true" focusable="false"><path d="M16 5v22M5 16h22" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/></svg> Pomodoros</button>
					<button class="quick-btn dark"    onclick="window.location.href='/temporizadores/'"><span class="qicon">⏱️</span> Temporizadores</button>
					<button class="quick-btn alert"   onclick="window.location.href='/alarmas/'"><span class="qicon">🔔</span> Alarmas</button>
				</div>
				</div>
				<!-- /ZONA FIXA -->

				<!-- ZONA QUE ROLA: pomodoros activos + histórico + busca -->
				<div class="dashboard-scroll-area">

				<!-- POMODOROS ACTIVOS -->
				<div class="active-pomodoros-section" id="active-pomodoros-section" style="display: none;">
					<h3><img class="tomato-icon" src="<?php echo $tomato_url; ?>" alt="🍅"> Pomodoros Activos</h3>
					<div id="active-pomodoros-list"></div>
				</div>



					<!-- HISTÓRICO -->
					<div class="tomatito-history">
						<div class="tomatito-history-header">
							<h3>Historial de Hoy</h3>
							<a href="/historial-completo/" class="btn-primary">Buscar historial completo</a>
						</div>
						<div class="history-table">
						<div id="history-list"></div>
					</div>

					<!-- PAGINAÇÃO -->
						<nav id="history-pagination" class="history-pagination" aria-label="Paginación del historial"></nav>

						<!-- BOTÃO DUPLICADO -->
						<a href="/historial-completo/" class="btn-primary btn-new-secondary">Buscar historial completo</a>
					</div>

					<!-- RODAPÉ: espaço de anúncio, bem abaixo de tudo -->
					<?php if ( function_exists( 'tomatito_render_ad_footer' ) ) { tomatito_render_ad_footer(); } ?>

				</div>
											
				<!-- /ZONA QUE ROLA -->

            <?php endif; ?>
        </main>
    </div>
    <?php
    // O JS vai para o rodapé (wp_footer) para o WordPress NÃO codificar os '&' (&& -> &#038;)
    if ( ! has_action( 'wp_footer', 'tomatito_dashboard_print_js' ) ) {
        add_action( 'wp_footer', 'tomatito_dashboard_print_js' );
    }
    return ob_get_clean();
}

function tomatito_dashboard_print_js() {
    // Mesma imagem do tomate usada no HTML acima. Esta funÃ§Ã£o Ã© separada da
    // do shortcode, por isso o caminho tem de ser repetido aqui.
    $tomato_url = esc_url( content_url( 'uploads/2026/08/tomatito-icon.png' ) );
    ?>
    <script>

		// ============================================
		// SONS DE T\u00C9RMINO (por tipo, configurados em Ajustes)
		// ============================================
		var TOMATITO_SOUNDS_URL = '<?php echo esc_url( content_url( 'uploads/2026/07/' ) ); ?>';
		var TOMATITO_TOMATO_URL = '<?php echo $tomato_url; ?>';
		var TOMATITO_TOMATO_IMG = '<img src="' + TOMATITO_TOMATO_URL + '" style="width:16px;height:16px;vertical-align:-3px;object-fit:contain;">';
		var tomatitoAudioUnlocked = false;

		// Toca o som configurado para um tipo: 'pomodoro' | 'temporizador' | 'alarma'
		// ⚠️ ALTERADO: aceita um 2º parâmetro opcional 'overrideSound' — o som
		// configurado num item específico, com prioridade sobre a config
		// global de Ajustes quando presente e diferente de vazio/'default'.
		function playFinishSound(tipo, overrideSound) {
			try {
				var ajustes = JSON.parse(localStorage.getItem('tomatito_ajustes') || '{}');
				var sonido  = (overrideSound && overrideSound !== 'default')
					? overrideSound
					: ajustes['sonido_' + tipo];

				// defaults iguais aos da API
				if (!sonido) {
					if (tipo === 'pomodoro')     sonido = 'clasico';
					if (tipo === 'temporizador') sonido = 'digital';
					if (tipo === 'alarma')       sonido = 'campana';
				}

				if (sonido === 'silent') return;

				if (sonido === 'vibracion') {
					if (navigator.vibrate) {
						navigator.vibrate([200, 100, 200]);
					}
					return;
				}

				var audio = new Audio(TOMATITO_SOUNDS_URL + sonido + '.mp3');
				audio.play().catch(function(err) {
					console.warn('Sonido bloqueado o no encontrado (' + sonido + '):', err);
				});
			} catch (e) {
				console.error('Error al reproducir sonido:', e);
			}
		}

		// Desbloqueia o \u00E1udio na primeira intera\u00E7\u00E3o do usu\u00E1rio
		// (navegadores bloqueiam autoplay sem gesto pr\u00E9vio)
		function unlockAudio() {
			if (tomatitoAudioUnlocked) return;
			tomatitoAudioUnlocked = true;
			var a = new Audio(TOMATITO_SOUNDS_URL + 'clasico.mp3');
			a.volume = 0;
			a.play().catch(function() {});
			document.removeEventListener('click', unlockAudio);
			document.removeEventListener('keydown', unlockAudio);
		}
		document.addEventListener('click', unlockAudio);
		document.addEventListener('keydown', unlockAudio);

		let temporizadoresAnteriores = {};
		let temporizadoresJaNotificados = JSON.parse(localStorage.getItem('tomatito_temp_notificados') || '{}');
		function marcarTempNotificado(id) {
			temporizadoresJaNotificados[id] = true;
			try {
				localStorage.setItem('tomatito_temp_notificados', JSON.stringify(temporizadoresJaNotificados));
			} catch (e) {}
		}
		let alarmasJaNotificadas = {};
		let duration = 1500;
		let timeLeft  = duration;
		let state     = 'stopped';
		let lastToday = null;
		let currentName = null; // nome do pomodoro que est\u00E1 no card
		let activePomodorosCache = [];
		let activeTemporizadoresCache = [];
		let principalAtual = null; // { kind: 'pomodoro'|'temporizador', source_id: N } ou null

		// ⚠️ NOVO: controle de transição de fase (compartilhado entre a lista
		// "Pomodoros Activos" e o card vermelho principal — ver syncCardFromCache)
		let awaitingAdvance = {}; // pomodoro_id => true, esperando clique manual do usuário
		let advancingPhase  = {}; // pomodoro_id => true, pedido em curso (evita duplo clique/duplo disparo)

		// ⚠️ NOVO: dados de fase do pomodoro mostrado no CARD VERMELHO principal
		// (só de leitura — sincronizados a partir de activePomodorosCache, nunca
		// decrementados por um relógio próprio; ver syncCardFromCache)
		let currentTimerId          = null;
		let currentPomodoroId       = null;
		let currentPhase            = null;
		let currentPhaseLabel       = null;
		let currentCycle            = 0;
		let currentCyclesTotal      = 1;
		let currentRepetition       = 0;
		let currentRepetitionsTotal = 1;
		let currentAutoStart        = 1;
		let currentPauseOnEnd       = 0;
		let cardAwaitingAdvance     = false; // true = mostra o bot\u00E3o verde no card

		// ⚠️ NOVO: perímetro do anel de progresso = 2 · π · 54 (raio do <circle>).
		// Se algum dia mudares o r="54" no SVG, tens de mudar este valor E o
		// stroke-dasharray no CSS — os três têm de bater certo.
		const RING_CIRCUMFERENCE = 339.292;

async function reiniciarUltimo() {
    if ( ! lastToday || ! lastToday.id ) {
        await window.tomatitoAlert('No hay pomodoro para reiniciar');
        return;
    }
    fetch('/wp-json/tomatito/v1/pomodoros/' + lastToday.id + '/start', {
        method: 'POST',
        credentials: 'include',
        headers: { 'X-WP-Nonce': wpApiSettings.nonce }
    })
        .then(res => res.json())
        .then(async data => {
            console.log('API REINICIAR ULTIMO:', data);
            if (data.success) {
                window.location.reload();
            } else {
                await window.tomatitoAlert('Error al reiniciar: ' + (data.message || ''));
            }
        })
        .catch(async err => {
            console.error('Erro ao reiniciar \u00FAltimo:', err);
            await window.tomatitoAlert('Error de conexi\u00F3n al reiniciar');
        });
}

function pauseTimer() {
    if (!currentPomodoroId) return;
    fetch('/wp-json/tomatito/v1/pomodoros/' + currentPomodoroId + '/pause', {
        method: 'POST',
        credentials: 'include',
        headers: { 'X-WP-Nonce': wpApiSettings.nonce }
    })
        .then(res => res.json())
        .then(data => {
            console.log('API PAUSE (card):', data);
            loadHistory();
            fetchActivePomodoros(); // atualiza o cache partilhado \u2192 sincroniza o card
        })
        .catch(() => {});
}
function stopTimer() {
    if (!currentPomodoroId) return;
    fetch('/wp-json/tomatito/v1/pomodoros/' + currentPomodoroId + '/stop', {
        method: 'POST',
        credentials: 'include',
        headers: { 'X-WP-Nonce': wpApiSettings.nonce }
    })
        .then(res => res.json())
        .then(data => {
            console.log('API STOP (card):', data);
            loadHistory();
            fetchActivePomodoros();
            initDashboard(); // o pomodoro saiu da lista de ativos: recarrega /current p/ "Reiniciar el último"
        })
        .catch(() => {});
}
function handlePause()  { pauseTimer(); }
function handleStop()   { stopTimer(); }

function handleResume() {
    if (!currentPomodoroId) return;
    fetch('/wp-json/tomatito/v1/pomodoros/' + currentPomodoroId + '/resume', {
        method: 'POST',
        credentials: 'include',
        headers: { 'X-WP-Nonce': wpApiSettings.nonce }
    })
        .then(res => res.json())
        .then(data => {
            console.log('API RESUME (card):', data);
            if (!data.success) console.error('Falha ao retomar:', data.message);
            fetchActivePomodoros();
        })
        .catch(err => console.error('Erro RESUME:', err));
}
function resetTimer() {
    if (!currentPomodoroId) return;
    fetch('/wp-json/tomatito/v1/pomodoros/' + currentPomodoroId + '/stop', {
        method: 'POST',
        credentials: 'include',
        headers: { 'X-WP-Nonce': wpApiSettings.nonce }
    })
        .then(() => fetch('/wp-json/tomatito/v1/pomodoros/' + currentPomodoroId + '/start', {
            method: 'POST',
            credentials: 'include',
            headers: { 'X-WP-Nonce': wpApiSettings.nonce }
        }))
        .then(res => res.json())
        .then(result => {
            console.log('RESET (card) result:', result);
            delete awaitingAdvance[currentPomodoroId];
            delete advancingPhase[currentPomodoroId];
            fetchActivePomodoros();
            initDashboard(); // recarrega fase/ciclo/repetição do zero (volta a 'work')
        })
        .catch(err => console.error('Erro ao resetar:', err));
}

// ============================================
// CARD VERMELHO PRINCIPAL — apenas uma "vitrine"
// ⚠️ O card NÃO tem relógio nem motor de fases próprios. Ele lê o mesmo
// activePomodorosCache que já é a fonte única de verdade para
// "Pomodoros Activos" (populado por fetchActivePomodoros/tickActivePomodoros
// e mantido por handlePhaseEnd/runAdvancePhase/completePomodoro, definidos
// mais abaixo). Ter dois relógios independentes contando o MESMO pomodoro
// causava alertas disparando antes da hora (o card e a lista chegavam a
// zero em momentos ligeiramente diferentes e cada um tentava avançar a
// fase por conta própria). Agora só existe UM tick, UMA chamada de
// advance-phase por transição — o card só espelha o resultado.
// O anel de progresso segue exatamente a mesma regra: é desenhado a partir
// de timeLeft/duration dentro de updateUI(), sem contador próprio.
// ============================================
function syncCardFromCache() {
    if (!currentPomodoroId) {
        cardAwaitingAdvance = false;
        updateUI();
        return;
    }

    const item = activePomodorosCache.find(i => Number(i.pomodoro_id) === Number(currentPomodoroId));
    if (!item) {
        // O pomodoro do card não está (mais) na lista de ativos (foi
        // parado/completado) — não faz sentido continuar mostrando o botão
        // "¿Empezar Descanso?/¿Volver al Trabajo?" para um pomodoro que já
        // não existe mais como ativo. Sem isto, o botão ficava preso na tela
        // até a página ser recarregada manualmente.
        cardAwaitingAdvance = false;
        updateUI();
        return;
    }

    timeLeft                = item.remaining;
    duration                = item.duration;
    state                   = item.state; // 'running' | 'paused'
    currentPhase            = item.phase;
    currentPhaseLabel       = item.phase_label;
    currentCycle            = item.cycle;
    currentCyclesTotal      = item.cycles_total;
    currentRepetition       = item.repetition;
    currentRepetitionsTotal = item.repetitions_total;
    currentAutoStart        = item.auto_start;
    currentPauseOnEnd       = item.pause_on_end;
    currentTimerId          = item.timer_id;
    cardAwaitingAdvance     = !!awaitingAdvance[currentPomodoroId];

    updateUI();
}

function handleCardAdvanceClick() {
    if (!currentPomodoroId) return;
    const btn = document.getElementById('advance-btn');
    if (btn) btn.disabled = true;
    // Usa o MESMO runAdvancePhase da lista "Pomodoros Activos" — não existe
    // uma versão separada para o card, para não duplicar a chamada à API.
    delete awaitingAdvance[currentPomodoroId];
    runAdvancePhase(currentPomodoroId);
}
function updateUI() {
    const el = document.getElementById('timer');
    if (!el) return;
    if (timeLeft === null) {
        el.textContent = '--:--';
    } else {
        const min = String(Math.floor(timeLeft / 60)).padStart(2, '0');
        const sec = String(timeLeft % 60).padStart(2, '0');
        el.textContent = min + ':' + sec;
    }

    // ⚠️ NOVO: ANEL DE PROGRESSO
    // Espelha o mesmo timeLeft/duration que o texto acima. Quanto menos
    // tempo falta, maior o stroke-dashoffset → menos traço desenhado →
    // o círculo "esvazia". Quando muda de fase, o duration muda junto e o
    // anel volta a encher-se sozinho.
    const ring = document.getElementById('ring-progress');
    if (ring) {
        if (timeLeft === null || !duration) {
            ring.style.strokeDashoffset = RING_CIRCUMFERENCE; // anel vazio
        } else {
            const frac = Math.max(0, Math.min(1, timeLeft / duration));
            ring.style.strokeDashoffset = RING_CIRCUMFERENCE * (1 - frac);
        }
    }

    // Texto pequeno por baixo do número: "de 25:00" (duração total da fase)
    const totalEl = document.getElementById('timer-total');
    if (totalEl) {
        if (timeLeft === null || !duration) {
            totalEl.textContent = '';
        } else {
            const tmin = String(Math.floor(duration / 60)).padStart(2, '0');
            const tsec = String(duration % 60).padStart(2, '0');
            totalEl.textContent = 'de ' + tmin + ':' + tsec;
        }
    }

    const statusEl = document.getElementById('timer-status');
    if (statusEl) {
        if (state === 'running') {
            statusEl.textContent = 'En marcha';
        } else if (state === 'paused') {
            statusEl.textContent = 'Pausado';
        } else if (state === 'finished') {
            statusEl.textContent = 'Terminado';
        } else {
            statusEl.textContent = 'En espera';
        }
    }

    // T\u00EDtulo do card: s\u00F3 o nome do pomodoro (o status "En marcha"/"Pausado"
    // j\u00E1 aparece na barra logo abaixo, n\u00E3o precisa repetir aqui). O emoji
    // agora \u00E9 um elemento fixo no HTML (.card-title-emoji), separado do
    // nome, para poder ter tamanho/sombra pr\u00F3prios via CSS.
    const cardTitleTextEl = document.getElementById('card-title-text');
    if (cardTitleTextEl) {
        if ((state === 'running' || state === 'paused') && currentName) {
            cardTitleTextEl.textContent = currentName;
        } else {
            cardTitleTextEl.textContent = 'Sin pomodoro activo';
        }
    }
const pauseBtn          = document.getElementById('pause-btn');
const reiniciarLastBtn  = document.getElementById('reiniciar-last-btn');
const advanceBtn        = document.getElementById('advance-btn');

if (pauseBtn) {
    if (cardAwaitingAdvance) {
        pauseBtn.style.display = 'none';
    } else if (state === 'running') {
        // \u26A0\uFE0F Bot\u00F5es redondos: s\u00F3 o \u00EDcone. O significado vai no title
        // (tooltip ao passar o rato) e no aria-label (leitores de ecr\u00E3).
        pauseBtn.style.display = '';
        pauseBtn.classList.remove('is-create-pomodoro');
        pauseBtn.innerText = '\u23F8';
        pauseBtn.title     = 'Pausar';
        pauseBtn.setAttribute('aria-label', 'Pausar');
        pauseBtn.onclick   = handlePause;
    } else if (state === 'paused') {
        pauseBtn.style.display = '';
        pauseBtn.classList.remove('is-create-pomodoro');
        pauseBtn.innerText = '\u25B6';
        pauseBtn.title     = 'Reanudar';
        pauseBtn.setAttribute('aria-label', 'Reanudar');
        pauseBtn.onclick   = handleResume;
    } else {
        pauseBtn.style.display = '';
        pauseBtn.classList.add('is-create-pomodoro');
        pauseBtn.innerText = '+';
        pauseBtn.title     = 'Crear Pomodoro';
        pauseBtn.setAttribute('aria-label', 'Crear Pomodoro');
        pauseBtn.onclick   = function () {
            window.location.href = '/pomodoros/?new=pomodoro';
        };
    }
}

if (advanceBtn) {
    if (cardAwaitingAdvance) {
        advanceBtn.style.display = '';
        advanceBtn.disabled      = false;
        advanceBtn.innerText     = currentPhase === 'work' ? '\u25B6 \u00bfEmpezar Descanso?' : '\u25B6 \u00bfVolver al Trabajo?';
    } else {
        advanceBtn.style.display = 'none';
    }
}

if (reiniciarLastBtn) {
    if ( !cardAwaitingAdvance && (state === 'stopped' || state === 'finished') && lastToday && lastToday.id ) {
        reiniciarLastBtn.style.display = '';
        reiniciarLastBtn.innerHTML     = '\u21BB Reiniciar "' + lastToday.name + '"';
    } else {
        reiniciarLastBtn.style.display = 'none';
    }
}

	const stopBtn  = document.getElementById('stop-btn');
	const resetBtn = document.getElementById('reset-btn');
	if (stopBtn && resetBtn) {
		if (cardAwaitingAdvance) {
			stopBtn.style.display  = 'none';
			resetBtn.style.display = 'none';
		} else if (state === 'running' || state === 'paused') {
			stopBtn.style.display  = '';
			resetBtn.style.display = '';
		} else {
			stopBtn.style.display  = 'none';
			resetBtn.style.display = 'none';
		}
	}

	const phaseBadgeEl = document.getElementById('card-phase-badge');
	if (phaseBadgeEl) {
		if (currentPhaseLabel && (state === 'running' || state === 'paused' || state === 'finished')) {
			phaseBadgeEl.style.display = '';
			const shownCycle = Math.min(currentCycle + 1, currentCyclesTotal);
			phaseBadgeEl.textContent = phaseIcon(currentPhase) + ' ' + currentPhaseLabel + ' \u00b7 Ciclo ' + shownCycle + '/' + currentCyclesTotal;
		} else {
			phaseBadgeEl.style.display = 'none';
		}
	}
}

		async function loadUpcomingAlarmas() {
    try {
        var res = await fetch('/wp-json/tomatito/v1/alarmas/upcoming?nocache=' + Date.now(), {
			cache: 'no-store',
			credentials: 'include',
			headers: { 'X-WP-Nonce': wpApiSettings.nonce }
		});
        var data = await res.json();
        console.log('ALARMAS UPCOMING:', data);
        var list = document.getElementById('alarmas-list');
        if (!list) return;
        list.innerHTML = '';
        if (!data.success || data.data.length === 0) {
            list.innerHTML = '<div class="item" style="color:#9ca3af;">Sin alarmas activas</div>';
            return;
        }
        data.data.slice(0, 2).forEach(function(item) {
            var row = document.createElement('div');
            row.classList.add('item');
            row.innerHTML = '\uD83D\uDD14 ' + item.name + ' \u2013 ' + item.time;
            list.appendChild(row);
        });
    } catch (error) {
        console.error('Erro ao carregar alarmas:', error);
    }
}

async function fetchActiveTemporizadores() {
    try {
        var res = await fetch('/wp-json/tomatito/v1/temporizadores/dashboard?nocache=' + Date.now(), {
			cache: 'no-store',
			credentials: 'include',
			headers: { 'X-WP-Nonce': wpApiSettings.nonce }
		});
        var data = await res.json();
        console.log('TEMPORIZADORES DASHBOARD:', data);

        var title = document.getElementById('temporizadores-title');
        if (title) {
            if (data.mode === 'running') {
                title.innerText = 'Temporizadores Activos';
            } else {
                title.innerText = '\u00DAltimos Temporizadores';
            }
        }

        if (!data.success || !data.data) {
            activeTemporizadoresCache = [];
        } else {
            activeTemporizadoresCache = data.data
                .filter(function(item) {
                    return Number(item.remaining) > 0 || item.state === 'paused';
                })
                .map(function(item) {
                    return {
                        timer_id:        item.timer_id,
                        temporizador_id: item.temporizador_id,
                        name:            item.name,
                        remaining:       Number(item.remaining),
                        state:           item.state,
                        mode:            data.mode,
                        sound:           item.sound
                    };
                });
        }

        renderActiveTemporizadores();

    } catch (error) {
        console.error('Erro ao carregar temporizadores:', error);
    }
}

function renderActiveTemporizadores() {
    var list = document.getElementById('temporizadores-list');
    if (!list) return;

    list.innerHTML = '';

    if (!activeTemporizadoresCache || activeTemporizadoresCache.length === 0) {
        list.innerHTML = '<div class="item" style="color:#9ca3af;">Sin temporizadores</div>';
        return;
    }

    activeTemporizadoresCache.forEach(function(item) {
        var remaining = Math.max(0, item.remaining);
        var min  = String(Math.floor(remaining / 60)).padStart(2, '0');
        var sec  = String(remaining % 60).padStart(2, '0');
        var time = min + ':' + sec;

        if (item.mode === 'recent' || !item.timer_id) {
            var simple = document.createElement('div');
            simple.classList.add('item');
            simple.innerHTML = '\u23F1\uFE0F ' + item.name + ' \u2013 ' + time;
            list.appendChild(simple);
            return;
        }

        var row = document.createElement('div');
        row.classList.add('temp-item');
        if (item.state === 'paused') row.classList.add('paused');

        var label = (item.state === 'paused' ? '\u23F8\uFE0F ' : '\u23F1\uFE0F ') + item.name + ' \u2013 ' + time;

        var pauseOrResume = item.state === 'paused'
            ? '<button class="temp-btn t-resume" data-taction="resume" data-tid="' + item.timer_id + '" title="Reanudar">\u25B6</button>'
            : '<button class="temp-btn t-pause"  data-taction="pause"  data-tid="' + item.timer_id + '" title="Pausar">\u23F8</button>';

        row.innerHTML =
            '<span class="temp-info">' + label + '</span>' +
            '<span class="temp-btns">' +
                pauseOrResume +
                '<button class="temp-btn t-stop"      data-taction="stop"      data-tid="' + item.timer_id + '" title="Detener">\u25A0</button>' +
                '<button class="temp-btn t-reiniciar" data-taction="reiniciar" data-tid="' + item.timer_id + '" title="Reiniciar">\u21BB</button>' +
            '</span>';

        list.appendChild(row);
    });
}

function tickActiveTemporizadores() {
    if (!activeTemporizadoresCache || activeTemporizadoresCache.length === 0) return;

    var changed = false;

    activeTemporizadoresCache.forEach(function(item) {
        if (item.mode === 'running' && item.state !== 'paused' && item.remaining > 0) {
            item.remaining--;
            changed = true;

            // Acabou de chegar a 0 AO VIVO \u2192 notificar (uma vez por temporizador)
            if (item.remaining <= 0) {
                notificarTemporizadorTerminado(item);
            }
        }
    });

    var antes = activeTemporizadoresCache.length;
    activeTemporizadoresCache = activeTemporizadoresCache.filter(function(item) {
        return item.remaining > 0 || item.state === 'paused';
    });
    if (activeTemporizadoresCache.length !== antes) {
        changed = true;
    }

    if (changed) {
        renderActiveTemporizadores();
    }
}

// Notifica que um temporizador terminou (uma s\u00F3 vez, persistente)
function notificarTemporizadorTerminado(item) {
    var tid = item.timer_id;
    if (!tid) return;
    if (temporizadoresJaNotificados[tid]) return; // j\u00E1 avisado

    playFinishSound('temporizador', item.sound);

    if (typeof window.tomatitoNotify === 'function') {
        window.tomatitoNotify(
            'Temporizador terminado! \u23F1\uFE0F',
            'El temporizador "' + item.name + '" ha llegado a cero.',
            { icon: '\u23F1\uFE0F' }
        );
    }
    marcarTempNotificado(tid);

    // \u26A0\uFE0F NOVO: avisa o servidor que o temporizador terminou \u2014 sem isto,
    // a linha ficava presa em state='running' para sempre no banco (achado
    // via testes no Postman: 31 registros "zumbis" acumulados). Mesmo
    // padr\u00E3o que os pomodoros j\u00E1 usam ao completar uma fase.
    fetch('/wp-json/tomatito/v1/temporizadores/' + tid + '/stop', {
        method: 'POST',
        credentials: 'include',
        headers: { 'X-WP-Nonce': wpApiSettings.nonce }
    }).catch(function(err) {
        console.error('Erro ao marcar temporizador como parado:', err);
    });
}

async function fetchActivePomodoros() {
    try {
        const res = await fetch('/wp-json/tomatito/v1/pomodoros/active?nocache=' + Date.now(), {
            cache: 'no-store',
            credentials: 'include',
            headers: { 'X-WP-Nonce': wpApiSettings.nonce }
        });
        const data = await res.json();

        console.log('POMODOROS ACTIVOS (fetch):', data);

        activePomodorosCache = (data.success && data.data) ? data.data : [];

        renderActivePomodoros();

        // \u26A0\uFE0F CATCH-UP: se a aba ficou fechada/em segundo plano/o
        // computador dormiu enquanto um pomodoro estava a correr, ele pode
        // chegar do servidor J\u00C1 em remaining=0 (fase presa h\u00E1 horas). O
        // tick local (tickActivePomodoros) s\u00F3 detecta a passagem de >0
        // para 0 \u2014 nunca dispara para algo que J\u00C1 chegou zerado. Por isso,
        // aqui, logo ap\u00F3s carregar os dados do servidor, verificamos
        // diretamente se algum item ativo j\u00E1 est\u00E1 zerado e avan\u00E7amos a
        // fase (ou marcamos como aguardando confirma\u00E7\u00E3o manual) na hora.
        activePomodorosCache.forEach(function(item) {
            if (item.state === 'running' && item.remaining <= 0) {
                handlePhaseEnd(item.pomodoro_id);
            }
        });

    } catch (error) {
        console.error('Erro ao carregar pomodoros activos:', error);
    }
}

// ============================================
// SISTEMA DE FASES — Pomodoros Activos do Dashboard
// (mesmo padrão usado na página de Pomodoros)
// ============================================
function phaseIcon(phase) {
    if (phase === 'work')        return '\uD83C\uDF45';
    if (phase === 'short_break') return '\u2615';
    if (phase === 'long_break')  return '\uD83C\uDF3F';
    return '\uD83C\uDF45';
}

// Mesma coisa que phaseIcon(), mas usa a imagem do tomate para a fase
// 'work'. S\u00F3 pode ser usada em contextos HTML (o \u00EDcone do toast) \u2014
// NUNCA no badge de fase (textContent) nem no t\u00EDtulo da notifica\u00E7\u00E3o
// nativa do SO, porque esses dois n\u00E3o renderizam HTML.
function phaseIconHtml(phase) {
    if (phase === 'work') return TOMATITO_TOMATO_IMG;
    return phaseIcon(phase);
}

// Chamado pelo tick local quando o remaining de um pomodoro chega a 0
async function handlePhaseEnd(pid) {
    const item = activePomodorosCache.find(i => Number(i.pomodoro_id) === Number(pid));
    if (!item || advancingPhase[pid] || awaitingAdvance[pid]) return;

    // Regra: work<->short_break/long_break usa 'auto_start';
    // a transição de long_break para a PRÓXIMA REPETIÇÃO usa 'pause_on_end'
    const autoAdvance = (item.phase === 'long_break')
        ? (item.pause_on_end != 1)
        : (item.auto_start == 1);

    if (autoAdvance) {
        await runAdvancePhase(pid);
    } else {
        awaitingAdvance[pid] = true;
        renderActivePomodoros();
    }
}

// Chama a API para avançar de fase (ou finalizar, se for a última)
async function runAdvancePhase(pid) {
    const item = activePomodorosCache.find(i => Number(i.pomodoro_id) === Number(pid));
    if (!item) return;
    advancingPhase[pid] = true;

    try {
        const res = await fetch('/wp-json/tomatito/v1/pomodoros/' + pid + '/advance-phase?nocache=' + Date.now(), {
            method: 'POST',
            cache: 'no-store',
            credentials: 'include',
            headers: { 'X-WP-Nonce': wpApiSettings.nonce }
        });
        const result = await res.json();
        console.log('ADVANCE PHASE (dashboard) result:', result);

        if (!result.success) {
            console.error('Erro ao avan\u00e7ar fase:', result.message);
            delete advancingPhase[pid];
            return;
        }

        if (result.data.is_final) {
            await completePomodoro(pid, item.timer_id);
            return;
        } else {
            item.phase             = result.data.phase;
            item.phase_label       = result.data.phase_label;
            item.cycle             = result.data.cycle;
            item.cycles_total      = result.data.cycles_total;
            item.repetition        = result.data.repetition;
            item.repetitions_total = result.data.repetitions_total;
            item.duration          = result.data.duration;
            item.remaining         = result.data.duration;
            item.state             = 'running';
            delete awaitingAdvance[pid];

            if (typeof playFinishSound === 'function') playFinishSound('pomodoro', item.sound);
            if (typeof window.tomatitoNotify === 'function') {
                const title = '"' + item.name + '" \u2014 ' + (item.phase === 'work'
                    ? '\u00a1De vuelta al trabajo! \uD83D\uDCAA'
                    : '\u00a1Hora del descanso! ' + phaseIcon(item.phase));
                window.tomatitoNotify(title, item.phase_label, { icon: phaseIconHtml(item.phase) });
            }
            renderActivePomodoros();
        }
    } catch (error) {
        console.error('Erro ao avan\u00e7ar fase:', error);
    }

    delete advancingPhase[pid];
}

// Finaliza o pomodoro de vez (todas as repetições completadas)
async function completePomodoro(pid, timerId) {
    // Captura o nome E o som ANTES de remover do cache, para poder
    // identificá-lo e tocar o som certo na notificação
    const finishedItem = activePomodorosCache.find(i => Number(i.pomodoro_id) === Number(pid));
    const finishedName = finishedItem ? finishedItem.name : '';
    const finishedSound = finishedItem ? finishedItem.sound : null;

    try {
        const res = await fetch('/wp-json/tomatito/v1/complete?timer_id=' + (timerId || 0), {
            method: 'POST',
            credentials: 'include',
            headers: { 'X-WP-Nonce': wpApiSettings.nonce }
        });
        const result = await res.json();
        console.log('COMPLETE (dashboard) result:', result);
    } catch (error) {
        console.error('Erro ao completar pomodoro:', error);
    }

    activePomodorosCache = activePomodorosCache.filter(i => Number(i.pomodoro_id) !== Number(pid));
    delete awaitingAdvance[pid];
    delete advancingPhase[pid];

    if (typeof playFinishSound === 'function') playFinishSound('pomodoro', finishedSound);
    if (typeof window.tomatitoNotify === 'function') {
        window.tomatitoNotify('\u00a1Pomodoro completado! \uD83C\uDF89', '"' + finishedName + '" \u2014 has terminado todas las fases.', { icon: '\uD83C\uDF89' });
    }

    renderActivePomodoros();
    loadHistory();
    initDashboard(); // atualiza tamb\u00e9m o card vermelho, caso este pomodoro fosse o favorito
}

// ============================================
// TROCAR O POMODORO DESTACADO — botão-ícone no canto do card + menu compacto.
// Só aparece quando há 2+ pomodoros ativos. O nome não é repetido aqui —
// só aparece uma vez, no título do card (card-title).
// ============================================
function renderFavoritoSelector() {
    const btn  = document.getElementById('favorito-switcher-btn');
    const menu = document.getElementById('favorito-switcher-menu');
    if (!btn || !menu) return;

    if (!activePomodorosCache || activePomodorosCache.length <= 1) {
        btn.style.display  = 'none';
        menu.style.display = 'none';
        menu.innerHTML = '';
        return;
    }

    const selectedId = (principalAtual && principalAtual.kind === 'pomodoro' && principalAtual.source_id)
        ? Number(principalAtual.source_id)
        : Number(activePomodorosCache[0].pomodoro_id);

    menu.innerHTML = '';
    activePomodorosCache.forEach(function(item) {
        if (!item.pomodoro_id) return;
        const opt = document.createElement('button');
        opt.type = 'button';
        opt.className = 'switcher-item' + (Number(item.pomodoro_id) === selectedId ? ' is-selected' : '');
        opt.dataset.id = item.pomodoro_id;
        opt.textContent = '\uD83C\uDF45 ' + item.name;
        menu.appendChild(opt);
    });

    btn.style.display = '';
}

function initFavoritoSelector() {
    const btn  = document.getElementById('favorito-switcher-btn');
    const menu = document.getElementById('favorito-switcher-menu');
    if (!btn || !menu) return;

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        menu.style.display = (menu.style.display === 'none' || !menu.style.display) ? '' : 'none';
    });

    menu.addEventListener('click', function(e) {
        const item = e.target.closest('.switcher-item');
        if (!item) return;
        const id = Number(item.dataset.id);
        menu.style.display = 'none';
        if (id) definirPrincipal('pomodoro', id);
    });

    // Fecha o menu ao clicar fora dele
    document.addEventListener('click', function(e) {
        if (menu.style.display === 'none' || !menu.style.display) return;
        if (menu.contains(e.target) || btn.contains(e.target)) return;
        menu.style.display = 'none';
    });
}

// ============================================
// DRAG AND DROP: arrastar um pomodoro da lista para o card vermelho
// para torn\u00E1-lo favorito.
// ============================================
function initDragDropFavorito() {
    const dropzone = document.querySelector('.tomatito-main-card');
    if (!dropzone) return;

    dropzone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropzone.classList.add('drag-over');
    });
    dropzone.addEventListener('dragleave', function() {
        dropzone.classList.remove('drag-over');
    });
    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropzone.classList.remove('drag-over');
        const id = Number(e.dataTransfer.getData('text/plain'));
        if (id) definirPrincipal('pomodoro', id);
    });
}

function renderActivePomodoros() {
    const section = document.getElementById('active-pomodoros-section');
    const list    = document.getElementById('active-pomodoros-list');
    if (!section || !list) return;

    if (!activePomodorosCache || activePomodorosCache.length === 0) {
        section.style.display = 'none';
        list.innerHTML = '';
        renderFavoritoSelector();
        syncCardFromCache();
        return;
    }

    section.style.display = 'block';
    list.innerHTML = '';

    activePomodorosCache.forEach(item => {
        const remaining = Math.max(0, item.remaining);
        const min = String(Math.floor(remaining / 60)).padStart(2, '0');
        const sec = String(remaining % 60).padStart(2, '0');
        const time = min + ':' + sec;

        const card = document.createElement('div');
        card.classList.add('active-pomodoro-card');
        if (item.state === 'paused') {
            card.classList.add('paused');
        }

        const stateLabel = item.state === 'paused'
            ? '<span class="state-label">Pausado</span>'
            : '';

        const shownCycle = Math.min((item.cycle || 0) + 1, item.cycles_total || 1);
        const phaseBadgeHTML = item.phase_label
            ? '<span class="phase-badge">' + phaseIcon(item.phase) + ' ' + item.phase_label +
              ' \u00b7 Ciclo ' + shownCycle + '/' + (item.cycles_total || 1) + '</span>'
            : '';

        const isPrincipalPom = principalAtual
            && principalAtual.kind === 'pomodoro'
            && Number(principalAtual.source_id) === Number(item.pomodoro_id);

        const estrelaPom =
            '<button class="card-btn btn-principal' + (isPrincipalPom ? ' is-principal' : '') + '"' +
            ' data-pkind="pomodoro" data-pid="' + item.pomodoro_id + '"' +
            ' title="' + (isPrincipalPom ? 'Quitar favorito' : 'Favorito') + '">' +
            (isPrincipalPom ? '\u2B50' : '\u2606') + '</button>';

        let buttonsHTML;
        if (awaitingAdvance[item.pomodoro_id]) {
            const nextLabel = item.phase === 'work'
                ? '\u25B6 \u00bfEmpezar Descanso?'
                : '\u25B6 \u00bfVolver al Trabajo?';
            buttonsHTML =
                '<div class="card-buttons">' +
                    estrelaPom +
                    '<button class="btn-advance-phase" data-advance-id="' + item.pomodoro_id + '">' + nextLabel + '</button>' +
                '</div>';
        } else {
            const pauseOrResume = item.state === 'paused'
                ? '<button class="card-btn btn-resume" data-action="resume" data-id="' + item.pomodoro_id + '" title="Reanudar">\u25B6</button>'
                : '<button class="card-btn btn-pause"  data-action="pause"  data-id="' + item.pomodoro_id + '" title="Pausar">\u23F8</button>';

            buttonsHTML =
                '<div class="card-buttons">' +
                    estrelaPom +
                    pauseOrResume +
                    '<button class="card-btn btn-stop"      data-action="stop"      data-id="' + item.pomodoro_id + '" title="Detener">\u25A0</button>' +
                    '<button class="card-btn btn-reiniciar" data-action="reiniciar" data-id="' + item.pomodoro_id + '" title="Reiniciar">\u21BB</button>' +
                '</div>';
        }

        card.innerHTML =
            '<div class="left-side">' +
                '<span class="name">' + TOMATITO_TOMATO_IMG + ' ' + item.name + stateLabel + '</span>' +
                phaseBadgeHTML +
            '</div>' +
            '<div class="right-side">' +
                '<span class="time">' + time + '</span>' +
                buttonsHTML +
            '</div>';

        // Torna o card arrast\u00E1vel (drag and drop para o card vermelho)
        card.draggable = true;
        card.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', String(item.pomodoro_id));
            card.classList.add('dragging');
        });
        card.addEventListener('dragend', function() {
            card.classList.remove('dragging');
        });

        list.appendChild(card);
    });

    renderFavoritoSelector();
    syncCardFromCache();
}

function tickActivePomodoros() {
    if (!activePomodorosCache || activePomodorosCache.length === 0) return;

    let changed = false;

    activePomodorosCache.forEach(item => {
        if (item.state === 'running' && item.remaining > 0) {
            item.remaining--;
            changed = true;

            // Acabou de chegar a 0 AO VIVO \u2192 aciona o motor de fases
            if (item.remaining <= 0) {
                handlePhaseEnd(item.pomodoro_id);
            }
        }
    });

    if (changed) {
        renderActivePomodoros();
    }
}

// ============================================
// TIMER FAVORITO (escolha do card vermelho)
// Guarda/l\u00EA a escolha e marca a estrela.
// ============================================

// L\u00EA do servidor qual \u00E9 o favorito atual e re-renderiza as listas
async function carregarPrincipal() {
    try {
        const res = await fetch('/wp-json/tomatito/v1/principal?nocache=' + Date.now(), {
            cache: 'no-store',
            credentials: 'include',
            headers: { 'X-WP-Nonce': wpApiSettings.nonce }
        });
        const data = await res.json();
        console.log('PRINCIPAL (carregar):', data);
        principalAtual = (data.success && data.data) ? data.data : null;
    } catch (error) {
        console.error('Erro ao carregar principal:', error);
        principalAtual = null;
    }
    renderActivePomodoros();
    renderActiveTemporizadores();
}

// Guarda no servidor (ou limpa, se source_id = 0)
async function definirPrincipal(kind, sourceId) {
    try {
        const res = await fetch('/wp-json/tomatito/v1/principal?nocache=' + Date.now(), {
            method: 'POST',
            cache: 'no-store',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': wpApiSettings.nonce
            },
            body: JSON.stringify({ kind: kind, source_id: sourceId })
        });
        const data = await res.json();
        console.log('PRINCIPAL (definir):', data);
        principalAtual = (data.success && data.data) ? data.data : null;
    } catch (error) {
        console.error('Erro ao definir principal:', error);
    }
    renderActivePomodoros();
    renderActiveTemporizadores();
    initDashboard();   // re-sincroniza o card vermelho com o novo favorito
}

// Liga os bot\u00F5es de estrela (delega\u00E7\u00E3o nas duas listas)
function initPrincipalActions() {
    function handler(event) {
        const target = event.target.closest('.btn-principal');
        if (!target) return;

        const kind     = target.dataset.pkind;          // 'pomodoro' | 'temporizador'
        const sourceId = Number(target.dataset.pid);
        if (!kind || !sourceId) return;

        // Se j\u00E1 \u00E9 o favorito, clicar outra vez remove-o (toggle)
        const jaEhPrincipal = principalAtual
            && principalAtual.kind === kind
            && Number(principalAtual.source_id) === sourceId;

        if (jaEhPrincipal) {
            definirPrincipal(kind, 0); // limpa
        } else {
            definirPrincipal(kind, sourceId);
        }
    }

    const pomList = document.getElementById('active-pomodoros-list');
    if (pomList) pomList.addEventListener('click', handler);
}

function initActivePomodorosActions() {
    const list = document.getElementById('active-pomodoros-list');
    if (!list) return;

    list.addEventListener('click', async function(event) {

        // ─── AVANÇAR DE FASE MANUALMENTE (botão verde) ───
        const advanceBtn = event.target.closest('.btn-advance-phase');
        if (advanceBtn) {
            const pid = advanceBtn.dataset.advanceId;
            advanceBtn.disabled = true;
            delete awaitingAdvance[pid];
            await runAdvancePhase(pid);
            return;
        }

        const target = event.target.closest('.card-btn');
        if (!target) return;

        const id     = target.dataset.id;
        const action = target.dataset.action;
        if (!id || !action) return;

        if (action === 'stop') {
            const stopItem = activePomodorosCache.find(i => Number(i.pomodoro_id) === Number(id));
            const stopName = stopItem ? stopItem.name : 'este pomodoro';
            if ( ! (await window.tomatitoConfirm('\u00BFDetener "' + stopName + '"?')) ) return;
        }

        if (action === 'reiniciar') {
            try {
                await fetch('/wp-json/tomatito/v1/pomodoros/' + id + '/stop?nocache=' + Date.now(), {
                    method: 'POST', cache: 'no-store', credentials: 'include',
                    headers: { 'X-WP-Nonce': wpApiSettings.nonce }
                });
                const r2 = await fetch('/wp-json/tomatito/v1/pomodoros/' + id + '/start?nocache=' + Date.now(), {
                    method: 'POST', cache: 'no-store', credentials: 'include',
                    headers: { 'X-WP-Nonce': wpApiSettings.nonce }
                });
                const result = await r2.json();
                console.log('REINICIAR (widget) result:', result);
                if (!result.success) {
                    await window.tomatitoAlert('Error: ' + (result.message || 'No se pudo reiniciar'));
                    return;
                }
                delete awaitingAdvance[id];
                delete advancingPhase[id];
                await fetchActivePomodoros();
                loadHistory();
                initDashboard();
            } catch (error) {
                console.error('Erro ao reiniciar (widget):', error);
                await window.tomatitoAlert('Error de conexi\u00F3n al reiniciar');
            }
            return;
        }

        target.disabled = true;
        try {
            const res = await fetch('/wp-json/tomatito/v1/pomodoros/' + id + '/' + action + '?nocache=' + Date.now(), {
                method: 'POST',
                cache: 'no-store',
                credentials: 'include',
                headers: { 'X-WP-Nonce': wpApiSettings.nonce }
            });
            const result = await res.json();
            console.log('ACTION (widget)', action, 'result:', result);
            if (!result.success) {
                await window.tomatitoAlert('Error: ' + (result.message || 'No se pudo completar la acci\u00F3n'));
                target.disabled = false;
                return;
            }
            await fetchActivePomodoros();
            loadHistory();
            initDashboard();
        } catch (error) {
            console.error('Erro na a\u00E7\u00E3o (widget):', error);
            await window.tomatitoAlert('Error de conexi\u00F3n');
            target.disabled = false;
        }
    });
}

function initActiveTemporizadoresActions() {
    const list = document.getElementById('temporizadores-list');
    if (!list) return;

    list.addEventListener('click', async function(event) {
        const target = event.target.closest('.temp-btn');
        if (!target) return;

        const tid    = target.dataset.tid;
        const action = target.dataset.taction;
        if (!tid || !action) return;

        if (action === 'stop') {
            const stopItem = activeTemporizadoresCache.find(i => String(i.timer_id) === String(tid));
            const stopName = stopItem ? stopItem.name : 'este temporizador';
            if ( ! (await window.tomatitoConfirm('\u00BFDetener "' + stopName + '"?')) ) return;
        }

        if (action === 'reiniciar') {
            try {
                const res = await fetch('/wp-json/tomatito/v1/temporizadores/' + tid + '/restart?nocache=' + Date.now(), {
                    method: 'POST', cache: 'no-store', credentials: 'include',
                    headers: { 'X-WP-Nonce': wpApiSettings.nonce }
                });
                const result = await res.json();
                console.log('TEMP ACTION reiniciar result:', result);
                if (!result.success) {
                    await window.tomatitoAlert('Error: ' + (result.message || 'No se pudo reiniciar'));
                    return;
                }
                await fetchActiveTemporizadores();
            } catch (error) {
                console.error('Erro ao reiniciar temporizador:', error);
                await window.tomatitoAlert('Error de conexi\u00F3n');
            }
            return;
        }

        target.disabled = true;
        try {
            const res = await fetch('/wp-json/tomatito/v1/temporizadores/' + tid + '/' + action + '?nocache=' + Date.now(), {
                method: 'POST',
                cache: 'no-store',
                credentials: 'include',
                headers: { 'X-WP-Nonce': wpApiSettings.nonce }
            });
            const result = await res.json();
            console.log('TEMP ACTION', action, 'result:', result);
            if (!result.success) {
                await window.tomatitoAlert('Error: ' + (result.message || 'No se pudo completar la acci\u00F3n'));
                target.disabled = false;
                return;
            }
            await fetchActiveTemporizadores();
        } catch (error) {
            console.error('Erro na a\u00E7\u00E3o do temporizador:', error);
            await window.tomatitoAlert('Error de conexi\u00F3n');
            target.disabled = false;
        }
    });
}

let currentHistoryPage = 1;

async function loadHistory( page ) {

    if ( typeof page === 'number' && page > 0 ) {
        currentHistoryPage = page;
    }

    try {
        const res = await fetch('/wp-json/tomatito/v1/history-sesiones?page=' + currentHistoryPage + '&per_page=4&nocache=' + Date.now(), {
            cache: 'no-store',
            credentials: 'include',
            headers: { 'X-WP-Nonce': wpApiSettings.nonce }
        });
        const data = await res.json();

        if ( ! data.success ) return;

        const list = document.getElementById('history-list');
        if ( ! list ) return;

        list.innerHTML = '';

        if ( data.data.length === 0 ) {
            list.innerHTML = '<p class="history-empty"> Ningun pomodoro hoy </p>';
            renderHistoryPagination( null );
            return;
        }

        data.data.forEach( item => {
            const row = document.createElement('div');
            row.classList.add('row');
            const minutes = Math.round( item.duration / 60 );
            let stateClass = 'cancel';
            let stateLabel = 'Cancelado';
            if ( item.state === 'completed' ) {
                stateClass = 'ok';
                stateLabel = 'Completado';
            }
            if ( item.state === 'paused' ) {
                stateClass = 'progress';
                stateLabel = 'Pausado';
            }
            const time = new Date( item.started_at ).toTimeString().slice( 0, 5 );
            const extra = [];
            if ( item.proyecto_name ) extra.push( item.proyecto_name );
            if ( item.tipo_name )     extra.push( item.tipo_name );
            const extraText = extra.length ? ' \u00B7 ' + extra.join(' \u00B7 ') : '';
            row.innerHTML =
                '<span>' + item.name + extraText + '</span>' +
                '<span>' + minutes + ' min</span>' +
                '<span class="' + stateClass + '">' + stateLabel + '</span>' +
                '<span>' + time + '</span>';
            list.appendChild( row );
        });

        renderHistoryPagination( data.pagination );

    } catch ( error ) {
        console.error( 'Erro ao carregar hist\u00F3rico:', error );
        const list = document.getElementById('history-list');
        if ( list ) {
            list.innerHTML = '<p class="history-empty">Nenhum pomodoro hoje ainda</p>';
        }
    }
}

function renderHistoryPagination( pagination ) {

    const nav = document.getElementById('history-pagination');
    if ( ! nav ) return;

    nav.innerHTML = '';

    if ( ! pagination || pagination.total_pages <= 1 ) {
        return;
    }

    const page  = pagination.page;
    const total = pagination.total_pages;

    const prev = document.createElement('button');
    prev.innerText = '\u2039';
    prev.setAttribute('aria-label', 'P\u00E1gina anterior');
    prev.disabled = ( page <= 1 );
    prev.addEventListener('click', function() { loadHistory( page - 1 ); });
    nav.appendChild( prev );

    for ( let i = 1; i <= total; i++ ) {
        const btn = document.createElement('button');
        btn.innerText = i;
        btn.setAttribute('aria-label', 'P\u00E1gina ' + i);
        if ( i === page ) {
            btn.classList.add('active');
            btn.setAttribute('aria-current', 'page');
        }
        btn.addEventListener('click', function() { loadHistory( i ); });
        nav.appendChild( btn );
    }

    const next = document.createElement('button');
    next.innerText = '\u203A';
    next.setAttribute('aria-label', 'P\u00E1gina siguiente');
    next.disabled = ( page >= total );
    next.addEventListener('click', function() { loadHistory( page + 1 ); });
    nav.appendChild( next );
}

let searchGranularity = 'day';
let searchBaseDate     = null;
let searchPage         = 1;
let searchHasSearched  = false;

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

    searchHasSearched = true;

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
            + '&per_page=6'
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
}

async function initDashboard() {
    try {
        const res = await fetch('/wp-json/tomatito/v1/current?nocache=' + Date.now(), {
			cache: 'no-store',
			credentials: 'include',
			headers: { 'X-WP-Nonce': wpApiSettings.nonce }
		});
        const data = await res.json();
        console.log('CURRENT:', data);
			if (data) {
				lastToday = data.last_today || null;

				if (data.success) {
					if (data.data) {
						state    = data.data.state    || 'stopped';
						duration = Number(data.data.duration)  || 1500;
						timeLeft = Number(data.data.remaining) || 0;
						currentName = data.data.name || null;

						currentTimerId          = data.data.timer_id    || null;
						currentPomodoroId       = data.data.pomodoro_id || null;
						currentPhase            = data.data.phase       || null;
						currentPhaseLabel       = data.data.phase_label || null;
						currentCycle            = Number(data.data.cycle)             || 0;
						currentCyclesTotal      = Number(data.data.cycles_total)      || 1;
						currentRepetition       = Number(data.data.repetition)        || 0;
						currentRepetitionsTotal = Number(data.data.repetitions_total) || 1;
						currentAutoStart        = Number(data.data.auto_start);
						currentPauseOnEnd       = Number(data.data.pause_on_end);

						if (state === 'running' && timeLeft <= 0) {
							state    = 'stopped';
							timeLeft = null;
						}

						if (state !== 'running') {
							if (state !== 'paused') {
								timeLeft = null;
							}
						}
					}
				} else {
					state    = 'stopped';
					timeLeft = null;
					currentName = null;
					currentTimerId    = null;
					currentPomodoroId = null;
					currentPhase      = null;
					currentPhaseLabel = null;
					cardAwaitingAdvance = false;
				}
			}
    } catch(e) {
        console.error('Erro ao carregar current:', e);
    }
    // Não há mais relógio próprio aqui: se o pomodoro do card já estiver em
    // activePomodorosCache (populado por fetchActivePomodoros), essa chamada
    // sobrescreve timeLeft/state/phase com os valores mais atuais do cache
    // compartilhado. Caso ainda não tenha chegado, fica com o que /current
    // acabou de devolver até a próxima sincronização (poucos segundos).
    syncCardFromCache();
}

document.addEventListener('DOMContentLoaded', function () {
    initDashboard();
    loadHistory();
    initActivePomodorosActions();
    initActiveTemporizadoresActions();
    initPrincipalActions();             // liga os bot\u00F5es \u2B50 Favorito
    initFavoritoSelector();             // liga o seletor dentro do card vermelho
    initDragDropFavorito();             // liga o arrastar-e-soltar para o card vermelho
    fetchActiveTemporizadores();
    fetchActivePomodoros();
    carregarPrincipal();                // l\u00EA qual \u00E9 o favorito guardado
    loadUpcomingAlarmas();
    setInterval(fetchActiveTemporizadores, 30000);
    setInterval(tickActiveTemporizadores, 1000);
    setInterval(fetchActivePomodoros, 30000);
    setInterval(tickActivePomodoros, 1000);
});

</script>
    <?php
}
add_shortcode( 'tomatito_dashboard', 'tomatito_dashboard_shortcode' );

/**
 * ============================================================
 * AUTO-REDIRECT PARA O LOGIN
 * ============================================================
 * Quem entra na app sem sess\u00e3o vai DIRETO para /login/, em vez de ver
 * uma mensagem "Debes iniciar sesión" e ter de clicar.
 *
 * Porqu\u00ea aqui e n\u00e3o dentro do shortcode: um shortcode s\u00f3 corre a meio da
 * renderiza\u00e7\u00e3o do conte\u00fado, quando o cabe\u00e7alho HTML j\u00e1 foi enviado ao
 * browser. Um redirect precisa de mandar um header HTTP, e headers t\u00eam de
 * sair ANTES de qualquer output (caso contr\u00e1rio d\u00e1 "headers already sent"
 * e o redirect n\u00e3o acontece). O hook 'template_redirect' corre depois de o
 * WordPress j\u00e1 saber que p\u00e1gina vai carregar, mas antes de imprimir seja o
 * que for \u2014 \u00e9 o s\u00edtio certo para decidir isto.
 *
 * Deteta a p\u00e1gina pelo SHORTCODE (has_shortcode) e n\u00e3o pelo nome/slug: assim
 * continua a funcionar se a p\u00e1gina for renomeada ou movida.
 */
add_action( 'template_redirect', 'tomatito_forzar_login' );
function tomatito_forzar_login() {

    // J\u00e1 tem sess\u00e3o, ou est\u00e1 no wp-admin / numa chamada da API: n\u00e3o mexe.
    if ( is_user_logged_in() || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return;
    }

    global $post;
    if ( ! $post instanceof WP_Post ) {
        return;
    }

    // S\u00f3 protege a p\u00e1gina que cont\u00e9m o dashboard.
    // \u26a0\ufe0f Para proteger tamb\u00e9m /pomodoros/, /temporizadores/, /alarmas/ e
    // /ajustes/, acrescenta aqui os shortcodes dessas p\u00e1ginas, por exemplo:
    // $shortcodes = array( 'tomatito_dashboard', 'tomatito_pomodoros', ... );
    $shortcodes = array( 'tomatito_dashboard' );

    $protegida = false;
    foreach ( $shortcodes as $sc ) {
        if ( has_shortcode( $post->post_content, $sc ) ) {
            $protegida = true;
            break;
        }
    }
    if ( ! $protegida ) {
        return;
    }

    wp_safe_redirect( home_url( '/login/' ) );
    exit; // obrigat\u00f3rio: sem isto o WordPress continuaria a montar a p\u00e1gina
}
