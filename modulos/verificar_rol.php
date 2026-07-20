<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Definir qué roles pueden acceder a cada módulo
$permisos = [
    // Módulos que SOLO puede ver ADMIN
    'usuarios' => ['admin'],
    'sucursales' => ['admin'],
    'logs' => ['admin'],
    
    // Módulos que pueden ver ADMIN y EDITOR
    'dashboard' => ['admin', 'editor'],
    'cajas' => ['admin', 'editor'],
    'ventas' => ['admin', 'editor'],
    'productos' => ['admin', 'editor'],
    'categorias' => ['admin', 'editor'],
    'clientes' => ['admin', 'editor'],
    'metodos_pago' => ['admin', 'editor'],
    'reportes' => ['admin', 'editor'],
    'mis_cajas' => ['admin', 'editor'],
    'cerrar_caja' => ['admin', 'editor']
];

$modulo_actual = $_GET['mod'] ?? 'dashboard';

// Verificar si el usuario tiene permiso
if (isset($permisos[$modulo_actual])) {
    if (!in_array($_SESSION['user_rol'], $permisos[$modulo_actual])) {
        echo "<script>
            alert('❌ No tienes permisos para acceder a este módulo.');
            window.location='admin.php?mod=dashboard';
        </script>";
        exit;
    }
}
?>