<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;

// Obtener fecha actual
$hoy = date('Y-m-d');
$inicio_semana = date('Y-m-d', strtotime('monday this week'));
$inicio_mes = date('Y-m-01');
$inicio_anio = date('Y-01-01');

// ========== ESTADÍSTICAS PRINCIPALES ==========

// Ventas hoy
$stmt = $pdo->prepare("SELECT COUNT(*) as total_ventas, SUM(total) as total_recaudado FROM ventas WHERE DATE(fecha_venta) = ? AND estado = 1");
$stmt->execute([$hoy]);
$hoy_data = $stmt->fetch();
$ventas_hoy = $hoy_data['total_ventas'] ?? 0;
$recaudado_hoy = $hoy_data['total_recaudado'] ?? 0;

// Ventas semana
$stmt = $pdo->prepare("SELECT COUNT(*) as total_ventas, SUM(total) as total_recaudado FROM ventas WHERE DATE(fecha_venta) BETWEEN ? AND ? AND estado = 1");
$stmt->execute([$inicio_semana, $hoy]);
$semana_data = $stmt->fetch();
$ventas_semana = $semana_data['total_ventas'] ?? 0;
$recaudado_semana = $semana_data['total_recaudado'] ?? 0;

// Ventas mes
$stmt = $pdo->prepare("SELECT COUNT(*) as total_ventas, SUM(total) as total_recaudado FROM ventas WHERE DATE(fecha_venta) BETWEEN ? AND ? AND estado = 1");
$stmt->execute([$inicio_mes, $hoy]);
$mes_data = $stmt->fetch();
$ventas_mes = $mes_data['total_ventas'] ?? 0;
$recaudado_mes = $mes_data['total_recaudado'] ?? 0;

// Ventas año
$stmt = $pdo->prepare("SELECT COUNT(*) as total_ventas, SUM(total) as total_recaudado FROM ventas WHERE DATE(fecha_venta) BETWEEN ? AND ? AND estado = 1");
$stmt->execute([$inicio_anio, $hoy]);
$anio_data = $stmt->fetch();
$recaudado_anio = $anio_data['total_recaudado'] ?? 0;

