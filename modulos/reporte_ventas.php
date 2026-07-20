<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar permisos de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 'admin') {
    echo "<script>alert('❌ No tienes permisos para acceder a este módulo.'); window.location='admin.php?mod=ventas';</script>";
    exit;
}

global $pdo;

// Obtener filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$sucursal_id = $_GET['sucursal_id'] ?? '';
$metodo_pago_id = $_GET['metodo_pago_id'] ?? '';
$usuario_id = $_GET['usuario_id'] ?? '';
$cliente_id = $_GET['cliente_id'] ?? '';

// Verificar si se aplicaron filtros (al menos fecha o sucursal)
$filtros_aplicados = !empty($fecha_inicio) && !empty($fecha_fin);
$ventas = [];

if ($filtros_aplicados) {
    // Construir consulta principal con JOINs
    $sql = "
        SELECT 
            v.id,
            v.fecha_venta,
            v.total,
            v.descuento,
            v.observaciones,
            s.nombre as sucursal_nombre,
            u.nombre as usuario_nombre,
            c.nombre as cliente_nombre,
            c.documento as cliente_documento,
            GROUP_CONCAT(DISTINCT mp.nombre SEPARATOR ', ') as metodos_pago,
            (
                SELECT COUNT(*) 
                FROM venta_detalle vd 
                WHERE vd.venta_id = v.id
            ) as total_productos,
            (
                SELECT SUM(vd.cantidad) 
                FROM venta_detalle vd 
                WHERE vd.venta_id = v.id
            ) as total_unidades
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
}

// Calcular totales solo si hay datos
$total_ventas = count($ventas);
$total_recaudado = array_sum(array_column($ventas, 'total'));
$promedio_venta = $total_ventas > 0 ? $total_recaudado / $total_ventas : 0;

// Obtener datos para filtros
$sucursales = $pdo->query("SELECT id, nombre FROM sucursales WHERE estado = 1 ORDER BY nombre")->fetchAll();
$metodos_pago = $pdo->query("SELECT id, nombre FROM metodos_pago WHERE estado = 1 ORDER BY nombre")->fetchAll();
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios WHERE estado = 1 ORDER BY nombre")->fetchAll();
$clientes = $pdo->query("SELECT id, nombre FROM clientes WHERE estado = 1 ORDER BY nombre LIMIT 100")->fetchAll();
?>

