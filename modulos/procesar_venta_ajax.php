<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$data = json_decode(file_get_contents('php://input'), true);

if(!isset($_SESSION['apertura_caja_id'])) {
    echo json_encode(['success' => false, 'error' => 'No hay caja abierta']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $apertura_caja_id = $_SESSION['apertura_caja_id'];
    $usuario_id = $_SESSION['usuario_id'];
    $cliente_id = $data['cliente_id'];
    $descuento = $data['descuento_global'];
    $total = $data['total'];
    $metodo_pago_id = $data['metodo_pago_id'];
    
    // Obtener sucursal de la caja
    $stmt = $pdo->prepare("SELECT c.sucursal_id FROM apertura_caja ac JOIN cajas c ON ac.caja_id = c.id WHERE ac.id = ?");
    $stmt->execute([$apertura_caja_id]);
    $sucursal_id = $stmt->fetchColumn();
    
    // Insertar venta
    $sql = "INSERT INTO ventas (sucursal_id, usuario_id, cliente_id, apertura_caja_id, fecha_venta, descuento, total, estado) 
            VALUES (?, ?, ?, ?, NOW(), ?, ?, 1)";
    $pdo->prepare($sql)->execute([$sucursal_id, $usuario_id, $cliente_id, $apertura_caja_id, $descuento, $total]);
    $venta_id = $pdo->lastInsertId();
    
    // Insertar detalles
    foreach($data['items'] as $item) {
        $subtotal_linea = ($item['precio'] * $item['cantidad']);
        $sql = "INSERT INTO venta_detalle (venta_id, producto_id, cantidad, precio_unitario, descuento_linea, subtotal_linea) 
                VALUES (?, ?, ?, ?, 0, ?)";
        $pdo->prepare($sql)->execute([$venta_id, $item['id'], $item['cantidad'], $item['precio'], $subtotal_linea]);
    }
    
    // Insertar pago
    $sql = "INSERT INTO venta_pagos (venta_id, metodo_pago_id, monto) VALUES (?, ?, ?)";
    $pdo->prepare($sql)->execute([$venta_id, $metodo_pago_id, $total]);
    
    // Log
    registrarLog($pdo, "VENTA_REGISTRADA", "Venta ID: $venta_id - Total: $total BOB");
    
    $pdo->commit();
    echo json_encode(['success' => true, 'venta_id' => $venta_id]);
    
} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>