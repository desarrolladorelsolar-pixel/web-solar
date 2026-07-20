<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 1. LÓGICA DE PROCESAMIENTO ---
if (isset($_POST['btn_guardar_popup'])) {
    $id = $_POST['id_popup'] ?? '';
    $nombre = $_POST['nombre'];
    $url = $_POST['url_destino'];
    $desc = $_POST['descripcion'];
    $f_inicio = $_POST['fecha_inicio'];
    $f_fin = $_POST['fecha_fin'];
    $h_inicio = $_POST['hora_inicio'];
    $h_cierre = $_POST['hora_cierre'];
    $visible = isset($_POST['visible']) ? 1 : 0;

    // Días de la semana (Booleanos)
    $lunes = isset($_POST['lunes']) ? 1 : 0;
    $martes = isset($_POST['martes']) ? 1 : 0;
    $miercoles = isset($_POST['miercoles']) ? 1 : 0;
    $jueves = isset($_POST['jueves']) ? 1 : 0;
    $viernes = isset($_POST['viernes']) ? 1 : 0;
    $sabado = isset($_POST['sabado']) ? 1 : 0;
    $domingo = isset($_POST['domingo']) ? 1 : 0;

    if (empty($id)) {
        $sql = "INSERT INTO popups (nombre, url_destino, descripcion, fecha_inicio, fecha_fin, lunes, martes, miercoles, jueves, viernes, sabado, domingo, hora_inicio, hora_cierre, visible) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $url, $desc, $f_inicio, $f_fin, $lunes, $martes, $miercoles, $jueves, $viernes, $sabado, $domingo, $h_inicio, $h_cierre, $visible]);
        $id = $pdo->lastInsertId();
        registrarLog($pdo, "INSERTAR_POPUP", "Popup creado: $nombre");
    } else {
        $sql = "UPDATE popups SET nombre=?, url_destino=?, descripcion=?, fecha_inicio=?, fecha_fin=?, lunes=?, martes=?, miercoles=?, jueves=?, viernes=?, sabado=?, domingo=?, hora_inicio=?, hora_cierre=?, visible=? WHERE id=?";
        $pdo->prepare($sql)->execute([$nombre, $url, $desc, $f_inicio, $f_fin, $lunes, $martes, $miercoles, $jueves, $viernes, $sabado, $domingo, $h_inicio, $h_cierre, $visible, $id]);
        registrarLog($pdo, "ACTUALIZAR_POPUP", "Popup actualizado: $nombre");
    }

    // --- MANEJO DE LA FOTO ---
    if (!empty($_FILES['foto']['name'])) {
        if (!is_dir('uploads/popups')) { mkdir('uploads/popups', 0777, true); }
        $filename = time() . "_popup_" . $_FILES['foto']['name'];
        $destino = "uploads/popups/" . $filename;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
            $pdo->prepare("UPDATE popups SET ruta_foto = ? WHERE id = ?")->execute([$destino, $id]);
        }
    }
    echo "<script>window.location='admin.php?mod=popups';</script>";
}

// Lógica de borrar
if (isset($_GET['del'])) {
    $pdo->prepare("DELETE FROM popups WHERE id = ?")->execute([$_GET['del']]);
    echo "<script>window.location='admin.php?mod=popups';</script>";
    registrarLog($pdo, "ELIMINAR_POPUP", "Popup eliminado");
}

// Consulta para la tabla
$popups = $pdo->query("SELECT * FROM popups ORDER BY id DESC")->fetchAll();

// Cargar datos para editar
$p_edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM popups WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $p_edit = $stmt->fetch();
}
?>

