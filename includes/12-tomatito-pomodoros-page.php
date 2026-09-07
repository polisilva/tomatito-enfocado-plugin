<?php

function tomatito_pomodoros_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p>Debes iniciar sesión.</p>';
    }
	
	// Vai buscar os CPTs para os dropdowns
    $proyectos    = get_posts( array( 'post_type' => 'proyectos',    'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
    $tipos_sesion = get_posts( array( 'post_type' => 'tipo_sesion',  'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
    
    ob_start();
    ?>
	
    <style>
    /* ============================================== */
    /* PÁGINA POMODOROS - LIMPEZA DOS WRAPPERS DO WP  */
    /* ============================================== */
		
	.emoji {
		pointer-events: none;
	}
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
    /* HEADER DA PÁGINA POMODOROS                     */
    /* ============================================== */
    .tomatito-pomodoros-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 80px;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 24px;
    }
    .tomatito-pomodoros-header h1 {
        font-size: 28px;
        font-weight: 600;
        margin: 0;
    }
    /* Botão "Nuevo Pomodoro" */
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
    .pomodoros-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        align-items: start;
    }
    .pomodoros-layout.form-open {
        grid-template-columns: 1fr 360px;
    }
    .pomodoros-main {
        min-width: 0;
    }
    /* ============================================== */
    /* DRAWER DE POMODOROS — mesmo UX do Hub/Alarmas */
    /* Mantém as cores do Tomatito; altera apenas UX. */
    /* ============================================== */
    .pomodoros-drawer-backdrop {
        position: fixed; inset: 0; z-index: 99998;
        background: rgba(20,24,35,0.38);
        backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px);
        opacity: 0; visibility: hidden; pointer-events: none;
        transition: opacity 0.22s ease, visibility 0.22s ease;
    }
    .pomodoros-drawer-backdrop.is-open {
        opacity: 1; visibility: visible; pointer-events: auto;
    }
    .pomodoros-form {
        position: fixed; top: 0; right: 0; bottom: 0;
        z-index: 99999; width: min(460px, 100vw); height: 100vh;
        box-sizing: border-box; margin: 0; border: 0; border-radius: 0;
        padding: 24px; background: #fff;
        box-shadow: -20px 0 60px rgba(20,24,35,0.18);
        overflow-y: auto; transform: translateX(100%);
        visibility: hidden; pointer-events: none;
        transition: transform 0.22s ease, visibility 0.22s ease;
    }
    .pomodoros-form.is-open {
        transform: translateX(0); visibility: visible; pointer-events: auto;
    }
    body.tomatito-pomodoros-drawer-open { overflow: hidden; }
    .pomodoros-form .drawer-head {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 16px; margin-bottom: 22px;
    }
    .pomodoros-form .drawer-head h2 { margin: 0; font-size: 20px; }
    .pomodoros-form .drawer-close {
        flex: 0 0 auto; width: 38px; height: 38px; padding: 0;
        display: grid; place-items: center; border: 1px solid #e5e7eb;
        border-radius: 11px; background: #fff; color: #374151;
        font-size: 22px; line-height: 1; cursor: pointer; transition: 0.2s;
    }
    .pomodoros-form .drawer-close:hover {
        background: #f3f4f6; border-color: #d1d5db;
    }
    @media (max-width: 520px) {
        .pomodoros-form { width: 100vw; padding: 20px; }
    }

    /* ============================================== */
    /* BARRA DE FILTROS                               */
    /* ============================================== */
    .pomodoros-filters {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        align-items: center;
    }
    .pomodoros-filters input {
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        background: #fff;
        flex: 1;
    }
    .pomodoros-filters input:focus {
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
       filtros, o savePomodoro() e o preview de som leem e escrevem
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
    .tsel-btn:focus-visible {
        outline: none;
        border-color: #ff4d4d;
        box-shadow: 0 0 0 3px rgba(255,77,77,0.12);
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
    /* TABELA DE POMODOROS                            */
    /* ============================================== */
    .pomodoros-table {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.04);
    }
    .pomodoros-table-header {
		display: grid;
		grid-template-columns: 1.5fr 1fr 1fr 1fr 1.2fr auto;
        padding: 14px 20px;
        background: #f8fafc;
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
        border-bottom: 1px solid #eee;
    }
    .pomodoros-table .pomodoro-row {
		display: grid;
		grid-template-columns: 1.5fr 1fr 1fr 1fr 1.2fr auto;
        align-items: center;
        padding: 14px 20px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }
    .pomodoros-table .pomodoro-row:last-child {
        border-bottom: none;
    }
    .pomodoros-table .pomodoro-row .name-wrap {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }
    .pomodoros-table .pomodoro-row .name {
        font-weight: 500;
    }
    .pomodoros-table .pomodoro-row .phase-badge {
        font-size: 11px;
        color: #ff4d4d;
        font-weight: 600;
        white-space: nowrap;
    }
    .pomodoros-table .pomodoro-row.is-paused .phase-badge {
        color: #f59e0b;
    }
    .pomodoros-table .pomodoro-row .last-used {
    color: #6b7280;
    font-size: 13px;
}
	.pomodoros-table .pomodoro-row .time-left {
		font-size: 14px;
		font-weight: 600;
		color: #9ca3af;
		font-variant-numeric: tabular-nums;
	}
	.pomodoros-table .pomodoro-row.is-active .time-left {
		color: #ff4d4d;
	}
	.pomodoros-table .pomodoro-row.is-active.is-paused .time-left {
		color: #f59e0b;
	}
	.pomodoros-table .pomodoro-row.is-active {
		background: #fef9e7;
	}
	.pomodoros-table .pomodoro-row.is-active.is-paused {
		background: #fef3c7;
	}
    /* Mensagem de loading / vazio */
    .pomodoros-table .loading,
    .pomodoros-table .empty {
        text-align: center;
        color: #9ca3af;
        padding: 30px;
        font-size: 14px;
    }
    /* ============================================== */
    /* CONTADOR                                       */
    /* ============================================== */
    .pomodoros-counter {
        color: #6b7280;
        font-size: 13px;
        margin: 16px 0 0;
    }
    /* ============================================== */
    /* AÇÕES NA LINHA (Iniciar/Editar/Eliminar)       */
    /* ============================================== */
    .pomodoro-row .actions {
        display: flex;
        gap: 6px;
        align-items: center;
    }
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
	
	/* Botões ações dos pomodoros ATIVOS */
	.btn-active-action {
		width: 36px;
		height: 36px;
		padding: 0;
		background: transparent;
		border: 1px solid #e5e7eb;
		border-radius: 8px;
		font-size: 18px;
		line-height: 1;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: 0.2s;
		color: #374151;
	}
    .btn-active-action:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }
    .btn-active-action.btn-pause:hover,
    .btn-active-action.btn-resume:hover {
        border-color: #f59e0b;
        color: #f59e0b;
    }
    .btn-active-action.btn-stop:hover {
        border-color: #ef4444;
        color: #ef4444;
        background: #fee2e2;
    }
    .btn-active-action.btn-reiniciar:hover {
        border-color: #2ecc71;
        color: #2ecc71;
    }

    /* Botão de avançar de fase manualmente (auto_start = 0) */
    .btn-advance-phase {
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
    .btn-advance-phase:hover {
        background: #27ae60;
    }
    .btn-advance-phase:disabled {
        opacity: 0.6;
        cursor: wait;
    }

    /* Controle de som: select + botão de preview lado a lado */
    /* ⚠️ A label desta linha NÃO pode crescer (flex:1), senão empurra o
       select + botão para fora do cartão. Por isso a linha do som usa a
       classe .sound-row, onde a label fica do tamanho do texto e é o
       conjunto select+botão que ocupa o espaço restante. */
    .form-row.sound-row {
        gap: 12px;
    }
    .form-row.sound-row label {
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
	
	button * {
    pointer-events: none;
	}
    /* ============================================== */
    /* FORMULÁRIO LATERAL                             */
    /* ============================================== */
    .pomodoros-form {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.04);
    }
    .pomodoros-form h2 {
        font-size: 20px;
        margin: 0 0 20px;
    }
    .pomodoros-form h3 {
        font-size: 14px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 24px 0 12px;
    }
    .form-group {
        margin-bottom: 16px;
    }
    .form-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 12px;
    }
    .form-row label {
        font-size: 14px;
        color: #374151;
        flex: 1;
    }
    .form-row label small {
        color: #9ca3af;
        font-weight: normal;
    }
    .pomodoros-form input[type="text"],
    .pomodoros-form input[type="number"] {
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        background: #fff;
        width: 100%;
    }
    .pomodoros-form input:focus {
        outline: none;
        border-color: #ff4d4d;
    }
    .input-with-unit {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .input-with-unit input {
        width: 70px !important;
        text-align: center;
    }
    .input-with-unit .unit {
        font-size: 13px;
        color: #6b7280;
    }
    .long-break-row {
        flex-wrap: nowrap;
        gap: 6px;
        font-size: 13px;
        color: #6b7280;
    }
    .long-break-row label {
        flex: 0 0 auto;
    }
    .long-break-row input.small {
        width: 46px;
        padding: 8px 4px;
        text-align: center;
        flex: 0 0 auto;
    }
    /* O input do descanso longo e o "min" ficam sempre juntos — nunca
       separados, mesmo com pouco espaço na linha. */
    .long-break-row .long-break-value {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
        flex: 0 0 auto;
    }
    .checkbox-row {
        justify-content: flex-start;
    }
    .checkbox-row input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #ff4d4d;
        cursor: pointer;
    }
    .checkbox-row label {
        cursor: pointer;
        flex: none;
    }
    .advanced-options {
        margin: 20px 0;
    }
    .advanced-options summary {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        padding: 8px 0;
        user-select: none;
    }
    .advanced-options summary:hover {
        color: #ff4d4d;
    }
    .form-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f0;
    }
    .form-actions button {
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
    .btn-new-secondary {
        display: inline-block;
        margin: 16px 0 0;
    }
    /* ============================================== */
    /* SETA + DETALHES EXPANSÍVEIS NA TABELA          */
    /* ============================================== */
    .pomodoro-row .toggle-arrow {
        background: transparent;
        border: none;
        font-size: 14px;
        color: #9ca3af;
        cursor: pointer;
        transition: transform 0.2s;
        padding: 4px 8px;
        justify-self: end;
    }
    .pomodoro-row.expanded .toggle-arrow {
        transform: rotate(180deg);
    }
    .pomodoro-details-row {
        display: none;
        padding: 16px 20px;
        border-bottom: 1px solid #f0f0f0;
        background: #fafbfc;
        font-size: 13px;
        color: #4b5563;
    }
    .pomodoro-details-row.expanded {
        display: block;
    }
    .pomodoro-details-row .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        max-width: 400px;
    }
    .pomodoro-details-row .detail-row .label {
        color: #6b7280;
    }
    .pomodoro-details-row .detail-row .value {
        font-weight: 500;
        color: #111827;
    }
	/* ============================================== */
	/* PAGINAÇÃO                                      */
	/* ============================================== */
	.pomodoros-pagination {
		display: flex;
		justify-content: center;
		align-items: center;
		gap: 6px;
		margin-top: 16px;
		flex-wrap: wrap;
	}
	.pomodoros-pagination button {
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
	.pomodoros-pagination button:hover:not(:disabled):not(.active) {
		background: #f3f4f6;
		border-color: #d1d5db;
	}
	.pomodoros-pagination button.active {
		background: #ff4d4d;
		color: #fff;
		border-color: #ff4d4d;
		font-weight: 600;
	}
	.pomodoros-pagination button:disabled {
		opacity: 0.4;
		cursor: not-allowed;
	}
	
    
    /* OVERRIDES DO DRAWER — mantêm o estilo do formulário, mas sem layout lateral interno. */
    .pomodoros-form {
        position: fixed !important;
        top: 0 !important; right: 0 !important; bottom: 0 !important;
        z-index: 99999 !important;
        width: min(460px, 100vw) !important;
        height: 100vh !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        padding: 24px !important;
        background: #fff !important;
        box-shadow: -20px 0 60px rgba(20,24,35,0.18) !important;
        overflow-y: auto !important;
        transform: translateX(100%) !important;
        visibility: hidden !important;
        pointer-events: none !important;
        transition: transform 0.22s ease, visibility 0.22s ease !important;
    }
    .pomodoros-form.is-open {
        transform: translateX(0) !important;
        visibility: visible !important;
        pointer-events: auto !important;
    }
    .pomodoros-drawer-backdrop {
        position: fixed !important;
        inset: 0 !important;
        z-index: 99998 !important;
    }
    .pomodoros-form .drawer-head {
        display: flex !important;
        align-items: flex-start !important;
        justify-content: space-between !important;
        gap: 16px !important;
        margin-bottom: 22px !important;
    }
    .pomodoros-form .drawer-head h2 {
        margin: 0 !important;
        font-size: 20px !important;
    }
    .pomodoros-form .drawer-close {
        flex: 0 0 auto !important;
        width: 38px !important;
        height: 38px !important;
        padding: 0 !important;
        display: grid !important;
        place-items: center !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 11px !important;
        background: #fff !important;
        color: #374151 !important;
        font-size: 22px !important;
        line-height: 1 !important;
        cursor: pointer !important;
    }
    @media (max-width: 520px) {
        .pomodoros-form { width: 100vw !important; padding: 20px !important; }
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
            <!-- HEADER da página -->
            <div class="tomatito-pomodoros-header">
                <h1>Pomodoros</h1>
                <button class="btn-primary" id="btn-new-pomodoro">+ Nuevo Pomodoro</button>
            </div>
            <!-- LAYOUT: tabela à esquerda, formulário à direita -->
            <div class="pomodoros-layout">
                <!-- COLUNA ESQUERDA: TABELA -->
					<div class="pomodoros-main">
						<!-- BARRA DE FILTROS -->
						<div class="pomodoros-filters">
							<input type="text" id="filter-search" placeholder="🔍 Buscar...">
							<select id="filter-type">
								<option value="todos">Todos</option>
								<option value="recientes">Recientes</option>
								<option value="nunca-usados">Nunca usados</option>
							</select>
							<select id="filter-sort">
								<option value="recientes">Ordenar por Recientes</option>
								<option value="nombre">Ordenar por Nombre</option>
								<option value="duracion">Ordenar por Duración</option>
							</select>
						</div>
						<!-- TABELA -->
						<div class="pomodoros-table">
							<div class="pomodoros-table-header">
								<span>Nombre</span>
								<span>Tiempo restante</span>
								<span>Duración</span>
								<span>Último uso</span>
								<span>Acciones</span>
								<span></span>
							</div>
							<div id="pomodoros-list">
								<p class="loading">Cargando pomodoros...</p>
							</div>
						</div>
						<!-- /pomodoros-table -->
						<!-- PAGINAÇÃO DA TABELA -->
						<nav id="pomodoros-table-pagination" class="pomodoros-pagination" aria-label="Paginación de la tabla"></nav>
						<!-- BOTÃO DUPLICADO -->
						<button class="btn-primary btn-new-secondary" id="btn-new-pomodoro-2">+ Nuevo Pomodoro</button>
						<!-- CONTADOR -->
						<p class="pomodoros-counter" id="pomodoros-counter"></p>
					</div>
					<!-- /pomodoros-main -->
					<!-- COLUNA DIREITA: FORMULÁRIO (escondido por defeito) -->
					<div class="pomodoros-drawer-backdrop" id="pomodoro-drawer-backdrop"></div>
					<aside class="pomodoros-form" id="pomodoro-form" aria-hidden="true">
						<div class="drawer-head">
							<div>
								<h2 id="form-title">Crear Pomodoro</h2>
							</div>
							<button type="button" class="drawer-close" id="btn-close-pomodoro" aria-label="Cerrar">×</button>
						</div>
						<input type="hidden" id="field-id" value="">
						<div class="form-group">
							<input type="text" id="field-name" placeholder="Mañana productiva">
						</div>
                    <h3>Configuración</h3>
                    <div class="form-row">
                        <label>Trabajo</label>
                        <div class="input-with-unit">
                            <input type="number" id="field-work" value="25" min="1">
                            <span class="unit">min</span>
                        </div>
                    </div>
                    <div class="form-row">
                        <label>Descanso corto</label>
                        <div class="input-with-unit">
                            <input type="number" id="field-short-break" value="5" min="0">
                            <span class="unit">min</span>
                        </div>
                    </div>
                    <div class="form-row long-break-row">
                        <label>Cada</label>
                        <input type="number" id="field-cycles" value="4" min="1" class="small">
                        <span>ciclos → Descanso</span>
                        <span class="long-break-value">
                            <input type="number" id="field-long-break" value="15" min="0" class="small">
                            <span class="unit">min</span>
                        </span>
                    </div>
                    <details class="advanced-options">
                        <summary>Opciones avanzadas</summary>
                        <div class="form-row">
                            <label>Repetir secuencia <small>(vacío = en bucle)</small></label>
                            <input type="number" id="field-repetitions" min="1" placeholder="∞">
                        </div>
                        <div class="form-row checkbox-row">
                            <input type="checkbox" id="field-auto-start" checked>
                            <label for="field-auto-start">Auto-iniciar siguiente fase</label>
                        </div>
                        <div class="form-row checkbox-row">
                            <input type="checkbox" id="field-pause-on-end">
                            <label for="field-pause-on-end">Pausar al finalizar la sesión</label>
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
                        <div class="form-row checkbox-row">
                            <input type="checkbox" id="field-vibration" checked>
                            <label for="field-vibration">Vibración</label>
                        </div>
                        <div class="form-row checkbox-row">
                            <input type="checkbox" id="field-sync" checked>
                            <label for="field-sync">Sincronizar en todos mis dispositivos</label>
                        </div>
                    </details>
                    <div class="form-actions">
                        <button class="btn-warning" id="btn-save-pomodoro">Guardar</button>
                        <button class="btn-launch" id="btn-save-launch">▶ Guardar y lanzar</button>
                        <button class="btn-secondary" id="btn-cancel-pomodoro">Cancelar</button>
                    </div>
                </aside>
            </div>
            <?php if ( function_exists( 'tomatito_render_ad_footer' ) ) { tomatito_render_ad_footer(); } ?>
        </main>
    </div>
    <script>
	// ============================================
	// ÍCONE DO TOMATE (imagem, usada nos toasts de notificação)
	// ============================================
	var TOMATITO_TOMATO_URL = '<?php echo esc_url( content_url( 'uploads/2026/08/tomatito-icon.png' ) ); ?>';
	var TOMATITO_TOMATO_IMG = '<img src="' + TOMATITO_TOMATO_URL + '" style="width:16px;height:16px;vertical-align:-3px;object-fit:contain;">';

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

		// ⚠️ Esta página tem `button * { pointer-events: none }`, por isso o
		// alvo do clique é sempre o próprio .tsel-option — o closest() abaixo
		// funciona nos dois casos.
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
		document.querySelectorAll('.pomodoros-layout select').forEach(enhanceSelect);
	}

	// clicar fora fecha qualquer painel aberto
	document.addEventListener('click', function() { tselCloseAll(null); });

	// ============================================
	// FORMATO DE HORA (12h / 24h)
	// ============================================
	function formatTime(date) {
		const ajustes = JSON.parse( localStorage.getItem('tomatito_ajustes') || '{}' );
		const formato = ajustes.hora || '24h';
		const d = (date instanceof Date) ? date : new Date(date);
		if (formato === '12h') {
			return d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', hour12: true });
		}
		return d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', hour12: false });
	}
								
    // ============================================
    // VARIÁVEL GLOBAL: lista de pomodoros
    // ============================================
let allPomodoros = [];
let activePomodorosMap = {}; // pomodoro_id => { state, remaining, phase, cycle, ... }
let expandedPomodoros = {};  // pomodoro_id => true/false (linhas expandidas)

// ⚠️ NOVO: controle de transição de fase
let awaitingAdvance = {}; // pomodoro_id => true, esperando clique manual do usuário
let advancingPhase  = {}; // pomodoro_id => true, pedido em curso (evita duplo clique/duplo disparo)

// Paginação
const PER_PAGE_TABLE = 10;
let currentTablePage = 1;
    // ============================================
    // CARREGAR / RENDERIZAR
    // ============================================
async function loadPomodoros() {
    try {
        const res  = await fetch('/wp-json/tomatito/v1/pomodoros?nocache=' + Date.now(), {
            cache: 'no-store',
            credentials: 'include',
            headers: { 'X-WP-Nonce': wpApiSettings.nonce }
        });
        const data = await res.json();
        console.log('POMODOROS:', data);
        if (!data.success) return;
        allPomodoros = data.data;
        renderPomodoros();
    } catch (error) {
        console.error('Erro ao carregar pomodoros:', error);
        const list = document.getElementById('pomodoros-list');
        if (list) list.innerHTML = '<p class="empty">Error al cargar pomodoros.</p>';
    }
}
// ============================================
// POMODOROS ATIVOS — sincroniza com servidor
// ⚠️ ALTERADO: agora guarda também phase/cycle/repetition/auto_start/pause_on_end
// ============================================
async function fetchActiveMap() {
    try {
        const res = await fetch('/wp-json/tomatito/v1/pomodoros/active?nocache=' + Date.now(), {
            cache: 'no-store',
            credentials: 'include',
            headers: { 'X-WP-Nonce': wpApiSettings.nonce }
        });
        const data = await res.json();
        console.log('ACTIVE MAP (fetch):', data);
        // Reconstrói o map do zero
        activePomodorosMap = {};
        if (data.success && data.data) {
            data.data.forEach(item => {
                activePomodorosMap[item.pomodoro_id] = {
                    timer_id:          item.timer_id,
                    name:              item.name,
                    state:             item.state,
                    remaining:         item.remaining,
                    duration:          item.duration,
                    phase:             item.phase,
                    phase_label:       item.phase_label,
                    cycle:             item.cycle,
                    cycles_total:      item.cycles_total,
                    repetition:        item.repetition,
                    repetitions_total: item.repetitions_total,
                    auto_start:        item.auto_start,
                    pause_on_end:      item.pause_on_end,
                    sound:             item.sound,
                };
            });
        }
        renderPomodoros();

        // \u26A0\uFE0F CATCH-UP: mesmo motivo do Dashboard \u2014 se a aba ficou
        // fechada/em segundo plano (ou o computador dormiu) enquanto um
        // pomodoro corria, ele pode chegar do servidor J\u00C1 zerado. O tick
        // local s\u00F3 detecta a passagem de >0 para 0, nunca algo que j\u00E1
        // chegou em 0 \u2014 ent\u00E3o verificamos aqui, logo ap\u00F3s carregar os
        // dados do servidor, se algum pomodoro ativo j\u00E1 est\u00E1 zerado e
        // avan\u00E7amos a fase (ou marcamos aguardando confirma\u00E7\u00E3o manual).
        Object.keys(activePomodorosMap).forEach(function(pid) {
            const item = activePomodorosMap[pid];
            if (item.state === 'running' && item.remaining <= 0) {
                handlePhaseEnd(pid);
            }
        });

    } catch (error) {
        console.error('Erro ao carregar pomodoros activos:', error);
    }
}

// ============================================
// SISTEMA DE FASES — o que acontece quando o tempo chega a 0
// ============================================
function phaseIcon(phase) {
    if (phase === 'work')        return '\uD83C\uDF45';
    if (phase === 'short_break') return '\u2615';
    if (phase === 'long_break')  return '\uD83C\uDF3F';
    return '\uD83C\uDF45';
}

// Mesma coisa que phaseIcon(), mas usa a imagem do tomate para a fase
// 'work'. S\u00F3 pode ser usada em contextos HTML (o \u00EDcone do toast) \u2014
// NUNCA no badge de fase (innerHTML j\u00E1 monta texto solto ali) nem no
// t\u00EDtulo da notifica\u00E7\u00E3o nativa do SO, que n\u00E3o renderiza HTML.
function phaseIconHtml(phase) {
    if (phase === 'work') return TOMATITO_TOMATO_IMG;
    return phaseIcon(phase);
}

// Chamado pelo tick local quando o remaining de um pomodoro chega a 0
async function handlePhaseEnd(pid) {
    const item = activePomodorosMap[pid];
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
        renderPomodoros();
    }
}

