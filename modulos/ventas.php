<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Verificar caja activa
if(!isset($_SESSION['apertura_caja_id'])) {
    echo "<script>alert('Debes abrir una caja primero.'); window.location='admin.php?mod=mis_cajas';</script>";
    exit;
}

// Verificar usuario logueado
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Debes iniciar sesión.'); window.location='login.php';</script>";
    exit;
}

// Obtener datos de la caja activa
$stmt = $pdo->prepare("SELECT ac.*, c.nombre as caja_nombre, c.sucursal_id, s.nombre as sucursal_nombre 
                       FROM apertura_caja ac 
                       JOIN cajas c ON ac.caja_id = c.id 
                       JOIN sucursales s ON c.sucursal_id = s.id 
                       WHERE ac.id = ?");
$stmt->execute([$_SESSION['apertura_caja_id']]);
$caja_activa = $stmt->fetch();

// Procesar venta directamente
if(isset($_POST['btn_procesar_venta'])) {
    try {
        $pdo->beginTransaction();
        
        $cliente_id = $_POST['cliente_id'] ?: null;
        $metodo_pago_id = $_POST['metodo_pago_id'];
        $descuento_global = $_POST['descuento_global'] ?: 0;
        $productos = $_POST['productos'];
        $cantidades = $_POST['cantidades'];
        $precios = $_POST['precios'];
        
        $subtotal = 0;
        foreach($productos as $i => $producto_id) {
            $subtotal += $precios[$i] * $cantidades[$i];
        }
        $total = $subtotal - ($subtotal * $descuento_global / 100);
        
        // Insertar venta
        $sql = "INSERT INTO ventas (sucursal_id, usuario_id, cliente_id, apertura_caja_id, fecha_venta, descuento, total, estado) 
                VALUES (?, ?, ?, ?, NOW(), ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$caja_activa['sucursal_id'], $_SESSION['user_id'], $cliente_id, $_SESSION['apertura_caja_id'], $descuento_global, $total]);
        $venta_id = $pdo->lastInsertId();
        
        // Insertar detalles
        foreach($productos as $i => $producto_id) {
            $subtotal_linea = $precios[$i] * $cantidades[$i];
            $sql = "INSERT INTO venta_detalle (venta_id, producto_id, cantidad, precio_unitario, descuento_linea, subtotal_linea) 
                    VALUES (?, ?, ?, ?, 0, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$venta_id, $producto_id, $cantidades[$i], $precios[$i], $subtotal_linea]);
        }
        
        // Insertar pago
        $sql = "INSERT INTO venta_pagos (venta_id, metodo_pago_id, monto) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$venta_id, $metodo_pago_id, $total]);
        
        registrarLog($pdo, "VENTA_REGISTRADA", "Venta ID: $venta_id - Total: $total BOB");
        
        $pdo->commit();
        //echo "<script>alert('✅ Venta registrada exitosamente'); window.location='admin.php?mod=ventas';</script>";
        echo "<script> alert('✅ Venta registrada exitosamente');window.location='modulos/ticket_venta.php?venta_id=$venta_id&auto_print=1'; </script>";
    } catch(Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('❌ Error: " . $e->getMessage() . "');</script>";
    }
}

