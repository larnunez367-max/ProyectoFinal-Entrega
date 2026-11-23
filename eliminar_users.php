<?php
require_once __DIR__ . '/auth.php';
requerirAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_users.php?vista=archivados');
    exit;
}

if (!csrfValido($_POST['csrf'] ?? null)) {
    http_response_code(403);
    exit('Solicitud no válida.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('Identificador inválido.');
}

if ($id === (int)$_SESSION['usuario_id']) {
    $_SESSION['flash'] = ['tipo' => 'error', 'texto' => 'No podés eliminar tu propia cuenta.'];
    header('Location: admin_users.php?vista=archivados');
    exit;
}

require_once __DIR__ . '/conexion.php';

try {
    $stmt = $conexion->prepare(
        'DELETE FROM users WHERE id = :id AND archivado_en IS NOT NULL'
    );
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['flash'] = ['tipo' => 'exito', 'texto' => 'Usuario eliminado definitivamente.'];
    } else {
        $_SESSION['flash'] = ['tipo' => 'error', 'texto' => 'No se encontró el usuario archivado.'];
    }
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    $_SESSION['flash'] = ['tipo' => 'error', 'texto' => 'No se pudo eliminar el usuario.'];
}

header('Location: admin_users.php?vista=archivados');
exit;