// Chama a API para avançar de fase (ou finalizar, se for a última)
async function runAdvancePhase(pid) {
    const item = activePomodorosMap[pid];
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
        console.log('ADVANCE PHASE result:', result);

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
            renderPomodoros();
        }
    } catch (error) {
        console.error('Erro ao avan\u00e7ar fase:', error);
    }

    delete advancingPhase[pid];
}

// Finaliza o pomodoro de vez (todas as repetições completadas)
async function completePomodoro(pid, timerId) {
    // Captura o nome E o som ANTES de remover do map, para poder
    // identificá-lo e tocar o som certo na notificação
    const finishedName  = activePomodorosMap[pid] ? activePomodorosMap[pid].name  : '';
    const finishedSound = activePomodorosMap[pid] ? activePomodorosMap[pid].sound : null;

    try {
        const res = await fetch('/wp-json/tomatito/v1/complete?timer_id=' + (timerId || 0), {
            method: 'POST',
            credentials: 'include',
            headers: { 'X-WP-Nonce': wpApiSettings.nonce }
        });
        const result = await res.json();
        console.log('COMPLETE result:', result);
    } catch (error) {
        console.error('Erro ao completar pomodoro:', error);
    }

    delete activePomodorosMap[pid];
    delete awaitingAdvance[pid];
    delete advancingPhase[pid];

    if (typeof playFinishSound === 'function') playFinishSound('pomodoro', finishedSound);
    if (typeof window.tomatitoNotify === 'function') {
        window.tomatitoNotify('\u00a1Pomodoro completado! \uD83C\uDF89', '"' + finishedName + '" \u2014 has terminado todas las fases.', { icon: '\uD83C\uDF89' });
    }

    await loadPomodoros(); // atualiza "Último uso" na tabela
    renderPomodoros();
}

    function renderPomodoros() {
        const list       = document.getElementById('pomodoros-list');
        const counter    = document.getElementById('pomodoros-counter');
        const search     = document.getElementById('filter-search').value.toLowerCase().trim();
        const filterType = document.getElementById('filter-type').value;
        const sortBy     = document.getElementById('filter-sort').value;
        if (!list) return;
        // 1. Filtrar pela pesquisa
        let filtered = allPomodoros.filter(p => {
            if (search && !p.name.toLowerCase().includes(search)) return false;
            return true;
        });
        // 2. Filtrar pelo tipo
        if (filterType === 'recientes') {
            filtered = filtered.filter(p => {
                if (!p.last_used_at) return false;
                const daysSince = (Date.now() - new Date(p.last_used_at).getTime()) / (1000 * 60 * 60 * 24);
                return daysSince <= 7;
            });
        } else if (filterType === 'nunca-usados') {
            filtered = filtered.filter(p => !p.last_used_at);
        }
        // 3. Ordenar
        filtered.sort((a, b) => {
            if (sortBy === 'nombre') {
                return a.name.localeCompare(b.name);
            }
            if (sortBy === 'duracion') {
                return (parseInt(b.work) || 0) - (parseInt(a.work) || 0);
            }
            const dateA = a.last_used_at ? new Date(a.last_used_at).getTime() : 0;
            const dateB = b.last_used_at ? new Date(b.last_used_at).getTime() : 0;
            return dateB - dateA;
        });
        // 4. Atualizar contador
        counter.innerText = `Mostrando ${filtered.length} de ${allPomodoros.length} pomodoros guardados`;
        // 5. PAGINAR para a tabela
        const totalTablePages = Math.max(1, Math.ceil(filtered.length / PER_PAGE_TABLE));
        if (currentTablePage > totalTablePages) currentTablePage = totalTablePages;
        const tableStart = (currentTablePage - 1) * PER_PAGE_TABLE;
        const tableItems = filtered.slice(tableStart, tableStart + PER_PAGE_TABLE);
        // 6. Mostrar na tabela (só os da página atual)
        list.innerHTML = '';
        if (filtered.length === 0) {
            list.innerHTML = '<p class="empty">No se han encontrado pomodoros.</p>';
        } else {
            tableItems.forEach(item => {
                const row = document.createElement('div');
                row.classList.add('pomodoro-row');
                row.dataset.id = item.id;
                const isExpanded = !!expandedPomodoros[item.id];
                if (isExpanded) row.classList.add('expanded');
                // Verifica se está ativo
                const active = activePomodorosMap[item.id];
                let timeText = '--:--';
                let actionsHTML = '';
                let phaseBadgeHTML = '';
                if (active) {
                    row.classList.add('is-active');
                    if (active.state === 'paused') {
                        row.classList.add('is-paused');
                    }
                    const remaining = Math.max(0, active.remaining);
                    const min = String(Math.floor(remaining / 60)).padStart(2, '0');
                    const sec = String(remaining % 60).padStart(2, '0');
                    timeText = min + ':' + sec;

                    if (active.phase_label) {
                        const shownCycle = Math.min((active.cycle || 0) + 1, active.cycles_total || 1);
                        phaseBadgeHTML = '<span class="phase-badge">' + phaseIcon(active.phase) + ' ' +
                            active.phase_label + ' \u00b7 Ciclo ' + shownCycle + '/' + (active.cycles_total || 1) +
                            '</span>';
                    }

                    if (awaitingAdvance[item.id]) {
                        const nextLabel = active.phase === 'work'
                            ? '\u00bfEmpezar Descanso?'
                            : '\u00bfVolver al Trabajo?';
                        actionsHTML = '<button class="btn-advance-phase" data-id="' + item.id + '">\u25B6 ' + nextLabel + '</button>';
                    } else {
						const pauseOrResume = active.state === 'paused'
							? '<button class="btn-active-action btn-resume" data-action="resume" data-id="' + item.id + '" title="Reanudar">\u25B6\uFE0E</button>'
							: '<button class="btn-active-action btn-pause"  data-action="pause"  data-id="' + item.id + '" title="Pausar">⏸</button>';
						actionsHTML =
							pauseOrResume +
							'<button class="btn-active-action btn-stop" data-action="stop"  data-id="' + item.id + '" title="Detener">■</button>' +
						   '<button class="btn-active-action btn-reiniciar" data-action="reiniciar" data-id="' + item.id + '" title="Reiniciar">\u21BB</button>';
                    }
					} else {
						actionsHTML =
							'<button class="btn-iniciar" data-id="' + item.id + '" title="Iniciar"><span class="play-icon">\u25B6\uFE0E</span></button>' +
							'<button class="btn-edit"    data-id="' + item.id + '" title="Editar">✏️</button>' +
							'<button class="btn-delete"  data-id="' + item.id + '" title="Eliminar">🗑️</button>';
					}
                row.innerHTML =
                    '<span class="name-wrap"><span class="name">' + item.name + '</span>' + phaseBadgeHTML + '</span>' +
                    '<span class="time-left">' + timeText + '</span>' +
                    '<span class="duration">' + item.duration_label + '</span>' +
                    '<span class="last-used">' + item.last_used_label + '</span>' +
                    '<span class="actions">' + actionsHTML + '</span>' +
                    '<button class="toggle-arrow" data-toggle-id="' + item.id + '" title="Detalles">▾</button>';
                list.appendChild(row);
                // Linha de detalhes (expansível)
                const detailsRow = document.createElement('div');
                detailsRow.classList.add('pomodoro-details-row');
                detailsRow.dataset.detailsId = item.id;
                if (isExpanded) detailsRow.classList.add('expanded');
                detailsRow.innerHTML = `
                    <div class="detail-row">
                        <span class="label">Trabajo</span>
                        <span class="value">${item.work} min</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Descanso corto</span>
                        <span class="value">${item.short_break} min</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Descanso largo</span>
                        <span class="value">${item.long_break} min (cada ${item.cycles} ciclos)</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Repeticiones</span>
                        <span class="value">${item.repetitions || '∞ (en bucle)'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Auto-iniciar</span>
                        <span class="value">${item.auto_start == 1 ? 'Sí' : 'No'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Sonido</span>
                        <span class="value">${item.sound === 'silent' ? 'Silencioso' : 'Default'}</span>
                    </div>
                `;
                list.appendChild(detailsRow);
            });
        }
        // 7. Renderizar paginação da tabela
        renderTablePagination(totalTablePages);
    }
	// ============================================
	// PAGINAÇÃO — renderizar botões
	// ============================================
	function renderTablePagination(totalPages) {
		const nav = document.getElementById('pomodoros-table-pagination');
		if (!nav) return;
		nav.innerHTML = '';
		if (totalPages <= 1) return;
		const prev = document.createElement('button');
		prev.innerText = '‹';
		prev.disabled = currentTablePage <= 1;
		prev.addEventListener('click', function() {
			currentTablePage--;
			renderPomodoros();
		});
		nav.appendChild(prev);
		for (let i = 1; i <= totalPages; i++) {
			const btn = document.createElement('button');
			btn.innerText = i;
			if (i === currentTablePage) btn.classList.add('active');
			btn.addEventListener('click', function() {
				currentTablePage = i;
				renderPomodoros();
			});
			nav.appendChild(btn);
		}
		const next = document.createElement('button');
		next.innerText = '›';
		next.disabled = currentTablePage >= totalPages;
		next.addEventListener('click', function() {
			currentTablePage++;
			renderPomodoros();
		});
		nav.appendChild(next);
	}
    // Reagir a mudanças nos filtros (volta para página 1)
