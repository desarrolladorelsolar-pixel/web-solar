<?php
require_once 'config.php';
$hoy       = date('Y-m-d');
$hora      = date('H:i:s');
$diaSemana = strtolower(date('l'));
$diaMap    = ['monday'=>'lunes','tuesday'=>'martes','wednesday'=>'miercoles',
              'thursday'=>'jueves','friday'=>'viernes','saturday'=>'sabado','sunday'=>'domingo'];
$col       = $diaMap[$diaSemana] ?? 'lunes';
$stmt = $pdo->prepare("SELECT ruta_foto, url_destino, nombre FROM popups
    WHERE visible=1 AND fecha_inicio<=? AND fecha_fin>=? AND `$col`=1
      AND hora_inicio<=? AND hora_cierre>=? ORDER BY id DESC LIMIT 1");
$stmt->execute([$hoy,$hoy,$hora,$hora]);
$popup = $stmt->fetch();
?>
<?php include 'navbar.php'; ?>
<?php include 'loader.php'; ?>
<title>Inicio | Pollo El Solar</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="home.css">
<link href="https://fonts.googleapis.com/css2?family=Boogaloo&display=swap" rel="stylesheet">

<style>
/* ══ LANDING EXTRA STYLES ══ */
@font-face {
    font-family: 'Gotham';
    src: url('../../tipografia/gothamnarrowoffice_bold.otf') format('opentype');
    font-weight: 700; font-style: normal; font-display: swap;
}
*, *::before, *::after { box-sizing: border-box; }

/* ── SECCIÓN BASE ── */
.land-section {
    width: 100%;
    padding: 60px 24px;
    background: #fff;
}
.land-section.dark  { background: #1a1a1a; color: #fff; }
.land-section.red   { background: #c62828; color: #fff; }
.land-section.cream { background: #faf8f4; }

.land-titulo {
    font-family: 'Boogaloo', sans-serif;
    font-size: clamp(36px, 6vw, 72px);
    font-weight: 400;
    line-height: 0.92;
    text-transform: uppercase;
    margin: 0 0 8px;
}
.land-sub {
    font-family: 'Gotham', 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    opacity: 0.6;
    margin: 0 0 32px;
}
.land-center { text-align: center; }

/* ── HERO ── */
</style>

<style>
/* ── HERO ── */
.home-page { background: #fff; }
.hero {
    position: relative;
    width: 100%;
    height: 92vh;
    min-height: 500px;
    background: #1a1a1a url('../combos/fw.png') center/cover no-repeat;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    overflow: hidden;
}
.hero-overlay { display: none; }
.hero-content {
    position: relative; z-index: 2;
    padding: 0 6vw;
    max-width: 700px;
}
.hero-eyebrow {
    font-family: 'Gotham','Inter',sans-serif;
    font-size: 11px; font-weight: 700;
    letter-spacing: 3px; text-transform: uppercase;
    color: #c62828; margin-bottom: 12px;
}
.hero-h1 {
    font-family: 'Boogaloo', sans-serif;
    font-size: clamp(52px, 9vw, 110px);
    font-weight: 400;
    line-height: 0.88;
    text-transform: uppercase;
    color: #1a1a1a;
    margin: 0 0 10px;
}
.hero-h1 span { color: #c62828; }
.hero-desc {
    font-family: 'Gotham','Inter',sans-serif;
    font-size: 15px; color: #444;
    margin: 0 0 28px; line-height: 1.5;
}
.hero-btns { display: flex; gap: 14px; flex-wrap: wrap; }
.btn-red {
    display: inline-flex; align-items: center; gap: 8px;
    background: #c62828; color: #fff;
    padding: 14px 28px; border-radius: 50px;
    font-family: 'Gotham','Inter',sans-serif;
    font-size: 13px; font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; text-decoration: none;
    transition: background .2s, transform .15s;
}
.btn-red:hover { background: #a81e1e; transform: translateY(-2px); }
.btn-outline {
    display: inline-flex; align-items: center; gap: 8px;
    background: transparent; color: #1a1a1a;
    padding: 14px 28px; border-radius: 50px;
    border: 2px solid #1a1a1a;
    font-family: 'Gotham','Inter',sans-serif;
    font-size: 13px; font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; text-decoration: none;
    transition: border-color .2s, background .2s, color .2s;
}
.btn-outline:hover { background: #1a1a1a; color: #fff; }

/* Logos arrastrables dentro del hero */
.cello1 {
    position: absolute; z-index: 3; cursor: grab;
    width: clamp(110px,14vw,200px);
    top: 18%; right: 12%;
    filter: drop-shadow(0 8px 24px rgba(0,0,0,0.4));
}
.cello2 {
    position: absolute; z-index: 3; cursor: grab;
    width: clamp(80px,10vw,150px);
    bottom: 14%; right: 28%;
    filter: drop-shadow(0 6px 18px rgba(0,0,0,0.3));
}
.cello1:active, .cello2:active { cursor: grabbing; }

/* ── FRANJA ── */
.franja { background: #1a1a1a; overflow: hidden; padding: 11px 0; }
.franja-track {
    display: inline-flex; white-space: nowrap;
    animation: scrollLeft 22s linear infinite;
}
.franja-item {
    padding: 0 36px;
    font-family: 'Gotham','Inter',sans-serif;
    font-weight: 700; font-size: 12px;
    letter-spacing: 2px; text-transform: uppercase;
    color: #ffcc00;
}
@keyframes scrollLeft { from { transform: translateX(0); } to { transform: translateX(-50%); } }
</style>

<style>
/* ── GRID PRODUCTOS / ITEMS ── */
.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 24px;
    max-width: 1100px; margin: 0 auto;
}
.item-card {
    display: flex; flex-direction: column; align-items: center;
    gap: 10px; text-align: center; cursor: pointer;
}
.item-card img {
    width: 100%; max-width: 150px; height: 140px;
    object-fit: contain;
    filter: drop-shadow(0 6px 16px rgba(0,0,0,0.14));
    transition: transform .25s;
}
.item-card:hover img { transform: translateY(-6px) scale(1.04); }
.item-card span {
    font-family: 'Gotham','Inter',sans-serif;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.5px;
    color: #333;
}

/* ── COMBOS WEEK ── */
.combos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
    max-width: 1200px; margin: 0 auto;
}
.combo-card {
    border-radius: 16px; overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: transform .2s, box-shadow .2s;
}
.combo-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.16); }
.combo-card img { width: 100%; height: auto; display: block; }

/* ── CARRUSEL FOTOS (fotopage) ── */
.promo-section {
    padding: 60px 24px 70px;
    background: #fff;
}
.promo-titulo {
    font-family: 'Boogaloo', sans-serif;
    font-size: clamp(32px,5vw,60px);
    color: #1a1a1a; text-align: center;
    margin-bottom: 36px; font-weight: 400;
}
.promo-carrusel-wrapper {
    overflow: hidden; max-width: 1000px;
    margin: 0 auto; cursor: grab; user-select: none;
}
.promo-carrusel-wrapper:active { cursor: grabbing; }
.promo-track {
    display: flex; gap: 20px;
    transition: transform 1.8s ease;
    will-change: transform;
}
.promo-img {
    flex-shrink: 0;
    width: calc((1000px - 40px) / 3);
    border-radius: 14px; overflow: hidden;
}
.promo-img img { width: 100%; height: auto; display: block; pointer-events: none; }

/* ── SUCURSALES CAROUSEL ── */
.suc-track-wrap { overflow: hidden; }
.suc-track {
    display: flex; gap: 16px;
    animation: scrollLeft 30s linear infinite;
}
.suc-track:hover { animation-play-state: paused; }
.suc-card {
    flex-shrink: 0; width: 280px; border-radius: 16px;
    overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}
.suc-card img { width: 100%; height: 190px; object-fit: cover; display: block; }
.suc-card-label {
    padding: 12px 16px;
    font-family: 'Gotham','Inter',sans-serif;
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.5px; background: #fff; color: #1a1a1a;
}

/* ── CONVENIO BANNER ── */
.convenio-banner {
    display: flex; align-items: center; justify-content: center;
    gap: 32px; flex-wrap: wrap;
    max-width: 900px; margin: 0 auto;
}
.uni-btn {
    display: flex; flex-direction: column; align-items: center; gap: 10px;
    padding: 28px 40px; border-radius: 18px;
    background: rgba(255,255,255,0.08);
    border: 1.5px solid rgba(255,255,255,0.18);
    text-decoration: none; color: #fff;
    transition: background .2s, transform .2s, border-color .2s;
    min-width: 200px;
}
.uni-btn:hover { background: rgba(255,255,255,0.16); border-color: #ffcc00; transform: translateY(-4px); }
.uni-btn img { height: 60px; object-fit: contain; filter: brightness(0) invert(1); }
.uni-btn span {
    font-family: 'Gotham','Inter',sans-serif;
    font-size: 11px; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
    color: #ffcc00;
}

/* ── STATS ── */
.stats-row {
    display: flex; justify-content: center;
    gap: 48px; flex-wrap: wrap;
    max-width: 800px; margin: 0 auto;
}
.stat { text-align: center; }
.stat-num {
    font-family: 'Boogaloo',sans-serif;
    font-size: clamp(52px,8vw,90px);
    line-height: 1; color: #ffcc00; font-weight: 400;
}
.stat-label {
    font-family: 'Gotham','Inter',sans-serif;
    font-size: 11px; font-weight: 700;
    letter-spacing: 2px; text-transform: uppercase;
    color: rgba(255,255,255,0.6); margin-top: 4px;
}

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
    .hero { height: 100svh; }
    .hero-content { padding: 0 20px; }
    .cello1 { top: auto; bottom: 28%; right: 6%; }
    .cello2 { display: none; }
    .items-grid { grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .combos-grid { grid-template-columns: repeat(2, 1fr); }
    .promo-img { width: calc((100vw - 68px) / 2); }
    .convenio-banner { gap: 16px; }
    .uni-btn { min-width: 140px; padding: 20px 24px; }
    .stats-row { gap: 28px; }
}
</style>

<!-- ══════════════════════════════════════════════
     HERO
══════════════════════════════════════════════ -->
<div class="home-page">
<div class="hero" id="hero">
    <div class="hero-overlay"></div>

    <div class="hero-content">
        <div class="hero-eyebrow">Santa Cruz de la Sierra, Bolivia</div>
        <h1 class="hero-h1">POLLO<br>EL <span>SOLAR</span></h1>
        <p class="hero-desc">Pollo broaster cruceño. Dorado por fuera,<br>jugoso por dentro. Desde hace más de 43 años.</p>
        <div class="hero-btns">
            <a href="menu.php" class="btn-red">Ver Menú</a>
            <a href="sucursal.php" class="btn-outline">Nuestras Sucursales</a>
        </div>
    </div>

    <img class="cello1"
         src="../fotos/logo solar_Mesa de trabajo 1.png"
         data-src-normal="../fotos/logo solar_Mesa de trabajo 1.png"
         data-src-hover="../fotos/LOGOS VECTORIZADOS PARA POLERA DON OMAR SOLAR (1)_Mesa de trabajo 1 copia 2.png"
         alt="El Solar">
    <img class="cello2"
         src="../fotos/Logo el Solar a color - sin texto.png"
         data-src-normal="../fotos/Logo el Solar a color - sin texto.png"
         data-src-hover="../fotos/Logo el Solar negro - sin texto.png"
         alt="El Solar">
</div>

<!-- FRANJA MARQUEE -->
<div class="franja">
    <div class="franja-track" id="franjaTrack"></div>
</div>

</div><!-- /home-page -->

<!-- ══════════════════════════════════════════════
     SECCIÓN 3 — CARRUSEL FOTOS REALES
══════════════════════════════════════════════ -->
<div class="promo-section">
    <div class="promo-titulo">Así somos</div>
    <div class="promo-carrusel-wrapper" id="promoWrapper">
        <div class="promo-track" id="promoTrack"></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     SECCIÓN 4 — STATS
══════════════════════════════════════════════ -->
<section class="land-section red">
    <div class="stats-row">
        <div class="stat">
            <div class="stat-num" data-target="14">0</div>
            <div class="stat-label">Sucursales</div>
        </div>
        <div class="stat">
            <div class="stat-num" data-target="43">0</div>
            <div class="stat-label">Años de sabor</div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════
     SECCIÓN 5 — SUCURSALES (scroll automático)
══════════════════════════════════════════════ -->
<section class="land-section" style="padding:60px 0; background:#fff;">
    <div class="land-center" style="margin-bottom:36px; padding:0 24px;">
        <div class="land-sub">Cerca de ti</div>
        <h2 class="land-titulo">Nuestras<br>sucursales</h2>
    </div>
    <div class="suc-track-wrap">
        <div class="suc-track" id="sucTrack">
            <div class="suc-card"><img src="../sucursales/CAÑOTO.png" alt="Cañoto"><div class="suc-card-label">Cañoto</div></div>
            <div class="suc-card"><img src="../sucursales/AUTOPIA.png" alt="Autopista"><div class="suc-card-label">Autopista</div></div>
            <div class="suc-card"><img src="../sucursales/SANTOS DUMONT.png" alt="Santos Dumont"><div class="suc-card-label">Santos Dumont</div></div>
            <div class="suc-card"><img src="../sucursales/MEGACENTER.png" alt="Megacenter"><div class="suc-card-label">Megacenter</div></div>
            <div class="suc-card"><img src="../sucursales/NORTE.png" alt="Norte"><div class="suc-card-label">Norte</div></div>
            <div class="suc-card"><img src="../sucursales/KM6.png" alt="Km 6"><div class="suc-card-label">Km 6</div></div>
            <div class="suc-card"><img src="../sucursales/INDANA SOLAR.png" alt="Indana"><div class="suc-card-label">Indana</div></div>
            <!-- duplicar para loop continuo -->
            <div class="suc-card"><img src="../sucursales/CAÑOTO.png" alt="Cañoto"><div class="suc-card-label">Cañoto</div></div>
            <div class="suc-card"><img src="../sucursales/AUTOPIA.png" alt="Autopista"><div class="suc-card-label">Autopista</div></div>
            <div class="suc-card"><img src="../sucursales/SANTOS DUMONT.png" alt="Santos Dumont"><div class="suc-card-label">Santos Dumont</div></div>
            <div class="suc-card"><img src="../sucursales/MEGACENTER.png" alt="Megacenter"><div class="suc-card-label">Megacenter</div></div>
            <div class="suc-card"><img src="../sucursales/NORTE.png" alt="Norte"><div class="suc-card-label">Norte</div></div>
            <div class="suc-card"><img src="../sucursales/KM6.png" alt="Km 6"><div class="suc-card-label">Km 6</div></div>
            <div class="suc-card"><img src="../sucursales/INDANA SOLAR.png" alt="Indana"><div class="suc-card-label">Indana</div></div>
        </div>
    </div>
    <div style="text-align:center; margin-top:36px; padding:0 24px;">
        <a href="sucursal.php" class="btn-red">Ver mapa de sucursales</a>
    </div>
</section>

<?php include 'footer.php'; ?>

<?php if ($popup): ?>
<div id="popup-overlay">
    <div class="popup-card">
        <button class="popup-close" onclick="cerrarPopup()" aria-label="Cerrar">×</button>
        <div class="popup-img-wrap">
            <img src="<?= htmlspecialchars($popup['ruta_foto']) ?>"
                 alt="<?= htmlspecialchars($popup['nombre']) ?>"
                 class="popup-img">
        </div>
        <div class="popup-btns">
            <button class="popup-btn popup-btn-secondary" onclick="cerrarPopup()">Ver más</button>
            <button class="popup-btn popup-btn-primary"
                onclick="<?= !empty($popup['url_destino'])
                    ? "window.open('" . htmlspecialchars($popup['url_destino']) . "','_blank'); cerrarPopup();"
                    : "cerrarPopup();" ?>">
                🛒 Pedir ya
            </button>
        </div>
    </div>
</div>
<style>
#popup-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.72); z-index:999999;
    align-items:center; justify-content:center;
    padding:16px; backdrop-filter:blur(3px);
}
#popup-overlay.visible { display:flex; animation:popupFadeIn .3s ease; }
@keyframes popupFadeIn { from{opacity:0} to{opacity:1} }
.popup-card {
    position:relative; width:min(380px,92vw); max-height:92vh;
    background:#fff; border-radius:20px; overflow:hidden;
    box-shadow:0 24px 70px rgba(0,0,0,0.55); display:flex;
    flex-direction:column;
    animation:popupSlideUp .35s cubic-bezier(0.34,1.4,0.64,1);
}
@keyframes popupSlideUp {
    from{transform:translateY(40px) scale(0.96);opacity:0}
    to{transform:translateY(0) scale(1);opacity:1}
}
.popup-close {
    position:absolute; top:10px; right:12px;
    width:32px; height:32px; border-radius:50%;
    border:none; background:rgba(0,0,0,0.45); color:#fff;
    font-size:18px; cursor:pointer; z-index:10;
    display:flex; align-items:center; justify-content:center;
}
.popup-close:hover { background:rgba(0,0,0,0.75); }
.popup-img-wrap { flex:1; min-height:0; overflow:hidden; }
.popup-img { display:block; width:100%; max-height:60vh; object-fit:cover; }
.popup-btns { display:flex; gap:10px; padding:14px 16px; background:#fff; border-top:1px solid #f0f0f0; }
.popup-btn { flex:1; padding:13px 10px; border:none; border-radius:12px; font-size:14px; font-weight:700; cursor:pointer; }
.popup-btn-secondary { background:#f0f0f0; color:#333; }
.popup-btn-secondary:hover { background:#e0e0e0; }
.popup-btn-primary { background:#c62828; color:#fff; box-shadow:0 4px 14px rgba(198,40,40,.35); }
.popup-btn-primary:hover { background:#a81e1e; }
@media(max-width:480px) {
    .popup-card { border-radius:16px 16px 0 0; position:fixed; bottom:0; left:0; right:0; width:100%; max-height:88vh; }
    #popup-overlay { align-items:flex-end; padding:0; }
}
</style>
<script>
(function(){
    var overlay = document.getElementById('popup-overlay');
    if(!overlay) return;
    function cerrarPopup(){ overlay.classList.remove('visible'); }
    window.cerrarPopup = cerrarPopup;
    setTimeout(function(){ overlay.classList.add('visible'); }, 2500);
    overlay.addEventListener('click', function(e){ if(e.target===overlay) cerrarPopup(); });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') cerrarPopup(); });
})();
</script>
<?php endif; ?>

<script>
// ── FRANJA MARQUEE ──
(function(){
    const items = [
        '🍗 POLLO BROASTER EL SOLAR',
        '✦ EL MEJOR SABOR DE BOLIVIA',
        '✦ 20% OFF LOS MARTES',
        '✦ MÁS DE 8 SUCURSALES',
        '✦ DORADO POR FUERA, JUGOSO POR DENTRO'
    ];
    const track = document.getElementById('franjaTrack');
    if(!track) return;
    let html = '';
    for(let b=0; b<2; b++) items.forEach(t => html += `<span class="franja-item">${t}</span>`);
    track.innerHTML = html;
})();

// ── CARRUSEL FOTOS (promoTrack) ──
(function(){
    const imgs = [
        '../fotos/fotopage1.jpeg','../fotos/fotopage2.jpeg',
        '../fotos/fotopage3.jpeg','../fotos/fotopage4.jpeg',
        '../fotos/fotopage5.jpeg','../fotos/fotopage6.jpeg',
        '../fotos/fotopage7.jpeg'
    ];
    const wrapper = document.getElementById('promoWrapper');
    const track   = document.getElementById('promoTrack');
    if(!wrapper || !track) return;

    const base = imgs.map(s => `<div class="promo-img"><img src="${s}" alt="El Solar" loading="lazy"></div>`).join('');
    track.innerHTML = base + base + base;

    let paso=imgs.length, timer=null, drag=false, startX=0, startOff=0;

    function anchoCard(){
        const c = track.querySelector('.promo-img');
        return c ? c.getBoundingClientRect().width + 20 : 1;
    }
    function mover(noAnim){
        track.style.transition = noAnim ? 'none' : 'transform 1.8s ease';
        track.style.transform  = `translateX(-${paso * anchoCard()}px)`;
        if(noAnim){ track.offsetHeight; track.style.transition='transform 1.8s ease'; }
    }
    function loop(){
        if(paso >= imgs.length*2){ setTimeout(()=>{ paso -= imgs.length; mover(true); }, 1900); }
        if(paso < 0)             { setTimeout(()=>{ paso += imgs.length; mover(true); }, 1900); }
    }
    function auto(){ clearInterval(timer); timer = setInterval(()=>{ paso++; mover(); loop(); }, 2800); }

    window.addEventListener('load',   ()=>{ mover(true); auto(); });
    window.addEventListener('resize', ()=>mover(true));

    wrapper.addEventListener('mousedown', e=>{
        drag=true; startX=e.clientX;
        const m = track.style.transform.match(/-?([\d.]+)px/);
        startOff = m ? parseFloat(m[0]) : 0;
        track.style.transition='none'; track.style.cursor='grabbing';
        clearInterval(timer);
    });
    window.addEventListener('mousemove', e=>{ if(!drag) return; track.style.transform=`translateX(${startOff+(e.clientX-startX)}px)`; });
    window.addEventListener('mouseup', e=>{
        if(!drag) return; drag=false; track.style.cursor='';
        paso = Math.round(-(startOff+(e.clientX-startX)) / anchoCard());
        track.style.transition='transform 1.8s ease'; mover(); loop(); setTimeout(auto,2000);
    });
    wrapper.addEventListener('touchstart', e=>{ startX=e.touches[0].clientX; const m=track.style.transform.match(/-?([\d.]+)px/); startOff=m?parseFloat(m[0]):0; track.style.transition='none'; clearInterval(timer); },{passive:true});
    wrapper.addEventListener('touchmove',  e=>{ track.style.transform=`translateX(${startOff+(e.touches[0].clientX-startX)}px)`; },{passive:true});
    wrapper.addEventListener('touchend',   e=>{ const dx=e.changedTouches[0].clientX-startX; paso=Math.round(-(startOff+dx)/anchoCard()); mover(); loop(); setTimeout(auto,2000); });
})();

// ── STATS COUNTER ──
(function(){
    const stats = document.querySelectorAll('.stat-num[data-target]');
    function countUp(el){
        const target = +el.dataset.target;
        const step   = Math.ceil(target / 60);
        let cur = 0;
        const t = setInterval(()=>{
            cur = Math.min(cur + step, target);
            el.textContent = cur.toLocaleString('es');
            if(cur >= target) clearInterval(t);
        }, 25);
    }
    if('IntersectionObserver' in window){
        const obs = new IntersectionObserver(entries=>{
            entries.forEach(e=>{ if(e.isIntersecting){ countUp(e.target); obs.unobserve(e.target); } });
        }, { threshold: 0.5 });
        stats.forEach(s=>obs.observe(s));
    } else {
        stats.forEach(s=>{ s.textContent = (+s.dataset.target).toLocaleString('es'); });
    }
})();

// ── CELLO1 arrastrable ──
(function(){
    const cello = document.querySelector('.cello1');
    if(!cello) return;
    const soloPC = window.matchMedia('(hover:hover) and (pointer:fine)');
    const srcN = cello.dataset.srcNormal, srcH = cello.dataset.srcHover;
    const ROT  = 'rotate(-8deg)';
    cello.style.transform = ROT;
    let sx,sy,ol,ot,dragging=false;
    function start(cx,cy){ ol=cello.offsetLeft; ot=cello.offsetTop; sx=cx; sy=cy;
        cello.style.left=ol+'px'; cello.style.top=ot+'px'; cello.style.right='auto'; cello.style.bottom='auto'; dragging=true; }
    function move(cx,cy){ cello.style.left=(ol+cx-sx)+'px'; cello.style.top=(ot+cy-sy)+'px'; cello.style.transform=ROT; }
    cello.addEventListener('mousedown',e=>{ e.preventDefault(); start(e.clientX,e.clientY);
        if(soloPC.matches) cello.src=srcH;
        const mv=e=>move(e.clientX,e.clientY); document.addEventListener('mousemove',mv);
        document.addEventListener('mouseup',e=>{ dragging=false; document.removeEventListener('mousemove',mv);
            const r=cello.getBoundingClientRect();
            if(soloPC.matches && !(e.clientX>=r.left&&e.clientX<=r.right&&e.clientY>=r.top&&e.clientY<=r.bottom)) cello.src=srcN;
        },{once:true}); });
    cello.addEventListener('touchstart',e=>{ const t=e.touches[0]; start(t.clientX,t.clientY); },{passive:true});
    cello.addEventListener('touchmove', e=>{ e.preventDefault(); const t=e.touches[0]; move(t.clientX,t.clientY); },{passive:false});
    if(soloPC.matches){
        cello.addEventListener('mouseenter',()=>{ if(!dragging) cello.src=srcH; });
        cello.addEventListener('mouseleave',()=>{ if(!dragging) cello.src=srcN; });
    }
})();

// ── CELLO2 arrastrable ──
(function(){
    const cello = document.querySelector('.cello2');
    if(!cello) return;
    const soloPC = window.matchMedia('(hover:hover) and (pointer:fine)');
    const srcN = cello.dataset.srcNormal, srcH = cello.dataset.srcHover;
    const ROT  = 'rotate(10deg)';
    cello.style.transform = ROT;
    let sx,sy,ol,ot,dragging=false;
    function start(cx,cy){ ol=cello.offsetLeft; ot=cello.offsetTop; sx=cx; sy=cy;
        cello.style.left=ol+'px'; cello.style.top=ot+'px'; cello.style.right='auto'; cello.style.bottom='auto'; dragging=true; }
    function move(cx,cy){ cello.style.left=(ol+cx-sx)+'px'; cello.style.top=(ot+cy-sy)+'px'; cello.style.transform=ROT; }
    cello.addEventListener('mousedown',e=>{ e.preventDefault(); start(e.clientX,e.clientY);
        if(soloPC.matches) cello.src=srcH;
        const mv=e=>move(e.clientX,e.clientY); document.addEventListener('mousemove',mv);
        document.addEventListener('mouseup',e=>{ dragging=false; document.removeEventListener('mousemove',mv);
            const r=cello.getBoundingClientRect();
            if(soloPC.matches && !(e.clientX>=r.left&&e.clientX<=r.right&&e.clientY>=r.top&&e.clientY<=r.bottom)) cello.src=srcN;
        },{once:true}); });
    cello.addEventListener('touchstart',e=>{ const t=e.touches[0]; start(t.clientX,t.clientY); },{passive:true});
    cello.addEventListener('touchmove', e=>{ e.preventDefault(); const t=e.touches[0]; move(t.clientX,t.clientY); },{passive:false});
    if(soloPC.matches){
        cello.addEventListener('mouseenter',()=>{ if(!dragging) cello.src=srcH; });
        cello.addEventListener('mouseleave',()=>{ if(!dragging) cello.src=srcN; });
    }
})();
</script>
