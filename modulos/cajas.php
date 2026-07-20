<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// VERIFICAR QUE EL USUARIO ESTÉ LOGUEADO
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('❌ Debes iniciar sesión primero.'); window.location='login.php';</script>";
    exit;
}
// Verificar caja activa en sesión
$caja_activa = null;
if(isset($_SESSION['apertura_caja_id'])) {
    $stmt = $pdo->prepare("SELECT ac.*, c.nombre as caja_nombre, c.sucursal_id, s.nombre as sucursal_nombre, u.nombre as usuario_nombre 
                           FROM apertura_caja ac 
                           JOIN cajas c ON ac.caja_id = c.id 
                           JOIN sucursales s ON c.sucursal_id = s.id 
                           JOIN usuarios u ON ac.usuario_id = u.id 
                           WHERE ac.id = ? AND ac.estado = 'abierta'");
    $stmt->execute([$_SESSION['apertura_caja_id']]);
    $caja_activa = $stmt->fetch();
    
    if(!$caja_activa) {
        unset($_SESSION['apertura_caja_id']);
    }
}

// Guardar nueva caja (submit real del modal)
if (isset($_POST['btn_guardar_caja_real'])) {
    $id = $_POST['id_caja'] ?? '';
    $nombre = $_POST['nombre'];
    $sucursal_id = $_POST['sucursal_id'];

    if (empty($id)) {
        $sql = "INSERT INTO cajas (sucursal_id, nombre, estado) VALUES (?, ?, 1)";
        $pdo->prepare($sql)->execute([$sucursal_id, $nombre]);
        registrarLog($pdo, "INSERTAR_CAJA", "Se creó la caja: $nombre");
    } else {
        $sql = "UPDATE cajas SET nombre = ?, sucursal_id = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$nombre, $sucursal_id, $id]);
        registrarLog($pdo, "EDITAR_CAJA", "Se actualizó la caja: $nombre (ID: $id)");
    }
    echo "<script>window.location='admin.php?mod=cajas';</script>";
}

// ELIMINAR (soft delete - cambia estado a 0)
if (isset($_GET['del'])) {
    $id_del = $_GET['del'];
    // Verificar si tiene aperturas activas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM apertura_caja WHERE caja_id = ? AND estado = 'abierta'");
    $stmt->execute([$id_del]);
    if($stmt->fetchColumn() > 0) {
        echo "<script>alert('❌ No se puede eliminar la caja porque está actualmente abierta.'); window.location='admin.php?mod=cajas';</script>";
    } else {
        $pdo->prepare("UPDATE cajas SET estado = 0 WHERE id = ?")->execute([$id_del]);
        registrarLog($pdo, "ELIMINAR_CAJA", "Se eliminó la caja ID: $id_del");
        echo "<script>alert('✅ Caja eliminada'); window.location='admin.php?mod=cajas';</script>";
    }
}

$caja_edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM cajas WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $caja_edit = $stmt->fetch();
}

// Obtener sucursales activas
$sucursales = $pdo->query("SELECT id, nombre FROM sucursales WHERE estado = 1 ORDER BY nombre")->fetchAll();

