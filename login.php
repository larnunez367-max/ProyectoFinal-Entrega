<?php
require_once __DIR__ . '/auth.php';

if (!function_exists('e')) {
    function e($valor) {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }
}

if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . destinoSegunRol($_SESSION['usuario_rol'] ?? ''));
    exit;
}

$errores      = [];
$nuevoUsuario = '';
$email        = '';
$dni          = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoUsuario = trim($_POST['user'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $dni          = trim($_POST['dni'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirmacion = $_POST['password_confirm'] ?? '';

    if ($nuevoUsuario === '' || strlen($nuevoUsuario) < 3 || strlen($nuevoUsuario) > 50) {
        $errores[] = 'El usuario debe tener entre 3 y 50 caracteres.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresá un correo válido.';
    }
    if (!preg_match('/^[0-9]{7,8}$/', $dni)) {
        $errores[] = 'El DNI debe tener 7 u 8 números.';
    }
    if (strlen($password) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if ($password !== $confirmacion) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        try {
            require_once __DIR__ . '/conexion.php';

            $check = $conexion->prepare(
                'SELECT `user`, email, dni FROM users WHERE `user` = :user OR email = :email OR dni = :dni LIMIT 1'
            );
            $check->execute([
                'user'  => $nuevoUsuario,
                'email' => $email,
                'dni'   => $dni,
            ]);
            $existente = $check->fetch(PDO::FETCH_ASSOC);

            if ($existente) {
                if (strcasecmp($existente['user'], $nuevoUsuario) === 0) {
                    $errores[] = 'Ese nombre de usuario ya está en uso.';
                }
                if (strcasecmp($existente['email'], $email) === 0) {
                    $errores[] = 'Ese correo electrónico ya está registrado.';
                }
                if ((string)$existente['dni'] === (string)$dni) {
                    $errores[] = 'Ese DNI ya está registrado.';
                }
            } else {
                $stmt = $conexion->prepare(
                    'INSERT INTO users (`user`, email, dni, password_hash, rol)
                    VALUES (:user, :email, :dni, :password_hash, :rol)'
                );
                $stmt->execute([
                    'user'          => $nuevoUsuario,
                    'email'         => $email,
                    'dni'           => $dni,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'rol'           => 'usuario',
                ]);

                header('Location: index.php?registro=ok');
                exit;
            }
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            $errores[] = 'Error de base de datos: ' . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Crear cuenta</title>
</head>
<body>

    <div>
        <h1>Crear cuenta</h1>
        <p class="subtitulo">Completá los datos para registrarte</p>

        <?php if (!empty($errores)): ?>
            <div class="mensaje mensaje-error" role="alert">
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="registroForm" autocomplete="off">

            <fieldset>
                <div class="control-group">
                    <label for="user">Crear usuario*</label>
                    <input type="text" id="user" name="user" placeholder="Usuario" maxlength="50"
                        value="<?= e($nuevoUsuario) ?>" autocomplete="off" required>
                </div>

                <div class="control-group">
                    <label for="email">Correo electrónico*</label>
                    <input type="email" id="email" name="email" placeholder="nombre@correo.com"
                        value="<?= e($email) ?>" autocomplete="off" required>
                </div>

                <div class="control-group">
                    <label for="dni">DNI*</label>
                    <input type="text" id="dni" name="dni" placeholder="Solo números" inputmode="numeric"
                        maxlength="8" pattern="[0-9]{7,8}" value="<?= e($dni) ?>" autocomplete="off" required>
                </div>

                <div class="control-group">
                    <label for="password">Crear contraseña*</label>
                    <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres"
                        minlength="8" autocomplete="new-password" required>
                </div>

                <div class="control-group">
                    <label for="password_confirm">Repetir contraseña*</label>
                    <input type="password" id="password_confirm" name="password_confirm"
                        placeholder="Repetí la contraseña" minlength="8" autocomplete="new-password" required>
                </div>
            </fieldset>

            <div class="botones-container">
                <button type="submit" class="btn-guardar">Crear cuenta</button>
                <a href="index.php" class="btn-guardar2">Volver</a>
            </div>

        </form>
    </div>

</body>
</html>
