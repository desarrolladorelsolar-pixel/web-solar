<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$producto_id = $_GET['producto_id'] ?? '';

$sql = "
    SELECT 
        p.id,
        p.nombre as producto,
        c.nombre as categoria,
        SUM(vd.cantidad) as unidades_vendidas,
        COUNT(DISTINCT vd.venta_id) as numero_ventas,
        SUM(vd.subtotal_linea) as total_recaudado,
        AVG(vd.precio_unitario) as precio_promedio
    FROM venta_detalle vd
    JOIN productos p ON vd.producto_id = p.id
    JOIN categorias c ON p.categoria_id = c.id
    JOIN ventas v ON vd.venta_id = v.id
    WHERE DATE(v.fecha_venta) BETWEEN :fecha_inicio AND :fecha_fin
      AND v.estado = 1
";

$params = [':fecha_inicio' => $fecha_inicio, ':fecha_fin' => $fecha_fin];

if(!empty($producto_id)) {
    $sql .= " AND vd.producto_id = :producto_id";
    $params[':producto_id'] = $producto_id;
}

$sql .= " GROUP BY vd.producto_id ORDER BY unidades_vendidas DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

if(isset($_GET['exportar_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte_productos_' . date('Y-m-d') . '.xls"');
    echo "<table border='1'><tr><th>Producto</th><th>Categoría</th><th>Unidades</th><th>Ventas</th><th>Total Recaudado</th><th>Precio Promedio</th></tr>";
    foreach($productos as $p) {
        echo "<tr><td>{$p['producto']}</td><td>{$p['categoria']}</td><td>{$p['unidades_vendidas']}</td><td>{$p['numero_ventas']}</td><td>{$p['total_recaudado']}</td><td>" . number_format($p['precio_promedio'], 2) . "</td></tr>";
    }
    echo "</table>";
    exit;
}

$productos_lista = $pdo->query("SELECT id, nombre FROM productos WHERE estado = 1 ORDER BY nombre")->fetchAll();
?>

<style>
    .filtros { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
    .filtros-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
    .btn-excel { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
    table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; }
    th { background: #1a1a1a; color: #D4AF37; padding: 12px; text-align: left; }
    td { padding: 10px 12px; border-bottom: 1px solid #eee; }
</style>

<h2>📊 Reporte de Ventas por Producto</h2>

<div class="filtros">
    <form method="GET" class="filtros-grid">
        <input type="hidden" name="mod" value="reporte_por_producto">
        <div><label>📅 Fecha Inicio</label><input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>"></div>
        <div><label>📅 Fecha Fin</label><input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>"></div>
        <div><label>🍗 Producto</label><select name="producto_id"><option value="">Todos</option><?php foreach($productos_lista as $p) echo "<option value='{$p['id']}' " . ($producto_id == $p['id'] ? 'selected' : '') . ">{$p['nombre']}</option>"; ?></select></div>
        <div><button type="submit" style="background:#D4AF37; border:none; padding:8px 20px; border-radius:5px;">🔍 FILTRAR</button></div>
        <div><button type="button" class="btn-excel" onclick="exportarExcel()">📊 EXPORTAR EXCEL</button></div>
    </form>
</div>

<table>
    <thead><tr><th>Producto</th><th>Categoría</th><th>Unidades Vendidas</th><th>N° Ventas</th><th>Total Recaudado</th><th>Precio Promedio</th></tr></thead>
    <tbody>
        <?php foreach($productos as $p): ?>
        <tr>
            <td><?php echo htmlspecialchars($p['producto']); ?></td>
            <td><?php echo $p['categoria']; ?></td>
            <td><strong><?php echo $p['unidades_vendidas']; ?></strong></td>
            <td><?php echo $p['numero_ventas']; ?></td>
            <td><?php echo number_format($p['total_recaudado'], 2); ?> BOB</td>
            <td><?php echo number_format($p['precio_promedio'], 2); ?> BOB</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
function exportarExcel() {
    let url = window.location.href;
    url += (url.indexOf('?') === -1 ? '?' : '&') + 'exportar_excel=1';
    window.location.href = url;
}
</script>