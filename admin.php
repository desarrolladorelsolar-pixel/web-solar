<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Restaurar caja abierta si existe
restaurarCajaAbierta($pdo, $_SESSION['user_id']);

// Obtener rol del usuario
$user_rol = $_SESSION['user_rol'] ?? '';
$user_nombre = $_SESSION['user_nombre'] ?? 'Usuario';

// Menú según rol (GENERADO DINÁMICAMENTE)
$menu_items = [];

if($user_rol == 'admin') {
    $menu_items = [
        ['mod' => 'dashboard', 'icono' => '📊', 'nombre' => 'Dashboard'],
        ['mod' => 'reporte_ventas', 'icono' => '📊', 'nombre' => 'Reporte Ventas'],
        ['mod' => 'reporte_detalle', 'icono' => '📋', 'nombre' => 'Reporte Detalle'],
        ['mod' => 'reporte_por_producto', 'icono' => '🍗', 'nombre' => 'Ventas por Producto'],
        ['mod' => 'cajas', 'icono' => '💰', 'nombre' => 'Panel Cajas'],
        ['mod' => 'ventas', 'icono' => '🛒', 'nombre' => 'Ventas'],
        ['mod' => 'productos', 'icono' => '🍗', 'nombre' => 'Productos'],
        ['mod' => 'categorias', 'icono' => '📂', 'nombre' => 'Categorías'],
        ['mod' => 'popups', 'icono' => '👨‍💼', 'nombre' => 'Pop-ups'],
        ['mod' => 'clientes', 'icono' => '👥', 'nombre' => 'Clientes'],
        ['mod' => 'cupones', 'icono' => '📝', 'nombre' => 'Cupones'],
        ['mod' => 'cupones_canjeados', 'icono' => '📝', 'nombre' => 'Cupones Cajeados'],
        ['mod' => 'reporte_canjes', 'icono' => '📋', 'nombre' => 'Reporte Canjes'],
        ['mod' => 'metodos_pago', 'icono' => '💳', 'nombre' => 'Métodos de Pago'],
        ['mod' => 'sucursales', 'icono' => '📍', 'nombre' => 'Sucursales'],
        ['mod' => 'usuarios', 'icono' => '👤', 'nombre' => 'Usuarios'],
        ['mod' => 'logs', 'icono' => '📝', 'nombre' => 'Logs'],
        
    ];
} else {
    // EDITOR (vendedor) - SOLO ve lo necesario
    $menu_items = [
        ['mod' => 'ventas', 'icono' => '🛒', 'nombre' => 'Ventas'],
        ['mod' => 'clientes', 'icono' => '👥', 'nombre' => 'Clientes'],
        ['mod' => 'mis_cajas', 'icono' => '📋', 'nombre' => 'Cajas Disponibles'],
        ['mod' => 'cupones_canjeados', 'icono' => '📝', 'nombre' => 'Cupones Cajeados'],
    ];
}

// Cargar el módulo solicitado
$modulo = $_GET['mod'] ?? 'mis_cajas';  // Por defecto va a cajas (no dashboard)
$archivo_modulo = "modulos/" . $modulo . ".php";

// Verificar permisos - EDITOR solo puede acceder a estos módulos
$permisos_modulos = [
    // Solo ADMIN
    'dashboard' => ['admin'],
    'reporte_ventas' => ['admin'],
    'reporte_canjes' => ['admin'],
    'productos' => ['admin'],
    'categorias' => ['admin'],
    'metodos_pago' => ['admin'],
    'sucursales' => ['admin'],
    'usuarios' => ['admin'],
    'logs' => ['admin'],
    'cajas' => ['admin'],
    'reporte_detalle' => ['admin', 'editor'],
    'cupones' => ['admin', 'editor'],
    'cupones_canjeados' => ['admin', 'editor'],
    // ADMIN y EDITOR
    'clientes' => ['admin','editor'],
    'ventas' => ['admin', 'editor'],
    'mis_cajas' => ['admin', 'editor'],
    'cerrar_caja' => ['admin', 'editor']
];

// Si el editor intenta entrar a un módulo no permitido, redirigir a cajas
if (isset($permisos_modulos[$modulo])) {
    if (!in_array($user_rol, $permisos_modulos[$modulo])) {
        echo "<script>alert('❌ No tienes permisos para acceder a este módulo.'); window.location='admin.php?mod=cajas';</script>";
        exit;
    }
} else {
    // Módulo no definido en permisos -> solo admin
    if ($user_rol != 'admin') {
        echo "<script>alert('❌ No tienes permisos.'); window.location='admin.php?mod=cajas';</script>";
        exit;
    }
}

