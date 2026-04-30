<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexion.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';
    
    if ($password_nueva !== $password_confirmar) {
        $error = 'Las contraseñas nuevas no coinciden';
    } elseif (strlen($password_nueva) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        try {
            $bd = new ConexionBD();
            $conn = $bd->getConexion();
            
            $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['usuario_id']);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $usuario = $resultado->fetch_assoc();
            
            if (password_verify($password_actual, $usuario['password'])) {
                $nuevo_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $nuevo_hash, $_SESSION['usuario_id']);
                $stmt->execute();
                $mensaje = 'Contraseña actualizada correctamente';
            } else {
                $error = 'Contraseña actual incorrecta';
            }
        } catch (Exception $e) {
            $error = 'Error en el sistema';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 { text-align: center; margin-bottom: 30px; color: #333; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #555; }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        .mensaje { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .link { text-align: center; margin-top: 20px; }
        .link a { color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Cambiar Contraseña</h1>
        
        <?php if ($mensaje): ?>
            <div class="mensaje"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Contraseña actual</label>
                <input type="password" name="password_actual" required>
            </div>
            <div class="form-group">
                <label>Nueva contraseña</label>
                <input type="password" name="password_nueva" required>
            </div>
            <div class="form-group">
                <label>Confirmar nueva contraseña</label>
                <input type="password" name="password_confirmar" required>
            </div>
            <button type="submit">Actualizar Contraseña</button>
        </form>
        <div class="link">
            <a href="ver_examenes.php">← Volver al panel</a>
        </div>
    </div>
</body>
</html>