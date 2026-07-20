<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;

// Obtener filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$sucursal_id = $_GET['sucursal_id'] ?? '';
$usuario_id = $_GET['usuario_id'] ?? '';
$cliente_id = $_GET['cliente_id'] ?? '';
$producto_id = $_GET['producto_id'] ?? '';
$estado_venta = $_GET['estado_venta'] ?? '';

// Verificar si se aplicaron filtros
$filtros_aplicados = !empty($fecha_inicio) && !empty($fecha_fin) && !empty($sucursal_id);
$ventas = [];

if ($filtros_aplicados) {
    // DEBUG: Verificar si hay ventas en el rango
    $check_sql = "SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha_venta) BETWEEN :fecha_inicio AND :fecha_fin AND sucursal_id = :sucursal_id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([
        ':fecha_inicio' => $fecha_inicio,
        ':fecha_fin' => $fecha_fin,
        ':sucursal_id' => $sucursal_id
    ]);
    $total_ventas_check = $check_stmt->fetch()['total'];
    
    // Si no hay ventas, mostrar mensaje
    if ($total_ventas_check == 0) {
        $sin_ventas = true;
    } else {
        $sin_ventas = false;
        
        // Construir consulta principal
        $sql = "
            SELECT 
                v.fecha_venta,
                v.id AS nro_venta,
                CASE 
                    WHEN v.estado = 1 THEN 'ACTIVA'
                    WHEN v.estado = 0 THEN 'ANULADA'
                    ELSE 'DESCONOCIDO'
                END AS estado_venta,
                v.estado as estado_numero,
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
    }
}

// Exportar Excel
if (isset($_GET['exportar_excel']) && $filtros_aplicados && count($ventas) > 0) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte_detalle_' . date('Y-m-d_H-i') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<table border="1">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Fecha Venta</th>';
    echo '<th>N° Venta</th>';
    echo '<th>Estado</th>';
    echo '<th>Sucursal</th>';
    echo '<th>Usuario/Vendedor</th>';
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
}

// Datos para filtros
$sucursales = $pdo->query("SELECT id, nombre FROM sucursales WHERE estado = 1 ORDER BY nombre")->fetchAll();
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios WHERE estado = 1 ORDER BY nombre")->fetchAll();
$clientes = $pdo->query("SELECT id, nombre FROM clientes WHERE estado = 1 ORDER BY nombre LIMIT 100")->fetchAll();
$productos = $pdo->query("SELECT id, nombre FROM productos WHERE estado = 1 ORDER BY nombre LIMIT 100")->fetchAll();

// Totales
$total_registros = count($ventas);
$total_recaudado = array_sum(array_column($ventas, 'subtotal_linea'));
$total_unidades = array_sum(array_column($ventas, 'cantidad'));
$total_descuento = array_sum(array_column($ventas, 'descuento_linea'));
?>

<style>
    .filtros-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 25px;
    }
    .filtros-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        align-items: end;
    }
    .filtro-group {
        display: flex;
        flex-direction: column;
    }
    .filtro-group label {
        font-size: 12px;
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
    }
    .filtro-group label.required {
        color: #c62828;
    }
    .filtro-group label.required::after {
        content: " *";
    }
    .filtro-group input,
    .filtro-group select {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
    }
    .btn-filtrar {
        background: #D4AF37;
        color: #000;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
    }
    .btn-excel {
        background: #28a745;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
    }
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .stat-card .number {
        font-size: 28px;
        font-weight: bold;
        color: #D4AF37;
    }
    .stat-card .label {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }
    .table-responsive {
        overflow-x: auto;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
    }
    th {
        background: #1a1a1a;
        color: #D4AF37;
        padding: 12px;
        text-align: left;
        font-size: 12px;
    }
    td {
        padding: 10px 12px;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    tr:hover {
        background: #f8f9fa;
    }
    .badge-activo {
        background: #e8f5e9;
        color: #1b5e20;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        display: inline-block;
    }
    .badge-anulado {
        background: #ffebee;
        color: #c62828;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        display: inline-block;
    }
    .alerta-filtros {
        background: #fff3cd;
        border: 1px solid #ffeeba;
        color: #856404;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        margin: 20px 0;
    }
    .alerta-error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }
    @media (max-width: 768px) {
        .filtros-grid { grid-template-columns: 1fr; }
        .stats-cards { grid-template-columns: 1fr; }
    }
</style>

<div style="margin-bottom: 25px;">
    <h2 style="margin:0 0 5px 0;">📊 Reporte de Ventas Detallado</h2>
    <p style="color:#666; margin:0;">Consulta detallada por producto - Filtros obligatorios: Fecha y Sucursal</p>
</div>

