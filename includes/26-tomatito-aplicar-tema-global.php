<?php

add_action( 'wp_head', function() {
    if ( ! is_user_logged_in() ) return;
    ?>
    <style id="tomatito-focus-style">
        body button:focus,
        body button:focus-visible,
        body a:focus,
        body a:focus-visible,
        body input[type="button"]:focus,
        body input[type="button"]:focus-visible,
        body input[type="submit"]:focus,
        body input[type="submit"]:focus-visible,
        body select:focus,
        body select:focus-visible {
            outline: none !important;
            outline-offset: 0 !important;
            box-shadow: inset 0 0 0 2px #ff4d4d !important;
        }
        /* Selects personalizados: evita el anillo nativo negro del tema
           en el primer clic, antes de abrir el menú. */
        body .tsel .tsel-btn:focus,
        body .tsel .tsel-btn:focus-visible {
            outline: none !important;
            outline-offset: 0 !important;
            border-color: #ff4d4d !important;
            box-shadow: 0 0 0 3px rgba(255,77,77,0.12) !important;
        }
        /* Outros controles nativos usados no formulário de Pomodoro. */
        body details summary:focus,
        body details summary:focus-visible {
            outline: 2px solid #ff4d4d !important;
            outline-offset: -2px !important;
        }
        body input[type="checkbox"]:focus,
        body input[type="checkbox"]:focus-visible {
            outline: none !important;
            box-shadow: 0 0 0 2px #ff4d4d !important;
        }
    </style>
    <script>
    // Aplica tema o mais cedo possível, evitando "flash" do tema claro
    (function() {
        try {
            const ajustes = JSON.parse( localStorage.getItem('tomatito_ajustes') || '{}' );
            if ( ajustes.tema === 'oscuro' ) {
                document.documentElement.classList.add('tomatito-dark-html');
                document.addEventListener('DOMContentLoaded', function() {
                    document.body.classList.add('tomatito-dark');
                });
            }
        } catch(e) {}
    })();
    </script>
    <?php
}, 1 );