<style>
    .modal-full { display:<?php echo $p_edit ? 'flex' : 'none'; ?>; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:999; justify-content:center; align-items:flex-start; overflow-y:auto; padding:20px; }
    .modal-container { background:#fff; width:100%; max-width:700px; border-radius:12px; border-top:8px solid #D4AF37; padding:25px; position:relative; margin-top:30px; }
    .grid-days { display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 10px; background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee; }
    .day-check { font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 5px; }
    .btn-pastilla { padding: 5px 12px; border-radius: 20px; text-decoration: none; font-size: 11px; font-weight: bold; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h2>📢 Gestión de Popups Publicitarios</h2>
    <button onclick="abrirModal()" style="background:#000; color:#D4AF37; padding:12px 20px; border:none; border-radius:6px; cursor:pointer; font-weight:bold;">+ NUEVO POPUP</button>
</div>

<table style="width:100%; border-collapse: collapse; background:#fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <tr style="background:#1a1a1a; color:#D4AF37; text-align:left;">
        <th style="padding:15px;">Imagen</th>
        <th>Nombre / Campaña</th>
        <th>Rango Fechas</th>
        <th>Estado</th>
        <th style="text-align:right; padding-right:15px;">Acciones</th>
    </tr>
    <?php foreach ($popups as $p): ?>
    <tr style="border-bottom: 1px solid #eee;">
        <td style="padding:10px;"><img src="<?php echo $p['ruta_foto']; ?>" style="width:60px; height:60px; object-fit:cover; border-radius:5px;"></td>
        <td><strong><?php echo $p['nombre']; ?></strong><br><small><?php echo $p['url_destino']; ?></small></td>
        <td><small><?php echo $p['fecha_inicio']; ?> al <?php echo $p['fecha_fin']; ?></small></td>
        <td><?php echo $p['visible'] ? '<span style="color:green">● Activo</span>' : '<span style="color:red">● Oculto</span>'; ?></td>
        <td style="text-align:right; padding-right:15px;">
            <a href="admin.php?mod=popups&edit=<?php echo $p['id']; ?>" class="btn-pastilla" style="background:#e3f2fd; color:#0d47a1;">Editar</a>
            <a href="admin.php?mod=popups&del=<?php echo $p['id']; ?>" class="btn-pastilla" style="background:#ffebee; color:#c62828;" onclick="return confirm('¿Eliminar popup?')">Borrar</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<div id="modalPop" class="modal-full">
    <div class="modal-container">
        <a href="admin.php?mod=popups" style="float:right; text-decoration:none; color:#aaa; font-size:24px;">&times;</a>
        <h3><?php echo $p_edit ? 'Editar Popup' : 'Crear Nuevo Popup'; ?></h3>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_popup" value="<?php echo $p_edit['id'] ?? ''; ?>">
            
            <div class="form-group">
                <label>Nombre Interno</label>
                <input type="text" name="nombre" class="form-control" required value="<?php echo $p_edit['nombre'] ?? ''; ?>" placeholder="Ej: Black Friday Abril">
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label>Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" required value="<?php echo $p_edit['fecha_inicio'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="form-control" required value="<?php echo $p_edit['fecha_fin'] ?? ''; ?>">
                </div>
            </div>

            <label style="font-size:13px; font-weight:bold; margin-bottom:5px; display:block;">Días de la semana que se mostrará:</label>
            <div class="grid-days">
                <?php 
                $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
                foreach($dias as $d): 
                    $checked = ($p_edit && $p_edit[$d]) ? 'checked' : ( !$p_edit ? 'checked' : '' );
                ?>
                <label class="day-check">
                    <input type="checkbox" name="<?php echo $d; ?>" <?php echo $checked; ?>> <?php echo ucfirst($d); ?>
                </label>
                <?php endforeach; ?>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px;">
                <div class="form-group">
                    <label>Hora Inicio</label>
                    <input type="time" name="hora_inicio" class="form-control" value="<?php echo $p_edit['hora_inicio'] ?? '00:00'; ?>">
                </div>
                <div class="form-group">
                    <label>Hora Cierre</label>
                    <input type="time" name="hora_cierre" class="form-control" value="<?php echo $p_edit['hora_cierre'] ?? '23:59'; ?>">
                </div>
            </div>

            <div class="form-group">
                <label>URL de Redirección (Opcional)</label>
                <input type="url" name="url_destino" class="form-control" value="<?php echo $p_edit['url_destino'] ?? ''; ?>" placeholder="https://tuweb.com/oferta">
            </div>

            <div class="form-group">
                <label>Imagen del Popup (Diseño sugerido 800x800px)</label>
                <input type="file" name="foto" class="form-control" accept="image/*" <?php echo $p_edit ? '' : 'required'; ?>>
                <?php if($p_edit): ?>
                    <img src="<?php echo $p_edit['ruta_foto']; ?>" style="width:100px; margin-top:10px; border-radius:5px;">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label><input type="checkbox" name="visible" <?php echo (!$p_edit || $p_edit['visible']) ? 'checked' : ''; ?>> Popup Visible</label>
            </div>

            <div style="text-align:right; border-top:1px solid #eee; padding-top:20px;">
                <a href="admin.php?mod=popups" style="padding:10px 20px; color:#666; text-decoration:none;">Cancelar</a>
                <button type="submit" name="btn_guardar_popup" style="background:#000; color:#D4AF37; padding:10px 30px; border:none; border-radius:5px; font-weight:bold; cursor:pointer;">GUARDAR POPUP</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModal() { document.getElementById('modalPop').style.display = 'flex'; }
</script>