<!-- Filtros -->
<div class="filtros-section">
    <form method="GET" id="filtrosForm">
        <input type="hidden" name="mod" value="reporte_detalle">
        <div class="filtros-grid">
            <div class="filtro-group">
                <label class="required">📅 Fecha Inicio</label>
                <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" required>
            </div>
            <div class="filtro-group">
                <label class="required">📅 Fecha Fin</label>
                <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>" required>
            </div>
            <div class="filtro-group">
                <label class="required">🏢 Sucursal</label>
                <select name="sucursal_id" required>
                    <option value="">-- Seleccionar --</option>
                    <?php foreach($sucursales as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $sucursal_id == $s['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filtro-group">
                <label>👤 Usuario</label>
                <select name="usuario_id">
                    <option value="">Todos</option>
                    <?php foreach($usuarios as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $usuario_id == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filtro-group">
                <label>👥 Cliente</label>
                <select name="cliente_id">
                    <option value="">Todos</option>
                    <?php foreach($clientes as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $cliente_id == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filtro-group">
                <label>🍗 Producto</label>
                <select name="producto_id">
                    <option value="">Todos</option>
                    <?php foreach($productos as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo $producto_id == $p['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filtro-group">
                <label>📌 Estado</label>
                <select name="estado_venta">
                    <option value="">Todos</option>
                    <option value="1" <?php echo $estado_venta === '1' ? 'selected' : ''; ?>>Activa</option>
                    <option value="0" <?php echo $estado_venta === '0' ? 'selected' : ''; ?>>Anulada</option>
                </select>
            </div>
            <div class="filtro-group">
                <button type="submit" class="btn-filtrar">🔍 FILTRAR</button>
            </div>
        </div>
    </form>
</div>

<?php if (!$filtros_aplicados): ?>
    <div class="alerta-filtros">
        <span style="font-size: 48px;">🔍</span>
        <h3>Aplica filtros para ver los resultados</h3>
        <p>Selecciona una fecha de inicio, fecha fin y una sucursal para generar el reporte.</p>
    </div>
<?php elseif (isset($sin_ventas) && $sin_ventas): ?>
    <div class="alerta-filtros alerta-error">
        <span style="font-size: 48px;">⚠️</span>
        <h3>No hay ventas en el rango seleccionado</h3>
        <p>Fecha: <?php echo $fecha_inicio; ?> al <?php echo $fecha_fin; ?><br>
        Sucursal ID: <?php echo $sucursal_id; ?><br>
        <strong>Verifica que existan ventas registradas en estas fechas.</strong></p>
    </div>
<?php elseif (count($ventas) === 0): ?>
    <div class="alerta-filtros">
        <span style="font-size: 48px;">📭</span>
        <h3>No hay datos para los filtros seleccionados</h3>
        <p>Prueba con otros rangos de fecha o sucursales diferentes.</p>
    </div>
<?php else: ?>
    <!-- Estadísticas -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="number"><?php echo $total_registros; ?></div>
            <div class="label">Registros (líneas)</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo number_format($total_unidades, 0); ?></div>
            <div class="label">Unidades Vendidas</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo number_format($total_descuento, 2); ?> BOB</div>
            <div class="label">Total Descuentos</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo number_format($total_recaudado, 2); ?> BOB</div>
            <div class="label">Total Recaudado</div>
        </div>
    </div>

    <!-- Botón Exportar -->
    <div style="text-align: right; margin-bottom: 15px;">
        <button type="button" class="btn-excel" onclick="exportarExcel()">📊 EXPORTAR A EXCEL</button>
    </div>

    <!-- Tabla -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>N° Venta</th>
                    <th>Estado</th>
                    <th>Sucursal</th>
                    <th>Vendedor</th>
                    <th>Cliente</th>
                    <th>SKU</th>
                    <th>Producto</th>
                    <th>Cant</th>
                    <th>P.Unit</th>
                    <th>Dto.Línea</th>
                    <th>Subtotal</th>
                    <th>Moneda</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($ventas as $v): ?>
                    <tr>
                        <td style="white-space: nowrap;"><?php echo date('d/m/Y H:i', strtotime($v['fecha_venta'])); ?></td>
                        <td>#<?php echo str_pad($v['nro_venta'], 6, '0', STR_PAD_LEFT); ?></td>
                        <td>
                            <?php if($v['estado_numero'] == 1): ?>
                                <span class="badge-activo">✓ ACTIVA</span>
                            <?php else: ?>
                                <span class="badge-anulado">✗ ANULADA</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($v['sucursal'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($v['usuario'] ?? ''); ?></td>
                        <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($v['cliente']); ?>">
                            <?php echo htmlspecialchars($v['cliente']); ?>
                        </td>
                        <td><?php echo $v['sku']; ?></td>
                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($v['nombre_producto']); ?>">
                            <?php echo htmlspecialchars($v['nombre_producto']); ?>
                        </td>
                        <td style="text-align:center;"><?php echo $v['cantidad']; ?></td>
                        <td style="text-align:right;"><?php echo number_format($v['precio_unitario'], 2); ?></td>
                        <td style="text-align:right;"><?php echo number_format($v['descuento_linea'], 2); ?></td>
                        <td style="text-align:right;"><strong><?php echo number_format($v['subtotal_linea'], 2); ?></strong></td>
                        <td style="text-align:center;"><?php echo $v['moneda']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot style="background:#f8f9fa; font-weight:bold;">
                <tr>
                    <td colspan="8" style="text-align:right;">TOTALES:</td>
                    <td style="text-align:center;"><?php echo $total_unidades; ?></td>
                    <td colspan="2"></td>
                    <td style="text-align:right;"><?php echo number_format($total_recaudado, 2); ?> BOB</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php endif; ?>

<script>
function exportarExcel() {
    const form = document.getElementById('filtrosForm');
    const inputs = form.querySelectorAll('input, select');
    let url = 'modulos/exportar_excel.php?';
    
    inputs.forEach(input => {
        if (input.name && input.value) {
            url += input.name + '=' + encodeURIComponent(input.value) + '&';
        }
    });
    url += 'exportar_excel=1';
    window.location.href = url;
}
</script>