<style>
    .reporte-container {
        background: white;
        border-radius: 10px;
        overflow: hidden;
    }
    
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
        margin-bottom: 15px;
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
        padding: 10px 25px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        margin-top: 28px;
    }
    
    .btn-filtrar:hover {
        background: #b8941e;
    }
    
    .btn-limpiar {
        background: #6c757d;
        color: white;
    }
    
    .btn-excel {
        background: #28a745;
        color: white;
    }
    
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
    }
    
    .stat-card .number {
        font-size: 28px;
        font-weight: bold;
        margin: 10px 0;
    }
    
    .stat-card .label {
        font-size: 12px;
        opacity: 0.9;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .ventas-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .ventas-table th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: #555;
        border-bottom: 2px solid #dee2e6;
    }
    
    .ventas-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    
    .ventas-table tr:hover {
        background: #f8f9fa;
    }
    
    .btn-ver-detalle {
        background: #D4AF37;
        color: #000;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 11px;
    }
    
    .alerta-filtros {
        background: #fff3cd;
        border: 1px solid #ffeeba;
        color: #856404;
        padding: 40px;
        border-radius: 10px;
        text-align: center;
        margin: 20px 0;
    }
    
    .alerta-filtros h3 {
        margin: 10px 0;
    }
    
    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        overflow: auto;
    }
    
    .modal-content {
        background: white;
        margin: 5% auto;
        padding: 25px;
        width: 90%;
        max-width: 800px;
        border-radius: 10px;
        position: relative;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    
    .close-modal {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: #999;
    }
    
    @media (max-width: 768px) {
        .filtros-grid {
            grid-template-columns: 1fr;
        }
        .stats-cards {
            grid-template-columns: 1fr;
        }
        .ventas-table {
            min-width: 800px;
        }
    }
</style>

<div class="reporte-container">
    <h2 style="margin-top:0; color:#333;">📊 Reporte de Ventas</h2>
    <p style="color:#666; margin-bottom:20px;">Selecciona fechas y aplica filtros para ver los resultados</p>
    
    <!-- Filtros -->
    <div class="filtros-section">
        <form method="GET" id="filtrosForm">
            <input type="hidden" name="mod" value="reporte_ventas">
            <div class="filtros-grid">
                <div class="filtro-group">
                    <label class="required">📅 Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                </div>
                <div class="filtro-group">
                    <label class="required">📅 Fecha Fin</label>
                    <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
                </div>
                <div class="filtro-group">
                    <label>🏢 Sucursal</label>
                    <select name="sucursal_id">
                        <option value="">Todas</option>
                        <?php foreach($sucursales as $suc): ?>
                            <option value="<?php echo $suc['id']; ?>" <?php echo $sucursal_id == $suc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($suc['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-group">
                    <label>👤 Usuario</label>
                    <select name="usuario_id">
                        <option value="">Todos</option>
                        <?php foreach($usuarios as $usr): ?>
                            <option value="<?php echo $usr['id']; ?>" <?php echo $usuario_id == $usr['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($usr['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-group">
                    <label>💳 Método Pago</label>
                    <select name="metodo_pago_id">
                        <option value="">Todos</option>
                        <?php foreach($metodos_pago as $mp): ?>
                            <option value="<?php echo $mp['id']; ?>" <?php echo $metodo_pago_id == $mp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mp['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-group">
                    <label>👥 Cliente</label>
                    <select name="cliente_id">
                        <option value="">Todos</option>
                        <?php foreach($clientes as $cli): ?>
                            <option value="<?php echo $cli['id']; ?>" <?php echo $cliente_id == $cli['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cli['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-group">
                    <button type="submit" class="btn-filtrar">🔍 FILTRAR</button>
                    <button type="button" class="btn-filtrar btn-limpiar" onclick="limpiarFiltros()">🔄 LIMPIAR</button>
                </div>
            </div>
        </form>
    </div>
    
    <?php if (!$filtros_aplicados): ?>
        <!-- Mensaje cuando no hay filtros -->
        <div class="alerta-filtros">
            <span style="font-size: 48px;">🔍</span>
            <h3>Aplica filtros para ver los resultados</h3>
            <p>Selecciona una fecha de inicio y fecha fin para generar el reporte.</p>
        </div>
    <?php elseif (count($ventas) === 0): ?>
        <!-- Sin resultados -->
        <div class="alerta-filtros">
            <span style="font-size: 48px;">📭</span>
            <h3>No hay ventas con los filtros seleccionados</h3>
            <p>Prueba con otros rangos de fecha o diferentes filtros.</p>
        </div>
    <?php else: ?>
        <!-- Estadísticas -->
        <div class="stats-cards">
            <div class="stat-card" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                <div class="label">📋 TOTAL VENTAS</div>
                <div class="number"><?php echo $total_ventas; ?></div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #D4AF37, #f1c40f);">
                <div class="label">💰 TOTAL RECAUDADO</div>
                <div class="number"><?php echo number_format($total_recaudado, 2); ?> BOB</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                <div class="label">📊 PROMEDIO POR VENTA</div>
                <div class="number"><?php echo number_format($promedio_venta, 2); ?> BOB</div>
            </div>
        </div>
        
        <!-- Botón Exportar Excel -->
        <div style="text-align: right; margin-bottom: 15px;">
            <button type="button" class="btn-excel" onclick="exportarExcel()" style="background:#28a745; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer;">📊 EXPORTAR A EXCEL</button>
        </div>
        
        <!-- Tabla de Ventas -->
        <div class="table-responsive">
            <table class="ventas-table">
                <thead>
                    <tr>
                        <th># VENTA</th>
                        <th>FECHA</th>
                        <th>SUCURSAL</th>
                        <th>VENDEDOR</th>
                        <th>CLIENTE</th>
                        <th>MÉTODO PAGO</th>
                        <th>PROD</th>
                        <th>UNID</th>
                        <th>DTO</th>
                        <th>TOTAL</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ventas as $venta): ?>
                        <tr>
                            <td>#<?php echo str_pad($venta['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></td>
                            <td><?php echo htmlspecialchars($venta['sucursal_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($venta['usuario_nombre']); ?></td>
                            <td>
                                <?php 
                                if($venta['cliente_nombre']) {
                                    echo htmlspecialchars($venta['cliente_nombre']);
                                    if($venta['cliente_documento']) {
                                        echo "<br><small>" . $venta['cliente_documento'] . "</small>";
                                    }
                                } else {
                                    echo "<span style='color:#999;'>Mostrador</span>";
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($venta['metodos_pago']); ?></td>
                            <td><?php echo $venta['total_productos']; ?></td>
                            <td><?php echo $venta['total_unidades']; ?></td>
                            <td><?php echo $venta['descuento'] > 0 ? $venta['descuento'] . '%' : '-'; ?></td>
                            <td><strong><?php echo number_format($venta['total'], 2); ?></strong></td>
                            <td>
                                <button class="btn-ver-detalle" onclick="verDetalle(<?php echo $venta['id']; ?>)">📋 VER</button>
                                <button class="btn-ver-detalle" onclick="window.open('modulos/ticket_venta.php?venta_id=<?php echo $venta['id']; ?>', '_blank')">🎫 TICKET</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para Detalle de Venta -->
<div id="modalDetalle" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📋 Detalle de Venta</h3>
            <button class="close-modal" onclick="cerrarModal()">&times;</button>
        </div>
        <div id="detalleContent">
            <div style="text-align:center; padding:40px;">
                <div class="loader">Cargando...</div>
            </div>
        </div>
    </div>
</div>

<script>
function limpiarFiltros() {
    window.location.href = 'admin.php?mod=reporte_ventas';
}

function exportarExcel() {
    const form = document.getElementById('filtrosForm');
    const inputs = form.querySelectorAll('input, select');
    let url = 'modulos/exportar_excel_ventas.php?';
    
    inputs.forEach(input => {
        if (input.name && input.value) {
            url += input.name + '=' + encodeURIComponent(input.value) + '&';
        }
    });
    url += 'exportar_excel=1';
    window.location.href = url;
}

function verDetalle(ventaId) {
    const modal = document.getElementById('modalDetalle');
    modal.style.display = 'block';
    
    fetch('modulos/ajax_detalle_venta.php?venta_id=' + ventaId)
        .then(response => response.text())
        .then(data => {
            document.getElementById('detalleContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('detalleContent').innerHTML = '<p style="color:red">Error al cargar detalles</p>';
        });
}

function cerrarModal() {
    const modal = document.getElementById('modalDetalle');
    modal.style.display = 'none';
}

document.addEventListener('keydown', function(event) {
    if(event.key === 'Escape') {
        cerrarModal();
    }
});

window.onclick = function(event) {
    const modal = document.getElementById('modalDetalle');
    if(event.target === modal) {
        cerrarModal();
    }
}
</script>