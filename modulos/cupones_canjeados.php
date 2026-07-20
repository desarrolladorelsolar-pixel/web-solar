<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- VERIFICAR SESIÓN DE USUARIO ---
if (!isset($_SESSION['user_id'])) {
    if (isset($_SESSION['usuario_id'])) {
        $_SESSION['user_id'] = $_SESSION['usuario_id'];
    } elseif (isset($_SESSION['id_usuario'])) {
        $_SESSION['user_id'] = $_SESSION['id_usuario'];
    } elseif (isset($_SESSION['id'])) {
        $_SESSION['user_id'] = $_SESSION['id'];
    } else {
        $_SESSION['user_id'] = 0;
    }
}

// --- INCLUIR CONFIGURACIÓN ---
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// --- PROCESAR CANJE DE CUPÓN ---
if (isset($_POST['btn_canjear_cupon'])) {
    $codigo = trim($_POST['codigo_cupon']);
    $cliente_nombre = trim($_POST['cliente_nombre']);
    $cliente_ci = trim($_POST['cliente_ci']);
    $cliente_id = !empty($_POST['cliente_id']) ? $_POST['cliente_id'] : null;
    $observaciones = trim($_POST['observaciones']);

    try {
        $pdo->beginTransaction();

        // 1. Verificar que el cupón existe, está activo y no ha sido usado
        $stmt = $pdo->prepare("SELECT * FROM cupones WHERE codigo = ? AND estado = 1 AND usado = 0");
        $stmt->execute([$codigo]);
        $cupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cupon) {
            throw new Exception("El cupón no existe, está inactivo o ya fue utilizado.");
        }

        // 2. Verificar vigencia
        $fecha_actual = date('Y-m-d H:i:s');
        if ($fecha_actual > $cupon['fecha_expiracion']) {
            throw new Exception("El cupón ha expirado. Fecha de expiración: " . date('d/m/Y H:i', strtotime($cupon['fecha_expiracion'])));
        }
        if ($fecha_actual < $cupon['fecha_inicio']) {
            throw new Exception("El cupón aún no está vigente. Fecha de inicio: " . date('d/m/Y H:i', strtotime($cupon['fecha_inicio'])));
        }

        // 3. Buscar o crear cliente
        if (!$cliente_id && !empty($cliente_ci)) {
            $stmt_cliente = $pdo->prepare("SELECT id, nombre FROM clientes WHERE documento = ? AND estado = 1");
            $stmt_cliente->execute([$cliente_ci]);
            $cliente_existente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

            if ($cliente_existente) {
                $cliente_id = $cliente_existente['id'];
                if ($cliente_existente['nombre'] != $cliente_nombre) {
                    $pdo->prepare("UPDATE clientes SET nombre = ? WHERE id = ?")->execute([$cliente_nombre, $cliente_id]);
                }
            } else {
                $sql_cliente = "INSERT INTO clientes (nombre, documento, created_at, estado) VALUES (?, ?, NOW(), 1)";
                $pdo->prepare($sql_cliente)->execute([$cliente_nombre, $cliente_ci]);
                $cliente_id = $pdo->lastInsertId();
            }
        } elseif (!$cliente_id && empty($cliente_ci)) {
            throw new Exception("Debes proporcionar el CI del cliente o seleccionarlo.");
        }

        // 4. Marcar cupón como usado
        $pdo->prepare("UPDATE cupones SET usado = 1 WHERE id = ?")->execute([$cupon['id']]);

        // 5. Registrar en cupon_uso
        // Si ya existe fila (generado desde convenio web), actualizar; si no, insertar nuevo registro
        $usuario_id = $_SESSION['user_id'] ?? null;

        $stmt_check = $pdo->prepare("SELECT id FROM cupon_uso WHERE cupon_id = ? LIMIT 1");
        $stmt_check->execute([$cupon['id']]);
        $uso_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($uso_existente) {
            // Cupón generado desde convenio — actualizar con datos del canje real en caja
            $pdo->prepare("UPDATE cupon_uso SET usuario_id=?, cliente_id=?, cliente_nombre=?, cliente_ci=?, fecha_uso=NOW(), observaciones=? WHERE id=?")
                ->execute([$usuario_id, $cliente_id, $cliente_nombre, $cliente_ci, $observaciones, $uso_existente['id']]);
        } else {
            // Cupón creado manualmente por admin — insertar registro nuevo
            $sql_uso = "INSERT INTO cupon_uso (cupon_id, usuario_id, cliente_id, cliente_nombre, cliente_ci, fecha_uso, observaciones)
                        VALUES (?, ?, ?, ?, ?, NOW(), ?)";
            $pdo->prepare($sql_uso)->execute([
                $cupon['id'], $usuario_id, $cliente_id, $cliente_nombre, $cliente_ci, $observaciones
            ]);
        }

        // 6. Registrar en logs
        if (function_exists('registrarLog')) {
            registrarLog($pdo, "CANJEAR_CUPON",
                "Cupón canjeado: Código '{$cupon['codigo']}' (ID: {$cupon['id']}) - Cliente: $cliente_nombre (CI: $cliente_ci) - Usuario ID: " . ($usuario_id ?? 'N/A'));
        }

        $pdo->commit();

        $_SESSION['mensaje_exito'] = "✅ ¡Cupón canjeado exitosamente!<br>Código: <strong>{$cupon['codigo']}</strong><br>Cliente: <strong>$cliente_nombre</strong> (CI: $cliente_ci)<br>Descripción: {$cupon['descripcion']}";

        echo "<script>
            alert('✅ ¡Cupón canjeado exitosamente!\\nCódigo: {$cupon['codigo']}\\nCliente: $cliente_nombre (CI: $cliente_ci)');
            window.location='admin.php?mod=cupones_canjeados';
        </script>";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        if (function_exists('registrarLog')) {
            registrarLog($pdo, "ERROR_CANJEAR_CUPON", "Error al canjear cupón '$codigo': " . $e->getMessage());
        }
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canjear Cupones</title>
    <style>
        .canje-container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .canje-box { background: #fff; border-radius: 12px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-top: 8px solid #D4AF37; margin-bottom: 30px; }
        .canje-box h2 { margin-top: 0; color: #000; border-bottom: 2px solid #D4AF37; padding-bottom: 10px; display: inline-block; margin-bottom: 30px; }
        .form-canje { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-canje .full-width { grid-column: 1 / -1; }
        .form-canje .form-group { margin-bottom: 15px; }
        .form-canje label { display: block; font-weight: bold; font-size: 13px; color: #555; margin-bottom: 5px; }
        .form-canje .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        .form-canje .form-control:focus { border-color: #D4AF37; outline: none; box-shadow: 0 0 0 3px rgba(212,175,55,0.1); }
        .btn-canjear { background: #000; color: #D4AF37; padding: 15px 40px; border: none; border-radius: 6px; font-weight: bold; font-size: 16px; cursor: pointer; transition: 0.3s; width: 100%; }
        .btn-canjear:hover:not(:disabled) { transform: scale(1.02); box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .btn-canjear:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-pastilla { padding: 6px 15px; border-radius: 20px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; }
        .btn-edit { background: #e3f2fd; color: #0d47a1; }
        .btn-edit:hover { background: #bbdefb; }
        .btn-success { background: #e8f5e9; color: #1b5e20; }
        .btn-success:hover { background: #c8e6c9; }
        .btn-primary { background: #e3f2fd; color: #0d47a1; }
        .btn-primary:hover { background: #bbdefb; }
        .cupon-info { background: #f8f9fa; border: 2px solid #D4AF37; border-radius: 8px; padding: 20px; margin-top: 20px; display: none; }
        .cupon-info.visible { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .cupon-info .codigo { font-family: 'Courier New', monospace; font-size: 24px; font-weight: bold; color: #D4AF37; background: #fff; padding: 10px 20px; border-radius: 6px; display: inline-block; border: 1px solid #ddd; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid; }
        .alert-success { background: #e8f5e9; color: #1b5e20; border-color: #4caf50; }
        .alert-danger { background: #ffebee; color: #c62828; border-color: #c62828; }
        .alert-info { background: #e3f2fd; color: #0d47a1; border-color: #2196f3; }
        .cliente-info { background: #e3f2fd; border: 1px solid #90caf9; border-radius: 6px; padding: 10px 15px; margin-top: 5px; display: none; font-size: 13px; }
        .cliente-info.visible { display: block; }
        .cliente-info strong { color: #0d47a1; }
    </style>
</head>
    <style>
        /* Modal */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: #fff; border-radius: 12px; padding: 35px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; border-top: 8px solid #D4AF37; box-shadow: 0 20px 50px rgba(0,0,0,0.3); animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-content h3 { margin-top: 0; color: #000; border-bottom: 2px solid #D4AF37; padding-bottom: 10px; }
        .modal-close { float: right; background: none; border: none; font-size: 28px; cursor: pointer; color: #999; transition: 0.3s; }
        .modal-close:hover { color: #000; }
        .modal-content .form-group { margin-bottom: 15px; }
        .modal-content label { display: block; font-weight: bold; font-size: 13px; color: #555; margin-bottom: 5px; }
        .modal-content .form-control { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .modal-content .form-control:focus { border-color: #D4AF37; outline: none; box-shadow: 0 0 0 3px rgba(212,175,55,0.1); }
        .btn-modal-crear { background: #000; color: #D4AF37; padding: 12px 30px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s; width: 100%; font-size: 15px; }
        .btn-modal-crear:hover { transform: scale(1.02); box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .btn-modal-crear:disabled { opacity: 0.5; cursor: not-allowed; }
        .cliente-no-encontrado { background: #fff3cd; border: 1px solid #ffe082; border-radius: 6px; padding: 10px 15px; margin-top: 5px; display: none; font-size: 13px; }
        .cliente-no-encontrado.visible { display: block; }
        .cliente-no-encontrado button { background: #000; color: #D4AF37; border: none; padding: 5px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 5px; }
        .cliente-no-encontrado button:hover { background: #333; }
        @media (max-width: 768px) {
            .canje-box { padding: 20px; }
            .form-canje { grid-template-columns: 1fr; }
            .form-canje .full-width { grid-column: 1; }
            .modal-content { padding: 20px; }
        }
    </style>
<body>
    <div class="canje-container">
        <div class="canje-box">
            <h2>🔄 Canjear Cupón</h2>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['mensaje_exito'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['mensaje_exito']; ?></div>
                <?php unset($_SESSION['mensaje_exito']); ?>
            <?php endif; ?>

            <form method="POST" id="formCanje" class="form-canje">
                <div class="form-group full-width">
                    <label>🔑 Código del Cupón *</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="codigo_cupon" id="codigo_cupon"
                               class="form-control" placeholder="Ingresa el código del cupón"
                               required style="font-family: 'Courier New', monospace; font-size: 16px; text-transform: uppercase;"
                               autofocus>
                        <button type="button" onclick="buscarCupon()" class="btn-pastilla btn-edit" style="padding: 12px 25px; white-space: nowrap;">
                            🔍 Buscar
                        </button>
                    </div>
                    <small style="color: #666;">El código es sensible a mayúsculas/minúsculas</small>
                </div>

                <div id="cuponInfo" class="cupon-info full-width">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div><strong>Código:</strong><br><span class="codigo" id="cuponCodigo"></span></div>
                        <div><strong>Tipo:</strong><br><span id="cuponTipo"></span></div>
                        <div><strong>Descripción:</strong><br><span id="cuponDescripcion"></span></div>
                        <div><strong>Vigencia:</strong><br><span id="cuponVigencia"></span></div>
                    </div>
                    <div style="margin-top: 10px; padding: 10px; background: #e8f5e9; border-radius: 6px;" id="cuponValido">
                        ✅ Cupón válido y disponible para canje
                    </div>
                </div>

                <div id="clienteSection" style="display: none; grid-column: 1 / -1;">
                    <h3 style="color: #333; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-top: 20px;">👤 Datos del Cliente</h3>

                    <div class="form-group">
                        <label>👤 CI del cliente o código de registro *</label>
                        <input type="text" name="cliente_ci" id="cliente_ci"
                               class="form-control" placeholder="Número de CI o código de registro" required
                               onkeyup="buscarCliente(this.value)">
                        <div id="clienteInfo" class="cliente-info" style="display:none;">
                            <strong>Cliente encontrado:</strong> <span id="clienteEncontrado"></span>
                        </div>
                        <div id="clienteNoEncontrado" class="cliente-no-encontrado">
                            ⚠️ No se encontró un cliente con este CI
                            <button type="button" onclick="abrirModalCrearCliente()">➕ Crear Cliente</button>
                        </div>
                        <small style="color: #666;">Al escribir, buscará automáticamente. Si no existe, crea uno nuevo.</small>
                    </div>

                    <div class="form-group">
                        <label>📛 Nombre del Cliente *</label>
                        <input type="text" name="cliente_nombre" id="cliente_nombre"
                               class="form-control" placeholder="Nombre completo" required>
                    </div>

                    <div class="form-group full-width">
                        <label>📝 Observaciones (Opcional)</label>
                        <textarea name="observaciones" class="form-control" rows="2"
                                  placeholder="Ej: Canje realizado en caja 1"></textarea>
                    </div>

                    <input type="hidden" name="cliente_id" id="cliente_id" value="">

                    <div class="form-group full-width" style="margin-top: 20px;">
                        <button type="submit" name="btn_canjear_cupon" id="btnCanjear"
                                class="btn-canjear" disabled>
                            🔄 CANJEAR CUPÓN
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL CREAR CLIENTE -->
    <div id="modalCrearCliente" class="modal-overlay">
        <div class="modal-content">
            <button type="button" class="modal-close" onclick="cerrarModalCrearCliente()">&times;</button>
            <h3>➕ Crear Nuevo Cliente</h3>
            <p style="color: #666; font-size: 14px; margin-bottom: 20px;">El cliente no existe en el sistema. Completa los datos para registrarlo.</p>
            <form id="formCrearCliente">
                <div class="form-group">
                    <label>👤 CI del Cliente *</label>
                    <input type="text" id="cliente_ci_rapido" class="form-control" placeholder="Número de CI" required>
                </div>
                <div class="form-group">
                    <label>📛 Nombre del Cliente *</label>
                    <input type="text" id="cliente_nombre_rapido" class="form-control" placeholder="Nombre completo" required>
                </div>
                <div class="form-group">
                    <label>📞 Teléfono</label>
                    <input type="text" id="cliente_telefono_rapido" class="form-control" placeholder="Número de teléfono">
                </div>
                <div class="form-group">
                    <label>📧 Email</label>
                    <input type="email" id="cliente_email_rapido" class="form-control" placeholder="correo@ejemplo.com">
                </div>
                <div class="form-group">
                    <label>📍 Dirección</label>
                    <input type="text" id="cliente_direccion_rapido" class="form-control" placeholder="Dirección del cliente">
                </div>
                <div id="mensajeModal" style="display:none; padding:10px; border-radius:4px; margin-bottom:15px;"></div>
                <button type="button" onclick="crearClienteRapido()" class="btn-modal-crear" id="btnCrearClienteModal">
                    ✅ CREAR CLIENTE
                </button>
            </form>
        </div>
    </div>

    <script>
    let cuponValidado = false;
    let clienteValidado = false;
    let clienteBuscado = false;

    function buscarCupon() {
        const codigo = document.getElementById('codigo_cupon').value.trim().toUpperCase();
        if (!codigo) { alert('Por favor ingresa un código de cupón'); return; }
        document.getElementById('codigo_cupon').value = codigo;
        const btnBuscar = document.querySelector('button[onclick="buscarCupon()"]');
        const textoOriginal = btnBuscar.textContent;
        btnBuscar.textContent = '⏳ Buscando...';
        btnBuscar.disabled = true;
        fetch(`ajax.php?buscar_cupon=${encodeURIComponent(codigo)}`)
            .then(response => response.json())
            .then(data => {
                const infoDiv = document.getElementById('cuponInfo');
                const clienteSection = document.getElementById('clienteSection');
                const btnCanjear = document.getElementById('btnCanjear');
                if (data.success && data.vigente) {
                    infoDiv.classList.add('visible');
                    document.getElementById('cuponCodigo').textContent = data.cupon.codigo;
                    document.getElementById('cuponTipo').textContent = data.cupon.tipo_nombre || 'Sin tipo';
                    document.getElementById('cuponDescripcion').textContent = data.cupon.descripcion || 'Sin descripción';
                    document.getElementById('cuponVigencia').textContent =
                        `Desde: ${formatDate(data.cupon.fecha_inicio)} Hasta: ${formatDate(data.cupon.fecha_expiracion)}`;
                    const validoDiv = document.getElementById('cuponValido');
                    validoDiv.style.background = '#e8f5e9';
                    validoDiv.style.color = '#1b5e20';
                    validoDiv.innerHTML = '✅ Cupón válido y disponible para canje';
                    clienteSection.style.display = 'block';
                    document.getElementById('cliente_ci').focus();
                    cuponValidado = true;
                    btnCanjear.disabled = true;
                    document.getElementById('codigo_cupon').style.borderColor = '#4caf50';
                } else {
                    infoDiv.classList.remove('visible');
                    clienteSection.style.display = 'none';
                    cuponValidado = false;
                    btnCanjear.disabled = true;
                    let mensaje = data.message || 'Cupón no válido';
                    if (data.success && !data.vigente) mensaje = data.mensaje || 'Cupón no vigente';
                    document.getElementById('codigo_cupon').style.borderColor = '#c62828';
                    setTimeout(() => { document.getElementById('codigo_cupon').style.borderColor = '#ddd'; }, 3000);
                    alert('❌ ' + mensaje);
                }
            })
            .catch(error => { alert('Error al buscar el cupón: ' + error.message); })
            .finally(() => { btnBuscar.textContent = textoOriginal; btnBuscar.disabled = false; });
    }

    let timeoutId;
    function buscarCliente(ci) {
        clearTimeout(timeoutId);
        const clienteInfo = document.getElementById('clienteInfo');
        const clienteNoEncontrado = document.getElementById('clienteNoEncontrado');
        const nombreInput = document.getElementById('cliente_nombre');
        const btnCanjear = document.getElementById('btnCanjear');
        if (ci.length < 5) {
            nombreInput.value = '';
            document.getElementById('cliente_id').value = '';
            clienteInfo.classList.remove('visible');
            clienteNoEncontrado.classList.remove('visible');
            clienteValidado = false;
            btnCanjear.disabled = true;
            return;
        }
        timeoutId = setTimeout(() => {
            fetch(`ajax.php?buscar_cliente=${encodeURIComponent(ci)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('cliente_nombre').value = data.cliente.nombre;
                        document.getElementById('cliente_id').value = data.cliente.id;
                        document.getElementById('clienteEncontrado').textContent = `${data.cliente.nombre} (${data.cliente.documento})`;
                        // No mostramos el aviso — el nombre ya se rellena solo en el campo de arriba
                        clienteInfo.style.display = 'none';
                        clienteNoEncontrado.classList.remove('visible');
                        nombreInput.style.borderColor = '#4caf50';
                        clienteValidado = true;
                        clienteBuscado = true;
                        verificarHabilitarBoton();
                    } else {
                        document.getElementById('cliente_nombre').value = '';
                        document.getElementById('cliente_id').value = '';
                        clienteInfo.classList.remove('visible');
                        clienteNoEncontrado.classList.add('visible');
                        nombreInput.style.borderColor = '#ff9800';
                        clienteValidado = false;
                        clienteBuscado = false;
                        btnCanjear.disabled = true;
                    }
                })
                .catch(error => { console.error('Error en fetch cliente:', error); });
        }, 500);
    }

    function abrirModalCrearCliente() {
        const ci = document.getElementById('cliente_ci').value.trim();
        if (!ci) { alert('Primero ingresa el CI del cliente'); return; }
        document.getElementById('cliente_ci_rapido').value = ci;
        document.getElementById('cliente_nombre_rapido').value = '';
        document.getElementById('cliente_telefono_rapido').value = '';
        document.getElementById('cliente_email_rapido').value = '';
        document.getElementById('cliente_direccion_rapido').value = '';
        document.getElementById('mensajeModal').style.display = 'none';
        document.getElementById('modalCrearCliente').classList.add('active');
        document.getElementById('cliente_nombre_rapido').focus();
    }

    function cerrarModalCrearCliente() {
        document.getElementById('modalCrearCliente').classList.remove('active');
    }

    function crearClienteRapido() {
        const nombre = document.getElementById('cliente_nombre_rapido').value.trim();
        const ci = document.getElementById('cliente_ci_rapido').value.trim();
        const telefono = document.getElementById('cliente_telefono_rapido').value.trim();
        const email = document.getElementById('cliente_email_rapido').value.trim();
        const direccion = document.getElementById('cliente_direccion_rapido').value.trim();
        if (!nombre || !ci) { mostrarMensajeModal('❌ Nombre y CI son obligatorios', 'danger'); return; }
        const btn = document.getElementById('btnCrearClienteModal');
        btn.textContent = '⏳ Creando...';
        btn.disabled = true;
        const formData = new FormData();
        formData.append('btn_crear_cliente_rapido', 1);
        formData.append('cliente_nombre_rapido', nombre);
        formData.append('cliente_ci_rapido', ci);
        formData.append('cliente_telefono_rapido', telefono);
        formData.append('cliente_email_rapido', email);
        formData.append('cliente_direccion_rapido', direccion);
        fetch('ajax.php', { method: 'POST', body: formData })
            .then(response => response.text())
            .then(text => {
                if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<')) {
                    mostrarMensajeModal('❌ Error del servidor. Revisa los logs.', 'danger');
                    return;
                }
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        mostrarMensajeModal('✅ ' + data.message, 'success');
                        document.getElementById('cliente_nombre').value = data.cliente.nombre;
                        document.getElementById('cliente_id').value = data.cliente.id;
                        document.getElementById('cliente_ci').value = data.cliente.documento;
                        document.getElementById('clienteEncontrado').textContent = `${data.cliente.nombre} (${data.cliente.documento})`;
                        document.getElementById('clienteInfo').style.display = 'none';
                        document.getElementById('clienteNoEncontrado').classList.remove('visible');
                        document.getElementById('cliente_nombre').style.borderColor = '#4caf50';
                        clienteValidado = true;
                        clienteBuscado = true;
                        verificarHabilitarBoton();
                        setTimeout(() => { cerrarModalCrearCliente(); }, 1500);
                    } else {
                        mostrarMensajeModal('❌ ' + data.message, 'danger');
                    }
                } catch (e) {
                    mostrarMensajeModal('❌ Error al procesar la respuesta del servidor', 'danger');
                }
            })
            .catch(error => { mostrarMensajeModal('❌ Error al crear cliente: ' + error.message, 'danger'); })
            .finally(() => { btn.textContent = '✅ CREAR CLIENTE'; btn.disabled = false; });
    }

    function mostrarMensajeModal(mensaje, tipo) {
        const div = document.getElementById('mensajeModal');
        div.textContent = mensaje;
        div.style.display = 'block';
        div.style.background = tipo === 'success' ? '#e8f5e9' : '#ffebee';
        div.style.color = tipo === 'success' ? '#1b5e20' : '#c62828';
        div.style.borderLeft = `4px solid ${tipo === 'success' ? '#4caf50' : '#c62828'}`;
    }

    function verificarHabilitarBoton() {
        const btnCanjear = document.getElementById('btnCanjear');
        const nombre = document.getElementById('cliente_nombre').value.trim();
        const ci = document.getElementById('cliente_ci').value.trim();
        if (cuponValidado && nombre && ci && clienteValidado) {
            btnCanjear.disabled = false;
        } else {
            btnCanjear.disabled = true;
        }
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const codigoInput = document.getElementById('codigo_cupon');
        const clienteCi = document.getElementById('cliente_ci');
        const clienteNombre = document.getElementById('cliente_nombre');
        codigoInput.addEventListener('keyup', function(e) { if (e.key === 'Enter') buscarCupon(); });
        codigoInput.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
        clienteNombre.addEventListener('input', function() {
            const ci = document.getElementById('cliente_ci').value.trim();
            if (this.value.trim() && ci) { clienteValidado = true; verificarHabilitarBoton(); }
            else { clienteValidado = false; verificarHabilitarBoton(); }
        });
        clienteCi.addEventListener('input', function() { verificarHabilitarBoton(); });
    });

    document.getElementById('formCanje').addEventListener('submit', function(e) {
        const codigo = document.getElementById('codigo_cupon').value.trim();
        const clienteNombre = document.getElementById('cliente_nombre').value.trim();
        const clienteCi = document.getElementById('cliente_ci').value.trim();
        if (!codigo) { e.preventDefault(); alert('❌ Por favor ingresa el código del cupón'); return; }
        if (!clienteNombre || !clienteCi) { e.preventDefault(); alert('❌ Por favor completa los datos del cliente'); return; }
        if (!confirm(`¿Estás seguro de canjear este cupón?\nCódigo: ${codigo}\nCliente: ${clienteNombre} (CI: ${clienteCi})`)) {
            e.preventDefault();
        }
    });

    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') cerrarModalCrearCliente(); });
    document.getElementById('modalCrearCliente').addEventListener('click', function(e) { if (e.target === this) cerrarModalCrearCliente(); });
    </script>
</body>
</html>
