<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['btn_guardar_prod'])) {
    $id = $_POST['id_producto'] ?? '';
    $cat_id = $_POST['categoria_id'];
    // Si no se selecciona sucursal, asumimos NULL o la primera por defecto
    $suc_id = !empty($_POST['sucursal_id']) ? $_POST['sucursal_id'] : null;
    $nombre = $_POST['nombre'];
    $desc = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $moneda = $_POST['moneda'];
    $es_combo = isset($_POST['es_combo']) ? 1 : 0;
    $dia = $_POST['dia_semana'];
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    $visible = isset($_POST['visible']) ? 1 : 0; // Nuevo campo visible
    $precio_oferta = !empty($_POST['precio_oferta']) ? $_POST['precio_oferta'] : null;
    $etiqueta = $_POST['etiqueta_oferta'];

    if (empty($id)) {
        $sql = "INSERT INTO productos (categoria_id, sucursal_id, nombre, descripcion, precio, precio_oferta, etiqueta_oferta, moneda, es_combo, dia_semana, destacado, visible) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cat_id, $suc_id, $nombre, $desc, $precio, $precio_oferta, $etiqueta, $moneda, $es_combo, $dia, $destacado, $visible]);
        $id = $pdo->lastInsertId();
        registrarLog($pdo, "INSERTAR_PRODUCTO", "Producto creado: $nombre");
    } else {
        // --- ACTUALIZAR EXISTENTE ---
        $sql = "UPDATE productos SET categoria_id=?, sucursal_id=?, nombre=?, descripcion=?, precio=?, precio_oferta=?, etiqueta_oferta=?, moneda=?, es_combo=?, dia_semana=?, destacado=?, visible=? WHERE id=?";
        $pdo->prepare($sql)->execute([$cat_id, $suc_id, $nombre, $desc, $precio, $precio_oferta, $etiqueta, $moneda, $es_combo, $dia, $destacado, $visible, $id]);
        registrarLog($pdo, "EDITAR_PRODUCTO", "Actualizado: $nombre (ID: $id)");
    }

    // --- MANEJO DE FOTOS (PRINCIPAL, HOVER Y GALERÍA) ---
    // Nomenclatura: 0=Principal, 1=Hover, 2+=Galería
    $tipos_foto = ['principal' => 0, 'hover' => 1, 'galeria' => 2];
    
    foreach ($tipos_foto as $input_name => $orden_f) {
        if (!empty($_FILES[$input_name]['name'][0])) {
            if (!is_dir('uploads/productos')) { mkdir('uploads/productos', 0777, true); }
            
            // Si es Principal o Hover, desactivamos las anteriores de ese tipo para ese producto
            if ($orden_f < 2) {
                $pdo->prepare("UPDATE producto_fotos SET estado = 0 WHERE producto_id = ? AND orden = ?")->execute([$id, $orden_f]);
            }

            $files = $_FILES[$input_name];
            // PHP maneja diferente si es single o multiple file input
            $count = is_array($files['name']) ? count($files['name']) : 1;

            for ($i = 0; $i < $count; $i++) {
                $tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                
                if ($error === UPLOAD_ERR_OK && !empty($tmp)) {
                    // Nombre único: tiempo + tipo + nombre original limpiado
                    $clean_name = preg_replace("/[^a-zA-Z0-9.]/", "_", $name);
                    $filename = time() . "_" . $input_name . "_" . $clean_name;
                    $destino = "uploads/productos/" . $filename;
                    
                    if (move_uploaded_file($tmp, $destino)) {
                        $sql_f = "INSERT INTO producto_fotos (producto_id, ruta_foto, orden, estado) VALUES (?, ?, ?, 1)";
                        // Si es galería, el orden empieza en 2 y sube
                        $orden_final = ($orden_f == 2) ? 2 + $i : $orden_f;
                        $pdo->prepare($sql_f)->execute([$id, $destino, $orden_final]);
                    }
                }
            }
        }
    }
    echo "<script>window.location='admin.php?mod=productos';</script>";
    exit;
}

