<?php
include 'config.php'; 

// 1. Obtener sucursales de Bolivia visibles
$stmt = $pdo->prepare("SELECT * FROM sucursales WHERE estado = 1 AND visible = 1 ORDER BY id DESC");
$stmt->execute();
$sucursales_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Obtener fotos de esas sucursales
$ids = array_column($sucursales_db, 'id');
$fotos_db = [];
if (!empty($ids)) {
    $in = implode(',', array_map('intval', $ids));
    $fotos_db = $pdo->query("SELECT * FROM sucursal_fotos WHERE estado = 1 AND sucursal_id IN ($in)")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<title>Sucursales | Pollo El Solar</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="sucursales.css">
<?php include 'navbar.php'; ?>

<div class="contenedor-mapa-global">
    <div id="mapa"></div>

    <div class="overlay-titulo-flotante">
        <h1>NUESTRAS SUCURSALES</h1>
        
    </div>

    <div class="card-lista-flotante">
        <div class="lista-header">
            <h2>SUCURSALES</h2>
            <span><?php echo count($sucursales_db); ?> ubicaciones</span>
        </div>
        <div id="items-sucursales">
            </div>
    </div>

    <div id="card-detalle-solar" class="hidden">
        <button class="btn-cerrar-solar" onclick="cerrarDetalle()">×</button>
        <div class="detalle-img-container">
            <img id="img-sucursal" src="" alt="Fachada">
        </div>
        <div class="detalle-info">
            <h2 id="nombre-sucursal"></h2>
            <div class="dato-grupo">
                <strong>Dirección:</strong>
                <p id="dir-sucursal"></p>
            </div>
            <div class="dato-grupo">
                <strong>Horario:</strong>
                <p id="horario-sucursal"></p>
            </div>
            <a id="btn-maps-link" href="#" target="_blank" class="btn-rojo-solar">ABRIR EN GOOGLE MAPS</a>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    // Pasar datos de PHP a JS
    const sucursales = <?php echo json_encode($sucursales_db); ?>;
    const fotos = <?php echo json_encode($fotos_db); ?>;

    // 1. Inicializar Mapa
    const map = L.map('mapa').setView([-17.7833, -63.1821], 13);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png').addTo(map);

    // 2. Renderizar la Lista Flotante
    function cargarLista() {
        const contenedor = document.getElementById('items-sucursales');
        contenedor.innerHTML = sucursales.map(s => `
            <div class="sucursal-row" id="row-${s.id}" onclick="seleccionarSucursal(${s.id})">
                <i class="fas fa-map-marker-alt" style="margin-right:10px; color:#ce1212"></i>
                ${s.nombre}
            </div>
        `).join('');
    }

    // 3. Función de Selección
    function seleccionarSucursal(id) {
        const s = sucursales.find(item => item.id == id);
        if(!s) return;

        // Marcar fila activa
        document.querySelectorAll('.sucursal-row').forEach(el => el.classList.remove('active'));
        const row = document.getElementById(`row-${id}`);
        if(row) row.classList.add('active');

        // Mover mapa
        map.flyTo([s.latitud, s.longitud], 16);

        // --- CARGAR FOTO (CORREGIDO) ---
        // Cargar datos en la card de detalle
        const fotoObj = fotos.find(f => f.sucursal_id == s.id);
        const imgElement = document.getElementById('img-sucursal');

        if (fotoObj) {
            // RUTA DIRECTA: No lleva '../' ni 'admin/'
            // Usamos encodeURI por la Ñ de CAÑOTO
            imgElement.src = encodeURI(fotoObj.ruta_foto); 
        } else {
            imgElement.src = 'img/default.jpg'; // O la ruta de tu imagen por defecto
        }

        // --- CARGAR DATOS ---
        document.getElementById('nombre-sucursal').innerText = s.nombre.toUpperCase();
        document.getElementById('dir-sucursal').innerText = s.direccion;
        document.getElementById('horario-sucursal').innerText = s.hora_apertura + ' - ' + s.hora_cierre;
        
        // Corregido el link de Google Maps que también tenía un error de sintaxis
        document.getElementById('btn-maps-link').href = `https://www.google.com/maps?q=${s.latitud},${s.longitud}`;

        document.getElementById('card-detalle-solar').classList.remove('hidden');
        //document.getElementById('card-detalle-solar').scrollTop = 0;
    }
    function cerrarDetalle() {
        document.getElementById('card-detalle-solar').classList.add('hidden');
        document.querySelectorAll('.sucursal-row').forEach(el => el.classList.remove('active'));
    }
    // 4. Configurar el Icono Personalizado (El Pollito)
    const iconoPollito = L.icon({
        iconUrl: '../fotos/logito.png', // Asegúrate que esta ruta sea la correcta
        iconSize: [40, 40],             // Tamaño del icono [ancho, alto]
        iconAnchor: [20, 40],           // Punto del icono que corresponde a la ubicación del marcador (mitad ancho, base alto)
        popupAnchor: [0, -40]           // Punto desde donde se abriría un popup si lo usaras
    });

    // 5. Agregar Marcadores al Mapa con el nuevo icono
    sucursales.forEach(s => {
        if(s.latitud && s.longitud) {
            // Añadimos { icon: iconoPollito } aquí:
            const marker = L.marker([s.latitud, s.longitud], { icon: iconoPollito }).addTo(map);
            
            // Al hacer click en el pollito, se abre la card de detalle
            marker.on('click', () => seleccionarSucursal(s.id));
        }
    });

    // Iniciar
    cargarLista();
</script>

