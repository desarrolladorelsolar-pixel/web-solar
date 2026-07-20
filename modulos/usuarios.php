<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que sea ADMIN
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] != 'admin') {
    echo "<script>alert('❌ No tienes permisos para ver usuarios.'); window.location='admin.php?mod=dashboard';</script>";
    exit;
}



if (isset($_POST['btn_guardar'])) {
    $id = $_POST['id_usuario'] ?? ''; // Si viene ID, es edición
    $nombre = $_POST['nombre'];
    $usuario = $_POST['usuario'];
    $rol = $_POST['rol'];

    if (empty($id)) {
        // --- LÓGICA DE REGISTRO (NUEVO) ---
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        try {
            $sql = "INSERT INTO usuarios (nombre, usuario, password, rol, estado, created_at) 
                    VALUES (?, ?, ?, ?, 1, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $usuario, $password, $rol]);
            echo "<script>alert('Usuario registrado con éxito'); window.location='admin.php?mod=usuarios';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Error: El usuario ya existe o hubo un problema');</script>";
        }
        registrarLog($pdo, "INSERTAR_USUARIO", "Se creó al usuario: $usuario");
    } else {
        // --- LÓGICA DE ACTUALIZACIÓN (EDITAR) ---
        if (!empty($_POST['password'])) {
            // Si el admin escribió una nueva contraseña
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET nombre=?, usuario=?, password=?, rol=? WHERE id=?";
            $params = [$nombre, $usuario, $password, $rol, $id];
        } else {
            // Si dejó la contraseña vacía, mantenemos la anterior
            $sql = "UPDATE usuarios SET nombre=?, usuario=?, rol=? WHERE id=?";
            $params = [$nombre, $usuario, $rol, $id];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo "<script>window.location='admin.php?mod=usuarios';</script>";
        registrarLog($pdo, "EDITAR_USUARIO", "Se actualizaron los datos de: $usuario (ID: $id)");
    }
}

// B. LÓGICA DE BORRADO LÓGICO (Soft Delete)
if (isset($_GET['del'])) {
    $id_del = $_GET['del'];
    $sql = "UPDATE usuarios SET estado = 0 WHERE id = ?";
    $pdo->prepare($sql)->execute([$id_del]);
    echo "<script>window.location='admin.php?mod=usuarios';</script>";
    registrarLog($pdo, "ELIMINAR_USUARIO", "Se eliminó el usuario: $usuario (ID: $id)");
}

// C. LÓGICA DE OBTENER DATOS PARA EDITAR
$res_edit = null;
if (isset($_GET['edit'])) {
    $id_edit = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$id_edit]);
    $res_edit = $stmt->fetch(); // Aquí cargamos los datos en la variable $res_edit
}

// --- 2. LÓGICA DE OBTENER LISTADO COMPLETO ---
// Solo traemos los que tienen estado 1 (los "no eliminados")
$sql_list = "SELECT id, nombre, usuario, rol, ultimo_acceso FROM usuarios WHERE estado = 1 ORDER BY id DESC";
$usuarios = $pdo->query($sql_list)->fetchAll();
?>

<div style="background:#fff; padding:20px; border-radius:8px; border:1px solid #eee; margin-bottom:30px;">
    <h3 style="margin-top:0;"><?php echo $res_edit ? "Editar Usuario: ".$res_edit['usuario'] : "Registrar Nuevo Usuario"; ?></h3>
    
    <form method="POST">
        <input type="hidden" name="id_usuario" value="<?php echo $res_edit['id'] ?? ''; ?>">

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px;">
            <div>
                <label>Nombre Completo</label>
                <input type="text" name="nombre" required value="<?php echo $res_edit['nombre'] ?? ''; ?>" style="width:100%; padding:10px; margin-top:5px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div>
                <label>Nombre de Usuario (Login)</label>
                <input type="text" name="usuario" required value="<?php echo $res_edit['usuario'] ?? ''; ?>" style="width:100%; padding:10px; margin-top:5px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div>
                <label>Contraseña <?php echo $res_edit ? '<small>(Dejar vacío para no cambiar)</small>' : ''; ?></label>
                <input type="password" name="password" <?php echo $res_edit ? '' : 'required'; ?> style="width:100%; padding:10px; margin-top:5px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div>
                <label>Rol de Sistema</label>
                <select name="rol" style="width:100%; padding:10px; margin-top:5px; border:1px solid #ddd; border-radius:4px;">
                    <option value="admin" <?php echo (isset($res_edit['rol']) && $res_edit['rol'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                    <option value="editor" <?php echo (isset($res_edit['rol']) && $res_edit['rol'] == 'editor') ? 'selected' : ''; ?>>Editor</option>
                </select>
            </div>
            <div style="display:flex; align-items:flex-end;">
                <button type="submit" name="btn_guardar" style="width:100%; background:#000; color:#D4AF37; border:none; padding:12px; border-radius:4px; cursor:pointer; font-weight:bold;">
                    <?php echo $res_edit ? "GUARDAR CAMBIOS" : "REGISTRAR USUARIO"; ?>
                </button>
            </div>
        </div>
    </form>
    <?php if($res_edit): ?>
        <p><a href="admin.php?mod=usuarios" style="color:red; font-size:12px;">[ Cancelar Edición ]</a></p>
    <?php endif; ?>
</div>

<table style="width:100%; border-collapse: collapse;">
    <thead>
        <tr style="background:#1a1a1a; color:#fff;">
            <th style="padding:12px;">ID</th>
            <th>Nombre</th>
            <th>Usuario</th>
            <th>Rol</th>
            <th>Último Acceso</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding:12px;"><?php echo $u['id']; ?></td>
            <td><strong><?php echo $u['nombre']; ?></strong></td>
            <td><code><?php echo $u['usuario']; ?></code></td>
            <td><?php echo strtoupper($u['rol']); ?></td>
            <td style="color:#888; font-size:13px;"><?php echo $u['ultimo_acceso'] ?? 'Sin datos'; ?></td>
            <td>
                <a href="admin.php?mod=usuarios&edit=<?php echo $u['id']; ?>" style="background:#e3f2fd; color:#0d47a1; padding:5px 12px; border-radius:4px; text-decoration:none; font-size:12px; font-weight:bold; margin-right:5px;">Editar</a> | 
                <a href="admin.php?mod=usuarios&del=<?php echo $u['id']; ?>" 
                   onclick="return confirm('¿Seguro que quieres eliminar a este usuario?')" 
                   style="background:#ffebee; color:#c62828; padding:5px 12px; border-radius:4px; text-decoration:none; font-size:12px; font-weight:bold;">Eliminar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>