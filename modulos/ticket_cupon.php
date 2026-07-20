<?php
/**
 * ticket_cupon.php
 * Genera un PDF térmico (80mm) para un cupón de convenio.
 * Parámetro GET: cupon_id (int) o codigo (string)
 * Uso: ticket_cupon.php?cupon_id=123  o  ticket_cupon.php?codigo=AB3K9X
 */

require_once '../config.php';
require_once '../vendor/autoload.php';

// ── Parámetro de entrada ─────────────────────────────────────
$cupon_id = isset($_GET['cupon_id']) ? (int)$_GET['cupon_id'] : 0;
$codigo   = isset($_GET['codigo'])   ? trim($_GET['codigo'])  : '';

if (!$cupon_id && !$codigo) {
    http_response_code(400);
    die('Parámetro requerido: cupon_id o codigo');
}

// ── Consulta principal ───────────────────────────────────────
$where = $cupon_id ? "c.id = ?" : "c.codigo = ?";
$param = $cupon_id ? $cupon_id  : $codigo;

$stmt = $pdo->prepare("
    SELECT
        c.id,
        c.codigo,
        c.descripcion,
        c.fecha_inicio,
        c.fecha_expiracion,
        c.usado,
        c.estado,
        tc.nombre        AS tipo_cupon,
        cu.cliente_nombre,
        cu.cliente_ci,
        cu.fecha_uso,
        cu.observaciones
    FROM cupones c
    LEFT JOIN tipo_cupon tc ON c.tipo_cupon_id = tc.id
    LEFT JOIN cupon_uso  cu ON cu.cupon_id = c.id
    WHERE $where
    LIMIT 1
");
$stmt->execute([$param]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    die('Cupón no encontrado');
}

// ── Extraer universidad de observaciones ────────────────────
// Campo contiene: 'Generado desde convenio UAGRM' o 'Generado desde convenio UPSA'
function extraerUniversidad(string $obs): string {
    if (stripos($obs, 'UAGRM') !== false) return 'UAGRM — Univ. Autónoma Gabriel René Moreno';
    if (stripos($obs, 'UPSA')  !== false) return 'UPSA — Univ. Privada de Santa Cruz';
    return 'Convenio universitario';
}

$universidad = extraerUniversidad($row['observaciones'] ?? '');

// ── Formatear fechas ─────────────────────────────────────────
function fmtFecha(?string $f): string {
    if (!$f) return '—';
    return date('d/m/Y H:i', strtotime($f));
}

// ── HTML del ticket ──────────────────────────────────────────
$logo_path = realpath(__DIR__ . '/../img/logito.png');
$logo_b64  = $logo_path ? 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path)) : '';

$html = '
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: "Courier New", Courier, monospace;
        font-size: 10pt;
        color: #111;
        width: 72mm;
    }
    .center  { text-align: center; }
    .bold    { font-weight: bold; }
    .small   { font-size: 8pt; }
    .large   { font-size: 13pt; }
    .xlarge  { font-size: 16pt; letter-spacing: 3px; }
    .divider {
        border-top: 1px dashed #555;
        margin: 5px 0;
    }
    .logo {
        display: block;
        margin: 0 auto 4px;
        width: 40px;
        height: auto;
    }
    .row {
        display: flex;
        justify-content: space-between;
        margin: 2px 0;
    }
    .label { color: #555; }
    .codigo-box {
        border: 2px solid #111;
        border-radius: 4px;
        padding: 6px 10px;
        text-align: center;
        margin: 6px 0;
        background: #f5f5f5;
    }
    .nota {
        font-size: 8pt;
        color: #444;
        text-align: center;
        margin-top: 4px;
        line-height: 1.4;
    }
</style>

<!-- CABECERA -->
<div class="center">
    ' . ($logo_b64 ? '<img class="logo" src="' . $logo_b64 . '">' : '') . '
    <div class="bold large">POLLO EL SOLAR</div>
    <div class="small">Av. Cañoto 581, Santa Cruz, Bolivia</div>
    <div class="small">Tel: 70000000</div>
</div>

<div class="divider"></div>

<!-- TIPO -->
<div class="center bold">CUPÓN DE CONVENIO</div>
<div class="center small">' . htmlspecialchars($universidad) . '</div>

<div class="divider"></div>

<!-- CÓDIGO -->
<div class="codigo-box">
    <div class="small label">CÓDIGO</div>
    <div class="xlarge bold">' . htmlspecialchars($row['codigo']) . '</div>
</div>

<div style="margin: 3px 0;">
    <div><span class="label">Tipo:    </span><span class="bold">' . htmlspecialchars($row['tipo_cupon'] ?? '—') . '</span></div>
    <div><span class="label">Detalle: </span>' . htmlspecialchars($row['descripcion'] ?? '—') . '</div>
</div>

<div class="divider"></div>

<!-- DATOS DEL ESTUDIANTE -->
<div style="margin: 2px 0;">
    <div><span class="label">Estudiante: </span><span class="bold">' . htmlspecialchars($row['cliente_nombre'] ?? '—') . '</span></div>
    <div><span class="label">Carnet:     </span>' . htmlspecialchars($row['cliente_ci'] ?? '—') . '</div>
    <div><span class="label">Generado:   </span>' . fmtFecha($row['fecha_uso']) . '</div>
    <div><span class="label">Vence:      </span><span class="bold">' . fmtFecha($row['fecha_expiracion']) . '</span></div>
</div>

<div class="divider"></div>

<!-- NOTA FINAL -->
<div class="nota">
    Válido solo el día de generación.<br>
    Hasta 2 usos por carnet, por día.<br>
    Presenta este código en caja.
</div>
';

// ── Generar PDF con mPDF ─────────────────────────────────────
$tmpDir = realpath(__DIR__ . '/../tmp');

$mpdf = new \Mpdf\Mpdf([
    'mode'          => 'utf-8',
    'format'        => [80, 140],
    'margin_top'    => 4,
    'margin_bottom' => 4,
    'margin_left'   => 4,
    'margin_right'  => 4,
    'tempDir'       => $tmpDir,
]);

$mpdf->SetTitle('Ticket Cupón — ' . $row['codigo']);
$mpdf->WriteHTML($html);

// 'I' = abrir en navegador / 'D' = forzar descarga
$mpdf->Output('ticket_cupon_' . $row['codigo'] . '.pdf', 'I');
