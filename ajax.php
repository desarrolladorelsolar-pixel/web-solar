<?php
// --- CONFIGURACIÓN DE ERRORES ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- PhpSpreadsheet autoload ---
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use PhpOffice\PhpSpreadsheet\IOFactory;

// --- INICIAR SESIÓN ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- INCLUIR CONFIGURACIÓN ---
require_once 'config.php';

// --- VERIFICAR QUE $pdo EXISTA ---
if (!isset($pdo)) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// --- MANEJAR PETICIONES ---

// Desactivar automáticamente cupones vencidos antes de cualquier petición de generación
if (isset($_POST['generar_cupon_gabriel']) || isset($_POST['generar_cupon_upsa']) || isset($_POST['generar_cupon_convenio'])) {
    $pdo->exec("UPDATE cupones SET estado = 0 WHERE fecha_expiracion < NOW() AND estado = 1 AND usado = 0");
}

// BUSCAR CUPÓN
if (isset($_GET['buscar_cupon'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        $codigo = trim($_GET['buscar_cupon']);
        if (empty($codigo)) {
            throw new Exception("Código vacío");
        }
        
        $sql = "SELECT c.*, tc.nombre as tipo_nombre,
                       cu.cliente_ci  AS ci_quien_genero,
                       cu.cliente_nombre AS nombre_quien_genero
                FROM cupones c
                LEFT JOIN tipo_cupon tc ON c.tipo_cupon_id = tc.id
                LEFT JOIN cupon_uso cu ON cu.cupon_id = c.id
                WHERE c.codigo = ? AND c.estado = 1 AND c.usado = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$codigo]);
        $cupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cupon) {
            echo json_encode(['success' => false, 'message' => 'Cupón no encontrado, inactivo o ya usado']);
            exit;
        }
        
        // VERIFICAR VIGENCIA
        $fecha_actual = date('Y-m-d H:i:s');
        $vigente = true;
        $mensaje = '';
        
        if ($fecha_actual > $cupon['fecha_expiracion']) {
            $vigente = false;
            $mensaje = 'Cupón expirado';
        } elseif ($fecha_actual < $cupon['fecha_inicio']) {
            $vigente = false;
            $mensaje = 'Cupón no vigente aún';
        }
        
        echo json_encode([
            'success' => true,
            'cupon' => $cupon,
            'vigente' => $vigente,
            'mensaje' => $mensaje
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// BUSCAR CLIENTE
if (isset($_GET['buscar_cliente'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        $ci = trim($_GET['buscar_cliente']);
        if (empty($ci)) {
            throw new Exception("CI vacío");
        }
        
        // 1. Buscar en tabla clientes
        $stmt = $pdo->prepare("SELECT id, nombre, documento, telefono, email FROM clientes WHERE documento = ? AND estado = 1");
        $stmt->execute([$ci]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cliente) {
            echo json_encode(['success' => true, 'cliente' => $cliente, 'fuente' => 'clientes']);
            exit;
        }

        // 2. Si no está en clientes, buscar el nombre en cupon_uso (estudiantes de convenio)
        $stmt = $pdo->prepare(
            "SELECT cliente_nombre, cliente_ci 
             FROM cupon_uso 
             WHERE cliente_ci = ? AND cliente_nombre IS NOT NULL AND cliente_nombre != '' AND cliente_nombre != 'Cliente Web'
             ORDER BY fecha_uso DESC 
             LIMIT 1"
        );
        $stmt->execute([$ci]);
        $uso = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($uso) {
            echo json_encode([
                'success' => true,
                'cliente' => [
                    'id'        => null,
                    'nombre'    => $uso['cliente_nombre'],
                    'documento' => $uso['cliente_ci'],
                    'telefono'  => null,
                    'email'     => null,
                ],
                'fuente' => 'convenio'
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// GENERAR CUPÓN GABRIEL — verifica CI contra el Excel de UAGRM (PhpSpreadsheet)
if (isset($_POST['generar_cupon_gabriel'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');

    try {
        $ci = preg_replace('/[^0-9]/', '', trim($_POST['ci'] ?? ''));
        if (strlen($ci) < 4) {
            echo json_encode(['success' => false, 'tipo' => 'ci_invalido', 'message' => 'Ingresa un número de carnet válido.']);
            exit;
        }

        // 1. Buscar en el Excel con PhpSpreadsheet
        $carpeta  = __DIR__ . '/gabriel_datos/';
        $archivos = glob($carpeta . '*.xlsx');

        if (empty($archivos)) {
            echo json_encode(['success' => false, 'tipo' => 'error', 'message' => 'Error de configuración interna.']);
            exit;
        }

        $excel_path   = $archivos[0];
        $nombre_excel = null;
        $encontrado   = false;

        $reader      = IOFactory::createReaderForFile($excel_path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($excel_path);
        $sheet       = $spreadsheet->getSheetByName('estudiantes activos');

        if (!$sheet) {
            echo json_encode(['success' => false, 'tipo' => 'error', 'message' => 'Error: hoja "estudiantes activos" no encontrada en el Excel.']);
            exit;
        }

        $highestRow = $sheet->getHighestRow();

        // Columna B desde fila 2 — número de registro
        for ($row = 2; $row <= $highestRow; $row++) {
            $ci_celda  = (string) $sheet->getCell('B' . $row)->getValue();
            $ci_limpio = preg_replace('/[^0-9]/', '', $ci_celda);
            if ($ci_limpio === $ci) {
                $encontrado   = true;
                $nombre_excel = 'Estudiante UAGRM';
                break;
            }
        }

        if (!$encontrado) {
            echo json_encode([
                'success' => false,
                'tipo'    => 'no_cliente',
                'message' => 'Tu carnet no está registrado en el convenio UAGRM con El Solar.'
            ]);
            exit;
        }

        // 2. ¿Ya generó 2 cupones hoy?
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cupon_uso WHERE cliente_ci = ? AND DATE(fecha_uso) = CURDATE()");
        $stmt->execute([$ci]);
        if ((int)$stmt->fetchColumn() >= 2) {
            echo json_encode(['success'=>false,'tipo'=>'ya_generado','message'=>'Ya generaste tus 2 cupones de hoy. Solo se permiten 2 por día por carnet.']);
            exit;
        }

        // 3. Buscar cupón disponible
        $stmt = $pdo->prepare(
            "SELECT id, codigo FROM cupones
             WHERE estado = 1 AND usado = 0 AND fecha_expiracion >= NOW()
               AND id NOT IN (SELECT cupon_id FROM cupon_uso WHERE DATE(fecha_uso) = CURDATE())
             ORDER BY id ASC LIMIT 1"
        );
        $stmt->execute();
        $cupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cupon) {
            echo json_encode(['success' => false, 'tipo' => 'sin_stock', 'message' => 'No hay cupones disponibles por el momento. Vuelve más tarde.']);
            exit;
        }

        // 4. Registrar en cupon_uso
        $stmt = $pdo->prepare(
            "INSERT INTO cupon_uso (cupon_id, usuario_id, cliente_id, cliente_ci, cliente_nombre, fecha_uso, observaciones)
             VALUES (?, 1, NULL, ?, ?, NOW(), 'Generado desde convenio UAGRM')"
        );
        $stmt->execute([$cupon['id'], $ci, $nombre_excel]);

        echo json_encode([
            'success'  => true,
            'tipo'     => 'ok',
            'codigo'   => $cupon['codigo'],
            'cupon_id' => (int)$cupon['id'],
            'nombre'   => $nombre_excel,
            'message'  => 'Cupón generado exitosamente.'
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'tipo' => 'error', 'message' => 'Error interno: ' . $e->getMessage()]);
        exit;
    }
}

// GENERAR CUPÓN UPSA — verifica CI contra el Excel de UPSA (PhpSpreadsheet)
if (isset($_POST['generar_cupon_upsa'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');

    try {
        $ci = preg_replace('/[^0-9]/', '', trim($_POST['ci'] ?? ''));
        if (strlen($ci) < 4) {
            echo json_encode(['success' => false, 'tipo' => 'ci_invalido', 'message' => 'Ingresa un número de carnet válido.']);
            exit;
        }

        // 1. Buscar en el Excel de UPSA
        $carpeta  = __DIR__ . '/upsa_datos/';
        $archivos = glob($carpeta . '*.xlsx');

        if (empty($archivos)) {
            echo json_encode(['success' => false, 'tipo' => 'error', 'message' => 'Error de configuración interna.']);
            exit;
        }

        $excel_path  = $archivos[0];
        $encontrado  = false;
        $nombre_excel = null;

        $reader      = IOFactory::createReaderForFile($excel_path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($excel_path);
        $sheet       = $spreadsheet->getSheetByName('estudiantes activos');

        if (!$sheet) {
            echo json_encode(['success' => false, 'tipo' => 'error', 'message' => 'Error: hoja "estudiantes activos" no encontrada en el Excel.']);
            exit;
        }

        $highestRow = $sheet->getHighestRow();

        // Columna B desde fila 2 — solo número de carnet, sin nombre
        for ($row = 2; $row <= $highestRow; $row++) {
            $ci_celda  = (string) $sheet->getCell('B' . $row)->getValue();
            $ci_limpio = preg_replace('/[^0-9]/', '', $ci_celda);
            if ($ci_limpio === $ci) {
                $encontrado   = true;
                $nombre_excel = 'Estudiante UPSA'; // UPSA no provee nombres
                break;
            }
        }

        if (!$encontrado) {
            echo json_encode([
                'success' => false,
                'tipo'    => 'no_cliente',
                'message' => 'Tu carnet no está registrado en el convenio UPSA con El Solar.'
            ]);
            exit;
        }

        // 2. ¿Ya generó 2 cupones hoy?
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cupon_uso WHERE cliente_ci = ? AND DATE(fecha_uso) = CURDATE()");
        $stmt->execute([$ci]);
        if ((int)$stmt->fetchColumn() >= 2) {
            echo json_encode(['success'=>false,'tipo'=>'ya_generado','message'=>'Ya generaste tus 2 cupones de hoy. Solo se permiten 2 por día por carnet.']);
            exit;
        }

        // 3. Buscar cupón disponible
        $stmt = $pdo->prepare(
            "SELECT id, codigo FROM cupones
             WHERE estado = 1 AND usado = 0 AND fecha_expiracion >= NOW()
               AND id NOT IN (SELECT cupon_id FROM cupon_uso WHERE DATE(fecha_uso) = CURDATE())
             ORDER BY id ASC LIMIT 1"
        );
        $stmt->execute();
        $cupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cupon) {
            echo json_encode(['success' => false, 'tipo' => 'sin_stock', 'message' => 'No hay cupones disponibles por el momento. Vuelve más tarde.']);
            exit;
        }

        // 4. Registrar en cupon_uso
        $stmt = $pdo->prepare(
            "INSERT INTO cupon_uso (cupon_id, usuario_id, cliente_id, cliente_ci, cliente_nombre, fecha_uso, observaciones)
             VALUES (?, 1, NULL, ?, ?, NOW(), 'Generado desde convenio UPSA')"
        );
        $stmt->execute([$cupon['id'], $ci, $nombre_excel]);

        echo json_encode([
            'success'  => true,
            'tipo'     => 'ok',
            'codigo'   => $cupon['codigo'],
            'cupon_id' => (int)$cupon['id'],
            'nombre'   => $nombre_excel,
            'message'  => 'Cupón generado exitosamente.'
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'tipo' => 'error', 'message' => 'Error interno: ' . $e->getMessage()]);
        exit;
    }
}

// GENERAR CUPÓN DESDE CONVENIO UPSA (verifica contra tabla clientes)
if (isset($_POST['generar_cupon_convenio'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');

    try {
        $ci = trim($_POST['ci'] ?? '');
        if (strlen($ci) < 4) {
            echo json_encode([
                'success' => false,
                'tipo'    => 'ci_invalido',
                'message' => 'Ingresa un número de carnet válido.'
            ]);
            exit;
        }

        // 1. ¿El CI existe en la tabla clientes?
        $stmt = $pdo->prepare(
            "SELECT id, nombre FROM clientes WHERE documento = ? AND estado = 1 LIMIT 1"
        );
        $stmt->execute([$ci]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            echo json_encode([
                'success' => false,
                'tipo'    => 'no_cliente',
                'message' => 'Tu carnet no está registrado en el convenio UPSA con El Solar.'
            ]);
            exit;
        }

        // 2. ¿Este CI ya generó 2 cupones hoy?
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cupon_uso WHERE cliente_ci = ? AND DATE(fecha_uso) = CURDATE()");
        $stmt->execute([$ci]);
        if ((int)$stmt->fetchColumn() >= 2) {
            echo json_encode(['success'=>false,'tipo'=>'ya_generado','message'=>'Ya generaste tus 2 cupones de hoy. Solo se permiten 2 por día por carnet.']);
            exit;
        }

        // 3. Buscar primer cupón disponible: activo, no usado, no mostrado hoy, vigente
        $stmt = $pdo->prepare(
            "SELECT id, codigo
             FROM cupones
             WHERE estado = 1
               AND usado = 0
               AND fecha_expiracion >= NOW()
               AND id NOT IN (
                   SELECT cupon_id FROM cupon_uso
                   WHERE DATE(fecha_uso) = CURDATE()
               )
             ORDER BY id ASC
             LIMIT 1"
        );
        $stmt->execute([]);
        $cupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cupon) {
            echo json_encode([
                'success' => false,
                'tipo'    => 'sin_stock',
                'message' => 'No hay cupones disponibles por el momento. Vuelve más tarde.'
            ]);
            exit;
        }

        // 4. Registrar en cupon_uso vinculando cliente real
        $stmt = $pdo->prepare(
            "INSERT INTO cupon_uso (cupon_id, usuario_id, cliente_id, cliente_ci, cliente_nombre, fecha_uso, observaciones)
             VALUES (?, 1, ?, ?, ?, NOW(), 'Generado desde convenio UPSA')"
        );
        $stmt->execute([$cupon['id'], $cliente['id'], $ci, $cliente['nombre']]);

        echo json_encode([
            'success'  => true,
            'tipo'     => 'ok',
            'codigo'   => $cupon['codigo'],
            'cupon_id' => (int)$cupon['id'],
            'nombre'   => $cliente['nombre'],
            'message'  => 'Cupón generado exitosamente.'
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'tipo'    => 'error',
            'message' => 'Error interno: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Si no hay parámetros válidos
echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
exit;
?>