<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



if(!isset($_SESSION['apertura_caja_id'])) {
    header('Location: admin.php?mod=cajas');
    exit;
}

// Calcular ventas del turno
$stmt = $pdo->prepare("SELECT SUM(total) as total_ventas FROM ventas WHERE apertura_caja_id = ? AND estado = 1");
$stmt->execute([$_SESSION['apertura_caja_id']]);
$total_ventas = $stmt->fetchColumn() ?? 0;

// Obtener monto inicial
$stmt = $pdo->prepare("SELECT monto_inicial FROM apertura_caja WHERE id = ?");
$stmt->execute([$_SESSION['apertura_caja_id']]);
$monto_inicial = $stmt->fetchColumn();

$monto_esperado = $monto_inicial + $total_ventas;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $monto_real = $_POST['monto_real'];
    $observaciones = $_POST['observaciones'];
    
    $sql = "INSERT INTO cierre_caja (apertura_caja_id, usuario_id, fecha_cierre, monto_esperado, monto_real, observaciones_cierre) 
            VALUES (?, ?, NOW(), ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['apertura_caja_id'], $_SESSION['user_id'], $monto_esperado, $monto_real, $observaciones]);
    
    // Actualizar estado de apertura
    $pdo->prepare("UPDATE apertura_caja SET estado = 'cerrada' WHERE id = ?")->execute([$_SESSION['apertura_caja_id']]);
    
    registrarLog($pdo, "CIERRE_CAJA", "Cierre de caja ID: {$_SESSION['apertura_caja_id']} - Esperado: $monto_esperado - Real: $monto_real");
    
    unset($_SESSION['apertura_caja_id']);
    
    echo "<script>alert('✅ Caja cerrada exitosamente'); window.location='admin.php?mod=mis_cajas';</script>";
    exit;
}
?>

<style>
    .cierre-container {
        max-width: 500px;
        margin: 50px auto;
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    .monto {
        font-size: 24px;
        font-weight: bold;
        margin: 10px 0;
        padding: 15px;
        background: #f9f9f9;
        border-radius: 8px;
        text-align: center;
    }
    @media (max-width: 768px) {
        .cierre-container {
            margin: 20px;
            padding: 20px;
        }
        .monto {
            font-size: 18px;
            padding: 10px;
        }
    }
</style>

<div class="cierre-container">
    <h2 style="text-align:center; color:#D4AF37;">🔒 Cierre de Caja</h2>
    
    <div class="monto">
        Monto Inicial: <strong><?php echo number_format($monto_inicial, 2); ?> BOB</strong>
    </div>
    <!--<div class="monto">
        Ventas del turno: <strong><?php echo number_format($total_ventas, 2); ?> BOB</strong>
    </div>
    <div class="monto" style="background:#D4AF37; color:#000;">
        Monto Esperado: <strong><?php echo number_format($monto_esperado, 2); ?> BOB</strong>
    </div>-->
    
    <form method="POST">
        <div style="margin: 20px 0;">
            <label>💰Ingresa el Monto Real Contado (en caja)</label>
            <input type="number" name="monto_real" step="0.01" required 
                   style="width:100%; padding:12px; border:1px solid #ddd; border-radius:4px; font-size:18px;">
        </div>
        <div style="margin: 20px 0;">
            <label>📝 Observaciones</label>
            <textarea name="observaciones" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></textarea>
        </div>
        <button type="submit" style="background:#000; color:#D4AF37; width:100%; padding:15px; border:none; border-radius:4px; font-weight:bold; cursor:pointer;">
            ✅ CONFIRMAR CIERRE DE CAJA
        </button>
    </form>
</div>