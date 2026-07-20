<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// A. GUARDAR (INSERTAR o ACTUALIZAR)
if (isset($_POST['btn_guardar_suc'])) {
    $id = $_POST['id_sucursal'] ?? '';
    $nombre = $_POST['nombre'];
    $pais = $_POST['pais'];
    $direccion = $_POST['direccion'];
    $lat = !empty($_POST['latitud']) ? $_POST['latitud'] : null;
    $lng = !empty($_POST['longitud']) ? $_POST['longitud'] : null;
    $h_ape = !empty($_POST['hora_apertura']) ? $_POST['hora_apertura'] : null;
    $h_cie = !empty($_POST['hora_cierre']) ? $_POST['hora_cierre'] : null;
    $visible = isset($_POST['visible']) ? 1 : 0;

    if (empty($id)) {
        // Al insertar, el estado (Soft Delete) siempre nace en 1 (Activo)
        $sql = "INSERT INTO sucursales (nombre, pais, direccion, latitud, longitud, hora_apertura, hora_cierre, estado, visible) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $pais, $direccion, $lat, $lng, $h_ape, $h_cie, $visible]);
        $id = $pdo->lastInsertId();
        registrarLog($pdo, "INSERTAR_SUCURSAL", "Se creó la sucursal: $nombre");
    } else {
        // Al actualizar, NO tocamos el estado, solo la visibilidad y los datos
        $sql = "UPDATE sucursales SET nombre=?, pais=?, direccion=?, latitud=?, longitud=?, hora_apertura=?, hora_cierre=?, visible=? WHERE id=?";
        $pdo->prepare($sql)->execute([$nombre, $pais, $direccion, $lat, $lng, $h_ape, $h_cie, $visible, $id]);
        registrarLog($pdo, "EDITAR_SUCURSAL", "Se actualizaron los datos de: $nombre (ID: $id)");
    }

    // MANEJO DE FOTOS SECUNDARIAS (Si aplicas tabla sucursal_fotos)
    if (!empty($_FILES['fotos']['name'][0])) {
        if (!is_dir('uploads/sucursales')) { 
            mkdir('uploads/sucursales', 0777, true); 
        }
        
        foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['fotos']['size'][$key] <= 8 * 1024 * 1024) { 
                $clean_name = preg_replace("/[^a-zA-Z0-9.]/", "_", $_FILES['fotos']['name'][$key]);
                $filename = time() . "_" . $clean_name;
                $ruta_destino = "uploads/sucursales/" . $filename;

                if (move_uploaded_file($tmp_name, $ruta_destino)) {
                    $sql_foto = "INSERT INTO sucursal_fotos (sucursal_id, ruta_foto, estado) VALUES (?, ?, 1)";
                    $pdo->prepare($sql_foto)->execute([$id, $ruta_destino]);
                }
            }
        }
    }
    
    echo "<script>window.location='admin.php?mod=sucursales';</script>";
    exit;
}

// B. TOGGLE VISIBLE (Visible en Web)
if (isset($_GET['toggle_visible'])) {
    $id = $_GET['toggle_visible'];
    $status = $_GET['status'];
    $pdo->prepare("UPDATE sucursales SET visible = ? WHERE id = ?")->execute([$status, $id]);
    registrarLog($pdo, "TOGGLE_VISIBLE_SUCURSAL", "Sucursal ID: $id - Visible Web: $status");
    echo "<script>window.location='admin.php?mod=sucursales';</script>";
    exit;
}

// C. ELIMINAR SUCURSAL (SOFT DELETE)
if (isset($_GET['del'])) {
    $id_del = $_GET['del'];
    // Cambiamos estado a 0. Opcional: también podrías poner visible = 0
    $pdo->prepare("UPDATE sucursales SET estado = 0 WHERE id = ?")->execute([$id_del]);
    registrarLog($pdo, "ELIMINAR_SUCURSAL", "Se eliminó (Soft Delete) la sucursal ID: $id_del");
    echo "<script>window.location='admin.php?mod=sucursales';</script>";
    exit;
}

// D. ELIMINAR FOTO ESPECÍFICA
if (isset($_GET['del_foto'])) {
    $foto_id = $_GET['del_foto'];
    $suc_id = $_GET['suc_id'];
    $pdo->prepare("UPDATE sucursal_fotos SET estado = 0 WHERE id = ?")->execute([$foto_id]);
    registrarLog($pdo, "ELIMINAR_FOTO_SUCURSAL", "Se eliminó foto ID: $foto_id");
    echo "<script>window.location='admin.php?mod=sucursales&edit=$suc_id';</script>";
    exit;
}