// Obtener productos
$categorias = $pdo->query("SELECT * FROM categorias WHERE estado = 1 ORDER BY nombre")->fetchAll();
$productos = $pdo->query("SELECT p.*, c.nombre as categoria_nombre 
                          FROM productos p 
                          JOIN categorias c ON p.categoria_id = c.id 
                          WHERE p.estado = 1 
                          ORDER BY c.nombre, p.nombre")->fetchAll();
?>

<style>
    .venta-container {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .productos-section {
        flex: 2;
        min-width: 300px;
    }
    .detalle-section {
        flex: 1;
        min-width: 350px;
        background: white;
        border-radius: 8px;
        padding: 20px;
        position: sticky;
        top: 20px;
        height: fit-content;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    .product-card {
        background: white;
        border-radius: 8px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.3s;
        border: 1px solid #eee;
        text-align: center;
    }
    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-color: #D4AF37;
    }
    .product-price {
        color: #D4AF37;
        font-size: 18px;
        font-weight: bold;
        margin: 10px 0;
    }
    .detalle-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        border-bottom: 1px solid #eee;
        margin-bottom: 10px;
    }
    .detalle-item input {
        width: 60px;
        padding: 5px;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .btn-eliminar {
        background: #ffebee;
        color: #c62828;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
    }
    .total-box {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
        text-align: center;
    }
    .total-box h2 {
        color: #D4AF37;
        margin: 0;
        font-size: 28px;
    }
    
    @media (max-width: 768px) {
        .venta-container {
            flex-direction: column;
        }
        .detalle-section {
            position: static;
        }
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 10px;
        }
        .product-card {
            padding: 10px;
        }
        .product-card h4 {
            font-size: 14px;
        }
    }
</style>

<!-- Header de caja -->
<div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <strong>💰 CAJA ACTIVA:</strong> <?php echo $caja_activa['caja_nombre']; ?> - <?php echo $caja_activa['sucursal_nombre']; ?>
            <br>
            <small>Monto inicial: <?php echo number_format($caja_activa['monto_inicial'], 2); ?> BOB | Abierta: <?php echo date('d/m/Y H:i', strtotime($caja_activa['fecha_apertura'])); ?></small>
        </div>
        <button onclick="location.href='admin.php?mod=cerrar_caja'" style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">🔒 CERRAR CAJA</button>
    </div>
</div>

<form method="POST" id="formVenta">
    <div class="venta-container">
        <!-- Sección de productos -->
        <div class="productos-section">
            <div style="background:#fff; padding:15px; border-radius:8px;">
                <select id="filtroCategoria" onchange="filtrarProductos()" style="padding:10px; border:1px solid #ddd; border-radius:4px; width:100%;">
                    <option value="todas">📋 Todas las categorías</option>
                    <?php foreach($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="product-grid" id="productosGrid">
                <?php foreach($productos as $prod): ?>
                <div class="product-card" data-categoria="<?php echo $prod['categoria_id']; ?>" onclick="agregarProducto(<?php echo $prod['id']; ?>, '<?php echo addslashes($prod['nombre']); ?>', <?php echo $prod['precio']; ?>)">
                    <h4><?php echo htmlspecialchars($prod['nombre']); ?></h4>
                    <small style="color:#999;"><?php echo $prod['categoria_nombre']; ?></small>
                    <div class="product-price"><?php echo number_format($prod['precio'], 2); ?> BOB</div>
                    <button type="button" class="btn-cart" style="background:#000; color:#D4AF37; border:none; padding:8px; border-radius:4px; width:100%; cursor:pointer;" onclick="event.stopPropagation(); agregarProducto(<?php echo $prod['id']; ?>, '<?php echo addslashes($prod['nombre']); ?>', <?php echo $prod['precio']; ?>)">
                        ➕ Agregar
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Sección de detalle de venta -->
        <div class="detalle-section">
            <h3 style="margin-top:0;">📝 Detalle de Venta</h3>
            
            <div id="detalleVenta">
                <p style="color:#999; text-align:center;">No hay productos agregados</p>
            </div>
            
            <div class="total-box">
                <small>SUBTOTAL</small>
                <h3 id="subtotal">0.00 BOB</h3>
                <hr>
                <small>DESCUENTO (%)</small>
                <input type="number" id="descuento_global" name="descuento_global" step="0.01" value="0" style="width:100%; padding:8px; margin:5px 0; border:1px solid #ddd; border-radius:4px;" onkeyup="calcularTotal()">
                <hr>
                <small>TOTAL</small>
                <h2 id="total">0.00 BOB</h2>
            </div>
            
            <div style="margin-bottom:15px;">
                <label>👤 Cliente</label>
                <select name="cliente_id" id="cliente_id" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                    <option value="">Venta al mostrador</option>
                    <?php 
                    $clientes = $pdo->query("SELECT id, nombre FROM clientes WHERE estado = 1 ORDER BY nombre")->fetchAll();
                    foreach($clientes as $cli): ?>
                        <option value="<?php echo $cli['id']; ?>"><?php echo htmlspecialchars($cli['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin-bottom:15px;">
                <label>💳 Método de Pago *</label>
                <select name="metodo_pago_id" id="metodo_pago_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                    <option value="">Seleccionar</option>
                    <?php 
                    $metodos = $pdo->query("SELECT id, nombre FROM metodos_pago WHERE estado = 1 ORDER BY nombre")->fetchAll();
                    foreach($metodos as $mp): ?>
                        <option value="<?php echo $mp['id']; ?>"><?php echo htmlspecialchars($mp['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="button" onclick="procesarVenta()" style="background:#000; color:#D4AF37; width:100%; padding:15px; border:none; border-radius:4px; font-weight:bold; font-size:16px; cursor:pointer;">
                💰 PROCESAR VENTA
            </button>
        </div>
    </div>
    
    <!-- Campos ocultos para enviar los productos -->
    <div id="productosHidden"></div>
    <input type="submit" name="btn_procesar_venta" id="realSubmitVenta" style="display:none;">
</form>

<script>
let productosVenta = [];

function agregarProducto(id, nombre, precio) {
    const existente = productosVenta.find(p => p.id === id);
    if(existente) {
        existente.cantidad++;
    } else {
        productosVenta.push({id, nombre, precio, cantidad: 1});
    }
    actualizarDetalle();
}

function actualizarDetalle() {
    const detalleDiv = document.getElementById('detalleVenta');
    const subtotalSpan = document.getElementById('subtotal');
    
    if(productosVenta.length === 0) {
        detalleDiv.innerHTML = '<p style="color:#999; text-align:center;">No hay productos agregados</p>';
        subtotalSpan.innerText = '0.00 BOB';
        calcularTotal();
        return;
    }
    
    let subtotal = 0;
    let html = '';
    
    productosVenta.forEach((item, index) => {
        const itemTotal = item.precio * item.cantidad;
        subtotal += itemTotal;
        
        html += `
            <div class="detalle-item">
                <div style="flex:2;">
                    <strong>${item.nombre}</strong><br>
                    <small>${item.precio.toFixed(2)} BOB</small>
                </div>
                <div style="flex:1; text-align:center;">
                    <input type="number" value="${item.cantidad}" min="1" onchange="cambiarCantidad(${index}, this.value)">
                </div>
                <div style="flex:1; text-align:right;">
                    <strong>${itemTotal.toFixed(2)} BOB</strong><br>
                    <button type="button" class="btn-eliminar" onclick="eliminarProducto(${index})">🗑️</button>
                </div>
            </div>
        `;
    });
    
    detalleDiv.innerHTML = html;
    subtotalSpan.innerText = subtotal.toFixed(2) + ' BOB';
    calcularTotal();
}

function cambiarCantidad(index, nuevaCantidad) {
    productosVenta[index].cantidad = parseInt(nuevaCantidad);
    if(productosVenta[index].cantidad <= 0) {
        productosVenta.splice(index, 1);
    }
    actualizarDetalle();
}

function eliminarProducto(index) {
    productosVenta.splice(index, 1);
    actualizarDetalle();
}

function calcularTotal() {
    const subtotal = productosVenta.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
    const descuento = parseFloat(document.getElementById('descuento_global').value) || 0;
    const total = subtotal - (subtotal * descuento / 100);
    document.getElementById('total').innerText = total.toFixed(2) + ' BOB';
}

function filtrarProductos() {
    const categoria = document.getElementById('filtroCategoria').value;
    const productos = document.querySelectorAll('.product-card');
    
    productos.forEach(producto => {
        if(categoria === 'todas' || producto.dataset.categoria === categoria) {
            producto.style.display = 'block';
        } else {
            producto.style.display = 'none';
        }
    });
}

function procesarVenta() {
    if(productosVenta.length === 0) {
        alert('❌ Agrega productos a la venta');
        return;
    }
    
    const metodoPago = document.getElementById('metodo_pago_id').value;
    if(!metodoPago) {
        alert('❌ Selecciona un método de pago');
        return;
    }
    
    // Crear campos ocultos para enviar
    const hiddenDiv = document.getElementById('productosHidden');
    hiddenDiv.innerHTML = '';
    
    productosVenta.forEach(item => {
        hiddenDiv.innerHTML += `
            <input type="hidden" name="productos[]" value="${item.id}">
            <input type="hidden" name="cantidades[]" value="${item.cantidad}">
            <input type="hidden" name="precios[]" value="${item.precio}">
        `;
    });
    
    document.getElementById('realSubmitVenta').click();
}
</script>