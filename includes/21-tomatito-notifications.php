<?php

/* TOMATITO - SISTEMA DE NOTIFICAÇÕES (toast + som + permissão + vigilância global) */

add_action( 'wp_footer', function () {

    // Só carregar para utilizadores logados
    if ( ! is_user_logged_in() ) {
        return;
    }
    ?>
    <style>
        /* TOAST estilo iOS */
        #tomatito-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .tomatito-toast {
            background: rgba(30, 30, 30, 0.95);
            color: #fff;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
            min-width: 280px;
            max-width: 360px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 14px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            opacity: 0;
            transform: translateX(120%);
            transition: opacity 0.3s ease, transform 0.3s ease;
            pointer-events: auto;
        }

        .tomatito-toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .tomatito-toast .toast-title {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 15px;
        }

        .tomatito-toast .toast-message {
            opacity: 0.9;
            font-size: 13px;
        }

        .tomatito-toast .toast-icon {
            display: inline-block;
            margin-right: 6px;
        }

.tomatito-confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999999;
    opacity: 0;
    transition: opacity 0.2s ease;
}
.tomatito-confirm-overlay.show {
    opacity: 1;
}
.tomatito-confirm-box {
    background: #fff;
    border-radius: 14px;
    padding: 24px;
    width: 90%;
    max-width: 360px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
    transform: translateY(10px);
    transition: transform 0.2s ease;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
.tomatito-confirm-overlay.show .tomatito-confirm-box {
    transform: translateY(0);
}
.tomatito-confirm-title {
    font-size: 17px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 8px;
}
.tomatito-confirm-message {
    font-size: 14px;
    color: #4b5563;
    margin-bottom: 22px;
    line-height: 1.4;
}
.tomatito-confirm-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
.tomatito-confirm-actions button {
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    font-family: inherit;
}
.tomatito-confirm-cancel {
    background: #f3f4f6;
    color: #374151;
}
.tomatito-confirm-cancel:hover {
    background: #e5e7eb;
}
.tomatito-confirm-ok {
    background: #ef4444;
    color: #fff;
}
.tomatito-confirm-ok:hover {
    background: #dc2626;
}		   
		   
		   
    </style>

    <div id="tomatito-toast-container"></div>

    <script>
    (function() {

        // Pedir permissão de notificação ao abrir a página (uma vez)
        if (typeof Notification !== 'undefined') {
            if (Notification.permission === 'default') {
                Notification.requestPermission();
            }
        }


        // ============================================
        // SONS DE TÉRMINO (por tipo, configurados em Ajustes)
        // Agora GLOBAL — funciona em qualquer página, não só no Dashboard
        // ============================================
        var TOMATITO_SOUNDS_URL = '<?php echo esc_url( content_url( 'uploads/2026/07/' ) ); ?>';
        var TOMATITO_PEEEEM_URL = '<?php echo esc_url( plugin_dir_url( __FILE__ ) . '../sounds/peeeem.mp3' ); ?>';
        var TOMATITO_TOMATO_URL = '<?php echo esc_url( content_url( 'uploads/2026/07/tomato.png' ) ); ?>';
        var TOMATITO_TOMATO_IMG = '<img src="' + TOMATITO_TOMATO_URL + '" style="width:16px;height:16px;vertical-align:-3px;object-fit:contain;">';
        var tomatitoAudioUnlocked = false;
        var tomatitoAlarmAudioContext = null;
        var tomatitoAlarmAudioElement = null;
        var tomatitoAlarmAudioTimeout = null;

        function stopAlarmSiren() {
            if (tomatitoAlarmAudioContext) {
                try { tomatitoAlarmAudioContext.close(); } catch (e) {}
                tomatitoAlarmAudioContext = null;
            }
            if (tomatitoAlarmAudioElement) {
                try { tomatitoAlarmAudioElement.pause(); } catch (e) {}
                tomatitoAlarmAudioElement = null;
            }
            if (tomatitoAlarmAudioTimeout) {
                clearTimeout(tomatitoAlarmAudioTimeout);
                tomatitoAlarmAudioTimeout = null;
            }
        }

        // Som de alarme propositalmente estridente: duas frequências alternadas
        // durante 45 segundos, para tornar o aviso impossível de ignorar.
        function playAlarmSiren() {
            try {
                stopAlarmSiren();
                var AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (!AudioContextClass) return;

                var ctx = new AudioContextClass();
                tomatitoAlarmAudioContext = ctx;
                var start = ctx.currentTime;

                for (var i = 0; i < 60; i++) {
                    var oscillator = ctx.createOscillator();
                    var gain = ctx.createGain();
                    var when = start + (i * 0.75);

                    oscillator.type = 'square';
                    oscillator.frequency.value = (i % 2 === 0) ? 880 : 1320;
                    gain.gain.setValueAtTime(0.0001, when);
                    gain.gain.exponentialRampToValueAtTime(0.22, when + 0.03);
                    gain.gain.exponentialRampToValueAtTime(0.0001, when + 0.62);
                    oscillator.connect(gain);
                    gain.connect(ctx.destination);
                    oscillator.start(when);
                    oscillator.stop(when + 0.65);
                }

                setTimeout(function() {
                    if (tomatitoAlarmAudioContext === ctx) {
                        stopAlarmSiren();
                    }
                }, 45500);
            } catch (e) {
                console.warn('No fue posible reproducir la sirena de alarma:', e);
            }
        }

        // ⚠️ ALTERADO: agora aceita um 2º parâmetro opcional 'overrideSound' —
        // o som configurado num pomodoro/temporizador/alarma ESPECÍFICO, que
        // tem prioridade sobre a configuração global de Ajustes quando
        // presente e diferente de vazio/'default'.
        function playFinishSound(tipo, overrideSound) {
            try {
                var ajustes = JSON.parse(localStorage.getItem('tomatito_ajustes') || '{}');
                var sonido  = (overrideSound && overrideSound !== 'default')
                    ? overrideSound
                    : ajustes['sonido_' + tipo];

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

                if (tipo === 'alarma' && sonido === 'campana') {
                    playAlarmSiren();
                    return;
                }

                var audio = new Audio(sonido === 'peeeem' ? TOMATITO_PEEEEM_URL : TOMATITO_SOUNDS_URL + sonido + '.mp3');

                // Las alarmas deben sonar entre 30 y 60 segundos sin importar el
                // sonido elegido — no solo con la sirena 'campana' de arriba. Si
                // el archivo elegido dura menos, lo repetimos hasta completar 45s.
                if (tipo === 'alarma') {
                    stopAlarmSiren();
                    audio.loop = true;
                    tomatitoAlarmAudioElement = audio;
                    tomatitoAlarmAudioTimeout = setTimeout(function() {
                        if (tomatitoAlarmAudioElement === audio) {
                            stopAlarmSiren();
                        }
                    }, 45000);
                }

                audio.play().catch(function(err) {
                    console.warn('Sonido bloqueado o no encontrado (' + sonido + '):', err);
                });
            } catch (e) {
                console.error('Error al reproducir sonido:', e);
            }
        }

        function unlockAudio() {
            if (tomatitoAudioUnlocked) return;
            tomatitoAudioUnlocked = true;
            var a = new Audio(TOMATITO_SOUNDS_URL + 'clasico.mp3');
            a.volume = 0;
            a.play().catch(function() {});
            document.removeEventListener('click', unlockAudio);
            document.removeEventListener('keydown', unlockAudio);
            document.removeEventListener('touchstart', unlockAudio);
        }
        document.addEventListener('click', unlockAudio);
        document.addEventListener('keydown', unlockAudio);
        document.addEventListener('touchstart', unlockAudio);

        window.playFinishSound = playFinishSound;

        // Função para tocar um beep simples via Web Audio API (mantida por compatibilidade,
        // mas já não é usada por defeito — playFinishSound faz esse papel agora)
        function playBeep() {
            try {
                var ctx = new (window.AudioContext || window.webkitAudioContext)();
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.type = 'sine';
                osc.frequency.value = 880;

                gain.gain.setValueAtTime(0, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.3, ctx.currentTime + 0.05);
                gain.gain.linearRampToValueAtTime(0, ctx.currentTime + 0.5);

                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.5);
            } catch (e) {
                console.warn('Não foi possível tocar som:', e);
            }
        }

        // Função para mostrar toast visual
        function showToast(title, message, icon) {
            var container = document.getElementById('tomatito-toast-container');
            if (!container) return;

            var toast = document.createElement('div');
            toast.className = 'tomatito-toast';
            toast.innerHTML =
                '<div class="toast-title"><span class="toast-icon">' + (icon || '🔔') + '</span>' + title + '</div>' +
                '<div class="toast-message">' + message + '</div>';

            container.appendChild(toast);

            setTimeout(function() {
                toast.classList.add('show');
            }, 10);

            setTimeout(function() {
                toast.classList.remove('show');
                setTimeout(function() {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, 5000);
        }

        // Função para notificação nativa do sistema
        function showNativeNotification(title, message, icon) {
            if (typeof Notification === 'undefined') return;
            if (Notification.permission !== 'granted') return;

            try {
                new Notification(title, {
                    body: message,
                    icon: icon || undefined,
                    tag: 'tomatito-' + Date.now(),
                });
            } catch (e) {
                console.warn('Não foi possível mostrar notificação nativa:', e);
            }
        }

        // Função GLOBAL que tudo o resto vai usar
        // ⚠️ ALTERADO: 'sound' agora é false por defeito — quem quer tocar som usa
        // playFinishSound(tipo) diretamente, para respeitar a escolha por tipo do utilizador.
        window.tomatitoNotify = function(title, message, options) {
            options = options || {};

            var icon       = options.icon       || '🔔';
            var withSound  = options.sound  === true;  // por defeito NÃO toca (evita duplicar com playFinishSound)
            var withToast  = options.toast  !== false; // por defeito mostra toast
            var withNative = options.native !== false; // por defeito tenta notificação nativa

            console.log('🔔 tomatitoNotify:', title, message);

            if (withToast) {
                showToast(title, message, icon);
            }

            if (withSound) {
                playBeep();
            }

            if (withNative) {
                showNativeNotification(title, message);
            }
        };
		
// ============================================
// CONFIRMAÇÃO CUSTOMIZADA (substitui o confirm() nativo do navegador)
// Uso: if (! await window.tomatitoConfirm('Sua pergunta?')) return;
// ============================================
window.tomatitoConfirm = function(message, options) {
    options = options || {};
    var title       = options.title       || '¿Estás seguro?';
    var confirmText = options.confirmText || 'Eliminar';
    var cancelText  = options.cancelText  || 'Cancelar';

    return new Promise(function(resolve) {
        var overlay = document.createElement('div');
        overlay.className = 'tomatito-confirm-overlay';
        overlay.innerHTML =
            '<div class="tomatito-confirm-box">' +
                '<div class="tomatito-confirm-title">' + title + '</div>' +
                '<div class="tomatito-confirm-message">' + message + '</div>' +
                '<div class="tomatito-confirm-actions">' +
                    '<button class="tomatito-confirm-cancel">' + cancelText + '</button>' +
                    '<button class="tomatito-confirm-ok">' + confirmText + '</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(overlay);

        setTimeout(function() { overlay.classList.add('show'); }, 10);

        function close(result) {
            overlay.classList.remove('show');
            setTimeout(function() {
                if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
            }, 200);
            resolve(result);
        }

        overlay.querySelector('.tomatito-confirm-cancel').addEventListener('click', function() { close(false); });
        overlay.querySelector('.tomatito-confirm-ok').addEventListener('click', function() { close(true); });
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) close(false);
        });
    });
};

// ============================================
// AVISO CUSTOMIZADO (substitui o alert() nativo do navegador)
// ⚠️ NOVO: esta função era chamada em vários lugares (ex: alarmas.php) mas
// nunca tinha sido definida em nenhum snippet — provavelmente estava
// quebrada em produção (erro de "tomatitoAlert is not a function" no
// console, silenciosamente). Reaproveita o mesmo visual do
// tomatitoConfirm, só que com um único botão.
// Uso: await window.tomatitoAlert('Mensagem aqui');
// ============================================
window.tomatitoAlert = function(message, options) {
    options = options || {};
    var title   = options.title   || 'Aviso';
    var okText  = options.okText  || 'Aceptar';

    return new Promise(function(resolve) {
        var overlay = document.createElement('div');
        overlay.className = 'tomatito-confirm-overlay';
        overlay.innerHTML =
            '<div class="tomatito-confirm-box">' +
                '<div class="tomatito-confirm-title">' + title + '</div>' +
                '<div class="tomatito-confirm-message">' + message + '</div>' +
                '<div class="tomatito-confirm-actions">' +
                    '<button class="tomatito-confirm-ok">' + okText + '</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(overlay);

        setTimeout(function() { overlay.classList.add('show'); }, 10);

        function close() {
            overlay.classList.remove('show');
            setTimeout(function() {
                if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
            }, 200);
            resolve();
        }

        overlay.querySelector('.tomatito-confirm-ok').addEventListener('click', close);
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) close();
        });
    });
};

        // ============================================
        // VIGILÂNCIA GLOBAL — funciona em QUALQUER página
        // Usa as MESMAS chaves de localStorage que o Dashboard usa,
        // então não há notificação duplicada: quem detectar primeiro
        // marca a chave, e o outro lado vê a marca e ignora.
        // ============================================

        var temporizadoresAnteriores = {};
        var temporizadoresJaNotificados = JSON.parse(localStorage.getItem('tomatito_temp_notificados') || '{}');
        function marcarTempNotificado(id) {
            temporizadoresJaNotificados[id] = true;
            try {
                localStorage.setItem('tomatito_temp_notificados', JSON.stringify(temporizadoresJaNotificados));
            } catch (e) {}
        }

        var pomodorosAnteriores = {};
        var pomodorosJaNotificados = JSON.parse(localStorage.getItem('tomatito_pomo_notificados') || '{}');
        function marcarPomoNotificado(id) {
            pomodorosJaNotificados[id] = true;
            try {
                localStorage.setItem('tomatito_pomo_notificados', JSON.stringify(pomodorosJaNotificados));
            } catch (e) {}
        }

        // ⚠️ ALTERADO: alarmasJaNotificadas agora persiste no localStorage,
        // igual pomodoros/temporizadores — assim n\u00E3o depende de nada em
        // mem\u00F3ria que se perde ao trocar de p\u00E1gina.
        var alarmasPendentes = JSON.parse(localStorage.getItem('tomatito_alarmas_pendentes') || '{}');
        // Guarda quais alarmas o utilizador já mandou parar hoje.
        // A chave contém a data e a hora, portanto no dia seguinte a mesma
        // alarma poderá disparar normalmente outra vez.
        var alarmasDetenidas = JSON.parse(localStorage.getItem('tomatito_alarmas_detenidas') || '{}');

        function guardarAlarmasPendentes() {
            try {
                localStorage.setItem('tomatito_alarmas_pendentes', JSON.stringify(alarmasPendentes));
            } catch (e) {}
        }

        function guardarAlarmasDetenidas() {
            try {
                localStorage.setItem('tomatito_alarmas_detenidas', JSON.stringify(alarmasDetenidas));
            } catch (e) {}
        }

        function detenerAlarma(chave) {
            delete alarmasPendentes[chave];
            alarmasDetenidas[chave] = true;
            guardarAlarmasPendentes();
            guardarAlarmasDetenidas();
            stopAlarmSiren();

            var aviso = document.querySelector('[data-tomatito-alarma="' + chave + '"]');
            if (aviso && aviso.parentNode) aviso.parentNode.removeChild(aviso);
        }

        function mostrarAvisoAlarma(chave, name, time, reminderMinutes) {
            if (document.querySelector('[data-tomatito-alarma="' + chave + '"]')) return;

            var overlay = document.createElement('div');
            overlay.className = 'tomatito-confirm-overlay show';
            overlay.setAttribute('data-tomatito-alarma', chave);

            var box = document.createElement('div');
            box.className = 'tomatito-confirm-box';

            var title = document.createElement('div');
            title.className = 'tomatito-confirm-title';
            title.textContent = '¡Alarma! 🔔';

            var message = document.createElement('div');
            message.className = 'tomatito-confirm-message';
            var minutos = reminderMinutes || 5;
            message.textContent = '"' + name + '" — ' + time + '. Se repetirá cada ' + minutos + ' minuto' + (minutos === 1 ? '' : 's') + ' hasta detenerla.';

            var actions = document.createElement('div');
            actions.className = 'tomatito-confirm-actions';
            var stopButton = document.createElement('button');
            stopButton.className = 'tomatito-confirm-ok';
            stopButton.textContent = 'Detener alarma';
            stopButton.addEventListener('click', function() { detenerAlarma(chave); });

            actions.appendChild(stopButton);
            box.appendChild(title);
            box.appendChild(message);
            box.appendChild(actions);
            overlay.appendChild(box);
            document.body.appendChild(overlay);
        }

        function dispararAlarma(chave, alarma) {
            var reminderMinutes = Number(alarma.reminder_minutes) || 5;
            playFinishSound('alarma', alarma.sound);
            window.tomatitoNotify(
                '¡Alarma! 🔔',
                '"' + alarma.name + '" — ' + alarma.time,
                { icon: '🔔' }
            );
            mostrarAvisoAlarma(chave, alarma.name, alarma.time, reminderMinutes);
            alarmasPendentes[chave].next_reminder_at = Date.now() + (reminderMinutes * 60 * 1000);
            guardarAlarmasPendentes();
        }

        async function vigiarTemporizadoresGlobal() {
            try {
                var res = await fetch('/wp-json/tomatito/v1/temporizadores/dashboard?nocache=' + Date.now(), {
                    cache: 'no-store',
                    credentials: 'include',
                    headers: { 'X-WP-Nonce': (window.wpApiSettings ? wpApiSettings.nonce : '') }
                });
                var data = await res.json();
                if (!data.success || data.mode !== 'running' || !data.data) {
                    temporizadoresAnteriores = {};
                    return;
                }
                data.data.forEach(function(item) {
                    var id        = item.timer_id;
                    var name      = item.name;
                    var remaining = Number(item.remaining);

                    if (temporizadoresAnteriores[id] !== undefined) {
                        var anterior = temporizadoresAnteriores[id];
                        if (anterior > 0 && remaining <= 0) {
                            if (!temporizadoresJaNotificados[id]) {
                                playFinishSound('temporizador', item.sound);
                                window.tomatitoNotify(
                                    'Temporizador terminado! ⏱️',
                                    'El temporizador "' + name + '" ha llegado a cero.',
                                    { icon: '⏱️' }
                                );
                                marcarTempNotificado(id);
                            }
                        }
                    } else {
                        if (remaining <= 0) {
                            marcarTempNotificado(id);
                        }
                    }
                    temporizadoresAnteriores[id] = remaining;
                });
            } catch (error) {
                console.error('Erro ao vigiar temporizadores (global):', error);
            }
        }

