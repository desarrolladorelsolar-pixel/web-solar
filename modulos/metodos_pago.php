<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['btn_guardar_mp'])) {
    $id = $_POST['id_metodo_pago'] ?? '';
    $nombre = $_POST['nombre'];

    if (empty($id)) {
        $sql = "INSERT INTO metodos_pago (nombre, estado) VALUES (?, 1)";
        $pdo->prepare($sql)->execute([$nombre]);
        registrarLog($pdo, "INSERTAR_METODO_PAGO", "Se creó el método de pago: $nombre");
    } else {
        $sql = "UPDATE metodos_pago SET nombre = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$nombre, $id]);
        registrarLog($pdo, "EDITAR_METODO_PAGO", "Se actualizó el método de pago: $nombre (ID: $id)");
    }
    echo "<script>window.location='admin.php?mod=metodos_pago';</script>";
}

// SOFT DELETE - ELIMINAR (solo cambia estado a 0)
if (isset($_GET['del'])) {
    $id_del = $_GET['del'];
    $pdo->prepare("UPDATE metodos_pago SET estado = 0 WHERE id = ?")->execute([$id_del]);
    registrarLog($pdo, "ELIMINAR_METODO_PAGO", "Método de pago eliminado ID: $id_del");
    echo "<script>window.location='admin.php?mod=metodos_pago';</script>";
}

$mp_edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM metodos_pago WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $mp_edit = $stmt->fetch();
}

// SOLO métodos ACTIVOS (estado = 1)
$metodos_pago = $pdo->query("SELECT * FROM metodos_pago WHERE estado = 1 ORDER BY nombre ASC")->fetchAll();
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
    
    .metodo-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
    }
    
    @media (max-width: 768px) {
        #formMetodoPago {
            flex-direction: column !important;
            gap: 15px !important;
        }
        #formMetodoPago > div {
            width: 100% !important;
        }
        #formMetodoPago button {
            width: 100% !important;
        }
        .table-card {
            overflow-x: auto !important;
        }
        table {
            min-width: 500px;
        }
        .modal-box {
            width: 90%;
            margin: 20px;
            padding: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .modal-box h2 {
            font-size: 1.2em;
        }
        .modal-box p {
            font-size: 13px;
        }
    }
</style>

<div style="background:#fff; padding:25px; border-radius:8px; border:1px solid #eee; margin-bottom:30px;">
    <h3 style="margin-top:0; color:#000; border-bottom: 2px solid #D4AF37; display:inline-block; padding-bottom:5px;">
        <?php echo $mp_edit ? "Editar Método de Pago" : "Nuevo Método de Pago"; ?>
    </h3>
    
    <form id="formMetodoPago" method="POST" style="display:flex; gap:15px; align-items: flex-end; margin-top:15px;">
        <input type="hidden" name="id_metodo_pago" id="id_mp" value="<?php echo $mp_edit['id'] ?? ''; ?>">
        
        <div style="flex:2;">
            <label style="font-weight:bold; font-size:13px;">Nombre del Método de Pago</label>
            <input type="text" name="nombre" id="input_nombre" required value="<?php echo $mp_edit['nombre'] ?? ''; ?>" 
                   placeholder="Ej: Efectivo, Tarjeta, Transferencia, QR..." 
                   style="width:100%; padding:12px; margin-top:5px; border:1px solid #ddd; border-radius:4px;">
        </div>

        <button type="button" onclick="abrirModalMP()" 
                style="background:#000; color:#D4AF37; border:none; padding:12px 30px; border-radius:4px; cursor:pointer; font-weight:bold;">
            <?php echo $mp_edit ? "ACTUALIZAR" : "GUARDAR MÉTODO"; ?>
        </button>
        
        <input type="submit" name="btn_guardar_mp" id="realSubmitMP" style="display:none;">
    </form>
    <?php if($mp_edit): ?>
        <a href="admin.php?mod=metodos_pago" style="color:red; font-size:12px; display:block; margin-top:10px; text-decoration:none;">[ Cancelar Edición ]</a>
    <?php endif; ?>
</div>

<div class="table-card">
    <table style="width:100%; border-collapse: collapse; background:#fff; border-radius:8px; overflow:hidden;">
        <thead>
            <tr style="background:#1a1a1a; color:#D4AF37; text-align:left;">
                <th style="padding:15px;">ID</th>
                <th>Método de Pago</th>
                <th style="text-align:right; padding-right:15px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($metodos_pago as $mp): ?>
            <tr style="border-bottom: 1px solid #f2f2f2;">
                <td style="padding:15px; color:#999;"><?php echo $mp['id']; ?></td>
                <td>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div class="metodo-icon">
                            <?php 
                                $iconos = [
                                    'Efectivo' => '💰',
                                    'Tarjeta' => '💳',
                                    'Transferencia' => '🏦',
                                    'QR' => '📱'
                                ];
                                $icono = '💵';
                                foreach($iconos as $key => $ico) {
                                    if(strpos($mp['nombre'], $key) !== false) {
                                        $icono = $ico;
                                        break;
                                    }
                                }
                                echo $icono;
                            ?>
                        </div>
                        <strong><?php echo htmlspecialchars($mp['nombre']); ?></strong>
                    </div>
                </td>
                <td style="text-align:right; padding-right:15px;">
                    <a href="admin.php?mod=metodos_pago&edit=<?php echo $mp['id']; ?>" 
                       style="background:#e3f2fd; color:#0d47a1; padding:5px 12px; border-radius:4px; text-decoration:none; font-size:12px; margin-right:5px;">Editar</a>
                    
                    <a href="admin.php?mod=metodos_pago&del=<?php echo $mp['id']; ?>" 
                       onclick="return confirm('¿Eliminar <?php echo $mp['nombre']; ?>?')" 
                       style="background:#ffebee; color:#c62828; padding:5px 12px; border-radius:4px; text-decoration:none; font-size:12px;">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($metodos_pago)): ?>
            <tr>
                <td colspan="3" style="text-align:center; padding:30px; color:#ccc;">No hay métodos de pago registrados.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MODAL -->
<div id="modalMP" class="modal-overlay">
    <div class="modal-box">
        <h2 id="modalTitleMP">¿Confirmar?</h2>
        <p id="modalTextMP"></p>
        <div style="margin-top:25px;">
            <button class="btn-no" onclick="cerrarModalMP()">No, volver</button>
            <button class="btn-si" onclick="confirmarEnvioMP()">Sí, confirmar</button>
        </div>
    </div>
</div>

<script>
    function abrirModalMP() {
        const id = document.getElementById('id_mp').value;
        const nombre = document.getElementById('input_nombre').value;
        
        if(nombre.trim() === "") {
            alert("El nombre es obligatorio.");
            return;
        }

        document.getElementById('modalTitleMP').innerText = id ? "Confirmar Cambio" : "Nuevo Método";
        document.getElementById('modalTextMP').innerText = id ? `¿Actualizar "${nombre}"?` : `¿Registrar "${nombre}"?`;
        
        document.getElementById('modalMP').style.display = 'flex';
    }

    function cerrarModalMP() {
        document.getElementById('modalMP').style.display = 'none';
    }

    function confirmarEnvioMP() {
        document.getElementById('realSubmitMP').click();
    }
    
    document.addEventListener('keydown', function(e) {
        if(e.key === 'Escape') cerrarModalMP();
    });
</script>