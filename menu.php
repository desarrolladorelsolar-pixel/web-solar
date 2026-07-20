<?php
/**
 * menu.php — Página pública del menú de Pollo El Solar (Bolivia)
 *
 * Carga productos y categorías desde la BD y los renderiza en un
 * "río infinito" de dos tiras horizontales con scroll sincronizado.
 *
 * Dependencias: config.php (conexión PDO $pdo), navbar.php, footer implícito propio
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';

/* ─────────────────────────────────────────────────────────────
   FUNCIÓN: obtenerImg
   Devuelve la ruta de imagen para un producto.
   Si hay foto en producto_fotos la usa directamente.
   Si no hay foto devuelve '' — sin fallback ni imagen por defecto.
   ───────────────────────────────────────────────────────────── */
function obtenerImg(array $p): string {
    if (!empty($p['ruta_foto'])) {
        return $p['ruta_foto'];
    }
    return '';
}

/* ─────────────────────────────────────────────────────────────
   CONSULTAS A LA BASE DE DATOS
   ───────────────────────────────────────────────────────────── */

// Categorías activas
$cats_db = $pdo->query(
    "SELECT id, nombre FROM categorias
     WHERE estado = 1
     ORDER BY id ASC"
)->fetchAll();

// Productos activos con su foto principal (la de menor orden con estado=1)
$prods_db = $pdo->query("
    SELECT
        p.id,
        p.nombre,
        p.descripcion,
        p.precio,
        p.precio_oferta,
        p.etiqueta_oferta,
        p.moneda,
        p.dia_semana,
        p.es_combo,
        p.destacado                AS prod_destacado,
        c.id                       AS cat_id,
        c.nombre                   AS cat_nombre,
        (SELECT pf.ruta_foto
         FROM producto_fotos pf
         WHERE pf.producto_id = p.id AND pf.estado = 1
         ORDER BY pf.orden ASC LIMIT 1) AS ruta_foto
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
    WHERE p.estado = 1 AND p.visible = 1
    ORDER BY c.id, p.id
")->fetchAll();

/* ─────────────────────────────────────────────────────────────
   CONSTRUCCIÓN DEL RÍO
   El río es la lista plana de productos en el orden que
   aparecerán en las tiras:
     1. Productos con destacado=1 (grupo "destacados")
     2. Resto agrupados por categoría (sin repetir los ya destacados)
   ───────────────────────────────────────────────────────────── */
$rio             = [];
$idsDestacados   = [];

// Grupo destacados: solo productos con destacado=1
foreach ($prods_db as $p) {
    if ($p['prod_destacado']) {
        $rio[]           = ['id' => $p['id'], 'categoria' => 'destacados', 'nombre' => mb_strtoupper($p['nombre']), 'img' => obtenerImg($p), 'precio' => $p['precio'], 'precio_oferta' => $p['precio_oferta'], 'etiqueta_oferta' => $p['etiqueta_oferta'], 'descripcion' => $p['descripcion'], 'cat_nombre' => $p['cat_nombre'], 'dia_semana' => $p['dia_semana'], 'es_combo' => $p['es_combo']];
        $idsDestacados[] = $p['id'];
    }
}

// Grupos por categoría (excluye los ya en destacados)
foreach ($cats_db as $cat) {
    foreach ($prods_db as $p) {
        if ($p['cat_id'] == $cat['id'] && !in_array($p['id'], $idsDestacados)) {
            $rio[] = ['id' => $p['id'], 'categoria' => 'cat_' . $cat['id'], 'nombre' => mb_strtoupper($p['nombre']), 'img' => obtenerImg($p), 'precio' => $p['precio'], 'precio_oferta' => $p['precio_oferta'], 'etiqueta_oferta' => $p['etiqueta_oferta'], 'descripcion' => $p['descripcion'], 'cat_nombre' => $p['cat_nombre'], 'dia_semana' => $p['dia_semana'], 'es_combo' => $p['es_combo']];
        }
    }
}

/* ─────────────────────────────────────────────────────────────
   MODO DE RENDERIZADO
   Con ≥8 productos se activa el loop infinito (3 copias por tira).
   Con menos se muestra 1 copia sin loop para no repetir.
   ───────────────────────────────────────────────────────────── */
$loopInfinito = count($rio) >= 8;
$copias       = $loopInfinito ? 3 : 1;

// Lista de categorías que usará el JS (solo las que tienen productos)
$cats_js = ['destacados'];
foreach ($cats_db as $cat) {
    foreach ($rio as $p) {
        if ($p['categoria'] === 'cat_' . $cat['id']) {
            $cats_js[] = 'cat_' . $cat['id'];
            break;
        }
    }
}
$cats_js = array_unique($cats_js);

/* ─────────────────────────────────────────────────────────────
   MAPA DE CATEGORÍAS CON PRODUCTOS
   Pre-calculado para evitar loops anidados duplicados en el HTML.
   $cats_con_productos[id] = true si esa categoría tiene al menos
   un producto en el río (usado por data-scroll en los botones).
   ───────────────────────────────────────────────────────────── */
$cats_con_productos = [];
foreach ($cats_db as $cat) {
    $key = 'cat_' . $cat['id'];
    foreach ($rio as $p) {
        if ($p['categoria'] === $key) {
            $cats_con_productos[$cat['id']] = true;
            break;
        }
    }
}
?>
<?php include 'navbar.php'; ?>
<!-- Estas etiquetas están aquí porque navbar.php incluye el cierre del <head> -->
<title>Menú | Pollo El Solar</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="menu.css">
<link href="https://fonts.googleapis.com/css2?family=Boogaloo&display=swap" rel="stylesheet">

<!-- ==================== CONTENEDOR PRINCIPAL ==================== -->
<main class="menu-page" role="main" aria-label="Menú de productos">

  <!-- ==================== SLOGAN ==================== -->
  <header class="menu-logo-bar" role="banner" aria-label="Slogan de la marca">
    <div class="texto" aria-hidden="true">
      <span>SABROSO</span>
      <span>HASTA LOS HUESOS</span>
    </div>
  </header>

  <!-- ==================== BARRA DE CATEGORÍAS ==================== -->
  <nav class="categorias-wrapper" aria-label="Filtrar por categoría">

    <!-- Botón hamburguesa: visible en móvil/tablet, abre el panel lateral -->
    <button id="btnCatMenu" class="categoria-hamburguesa"
            aria-label="Abrir menú de categorías"
            aria-controls="catPanel">
      <i class="fas fa-bars" aria-hidden="true"></i>
    </button>

    <!-- Scroll horizontal de botones de categoría -->
    <div id="categoriasBar" class="categorias" aria-label="Categorías de productos">

      <!-- Destacados: siempre primero -->
      <button class="categoria-btn active"
              data-categoria="destacados"
              data-scroll="destacados">DESTACADOS</button>

      <?php foreach ($cats_db as $cat):
        $key   = 'cat_' . $cat['id'];
        $tiene = !empty($cats_con_productos[$cat['id']]);
      ?>
      <button class="categoria-btn"
              data-categoria="<?= $key ?>"
              data-scroll="<?= $tiene ? $key : '' ?>">
        <?= mb_strtoupper(htmlspecialchars($cat['nombre'])) ?>
      </button>
      <?php endforeach; ?>

    </div>
  </nav>

  <!-- ==================== PANEL FLOTANTE DE CATEGORÍAS ==================== -->
  <aside id="catPanel" class="cat-panel"
         aria-label="Panel de categorías">

    <div class="cat-panel-header">
      <span id="catPanelTitle">CATEGORÍAS</span>
      <button id="btnCatClose" class="cat-panel-close"
              aria-label="Cerrar panel de categorías">
        <i class="fas fa-times" aria-hidden="true"></i>
      </button>
    </div>

    <button class="cat-panel-btn active"
            data-categoria="destacados"
            data-scroll="destacados">
      <i class="fas fa-star" aria-hidden="true"></i> DESTACADOS
    </button>

    <?php foreach ($cats_db as $cat):
      $key   = 'cat_' . $cat['id'];
      $tiene = !empty($cats_con_productos[$cat['id']]);
    ?>
    <button class="cat-panel-btn"
            data-categoria="<?= $key ?>"
            data-scroll="<?= $tiene ? $key : '' ?>">
      <i class="fas fa-chevron-right" aria-hidden="true"></i>
      <?= mb_strtoupper(htmlspecialchars($cat['nombre'])) ?>
    </button>
    <?php endforeach; ?>

  </aside>

  <!-- Overlay oscuro detrás del panel lateral -->
  <div id="catPanelOverlay" class="cat-panel-overlay" aria-hidden="true"></div>

  <!-- ==================== RÍO DE PRODUCTOS (DESKTOP) ==================== -->
  <!-- Espaciador superior — oculto en móvil vía CSS (#spacerTop) -->
  <div id="spacerTop" class="menu-spacer" aria-hidden="true"></div>

  <section id="gridWrapper" class="grid-wrapper"
           aria-label="Productos del menú"
           aria-live="polite">
    <div id="tirasContainer" class="tiras-container">
      <div id="tiraArriba" class="tira tira-arriba" role="list" aria-label="Productos fila superior"></div>
      <div id="tiraAbajo"  class="tira tira-abajo"  role="list" aria-label="Productos fila inferior"></div>
    </div>
  </section>

  <!-- ==================== GRID MÓVIL ==================== -->
  <!-- Grid vertical — visible solo en pantallas ≤768px -->
  <section id="gridMovil" class="menu-grid-movil"
           style="display:none"
           aria-label="Productos del menú"
           aria-live="polite"></section>

  <!-- Espaciador inferior -->
  <div id="spacerBottom" class="menu-spacer" aria-hidden="true"></div>

  <!-- ==================== LOGOS DECORATIVOS ==================== -->
  <!-- Arrastrables, position fixed, encima de todo el contenido -->
  <img id="cellomenu1" class="logo-deco-drag"
       src="../fotos/logo solar_Mesa de trabajo 1.png"
       alt="Logo decorativo El Solar — arrastrable"
       draggable="false">
  <img id="cellomenu2" class="logo-deco-drag"
       src="../fotos/logo solar_Mesa de trabajo 1.png"
       alt="Logo decorativo El Solar — arrastrable"
       draggable="false">

  <!-- ==================== FOOTER ==================== -->
  <footer class="footer-bo">
    <div class="footer-bo-info">
        <span><i class="fas fa-map-marker-alt"></i> Av. Cañoto 581, Santa Cruz, Bolivia</span>
        <span><i class="fas fa-phone"></i> +591 700 00000</span>
        <span><i class="fas fa-envelope"></i> info@polloelsolar.com</span>
    </div>
    <ul class="footer-bo-social">
        <li class="icon whatsapp">
            <span class="tooltip">WhatsApp</span>
            <a href="https://wa.link/njstgf" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
        </li>
        <li class="icon instagram">
            <span class="tooltip">Instagram</span>
            <a href="https://www.instagram.com/polloelsolar" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        </li>
        <li class="icon facebook">
            <span class="tooltip">Facebook</span>
            <a href="https://www.facebook.com/polloelsolar" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
        </li>
        <li class="icon tiktok">
            <span class="tooltip">TikTok</span>
            <a href="https://www.tiktok.com/@papasolar" target="_blank" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
        </li>
    </ul>
  </footer>

</main><!-- /menu-page -->

<!-- ==================== MODAL DETALLE PRODUCTO ==================== -->
<div id="modalProducto" class="prod-modal-overlay" aria-modal="true" role="dialog" aria-label="Detalle del producto" style="display:none;">
  <div class="prod-modal-box">
    <button class="prod-modal-close" id="prodModalClose" aria-label="Cerrar">
      <i class="fas fa-times"></i>
    </button>
    <div class="prod-modal-left">
      <div class="prod-modal-img-wrap">
        <img id="prodModalImgMain" src="" alt="" class="prod-modal-img-main">
        <span id="prodModalBadge" class="prod-modal-badge" style="display:none;"></span>
      </div>
      <div id="prodModalThumbs" class="prod-modal-thumbs"></div>
    </div>
    <div class="prod-modal-right">
      <span id="prodModalCategoria" class="prod-modal-categoria"></span>
      <h2 id="prodModalNombre" class="prod-modal-nombre"></h2>
      <div id="prodModalPrecioWrap" class="prod-modal-precio-wrap">
        <span id="prodModalPrecioTachado" class="prod-modal-precio-tachado" style="display:none;"></span>
        <span id="prodModalPrecio" class="prod-modal-precio"></span>
      </div>
      <p id="prodModalDesc" class="prod-modal-desc"></p>
      <div id="prodModalMeta" class="prod-modal-meta"></div>
      <button class="prod-modal-btn-pedir" id="prodModalBtnPedir">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
          <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <path d="M16 10a4 4 0 0 1-8 0"/>
        </svg>
        PEDIR AHORA
      </button>
    </div>
  </div>
</div>
<!-- / MODAL DETALLE PRODUCTO -->

<script>
/**
 * Lógica del menú — río de productos con scroll horizontal infinito
 *
 * ARQUITECTURA:
 *   - rioBase: lista plana de productos en el orden correcto (PHP → JS)
 *   - Las cards se reparten en dos tiras (par → arriba, impar → abajo)
 *   - Un único div padre (.grid-wrapper) scrollea horizontalmente
 *     y ambas tiras se mueven como un bloque sincronizado
 *   - Con ≥8 productos: 3 copias por tira + loop infinito silencioso
 *   - Con <8 productos: 1 copia, sin loop
 *   - data-categoria: identidad única del botón (no se duplica)
 *   - data-scroll: categoría de destino en el grid (puede compartirse)
 */

// ── Datos inyectados desde PHP ──────────────────────────────
const categorias    = <?= json_encode(array_values($cats_js)) ?>;
const rioBase       = <?= json_encode(array_values($rio)) ?>;
const COPIAS        = <?= $copias ?>;
const LOOP_INFINITO = <?= $loopInfinito ? 'true' : 'false' ?>;

// ══════════════════════════════════════════════════════════════
// CONSTANTES Y CONFIGURACIÓN
// ══════════════════════════════════════════════════════════════

// ── Breakpoints — deben coincidir con los media queries de menu.css ──
const BREAKPOINT_MOVIL  = 768;
const BREAKPOINT_TABLET = 1024;
const ES_MOVIL          = () => window.innerWidth <= BREAKPOINT_MOVIL;
const ES_TABLET         = () => window.innerWidth >  BREAKPOINT_MOVIL && window.innerWidth <= BREAKPOINT_TABLET;

// ── Escalado de cards — CALIBRADO VISUALMENTE, no cambiar sin prueba en dispositivo real ──
const ESCALA_MARGEN_INTERNO = 16;   // padding interno del grid-wrapper
const ESCALA_NOMBRE_H       = 30;   // altura estimada del nombre del producto
const ESCALA_FACTOR_TIRA    = 0.08; // proporción del gap entre tira arriba y abajo
const ESCALA_FACTOR_IMAGEN  = 0.7;  // fracción del espacio disponible para la imagen
const ESCALA_PADDING_CARD   = 80;   // padding extra del ancho de card sobre el ancho de imagen

// ══════════════════════════════════════════════════════════════
// UTILIDADES / HELPERS
// ══════════════════════════════════════════════════════════════

/**
 * generarPrecioHtml — genera el bloque HTML del precio de un producto.
 * @param {Object} p    — producto con campos precio, precio_oferta, etiqueta_oferta
 * @param {string} modo — 'desktop' | 'movil'
 * @returns {string} HTML del precio, o '' si no hay precio
 */
function generarPrecioHtml(p, modo) {
    if (!p.precio) return '';
    if (p.precio_oferta) {
        if (modo === 'desktop') {
            return `
            <span class="precio-texto precio-oferta-activa">
                ${p.etiqueta_oferta ? `<span class="etiqueta-oferta">${p.etiqueta_oferta}</span>` : ''}
                <span class="precio-normal-tachado">Bs.${parseFloat(p.precio).toFixed(0)}</span>
                <span class="precio-oferta">Bs.${parseFloat(p.precio_oferta).toFixed(0)}</span>
            </span>`;
        } else {
            return `
            <div class="precio-movil-wrap">
                ${p.etiqueta_oferta ? `<span class="etiqueta-oferta-movil">${p.etiqueta_oferta}</span>` : ''}
                <span class="precio-normal-tachado-movil">Bs.${parseFloat(p.precio).toFixed(0)}</span>
                <span class="precio-badge oferta">Bs.${parseFloat(p.precio_oferta).toFixed(0)}</span>
            </div>`;
        }
    }
    return modo === 'desktop'
        ? `<span class="precio-texto">Bs.${parseFloat(p.precio).toFixed(0)}</span>`
        : `<div class="precio-badge">Bs.${parseFloat(p.precio).toFixed(0)}</div>`;
}

// ══════════════════════════════════════════════════════════════
// REFERENCIAS AL DOM
// ══════════════════════════════════════════════════════════════
const tiraArriba    = document.getElementById('tiraArriba');
const tiraAbajo     = document.getElementById('tiraAbajo');
const gridWrapper   = document.getElementById('gridWrapper');
const categoriasBar = document.getElementById('categoriasBar');
const botones       = document.querySelectorAll('.categoria-btn');

// ── Estado de navegación ────────────────────────────────────
let categoriaActiva  = 'destacados'; // data-categoria del botón activo
let navegandoPorClic = false;        // bloquea el spy durante scroll animado
let anchoUnaCopia    = 0;            // ancho en px de una copia del río (para el loop)
const posiciones     = {};           // { cat_id: [posX_copiaA, posX_copiaB, posX_copiaC] }

/* ─── Reparto en tiras ──────────────────────────────────────
   Los productos pares van a la tira de arriba y los impares
   a la de abajo. Esto mantiene el orden lógico del río y
   produce el efecto "ladrillo" con el offset CSS.
   ─────────────────────────────────────────────────────────── */
function repartirEnTiras(rio) {
    const arriba = [], abajo = [];
    rio.forEach((p, i) => (i % 2 === 0 ? arriba : abajo).push(p));
    return { arriba, abajo };
}

const { arriba: rioArriba, abajo: rioAbajo } = repartirEnTiras(rioBase);

/* ─── Creación de HTML de una card ─────────────────────────
   Cada card lleva data-cat para que el spy sepa su categoría.
   ─────────────────────────────────────────────────────────── */
function crearCard(p) {
    return `<div class="card" data-cat="${p.categoria}" data-id="${p.id}" onclick="event.stopPropagation(); abrirModalProducto(${p.id})" style="cursor:pointer;">
        <div class="card-imagen-box">
            <img class="producto-img" src="${p.img}" alt="${p.nombre}" loading="lazy">
            ${generarPrecioHtml(p, 'desktop')}
        </div>
        <div class="card-nombre">${p.nombre}</div>
        <div class="separador"></div>
    </div>`;
}

/* ─── Renderizado de una tira ───────────────────────────────
   Se renderizan COPIAS veces la lista para que el loop
   infinito tenga contenido suficiente a ambos lados.
   ─────────────────────────────────────────────────────────── */
function renderTira(el, lista) {
    let html = '';
    for (let c = 0; c < COPIAS; c++) lista.forEach(p => html += crearCard(p));
    el.innerHTML = html;
}

// ADVERTENCIA: estas líneas se ejecutan al parsear el script.
// Dependen de que el <script> esté al final del <body> — no mover al <head>.
renderTira(tiraArriba, rioArriba);
renderTira(tiraAbajo,  rioAbajo);

/* ─── Mapa de posiciones ────────────────────────────────────
   Para cada categoría guarda el offsetLeft de su primera card
   en cada copia. Usado por irACategoria() para elegir el
   camino más corto al hacer clic.
   ─────────────────────────────────────────────────────────── */
function construirMapa() {
    const cards = Array.from(tiraArriba.querySelectorAll('.card'));
    const n     = rioArriba.length;
    if (!cards[n] || n === 0) return;

    // Ancho de una copia = distancia entre la primera card de copia 0 y de copia 1
    anchoUnaCopia = cards[n].offsetLeft - cards[0].offsetLeft;

    // Categorías únicas presentes en la tira de arriba
    [...new Set(rioArriba.map(p => p.categoria))].forEach(cat => {
        posiciones[cat] = [];
        for (let c = 0; c < COPIAS; c++) {
            for (let i = c * n; i < Math.min((c + 1) * n, cards.length); i++) {
                if (cards[i]?.dataset.cat === cat) {
                    posiciones[cat].push(cards[i].offsetLeft);
                    break;
                }
            }
        }
    });
}

/* ─── Posición inicial ──────────────────────────────────────
   Arrancamos en la copia B (índice 1) para tener margen
   hacia ambos lados antes de que el loop salte.
   ─────────────────────────────────────────────────────────── */
function inicializar() {
    construirMapa();
    gridWrapper.scrollLeft = posiciones['destacados']?.[1] ?? 0;
}
window.addEventListener('load', inicializar);

/* ─── Evento scroll ─────────────────────────────────────────
   1. Si LOOP_INFINITO: salta silenciosamente al acercarse
      a los extremos (el usuario no lo nota porque el contenido
      es idéntico en las tres copias).
   2. Actualiza el botón activo según lo visible (spy).
   ─────────────────────────────────────────────────────────── */
gridWrapper.addEventListener('scroll', () => {
    const s = gridWrapper.scrollLeft;
    if (LOOP_INFINITO) {
        if (s < anchoUnaCopia * 0.4) { gridWrapper.scrollLeft = s + anchoUnaCopia; return; }
        if (s > anchoUnaCopia * 1.6) { gridWrapper.scrollLeft = s - anchoUnaCopia; return; }
    }
    if (!navegandoPorClic) spy();
}, { passive: true });

/* ─── Spy de categoría ──────────────────────────────────────
   Detecta qué categoría está en el borde izquierdo visible
   y marca el botón correspondiente.
   Usa data-scroll para relacionar posición con botón.
   ─────────────────────────────────────────────────────────── */
function spy() {
    const borde = gridWrapper.scrollLeft + 60;
    const cards = Array.from(tiraArriba.querySelectorAll('.card'));
    let catVisible = rioArriba[0]?.categoria ?? 'destacados';

    for (const card of cards) {
        if (card.offsetLeft + card.offsetWidth > borde) {
            catVisible = card.dataset.cat;
            break;
        }
    }

    // Buscar el botón que apunta a esa categoría (por data-scroll)
    let btn = null;
    for (const b of botones) {
        if (b.dataset.scroll === catVisible) { btn = b; break; }
    }
    // Fallback: buscar por data-categoria
    if (!btn) {
        for (const b of botones) {
            if (b.dataset.categoria === catVisible) { btn = b; break; }
        }
    }

    if (btn && btn.dataset.categoria !== categoriaActiva) {
        categoriaActiva = btn.dataset.categoria;
        marcarBoton(btn);
    }
}

/* ─── Marcar botón activo ───────────────────────────────────
   Quita la clase active de todos y la pone solo en el botón
   dado. Además lo centra en la barra de categorías.
   También sincroniza el panel desplegable móvil.
   ─────────────────────────────────────────────────────────── */
function marcarBoton(btn) {
    botones.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    btn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    // Sincronizar panel móvil
    document.querySelectorAll('.cat-panel-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.categoria === btn.dataset.categoria);
    });
}

/* ─── Clic en categoría ─────────────────────────────────────
   Cada botón tiene:
     - data-categoria: su identidad única
     - data-scroll: la categoría de destino en el grid
   Se elige la posición más cercana entre las copias disponibles
   para que el scroll tome el camino más corto.
   Si data-scroll está vacío (categoría sin productos), solo marca.
   ─────────────────────────────────────────────────────────── */
let _clicTimer = null;
botones.forEach(btn => {
    btn.addEventListener('click', () => {
        categoriaActiva = btn.dataset.categoria;
        marcarBoton(btn);

        // ── Móvil: filtrar por categoría ──
        if (ES_MOVIL()) {
            filtrarMovil(btn.dataset.scroll);
            return;
        }

        // ── Desktop/tablet: scroll horizontal en el río ──
        navegarDesktop(btn.dataset.scroll);
    });
});

/* ─── navegarDesktop ────────────────────────────────────────
   Scrollea el río horizontal a la categoría indicada,
   eligiendo la copia más cercana para el camino más corto.
   Usada tanto por los botones de la barra como por el panel
   flotante en tablet.
   ─────────────────────────────────────────────────────────── */
function navegarDesktop(dest) {
    if (!dest || !posiciones[dest]?.length) return;

    const actual = gridWrapper.scrollLeft;
    let target   = posiciones[dest][0];
    let minDist  = Math.abs(actual - target);
    posiciones[dest].forEach(pos => {
        const d = Math.abs(actual - pos);
        if (d < minDist) { minDist = d; target = pos; }
    });

    navegandoPorClic = true;
    gridWrapper.scrollTo({ left: target, behavior: 'smooth' });
    clearTimeout(_clicTimer);
    _clicTimer = setTimeout(() => { navegandoPorClic = false; }, 700);
}

/* ─── Rueda del mouse sobre la barra ───────────────────────
   Permite navegar entre categorías girando la rueda del mouse
   sobre la barra, sin mover la página.
   ─────────────────────────────────────────────────────────── */
categoriasBar.addEventListener('wheel', e => {
    e.preventDefault();
    const lista = Array.from(botones);
    const idx   = lista.findIndex(b => b.dataset.categoria === categoriaActiva);
    const sig   = e.deltaY > 0
        ? (idx + 1) % lista.length
        : (idx - 1 + lista.length) % lista.length;
    lista[sig].click();
}, { passive: false });

/* ─── Rueda del mouse sobre el grid ────────────────────────
   Mientras el cursor está dentro del grid, la rueda scrollea
   horizontalmente. Al salir del grid vuelve el comportamiento
   normal de scroll vertical de la página.
   ─────────────────────────────────────────────────────────── */
let enGrid = false;
gridWrapper.addEventListener('mouseenter', () => enGrid = true);
gridWrapper.addEventListener('mouseleave', () => enGrid = false);
gridWrapper.addEventListener('wheel', e => {
    if (!enGrid) return;
    e.preventDefault();
    gridWrapper.scrollLeft += e.deltaY * 2;
}, { passive: false });

/* ─── MÓVIL: grid vertical ──────────────────────────────────
   Si la pantalla es móvil (≤768px):
     - Se oculta el río horizontal
     - Se renderiza el gridMovil con todas las cards
     - Los botones de categoría filtran las cards visibles
       scrolleando la página al primer producto del grupo
   ─────────────────────────────────────────────────────────── */
const gridMovil  = document.getElementById('gridMovil');

function crearCardMovil(p) {
    return `<div class="card-movil" data-cat="${p.categoria}" data-id="${p.id}" onclick="event.stopPropagation(); abrirModalProducto(${p.id})" style="cursor:pointer;">
        <img class="producto-img-movil" src="${p.img}" alt="${p.nombre}" loading="lazy">
        <div class="card-info-movil">
            <div class="card-nombre-movil">${p.nombre}</div>
            ${generarPrecioHtml(p, 'movil')}
        </div>
    </div>`;
}

function renderMovil() {
    if (!ES_MOVIL()) return;
    if (gridMovil.innerHTML !== '') {
        // Ya renderizado, solo asegurar visibilidad
        gridWrapper.style.display = 'none';
        gridMovil.style.display   = 'grid';
        return;
    }
    gridWrapper.style.display = 'none';
    gridMovil.style.display   = 'grid';
    gridMovil.innerHTML = rioBase.map(p => crearCardMovil(p)).join('');
}

// Filtrar cards en móvil por categoría (sin scroll)
function filtrarMovil(dest) {
    const cards = Array.from(gridMovil.querySelectorAll('.card-movil'));
    cards.forEach(card => {
        const visible = !dest || card.dataset.cat === dest;
        card.style.display = visible ? '' : 'none';
    });
    // Volver al inicio del grid al cambiar categoría
    gridMovil.scrollTop = 0;
}

// spyMovil eliminado — ya no se usa scroll spy en móvil

/* ─── Panel flotante de categorías (móvil/tablet) ──────────
   position: fixed — flota encima de todo, no empuja cards.
   Se abre/cierra con el botón hamburguesa.
   El overlay oscuro cierra el panel al tocarlo.
   ─────────────────────────────────────────────────────────── */
const btnCatMenu     = document.getElementById('btnCatMenu');
const btnCatClose    = document.getElementById('btnCatClose');
const catPanel       = document.getElementById('catPanel');
const catOverlay     = document.getElementById('catPanelOverlay');
const botonesPanel   = document.querySelectorAll('.cat-panel-btn');

function abrirPanel() {
    catPanel.classList.add('open');
    catOverlay.classList.add('visible');
    btnCatMenu.classList.add('active');
}

function cerrarPanel() {
    catPanel.classList.remove('open');
    catOverlay.classList.remove('visible');
    btnCatMenu.classList.remove('active');
}

btnCatMenu?.addEventListener('click', () => {
    catPanel.classList.contains('open') ? cerrarPanel() : abrirPanel();
});

btnCatClose?.addEventListener('click', cerrarPanel);
catOverlay?.addEventListener('click', cerrarPanel);

botonesPanel.forEach(btn => {
    btn.addEventListener('click', () => {
        botonesPanel.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Sincronizar barra de categorías
        categoriaActiva = btn.dataset.categoria;
        const barBtn = [...botones].find(b => b.dataset.categoria === btn.dataset.categoria);
        if (barBtn) marcarBoton(barBtn);

        // Navegar según dispositivo
        if (ES_MOVIL()) {
            filtrarMovil(btn.dataset.scroll);
        } else {
            // Tablet: el río horizontal está activo — mismo comportamiento que desktop
            navegarDesktop(btn.dataset.scroll);
        }

        cerrarPanel();
    });
});

// Sin scroll spy en móvil — el filtrado es instantáneo al seleccionar categoría

// Inicializar según dispositivo
function inicializarSegunDispositivo() {
    if (ES_MOVIL()) {
        renderMovil();
    } else {
        // Tanto tablet como PC usan el río horizontal
        gridWrapper.style.display    = '';
        gridWrapper.style.visibility = 'hidden';
        gridWrapper.style.opacity    = '0';
        gridMovil.style.display      = 'none';

        // Asegurar que las tiras tienen contenido (por si cargó en móvil primero)
        if (tiraArriba.innerHTML === '') {
            renderTira(tiraArriba, rioArriba);
            renderTira(tiraAbajo,  rioAbajo);
        }

        // Esperar un frame para que el layout esté listo antes de medir
        requestAnimationFrame(() => {
            inicializar();
            escalarCards();
        });
    }
}

// Reaccionar a cambio de tamaño (rotar pantalla, tablet↔pc↔mobile)
let _resizeMenu;
function onResize() {
    clearTimeout(_resizeMenu);
    _resizeMenu = setTimeout(() => {
        inicializarSegunDispositivo();
        if (window.innerWidth > BREAKPOINT_MOVIL) escalarCards();
    }, 100);
}
window.addEventListener('resize', onResize);

// Registrar inicializarSegunDispositivo en load (reemplaza el inicializar original)
window.removeEventListener('load', inicializar);
window.addEventListener('load', inicializarSegunDispositivo);
/* ─── Escalado de cards según espacio disponible ───────────
   Mide el alto real del grid-wrapper y calcula el tamaño
   óptimo para que las dos tiras llenen exactamente ese espacio.
   Se ejecuta en load y en resize.
   ─────────────────────────────────────────────────────────── */
function escalarCards() {
    if (ES_MOVIL()) return;
    const wrapper = document.getElementById('gridWrapper');
    if (!wrapper) return;

    const alto       = wrapper.clientHeight;
    const marginTira = Math.max(10, alto * ESCALA_FACTOR_TIRA);
    const imgH       = Math.floor((alto - ESCALA_MARGEN_INTERNO - ESCALA_NOMBRE_H * 2 - marginTira) / 2 * ESCALA_FACTOR_IMAGEN);
    const imgW       = imgH;
    const cardW      = imgW + ESCALA_PADDING_CARD;

    const root = document.documentElement;
    root.style.setProperty('--card-img-h-real', imgH + 'px');
    root.style.setProperty('--card-img-w-real', imgW + 'px');
    root.style.setProperty('--card-w-real',     cardW + 'px');
    root.style.setProperty('--tira-gap-real',   marginTira + 'px');

    // Mostrar el grid en cuanto los tamaños están calculados — sin esperar que carguen las imágenes
    wrapper.style.visibility = 'visible';
    wrapper.style.opacity    = '1';
    wrapper.style.transition = 'opacity 0.2s ease';
}

// DOMContentLoaded: mostrar el grid y el skeleton apenas el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    requestAnimationFrame(escalarCards);

    // Quitar shimmer de cada imagen cuando termina de cargar
    document.querySelectorAll('.producto-img').forEach(img => {
        if (img.complete && img.naturalWidth > 0) {
            img.classList.add('loaded');
        } else {
            img.addEventListener('load',  () => img.classList.add('loaded'));
            img.addEventListener('error', () => img.classList.add('loaded'));
        }
    });
});

