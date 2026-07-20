<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// COPIAR Y PEGAR EXACTAMENTE LA MISMA LÓGICA DE FILTROS DEL ARCHIVO ANTERIOR
$sql = "SELECT v.fecha_venta, v.id AS nro_venta, v.estado AS estado_venta, 
               s.nombre AS sucursal, u.nombre AS usuario, IFNULL(c.nombre, 'Ocasional') AS cliente, 
               p.id AS sku, p.nombre AS nombre_producto, vd.cantidad, vd.precio_unitario, 
               vd.subtotal_linea, v.moneda 
        FROM ventas v
        INNER JOIN venta_detalle vd ON v.id = vd.venta_id
        INNER JOIN productos p ON vd.producto_id = p.id
        LEFT JOIN sucursales s ON v.sucursal_id = s.id
        LEFT JOIN usuarios u ON v.usuario_id = u.id
        LEFT JOIN clientes c ON v.cliente_id = c.id
        WHERE 1=1";
        
$params = [];
if (!empty($_GET['fecha_inicio']) && !empty($_GET['fecha_fin'])) {
    $sql .= " AND DATE(v.fecha_venta) BETWEEN ? AND ?";
    $params[] = $_GET['fecha_inicio'];
    $params[] = $_GET['fecha_fin'];
}
if (!empty($_GET['sucursal_id'])) {
    $sql .= " AND v.sucursal_id = ?";
    $params[] = $_GET['sucursal_id'];
}
if (isset($_GET['estado_venta']) && $_GET['estado_venta'] !== '') {
    $sql .= " AND v.estado = ?";
    $params[] = $_GET['estado_venta'];
}
$sql .= " ORDER BY v.fecha_venta DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CONFIGURACIÓN DE EXCEL
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Reporte_Ventas_Detalle_" . date('Ymd_Hi') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// IMPRIMIR LA TABLA
echo "<table border='1'>";
echo "<tr>
        <th style='background-color: #212529; color: white;'>Fecha</th>
        <th style='background-color: #212529; color: white;'>Nro Venta</th>
        <th style='background-color: #212529; color: white;'>Estado</th>
        <th style='background-color: #212529; color: white;'>Sucursal</th>
        <th style='background-color: #212529; color: white;'>Usuario</th>
        <th style='background-color: #212529; color: white;'>Cliente</th>
        <th style='background-color: #212529; color: white;'>SKU</th>
        <th style='background-color: #212529; color: white;'>Producto</th>
        <th style='background-color: #212529; color: white;'>Cant.</th>
        <th style='background-color: #212529; color: white;'>Total</th>
      </tr>";

foreach ($resultados as $row) {
    $estado_txt = ($row['estado_venta'] == 1) ? 'Activa' : 'Anulada';
    $color_fila = ($row['estado_venta'] == 0) ? "style='background-color: #f8d7da;'" : "";

    echo "<tr $color_fila>";
    echo "<td>" . $row['fecha_venta'] . "</td>";
    echo "<td>" . $row['nro_venta'] . "</td>";
    echo "<td>" . $estado_txt . "</td>";
    echo "<td>" . $row['sucursal'] . "</td>";
    echo "<td>" . $row['usuario'] . "</td>";
    echo "<td>" . $row['cliente'] . "</td>";
    echo "<td>" . $row['sku'] . "</td>";
    echo "<td>" . $row['nombre_producto'] . "</td>";
    echo "<td>" . $row['cantidad'] . "</td>";
    echo "<td>" . $row['subtotal_linea'] . " " . $row['moneda'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>