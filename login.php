<?php
session_start();
include 'config.php'; // Tu conexión PDO

if (isset($_POST['btn_login'])) {
    $user = $_POST['usuario'];
    $pass = $_POST['password'];

    // Buscamos al usuario activo
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND estado = 1 LIMIT 1");
    $stmt->execute([$user]);
    $u = $stmt->fetch();

    // Verificamos contraseña con el hash de la base de datos
    if ($u && password_verify($pass, $u['password'])) {
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['user_nombre'] = $u['nombre'];
        $_SESSION['user_rol'] = $u['rol'];
        
        // Después de $_SESSION['user_id'] = $u['id'];
        restaurarCajaAbierta($pdo, $u['id']);
        // Actualizamos último acceso
        $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?")->execute([$u['id']]);
        
        header("Location: admin.php");
        exit;
    } else {
        $error = "Acceso denegado. Verifica tus credenciales.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Admin Luxury</title>
    <style>
        body { background: #0b0b0b; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; color: #fff; }
        .login-box { background: #141414; padding: 40px; border-radius: 10px; border-top: 4px solid #D4AF37; width: 320px; box-shadow: 0 15px 35px rgba(0,0,0,0.7); }
        h2 { color: #D4AF37; text-align: center; margin-bottom: 30px; letter-spacing: 1px; }
        input { width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #333; background: #1a1a1a; color: #fff; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #D4AF37; color: #000; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        button:hover { background: #f1c40f; }
        .error { background: #ff4d4d22; color: #ff4d4d; padding: 10px; border-radius: 5px; font-size: 13px; margin-bottom: 20px; text-align: center; border: 1px solid #ff4d4d; }
        
        /* ===== LO QUE AGREGAMOS ===== */
        /* Logo */
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            max-width: 100%;
            height: auto;
            max-height: 100px;
        }
        
        /* Responsivo */
        @media (max-width: 480px) {
            .login-box {
                width: 90%;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-box">
        <!-- Logo agregado arriba del recuadro -->
        <div class="logo">
            <img src="img/logito.png" alt="Logo Luxury">
        </div>
        
        <h2>INICIAR SESION</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="usuario" placeholder="Usuario" required autofocus>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit" name="btn_login">INICIAR SESIÓN</button>
        </form>
    </div>
</body>
</html>