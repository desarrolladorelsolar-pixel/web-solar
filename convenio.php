<?php include 'navbar.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Convenio | Pollo El Solar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=IBM+Plex+Mono:wght@500;600&family=Boogaloo&display=swap" rel="stylesheet">
<style>

/* ── Variables ── */
:root {
    --rojo:  #c62828;
    --negro: #1a1a1a;
    --gris:  #f5f5f5;
    --font-display: 'Boogaloo', sans-serif;
    --font-body:    'Segoe UI', sans-serif;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: var(--font-body);
    background: #fff;
    color: var(--negro);
}

/* ── Header título ── */
.conv-logo-bar {
    display: flex;
    align-items: center;
    padding: 4px 20px;
    border-bottom: 1px solid #f0f0f0;
    background: #fff;
    overflow: hidden;
}

.conv-logo-bar h1 {
    font-family: var(--font-display);
    font-size: clamp(60px, 14vh, 180px);
    font-weight: 400;
    line-height: 0.88;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #000;
    margin: 0;
}

/* ── Sección principal ── */
.convenio-landing {
    padding: 56px 24px 80px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* ── Título central ── */
.convenio-tagline {
    text-align: center;
    margin-bottom: 52px;
    max-width: 640px;
}

.convenio-tagline h2 {
    font-family: var(--font-display);
    font-size: clamp(22px, 4vw, 38px);
    font-weight: 400;
    color: var(--negro);
    line-height: 1.2;
    margin-bottom: 10px;
}

.convenio-tagline p {
    font-size: 15px;
    color: #777;
    line-height: 1.6;
}

/* ── Grid de universidades ── */
.uni-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 28px;
    width: 100%;
    max-width: 720px;
}

/* ── Card de universidad ── */
.uni-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 20px;
    padding: 36px 24px;
    background: #fff;
    border: 1.5px solid #eee;
    border-radius: 18px;
    text-decoration: none;
    color: var(--negro);
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.uni-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(198,40,40,0.04), transparent);
    opacity: 0;
    transition: opacity .25s ease;
}

.uni-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(0,0,0,0.13);
    border-color: var(--rojo);
}

.uni-card:hover::before { opacity: 1; }

.uni-card img {
    width: 100%;
    max-width: 160px;
    height: 100px;
    object-fit: contain;
}

.uni-card-name {
    font-size: 13px;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #444;
    line-height: 1.4;
}

.uni-card-cta {
    font-size: 12px;
    color: var(--rojo);
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 4px;
    letter-spacing: 0.5px;
}

.uni-card-cta svg {
    transition: transform .2s ease;
}

.uni-card:hover .uni-card-cta svg {
    transform: translateX(4px);
}

/* ── Nota al pie ── */
.convenio-nota {
    margin-top: 48px;
    font-size: 13px;
    color: #aaa;
    text-align: center;
    max-width: 480px;
    line-height: 1.6;
}

/* ══════════════════════════════════════════════
   RESPONSIVE
   ══════════════════════════════════════════════ */
@media (max-width: 768px) {

    .conv-logo-bar {
        padding: 20px 20px 16px;
    }

    .convenio-landing {
        padding: 28px 20px calc(120px + env(safe-area-inset-bottom));
    }

    .convenio-tagline {
        margin-bottom: 28px;
    }

    .convenio-tagline h2 {
        font-size: clamp(22px, 5.5vw, 30px);
    }

    /* Una sola columna, cards grandes que ocupan todo el ancho */
    .uni-grid {
        grid-template-columns: 1fr;
        gap: 20px;
        width: 100%;
        max-width: 420px;
    }

    .uni-card {
        flex-direction: row;          /* logo a la izquierda, texto a la derecha */
        align-items: center;
        justify-content: flex-start;
        gap: 20px;
        padding: 24px 28px;
        border-radius: 20px;
        text-align: left;
    }

    .uni-card img {
        max-width: 80px;
        height: 80px;
        flex-shrink: 0;
    }

    .uni-card-name {
        font-size: 15px;
        line-height: 1.35;
    }

    .uni-card-cta {
        font-size: 13px;
        margin-top: 6px;
    }

    .convenio-nota {
        margin-top: 28px;
        font-size: 12px;
        padding: 0 4px;
    }
}

@media (max-width: 400px) {
    .uni-card {
        padding: 20px 20px;
        gap: 16px;
    }
    .uni-card img {
        max-width: 68px;
        height: 68px;
    }
    .uni-card-name { font-size: 14px; }
}

</style>
</head>
<body>


<!-- ── Landing ── -->
<section class="convenio-landing">

    <div class="convenio-tagline">
        <h2>¿Eres estudiante de alguna de estas universidades?</h2>
        <p>Obtén tus cupones de descuento diario. Solo necesitas tu número de carnet.</p>
    </div>

    <div class="uni-grid">

        <!-- UAGRM -->
        <a href="cuponeragabriel.php" class="uni-card">
            <img src="img/gabrielogo.png" alt="Universidad Autónoma Gabriel René Moreno">
            <div class="uni-card-name">Univ. Autónoma<br>Gabriel René Moreno</div>
            <div class="uni-card-cta">
                Obtener cupón
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        <!-- UPSA -->
        <a href="cuponeraupsa.php" class="uni-card">
            <img src="img/upsalogo.jpeg" alt="Universidad Privada de Santa Cruz de la Sierra">
            <div class="uni-card-name">Univ. Privada de<br>Santa Cruz (UPSA)</div>
            <div class="uni-card-cta">
                Obtener cupón
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

    </div>

    <p class="convenio-nota">Dos cupones por carnet, por día. Válido hasta las 23:59 del día de generación. Presenta el código en caja al momento de tu compra.</p>

</section>

</body>
</html>