// B. ELIMINAR FOTO ESPECÍFICA (Vía GET para AJAX o enlace rápido)
if (isset($_GET['del_foto'])) {
    $foto_id = $_GET['del_foto'];
    $prod_id = $_GET['prod_id'];
    $pdo->prepare("UPDATE producto_fotos SET estado = 0 WHERE id = ?")->execute([$foto_id]);
    registrarLog($pdo, "ELIMINAR_FOTO_PRODUCTO", "Foto ID: $foto_id del Producto ID: $prod_id");
    // Redirigir de vuelta al modo edición
    echo "<script>window.location='admin.php?mod=productos&edit=$prod_id';</script>";
    exit;
}

// C. CAMBIAR VISIBILIDAD RÁPIDA (Vía GET)
if (isset($_GET['toggle_visible'])) {
    $id_vis = $_GET['toggle_visible'];
    $status = $_GET['status'];
    $pdo->prepare("UPDATE productos SET visible = ? WHERE id = ?")->execute([$status, $id_vis]);
    registrarLog($pdo, "VISIBILIDAD_PRODUCTO", "Producto ID: $id_vis establecido en visible=$status");
    echo "<script>window.location='admin.php?mod=productos';</script>";
    exit;
}

// D. ELIMINAR PRODUCTO (SOFT DELETE)
if (isset($_GET['del'])) {
    $id_del = $_GET['del'];
    $pdo->prepare("UPDATE productos SET estado = 0 WHERE id = ?")->execute([$id_del]); // O usar campo estado si lo tienes
    registrarLog($pdo, "ELIMINAR_PRODUCTO", "ID: $id_del (Seteado como no visible)");
    echo "<script>window.location='admin.php?mod=productos';</script>";
    exit;
}


// --- 2. CONSULTAS PARA INTERFAZ (Frontend) ---
$categorias = $pdo->query("SELECT id, nombre FROM categorias WHERE estado = 1 ORDER BY nombre ASC")->fetchAll();
$sucursales = $pdo->query("SELECT id, nombre FROM sucursales WHERE estado = 1 ORDER BY nombre ASC")->fetchAll();

// Traemos productos, su categoría y su foto principal (orden=0)
$sql_prod = "SELECT p.*, c.nombre as cat_nom, 
             (SELECT ruta_foto FROM producto_fotos WHERE producto_id = p.id AND orden = 0 AND estado = 1 LIMIT 1) as foto_id
             FROM productos p 
             LEFT JOIN categorias c ON p.categoria_id = c.id 
             WHERE p.estado = 1
             ORDER BY p.id DESC";
$productos = $pdo->query($sql_prod)->fetchAll();


// --- 3. LÓGICA DE EDICIÓN (Cargar datos si hay ?edit=ID) ---
$p_edit = null;
$fotos_edit = ['principal' => null, 'hover' => null, 'galeria' => []];

if (isset($_GET['edit'])) {
    $id_editar = $_GET['edit'];
    // Datos del producto
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$id_editar]);
    $p_edit = $stmt->fetch();

    if ($p_edit) {
        // Fotos del producto (activas)
        $stmt_f = $pdo->prepare("SELECT * FROM producto_fotos WHERE producto_id = ? AND estado = 1 ORDER BY orden ASC");
        $stmt_f->execute([$id_editar]);
        $todas_fotos = $stmt_f->fetchAll();

        foreach ($todas_fotos as $f) {
            if ($f['orden'] == 0) $fotos_edit['principal'] = $f;
            elseif ($f['orden'] == 1) $fotos_edit['hover'] = $f;
            else $fotos_edit['galeria'][] = $f;
        }
    }
}
?>