async function vigiarPomodorosGlobal() {
    try {
        var res = await fetch('/wp-json/tomatito/v1/pomodoros/active?nocache=' + Date.now(), {
            cache: 'no-store',
            credentials: 'include',
            headers: { 'X-WP-Nonce': (window.wpApiSettings ? wpApiSettings.nonce : '') }
        });
        var data = await res.json();
        var presentIds = {};

        if (data.success && data.data) {
            data.data.forEach(function(item) {
                var id = item.pomodoro_id;
                if (!id) return;
                presentIds[id] = true;
                var remaining = Number(item.remaining);
                var name       = item.name;

                if (pomodorosAnteriores[id] !== undefined) {
                    var anterior = pomodorosAnteriores[id];
                    if (anterior > 0 && remaining <= 0) {
                        if (!pomodorosJaNotificados[id]) {
                            playFinishSound('pomodoro', item.sound);
                            window.tomatitoNotify(
                                'Pomodoro terminado! 🍅',
                                'Tu pomodoro "' + name + '" ha llegado al final. ¡Buen trabajo!',
                                { icon: TOMATITO_TOMATO_IMG }
                            );
                            marcarPomoNotificado(id);
                        }
                    }
                } else {
                    if (remaining <= 0) {
                        marcarPomoNotificado(id);
                    } else if (pomodorosJaNotificados[id]) {
                        delete pomodorosJaNotificados[id];
                        try {
                            localStorage.setItem('tomatito_pomo_notificados', JSON.stringify(pomodorosJaNotificados));
                        } catch (e) {}
                    }
                }
                pomodorosAnteriores[id] = remaining;
            });
        }

        // \u26A0\uFE0F NOVO: detecta pomodoros que SUMIRAM da lista (foram completados
        // por outra p\u00E1gina/aba antes deste vigia ver o "remaining" chegar a 0).
        // Nota: isto tamb\u00E9m dispara se o pomodoro foi CANCELADO manualmente
        // (bot\u00E3o Detener), n\u00E3o s\u00F3 quando termina naturalmente.
        Object.keys(pomodorosAnteriores).forEach(function(id) {
            if (presentIds[id]) return;
            if (pomodorosAnteriores[id] > 0 && !pomodorosJaNotificados[id]) {
                playFinishSound('pomodoro');
                window.tomatitoNotify(
                    'Pomodoro terminado! 🍅',
                    '¡Buen trabajo!',
                    { icon: TOMATITO_TOMATO_IMG }
                );
                marcarPomoNotificado(id);
            }
            delete pomodorosAnteriores[id];
        });

    } catch (error) {
        console.error('Erro ao vigiar pomodoros (global):', error);
    }
}

