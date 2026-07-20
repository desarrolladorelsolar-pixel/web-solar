<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['btn_guardar_cliente'])) {
    $id = $_POST['id_cliente'] ?? '';
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $documento = $_POST['documento'];
    $tipo_documento = $_POST['tipo_documento'];
    $direccion = $_POST['direccion'];
    $estado = isset($_POST['estado']) ? 1 : 0;

    if (empty($id)) {
        // NUEVO CLIENTE
        $estado = 1;
        $sql = "INSERT INTO clientes (nombre, email, telefono, documento, tipo_documento, direccion, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$nombre, $email, $telefono, $documento, $tipo_documento, $direccion, $estado]);
        registrarLog($pdo, "INSERTAR_CLIENTE", "Se creó el cliente: $nombre");
    } else {
        // EDITAR CLIENTE
        $estado = 1;
        $sql = "UPDATE clientes SET nombre = ?, email = ?, telefono = ?, documento = ?, tipo_documento = ?, direccion = ?, estado = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$nombre, $email, $telefono, $documento, $tipo_documento, $direccion, $estado, $id]);
        registrarLog($pdo, "EDITAR_CLIENTE", "Se actualizó el cliente: $nombre (ID: $id)");
    }
    echo "<script>window.location='admin.php?mod=clientes';</script>";
}

// SOFT DELETE
if (isset($_GET['del'])) {
    $id_del = $_GET['del'];
    $pdo->prepare("UPDATE clientes SET estado = 0 WHERE id = ?")->execute([$id_del]);
    registrarLog($pdo, "ELIMINAR_CLIENTE", "ID eliminado: $id_del");
    echo "<script>window.location='admin.php?mod=clientes';</script>";
}

$cliente_edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $cliente_edit = $stmt->fetch();
}

// Obtener todos los clientes
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nombre ASC")->fetchAll();
?>

<style>
    /* ===== CSS RESPONSIVO PARA CLIENTES ===== */
    
    .cliente-avatar {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        font-weight: bold;
    }
    
    .info-cliente {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }
    
    .info-label {
        font-size: 10px;
        color: #999;
        text-transform: uppercase;
    }
    
    /* Tablets */
    @media (max-width: 768px) {
        #formCliente {
            flex-direction: column !important;
            gap: 15px !important;
        }
        
        #formCliente > div {
            width: 100% !important;
        }
        
        #formCliente button {
            width: 100% !important;
        }
        
        .table-card {
            overflow-x: auto !important;
        }
        
        table {
            min-width: 700px;
        }
        
        th, td {
            padding: 10px !important;
            font-size: 0.85em;
        }
        
        .grid-2cols {
            grid-template-columns: 1fr !important;
        }
    }
    
    /* Móviles */
    @media (max-width: 480px) {
        #formCliente input, #formCliente select, #formCliente textarea {
            padding: 10px !important;
            font-size: 14px;
        }
        
        h3 {
            font-size: 1.2em !important;
        }
        
        .cliente-avatar {
            width: 35px;
            height: 35px;
            font-size: 14px;
        }
    }
    
    /* Grid para formulario */
    .grid-2cols {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-top: 15px;
    }
    
    .form-group {
        margin-bottom: 5px;
    }
    
    .form-group label {
        font-weight: bold;
        font-size: 13px;
        display: block;
        margin-bottom: 5px;
    }
    
    .form-group input, 
    .form-group select, 
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 60px;
    }
</style>

<div style="background:#fff; padding:25px; border-radius:8px; border:1px solid #eee; margin-bottom:30px;">
    <h3 style="margin-top:0; color:#000; border-bottom: 2px solid #D4AF37; display:inline-block; padding-bottom:5px;">
        <?php echo $cliente_edit ? "Editar Cliente" : "Nuevo Cliente"; ?>
    </h3>
    
    <form id="formCliente" method="POST">
        <input type="hidden" name="id_cliente" id="id_cliente" value="<?php echo $cliente_edit['id'] ?? ''; ?>">
        
        <div class="grid-2cols">
            <div class="form-group">
                <label>Nombre Completo *</label>
                <input type="text" name="nombre" id="input_nombre" required value="<?php echo $cliente_edit['nombre'] ?? ''; ?>" placeholder="Ej: Juan Pérez">
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo $cliente_edit['email'] ?? ''; ?>" placeholder="cliente@ejemplo.com">
            </div>
            
            <div class="form-group">
                <label>Teléfono</label>
                <input type="tel" name="telefono" value="<?php echo $cliente_edit['telefono'] ?? ''; ?>" placeholder="+591 12345678">
            </div>
            
            <div class="form-group" style="display:grid; grid-template-columns: 1fr 2fr; gap:10px;">
                <div>
                    <label>Tipo Doc.</label>
                    <select name="tipo_documento">
                        <option value="CI" <?php echo ($cliente_edit['tipo_documento'] ?? '') == 'CI' ? 'selected' : ''; ?>>CI</option>
                        <option value="NIT" <?php echo ($cliente_edit['tipo_documento'] ?? '') == 'NIT' ? 'selected' : ''; ?>>NIT</option>
                        <option value="RUC" <?php echo ($cliente_edit['tipo_documento'] ?? '') == 'RUC' ? 'selected' : ''; ?>>RUC</option>
                        <option value="Pasaporte" <?php echo ($cliente_edit['tipo_documento'] ?? '') == 'Pasaporte' ? 'selected' : ''; ?>>Pasaporte</option>
                    </select>
                </div>
                <div>
                    <label>Documento</label>
                    <input type="text" name="documento" value="<?php echo $cliente_edit['documento'] ?? ''; ?>" placeholder="Número">
                </div>
            </div>
            
            <div class="form-group">
                <label>Dirección</label>
                <textarea name="direccion" placeholder="Dirección completa"><?php echo $cliente_edit['direccion'] ?? ''; ?></textarea>
            </div>
            
 
        </div>
        
        <div style="margin-top:20px; display:flex; gap:10px; justify-content:flex-end;">
            <?php if($cliente_edit): ?>
                <a href="admin.php?mod=clientes" style="background:#f4f4f4; color:#666; padding:12px 25px; border-radius:4px; text-decoration:none;">Cancelar</a>
            <?php endif; ?>
            <button type="button" onclick="abrirModalCliente()" 
                    style="background:#000; color:#D4AF37; border:none; padding:12px 30px; border-radius:4px; cursor:pointer; font-weight:bold;">
                <?php echo $cliente_edit ? "ACTUALIZAR CLIENTE" : "GUARDAR CLIENTE"; ?>
            </button>
        </div>
        
        <input type="submit" name="btn_guardar_cliente" id="realSubmitCliente" style="display:none;">
    </form>
