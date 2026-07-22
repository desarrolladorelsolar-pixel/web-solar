<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// ── PROCESAR CANJE ───────────────────────────────────────────
if (isset($_POST['btn_canjear_cupon'])) {
    $codigo       = trim($_POST['codigo_cupon']);
    $cliente_ci   = trim($_POST['cliente_ci']);
    $cliente_id   = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
    $observaciones = trim($_POST['observaciones'] ?? '');
    $usuario_id   = $_SESSION['user_id'] ?? null;

    try {
        $pdo->beginTransaction();

        // 1. Verificar cupón
        $stmt = $pdo->prepare("SELECT * FROM cupones WHERE codigo = ? AND estado = 1 AND usado = 0");
        $stmt->execute([$codigo]);
        $cupon = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cupon) throw new Exception("El cupón no existe, está inactivo o ya fue utilizado.");

        // 2. Verificar vigencia
        if (date('Y-m-d') > date('Y-m-d', strtotime($cupon['fecha_expiracion'])))
            throw new Exception("El cupón ha expirado el " . date('d/m/Y', strtotime($cupon['fecha_expiracion'])));
        if (date('Y-m-d') < date('Y-m-d', strtotime($cupon['fecha_inicio'])))
            throw new Exception("El cupón aún no está vigente. Inicio: " . date('d/m/Y', strtotime($cupon['fecha_inicio'])));

        // 3. Resolver cliente por carnet
        if (!$cliente_id && !empty($cliente_ci)) {
            $stmt_c = $pdo->prepare("SELECT id, nombre FROM clientes WHERE documento = ? AND estado = 1");
            $stmt_c->execute([$cliente_ci]);
            $cli = $stmt_c->fetch(PDO::FETCH_ASSOC);
            if ($cli) $cliente_id = $cli['id'];
        }
        if (!$cliente_id) throw new Exception("Debes ingresar el carnet del cliente.");

        // Obtener nombre del cliente para el log
        $stmt_n = $pdo->prepare("SELECT nombre FROM clientes WHERE id = ?");
        $stmt_n->execute([$cliente_id]);
        $cliente_nombre = $stmt_n->fetchColumn() ?: '—';

        // 4. Marcar cupón como usado
        $pdo->prepare("UPDATE cupones SET usado = 1 WHERE id = ?")->execute([$cupon['id']]);

        // 5. Registrar en cupon_uso (UPDATE si ya existía, INSERT si no)
        $stmt_check = $pdo->prepare("SELECT id FROM cupon_uso WHERE cupon_id = ? LIMIT 1");
        $stmt_check->execute([$cupon['id']]);
        $uso = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($uso) {
            $pdo->prepare("UPDATE cupon_uso SET usuario_id=?, cliente_id=?, cliente_ci=?, cliente_nombre=?, fecha_uso=NOW(), observaciones=? WHERE id=?")
                ->execute([$usuario_id, $cliente_id, $cliente_ci, $cliente_nombre, $observaciones, $uso['id']]);
        } else {
            $pdo->prepare("INSERT INTO cupon_uso (cupon_id, usuario_id, cliente_id, cliente_ci, cliente_nombre, fecha_uso, observaciones) VALUES (?,?,?,?,?,NOW(),?)")
                ->execute([$cupon['id'], $usuario_id, $cliente_id, $cliente_ci, $cliente_nombre, $observaciones]);
        }

        // 6. Log — cliente identificado por carnet
        if (function_exists('registrarLog')) {
            registrarLog($pdo, "CANJEAR_CUPON",
                "Cupón '{$cupon['codigo']}' canjeado — Cliente: $cliente_nombre (CI: $cliente_ci) — Cajero ID: $usuario_id");
        }

        $pdo->commit();

        $_SESSION['mensaje_exito'] = "✅ Cupón canjeado — <strong>{$cupon['codigo']}</strong> — Cliente: <strong>$cliente_nombre</strong> (CI: $cliente_ci)";

        echo "<script>alert(" . json_encode("✅ Cupón canjeado exitosamente\nCódigo: {$cupon['codigo']}\nCliente: $cliente_nombre (CI: $cliente_ci)") . "); window.location='admin.php?mod=cupones_canjeados';</script>";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        if (function_exists('registrarLog'))
            registrarLog($pdo, "ERROR_CANJEAR_CUPON", "Error al canjear '$codigo': " . $e->getMessage());
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Canjear Cupón</title>
<style>
.canje-container { max-width: 760px; margin: 0 auto; padding: 20px; }
.canje-box { background:#fff; border-radius:12px; padding:36px; box-shadow:0 4px 20px rgba(0,0,0,.08); border-top:8px solid #D4AF37; margin-bottom:30px; }
.canje-box h2 { margin-top:0; color:#000; border-bottom:2px solid #D4AF37; padding-bottom:10px; display:inline-block; margin-bottom:28px; }
.form-group { margin-bottom:18px; }
.form-group label { display:block; font-weight:700; font-size:13px; color:#555; margin-bottom:6px; }
.form-control { width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box; font-size:14px; }
.form-control:focus { border-color:#D4AF37; outline:none; box-shadow:0 0 0 3px rgba(212,175,55,.1); }
.btn-canjear { background:#000; color:#D4AF37; padding:15px; border:none; border-radius:6px; font-weight:700; font-size:16px; cursor:pointer; width:100%; margin-top:8px; }
.btn-canjear:disabled { opacity:.45; cursor:not-allowed; }
.btn-canjear:not(:disabled):hover { opacity:.85; }
.btn-buscar { background:#e3f2fd; color:#0d47a1; border:none; padding:12px 22px; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer; white-space:nowrap; }
.btn-buscar:hover { background:#bbdefb; }
.cupon-info { background:#f8f9fa; border:2px solid #D4AF37; border-radius:8px; padding:18px; margin:16px 0; display:none; }
.cupon-info.visible { display:block; }
.cupon-info .codigo { font-family:'Courier New',monospace; font-size:22px; font-weight:700; color:#D4AF37; background:#fff; padding:8px 16px; border-radius:6px; border:1px solid #ddd; display:inline-block; }
.alert { padding:13px 16px; border-radius:6px; margin-bottom:18px; border-left:4px solid; font-size:14px; }
.alert-success { background:#e8f5e9; color:#1b5e20; border-color:#4caf50; }
.alert-danger  { background:#ffebee; color:#c62828; border-color:#c62828; }
.aviso-no-encontrado { background:#fff3cd; border:1px solid #ffe082; border-radius:6px; padding:12px 16px; margin-top:8px; display:none; font-size:13px; }
.aviso-no-encontrado.visible { display:block; }
.btn-registrar { background:#000; color:#D4AF37; border:none; padding:7px 18px; border-radius:4px; font-weight:700; font-size:12px; cursor:pointer; margin-top:8px; }
.btn-registrar:hover { background:#333; }
/* Modal */
.modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:10000; align-items:center; justify-content:center; }
.modal-bg.active { display:flex; }
.modal-box { background:#fff; border-radius:12px; padding:32px; max-width:480px; width:90%; max-height:90vh; overflow-y:auto; border-top:8px solid #D4AF37; box-shadow:0 20px 50px rgba(0,0,0,.3); }
.modal-box h3 { margin-top:0; color:#000; border-bottom:2px solid #D4AF37; padding-bottom:8px; margin-bottom:20px; }
.btn-modal-ok { background:#000; color:#D4AF37; padding:13px; border:none; border-radius:6px; font-weight:700; font-size:15px; cursor:pointer; width:100%; margin-top:4px; }
.btn-modal-ok:disabled { opacity:.45; cursor:not-allowed; }
.msg-modal { padding:10px 14px; border-radius:5px; margin-bottom:12px; display:none; font-size:13px; }
@media(max-width:600px){ .canje-box{padding:20px;} .modal-box{padding:20px;} }
</style>
</head>
<body>
<div class="canje-container">

    <div class="canje-box">
        <h2>Canjear Cupón</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensaje_exito'])): ?>
            <div class="alert alert-success"><?= $_SESSION['mensaje_exito'] ?></div>
            <?php unset($_SESSION['mensaje_exito']); ?>
        <?php endif; ?>

        <!-- PASO 1: código del cupón -->
        <div class="form-group">
            <label>Código del cupón *</label>
            <div style="display:flex;gap:10px;">
                <input type="text" id="inCodigo" class="form-control"
                       placeholder="Ej: AB3K9X"
                       style="font-family:'Courier New',monospace;font-size:16px;text-transform:uppercase;"
                       autofocus>
                <button type="button" class="btn-buscar" onclick="buscarCupon()">🔍 Buscar</button>
            </div>
        </div>

        <!-- Info del cupón -->
        <div id="cuponInfo" class="cupon-info">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:10px;">
                <div><strong>Código:</strong><br><span id="iCodigo" class="codigo"></span></div>
                <div><strong>Tipo:</strong><br><span id="iTipo"></span></div>
                <div><strong>Descripción:</strong><br><span id="iDesc"></span></div>
                <div><strong>Vence:</strong><br><span id="iVence"></span></div>
            </div>
            <div style="background:#e8f5e9;color:#1b5e20;padding:8px 12px;border-radius:6px;font-size:13px;font-weight:700;">
                ✅ Cupón válido — ingresá el carnet del cliente
            </div>
        </div>

        <!-- PASO 2: carnet del cliente -->
        <div id="secCliente" style="display:none;">
            <hr style="border:0;border-top:1px solid #eee;margin:20px 0;">
            <div class="form-group">
                <label>Número de carnet del cliente *</label>
                <input type="text" id="inCarnet" class="form-control"
                       placeholder="Ingresá el CI / carnet"
                       oninput="buscarCliente(this.value)">
                <!-- cliente encontrado -->
                <div id="avisoCli" style="display:none;background:#e8f5e9;border:1px solid #a5d6a7;border-radius:6px;padding:10px 14px;margin-top:8px;font-size:13px;">
                    <strong style="color:#1b5e20;">Cliente encontrado:</strong>
                    <span id="nomCli" style="margin-left:6px;"></span>
                </div>
                <!-- cliente no encontrado -->
                <div id="avisoNoCli" class="aviso-no-encontrado">
                    ⚠️ No existe un cliente con ese carnet.
                    <br>
                    <button type="button" class="btn-registrar" onclick="abrirModalRegistrar()">➕ Registrar nuevo cliente</button>
                </div>
            </div>

            <div class="form-group">
                <label>Observaciones (opcional)</label>
                <textarea id="inObs" class="form-control" rows="2" placeholder="Ej: Canje en caja 2"></textarea>
            </div>

            <!-- campos ocultos para el submit -->
            <form method="POST" id="frmCanje">
                <input type="hidden" name="codigo_cupon" id="hCodigo">
                <input type="hidden" name="cliente_ci"   id="hCi">
                <input type="hidden" name="cliente_id"   id="hClienteId">
                <input type="hidden" name="observaciones" id="hObs">
                <button type="submit" name="btn_canjear_cupon" id="btnCanjear" class="btn-canjear" disabled>
                    CANJEAR CUPÓN
                </button>
            </form>
        </div>
    </div>
</div>

<!-- MODAL REGISTRAR CLIENTE -->
<div id="modRegistrar" class="modal-bg">
    <div class="modal-box">
        <h3>➕ Registrar nuevo cliente</h3>
        <div class="form-group">
            <label>Carnet / CI *</label>
            <input type="text" id="mCI" class="form-control" placeholder="Número de carnet">
        </div>
        <div class="form-group">
            <label>Nombre completo *</label>
            <input type="text" id="mNombre" class="form-control" placeholder="Nombre y apellido">
        </div>
        <div class="form-group">
            <label>Teléfono</label>
            <input type="text" id="mTel" class="form-control" placeholder="+591 ...">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" id="mEmail" class="form-control" placeholder="correo@ejemplo.com">
        </div>
        <div class="form-group">
            <label>Fecha de nacimiento</label>
            <input type="date" id="mFechaN" class="form-control">
            <small style="color:#999;font-size:11px;">Opcional — para alerta de cumpleaños.</small>
        </div>
        <div id="msgMod" class="msg-modal"></div>
        <button type="button" class="btn-modal-ok" id="btnCrear" onclick="registrarCliente()">✅ Crear cliente</button>
        <button type="button" onclick="cerrarModal()" style="background:#f4f4f4;color:#555;border:none;padding:10px;border-radius:6px;width:100%;margin-top:8px;cursor:pointer;font-size:13px;">
            Cancelar
        </button>
    </div>
</div>

<script>
let cuponOk   = false;
let clienteId = null;
let timerCI;

// ── BUSCAR CUPÓN ─────────────────────────────────────────────
function buscarCupon() {
    const codigo = document.getElementById('inCodigo').value.trim().toUpperCase();
    if (!codigo) { alert('Ingresá el código del cupón'); return; }
    document.getElementById('inCodigo').value = codigo;

    fetch(`ajax.php?buscar_cupon=${encodeURIComponent(codigo)}`)
        .then(r => r.json())
        .then(d => {
            if (d.success && d.vigente) {
                document.getElementById('iCodigo').textContent  = d.cupon.codigo;
                document.getElementById('iTipo').textContent    = d.cupon.tipo_nombre || '—';
                document.getElementById('iDesc').textContent    = d.cupon.descripcion || '—';
                document.getElementById('iVence').textContent   = fmtDate(d.cupon.fecha_expiracion);
                document.getElementById('cuponInfo').classList.add('visible');
                document.getElementById('secCliente').style.display = 'block';
                document.getElementById('hCodigo').value = codigo;
                document.getElementById('inCarnet').focus();
                cuponOk = true;
            } else {
                document.getElementById('cuponInfo').classList.remove('visible');
                document.getElementById('secCliente').style.display = 'none';
                cuponOk = false;
                alert('❌ ' + (d.mensaje || d.message || 'Cupón no válido o ya utilizado'));
            }
        })
        .catch(() => alert('Error al conectar con el servidor'));
}

// ── BUSCAR CLIENTE POR CARNET ────────────────────────────────
function buscarCliente(ci) {
    clearTimeout(timerCI);
    const avisoCli   = document.getElementById('avisoCli');
    const avisoNoCli = document.getElementById('avisoNoCli');
    const btnCanjear = document.getElementById('btnCanjear');

    avisoCli.style.display = 'none';
    avisoNoCli.classList.remove('visible');
    clienteId = null;
    btnCanjear.disabled = true;

    if (ci.length < 4) return;

    timerCI = setTimeout(() => {
        fetch(`ajax.php?buscar_cliente=${encodeURIComponent(ci)}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    clienteId = d.cliente.id;
                    document.getElementById('nomCli').textContent =
                        d.cliente.nombre + ' (' + d.cliente.documento + ')';
                    avisoCli.style.display = 'block';
                    avisoNoCli.classList.remove('visible');
                    habilitarCanje(ci);
                } else {
                    avisoCli.style.display = 'none';
                    avisoNoCli.classList.add('visible');
                    clienteId = null;
                    btnCanjear.disabled = true;
                }
            });
    }, 400);
}

function habilitarCanje(ci) {
    document.getElementById('hCi').value       = ci;
    document.getElementById('hClienteId').value = clienteId || '';
    document.getElementById('hObs').value       = document.getElementById('inObs').value;
    document.getElementById('btnCanjear').disabled = false;
}

// Sync observaciones al submit
document.getElementById('frmCanje').addEventListener('submit', function() {
    document.getElementById('hObs').value = document.getElementById('inObs').value;
    document.getElementById('hCi').value  = document.getElementById('inCarnet').value.trim();
});

// ── MODAL REGISTRAR CLIENTE ──────────────────────────────────
function abrirModalRegistrar() {
    document.getElementById('mCI').value     = document.getElementById('inCarnet').value.trim();
    document.getElementById('mNombre').value = '';
    document.getElementById('mTel').value    = '';
    document.getElementById('mEmail').value  = '';
    document.getElementById('mFechaN').value = '';
    document.getElementById('msgMod').style.display = 'none';
    document.getElementById('modRegistrar').classList.add('active');
    document.getElementById('mNombre').focus();
}

function cerrarModal() {
    document.getElementById('modRegistrar').classList.remove('active');
}

function registrarCliente() {
    const ci     = document.getElementById('mCI').value.trim();
    const nombre = document.getElementById('mNombre').value.trim();
    const tel    = document.getElementById('mTel').value.trim();
    const email  = document.getElementById('mEmail').value.trim();
    const fechaN = document.getElementById('mFechaN').value.trim();

    if (!ci || !nombre) { mostrarMsg('❌ Carnet y nombre son obligatorios', 'danger'); return; }

    const btn = document.getElementById('btnCrear');
    btn.disabled = true; btn.textContent = '⏳ Guardando...';

    const fd = new FormData();
    fd.append('btn_crear_cliente_rapido', 1);
    fd.append('cliente_ci_rapido',        ci);
    fd.append('cliente_nombre_rapido',    nombre);
    fd.append('cliente_telefono_rapido',  tel);
    fd.append('cliente_email_rapido',     email);
    fd.append('cliente_fecha_nacimiento', fechaN);

    fetch('ajax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                clienteId = d.cliente.id;
                document.getElementById('nomCli').textContent =
                    d.cliente.nombre + ' (' + d.cliente.documento + ')';
                document.getElementById('avisoCli').style.display = 'block';
                document.getElementById('avisoNoCli').classList.remove('visible');
                document.getElementById('inCarnet').value = ci;
                habilitarCanje(ci);
                cerrarModal();
            } else {
                mostrarMsg('❌ ' + d.message, 'danger');
            }
        })
        .catch(() => mostrarMsg('❌ Error de conexión', 'danger'))
        .finally(() => { btn.disabled = false; btn.textContent = '✅ Crear cliente'; });
}

function mostrarMsg(txt, tipo) {
    const el = document.getElementById('msgMod');
    el.textContent = txt;
    el.style.display = 'block';
    el.style.background = tipo === 'success' ? '#e8f5e9' : '#ffebee';
    el.style.color = tipo === 'success' ? '#1b5e20' : '#c62828';
    el.style.borderLeft = '4px solid ' + (tipo === 'success' ? '#4caf50' : '#c62828');
}

function fmtDate(s) {
    if (!s) return '—';
    return new Date(s).toLocaleDateString('es-BO', { day:'2-digit', month:'2-digit', year:'numeric' });
}

// Enter en código → buscar
document.getElementById('inCodigo').addEventListener('keyup', e => {
    if (e.key === 'Enter') buscarCupon();
});
document.getElementById('inCodigo').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});
</script>
</body>
</html>
