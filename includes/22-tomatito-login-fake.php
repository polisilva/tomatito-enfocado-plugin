<?php

/* TOMATITO - PÁGINA DE LOGIN FAKE (provisório até integração Omkrom) */

function tomatito_login_fake_shortcode() {

    // Se está no admin, não faz nada (evita erros no editor)
    if ( is_admin() ) {
        return '';
    }

    // Se já está logado, mostra link para o Dashboard (sem redirect imediato)
    if ( is_user_logged_in() ) {
        return '<p style="text-align:center; padding:40px;">Ya estás logado. <a href="/dashboard/">Ir al Dashboard</a></p>';
    }

    // ============================================================
    // IMAGENS  👉 COLA AQUI AS URLs DA BIBLIOTECA DE MEDIOS
    // ============================================================
    // $logo_url  = tomate-relógio pequeno, no topo do cartão branco.
    //              Se ficar vazio, entra o emoji 🍅 como antes.
    // $hero_url  = ilustração grande do tomate-relógio, no canto
    //              inferior esquerdo. Se ficar vazio, o canto fica
    //              limpo (a página continua a funcionar na mesma).
    // A mesma $hero_url é reaproveitada, muito esbatida, como
    // decoração de fundo — não precisas de imagens extra para isso.
    $logo_url = esc_url( content_url( 'uploads/2026/08/tomatito-icon.png' ) );
    $hero_url = esc_url( content_url( 'uploads/2026/08/tomatito-icon.png' ) );

    // Verifica se houve tentativa de login (POST)
    $error_message = '';

    if ( isset( $_POST['tomatito_login_submit'] ) ) {

        $creds = array(
            'user_login'    => sanitize_text_field( $_POST['tomatito_user'] ?? '' ),
            'user_password' => $_POST['tomatito_pass'] ?? '',
            'remember'      => true,
        );

        $user = wp_signon( $creds, is_ssl() );

        if ( is_wp_error( $user ) ) {
            $error_message = 'Email o contraseña incorrectos.';
        } else {
            echo '<script>window.location.href="/dashboard/";</script>';
            exit;
        }
    }

    ob_start();
    ?>

    <style>
    /* Reset wrappers do WordPress para esta página */
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

    body .tomatito-sidebar,
    body .tomatito-content > h1 {
        display: none;
    }

    /* Tira limitações de wrappers do WordPress */
	body {
		margin: 0 !important;
		padding: 0 !important;
		overflow: hidden;
	}

	.tomatito-login-page {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		width: 100%;
		height: 100%;
		/* Degradê diagonal: mais claro em cima à direita, mais intenso
		   em baixo à esquerda — como no mockup. */
		background: linear-gradient(150deg, #ff8189 0%, #fb5c67 45%, #f0454f 100%);
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 20px;
		box-sizing: border-box;
		z-index: 999999;
		overflow: hidden;
		font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
	}

	/* ============================================================ */
	/* DECORAÇÃO DO FUNDO                                            */
	/* Tudo aqui é decorativo: fica atrás do cartão (z-index) e não  */
	/* recebe cliques (pointer-events:none), para nunca atrapalhar   */
	/* o formulário.                                                 */
	/* ============================================================ */
	.tomatito-login-page .deco {
		position: absolute;
		pointer-events: none;
		user-select: none;
		z-index: 0;
	}

	/* Onda creme na base — SVG, acompanha a largura do ecrã */
	.tomatito-login-page .deco-wave {
		left: 0;
		right: 0;
		bottom: 0;
		width: 100%;
		line-height: 0;
	}
	.tomatito-login-page .deco-wave svg {
		display: block;
		width: 100%;
		height: 26vh;
		min-height: 140px;
	}

	/* Grelhas de pontinhos — feitas só com CSS (radial-gradient),
	   sem imagens. Cada bloco é uma grelha 5x5. */
	.tomatito-login-page .deco-dots {
		width: 116px;
		height: 116px;
		background-image: radial-gradient(rgba(255,255,255,0.55) 2.4px, transparent 2.5px);
		background-size: 24px 24px;
	}
	.tomatito-login-page .deco-dots-tl { top: 8%;  left: 4%; }
	.tomatito-login-page .deco-dots-br { bottom: 22%; right: 5%; }

	/* Tomates esbatidos no fundo (reutilizam a ilustração principal) */
	.tomatito-login-page .deco-ghost {
		opacity: 0.10;
		filter: saturate(0.6);
	}
	.tomatito-login-page .deco-ghost-1 {
		top: 6%;
		left: 22%;
		width: 190px;
		transform: rotate(-8deg);
	}
	.tomatito-login-page .deco-ghost-2 {
		top: 12%;
		right: 14%;
		width: 240px;
		transform: rotate(10deg);
	}

	/* Ilustração principal, em baixo à esquerda, por cima da onda */
	.tomatito-login-page .deco-hero {
		left: 3%;
		bottom: 4%;
		width: min(340px, 24vw);
		max-width: 340px;
		z-index: 1;
	}
	/* ⚠️ Esta regra NÃO pode definir width: as larguras vêm de cada .deco-*
	   acima. Um `width: 100%` aqui ganharia por especificidade (img.deco é
	   mais específico do que .deco-hero) e as imagens ocupariam o ecrã todo. */
	.tomatito-login-page img.deco {
		height: auto;
		display: block;
	}

	/* Em ecrãs estreitos a decoração só ia roubar espaço ao cartão */
	@media (max-width: 1100px) {
		.tomatito-login-page .deco-hero,
		.tomatito-login-page .deco-ghost,
		.tomatito-login-page .deco-dots-br {
			display: none;
		}
	}

    .tomatito-login-card {
        background: #fff;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        width: 100%;
        max-width: 400px;
        box-sizing: border-box;
        /* fica acima de toda a decoração */
        position: relative;
        z-index: 2;
    }

    .tomatito-login-card .logo {
        text-align: center;
        margin-bottom: 8px;
    }

    .tomatito-login-card .logo .emoji {
        font-size: 48px;
        display: block;
        margin-bottom: 8px;
    }

    /* Logo em imagem (quando $logo_url está preenchido) */
    .tomatito-login-card .logo .logo-img {
        display: block;
        width: 96px;
        height: 96px;
        object-fit: contain;
        margin: 0 auto 10px;
    }

    .tomatito-login-card .logo .brand {
        font-size: 24px;
        font-weight: 600;
        color: #111;
    }

    .tomatito-login-card .logo .brand .red {
        color: #ff4d4d;
    }

    .tomatito-login-card .subtitle {
        text-align: center;
        color: #6b7280;
        font-size: 14px;
        margin-bottom: 32px;
    }

    .tomatito-login-card .field {
        margin-bottom: 16px;
    }

    .tomatito-login-card .field label {
        display: block;
        font-size: 13px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }

    .tomatito-login-card .field input {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        box-sizing: border-box;
        font-family: inherit;
        transition: border-color 0.2s;
    }

    .tomatito-login-card .field input:focus {
        outline: none;
        border-color: #ff4d4d;
    }

    .tomatito-login-card .btn-login {
        width: 100%;
        padding: 14px;
        background: linear-gradient(180deg, #ff6b6b, #ff4d4d);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 8px;
        transition: 0.2s;
        font-family: inherit;
    }

    .tomatito-login-card .btn-login:hover {
        background: linear-gradient(180deg, #ff5a5a, #ff3333);
    }

    .tomatito-login-card .error {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 13px;
        margin-bottom: 16px;
        text-align: center;
    }

    .tomatito-login-card .notice {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 12px;
        margin-top: 20px;
        text-align: center;
    }
    </style>

    <div class="tomatito-login-page">

        <!-- ===== DECORAÇÃO (não interativa) ===== -->
        <div class="deco deco-dots deco-dots-tl" aria-hidden="true"></div>
        <div class="deco deco-dots deco-dots-br" aria-hidden="true"></div>

        <?php if ( $hero_url ) : ?>
            <img class="deco deco-ghost deco-ghost-1" src="<?php echo esc_url( $hero_url ); ?>" alt="" aria-hidden="true">
            <img class="deco deco-ghost deco-ghost-2" src="<?php echo esc_url( $hero_url ); ?>" alt="" aria-hidden="true">
        <?php endif; ?>

        <!-- Onda creme da base -->
        <div class="deco deco-wave" aria-hidden="true">
            <svg viewBox="0 0 1440 320" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path fill="#f8ede3" d="M0,120 C240,40 480,180 720,190 C960,200 1200,120 1440,150 L1440,320 L0,320 Z"></path>
            </svg>
        </div>

        <?php if ( $hero_url ) : ?>
            <img class="deco deco-hero" src="<?php echo esc_url( $hero_url ); ?>" alt="" aria-hidden="true">
        <?php endif; ?>

        <!-- ===== CARTÃO DE LOGIN ===== -->
        <div class="tomatito-login-card">

            <div class="logo">
                <?php if ( $logo_url ) : ?>
                    <img class="logo-img" src="<?php echo esc_url( $logo_url ); ?>" alt="Tomatito Enfocado">
                <?php else : ?>
                    <span class="emoji">🍅</span>
                <?php endif; ?>
                <span class="brand"><span class="red">Tomatito</span> Enfocado</span>
            </div>

            <p class="subtitle">Bienvenido de nuevo</p>

            <?php if ( $error_message ) : ?>
                <div class="error"><?php echo esc_html( $error_message ); ?></div>
            <?php endif; ?>

            <form method="POST">

                <div class="field">
                    <label for="tomatito_user">Email o usuario</label>
                    <input type="text" id="tomatito_user" name="tomatito_user" required autofocus>
                </div>

                <div class="field">
                    <label for="tomatito_pass">Contraseña</label>
                    <input type="password" id="tomatito_pass" name="tomatito_pass" required>
                </div>

                <button type="submit" name="tomatito_login_submit" class="btn-login">
                    Entrar
                </button>

            </form>

            <div class="notice">
                ⚠️ Login provisional para tests<br>
                <small>Será reemplazado por Omkrom Hub</small>
            </div>

        </div>
    </div>

    <?php
    return ob_get_clean();
}

add_shortcode( 'tomatito_login_fake', 'tomatito_login_fake_shortcode' );