window.addEventListener('load',   escalarCards);
window.addEventListener('resize', escalarCards);

// Mostrar el grid apenas el DOM esté listo (sin esperar imágenes)
document.addEventListener('DOMContentLoaded', () => {
    requestAnimationFrame(() => {
        escalarCards();
    });
});

// Marcar imágenes como cargadas para quitar el skeleton
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.producto-img').forEach(img => {
        if (img.complete && img.naturalWidth > 0) {
            img.classList.add('loaded');
        } else {
            img.addEventListener('load',  () => img.classList.add('loaded'));
            img.addEventListener('error', () => img.classList.add('loaded')); // quitar shimmer aunque falle
        }
    });
});

/* ─── Logos decorativos arrastrables ───────────────────────
   Posiciones iniciales definidas en menu.css (#cellomenu1, #cellomenu2).
   Este bloque solo maneja el drag libre con mouse y touch.
   ─────────────────────────────────────────────────────────── */
function iniciarLogosArrastrables() {
    ['cellomenu1', 'cellomenu2'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;

        let startX, startY, origLeft, origTop;

        function onStart(cx, cy) {
            origLeft = el.offsetLeft;
            origTop  = el.offsetTop;
            startX   = cx;
            startY   = cy;
            el.style.left   = origLeft + 'px';
            el.style.top    = origTop  + 'px';
            el.style.right  = 'auto';
            el.style.bottom = 'auto';
        }

        function onMove(cx, cy) {
            el.style.left = (origLeft + cx - startX) + 'px';
            el.style.top  = (origTop  + cy - startY) + 'px';
        }

        // Mouse
        el.addEventListener('mousedown', e => {
            e.preventDefault();
            onStart(e.clientX, e.clientY);
            const onMv = e => onMove(e.clientX, e.clientY);
            document.addEventListener('mousemove', onMv);
            document.addEventListener('mouseup', () => document.removeEventListener('mousemove', onMv), { once: true });
        });

        // Touch
        el.addEventListener('touchstart', e => {
            const t = e.touches[0];
            onStart(t.clientX, t.clientY);
        }, { passive: true });

        el.addEventListener('touchmove', e => {
            e.preventDefault();
            const t = e.touches[0];
            onMove(t.clientX, t.clientY);
        }, { passive: false });
    });
}

