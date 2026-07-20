<?php include 'navbar.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cuponera UAGRMB | Pollo El Solar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=IBM+Plex+Mono:wght@500;600&family=Boogaloo&display=swap" rel="stylesheet">
<link rel="stylesheet" href="convenio.css">
</head>
<body>

<!-- ── Título CUPONERA ── -->
<header class="conv-logo-bar" role="banner">
    <div class="texto" aria-hidden="true">
        <span>CUPONERA</span>
    </div>
</header>

<!-- ── Ticket ── -->
<section class="convenio-section">

    <p class="convenio-instruccion">INGRESA TU NÚMERO DE REGISTRO</p>

    <div class="ticket-wrap">
        <div class="ticket">

            <!-- Lado izquierdo: input -->
            <div class="ticket-side left">
                <div class="input-container">
                    <input type="text" id="carnetInput" class="carnet-input" placeholder="número de registro" maxlength="20">
                </div>
                <button id="btnGenerar" class="btn-generar">
                    <p class="text">Generar cupón</p>
                </button>
                <div id="helperText" class="helper-text">Hasta 2 cupones por registro, por día.</div>
            </div>

            <!-- Perforación central -->
            <div class="perforation">
                <div class="perf-dashes"></div>
            </div>

            <!-- Lado derecho: código generado -->
            <div class="ticket-side right">
                <div class="stamp" id="stamp">Hoy</div>

                <div class="placeholder-state" id="placeholderState">
                    <div class="placeholder-icon">🎟️</div>
                    Tu código aparecerá aquí
                </div>

                <div class="code-state" id="codeState">
                    <div class="code-label">Tu código</div>
                    <div class="code-value" id="codeValueBox">
                        <span id="codeValue">000000</span>
                        <div class="copy-divider"></div>
                        <button class="copy" id="btnCopiar">
                            <span data-text-end="¡Copiado!" data-text-initial="Copiar código" class="tooltip"></span>
                            <span>
                                <svg viewBox="0 0 6.35 6.35" height="17" width="17" xmlns="http://www.w3.org/2000/svg" class="clipboard">
                                    <g><path fill="currentColor" d="M2.43.265c-.3 0-.548.236-.573.53h-.328a.74.74 0 0 0-.735.734v3.822a.74.74 0 0 0 .735.734H4.82a.74.74 0 0 0 .735-.734V1.529a.74.74 0 0 0-.735-.735h-.328a.58.58 0 0 0-.573-.53zm0 .529h1.49c.032 0 .049.017.049.049v.431c0 .032-.017.049-.049.049H2.43c-.032 0-.05-.017-.05-.049V.843c0-.032.018-.05.05-.05zm-.901.53h.328c.026.292.274.528.573.528h1.49a.58.58 0 0 0 .573-.529h.328a.2.2 0 0 1 .206.206v3.822a.2.2 0 0 1-.206.205H1.53a.2.2 0 0 1-.206-.205V1.529a.2.2 0 0 1 .206-.206z"/></g>
                                </svg>
                                <svg viewBox="0 0 24 24" height="15" width="15" xmlns="http://www.w3.org/2000/svg" class="checkmark">
                                    <g><path fill="currentColor" d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"/></g>
                                </svg>
                            </span>
                        </button>
                    </div>
                    <div class="countdown-row">
                        <span>Vence en</span>
                        <span class="countdown-value" id="countdownValue">00:00:00</span>
                    </div>
                    <div class="code-note">Muéstralo en caja antes de medianoche para canjearlo.</div>
                    <button id="btnPDF" style="display:none; margin-top:10px; width:100%; align-items:center; justify-content:center; gap:8px; padding:8px 14px; background:#1a1a1a; color:#fff; border:none; border-radius:6px; font-family:'IBM Plex Mono',monospace; font-size:12px; font-weight:600; cursor:pointer; letter-spacing:0.5px;">
                        🖨️ Descargar ticket PDF
                    </button>
                </div>
            </div>

        </div>
    </div>

    <p class="convenio-foot">Este beneficio aplica para carnets registrados en un convenio vigente. El código pierde validez a las 23:59:59 del día en que fue generado.</p>

