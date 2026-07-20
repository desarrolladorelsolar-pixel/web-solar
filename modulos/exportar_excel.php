<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar cualquier salida previa
ob_clean();

global $pdo;
require_once '../config.php';

// Obtener filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$sucursal_id = $_GET['sucursal_id'] ?? '';
$usuario_id = $_GET['usuario_id'] ?? '';
$cliente_id = $_GET['cliente_id'] ?? '';
$producto_id = $_GET['producto_id'] ?? '';
$estado_venta = $_GET['estado_venta'] ?? '';

// Construir consulta
$sql = "
    SELECT 
        v.fecha_venta,
        v.id AS nro_venta,
        CASE 
            WHEN v.estado = 1 THEN 'ACTIVA'
            WHEN v.estado = 0 THEN 'ANULADA'
            ELSE 'DESCONOCIDO'
        END AS estado_venta,
        s.nombre AS sucursal,
        u.nombre AS usuario,
        IFNULL(c.nombre, 'Cliente Ocasional') AS cliente,
        p.id AS sku,
        p.nombre AS nombre_producto,
        vd.cantidad,
        vd.precio_unitario,
        vd.descuento_linea,
        vd.subtotal_linea,
        v.moneda
    FROM ventas v
    INNER JOIN venta_detalle vd ON v.id = vd.venta_id
    INNER JOIN productos p ON vd.producto_id = p.id
    LEFT JOIN sucursales s ON v.sucursal_id = s.id
    LEFT JOIN usuarios u ON v.usuario_id = u.id
    LEFT JOIN clientes c ON v.cliente_id = c.id
    WHERE DATE(v.fecha_venta) BETWEEN :fecha_inicio AND :fecha_fin
      AND v.sucursal_id = :sucursal_id
";

$params = [
    ':fecha_inicio' => $fecha_inicio,
    ':fecha_fin' => $fecha_fin,
    ':sucursal_id' => $sucursal_id
];

if (!empty($usuario_id)) {
    $sql .= " AND v.usuario_id = :usuario_id";
    $params[':usuario_id'] = $usuario_id;
}

if (!empty($cliente_id)) {
    $sql .= " AND v.cliente_id = :cliente_id";
    $params[':cliente_id'] = $cliente_id;
}

if (!empty($producto_id)) {
    $sql .= " AND vd.producto_id = :producto_id";
    $params[':producto_id'] = $producto_id;
}

if ($estado_venta !== '') {
    $sql .= " AND v.estado = :estado_venta";
    $params[':estado_venta'] = $estado_venta;
}

$sql .= " ORDER BY v.fecha_venta DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll();

// Headers para Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="reporte_detalle_' . date('Y-m-d_H-i') . '.xls"');
header('Cache-Control: max-age=0');
header('Pragma: public');

echo '<table border="1">';
echo '<thead>';
echo '<tr>';
echo '<th>Fecha Venta</th>';
echo '<th>N° Venta</th>';
echo '<th>Estado</th>';
echo '<th>Sucursal</th>';
echo '<th>Vendedor</th>';
echo '<th>Cliente</th>';
echo '<th>SKU</th>';
echo '<th>Producto</th>';
echo '<th>Cantidad</th>';
echo '<th>Precio Unitario</th>';
echo '<th>Descuento Línea</th>';
echo '<th>Subtotal Línea</th>';
echo '<th>Moneda</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($ventas as $v) {
    echo '<tr>';
    echo '<td>' . date('d/m/Y H:i', strtotime($v['fecha_venta'])) . '</td>';
    echo '<td>' . str_pad($v['nro_venta'], 6, '0', STR_PAD_LEFT) . '</td>';
    echo '<td>' . $v['estado_venta'] . '</td>';
    echo '<td>' . htmlspecialchars($v['sucursal'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($v['usuario'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($v['cliente']) . '</td>';
    echo '<td>' . $v['sku'] . '</td>';
    echo '<td>' . htmlspecialchars($v['nombre_producto']) . '</td>';
    echo '<td>' . $v['cantidad'] . '</td>';
    echo '<td>' . number_format($v['precio_unitario'], 2) . '</td>';
    echo '<td>' . number_format($v['descuento_linea'], 2) . '</td>';
    echo '<td>' . number_format($v['subtotal_linea'], 2) . '</td>';
    echo '<td>' . $v['moneda'] . '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';
exit;
?>