window.addEventListener('load', iniciarLogosArrastrables);

/* ══════════════════════════════════════════════════════════════
   MODAL DETALLE PRODUCTO
   — Abre inmediatamente con datos de rioBase (sin esperar fetch)
   — Enriquece con fotos adicionales de la BD en segundo plano
   ══════════════════════════════════════════════════════════════ */
const modalOverlay   = document.getElementById('modalProducto');
const modalClose     = document.getElementById('prodModalClose');
const modalImgMain   = document.getElementById('prodModalImgMain');
const modalBadge     = document.getElementById('prodModalBadge');
const modalThumbs    = document.getElementById('prodModalThumbs');
const modalCategoria = document.getElementById('prodModalCategoria');
const modalNombre    = document.getElementById('prodModalNombre');
const modalPrecio    = document.getElementById('prodModalPrecio');
const modalTachado   = document.getElementById('prodModalPrecioTachado');
const modalDesc      = document.getElementById('prodModalDesc');
const modalMeta      = document.getElementById('prodModalMeta');

// Índice rápido: id → objeto producto del rioBase
const _rioById = {};
rioBase.forEach(p => { if (p.id) _rioById[p.id] = p; });

function _rellenarModal(p, img) {
    modalImgMain.src = img;
    modalImgMain.alt = p.nombre;

    if (p.etiqueta_oferta) {
        modalBadge.textContent   = p.etiqueta_oferta;
        modalBadge.style.display = 'inline-block';
    } else {
        modalBadge.style.display = 'none';
    }

    modalNombre.textContent    = p.nombre ? p.nombre.toUpperCase() : '';
    modalCategoria.textContent = p.cat_nombre ? p.cat_nombre.toUpperCase() : '';

    if (p.precio_oferta) {
        modalTachado.textContent   = `Bs. ${parseFloat(p.precio).toFixed(2)}`;
        modalTachado.style.display = 'inline';
        modalPrecio.textContent    = `Bs. ${parseFloat(p.precio_oferta).toFixed(2)}`;
        modalPrecio.classList.add('oferta');
    } else {
        modalTachado.style.display = 'none';
        modalPrecio.textContent    = p.precio ? `Bs. ${parseFloat(p.precio).toFixed(2)}` : '';
        modalPrecio.classList.remove('oferta');
    }

    modalDesc.textContent = p.descripcion || '';

    let meta = '';
    if (p.es_combo)  meta += `<span class="prod-meta-tag combo">📦 Combo</span>`;
    if (p.destacado) meta += `<span class="prod-meta-tag destac">⭐ Destacado</span>`;
    if (p.dia_semana && p.dia_semana !== 'Todos') {
        meta += `<span class="prod-meta-tag dia">📅 ${p.dia_semana}</span>`;
    }
    modalMeta.innerHTML = meta;
}