</section>

<script>
const btn              = document.getElementById('btnGenerar');
const input            = document.getElementById('carnetInput');
const helper           = document.getElementById('helperText');
const placeholderState = document.getElementById('placeholderState');
const codeState        = document.getElementById('codeState');
const codeValue        = document.getElementById('codeValue');
const countdownValue   = document.getElementById('countdownValue');
const stamp            = document.getElementById('stamp');
let timerId = null;

function msUntilMidnight() {
    const now      = new Date();
    const midnight = new Date(now);
    midnight.setHours(24, 0, 0, 0);
    return midnight - now;
}

function formatCountdown(ms) {
    if (ms < 0) ms = 0;
    const totalSec = Math.floor(ms / 1000);
    const h = String(Math.floor(totalSec / 3600)).padStart(2, '0');
    const m = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
    const s = String(totalSec % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
}

function startCountdown() {
    if (timerId) clearInterval(timerId);
    const tick = () => {
        const remaining = msUntilMidnight();
        countdownValue.textContent = formatCountdown(remaining);
        if (remaining <= 0) {
            clearInterval(timerId);
            countdownValue.textContent = 'Expirado';
        }
    };
    tick();
    timerId = setInterval(tick, 1000);
}

function mostrarCodigo(codigo, cuponId) {
    codeValue.textContent = codigo;
    placeholderState.style.display = 'none';
    codeState.classList.add('active');
    stamp.classList.add('active');
    startCountdown();
    btn.disabled = true;
    btn.querySelector('.text').textContent = 'Cupón generado ✓';
    input.disabled = true;

    // Botón copiar
    const btnCopiar = document.getElementById('btnCopiar');
    btnCopiar.addEventListener('click', () => {
        navigator.clipboard.writeText(codigo).then(() => {
            btnCopiar.focus();
        }).catch(() => {
            const el = document.createElement('textarea');
            el.value = codigo;
            el.style.position = 'fixed';
            el.style.opacity  = '0';
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            btnCopiar.focus();
        });
    });

    // Botón descargar PDF
    const btnPDF = document.getElementById('btnPDF');
    if (btnPDF && cuponId) {
        btnPDF.style.display = 'inline-flex';
        btnPDF.onclick = () => {
            window.open('modulos/ticket_cupon.php?cupon_id=' + cuponId, '_blank');
        };
    }
}

function mostrarError(tipo, mensaje) {
    helper.classList.add('error');

    if (tipo === 'no_cliente') {
        helper.innerHTML = mensaje;
    } else if (tipo === 'ya_generado') {
        helper.innerHTML = `⏳ ${mensaje}`;
    } else if (tipo === 'sin_stock') {
        helper.innerHTML = `😕 ${mensaje}`;
    } else {
        helper.innerHTML = mensaje;
    }

    btn.disabled = false;
    btn.querySelector('.text').textContent = 'Generar cupón';
}

btn.addEventListener('click', () => {
    const carnet = input.value.trim();

    if (carnet.length < 4) {
        helper.classList.add('error');
        helper.innerHTML = 'Ingresa un número de carnet válido.';
        input.focus();
        return;
    }

    // Estado cargando
    btn.disabled = true;
    btn.querySelector('.text').textContent = 'Verificando...';
    helper.classList.remove('error');
    helper.innerHTML = '';

    const formData = new FormData();
    formData.append('generar_cupon_gabriel', '1');
    formData.append('ci', carnet);

    fetch('ajax.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                helper.classList.remove('error');
                helper.innerHTML = `✅ Cupón generado para <strong>${data.nombre}</strong>.`;
                mostrarCodigo(data.codigo, data.cupon_id);
            } else {
                mostrarError(data.tipo, data.message);
            }
        })
        .catch(() => {
            mostrarError('error', 'Error de conexión. Intenta de nuevo.');
        });
});

// Limpiar error al escribir
input.addEventListener('input', () => {
    helper.classList.remove('error');
    helper.innerHTML = 'Hasta 2 cupones por carnet, por día.';
});
</script>

</body>
</html>