</div>

<div class="table-card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Contacto</th>
                <th>Documento</th>
                <th>Estado</th>
                <th style="text-align:center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clientes as $c): ?>
            <tr>
                <td style="padding:15px;"><?php echo $c['id']; ?></td>
                <td>
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div class="cliente-avatar">
                            <?php echo strtoupper(substr($c['nombre'], 0, 1)); ?>
                        </div>
                        <div class="info-cliente">
                            <strong><?php echo htmlspecialchars($c['nombre']); ?></strong>
                            <span class="info-label">Cliente desde: <?php echo date('d/m/Y', strtotime($c['created_at'])); ?></span>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="info-cliente">
                        <?php if($c['email']): ?>
                            <span>📧 <?php echo htmlspecialchars($c['email']); ?></span>
                        <?php endif; ?>
                        <?php if($c['telefono']): ?>
                            <span>📱 <?php echo htmlspecialchars($c['telefono']); ?></span>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <?php if($c['documento']): ?>
                        <span class="badge" style="background:#f3e5f5; color:#6a1b9a;">
                            <?php echo $c['tipo_documento']; ?>: <?php echo $c['documento']; ?>
                        </span>
                    <?php else: ?>
                        <span style="color:#ccc;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?php echo $c['estado'] ? 'badge-active' : 'badge-inactive'; ?>">
                        <?php echo $c['estado'] ? 'Activo' : 'Inactivo'; ?>
                    </span>
                </td>
                <td style="text-align:center;">
                    <div class="btn-group" style="display:flex; gap:5px; justify-content:center;">
                        <a href="admin.php?mod=clientes&edit=<?php echo $c['id']; ?>" 
                           style="background:#e3f2fd; color:#0d47a1; padding:5px 12px; border-radius:4px; text-decoration:none; font-size:12px;">
                            ✏️ Editar
                        </a>
                        <?php if(!$c['estado']): ?>
                        <a href="admin.php?mod=clientes&del=<?php echo $c['id']; ?>" 
                           onclick="return confirm('¿Eliminar a <?php echo $c['nombre']; ?>?')" 
                           style="background:#ffebee; color:#c62828; padding:5px 12px; border-radius:4px; text-decoration:none; font-size:12px;">
                            🗑️ Eliminar
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Confirmación -->
<div id="modalCliente" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h2 id="modalTitleCliente">Confirmar</h2>
        <p id="modalTextCliente"></p>
        <div style="margin-top:25px;">
            <button class="btn-no" onclick="cerrarModalCliente()">No, volver</button>
            <button class="btn-si" onclick="confirmarEnvioCliente()">Sí, confirmar</button>
        </div>
    </div>
</div>

<script>
    function abrirModalCliente() {
        const id = document.getElementById('id_cliente').value;
        const nombre = document.getElementById('input_nombre').value;
        
        if(nombre.trim() === "") {
            alert("El nombre del cliente es obligatorio.");
            return;
        }

        document.getElementById('modalTitleCliente').innerText = id ? "Confirmar Cambio" : "Nuevo Cliente";
        document.getElementById('modalTextCliente').innerText = id ? `¿Actualizar datos de "${nombre}"?` : `¿Registrar a "${nombre}" como cliente?`;
        
        document.getElementById('modalCliente').style.display = 'flex';
    }

    function cerrarModalCliente() {
        document.getElementById('modalCliente').style.display = 'none';
    }

    function confirmarEnvioCliente() {
        document.getElementById('realSubmitCliente').click();
    }
</script>