function abrirModalProducto(id) {
    const local = _rioById[id];
    if (local) _rellenarModal(local, local.img);
    else modalNombre.textContent = 'Cargando...';
    modalThumbs.innerHTML      = '';
    modalOverlay.style.display = 'flex';

    fetch(`producto_detalle.php?id=${id}`)
        .then(r => { if (!r.ok) return null; return r.json(); })
        .then(d => {
            if (!d || !d.ok) return;
            const p = d.producto;
            const imgFinal = d.fotos[0]?.ruta_foto || (local ? local.img : '');
            _rellenarModal({
                nombre: p.nombre, cat_nombre: p.cat_nombre,
                precio: p.precio, precio_oferta: p.precio_oferta,
                etiqueta_oferta: p.etiqueta_oferta, descripcion: p.descripcion,
                dia_semana: p.dia_semana, es_combo: p.es_combo, destacado: p.destacado,
            }, imgFinal);

            if (d.fotos.length > 1) {
                modalThumbs.innerHTML = d.fotos.map((f, i) => `
                    <img src="${f.ruta_foto}" alt="${p.nombre}"
                         class="prod-thumb${i === 0 ? ' active' : ''}"
                         onclick="cambiarImgModal(this,'${f.ruta_foto}')">
                `).join('');
            }
        })
        .catch(() => {});
}

function cambiarImgModal(thumb, src) {
    modalImgMain.src = src;
    document.querySelectorAll('.prod-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}

function cerrarModalProducto() {
    modalOverlay.style.display = 'none';
}

modalClose?.addEventListener('click', e => { e.stopPropagation(); cerrarModalProducto(); });
modalOverlay?.addEventListener('click', e => { if (e.target === modalOverlay) cerrarModalProducto(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape' && modalOverlay.style.display === 'flex') cerrarModalProducto(); });
</script>