<style>
    /* Modal Fullscreen */
    .modal-full { display:<?php echo $p_edit ? 'flex' : 'none'; ?>; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:flex-start; overflow-y:auto; padding:40px 20px; box-sizing: border-box;}
    .modal-container { background:#fff; width:100%; max-width:900px; border-radius:12px; border-top:8px solid #D4AF37; padding:30px; position:relative; box-shadow: 0 15px 40px rgba(0,0,0,0.5); margin-bottom: 40px;}
    
    /* Grid y Formularios */
    .grid-form { display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom: 20px;}
    .form-group label { display:block; font-weight:bold; font-size:13px; color:#555; margin-bottom:5px; }
    .form-control { width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; box-sizing: border-box; font-family: inherit;}
    
    /* Botones Estilo Pastilla (Luxury) */
    .btn-pastilla { padding: 6px 15px; border-radius: 20px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer;}
    
    .btn-edit { background: #e3f2fd; color: #0d47a1; }
    .btn-edit:hover { background: #bbdefb; }
    
    .btn-delete { background: #ffebee; color: #c62828; }
    .btn-delete:hover { background: #ffcdd2; }
    
    .btn-visible { background: #e8f5e9; color: #1b5e20; }
    .btn-visible:hover { background: #c8e6c9; }
    
    .btn-hidden { background: #f5f5f5; color: #616161; }
    .btn-hidden:hover { background: #e0e0e0; }

    /* Zonas de Carga de Fotos */
    .drop-zone { border: 2px dashed #ccc; padding: 20px; text-align: center; cursor: pointer; border-radius: 8px; transition: 0.3s; background: #fafafa; position: relative; min-height: 90px; display: flex; flex-direction: column; justify-content: center; align-items: center;}
    .drop-zone:hover { border-color: #D4AF37; background: #fffcf0; }
    .drop-zone span { font-size: 12px; font-weight: bold; color: #777; }
    .drop-zone small { font-size: 10px; color: #aaa; }
    
    /* Previsualización de Fotos Existentes en Editar */
    .current-photo { position: relative; width: 100%; height: 100px; border-radius: 4px; overflow: hidden; border: 1px solid #ddd; margin-top: 10px;}
    .current-photo img { width: 100%; height: 100%; object-fit: cover; }
    .btn-del-photo { position: absolute; top: 5px; right: 5px; background: rgba(255,0,0,0.7); color: #fff; border: none; border-radius: 50%; width: 22px; height: 22px; cursor: pointer; font-size: 14px; line-height: 20px; text-align: center; padding: 0;}
    .btn-del-photo:hover { background: rgba(255,0,0,1); }

</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; padding-bottom:10px; border-bottom:1px solid #eee;">
    <h2 style="margin:0;">📦 Gestión de Inventario de Productos</h2>
    <button onclick="abrirModalNuevo()" style="background:#000; color:#D4AF37; padding:12px 25px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; box-shadow: 0 4px 6px rgba(0,0,0,0.2);">
        + AGREGAR NUEVO PRODUCTO
    </button>
</div>

<table style="width:100%; border-collapse: collapse; background:#fff; border-radius:8px; overflow:hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <thead>
        <tr style="background:#1a1a1a; color:#D4AF37; text-align:left; font-size:13px;">
            <th style="padding:18px; width:60px;">Foto</th>
            <th>Nombre del Producto</th>
            <th>Categoría</th>
            <th>Precio Venta</th>
            <th>Día/Combo</th>
            <th style="text-align:center;">Visible</th>
            <th style="text-align:right; padding-right:18px;">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($productos as $p): ?>
        <tr style="border-bottom: 1px solid #f2f2f2; transition: 0.2s;" onmouseover="this.style.background='#fbfbfb'" onmouseout="this.style.background='#fff'">
            <td style="padding:10px 18px;">
                <?php if ($p['foto_id']): ?>
                    <img src="<?php echo $p['foto_id']; ?>" style="width:50px; height:50px; object-fit:cover; border-radius:4px; border:1px solid #ddd;">
                <?php else: ?>
                    <div style="width:50px; height:50px; background:#eee; border-radius:4px; display:flex; justify-content:center; align-items:center; color:#aaa; font-size:10px;">Sin foto</div>
                <?php if ($p['destacado']): ?>
                    <span style="position:absolute; background:#D4AF37; color:#000; font-size:8px; padding:1px 3px; border-radius:2px; margin-left:-10px; margin-top:-10px;">⭐</span>
                <?php endif; ?>
                <?php endif; ?>
            </td>
            
            <td>
                <span style="font-weight:bold; color:#000; font-size:15px;"><?php echo $p['nombre']; ?></span>
                <?php if($p['es_combo']): ?>
                    <span style="background:#fff3cd; color:#856404; font-size:10px; padding:2px 5px; border-radius:4px; margin-left:5px;">COMBO</span>
                <?php endif; ?>
            </td>
            <td style="color:#666; font-size:14px;"><?php echo $p['cat_nom'] ?? '<i style="color:#ccc">Sin categoría</i>'; ?></td>
            <td style="font-size:16px;">
                <span style="color:#aaa; font-size:12px;"><?php echo $p['moneda']; ?></span> 
                <strong style="color:#1b5e20;"><?php echo number_format($p['precio'], 2); ?></strong>
            </td>
            <td style="font-size:12px; color:#777;"><?php echo $p['dia_semana']; ?></td>
            
            <td style="text-align:center;">
                <?php if($p['visible']): ?>
                    <a href="admin.php?mod=productos&toggle_visible=<?php echo $p['id']; ?>&status=0" class="btn-pastilla btn-visible" title="Click para ocultar">👁️ Sí</a>
                <?php else: ?>
                    <a href="admin.php?mod=productos&toggle_visible=<?php echo $p['id']; ?>&status=1" class="btn-pastilla btn-hidden" title="Click para mostrar">👁️ No</a>
                <?php endif; ?>
            </td>
            
            <td style="text-align:right; padding-right:18px; white-space:nowrap;">
                <a href="admin.php?mod=productos&edit=<?php echo $p['id']; ?>" class="btn-pastilla btn-edit">✏️ Editar</a>
                <a href="admin.php?mod=productos&del=<?php echo $p['id']; ?>" class="btn-pastilla btn-delete" onclick="return confirm('¿Estás seguro de eliminar (u ocultar) el producto: <?php echo $p['nombre']; ?>?')">🗑️ Borrar</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($productos)): ?>
            <tr><td colspan="7" style="text-align:center; padding:40px; color:#aaa;">No hay productos registrados aún. ¡Agrega el primero!</td></tr>
        <?php endif; ?>
    </tbody>
</table>


<div id="modalProd" class="modal-full">
    <div class="modal-container">
        <a href="admin.php?mod=productos" style="position:absolute; top:15px; right:20px; cursor:pointer; font-size:28px; text-decoration:none; color:#aaa;">&times;</a>
        
        <h3 id="mTitle" style="margin-top:0; color:#000; border-bottom:2px solid #D4AF37; display:inline-block; padding-bottom:5px;">
            <?php echo $p_edit ? "Editar Producto: ".$p_edit['nombre'] : "Registrar Nuevo Producto"; ?>
        </h3>
        
        <form id="formP" method="POST" enctype="multipart/form-data" style="margin-top:25px;" onkeydown="return event.key != 'Enter';">
            <input type="hidden" name="id_producto" id="id_prod" value="<?php echo $p_edit['id'] ?? ''; ?>">
            
            <div class="grid-form">
                <div class="form-group">
                    <label>Nombre del Producto *</label>
                    <input type="text" name="nombre" id="in_nombre" required value="<?php echo $p_edit['nombre'] ?? ''; ?>" class="form-control" placeholder="Ej: Pollo a la Brasa Familiar">
                </div>
                <div class="form-group">
                    <label>Categoría Menú *</label>
                    <select name="categoria_id" required class="form-control">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($p_edit['categoria_id']) && $p_edit['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo $cat['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-form" style="grid-template-columns: 2fr 1fr 1fr;">
                <div class="form-group">
                    <label>Precio de Venta *</label>
                    <input type="number" step="0.01" name="precio" required value="<?php echo $p_edit['precio'] ?? ''; ?>" class="form-control" placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label>Moneda</label>
                    <select name="moneda" class="form-control">
                        <option value="BOB" <?php echo (isset($p_edit['moneda']) && $p_edit['moneda'] == 'BOB') ? 'selected' : ''; ?>>BOB</option>
                        <option value="USD" <?php echo (isset($p_edit['moneda']) && $p_edit['moneda'] == 'USD') ? 'selected' : ''; ?>>USD</option>
                        <option value="PYG" <?php echo (isset($p_edit['moneda']) && $p_edit['moneda'] == 'PYG') ? 'selected' : ''; ?>>PYG</option>
                        <option value="BRL" <?php echo (isset($p_edit['moneda']) && $p_edit['moneda'] == 'BRL') ? 'selected' : ''; ?>>BRL</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Sucursal (Opcional)</label>
                    <select name="sucursal_id" class="form-control">
                        <option value="">-- Todas --</option>
                        <?php foreach($sucursales as $suc): ?>
                            <option value="<?php echo $suc['id']; ?>" <?php echo (isset($p_edit['sucursal_id']) && $p_edit['sucursal_id'] == $suc['id']) ? 'selected' : ''; ?>>
                                <?php echo $suc['nombre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid-form" style="background: #fff8e1; padding: 15px; border-radius: 8px; border: 1px solid #ffe082; margin-top: 10px;">
                <div class="form-group">
                    <label style="color: #856404;">🔥 Precio de Oferta (Opcional)</label>
                    <input type="number" step="0.01" name="precio_oferta" value="<?php echo $p_edit['precio_oferta'] ?? ''; ?>" class="form-control" placeholder="Ej: 25.00" style="border-color: #ffe082;">
                    <small style="color: #b08900; font-size: 10px;">Si se llena, este será el precio visible.</small>
                </div>
                <div class="form-group">
                    <label style="color: #856404;">🏷️ Etiqueta de Campaña</label>
                    <input type="text" name="etiqueta_oferta" value="<?php echo $p_edit['etiqueta_oferta'] ?? ''; ?>" class="form-control" placeholder="Ej: Oferta del día, -15%, Black Friday" style="border-color: #ffe082;">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <label>Descripción / Ingredientes (Opcional)</label>
                <textarea name="descripcion" rows="3" class="form-control" placeholder="Describe brevemente el plato..."><?php echo $p_edit['descripcion'] ?? ''; ?></textarea>
            </div>

            <div style="background:#f9f9f9; padding:15px; border-radius:8px; display:grid; grid-template-columns: 1fr 1fr 2fr; gap:15px; align-items:center; margin-bottom:25px; border:1px solid #eee;">
                <label style="cursor:pointer; font-size:14px;"><input type="checkbox" name="es_combo" <?php echo (isset($p_edit['es_combo']) && $p_edit['es_combo']) ? 'checked' : ''; ?>> 📦 Es un Combo</label>
                <label style="cursor:pointer; font-size:14px; color:#D4AF37; font-weight:bold;"><input type="checkbox" name="destacado" <?php echo (isset($p_edit['destacado']) && $p_edit['destacado']) ? 'checked' : ''; ?>> ⭐ Destacado</label>
                
                <div class="form-group" style="margin:0;">
                    <label style="margin:0; font-size:12px;">Disponible el día:</label>
                    <select name="dia_semana" class="form-control" style="padding:8px; margin-top:3px;">
                        <?php 
                        $dias = ['Todos', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                        foreach($dias as $d) {
                            $sel = (isset($p_edit['dia_semana']) && $p_edit['dia_semana'] == $d) ? 'selected' : '';
                            echo "<option value='$d' $sel>$d</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div style="margin-bottom:15px; display:flex; justify-content:space-between; align-items:center;">
                <h4 style="margin:0; color:#333;">🖼️ Galería de Imágenes</h4>
                <label style="font-size:14px; color:#1b5e20; font-weight:bold; cursor:pointer;">
                    <input type="checkbox" name="visible" <?php echo (!isset($p_edit) || $p_edit['visible']) ? 'checked' : ''; ?>> ✅ Producto Visible en Web
                </label>
            </div>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px; margin-bottom:30px;">
                
                <div class="form-group">
                    <label>1. Foto Principal (Portada)</label>
                    <div class="drop-zone" onclick="document.getElementById('f_prin').click()">
                        <span>📷 Subir Principal</span>
                        <small>Max 8MB. Reemplaza anterior.</small>
                        <input type="file" id="f_prin" name="principal" accept="image/jpeg, image/png" style="display:none;">
                        <div id="pre-prin" style="margin-top:8px; width:100%;"></div>
                    </div>
                    <?php if($fotos_edit['principal']): ?>
                        <div class="current-photo">
                            <img src="<?php echo $fotos_edit['principal']['ruta_foto']; ?>">
                            <a href="admin.php?mod=productos&del_foto=<?php echo $fotos_edit['principal']['id']; ?>&prod_id=<?php echo $p_edit['id']; ?>" class="btn-del-photo" onclick="return confirm('¿Borrar foto principal?')">×</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>2. Foto Hover (Efecto)</label>
                    <div class="drop-zone" onclick="document.getElementById('f_hov').click()">
                        <span>📷 Subir Hover</span>
                        <small>Opcional. Reemplaza anterior.</small>
                        <input type="file" id="f_hov" name="hover" accept="image/jpeg, image/png" style="display:none;">
                        <div id="pre-hov" style="margin-top:8px; width:100%;"></div>
                    </div>
                    <?php if($fotos_edit['hover']): ?>
                        <div class="current-photo">
                            <img src="<?php echo $fotos_edit['hover']['ruta_foto']; ?>">
                            <a href="admin.php?mod=productos&del_foto=<?php echo $fotos_edit['hover']['id']; ?>&prod_id=<?php echo $p_edit['id']; ?>" class="btn-del-photo" onclick="return confirm('¿Borrar foto hover?')">×</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>3. Galería (+) </label>
                    <div class="drop-zone" onclick="document.getElementById('f_gal').click()">
                        <span>📷 Añadir Fotos</span>
                        <small>Puedes seleccionar varias.</small>
                        <input type="file" id="f_gal" name="galeria[]" multiple accept="image/jpeg, image/png" style="display:none;">
                        <div id="pre-gal" style="margin-top:8px; display:grid; grid-template-columns:1fr 1fr; gap:4px; width:100%;"></div>
                    </div>
                    <?php if(!empty($fotos_edit['galeria'])): ?>
                        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap:5px; margin-top:10px;">
                            <?php foreach($fotos_edit['galeria'] as $fg): ?>
                                <div style="position:relative; height:50px; border-radius:4px; overflow:hidden; border:1px solid #eee;">
                                    <img src="<?php echo $fg['ruta_foto']; ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <a href="admin.php?mod=productos&del_foto=<?php echo $fg['id']; ?>&prod_id=<?php echo $p_edit['id']; ?>" class="btn-del-photo" style="width:16px; height:16px; font-size:10px; line-height:15px;" onclick="return confirm('¿Borrar esta foto de la galería?')">×</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="border-top:1px solid #eee; padding-top:20px; text-align:right;">
                <a href="admin.php?mod=productos" style="padding:12px 25px; border-radius:6px; background:#f5f5f5; color:#333; text-decoration:none; margin-right:10px; font-weight:bold; display:inline-block;">CANCELAR</a>
                
                <button type="button" onclick="abrirConfirmar()" style="padding:12px 35px; border:none; background:#000; color:#D4AF37; cursor:pointer; font-weight:bold; border-radius:6px; font-size:15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <?php echo $p_edit ? "GUARDAR CAMBIOS" : "CREAR PRODUCTO"; ?>
                </button>
                
                <input type="submit" name="btn_guardar_prod" id="submitReal" style="display:none;">
            </div>
        </form>
    </div>
</div>

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
    // Variables para saber si estamos editando (usadas por JS)
    const estaEditando = <?php echo $p_edit ? 'true' : 'false'; ?>;

    // Abrir modal en modo "Nuevo"
    function abrirModalNuevo() {
        // Limpiar formulario si venía de una edición cancelada
        if(estaEditando) {
            window.location.href = 'admin.php?mod=productos'; 
            return;
        }
        document.getElementById('modalProd').style.display = 'flex';
        document.getElementById('mTitle').innerText = "Registrar Nuevo Producto";
        document.getElementById('formP').reset();
        document.getElementById('id_prod').value = "";
        // Ocultar zonas de fotos actuales
        document.querySelectorAll('.current-photo, .fg-current').forEach(el => el.style.display = 'none');
    }

    // Cerrar modal y limpiar URL
    function cerrarModalProd() {
        document.getElementById('modalProd').style.display = 'none';
        if(estaEditando) {
            window.location.href = 'admin.php?mod=productos'; // Quita el ?edit=ID de la URL
        }
    }

    // --- Previsualización de Fotos Nuevas ---
    function setupPreview(inputId, previewId, multiple = false) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const maxSize = 8 * 1024 * 1024; // 8MB

        input.addEventListener('change', function() {
            preview.innerHTML = ''; // Limpiar anteriores
            
            if (this.files.length === 0) return;

            Array.from(this.files).forEach(file => {
                // Validar tamaño
                if (file.size > maxSize) {
                    alert(`El archivo ${file.name} es muy grande (Max 8MB).`);
                    this.value = ''; // Reset input
                    return;
                }
                // Validar tipo
                if (!['image/jpeg', 'image/png'].includes(file.type)) {
                    alert(`El archivo ${file.name} no es JPG o PNG.`);
                    this.value = '';
                    return;
                }

                // Crear preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.cssText = multiple 
                        ? "width:100%; height:40px; object-fit:cover; border-radius:2px; border:1px solid #D4AF37;"
                        : "width:100%; height:60px; object-fit:cover; border-radius:4px; border:1px solid #D4AF37; margin-top:5px;";
                    preview.appendChild(img);
                }
                reader.readAsDataURL(file);
            });
        });
    }

    setupPreview('f_prin', 'pre-prin');
    setupPreview('f_hov', 'pre-hov');
    setupPreview('f_gal', 'pre-gal', true); // true para galería múltiple

    function abrirConfirmar() {
        const nombre = document.getElementById('in_nombre').value;
        const id = document.getElementById('id_prod').value;
        
        // Validación básica antes de preguntar
        if(nombre.trim() === "" || document.getElementById('formP').checkValidity() === false) {
            document.getElementById('formP').reportValidity(); // Muestra los errores nativos de HTML5
            return;
        }

        const title = id ? "❓ ¿Confirmar Cambios?" : "❓ ¿Crear Producto?";
        const text = id ? `¿Estás seguro de guardar las actualizaciones del producto "${nombre}"?` : `¿Deseas registrar el nuevo producto "${nombre}" en el menú?`;
        
        document.getElementById('cTitle').innerText = title;
        document.getElementById('cText').innerText = text;
        document.getElementById('modalConfirm').style.display = 'flex';
    }

    function cerrarConfirmar() {
        document.getElementById('modalConfirm').style.display = 'none';
    }

    function finalizarEnvio() {
        cerrarConfirmar();
        // Mostrar un "Cargando..." estético podría ir aquí
        document.getElementById('submitReal').click(); // Clic en el submit oculto
    }
</script>