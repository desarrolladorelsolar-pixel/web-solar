<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_GET['venta_id'])) {
    die("ID de venta no especificado");
}

global $pdo;
require_once '../config.php';

$venta_id = $_GET['venta_id'];

// Obtener info de la venta
$stmt = $pdo->prepare("
    SELECT v.*, 
           u.nombre as vendedor,
           c.nombre as cliente,
           c.documento as cliente_doc,
           s.nombre as sucursal
    FROM ventas v
    LEFT JOIN usuarios u ON v.usuario_id = u.id
    LEFT JOIN clientes c ON v.cliente_id = c.id
    LEFT JOIN sucursales s ON v.sucursal_id = s.id
    WHERE v.id = ?
");
$stmt->execute([$venta_id]);
$venta = $stmt->fetch();

// Obtener productos
$stmt = $pdo->prepare("
    SELECT vd.*, p.nombre as producto_nombre
    FROM venta_detalle vd
    JOIN productos p ON vd.producto_id = p.id
    WHERE vd.venta_id = ?
");
$stmt->execute([$venta_id]);
$detalles = $stmt->fetchAll();

// Obtener pagos
$stmt = $pdo->prepare("
    SELECT vp.*, mp.nombre as metodo_nombre
    FROM venta_pagos vp
    JOIN metodos_pago mp ON vp.metodo_pago_id = mp.id
    WHERE vp.venta_id = ?
");
$stmt->execute([$venta_id]);
$pagos = $stmt->fetchAll();

$subtotal = array_sum(array_column($detalles, 'subtotal_linea'));
?>

<div style="font-size: 14px;">
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <p><strong>📄 N° Venta:</strong> #<?php echo str_pad($venta_id, 6, '0', STR_PAD_LEFT); ?></p>
        <p><strong>📅 Fecha:</strong> <?php echo date('d/m/Y H:i:s', strtotime($venta['fecha_venta'])); ?></p>
        <p><strong>🏢 Sucursal:</strong> <?php echo htmlspecialchars($venta['sucursal']); ?></p>
        <p><strong>👤 Vendedor:</strong> <?php echo htmlspecialchars($venta['vendedor']); ?></p>
        <p><strong>👥 Cliente:</strong> <?php echo $venta['cliente'] ? htmlspecialchars($venta['cliente']) : 'Venta al mostrador'; ?>
        <?php if($venta['cliente_doc']): ?> (<?php echo $venta['cliente_doc']; ?>)<?php endif; ?></p>
        <?php if($venta['observaciones']): ?>
        <p><strong>📝 Observaciones:</strong> <?php echo nl2br(htmlspecialchars($venta['observaciones'])); ?></p>
        <?php endif; ?>
    </div>
    
    <h4>🛒 Productos</h4>
    <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
        <thead>
            <tr style="background:#f1f1f1;">
                <th style="padding:8px; text-align:left;">Producto</th>
                <th style="padding:8px; text-align:center;">Cant</th>
                <th style="padding:8px; text-align:right;">P.Unit</th>
                <th style="padding:8px; text-align:right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($detalles as $item): ?>
            <tr>
                <td style="padding:8px;"><?php echo htmlspecialchars($item['producto_nombre']); ?></td>
                <td style="padding:8px; text-align:center;"><?php echo $item['cantidad']; ?></td>
                <td style="padding:8px; text-align:right;"><?php echo number_format($item['precio_unitario'], 2); ?></td>
                <td style="padding:8px; text-align:right;"><?php echo number_format($item['subtotal_linea'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="border-top:2px solid #ddd;">
                <td colspan="3" style="padding:8px; text-align:right;"><strong>SUBTOTAL:</strong></td>
                <td style="padding:8px; text-align:right;"><?php echo number_format($subtotal, 2); ?></td>
            </tr>
            <?php if($venta['descuento'] > 0): ?>
            <tr>
                <td colspan="3" style="padding:8px; text-align:right;"><strong>DESCUENTO (<?php echo $venta['descuento']; ?>%):</strong></td>
                <td style="padding:8px; text-align:right;">-<?php echo number_format($subtotal * $venta['descuento'] / 100, 2); ?></td>
            </tr>
            <?php endif; ?>
            <tr style="background:#f1f1f1;">
                <td colspan="3" style="padding:8px; text-align:right;"><strong>TOTAL:</strong></td>
                <td style="padding:8px; text-align:right;"><strong><?php echo number_format($venta['total'], 2); ?> BOB</strong></td>
            </tr>
        </tfoot>
    </table>
    
    <h4>💳 Pagos</h4>
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:#f1f1f1;">
                <th style="padding:8px; text-align:left;">Método</th>
                <th style="padding:8px; text-align:right;">Monto</th>
                <th style="padding:8px; text-align:left;">Referencia</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($pagos as $pago): ?>
            <tr>
                <td style="padding:8px;"><?php echo htmlspecialchars($pago['metodo_nombre']); ?></td>
                <td style="padding:8px; text-align:right;"><?php echo number_format($pago['monto'], 2); ?></td>
                <td style="padding:8px;"><?php echo htmlspecialchars($pago['referencia'] ?? '-'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="margin-top:20px; text-align:center; padding-top:15px; border-top:1px solid #eee;">
        <button onclick="window.open('ticket_venta.php?venta_id=<?php echo $venta_id; ?>', '_blank')" style="background:#D4AF37; border:none; padding:8px 20px; border-radius:5px; cursor:pointer;">
            🖨️ IMPRIMIR TICKET
        </button>
    </div>
</div>