<?php
require_once '../config.php';

// Verificar si $pdo está definido
if(!isset($pdo)) {
    die("Error: La variable \$pdo no está definida. Verifica tu archivo de conexión.");
}

if(!isset($_GET['venta_id'])) {
    die("No se especificó la venta");
}

$venta_id = $_GET['venta_id'];

// Obtener datos de la venta
$stmt = $pdo->prepare("
    SELECT v.*, 
           u.nombre as usuario_nombre,
           c.nombre as cliente_nombre,
           mp.nombre as metodo_pago,
           s.nombre as sucursal_nombre,
           ac.id as apertura_id
    FROM ventas v
    LEFT JOIN usuarios u ON v.usuario_id = u.id
    LEFT JOIN clientes c ON v.cliente_id = c.id
    LEFT JOIN venta_pagos vp ON v.id = vp.venta_id
    LEFT JOIN metodos_pago mp ON vp.metodo_pago_id = mp.id
    LEFT JOIN sucursales s ON v.sucursal_id = s.id
    LEFT JOIN apertura_caja ac ON v.apertura_caja_id = ac.id
    WHERE v.id = ?
");
$stmt->execute([$venta_id]);
$venta = $stmt->fetch();

if(!$venta) {
    die("Venta no encontrada");
}

// Obtener detalles de la venta
$stmt = $pdo->prepare("
    SELECT vd.*, p.nombre as producto_nombre
    FROM venta_detalle vd
    JOIN productos p ON vd.producto_id = p.id
    WHERE vd.venta_id = ?
");
$stmt->execute([$venta_id]);
$detalles = $stmt->fetchAll();

// Obtener nombre de la caja
$caja_nombre = '';
if($venta['apertura_id']) {
    $stmt = $pdo->prepare("
        SELECT c.nombre 
        FROM apertura_caja ac 
        JOIN cajas c ON ac.caja_id = c.id 
        WHERE ac.id = ?
    ");
    $stmt->execute([$venta['apertura_id']]);
    $caja = $stmt->fetch();
    if($caja) {
        $caja_nombre = $caja['nombre'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Venta #<?php echo $venta_id; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .ticket {
            background: white;
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .ticket-header {
            text-align: center;
            border-bottom: 1px dashed #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .ticket-header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .ticket-header p {
            font-size: 10px;
            color: #666;
        }
        
        .ticket-info {
            margin-bottom: 15px;
            font-size: 11px;
        }
        
        .ticket-info p {
            margin: 4px 0;
        }
        
        .ticket-items {
            border-top: 1px dashed #333;
            border-bottom: 1px dashed #333;
            padding: 10px 0;
            margin: 10px 0;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 11px;
        }
        
        .item-nombre {
            flex: 2;
        }
        
        .item-cantidad {
            flex: 0.5;
            text-align: center;
        }
        
        .item-precio {
            flex: 0.8;
            text-align: right;
        }
        
        .item-total {
            flex: 0.8;
            text-align: right;
        }
        
        .ticket-totales {
            margin: 15px 0;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 12px;
        }
        
        .total-grande {
            font-size: 16px;
            font-weight: bold;
            border-top: 1px dashed #333;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .ticket-footer {
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px dashed #333;
            padding-top: 10px;
            margin-top: 15px;
        }
        
        .buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            font-family: Arial, sans-serif;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-print {
            background: #000;
            color: #D4AF37;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            
            .ticket {
                box-shadow: none;
                padding: 10px;
                margin: 0;
            }
            
            .buttons {
                display: none;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="ticket-header">
            <h1>POLLO EL SOLAR</h1>
            <p>Dirección: Av. Cañoto 581, Santa Cruz, Bolivia</p>
            <p>Tel: 70000000</p>
        </div>
        
        <div class="ticket-info">
            <p><strong>TICKET #:</strong> <?php echo str_pad($venta_id, 8, '0', STR_PAD_LEFT); ?></p>
            <p><strong>FECHA:</strong> <?php echo date('d/m/Y H:i:s', strtotime($venta['fecha_venta'])); ?></p>
            <p><strong>SUCURSAL:</strong> <?php echo htmlspecialchars($venta['sucursal_nombre'] ?? 'N/A'); ?></p>
            <p><strong>CAJA:</strong> <?php echo htmlspecialchars($caja_nombre ?: 'N/A'); ?></p>
            <p><strong>VENDEDOR:</strong> <?php echo htmlspecialchars($venta['usuario_nombre'] ?? 'N/A'); ?></p>
            <p><strong>CLIENTE:</strong> <?php echo $venta['cliente_nombre'] ? htmlspecialchars($venta['cliente_nombre']) : 'Venta al mostrador'; ?></p>
            <p><strong>PAGO:</strong> <?php echo htmlspecialchars($venta['metodo_pago'] ?? 'N/A'); ?></p>
        </div>
        
        <div class="ticket-items">
            <div class="item-row" style="font-weight: bold; border-bottom: 1px solid #333; margin-bottom: 8px;">
                <div class="item-nombre">PRODUCTO</div>
                <div class="item-cantidad">CANT</div>
                <div class="item-precio">P.UNIT</div>
                <div class="item-total">SUBT</div>
            </div>
            
            <?php 
            $subtotal_calculo = 0;
            foreach($detalles as $item): 
                $subtotal_calculo += $item['subtotal_linea'];
            ?>
            <div class="item-row">
                <div class="item-nombre"><?php echo htmlspecialchars($item['producto_nombre']); ?></div>
                <div class="item-cantidad"><?php echo $item['cantidad']; ?></div>
                <div class="item-precio"><?php echo number_format($item['precio_unitario'], 2); ?></div>
                <div class="item-total"><?php echo number_format($item['subtotal_linea'], 2); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="ticket-totales">
            <div class="total-line">
                <span>SUBTOTAL:</span>
                <span><?php echo number_format($subtotal_calculo, 2); ?> BOB</span>
            </div>
            <?php if($venta['descuento'] > 0): ?>
            <div class="total-line">
                <span>DESCUENTO (<?php echo $venta['descuento']; ?>%):</span>
                <span>-<?php echo number_format($subtotal_calculo * $venta['descuento'] / 100, 2); ?> BOB</span>
            </div>
            <?php endif; ?>
            <div class="total-line total-grande">
                <span><strong>TOTAL:</strong></span>
                <span><strong><?php echo number_format($venta['total'], 2); ?> BOB</strong></span>
            </div>
        </div>
        
        <div class="ticket-footer">
            <p>✅ ¡Gracias por su compra!</p>
            <p>Ticket Sin Valor Fiscal</p>
            <p><?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        
        <div class="buttons no-print">
            <button class="btn btn-print" onclick="window.print()">🖨️ IMPRIMIR TICKET</button>
            <button class="btn btn-back" onclick="window.location.href='../admin.php?mod=ventas'">⬅️ VOLVER A VENTAS</button>
        </div>
    </div>
    
    <script>
        // Auto-imprimir si se especifica en la URL
        <?php if(isset($_GET['auto_print'])): ?>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
        <?php endif; ?>
    </script>
</body>
</html>