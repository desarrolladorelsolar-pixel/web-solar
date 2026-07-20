<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (isset($_POST['btn_guardar_cat'])) {
    $id = $_POST['id_categoria'] ?? '';
    $nombre = $_POST['nombre'];
    $icono = $_POST['icono'];

    if (empty($id)) {
        // NUEVA CATEGORÍA
        $sql = "INSERT INTO categorias (nombre, icono, estado) VALUES (?, ?, 1)";
        $pdo->prepare($sql)->execute([$nombre, $icono]);
        registrarLog($pdo, "INSERTAR_CATEGORIA", "Se creó la categoría: $nombre");
    } else {
        // EDITAR CATEGORÍA
        $sql = "UPDATE categorias SET nombre = ?, icono = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$nombre, $icono, $id]);
        registrarLog($pdo, "EDITAR_CATEGORIA", "Se actualizaron los datos de: $nombre (ID: $id)");
    }
    echo "<script>window.location='admin.php?mod=categorias';</script>";
}

// SOFT DELETE
if (isset($_GET['del'])) {
    $id_del = $_GET['del'];
    $pdo->prepare("UPDATE categorias SET estado = 0 WHERE id = ?")->execute([$id_del]);
    registrarLog($pdo, "ELIMINAR_CATEGORIA", "ID eliminado: $id_del");
    echo "<script>window.location='admin.php?mod=categorias';</script>";
}

$cat_edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $cat_edit = $stmt->fetch();
}

$categorias = $pdo->query("SELECT * FROM categorias WHERE estado = 1 ORDER BY nombre ASC")->fetchAll();
?>

<style>
    .modal-overlay {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;
    }
    .modal-box {
        background: #fff; padding: 30px; border-radius: 12px; width: 400px; text-align: center;
        border-top: 6px solid #D4AF37; box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    }
    .btn-si { background: #000; color: #D4AF37; padding: 12px 25px; border: none; cursor: pointer; font-weight: bold; border-radius: 6px; }
    .btn-no { background: #f4f4f4; color: #333; padding: 12px 25px; border: none; cursor: pointer; margin-right: 10px; border-radius: 6px; }
    /* ===== CSS RESPONSIVO PARA MÓDULO DE CATEGORÍAS ===== */

    /* Tablets y pantallas medianas (768px y menos) */
    @media (max-width: 768px) {
        /* Formulario en columna */
        #formCat {
            flex-direction: column !important;
            gap: 15px !important;
        }
        
        #formCat > div {
            width: 100% !important;
        }
        
        #formCat button {
            width: 100% !important;
            margin-top: 5px;
        }
        
        /* Modal más pequeño */
        .modal-box {
            width: 90% !important;
            margin: 20px;
            padding: 20px !important;
        }
        
        /* Tabla - scroll horizontal */
        .table-card {
            overflow-x: auto !important;
        }
        
        table {
            min-width: 500px;
        }
        
        th, td {
            padding: 10px !important;
            font-size: 0.9em;
        }
    }

    /* Móviles (480px y menos) */
    @media (max-width: 480px) {
        /* Formulario más compacto */
        #formCat {
            gap: 10px !important;
        }
        
        #formCat input {
            padding: 10px !important;
            font-size: 14px;
        }
        
        #formCat button {
            padding: 10px !important;
            font-size: 14px;
            height: auto !important;
        }
        
        /* Títulos más pequeños */
        h3 {
            font-size: 1.2em !important;
        }
        
        /* Botones de acción en tabla */
        td a {
            display: inline-block;
            margin-bottom: 5px;
            padding: 6px 10px !important;
            font-size: 11px !important;
        }
        
        /* Modal ajustado */
        .modal-box {
            padding: 15px !important;
        }
        
        .btn-si, .btn-no {
            padding: 8px 15px !important;
            font-size: 13px;
        }
    }

    /* Móviles muy pequeños (375px) */
    @media (max-width: 375px) {
        .modal-box h2 {
            font-size: 1.2em !important;
        }
        
        .modal-box p {
            font-size: 13px !important;
        }
        
        td a {
            padding: 5px 8px !important;
            font-size: 10px !important;
        }
    }
</style>