// Obtener SOLO cajas activas (estado = 1)
$cajas = $pdo->query("SELECT c.*, s.nombre as sucursal_nombre 
                      FROM cajas c 
                      JOIN sucursales s ON c.sucursal_id = s.id 
                      WHERE c.estado = 1
                      ORDER BY s.nombre, c.nombre")->fetchAll();
?>

<style>
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }
    .modal-box {
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        width: 400px;
        text-align: center;
        border-top: 6px solid #D4AF37;
        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    }
    .btn-si {
        background: #000;
        color: #D4AF37;
        padding: 12px 25px;
        border: none;
        cursor: pointer;
        font-weight: bold;
        border-radius: 6px;
    }
    .btn-no {
        background: #f4f4f4;
        color: #333;
        padding: 12px 25px;
        border: none;
        cursor: pointer;
        margin-right: 10px;
        border-radius: 6px;
    }
    
    .caja-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 20px;
        transition: transform 0.3s;
    }
    .caja-card:hover {
        transform: translateY(-5px);
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
    .caja-stats {
        font-size: 12px;
        opacity: 0.9;
        margin-top: 10px;
    }
    
    @media (max-width: 768px) {
        .caja-card {
            padding: 15px;
        }
        .btn-abrir {
            width: 100%;
            margin-top: 10px;
        }
        .modal-box {
            width: 90%;
            margin: 20px;
            padding: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .caja-card h3 {
            font-size: 16px;
        }
        .modal-box h2 {
            font-size: 1.2em;
        }
        .modal-box p {
            font-size: 13px;
        }
    }
</style>

<!-- ALERTA DE CAJA ACTIVA -->
<?php if($caja_activa): ?>
<div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <strong>💰 CAJA ABIERTA:</strong> <?php echo $caja_activa['caja_nombre']; ?> - <?php echo $caja_activa['sucursal_nombre']; ?>
            <br>
            <small>Abierta por: <?php echo $caja_activa['usuario_nombre']; ?> | Monto inicial: <?php echo number_format($caja_activa['monto_inicial'], 2); ?> BOB</small>
        </div>
        <div>
            <a href="admin.php?mod=ventas" style="background: #000; color: #D4AF37; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold;">🛒 IR A VENTAS</a>
            <button onclick="cerrarCajaActual()" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">🔒 CERRAR CAJA</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- FORMULARIO DE CAJAS -->
<div style="background:#fff; padding:25px; border-radius:8px; border:1px solid #eee; margin-bottom:30px;">
    <h3 style="margin-top:0; color:#000; border-bottom: 2px solid #D4AF37; display:inline-block; padding-bottom:5px;">
        <?php echo $caja_edit ? "✏️ Editar Caja" : "➕ Nueva Caja"; ?>
    </h3>
    <form id="formCaja" method="POST" style="display: flex; gap: 15px; align-items: flex-end; margin-top: 15px; flex-wrap: wrap;">
        <input type="hidden" name="id_caja" id="id_caja" value="<?php echo $caja_edit['id'] ?? ''; ?>">
        
        <div style="flex: 2; min-width: 200px;">
            <label style="font-weight: bold; font-size: 13px;">🏢 Sucursal</label>
            <select name="sucursal_id" id="sucursal_id" required style="width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">Seleccionar sucursal</option>
                <?php foreach($sucursales as $suc): ?>
                    <option value="<?php echo $suc['id']; ?>" <?php echo ($caja_edit['sucursal_id'] ?? '') == $suc['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($suc['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="flex: 2; min-width: 200px;">
            <label style="font-weight: bold; font-size: 13px;">📦 Nombre de Caja</label>
            <input type="text" name="nombre" id="nombre_caja" required value="<?php echo htmlspecialchars($caja_edit['nombre'] ?? ''); ?>" 
                   placeholder="Ej: Caja 1, Caja Principal"
                   style="width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <button type="button" onclick="abrirModalCaja()" 
                style="background:#000; color:#D4AF37; border:none; padding:12px 30px; border-radius:4px; cursor:pointer; font-weight:bold;">
            💾 GUARDAR CAJA
        </button>
        
        <?php if($caja_edit): ?>
            <a href="admin.php?mod=cajas" style="background:#f4f4f4; color:#666; padding:12px 20px; border-radius:4px; text-decoration:none;">❌ Cancelar</a>
        <?php endif; ?>
        
        <input type="submit" name="btn_guardar_caja_real" id="realSubmitCaja" style="display:none;">
    </form>
</div>

<!-- LISTADO DE CAJAS -->
<h3 style="color:#000; margin-bottom:15px;">📋 Listado de Cajas</h3>
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
    <?php foreach($cajas as $caja): 
        // Verificar si esta caja tiene apertura activa
        $stmt = $pdo->prepare("SELECT ac.*, u.nombre as usuario FROM apertura_caja ac JOIN usuarios u ON ac.usuario_id = u.id WHERE ac.caja_id = ? AND ac.estado = 'abierta'");
        $stmt->execute([$caja['id']]);
        $apertura_activa = $stmt->fetch();
    ?>
    <div class="caja-card <?php echo $apertura_activa ? 'active' : ''; ?>">
        <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap;">
            <div>
                <h3 style="margin:0 0 10px 0;">📦 <?php echo htmlspecialchars($caja['nombre']); ?></h3>
                <p style="margin:5px 0;">🏢 <?php echo htmlspecialchars($caja['sucursal_nombre']); ?></p>
                <?php if($apertura_activa): ?>
                    <div class="caja-stats">
                        ✅ CAJA ABIERTA<br>
                        👤 <?php echo $apertura_activa['usuario']; ?><br>
                        🕐 <?php echo date('H:i', strtotime($apertura_activa['fecha_apertura'])); ?>
                    </div>
                <?php else: ?>
                    <div class="caja-stats">
                        ⚪ Caja cerrada / disponible
                    </div>
                <?php endif; ?>
            </div>
            <div style="text-align: right;">
                <?php if(!$apertura_activa && !$caja_activa): ?>
                    <button onclick="abrirCaja(<?php echo $caja['id']; ?>)" class="btn-abrir">🔓 ABRIR CAJA</button>
                <?php elseif(!$apertura_activa && $caja_activa): ?>
                    <span style="background: #ffc107; color: #000; padding: 5px 10px; border-radius: 5px; font-size: 11px;">⚠️ Hay otra caja abierta</span>
                <?php endif; ?>
                <div style="margin-top: 10px;">
                    <a href="admin.php?mod=cajas&edit=<?php echo $caja['id']; ?>" style="color: white; font-size: 12px; text-decoration: none; margin-right: 10px;">✏️ Editar</a>
                    <a href="admin.php?mod=cajas&del=<?php echo $caja['id']; ?>" onclick="return confirm('¿Eliminar esta caja?')" style="color: #ff6b6b; font-size: 12px; text-decoration: none;">🗑️ Eliminar</a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if(empty($cajas)): ?>
        <div style="background:#f9f9f9; padding:40px; text-align:center; border-radius:8px; color:#999; grid-column: 1 / -1;">
            📭 No hay cajas registradas. Crea tu primera caja arriba.
        </div>
    <?php endif; ?>
</div>

<!-- MODAL DE CONFIRMACIÓN PARA GUARDAR CAJA -->
<div id="modalCajaConfirm" class="modal-overlay">
    <div class="modal-box">
        <h2 id="modalTitleCaja">¿Confirmar?</h2>
        <p id="modalTextCaja"></p>
        <div style="margin-top:25px;">
            <button class="btn-no" onclick="cerrarModalCaja()">No, volver</button>
            <button class="btn-si" onclick="confirmarEnvioCaja()">Sí, confirmar</button>
        </div>
    </div>
</div>

<!-- MODAL APERTURA CAJA -->
<div id="modalApertura" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:30px; border-radius:12px; width:90%; max-width:400px; border-top:6px solid #D4AF37;">
        <h2>🔓 Abrir Caja</h2>
        <form method="POST">
            <input type="hidden" name="caja_id" id="apertura_caja_id">
            <div style="margin: 20px 0;">
                <label style="font-weight:bold;">💰 Monto Inicial (Fondo de caja)</label>
                <input type="number" name="monto_inicial" step="0.01" required placeholder="0.00" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="margin: 20px 0;">
                <label style="font-weight:bold;">📝 Observaciones</label>
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
    function abrirModalCaja() {
        const id = document.getElementById('id_caja').value;
        const nombre = document.getElementById('nombre_caja').value;
        const sucursalSelect = document.getElementById('sucursal_id');
        const sucursalNombre = sucursalSelect.options[sucursalSelect.selectedIndex]?.text || '';
        
        if(nombre.trim() === "") {
            alert("El nombre de la caja es obligatorio.");
            return;
        }
        
        if(!sucursalSelect.value) {
            alert("Selecciona una sucursal.");
            return;
        }

        document.getElementById('modalTitleCaja').innerText = id ? "Confirmar Cambio" : "Nueva Caja";
        document.getElementById('modalTextCaja').innerHTML = id 
            ? `¿Actualizar la caja <strong>"${nombre}"</strong> en <strong>${sucursalNombre}</strong>?` 
            : `¿Registrar la caja <strong>"${nombre}"</strong> en <strong>${sucursalNombre}</strong>?`;
        
        document.getElementById('modalCajaConfirm').style.display = 'flex';
    }

    function cerrarModalCaja() {
        document.getElementById('modalCajaConfirm').style.display = 'none';
    }

    function confirmarEnvioCaja() {
        document.getElementById('realSubmitCaja').click();
    }
    
    function abrirCaja(cajaId) {
        document.getElementById('apertura_caja_id').value = cajaId;
        document.getElementById('modalApertura').style.display = 'flex';
    }

    function cerrarModalApertura() {
        document.getElementById('modalApertura').style.display = 'none';
    }

    function cerrarCajaActual() {
        if(confirm('🔒 ¿Cerrar la caja actual?')) {
            window.location.href = 'admin.php?mod=cerrar_caja';
        }
    }
    
    document.addEventListener('keydown', function(e) {
        if(e.key === 'Escape') {
            cerrarModalCaja();
            cerrarModalApertura();
        }
    });
    
    document.getElementById('modalCajaConfirm').addEventListener('click', function(e) {
        if(e.target === this) cerrarModalCaja();
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
    
    // USAR user_id (como está en tu login)
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('❌ Error: No hay usuario logueado.'); window.location='login.php';</script>";
        exit;
    }
    
    $usuario_id = $_SESSION['user_id'];
    
    $sql = "INSERT INTO apertura_caja (caja_id, usuario_id, fecha_apertura, monto_inicial, observaciones_apertura, estado) 
            VALUES (?, ?, NOW(), ?, ?, 'abierta')";
    $pdo->prepare($sql)->execute([$caja_id, $usuario_id, $monto_inicial, $observaciones]);
    $apertura_id = $pdo->lastInsertId();
    
    $_SESSION['apertura_caja_id'] = $apertura_id;
    registrarLog($pdo, "APERTURA_CAJA", "Se abrió caja ID: $caja_id con monto inicial: $monto_inicial");
    
    echo "<script>window.location='admin.php?mod=ventas';</script>";
}
?>