// Productos más vendidos
$top_productos = $pdo->query("
    SELECT p.nombre, SUM(vd.cantidad) as total_vendido, SUM(vd.subtotal_linea) as total_recaudado
    FROM venta_detalle vd
    JOIN productos p ON vd.producto_id = p.id
    JOIN ventas v ON vd.venta_id = v.id
    WHERE v.estado = 1 AND DATE(v.fecha_venta) BETWEEN '$inicio_mes' AND '$hoy'
    GROUP BY vd.producto_id
    ORDER BY total_vendido DESC
    LIMIT 5
")->fetchAll();

// Métodos de pago más usados
$metodos_pago = $pdo->query("
    SELECT mp.nombre, COUNT(*) as cantidad, SUM(vp.monto) as total
    FROM venta_pagos vp
    JOIN metodos_pago mp ON vp.metodo_pago_id = mp.id
    JOIN ventas v ON vp.venta_id = v.id
    WHERE v.estado = 1 AND DATE(v.fecha_venta) BETWEEN '$inicio_mes' AND '$hoy'
    GROUP BY vp.metodo_pago_id
    ORDER BY total DESC
")->fetchAll();

// Ventas por hora (últimos 7 días)
$ventas_por_hora = $pdo->query("
    SELECT HOUR(fecha_venta) as hora, COUNT(*) as cantidad, SUM(total) as total
    FROM ventas
    WHERE estado = 1 AND fecha_venta >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY HOUR(fecha_venta)
    ORDER BY hora ASC
")->fetchAll();

// Top vendedores
$top_vendedores = $pdo->query("
    SELECT u.nombre, COUNT(v.id) as ventas, SUM(v.total) as total
    FROM ventas v
    JOIN usuarios u ON v.usuario_id = u.id
    WHERE v.estado = 1 AND DATE(v.fecha_venta) BETWEEN '$inicio_mes' AND '$hoy'
    GROUP BY v.usuario_id
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();

// Ticket promedio
$ticket_promedio = $recaudado_mes > 0 && $ventas_mes > 0 ? $recaudado_mes / $ventas_mes : 0;
?>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 20px;
        transition: transform 0.3s;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-card h4 { font-size: 13px; opacity: 0.9; margin: 0 0 10px 0; }
    .stat-card .number { font-size: 32px; font-weight: bold; margin: 10px 0; }
    .stat-card .label { font-size: 12px; opacity: 0.8; }
    
    .chart-container {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .chart-title {
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #D4AF37;
    }
    .top-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .top-list li {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    .top-list .rank {
        font-weight: bold;
        color: #D4AF37;
        width: 30px;
    }
    .top-list .name { flex: 2; }
    .top-list .value { font-weight: bold; color: #1b5e20; }
    
    .progress-bar {
        height: 8px;
        background: #eee;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 5px;
    }
    .progress-fill {
        height: 100%;
        background: #D4AF37;
        border-radius: 4px;
        transition: width 0.5s;
    }
    
    @media (max-width: 768px) {
        .dashboard-grid { grid-template-columns: 1fr; }
        .chart-container { padding: 15px; }
    }
</style>

<div class="dashboard-grid">
    <div class="stat-card" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
        <h4>💰 VENTAS HOY</h4>
        <div class="number"><?php echo number_format($recaudado_hoy, 2); ?> BOB</div>
        <div class="label"><?php echo $ventas_hoy; ?> transacciones</div>
    </div>
    
    <div class="stat-card" style="background: linear-gradient(135deg, #D4AF37, #f1c40f);">
        <h4>📊 VENTAS SEMANA</h4>
        <div class="number"><?php echo number_format($recaudado_semana, 2); ?> BOB</div>
        <div class="label"><?php echo $ventas_semana; ?> transacciones</div>
    </div>
    
    <div class="stat-card" style="background: linear-gradient(135deg, #667eea, #764ba2);">
        <h4>📅 VENTAS MES</h4>
        <div class="number"><?php echo number_format($recaudado_mes, 2); ?> BOB</div>
        <div class="label"><?php echo $ventas_mes; ?> transacciones</div>
    </div>
    
    <div class="stat-card" style="background: linear-gradient(135deg, #ee5a24, #ff6b6b);">
        <h4>🎫 TICKET PROMEDIO</h4>
        <div class="number"><?php echo number_format($ticket_promedio, 2); ?> BOB</div>
        <div class="label">Promedio por venta</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
    <!-- Productos más vendidos -->
    <div class="chart-container">
        <div class="chart-title">🔥 Top 5 Productos Más Vendidos</div>
        <ul class="top-list">
            <?php foreach($top_productos as $i => $p): ?>
            <li>
                <span class="rank">#<?php echo $i+1; ?></span>
                <span class="name"><?php echo htmlspecialchars($p['nombre']); ?></span>
                <span class="value"><?php echo $p['total_vendido']; ?> unidades</span>
            </li>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo min(100, ($p['total_vendido'] / max($top_productos[0]['total_vendido'] ?? 1, 1)) * 100); ?>%"></div>
            </div>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <!-- Métodos de pago -->
    <div class="chart-container">
        <div class="chart-title">💳 Métodos de Pago</div>
        <ul class="top-list">
            <?php foreach($metodos_pago as $mp): ?>
            <li>
                <span class="name"><?php echo htmlspecialchars($mp['nombre']); ?></span>
                <span class="value"><?php echo number_format($mp['total'], 2); ?> BOB</span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 20px;">
    <!-- Ventas por hora -->
    <div class="chart-container">
        <div class="chart-title">⏰ Ventas por Hora (Últimos 7 días)</div>
        <div style="overflow-x: auto;">
            <table style="width:100%; font-size:12px;">
                <thead>
                    <tr style="background:#f8f9fa;">
                        <th>Hora</th>
                        <th>Cantidad</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ventas_por_hora as $vh): ?>
                    <tr>
                        <td><?php echo str_pad($vh['hora'], 2, '0', STR_PAD_LEFT) . ':00'; ?></td>
                        <td><?php echo $vh['cantidad']; ?> ventas</td>
                        <td><?php echo number_format($vh['total'], 2); ?> BOB</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Top vendedores -->
    <div class="chart-container">
        <div class="chart-title">👤 Top 5 Vendedores</div>
        <ul class="top-list">
            <?php foreach($top_vendedores as $i => $v): ?>
            <li>
                <span class="rank">#<?php echo $i+1; ?></span>
                <span class="name"><?php echo htmlspecialchars($v['nombre']); ?></span>
                <span class="value"><?php echo number_format($v['total'], 2); ?> BOB</span>
            </li>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo min(100, ($v['total'] / max($top_vendedores[0]['total'] ?? 1, 1)) * 100); ?>%"></div>
            </div>
            <?php endforeach; ?>
        </ul>
    </div>
</div>