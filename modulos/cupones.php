<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generador de código mejorado: sin I, O, 0, 1 para evitar confusión visual al leerlos en papel
function generarCodigoUnico($longitud = 6) {
    $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $codigo = '';
    for ($i = 0; $i < $longitud; $i++) {
        $codigo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    return $codigo;
}

// Generación masiva con verificación de duplicados dentro del lote
function generarCodigosMasivos($cantidad, $longitud = 6) {
    $codigos = [];
    $intentos = 0;
    while (count($codigos) < $cantidad && $intentos < $cantidad * 10) {
        $nuevo = generarCodigoUnico($longitud);
        if (!in_array($nuevo, $codigos)) {
            $codigos[] = $nuevo;
        }
        $intentos++;
    }
    return $codigos;
}

// --- A. GUARDAR CUPÓN (CREAR/ACTUALIZAR) ---
if (isset($_POST['btn_guardar_cupon'])) {
    $id = $_POST['id_cupon'] ?? '';
    $tipo_cupon_id = $_POST['tipo_cupon_id'];
    $descripcion = $_POST['descripcion'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_expiracion = $_POST['fecha_expiracion'];
    $estado = isset($_POST['estado']) ? 1 : 0;
    $cantidad_codigos = isset($_POST['cantidad_codigos']) ? (int)$_POST['cantidad_codigos'] : 1;

    if (empty($id)) {
        if ($cantidad_codigos > 1) {
            $codigos = generarCodigosMasivos($cantidad_codigos);
            $sql = "INSERT INTO cupones (codigo, tipo_cupon_id, descripcion, fecha_inicio, fecha_expiracion, estado) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            foreach ($codigos as $codigo) {
                $stmt->execute([$codigo, $tipo_cupon_id, $descripcion, $fecha_inicio, $fecha_expiracion, $estado]);
            }
            registrarLog($pdo, "INSERTAR_CUPONES_MASIVOS", "Se crearon $cantidad_codigos cupones del tipo ID: $tipo_cupon_id - Descripción: $descripcion");
        } else {
            $codigo = generarCodigoUnico();
            $sql = "INSERT INTO cupones (codigo, tipo_cupon_id, descripcion, fecha_inicio, fecha_expiracion, estado) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$codigo, $tipo_cupon_id, $descripcion, $fecha_inicio, $fecha_expiracion, $estado]);
            $id = $pdo->lastInsertId();
            registrarLog($pdo, "INSERTAR_CUPON", "Cupón creado: $codigo - Tipo ID: $tipo_cupon_id - Descripción: $descripcion");
        }
    } else {
        $sql = "UPDATE cupones SET tipo_cupon_id=?, descripcion=?, fecha_inicio=?, fecha_expiracion=?, estado=? WHERE id=?";
        $pdo->prepare($sql)->execute([$tipo_cupon_id, $descripcion, $fecha_inicio, $fecha_expiracion, $estado, $id]);
        registrarLog($pdo, "EDITAR_CUPON", "Actualizado cupón ID: $id - Nuevo tipo: $tipo_cupon_id - Estado: " . ($estado ? 'Activo' : 'Inactivo'));
    }
    
    echo "<script>window.location='admin.php?mod=cupones';</script>";
    exit;
}

// --- B. ANULAR CUPÓN (CAMBIO DE ESTADO) ---
if (isset($_GET['toggle_estado'])) {
    $id_cupon = $_GET['toggle_estado'];
    $estado_actual = $_GET['estado'] ?? 0;
    $nuevo_estado = $estado_actual == 1 ? 0 : 1;
    $pdo->prepare("UPDATE cupones SET estado = ? WHERE id = ?")->execute([$nuevo_estado, $id_cupon]);
    registrarLog($pdo, "CAMBIAR_ESTADO_CUPON", "Cupón ID: $id_cupon cambiado a estado=" . ($nuevo_estado ? 'Activo' : 'Inactivo'));
    echo "<script>window.location='admin.php?mod=cupones';</script>";
    exit;
}

// --- C. ELIMINAR CUPÓN (SOFT DELETE) ---
if (isset($_GET['del'])) {
    $id_del = $_GET['del'];
    $pdo->prepare("UPDATE cupones SET estado = 0 WHERE id = ?")->execute([$id_del]);
    registrarLog($pdo, "ELIMINAR_CUPON", "Cupón ID: $id_del eliminado (estado=0)");
    echo "<script>window.location='admin.php?mod=cupones';</script>";
    exit;
}

// --- 2. CONSULTAS PARA INTERFAZ ---
$tipos_cupon = $pdo->query("SELECT id, nombre FROM tipo_cupon WHERE estado = 1 ORDER BY nombre ASC")->fetchAll();

$sql_cupones = "SELECT c.*, tc.nombre as tipo_nombre 
                FROM cupones c 
                LEFT JOIN tipo_cupon tc ON c.tipo_cupon_id = tc.id 
                /*WHERE c.estado = 1*/
                ORDER BY c.id DESC";
$cupones = $pdo->query($sql_cupones)->fetchAll();

// --- 3. LÓGICA DE EDICIÓN ---
$c_edit = null;
if (isset($_GET['edit'])) {
    $id_editar = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM cupones WHERE id = ?");
    $stmt->execute([$id_editar]);
    $c_edit = $stmt->fetch();
}
?>

<style>
    .modal-full { 
        display: <?php echo $c_edit ? 'flex' : 'none'; ?>; 
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(0,0,0,0.8); z-index: 9999; 
        justify-content: center; align-items: flex-start; 
        overflow-y: auto; padding: 40px 20px; box-sizing: border-box;
    }
    .modal-container { 
        background: #fff; width: 100%; max-width: 900px; border-radius: 12px; 
        border-top: 8px solid #D4AF37; padding: 30px; position: relative; 
        box-shadow: 0 15px 40px rgba(0,0,0,0.5); margin-bottom: 40px;
    }
    .grid-form { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;}
    .form-group label { display: block; font-weight: bold; font-size: 13px; color: #555; margin-bottom: 5px; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-family: inherit;}
    .btn-pastilla { padding: 6px 15px; border-radius: 20px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer;}
    .btn-edit { background: #e3f2fd; color: #0d47a1; }
    .btn-edit:hover { background: #bbdefb; }
    .btn-delete { background: #ffebee; color: #c62828; }
    .btn-delete:hover { background: #ffcdd2; }
    .btn-active { background: #e8f5e9; color: #1b5e20; }
    .btn-active:hover { background: #c8e6c9; }
    .btn-inactive { background: #f5f5f5; color: #616161; }
    .btn-inactive:hover { background: #e0e0e0; }
    .btn-used { background: #e8eaf6; color: #283593; }
    .codigo-card { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; margin-top: 10px; font-family: 'Courier New', monospace; font-size: 14px; display: inline-block;}
    .codigo-card strong { color: #D4AF37; font-size: 18px; }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; padding-bottom:10px; border-bottom:1px solid #eee;">
    <h2 style="margin:0;">🎫 Gestión de Cupones</h2>
    <button onclick="abrirModalNuevo()" style="background:#000; color:#D4AF37; padding:12px 25px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; box-shadow: 0 4px 6px rgba(0,0,0,0.2);">
        + NUEVO CUPÓN
    </button>
</div>

<table style="width:100%; border-collapse: collapse; background:#fff; border-radius:8px; overflow:hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <thead>
        <tr style="background:#1a1a1a; color:#D4AF37; text-align:left; font-size:13px;">
            <th style="padding:18px;">Código</th>
            <th>Tipo de Cupón</th>
            <th>Descripción</th>
            <th>Vigencia</th>
            <th style="text-align:center;">Usado</th>
            <th style="text-align:center;">Estado</th>
            <th style="text-align:right; padding-right:18px;">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($cupones as $c): ?>
        <tr style="border-bottom: 1px solid #f2f2f2; transition: 0.2s;" onmouseover="this.style.background='#fbfbfb'" onmouseout="this.style.background='#fff'">
            <td style="padding:10px 18px;">
                <span style="font-family: 'Courier New', monospace; font-weight:bold; font-size:14px; background:#f8f9fa; padding:3px 8px; border-radius:4px;">
                    <?php echo $c['codigo']; ?>
                </span>
            </td>
            <td><span style="font-weight:bold; color:#000; font-size:14px;"><?php echo $c['tipo_nombre'] ?? 'Sin tipo'; ?></span></td>
            <td style="font-size:13px; color:#666;"><?php echo $c['descripcion'] ?: '<i style="color:#ccc">Sin descripción</i>'; ?></td>
            <td style="font-size:12px; color:#777;">
                <div><strong>Inicio:</strong> <?php echo date('d/m/Y H:i', strtotime($c['fecha_inicio'])); ?></div>
                <div><strong>Expira:</strong> <?php echo date('d/m/Y H:i', strtotime($c['fecha_expiracion'])); ?></div>
            </td>
            <td style="text-align:center;">
                <?php if($c['usado']): ?>
                    <span style="background:#e8eaf6; color:#283593; padding:4px 12px; border-radius:12px; font-size:12px; font-weight:bold;">✅ Usado</span>
                <?php else: ?>
                    <span style="background:#e8f5e9; color:#1b5e20; padding:4px 12px; border-radius:12px; font-size:12px; font-weight:bold;">🟢 Disponible</span>
                <?php endif; ?>
            </td>
            <td style="text-align:center;">
                <?php if($c['estado']): ?>
                    <a>✅ Activo</a>
                <?php else: ?>
                    <a>⛔ Anulado</a>
                <?php endif; ?>
            </td>
            <td style="text-align:right; padding-right:18px; white-space:nowrap;">
                <a href="admin.php?mod=cupones&del=<?php echo $c['id']; ?>" class="btn-pastilla btn-delete" onclick="return confirm('¿Estás seguro de eliminar el cupón: <?php echo $c['codigo']; ?>?')">🗑️</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($cupones)): ?>
            <tr><td colspan="7" style="text-align:center; padding:40px; color:#aaa;">No hay cupones registrados aún. ¡Crea el primero!</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- MODAL DE CREACIÓN/EDICIÓN -->
<div id="modalCupon" class="modal-full">
    <div class="modal-container">
        <a href="admin.php?mod=cupones" style="position:absolute; top:15px; right:20px; cursor:pointer; font-size:28px; text-decoration:none; color:#aaa;">&times;</a>
        
        <h3 id="mTitle" style="margin-top:0; color:#000; border-bottom:2px solid #D4AF37; display:inline-block; padding-bottom:5px;">
            <?php echo $c_edit ? "Editar Cupón: ".$c_edit['codigo'] : "Registrar Nuevo Cupón"; ?>
        </h3>
        
        <form id="formC" method="POST" style="margin-top:25px;">
            <input type="hidden" name="id_cupon" id="id_cupon" value="<?php echo $c_edit['id'] ?? ''; ?>">
            
            <div class="grid-form">
                <div class="form-group">
                    <label>Tipo de Cupón *</label>
                    <select name="tipo_cupon_id" required class="form-control">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach($tipos_cupon as $tc): ?>
                            <option value="<?php echo $tc['id']; ?>" <?php echo (isset($c_edit['tipo_cupon_id']) && $c_edit['tipo_cupon_id'] == $tc['id']) ? 'selected' : ''; ?>>
                                <?php echo $tc['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Descripción del Cupón</label>
                    <input type="text" name="descripcion" value="<?php echo $c_edit['descripcion'] ?? ''; ?>" class="form-control" placeholder="Ej: Descuento 8% en 1/8 de pollo">
                    <small style="color: #666; font-size: 10px; display: block; margin-top: 3px;">Ejemplo: "Vale por 1/4 de pollo" o "Descuento del 8% en 1/8 de pollo"</small>
                </div>
            </div>

            <div class="grid-form">
                <div class="form-group">
                    <label>Fecha de Inicio *</label>
                    <input type="datetime-local" name="fecha_inicio" required 
                           value="<?php echo isset($c_edit['fecha_inicio']) ? date('Y-m-d\TH:i', strtotime($c_edit['fecha_inicio'])) : date('Y-m-d\TH:i'); ?>" 
                           class="form-control">
                </div>
                <div class="form-group">
                    <label>Fecha de Expiración *</label>
                    <input type="datetime-local" name="fecha_expiracion" required 
                           value="<?php echo isset($c_edit['fecha_expiracion']) ? date('Y-m-d\TH:i', strtotime($c_edit['fecha_expiracion'])) : date('Y-m-d\TH:i', strtotime('+30 days')); ?>" 
                           class="form-control">
                </div>
            </div>

            <div style="background:#f9f9f9; padding:15px; border-radius:8px; display:grid; grid-template-columns: 1fr 1fr; gap:15px; align-items:center; margin-bottom:25px; border:1px solid #eee;">
                <label style="cursor:pointer; font-size:14px; display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="estado" <?php echo (!isset($c_edit) || $c_edit['estado']) ? 'checked' : ''; ?>> 
                    🟢 Cupón Activo
                </label>
                <?php if(empty($c_edit)): ?>
                    <div class="form-group" style="margin:0;">
                        <label style="margin:0; font-size:12px;">Cantidad a generar:</label>
                        <input type="number" name="cantidad_codigos" min="1" max="1000" value="1" 
                               class="form-control" style="padding:8px; margin-top:3px;">
                        <small style="color: #666; font-size: 10px;">Máximo 1000 cupones por lote</small>
                    </div>
                <?php else: ?>
                    <div class="form-group" style="margin:0;">
                        <label style="margin:0; font-size:12px;">Código actual:</label>
                        <div class="codigo-card"><strong><?php echo $c_edit['codigo']; ?></strong></div>
                        <small style="color: #666; font-size: 10px; display: block;">Los códigos no se pueden modificar</small>
                    </div>
                <?php endif; ?>
            </div>

            <div style="border-top:1px solid #eee; padding-top:20px; text-align:right;">
                <a href="admin.php?mod=cupones" style="padding:12px 25px; border-radius:6px; background:#f5f5f5; color:#333; text-decoration:none; margin-right:10px; font-weight:bold; display:inline-block;">CANCELAR</a>
                <button type="button" onclick="abrirConfirmar()" style="padding:12px 35px; border:none; background:#000; color:#D4AF37; cursor:pointer; font-weight:bold; border-radius:6px; font-size:15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <?php echo $c_edit ? "GUARDAR CAMBIOS" : "CREAR CUPÓN"; ?>
                </button>
                <input type="submit" name="btn_guardar_cupon" id="submitReal" style="display:none;">
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN -->
<div id="modalConfirm" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:10000; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:35px; border-radius:15px; width:400px; text-align:center; border-top:8px solid #D4AF37; box-shadow:0 20px 50px rgba(0,0,0,0.5);">
        <div style="font-size:50px; color:#D4AF37; margin-bottom:15px;">❓</div>
        <h2 id="cTitle" style="margin-top:0; color:#000;">¿Confirmar Acción?</h2>
        <p id="cText" style="color:#666; font-size:15px; margin-bottom:30px;"></p>
        <div style="display:flex; justify-content:center; gap:15px;">
            <button onclick="cerrarConfirmar()" style="padding:12px 25px; border:none; background:#eee; color:#333; cursor:pointer; border-radius:8px; font-weight:bold;">No, revisar</button>
            <button onclick="finalizarEnvio()" style="padding:12px 30px; border:none; background:#000; color:#D4AF37; cursor:pointer; border-radius:8px; font-weight:bold; font-size:16px;">Sí, confirmar</button>
        </div>
    </div>
</div>

<script>
    const estaEditando = <?php echo $c_edit ? 'true' : 'false'; ?>;

    function abrirModalNuevo() {
        if(estaEditando) { window.location.href = 'admin.php?mod=cupones'; return; }
        document.getElementById('modalCupon').style.display = 'flex';
        document.getElementById('mTitle').innerText = "Registrar Nuevo Cupón";
        document.getElementById('formC').reset();
        document.getElementById('id_cupon').value = "";
        const ahora = new Date();
        const en30Dias = new Date(ahora.getTime() + 30*24*60*60*1000);
        document.querySelector('input[name="fecha_inicio"]').value = formatoFechaLocal(ahora);
        document.querySelector('input[name="fecha_expiracion"]').value = formatoFechaLocal(en30Dias);
        document.querySelector('input[name="cantidad_codigos"]').value = 1;
    }

    function formatoFechaLocal(fecha) {
        const year = fecha.getFullYear();
        const month = String(fecha.getMonth() + 1).padStart(2, '0');
        const day = String(fecha.getDate()).padStart(2, '0');
        const hours = String(fecha.getHours()).padStart(2, '0');
        const minutes = String(fecha.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    function cerrarModalCupon() {
        document.getElementById('modalCupon').style.display = 'none';
        if(estaEditando) window.location.href = 'admin.php?mod=cupones';
    }

    function abrirConfirmar() {
        const tipo = document.querySelector('select[name="tipo_cupon_id"]');
        const desc = document.querySelector('input[name="descripcion"]');
        const cantidad = document.querySelector('input[name="cantidad_codigos"]');
        const id = document.getElementById('id_cupon').value;
        if(tipo.value === "") { alert('Por favor selecciona un tipo de cupón.'); tipo.focus(); return; }
        if(!document.getElementById('formC').checkValidity()) { document.getElementById('formC').reportValidity(); return; }
        const nombreTipo = tipo.options[tipo.selectedIndex]?.text || 'Seleccionado';
        const esMasivo = !id && cantidad && parseInt(cantidad.value) > 1;
        const title = id ? "❓ ¿Confirmar Cambios?" : "❓ ¿Crear Cupón(es)?";
        let text = esMasivo ? `¿Deseas generar ${cantidad.value} cupones del tipo "${nombreTipo}"?`
                 : id ? `¿Estás seguro de guardar los cambios del cupón?`
                 : `¿Deseas crear un nuevo cupón del tipo "${nombreTipo}"?`;
        if(desc.value.trim()) text += `\nDescripción: "${desc.value.trim()}"`;
        document.getElementById('cTitle').innerText = title;
        document.getElementById('cText').innerText = text;
        document.getElementById('modalConfirm').style.display = 'flex';
    }

    function cerrarConfirmar() { document.getElementById('modalConfirm').style.display = 'none'; }
    function finalizarEnvio() { cerrarConfirmar(); document.getElementById('submitReal').click(); }
</script>