// ============================================
// VIGIA DE ALARMAS
// ⚠️ ALTERADO: em vez de comparar "desde a \u00FAltima checagem" (que falhava
// se a p\u00E1gina era aberta bem no minuto do alarme), agora dispara sempre
// que o hor\u00E1rio j\u00E1 passou h\u00E1 no m\u00E1ximo 3 minutos — e a marca de
// "j\u00E1 notificada" persiste no localStorage, ent\u00E3o funciona igual em
// qualquer p\u00E1gina, mesmo trocando de tela no meio do caminho.
// ============================================
async function vigiarAlarmasGlobal() {
    try {
        // Recarrega a lista para que o 'Detener alarma' de uma aba também
        // seja respeitado por outras abas abertas.
        try {
            alarmasDetenidas = JSON.parse(localStorage.getItem('tomatito_alarmas_detenidas') || '{}');
        } catch (e) {}

        var res = await fetch('/wp-json/tomatito/v1/alarmas/upcoming?nocache=' + Date.now(), {
            cache: 'no-store',
            credentials: 'include',
            headers: { 'X-WP-Nonce': (window.wpApiSettings ? wpApiSettings.nonce : '') }
        });
        var data = await res.json();
        if (!data.success || !data.data) return;

        var now = new Date();
        var nowMinutes = now.getHours() * 60 + now.getMinutes();
        var hojeStr = now.toISOString().slice(0, 10);

        data.data.forEach(function(alarma) {
            var id   = alarma.id;
            var name = alarma.name;
            var time = alarma.time;
            var parts = time.split(':');
            var alarmMinutes = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
            var diff = nowMinutes - alarmMinutes;

            // Ao chegar no horário, o alarme permanece pendente até o utilizador
            // escolher "Detener alarma". Enquanto estiver pendente, é repetido
            // a cada cinco minutos.
            if (diff >= 0) {
                var chave = id + '_' + hojeStr + '_' + time;

                // Se o utilizador já parou esta ocorrência, não a recria
                // no próximo ciclo de vigilância.
                if (alarmasDetenidas[chave]) return;

                if (!alarmasPendentes[chave]) {
                    alarmasPendentes[chave] = {
                        name: name,
                        time: time,
                        next_reminder_at: 0
                    };
                    dispararAlarma(chave, alarma);
                } else if (Date.now() >= Number(alarmasPendentes[chave].next_reminder_at || 0)) {
                    dispararAlarma(chave, alarma);
                }
            }
        });

    } catch (error) {
        console.error('Erro ao vigiar alarmas (global):', error);
    }
}

        // Só ativa a vigilância se o utilizador estiver logado na REST (wpApiSettings existe
        // em qualquer página do WP quando há nonce disponível — se não existir, os fetches
        // simplesmente falham silenciosamente e tentam de novo no próximo ciclo)
        // 
        
