<style>
:root {
    --primary: #c62828;
    --secondary: #ffcc00;
    --dark: #1a1a1a;
    --light: #ffffff;
}

/* HEADER */
.header {
    position: sticky;
    top: 0;
    background: var(--light);
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    z-index: 9999;
}

.nav-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 40px;
}

/* LOGO */
.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: bold;
    color: var(--primary);
    font-size: 20px;
}

.logo img {
    height: 52px;
}

/* MENU */
.nav-menu {
    display: flex;
    gap: 30px;
}

.nav-menu a {
    text-decoration: none;
    color: var(--dark);
    font-weight: 700;
    font-family: 'Gotham', 'Inter', sans-serif;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: color 0.3s;
}

.nav-menu a svg {
    width: 15px;
    height: 15px;
    stroke-width: 2;
    flex-shrink: 0;
}

/* Carga Gotham local para el navbar */
@font-face {
    font-family: 'Gotham';
    src: url('../../tipografia/gothamnarrowoffice_bold.otf') format('opentype');
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}

/* EFECTO SUBRAYADO — crece desde el centro */
.nav-menu a::after {
    content: "";
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--primary);
    transform: scaleX(0);
    transform-origin: center;
    transition: transform 0.3s ease;
}

.nav-menu a:hover::after {
    transform: scaleX(1);
}

/* BOTON PEDIR — estilo cart con pierna de pollo animada */
.btn-order {
    width: 140px;
    height: 44px;
    border: none;
    border-radius: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: white;
    font-family: 'Gotham', 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    text-decoration: none;
    position: relative;
    background-color: var(--primary);
    transition: background-color 0.3s ease-in-out;
    cursor: pointer;
    overflow: hidden;
    flex-shrink: 0;
}

.btn-order:active { transform: scale(0.96); }

.btn-order__cart {
    z-index: 2;
    flex-shrink: 0;
}

.btn-order__label {
    z-index: 2;
}

.btn-order:hover {
    background-color: #a81e1e;
}

/* MENU MOBILE — oculto en móvil, reemplazado por bottom-nav */
.menu-toggle { display: none; }

/* BOTTOM NAV — oculto en desktop */
.bo-bottom-nav { display: none; }
.bo-options-menu {
    position: fixed;
    bottom: 100px;
    right: 5%;
    background: rgba(38, 36, 36, 0.98);
    backdrop-filter: blur(15px);
    border-radius: 15px;
    padding: 10px 0;
    list-style: none;
    width: 180px;
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 10000;
    display: none;
}