function onFilterChange() {
    currentTablePage = 1;
    renderPomodoros();
}
document.getElementById('filter-search').addEventListener('input', onFilterChange);
document.getElementById('filter-type').addEventListener('change', onFilterChange);
document.getElementById('filter-sort').addEventListener('change', onFilterChange);
    // ============================================
    // FORMULÁRIO: ABRIR / FECHAR
    // ============================================
    function openPomodoroForm( pomodoro = null ) {
        const form     = document.getElementById('pomodoro-form');
        const backdrop = document.getElementById('pomodoro-drawer-backdrop');
        const title    = document.getElementById('form-title');

        // Abre como drawer, sem alterar o layout da página por baixo.
        form.classList.add('is-open');
        form.setAttribute('aria-hidden', 'false');
        form.inert = false;
        backdrop.classList.add('is-open');
        document.body.classList.add('tomatito-pomodoros-drawer-open');

        if ( pomodoro ) {
            // Modo EDITAR
            title.innerText = 'Editar Pomodoro';
            document.getElementById('field-id').value             = pomodoro.id;
            document.getElementById('field-name').value           = pomodoro.name;
            document.getElementById('field-work').value           = pomodoro.work;
            document.getElementById('field-short-break').value    = pomodoro.short_break;
            document.getElementById('field-long-break').value     = pomodoro.long_break;
            document.getElementById('field-cycles').value         = pomodoro.cycles;
            document.getElementById('field-repetitions').value    = pomodoro.repetitions || '';
            document.getElementById('field-auto-start').checked   = !!pomodoro.auto_start;
            document.getElementById('field-pause-on-end').checked = !!pomodoro.pause_on_end;
            document.getElementById('field-sound').value          = pomodoro.sound || 'default';
            document.getElementById('field-vibration').checked    = !!pomodoro.vibration;
            document.getElementById('field-sync').checked         = !!pomodoro.sync;
        } else {
            // Modo CRIAR
            title.innerText = 'Crear Pomodoro';
            document.getElementById('field-id').value             = '';
            document.getElementById('field-name').value           = '';
            document.getElementById('field-work').value           = 25;
            document.getElementById('field-short-break').value    = 5;
            document.getElementById('field-long-break').value     = 15;
            document.getElementById('field-cycles').value         = 4;
            document.getElementById('field-repetitions').value    = '';
            document.getElementById('field-auto-start').checked   = true;
            document.getElementById('field-pause-on-end').checked = false;
            document.getElementById('field-sound').value          = 'default';
            document.getElementById('field-vibration').checked    = true;
            document.getElementById('field-sync').checked         = true;
        }

        syncCustomSelects();
    }

    function closePomodoroForm() {
        const form     = document.getElementById('pomodoro-form');
        const backdrop = document.getElementById('pomodoro-drawer-backdrop');

        form.classList.remove('is-open');
        form.setAttribute('aria-hidden', 'true');
        form.inert = true;
        backdrop.classList.remove('is-open');
        document.body.classList.remove('tomatito-pomodoros-drawer-open');
    }
    document.getElementById('btn-new-pomodoro').addEventListener('click', function() { openPomodoroForm(); });
    document.getElementById('btn-new-pomodoro-2').addEventListener('click', function() { openPomodoroForm(); });
    document.getElementById('btn-cancel-pomodoro').addEventListener('click', function() {
        closePomodoroForm();
    });

    document.getElementById('btn-close-pomodoro').addEventListener('click', closePomodoroForm);
    document.getElementById('pomodoro-drawer-backdrop').addEventListener('click', closePomodoroForm);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const form = document.getElementById('pomodoro-form');
            if (form && form.classList.contains('is-open')) closePomodoroForm();
        }
    });

    document.getElementById('pomodoro-form').inert = true;

    // ============================================
    // PREVIEW DE SOM (botão "Escuchar" no formulário)
    // ============================================
    let previewAudio = null;

    function playPreview(soundName) {
        // 'default' n\u00E3o \u00E9 um arquivo de som \u2014 resolve pro que est\u00E1 configurado
        // em Ajustes pra pomodoros antes de tocar
        if (soundName === 'default') {
            try {
                const ajustes = JSON.parse(localStorage.getItem('tomatito_ajustes') || '{}');
                soundName = ajustes['sonido_pomodoro'] || 'clasico';
            } catch (e) {
                soundName = 'clasico';
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
    // FORMULÁRIO: GUARDAR (CRIAR OU EDITAR)
    // ============================================
	async function savePomodoro() {
		const id = document.getElementById('field-id').value;
		const data = {
			name:         document.getElementById('field-name').value.trim(),
			work:         parseInt( document.getElementById('field-work').value )         || 25,
			short_break:  parseInt( document.getElementById('field-short-break').value )  || 5,
			long_break:   parseInt( document.getElementById('field-long-break').value )   || 15,
			cycles:       parseInt( document.getElementById('field-cycles').value )       || 4,
			repetitions:  document.getElementById('field-repetitions').value
							? parseInt( document.getElementById('field-repetitions').value )
							: null,
			auto_start:   document.getElementById('field-auto-start').checked   ? 1 : 0,
			pause_on_end: document.getElementById('field-pause-on-end').checked ? 1 : 0,
			sound:        document.getElementById('field-sound').value,
			vibration:    document.getElementById('field-vibration').checked    ? 1 : 0,
			sync:         document.getElementById('field-sync').checked         ? 1 : 0,
		};
		if ( ! data.name ) {
			await window.tomatitoAlert('El nombre es obligatorio');
			return;
		}
		const isEdit = !!id;
		const url    = isEdit
			? `/wp-json/tomatito/v1/pomodoros/${id}`
			: `/wp-json/tomatito/v1/pomodoros`;
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
				await window.tomatitoAlert('Error: ' + (result.message || 'No se pudo guardar el pomodoro'));
				return;
			}
			await loadPomodoros();
			return result.data ? result.data.id : id;
		} catch (error) {
			console.error('Erro ao guardar pomodoro:', error);
			await window.tomatitoAlert('Error de conexión al guardar el pomodoro');
		}
	}
    // Botão "Guardar"
    document.getElementById('btn-save-pomodoro').addEventListener('click', async function() {
        const id = await savePomodoro();
        if (id) {
            closePomodoroForm();
        }
    });
    // Botão "Guardar y lanzar"
    document.getElementById('btn-save-launch').addEventListener('click', async function() {
        const id = await savePomodoro();
        if (id) {
            try {
                const res    = await fetch(`/wp-json/tomatito/v1/pomodoros/${id}/start`, {
					method: 'POST',
					credentials: 'include',
					headers: { 'X-WP-Nonce': wpApiSettings.nonce }
				});
				const result = await res.json();
                if (result.success) {
                    window.location.href = '/dashboard/';
                } else {
                    await window.tomatitoAlert('Pomodoro guardado, pero no se pudo iniciar: ' + (result.message || ''));
                    closePomodoroForm();
                }
            } catch (error) {
                console.error('Erro ao iniciar:', error);
                await window.tomatitoAlert('Pomodoro guardado, pero error de conexión al iniciar');
                closePomodoroForm();
            }
        }
    });
    // ============================================
    // AÇÕES NAS LINHAS: INICIAR / EDITAR / ELIMINAR / TOGGLE
    // ============================================
    document.getElementById('pomodoros-list').addEventListener('click', async function(event) {
		
		 console.log('Clique na lista');
        const target = event.target;
        // ─── TOGGLE DETALHES (seta) ───
        if ( target.matches('.toggle-arrow') ) {
            const toggleId = target.dataset.toggleId;
            expandedPomodoros[toggleId] = !expandedPomodoros[toggleId];
            renderPomodoros();
            return;
        }

        // ─── AVANÇAR DE FASE MANUALMENTE (botão verde) ───
        if ( target.matches('.btn-advance-phase') ) {
            const pid = target.dataset.id;
            target.disabled = true;
            delete awaitingAdvance[pid];
            await runAdvancePhase(pid);
            return;
        }

        const id     = target.dataset.id;
        if ( ! id ) return;
        // ─── INICIAR ───
        if ( target.matches('.btn-iniciar') ) {
            try {
                const res    = await fetch(`/wp-json/tomatito/v1/pomodoros/${id}/start`, {
					method: 'POST',
					credentials: 'include',
					headers: { 'X-WP-Nonce': wpApiSettings.nonce }
				});
                const result = await res.json();
                console.log('START result:', result);
                if ( result.success ) {
                    window.location.href = '/dashboard/';
                } else {
                    await window.tomatitoAlert('Error: ' + (result.message || 'No se pudo iniciar el pomodoro'));
                }
            } catch (error) {
                console.error('Erro ao iniciar pomodoro:', error);
                await window.tomatitoAlert('Error de conexión al iniciar el pomodoro');
            }
        }
		
		// ─── PAUSE / RESUME / STOP / REINICIAR (pomodoros ativos) ───
        if ( target.matches('.btn-active-action') ) {
            const action = target.dataset.action; // 'pause' / 'resume' / 'stop' / 'reiniciar'
            if (action === 'stop') {
                const stopItem = activePomodorosMap[id];
                const stopName = stopItem ? stopItem.name : 'este pomodoro';
                if ( ! (await window.tomatitoConfirm('¿Detener "' + stopName + '"?')) ) return;
            }
            // 'reiniciar' usa endpoint /start (que já para + começa de novo)
            // mas precisa parar primeiro
            let endpoint = action;
            if (action === 'reiniciar') {
                // Primeiro fazer STOP, depois START
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
                    console.log('REINICIAR result:', result);
                    if (!result.success) {
                        await window.tomatitoAlert('Error: ' + (result.message || 'No se pudo reiniciar'));
                        return;
                    }
                    // Reiniciar volta sempre à fase 'work': limpa qualquer estado pendente
                    delete awaitingAdvance[id];
                    delete advancingPhase[id];
                    await fetchActiveMap();
                } catch (error) {
                    console.error('Erro ao reiniciar:', error);
                    await window.tomatitoAlert('Error de conexión al reiniciar');
                }
                return;
            }
            // Caso normal: pause/resume/stop
            target.disabled = true;
            try {
                const res = await fetch('/wp-json/tomatito/v1/pomodoros/' + id + '/' + endpoint + '?nocache=' + Date.now(), {
                    method: 'POST',
                    cache: 'no-store',
                    credentials: 'include',
                    headers: { 'X-WP-Nonce': wpApiSettings.nonce }
                });
                const result = await res.json();
                console.log('ACTION', action, 'result:', result);
                if (!result.success) {
                    await window.tomatitoAlert('Error: ' + (result.message || 'No se pudo completar la acción'));
                    target.disabled = false;
                    return;
                }
                if (action === 'stop') {
                    delete awaitingAdvance[id];
                    delete advancingPhase[id];
                }
                await fetchActiveMap();
            } catch (error) {
                console.error('Erro na ação:', error);
                await window.tomatitoAlert('Error de conexión');
                target.disabled = false;
            }
            return;
        }
        // ─── EDITAR ───
				if ( target.matches('.btn-edit') ) {
					try {
						const res    = await fetch('/wp-json/tomatito/v1/pomodoros', {
							credentials: 'include',
							headers: { 'X-WP-Nonce': wpApiSettings.nonce }
						});
						const result = await res.json();
						if ( ! result.success ) return;
						const pomodoro = result.data.find( p => p.id == id );
						if ( pomodoro ) {
							openPomodoroForm( pomodoro );
						}
					} catch (error) {
						console.error('Erro ao carregar pomodoro:', error);
					}
				}												   
        // ─── ELIMINAR ───
     
				if ( target.matches('.btn-delete') ) {
				const delPomodoro = allPomodoros.find( p => p.id == id );
				const delName     = delPomodoro ? delPomodoro.name : 'este pomodoro';
				if ( ! (await window.tomatitoConfirm('¿Estás seguro de que quieres eliminar "' + delName + '"?')) ) return;
					try {
						const res    = await fetch(`/wp-json/tomatito/v1/pomodoros/${id}`, {
							method: 'DELETE',
							credentials: 'include',
							headers: { 'X-WP-Nonce': wpApiSettings.nonce }
						});
                const result = await res.json();
                console.log('DELETE result:', result);
                if ( result.success ) {
                    await loadPomodoros();
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
// ============================================
    // TICK — decrementa tempo localmente, todos os segundos
    // ⚠️ ALTERADO: quando chega a 0, aciona handlePhaseEnd() em vez de
    // simplesmente ficar parado sem fazer nada.
    // ============================================
    function tickActivePomodorosTable() {
        let changed = false;
        Object.keys(activePomodorosMap).forEach(pid => {
            const item = activePomodorosMap[pid];
            if (item.state === 'running' && item.remaining > 0) {
                item.remaining--;
                changed = true;
                if (item.remaining <= 0) {
                    handlePhaseEnd(pid);
                }
            }
        });
        if (changed) {
            renderPomodoros();
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        initCustomSelects();                             // ⚠️ NOVO: veste os selects
        loadPomodoros();
        fetchActiveMap();                                // 1ª chamada
        setInterval(fetchActiveMap, 30000);              // sincroniza com servidor a cada 30s
        setInterval(tickActivePomodorosTable, 1000);     // decrementa localmente a cada 1s
        // Se vieram do dashboard com ?new=pomodoro, abre o formulário automaticamente
        const params = new URLSearchParams(window.location.search);
        if ( params.get('new') === 'pomodoro' ) {
            openPomodoroForm();
        }
    });
	
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tomatito_pomodoros', 'tomatito_pomodoros_shortcode' );
