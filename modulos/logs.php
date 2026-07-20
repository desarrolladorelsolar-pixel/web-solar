<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Traemos los logs y el nombre del usuario que lo hizo
$sql = "SELECT l.*, u.usuario as nick 
        FROM logs_actividad l 
        LEFT JOIN usuarios u ON l.usuario_id = u.id 
        ORDER BY l.created_at DESC LIMIT 100";
$logs = $pdo->query($sql)->fetchAll();
?>
<style>
/* ===== CSS RESPONSIVO PARA MÓDULO DE LOGS ===== */

/* Tablets y pantallas medianas (768px y menos) */
@media (max-width: 768px) {
    /* Contenedor principal */
    div[style*="background:#fff"] {
        padding: 15px !important;
    }
    
    h2 {
        font-size: 1.3em !important;
    }
    
    /* Tabla con scroll horizontal */
    table {
        display: block !important;
        overflow-x: auto !important;
        white-space: nowrap !important;
        font-size: 12px !important;
    }
    
    th, td {
        padding: 8px !important;
    }
    
    /* Columna fecha más compacta */
    td:first-child, th:first-child {
        min-width: 140px;
    }
    
    /* Columna IP más pequeña */
    td:last-child, th:last-child {
        min-width: 100px;
    }
}

/* Móviles (480px y menos) */
@media (max-width: 480px) {
    div[style*="background:#fff"] {
        padding: 10px !important;
    }
    
    h2 {
        font-size: 1.1em !important;
        margin-bottom: 10px !important;
    }
    
    table {
        font-size: 11px !important;
    }
    
    th, td {
        padding: 6px !important;
    }
    
    /* Texto de acción más pequeño */
    td span {
        font-size: 10px !important;
    }
    
    /* Detalle truncado con puntos suspensivos */
    td:nth-child(4) {
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

/* Móviles muy pequeños (375px) */
@media (max-width: 375px) {
    h2 {
        font-size: 1em !important;
    }
    
    table {
        font-size: 10px !important;
    }
    
    th, td {
        padding: 5px !important;
    }
    
    td:nth-child(4) {
        max-width: 120px;
    }
    
    /* Columna usuario más compacta */
    td:nth-child(2), th:nth-child(2) {
        min-width: 70px;
    }
}
</style>
<div style="background:#fff; padding:20px; border-radius:8px; border:1px solid #eee;">
    <h2 style="margin-top:0; color:#333;">📜 Historial de Actividad</h2>
    <table style="width:100%; border-collapse: collapse; font-size:14px;">
        <thead>
            <tr style="background:#000; color:#D4AF37; text-align:left;">
                <th style="padding:12px;">Fecha/Hora</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Detalle</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $l): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding:10px; color:#888;"><?php echo $l['created_at']; ?></td>
                <td><strong><?php echo $l['nick'] ?? 'Sistema'; ?></strong></td>
                <td>
                    <span style="font-weight:bold; color: <?php 
                        echo (strpos($l['accion'], 'ELIMINAR') !== false) ? 'red' : 'green'; 
                    ?>">
                        <?php echo $l['accion']; ?>
                    </span>
                </td>
                <td><?php echo $l['detalle']; ?></td>
                <td style="font-family:monospace; color:#aaa;"><?php echo $l['ip_address']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>