// DATOS PARA EDITAR (Cargar datos si hay ?edit=ID)
$s_edit = null;
$fotos_edit = [];
if (isset($_GET['edit'])) {
    $id_editar = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM sucursales WHERE id = ?");
    $stmt->execute([$id_editar]);
    $s_edit = $stmt->fetch();

    if ($s_edit) {
        $stmt_f = $pdo->prepare("SELECT * FROM sucursal_fotos WHERE sucursal_id = ? AND estado = 1 ORDER BY id ASC");
        $stmt_f->execute([$id_editar]);
        $fotos_edit = $stmt_f->fetchAll();
    }
}

// LISTADO DE SUCURSALES (Solo traemos las que tienen estado=1 para respetar el soft delete)
$sucursales = $pdo->query("SELECT * FROM sucursales WHERE estado = 1 ORDER BY id DESC")->fetchAll();
?>

<style>
    /* Modal Fullscreen */
    .modal-full { display:<?php echo $s_edit ? 'flex' : 'none'; ?>; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:flex-start; overflow-y:auto; padding:40px 20px; box-sizing: border-box;}
    .modal-container { background:#fff; width:100%; max-width:900px; border-radius:12px; border-top:8px solid #D4AF37; padding:30px; position:relative; box-shadow: 0 15px 40px rgba(0,0,0,0.5); margin-bottom: 40px;}
    
    /* Grid y Formularios */
    .grid-form { display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom: 20px;}
    .form-group label { display:block; font-weight:bold; font-size:13px; color:#555; margin-bottom:5px; }
    .form-control { width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; box-sizing: border-box; font-family: inherit;}
    
    /* Botones Estilo Pastilla */
    .btn-pastilla { padding: 6px 15px; border-radius: 20px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer;}
    .btn-edit { background: #e3f2fd; color: #0d47a1; }
    .btn-edit:hover { background: #bbdefb; }
    .btn-delete { background: #ffebee; color: #c62828; }
    .btn-delete:hover { background: #ffcdd2; }
    .btn-visible { background: #e8f5e9; color: #1b5e20; }
    .btn-visible:hover { background: #c8e6c9; }
    .btn-hidden { background: #f5f5f5; color: #616161; }
    .btn-hidden:hover { background: #e0e0e0; }
    
    /* Zonas de carga */
    .drop-zone { border: 2px dashed #ccc; padding: 20px; text-align: center; cursor: pointer; border-radius: 8px; transition: 0.3s; background: #fafafa; position: relative; min-height: 90px; display: flex; flex-direction: column; justify-content: center; align-items: center;}
    .drop-zone:hover { border-color: #D4AF37; background: #fffcf0; }
    .drop-zone span { font-size: 12px; font-weight: bold; color: #777; }
    .drop-zone small { font-size: 10px; color: #aaa; margin-top: 5px;}
    
    .fotos-grid { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
    .foto-item { position: relative; border: 1px solid #ddd; border-radius: 6px; overflow: hidden; }
    .foto-item img { width: 80px; height: 60px; object-fit: cover; }
    .btn-del-foto { position: absolute; top: -8px; right: -8px; background: #c62828; color: white; border-radius: 50%; width: 20px; height: 20px; text-align: center; text-decoration: none; font-size: 12px; line-height: 20px; font-weight: bold;}
    
    .checkbox-group { display: flex; gap: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px; margin: 15px 0; border: 1px solid #eee;}
    .checkbox-group label { cursor: pointer; font-weight: normal; font-size: 14px; color: #333;}
    
    @media (max-width: 768px) {
        .grid-form { grid-template-columns: 1fr; gap: 10px; }
        .modal-container { padding: 20px; margin: 0 15px; }
    }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; padding-bottom:10px; border-bottom:1px solid #eee;">
    <h2 style="margin:0;">📍 Gestión de Sucursales</h2>
    <button onclick="abrirModalNuevo()" style="background:#000; color:#D4AF37; padding:12px 25px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; box-shadow: 0 4px 6px rgba(0,0,0,0.2);">
        + AGREGAR SUCURSAL
    </button>
</div>

<table style="width:100%; border-collapse: collapse; background:#fff; border-radius:8px; overflow:hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <thead>
        <tr style="background:#1a1a1a; color:#D4AF37; text-align:left; font-size:13px;">
            <th style="padding:18px;">Sucursal</th>
            <th>País</th>
            <th>Dirección</th>
            <th>Horario</th>
            <th style="text-align:center;">Visible en Web</th>
            <th style="text-align:right; padding-right:18px;">Acciones</th>
         </tr>
    </thead>
    <tbody>
        <?php foreach ($sucursales as $s): ?>
        <tr style="border-bottom: 1px solid #f2f2f2; transition: 0.2s;" onmouseover="this.style.background='#fbfbfb'" onmouseout="this.style.background='#fff'">
            <td style="padding:15px;"><strong style="color:#000; font-size:15px;"><?php echo htmlspecialchars($s['nombre']); ?></strong></td>
            <td style="padding:15px; color:#555;"><?php echo $s['pais']; ?></td>
            <td style="padding:15px; font-size:13px; color:#666;"><?php echo htmlspecialchars(substr($s['direccion'], 0, 40)); ?>…</td>
            <td style="padding:15px; font-size:13px; color:#555;">
                <?php echo ($s['hora_apertura'] && $s['hora_cierre']) ? $s['hora_apertura'] . " - " . $s['hora_cierre'] : '<i style="color:#ccc;">No asignado</i>'; ?>
            </td>
             <td style="text-align:center;">
                <?php if($s['visible']): ?>
                    <a href="admin.php?mod=sucursales&toggle_visible=<?php echo $s['id']; ?>&status=0" class="btn-pastilla btn-visible" title="Click para ocultar">👁️ Sí</a>
                <?php else: ?>
                    <a href="admin.php?mod=sucursales&toggle_visible=<?php echo $s['id']; ?>&status=1" class="btn-pastilla btn-hidden" title="Click para mostrar">👁️ No</a>
                <?php endif; ?>
             </td>
             <td style="text-align:right; padding-right:18px; white-space:nowrap;">
                <a href="admin.php?mod=sucursales&edit=<?php echo $s['id']; ?>" class="btn-pastilla btn-edit">✏️ Editar</a>
                <a href="admin.php?mod=sucursales&del=<?php echo $s['id']; ?>" class="btn-pastilla btn-delete" onclick="return confirm('¿Estás seguro de eliminar (ocultar) la sucursal: <?php echo $s['nombre']; ?>?')">🗑️ Borrar</a>
             </td>
         </tr>
        <?php endforeach; ?>
        <?php if(empty($sucursales)): ?>
        <tr><td colspan="6" style="text-align:center; padding:40px; color:#aaa;">No hay sucursales registradas. ¡Agrega la primera!</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div id="modalSuc" class="modal-full">
    <div class="modal-container">
        <a href="admin.php?mod=sucursales" style="position:absolute; top:15px; right:20px; cursor:pointer; font-size:28px; text-decoration:none; color:#aaa;">&times;</a>
        
        <h3 id="mTitle" style="margin-top:0; color:#000; border-bottom:2px solid #D4AF37; display:inline-block; padding-bottom:5px;">
            <?php echo $s_edit ? "Editar Sucursal: ".$s_edit['nombre'] : "Registrar Nueva Sucursal"; ?>
        </h3>
        
        <form id="formS" method="POST" enctype="multipart/form-data" style="margin-top:25px;" onkeydown="return event.key != 'Enter';">
            <input type="hidden" name="id_sucursal" id="id_suc" value="<?php echo $s_edit['id'] ?? ''; ?>">
            
            <div class="grid-form">
                <div class="form-group">
                    <label>📌 Nombre de Sucursal *</label>
                    <input type="text" name="nombre" required value="<?php echo $s_edit['nombre'] ?? ''; ?>" class="form-control" placeholder="Ej: Sucursal Centro">
                </div>
                <div class="form-group">
                    <label>🌎 País *</label>
                    <select name="pais" required class="form-control">
                        <option value="Bolivia" <?php echo (isset($s_edit['pais']) && $s_edit['pais'] == 'Bolivia') ? 'selected' : ''; ?>>Bolivia</option>
                        <option value="Paraguay" <?php echo (isset($s_edit['pais']) && $s_edit['pais'] == 'Paraguay') ? 'selected' : ''; ?>>Paraguay</option>
                        <option value="Brasil" <?php echo (isset($s_edit['pais']) && $s_edit['pais'] == 'Brasil') ? 'selected' : ''; ?>>Brasil</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>📍 Dirección</label>
                    <input type="text" name="direccion" value="<?php echo $s_edit['direccion'] ?? ''; ?>" class="form-control" placeholder="Avenida y calle...">
                </div>
                <div class="form-group">
                    <label>🗺️ Latitud</label>
                    <input type="text" name="latitud" placeholder="-17.7833" value="<?php echo $s_edit['latitud'] ?? ''; ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>🗺️ Longitud</label>
                    <input type="text" name="longitud" placeholder="-63.1821" value="<?php echo $s_edit['longitud'] ?? ''; ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label>🕐 Horario de Atención</label>
                    <div style="display:flex; gap:10px;">
                        <input type="time" name="hora_apertura" value="<?php echo $s_edit['hora_apertura'] ?? ''; ?>" class="form-control" title="Apertura">
                        <input type="time" name="hora_cierre" value="<?php echo $s_edit['hora_cierre'] ?? ''; ?>" class="form-control" title="Cierre">
                    </div>
                </div>
            </div>
            
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="visible" <?php echo (!isset($s_edit) || $s_edit['visible'] == 1) ? 'checked' : ''; ?>> 
                    🌐 Mostrar públicamente esta sucursal en la Página Web
                </label>
            </div>
            
            <div class="form-group">
                <label>📷 Galería de Fotos (Opcional)</label>
                <div class="drop-zone" onclick="document.getElementById('input-fotos').click()">
                    <span>📷 Haz clic aquí para añadir fotos</span>
                    <small>Admite JPG, PNG. Máx 8MB. Selecciona varias a la vez.</small>
                    <input type="file" id="input-fotos" name="fotos[]" multiple accept="image/jpeg, image/png" style="display:none;">
                    <div id="preview-nuevas" class="fotos-grid" style="margin-top:10px;"></div>
                </div>
            </div>
            
            <?php if($s_edit && !empty($fotos_edit)): ?>
                <div style="margin-top:15px; background:#fbfbfb; padding:15px; border-radius:8px; border:1px solid #ddd;">
                    <label style="font-size:12px; font-weight:bold; color:#555; margin-bottom:10px; display:block;">📷 Fotos guardadas actualmente:</label>
                    <div class="fotos-grid">
                        <?php foreach($fotos_edit as $f): ?>
                            <div class="foto-item">
                                <img src="<?php echo $f['ruta_foto']; ?>">
                                <a href="admin.php?mod=sucursales&del_foto=<?php echo $f['id']; ?>&suc_id=<?php echo $s_edit['id']; ?>" class="btn-del-foto" title="Eliminar foto" onclick="return confirm('¿Eliminar esta foto permanentemente?')">×</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div style="border-top:1px solid #eee; padding-top:20px; margin-top:20px; text-align:right;">
                <a href="admin.php?mod=sucursales" style="padding:12px 25px; border-radius:6px; background:#f5f5f5; color:#333; text-decoration:none; margin-right:10px; font-weight:bold;">CANCELAR</a>
                <button type="submit" name="btn_guardar_suc" style="padding:12px 35px; border:none; background:#000; color:#D4AF37; cursor:pointer; font-weight:bold; border-radius:6px; box-shadow: 0 4px 6px rgba(0,0,0,0.2);">💾 GUARDAR SUCURSAL</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalNuevo() {
    if(<?php echo $s_edit ? 'true' : 'false'; ?>) {
        window.location.href = 'admin.php?mod=sucursales';
        return;
    }
    document.getElementById('modalSuc').style.display = 'flex';
    document.getElementById('mTitle').innerText = "Registrar Nueva Sucursal";
    document.getElementById('formS').reset();
    document.getElementById('id_suc').value = '';
    document.getElementById('preview-nuevas').innerHTML = '';
}

// Previsualización de fotos múltiples
document.getElementById('input-fotos')?.addEventListener('change', function() {
    const container = document.getElementById('preview-nuevas');
    container.innerHTML = '';
    
    Array.from(this.files).forEach(file => {
        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            alert(`❌ El archivo ${file.name} no es válido. Solo permitimos JPG o PNG.`);
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'foto-item';
            div.innerHTML = `<img src="${e.target.result}" style="width:80px; height:60px;"><div style="position:absolute; top:-8px; right:-8px; background:#D4AF37; border-radius:50%; width:18px; height:18px; text-align:center; font-size:10px; line-height:18px; font-weight:bold; color:#000;">N</div>`;
            container.appendChild(div);
        }
        reader.readAsDataURL(file);
    });
});
</script>