/* RESPONSIVE */
@media (max-width: 768px) {

    .nav-menu   { display: none; }
    .btn-order  { display: none; }
    .nav-wrapper { padding: 8px 16px; }

    /* Mostrar btn-order en móvil, dentro del header */
    .btn-order {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        padding: 8px 14px;
    }

    /* ── Bottom Nav ── */
    .bo-bottom-nav {
        display: flex;
        position: fixed;
        bottom: calc(20px + env(safe-area-inset-bottom));
        left: 5%;
        right: 5%;
        width: 90%;
        height: 70px;
        background: rgba(38, 36, 36, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        justify-content: space-around;
        align-items: center;
        z-index: 9998;
        box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        border: 1px solid rgba(255,255,255,0.1);
    }

    .bo-nav-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        color: #b0b0b0;
        text-decoration: none;
        font-family: 'Inter', sans-serif;
        font-size: 10px;
        transition: 0.3s ease;
        flex: 1;
        position: relative;
        z-index: 1;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .bo-nav-link span {
        margin-top: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .bo-nav-link.active {
        color: #fff;
        transform: translateY(-20px);
    }

    .bo-nav-link.active::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 60px;
        height: 60px;
        background: #ce1212;
        border-radius: 50%;
        z-index: -1;
        box-shadow: 0 8px 20px rgba(211,47,47,0.5);
        border: 4px solid #fff;
    }

    .bo-nav-link.active span { display: none; }

    /* ── Menú opciones ── */
    .bo-options-menu {
        display: block;
    }

    .bo-options-menu.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .bo-options-menu li a {
        display: block;
        padding: 12px 20px;
        color: white;
        text-decoration: none;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 600;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .bo-options-menu li:last-child a { border-bottom: none; }
    .bo-options-menu li a:active    { background: #ce1212; border-radius: 8px; }

    /* Hamburguesa → X */
    .bo-hamburger svg { transition: transform 0.3s ease; }
    .line-top-bo, .line-mid-bo, .line-bot-bo {
        transition: all 0.3s ease;
        transform-origin: center;
    }
    .bo-nav-link.menu-open .line-top-bo { transform: translateY(6px) rotate(45deg); }
    .bo-nav-link.menu-open .line-mid-bo { opacity: 0; transform: scaleX(0); }
    .bo-nav-link.menu-open .line-bot-bo { transform: translateY(-6px) rotate(-45deg); }
    .bo-nav-link.menu-open { color: #ffcc00 !important; }

    /* Espacio para que el contenido no quede bajo la barra */
    body { padding-bottom: calc(100px + env(safe-area-inset-bottom)); }
}
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://unpkg.com/lucide@latest"></script>

<header class="header">
    <div class="nav-wrapper">

        <!-- LOGO — link a la página principal -->
        <a href="../index.html" class="logo">
            <img src="img/logito.png" alt="Pollo El Solar">
            <span></span>
        </a>

        <!-- MENU desktop -->
        <nav class="nav-menu" id="navMenu">
            <a href="indexbo.php">
                <i data-lucide="home"></i>
                <span>Inicio</span>
            </a>
            <a href="menu.php">
                <i data-lucide="clipboard-list"></i>
                <span>Menú</span>
            </a>
            <a href="sucursal.php">
                <i data-lucide="map-pin"></i>
                <span>Sucursales</span>
            </a>
            <a href="menu.php">
                <i data-lucide="star"></i>
                <span>Promos</span>
            </a>
            <a href="convenio.php">
                <i data-lucide="handshake"></i>
                <span>Convenio</span>
            </a>
            <a href="contacto.php">
                <i data-lucide="mail"></i>
                <span>Contacto</span>
            </a>
        </nav>

        <!-- CTA desktop -->
        <a href="#" class="btn-order" aria-label="Pedir ya">
            <svg class="btn-order__cart" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="18">
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="9" cy="21" r="1" fill="currentColor"/>
                <circle cx="20" cy="21" r="1" fill="currentColor"/>
            </svg>
            <span class="btn-order__label">DELIVERY</span>
        </a>

    </div>
</header>

<!-- Bottom Nav móvil -->
<nav class="bo-bottom-nav">
    <a href="indexbo.php" class="bo-nav-link" data-match="indexbo">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span>Inicio</span>
    </a>
    <a href="menu.php" class="bo-nav-link" data-match="menu">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1" ry="1"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/></svg>
        <span>Menú</span>
    </a>
    <a href="sucursal.php" class="bo-nav-link" data-match="sucursal">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <span>Sucursales</span>
    </a>
    <a href="menu.php?tab=promos" class="bo-nav-link" data-match="promos">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        <span>Promos</span>
    </a>
    <a href="javascript:void(0)" class="bo-nav-link bo-hamburger" id="bo-options-trigger">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line class="line-top-bo" x1="3" y1="6" x2="21" y2="6"/>
            <line class="line-mid-bo" x1="3" y1="12" x2="21" y2="12"/>
            <line class="line-bot-bo" x1="3" y1="18" x2="21" y2="18"/>
        </svg>
        <span>Más</span>
    </a>
</nav>

<!-- Menú opciones que sube -->
<ul class="bo-options-menu" id="bo-options-menu">
    <li><a href="convenio.php"><i class="fas fa-handshake" style="margin-right:8px;color:#aaa"></i>Convenio</a></li>
    <li><a href="contacto.php"><i class="fas fa-envelope" style="margin-right:8px;color:#aaa"></i>Contacto</a></li>
    <li><a href="../index.html"><i class="fas fa-globe" style="margin-right:8px;color:#aaa"></i>Página principal</a></li>
</ul>

<script>
function toggleMenu() {
    document.getElementById('navMenu').classList.toggle('active');
}

// Bottom nav — marcar link activo según página actual
(function() {
    const links  = document.querySelectorAll('.bo-nav-link[data-match]');
    const pagina = window.location.pathname.split('/').pop().replace('.php','');
    const tab    = new URLSearchParams(window.location.search).get('tab') ?? '';

    links.forEach(l => {
        const match = l.dataset.match;
        // "menu" activo solo si estamos en menu.php SIN tab=promos
        if (match === 'menu' && pagina === 'menu' && tab !== 'promos') { l.classList.add('active'); return; }
        // resto: coincidencia por nombre de archivo
        if (match !== 'menu' && pagina === match) { l.classList.add('active'); }
    });
})();

// Bottom nav — menú opciones
const boTrigger = document.getElementById('bo-options-trigger');
const boMenu    = document.getElementById('bo-options-menu');

boTrigger?.addEventListener('click', e => {
    e.preventDefault();
    const abierto = boMenu.classList.toggle('show');
    boTrigger.classList.toggle('menu-open', abierto);
});

document.addEventListener('click', e => {
    if (!boTrigger?.contains(e.target) && !boMenu?.contains(e.target)) {
        boMenu?.classList.remove('show');
        boTrigger?.classList.remove('menu-open');
    }
});

// Activar iconos Lucide
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>