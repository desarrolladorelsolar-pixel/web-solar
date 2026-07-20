<?php
// ============================================
// MÓDULO: MIS CAJAS (para VENDEDORES)
// SOLO ven cajas disponibles y su caja activa
// NO ven historial, NO pueden crear/editar
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['user_id'];

// Verificar caja activa del usuario
$caja_activa = null;
if(isset($_SESSION['apertura_caja_id'])) {
    $stmt = $pdo->prepare("SELECT ac.*, c.nombre as caja_nombre, c.sucursal_id, s.nombre as sucursal_nombre 
                           FROM apertura_caja ac 
                           JOIN cajas c ON ac.caja_id = c.id 
                           JOIN sucursales s ON c.sucursal_id = s.id 
                           WHERE ac.id = ? AND ac.estado = 'abierta'");
    $stmt->execute([$_SESSION['apertura_caja_id']]);
    $caja_activa = $stmt->fetch();
    
    if(!$caja_activa) {
        unset($_SESSION['apertura_caja_id']);
    }
}

// Obtener CAJAS DISPONIBLES (activas y no ocupadas por nadie)
$cajas_disponibles = $pdo->query("SELECT c.*, s.nombre as sucursal_nombre 
                                   FROM cajas c 
                                   JOIN sucursales s ON c.sucursal_id = s.id 
                                   WHERE c.estado = 1 
                                   AND c.id NOT IN (SELECT caja_id FROM apertura_caja WHERE estado = 'abierta')
                                   ORDER BY s.nombre, c.nombre")->fetchAll();
?>

<style>
    .caja-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 20px;
        transition: transform 0.3s;
        margin-bottom: 20px;
    }
    .caja-card:hover {
        transform: translateY(-3px);
    }
    .caja-card.active {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    .btn-abrir {
        background: #D4AF37;
        color: #000;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
    }
    .btn-cerrar {
        background: #dc3545;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
    }
    .stats {
        font-size: 12px;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid rgba(255,255,255,0.2);
    }
    
    @media (max-width: 768px) {
        .caja-card {
            padding: 15px;
        }
        .btn-abrir, .btn-cerrar {
            width: 100%;
            margin-top: 10px;
        }
    }
</style>

<!-- CAJA ACTIVA (si tiene) -->
<?php if($caja_activa): ?>
<div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <strong>💰 CAJA ABIERTA:</strong> <?php echo $caja_activa['caja_nombre']; ?> - <?php echo $caja_activa['sucursal_nombre']; ?>
            <br>
            <small>Monto inicial: <?php echo number_format($caja_activa['monto_inicial'], 2); ?> BOB | Abierta: <?php echo date('d/m/Y H:i', strtotime($caja_activa['fecha_apertura'])); ?></small>
        </div>
        <div>
            <a href="admin.php?mod=ventas" style="background: #000; color: #D4AF37; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;">🛒 IR A VENTAS</a>
            <button onclick="cerrarCajaActual()" class="btn-cerrar" style="margin-left: 10px;">🔒 CERRAR CAJA</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- CAJAS DISPONIBLES PARA ABRIR -->
<h3 style="color:#000; margin-bottom:15px;">🔓 Cajas Disponibles</h3>
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
    <?php if($caja_activa): ?>
        <div style="background:#f0f0f0; padding:40px; text-align:center; border-radius:8px; color:#999; grid-column: 1 / -1;">
            ⚠️ Ya tienes una caja abierta. Ciérrala antes de abrir otra.
        </div>
    <?php elseif(empty($cajas_disponibles)): ?>
        <div style="background:#f9f9f9; padding:40px; text-align:center; border-radius:8px; color:#999; grid-column: 1 / -1;">
            📭 No hay cajas disponibles en este momento.
        </div>
    <?php else: ?>
        <?php foreach($cajas_disponibles as $caja): ?>
        <div class="caja-card">
            <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap;">
                <div>
                    <h3 style="margin:0 0 5px 0;">📦 <?php echo htmlspecialchars($caja['nombre']); ?></h3>
                    <p style="margin:0;">🏢 <?php echo htmlspecialchars($caja['sucursal_nombre']); ?></p>
                    <div class="stats">
                        ✅ Disponible para abrir
                    </div>
                </div>
                <div>
                    <button onclick="abrirCaja(<?php echo $caja['id']; ?>)" class="btn-abrir">🔓 ABRIR CAJA</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- MODAL APERTURA CAJA -->
<div id="modalApertura" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:30px; border-radius:12px; width:90%; max-width:400px; border-top:6px solid #D4AF37;">
        <h2 style="margin-top:0;">🔓 Abrir Caja</h2>
        <form method="POST" action="admin.php?mod=mis_cajas">
            <input type="hidden" name="caja_id" id="apertura_caja_id">
            <div style="margin: 20px 0;">
                <label style="font-weight:bold;">💰 Monto Inicial (Fondo de caja)</label>
                <input type="number" name="monto_inicial" step="0.01" required placeholder="0.00" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="margin: 20px 0;">
                <label style="font-weight:bold;">📝 Observaciones (opcional)</label>
                <textarea name="observaciones" rows="3" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="cerrarModalApertura()" style="background:#f4f4f4; padding:12px 20px; border:none; border-radius:4px; cursor:pointer;">Cancelar</button>
                <button type="submit" name="btn_abrir_caja" style="background:#000; color:#D4AF37; padding:12px 25px; border:none; border-radius:4px; font-weight:bold; cursor:pointer;">✅ Abrir Caja</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirCaja(cajaId) {
        document.getElementById('apertura_caja_id').value = cajaId;
        document.getElementById('modalApertura').style.display = 'flex';
    }

    function cerrarModalApertura() {
        document.getElementById('modalApertura').style.display = 'none';
    }

    function cerrarCajaActual() {
        if(confirm('🔒 ¿Estás seguro de cerrar la caja actual?')) {
            window.location.href = 'admin.php?mod=cerrar_caja';
        }
    }
    
    document.addEventListener('keydown', function(e) {
        if(e.key === 'Escape') cerrarModalApertura();
    });
    
    document.getElementById('modalApertura').addEventListener('click', function(e) {
        if(e.target === this) cerrarModalApertura();
    });
</script>

<?php
// Procesar apertura de caja
if(isset($_POST['btn_abrir_caja'])) {
    $caja_id = $_POST['caja_id'];
    $monto_inicial = $_POST['monto_inicial'];
    $observaciones = $_POST['observaciones'];
    
    // Verificar que no tenga ya una caja abierta
    $stmt = $pdo->prepare("SELECT id FROM apertura_caja WHERE usuario_id = ? AND estado = 'abierta'");
    $stmt->execute([$_SESSION['user_id']]);
    if($stmt->fetch()) {
        echo "<script>alert('❌ Ya tienes una caja abierta. Ciérrala primero.'); window.location='admin.php?mod=mis_cajas';</script>";
        exit;
    }
    
    // Verificar que la caja esté activa y no esté ocupada
    $stmt = $pdo->prepare("SELECT c.estado, ac.id as apertura_activa 
                           FROM cajas c 
                           LEFT JOIN apertura_caja ac ON ac.caja_id = c.id AND ac.estado = 'abierta'
                           WHERE c.id = ?");
    $stmt->execute([$caja_id]);
    $resultado = $stmt->fetch();
    
    if($resultado['estado'] != 1) {
        echo "<script>alert('❌ Esta caja está desactivada.'); window.location='admin.php?mod=mis_cajas';</script>";
        exit;
    }
    
    if($resultado['apertura_activa']) {
        echo "<script>alert('❌ Esta caja ya está siendo utilizada por otro usuario.'); window.location='admin.php?mod=mis_cajas';</script>";
        exit;
    }
    
    $sql = "INSERT INTO apertura_caja (caja_id, usuario_id, fecha_apertura, monto_inicial, observaciones_apertura, estado) 
            VALUES (?, ?, NOW(), ?, ?, 'abierta')";
    $pdo->prepare($sql)->execute([$caja_id, $_SESSION['user_id'], $monto_inicial, $observaciones]);
    $apertura_id = $pdo->lastInsertId();
    
    $_SESSION['apertura_caja_id'] = $apertura_id;
    registrarLog($pdo, "APERTURA_CAJA", "Vendedor abrió caja ID: $caja_id con monto: $monto_inicial");
    
    echo "<script>window.location='admin.php?mod=ventas';</script>";
}
?>