<?php
/**
 * producto_detalle.php — Endpoint AJAX
 * Devuelve JSON con datos completos de un producto y sus fotos.
 * Usado por el modal de detalle en menu.php (Bolivia).
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

// ── Datos del producto ──────────────────────────────────────
$stmt = $pdo->prepare("
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
        p.destacado,
        c.nombre AS cat_nombre
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
    WHERE p.id = ? AND p.estado = 1
    LIMIT 1
");
$stmt->execute([$id]);
$producto = $stmt->fetch();

if (!$producto) {
    echo json_encode(['ok' => false, 'error' => 'Producto no encontrado']);
    exit;
}

// ── Todas las fotos activas del producto ────────────────────
$stmtF = $pdo->prepare("
    SELECT ruta_foto, orden
    FROM producto_fotos
    WHERE producto_id = ? AND estado = 1
    ORDER BY orden ASC
");
$stmtF->execute([$id]);
$fotos = $stmtF->fetchAll();

if (empty($fotos)) {
    $fotos = [['ruta_foto' => '', 'orden' => 0]];
}

echo json_encode([
    'ok'       => true,
    'producto' => $producto,
    'fotos'    => $fotos,
]);