// Si el archivo del módulo no existe, mostrar error
if (!file_exists($archivo_modulo)) {
    if ($user_rol == 'admin') {
        $archivo_modulo = "modulos/dashboard.php";
    } else {
        $archivo_modulo = "modulos/mis_cajas.php";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Admin | Pollo El Solar</title>
    <style>
        :root { --sidebar-width: 250px; --gold: #D4AF37; --dark: #1a1a1a; }
        body { margin: 0; display: flex; font-family: 'Segoe UI', sans-serif; background: #f4f4f4; color: #333; }
        
        /* Sidebar */
        .sidebar { width: var(--sidebar-width); background: var(--dark); height: 100vh; color: #fff; position: fixed; box-shadow: 2px 0 5px rgba(0,0,0,0.1); transition: transform 0.3s ease; z-index: 1000; overflow-y: auto; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid #333; }
        .sidebar-header img { display: block; margin: 0 auto 15px; width: 120px; max-width: 80%; height: auto; }
        .sidebar-header h3 { color: var(--gold); margin: 0; font-size: 1.1em; }
        .sidebar-header p { color: #888; font-size: 11px; margin-top: 5px; }
        .sidebar a { display: block; color: #bbb; padding: 12px 20px; text-decoration: none; transition: 0.3s; font-size: 0.9em; }
        .sidebar a:hover { background: #333; color: var(--gold); padding-left: 30px; }
        .sidebar a.active { background: #222; color: #fff; border-left: 4px solid var(--gold); }
        .logout-link { margin-top: 30px; border-top: 1px solid #333; color: #ff6b6b !important; }
        
        /* Botón menú hamburguesa */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: var(--gold);
            color: #000;
            border: none;
            font-size: 24px;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        /* Contenido Principal */
        .main-content { margin-left: var(--sidebar-width); padding: 20px; width: calc(100% - var(--sidebar-width)); min-height: 100vh; box-sizing: border-box; transition: margin-left 0.3s ease; }
        
        /* Top Bar */
        .top-bar { background: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .user-info { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 40px; height: 40px; background: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #000; }
        
        /* Tarjeta Blanca */
        .table-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        th { background: #fcfcfc; color: #666; text-transform: uppercase; font-size: 0.75em; }
        tr:hover { background: #f9f9f9; }
        
        /* ===== MEDIA QUERIES ===== */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .menu-toggle { display: block; }
            .main-content { margin-left: 0; padding: 15px; padding-top: 70px; width: 100%; }
            .table-card { padding: 15px; }
            th, td { padding: 8px; font-size: 0.85em; }
            table { min-width: 500px; }
        }
        
        @media (max-width: 480px) {
            .main-content { padding: 10px; padding-top: 65px; }
            .table-card { padding: 10px; }
            .top-bar { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<button class="menu-toggle" id="menuToggle">☰</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="../fotos/logito.png" alt="logo solar">
        <h3>🌞 POLLO EL SOLAR</h3>
        <p><?php echo ucfirst($user_rol); ?></p>
    </div>
    
    <?php foreach($menu_items as $item): ?>
    <a href="?mod=<?php echo $item['mod']; ?>" class="<?php echo ($modulo == $item['mod']) ? 'active' : ''; ?>">
        <?php echo $item['icono']; ?> <?php echo $item['nombre']; ?>
    </a>
    <?php endforeach; ?>
    
    <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
</div>

<div class="main-content">
    <div class="top-bar">
        <div class="user-info">
            <div class="user-avatar"><?php echo strtoupper(substr($user_nombre, 0, 1)); ?></div>
            <div>
                <strong><?php echo htmlspecialchars($user_nombre); ?></strong><br>
                <small style="color:#888;"><?php echo $user_rol == 'admin' ? 'Administrador' : 'Vendedor'; ?></small>
            </div>
        </div>
        <?php if(isset($_SESSION['apertura_caja_id'])): ?>
        <span style="background:#28a745; color:white; padding:5px 12px; border-radius:20px; font-size:12px;">💰 Caja Abierta</span>
        <?php endif; ?>
    </div>
    
    <div class="table-card">
        <?php
        $titulo_modulo = [
            'cajas' => 'Panel de Cajas',
            'ventas' => 'Registro de Ventas',
            'mis_cajas' => 'Mis Cajas',
            'dashboard' => 'Dashboard'
        ];
        $titulo = $titulo_modulo[$modulo] ?? ucfirst($modulo);
        echo "<h2 style='margin-top:0;'>" . $titulo . "</h2>";
        echo "<hr style='border:0; border-top:1px solid #eee; margin-bottom:20px;'>";
        include $archivo_modulo;
        ?>
    </div>
</div>

<script>
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('open');
    });
    
    const links = sidebar.querySelectorAll('a');
    links.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('open');
            }
        });
    });
    
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('open');
        }
    });
</script>

</body>
</html>