document.addEventListener('DOMContentLoaded', function() {
            // Se estamos no Dashboard, ele já tem vigilância instantânea de pomodoros
            // e de temporizadores (via fetchActivePomodoros/tickActivePomodoros e
            // tickActiveTemporizadores) — não duplica aqui.
            var isDashboard = !!document.getElementById('active-pomodoros-section');

            // ⚠️ CORRIGIDO: a página de Pomodoros (/pomodoros/) TAMBÉM já tem o
            // próprio motor de fases (handlePhaseEnd/runAdvancePhase/completePomodoro)
            // com suas próprias notificações por fase. Antes, só o Dashboard era
            // excluído daqui — então o vigia global de pomodoros continuava rodando
            // na página de Pomodoros e disparava um "Pomodoro terminado" duplicado
            // e genérico (sem saber de fases) por cima do alerta correto do motor
            // de fases, mesmo quando o motor estava certo em esperar confirmação
            // manual no long_break final.
            var isPomodorosPage = !!document.getElementById('pomodoros-list');

            vigiarAlarmasGlobal();
            setInterval(vigiarAlarmasGlobal, 30000);

            // Temporizadores não têm motor de fases em nenhuma página — o vigia
            // global continua útil em qualquer lugar, exceto no Dashboard (que já
            // tem o seu próprio tick instantâneo).
            if (!isDashboard) {
                vigiarTemporizadoresGlobal();
                setInterval(vigiarTemporizadoresGlobal, 5000);
            }

            // Pomodoros: só o vigia global aqui, nas páginas que NÃO têm motor
            // de fases próprio (ou seja, nem Dashboard nem Pomodoros).
            if (!isDashboard && !isPomodorosPage) {
                vigiarPomodorosGlobal();
                setInterval(vigiarPomodorosGlobal, 5000);
            }
        });

    })();
    </script>
    <?php
});
