<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar buffers
while (ob_get_level()) ob_end_clean();

global $pdo;
require_once '../config.php';

// Obtener filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$sucursal_id = $_GET['sucursal_id'] ?? '';
$metodo_pago_id = $_GET['metodo_pago_id'] ?? '';
$usuario_id = $_GET['usuario_id'] ?? '';
$cliente_id = $_GET['cliente_id'] ?? '';

// Construir consulta
$sql = "
    SELECT 
        v.id,
        v.fecha_venta,
        v.total,
        v.descuento,
        s.nombre as sucursal_nombre,
        u.nombre as usuario_nombre,
        IFNULL(c.nombre, 'Cliente Ocasional') as cliente_nombre,
        GROUP_CONCAT(DISTINCT mp.nombre SEPARATOR ', ') as metodos_pago,
        (SELECT COUNT(*) FROM venta_detalle WHERE venta_id = v.id) as total_productos,
        (SELECT SUM(cantidad) FROM venta_detalle WHERE venta_id = v.id) as total_unidades
    FROM ventas v
    LEFT JOIN sucursales s ON v.sucursal_id = s.id
    LEFT JOIN usuarios u ON v.usuario_id = u.id
    LEFT JOIN clientes c ON v.cliente_id = c.id
    LEFT JOIN venta_pagos vp ON v.id = vp.venta_id
    LEFT JOIN metodos_pago mp ON vp.metodo_pago_id = mp.id
    WHERE DATE(v.fecha_venta) BETWEEN :fecha_inicio AND :fecha_fin
";

$params = [
    ':fecha_inicio' => $fecha_inicio,
    ':fecha_fin' => $fecha_fin
];

if(!empty($sucursal_id)) {
    $sql .= " AND v.sucursal_id = :sucursal_id";
    $params[':sucursal_id'] = $sucursal_id;
}

if(!empty($usuario_id)) {
    $sql .= " AND v.usuario_id = :usuario_id";
    $params[':usuario_id'] = $usuario_id;
}

if(!empty($cliente_id)) {
    $sql .= " AND v.cliente_id = :cliente_id";
    $params[':cliente_id'] = $cliente_id;
}

if(!empty($metodo_pago_id)) {
    $sql .= " AND vp.metodo_pago_id = :metodo_pago_id";
    $params[':metodo_pago_id'] = $metodo_pago_id;
}

$sql .= " GROUP BY v.id ORDER BY v.fecha_venta DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll();

// Headers para Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="reporte_ventas_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');
header('Pragma: public');

// Generar Excel
echo '<table border="1">';
echo '<thead>';
echo '<tr>';
echo '<th>N° Venta</th>';
echo '<th>Fecha</th>';
echo '<th>Sucursal</th>';
echo '<th>Vendedor</th>';
echo '<th>Cliente</th>';
echo '<th>Método Pago</th>';
echo '<th>Productos</th>';
echo '<th>Unidades</th>';
echo '<th>Descuento</th>';
echo '<th>Total</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($ventas as $v) {
    echo '<tr>';
    echo '<td>' . str_pad($v['id'], 6, '0', STR_PAD_LEFT) . '</td>';
    echo '<td>' . date('d/m/Y H:i', strtotime($v['fecha_venta'])) . '</td>';
    echo '<td>' . htmlspecialchars($v['sucursal_nombre']) . '</td>';
    echo '<td>' . htmlspecialchars($v['usuario_nombre']) . '</td>';
    echo '<td>' . htmlspecialchars($v['cliente_nombre']) . '</td>';
    echo '<td>' . htmlspecialchars($v['metodos_pago']) . '</td>';
    echo '<td>' . $v['total_productos'] . '</td>';
    echo '<td>' . $v['total_unidades'] . '</td>';
    echo '<td>' . ($v['descuento'] > 0 ? $v['descuento'] . '%' : '-') . '</td>';
    echo '<td>' . number_format($v['total'], 2) . ' BOB</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';
exit;
?>