<div style="background:#fff; padding:25px; border-radius:8px; border:1px solid #eee; margin-bottom:30px;">
    <h3 style="margin-top:0; color:#000; border-bottom: 2px solid #D4AF37; display:inline-block; padding-bottom:5px;">
        <?php echo $cat_edit ? "Editar Categoría" : "Nueva Categoría"; ?>
    </h3>
    <form id="formCat" method="POST" style="display:flex; gap:15px; align-items: flex-end; margin-top:15px;">
        <input type="hidden" name="id_categoria" id="id_cat" value="<?php echo $cat_edit['id'] ?? ''; ?>">
        
        <div style="flex:2;">
            <label style="font-weight:bold; font-size:13px;">Nombre de Categoría</label>
            <input type="text" name="nombre" id="input_nombre" required value="<?php echo $cat_edit['nombre'] ?? ''; ?>" 
                   style="width:100%; padding:12px; margin-top:5px; border:1px solid #ddd; border-radius:4px;">
        </div>

        <div style="flex:1;">
            <label style="font-weight:bold; font-size:13px;">Icono (FontAwesome)</label>
            <input type="text" name="icono" value="<?php echo $cat_edit['icono'] ?? ''; ?>" 
                   placeholder="fa fa-star" style="width:100%; padding:12px; margin-top:5px; border:1px solid #ddd; border-radius:4px;">
        </div>

        <button type="button" onclick="abrirModal()" 
                style="background:#000; color:#D4AF37; border:none; padding:12px 30px; border-radius:4px; cursor:pointer; font-weight:bold; height:45px;">
            <?php echo $cat_edit ? "ACTUALIZAR DATOS" : "GUARDAR CATEGORÍA"; ?>
        </button>
        
        <input type="submit" name="btn_guardar_cat" id="realSubmit" style="display:none;">
    </form>
    <?php if($cat_edit): ?>
        <a href="admin.php?mod=categorias" style="color:red; font-size:12px; display:block; margin-top:10px; text-decoration:none;">[ Cancelar Edición ]</a>
    <?php endif; ?>
</div>

<table style="width:100%; border-collapse: collapse; background:#fff; border-radius:8px; overflow:hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
    <thead>
        <tr style="background:#1a1a1a; color:#D4AF37; text-align:left;">
            <th style="padding:15px;">ID</th>
            <th>Icono</th>
            <th>Nombre</th>
            <th style="text-align:right; padding-right:15px;">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categorias as $c): ?>
        <tr style="border-bottom: 1px solid #f2f2f2;">
            <td style="padding:15px; color:#999;"><?php echo $c['id']; ?></td>
            <td style="font-size:18px;">
                <i class="<?php echo $c['icono']; ?>" style="color:#000;"></i>
                <span style="font-size:10px; color:#aaa; display:block;"><?php echo $c['icono']; ?></span>
            </td>
            <td><strong><?php echo $c['nombre']; ?></strong></td>
            <td style="text-align:right; padding-right:15px;">
                <a href="admin.php?mod=categorias&edit=<?php echo $c['id']; ?>" 
                   style="background:#e3f2fd; color:#0d47a1; padding:5px 12px; border-radius:4px; text-decoration:none; font-size:12px; font-weight:bold; margin-right:5px;">Editar</a>
                
                <a href="admin.php?mod=categorias&del=<?php echo $c['id']; ?>" 
                   onclick="return confirm('¿Seguro que quieres eliminar la categoría <?php echo $c['nombre']; ?>?')" 
                   style="background:#ffebee; color:#c62828; padding:5px 12px; border-radius:4px; text-decoration:none; font-size:12px; font-weight:bold;">Borrar</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($categorias)): ?>
            <tr><td colspan="4" style="text-align:center; padding:30px; color:#ccc;">No hay categorías activas.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div id="miModal" class="modal-overlay">
    <div class="modal-box">
        <h2 id="modalTitle" style="margin-top:0;">¿Confirmar?</h2>
        <p id="modalText" style="color:#666;"></p>
        <div style="margin-top:25px;">
            <button class="btn-no" onclick="cerrarModal()">No, volver</button>
            <button class="btn-si" onclick="confirmarEnvio()">Sí, confirmar</button>
        </div>
    </div>
</div>

<script>
    function abrirModal() {
        const id = document.getElementById('id_cat').value;
        const nombre = document.getElementById('input_nombre').value;
        
        if(nombre.trim() === "") {
            alert("El nombre de la categoría es obligatorio.");
            return;
        }

        document.getElementById('modalTitle').innerText = id ? "Confirmar Cambio" : "Nueva Categoría";
        document.getElementById('modalText').innerText = id ? `¿Estás seguro de actualizar "${nombre}"?` : `¿Deseas registrar la categoría "${nombre}" en el sistema?`;
        
        document.getElementById('miModal').style.display = 'flex';
    }

    function cerrarModal() {
        document.getElementById('miModal').style.display = 'none';
    }

    function confirmarEnvio() {
        document.getElementById('realSubmit').click();
    }
</script>