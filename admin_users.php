<?php
require_once __DIR__ . '/auth.php';
requerirAdmin();
require_once __DIR__ . '/conexion.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$soloArchivados = ($_GET['vista'] ?? '') === 'archivados';
$buscar = trim($_GET['buscar'] ?? '');
$sql = 'SELECT id, `user`, email, dni, rol, creado_en, archivado_en FROM users WHERE archivado_en ';
$sql .= $soloArchivados ? 'IS NOT NULL' : 'IS NULL';
$parametros = [];

if ($buscar !== '') {
    $sql .= ' AND (`user` LIKE :user OR dni LIKE :dni OR email LIKE :email)';
    $termino = '%' . addcslashes($buscar, '%_\\') . '%';
    $parametros = ['user' => $termino, 'dni' => $termino, 'email' => $termino];
}
$sql .= ' ORDER BY id DESC';
$usuarios = [];
$error = '';

try {
    $stmt = $conexion->prepare($sql);
    $stmt->execute($parametros);
    $usuarios = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    $error = 'No pudimos cargar los usuarios. Intentá de nuevo.';
}

$total = count($usuarios);
$token = csrfToken();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Administración</title>

        <style>
        body > div.tarjeta-ancha {
            max-width: 960px !important;
        }
        .tarjeta-ancha > p {
            text-align: center;
            margin-bottom: 1.25rem;
        }
        .busqueda {
            display: flex !important;
            flex-direction: row !important;
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        .busqueda input[type="search"] {
            flex: 1;
            padding: 10px 14px;
            border: 2px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            color: #1e293b;
            background: #f1f5f9;
        }
        .busqueda .btn-guardar {
            flex: 0 0 auto !important;
            padding: 10px 24px !important;
        }
        .tabla-contenedor {
            width: 100%;
            overflow-x: auto;
            background: rgba(15, 23, 42, 0.3);
            border: 1px solid rgba(147, 197, 253, 0.25);
            border-radius: 12px;
            padding: 0.75rem;
        }
        .tabla-usuarios {
            width: 100%;
            border-collapse: collapse;
            color: #f8fafc;
            font-size: 0.92rem;
            text-align: left;
        }
        .tabla-usuarios th,
        .tabla-usuarios td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(147, 197, 253, 0.2);
            vertical-align: middle;
        }
        .tabla-usuarios th {
            color: #93c5fd;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .tabla-usuarios tbody tr:last-child td {
            border-bottom: none;
        }
        .tabla-usuarios tbody tr:hover {
            background: rgba(255, 255, 255, 0.06);
        }
        .acciones {
            display: flex !important;
            flex-direction: row !important;
            gap: 8px !important;
            align-items: center !important;
        }
        .form-inline {
            display: inline-flex !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .btn-chico {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 7px 14px !important;
            background: #38bdf8 !important;
            color: #0f172a !important;
            border: none !important;
            border-radius: 8px !important;
            font-family: inherit !important;
            font-size: 0.82rem !important;
            font-weight: 700 !important;
            line-height: 1.2 !important;
            text-decoration: none !important;
            cursor: pointer !important;
            transition: background 0.2s, transform 0.15s;
        }
        .btn-chico:hover {
            background: #7dd3fc !important;
            transform: translateY(-1px);
        }
        .btn-peligro {
            background: #ef4444 !important;
            color: #ffffff !important;
        }
        .btn-peligro:hover {
            background: #dc2626 !important;
        }
        .vacio {
            color: #bfdbfe !important;
            text-align: center;
            padding: 1.5rem 0 0.5rem;
            font-size: 0.95rem;
        }
    </style>

</head>

<body>

    <nav class="navbar">
        <a id="log" href="admin_users.php">
            <img src="img/loog.png" alt="Logo del Movistar Arena" height="48">
        </a>
        <ul class="nav-links">
            <li><a href="admin_users.php">Usuarios</a></li>
            <li><a href="signout.php">Cerrar sesión</a></li>
        </ul>
    </nav>

    <div class="tarjeta-ancha">
        <h1><?= $soloArchivados ? 'Usuarios archivados' : 'Usuarios activos' ?></h1>
        <p class="subtitulo">
            <?= $total === 1 ? '1 usuario' : $total . ' usuarios' ?>
            <?php if ($buscar !== ''): ?>
                que coinciden con «<?= e($buscar) ?>»
            <?php endif; ?>
        </p>

        <p>
            <?php if ($soloArchivados): ?>
                <a href="admin_users.php" class="btn-chico">Ver usuarios activos</a>
            <?php else: ?>
                <a href="admin_users.php?vista=archivados" class="btn-chico">Ver archivados</a>
            <?php endif; ?>
        </p>

        <?php if ($flash): ?>
            <p class="mensaje <?= $flash['tipo'] === 'exito' ? 'mensaje-exito' : 'mensaje-error' ?>" role="status">
                <?= e($flash['texto']) ?>
            </p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="mensaje mensaje-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="GET" action="admin_users.php" class="busqueda">
            <?php if ($soloArchivados): ?>
                <input type="hidden" name="vista" value="archivados">
            <?php endif; ?>
            <input type="search" name="buscar" maxlength="100" value="<?= e($buscar) ?>"
                placeholder="Usuario, DNI o correo">
            <button type="submit" class="btn-guardar">Buscar</button>
        </form>

        <?php if ($total === 0): ?>
            <p class="vacio">No hay usuarios para mostrar.</p>
        <?php else: ?>
            <div class="tabla-contenedor">
                <table class="tabla-usuarios">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Correo</th>
                            <th>DNI</th>
                            <th>Rol</th>
                            <th><?= $soloArchivados ? 'Archivado' : 'Ingreso' ?></th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= e($u['user']) ?></td>
                                <td><?= e($u['email']) ?></td>
                                <td><?= e($u['dni']) ?></td>
                                <td><?= e($u['rol']) ?></td>
                                <td><?= e(date('d/m/Y', strtotime($soloArchivados ? $u['archivado_en'] : $u['creado_en']))) ?></td>
                                <td>
                                    <div class="acciones">
                                        <?php if (!$soloArchivados): ?>
                                            <a href="edit_users.php?id=<?= (int)$u['id'] ?>" class="btn-chico">Editar</a>
                                            <?php if ((int)$u['id'] !== (int)$_SESSION['usuario_id']): ?>
                                                <form method="POST" action="archivar_users.php" class="form-inline"
                                                    onsubmit="return confirm('¿Archivar a este usuario?');">
                                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                    <input type="hidden" name="csrf" value="<?= e($token) ?>">
                                                    <button type="submit" class="btn-chico btn-peligro">Archivar</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <form method="POST" action="reactivar_users.php" class="form-inline"
                                                onsubmit="return confirm('¿Reactivar a este usuario?');">
                                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                <input type="hidden" name="csrf" value="<?= e($token) ?>">
                                                <button type="submit" class="btn-chico">Reactivar</button>
                                            </form>
                                            <?php if ((int)$u['id'] !== (int)$_SESSION['usuario_id']): ?>
                                                <form method="POST" action="eliminar_users.php" class="form-inline"
                                                    onsubmit="return confirm('¿Eliminar definitivamente a este usuario? Esta acción no se puede deshacer.');">
                                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                    <input type="hidden" name="csrf" value="<?= e($token) ?>">
                                                    <button type="submit" class="btn-chico btn-peligro">Eliminar</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        &copy; <?= date('Y') ?> Movistar Arena de Lara Nuñez.
    </footer>

</body>

</html>