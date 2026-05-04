<?php

/**
 * admin/dashboard.php — Panel de administración
 *
 * Solo accesible para usuarios con rol 'admin'.
 * Tabs: stats (resumen global), usuarios (CRUD), hilos (CRUD).
 *
 * Las acciones de eliminación se procesan por POST antes de imprimir
 * HTML para poder redirigir sin errores de headers already sent.
 */

require_once '../includes/db.php';

// Proteger la ruta: solo admins pueden entrar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Eliminar usuario — no permite eliminar admins ni al propio usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_usuario'])) {
    $uid = (int)$_POST['eliminar_usuario'];
    $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND rol != 'admin' AND id != ?")
        ->execute([$uid, $_SESSION['usuario_id']]);
    header('Location: dashboard.php?tab=usuarios');
    exit;
}

// Alternar rol entre user y admin — no permite modificar al propio usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_rol'])) {
    $uid = (int)$_POST['toggle_rol'];
    if ($uid !== (int)$_SESSION['usuario_id']) {
        // Leer el rol actual y aplicar el opuesto
        $stmtRol = $pdo->prepare('SELECT rol FROM usuarios WHERE id = ?');
        $stmtRol->execute([$uid]);
        $rolActual  = $stmtRol->fetchColumn();
        $nuevoRol   = ($rolActual === 'admin') ? 'user' : 'admin';
        $pdo->prepare('UPDATE usuarios SET rol = ? WHERE id = ?')
            ->execute([$nuevoRol, $uid]);
    }
    header('Location: dashboard.php?tab=usuarios');
    exit;
}

// Eliminar hilo — elimina también sus respuestas y likes por CASCADE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_hilo'])) {
    $hid = (int)$_POST['eliminar_hilo'];
    $pdo->prepare("DELETE FROM hilos WHERE id = ?")
        ->execute([$hid]);
    header('Location: dashboard.php?tab=hilos');
    exit;
}

// Pestaña activa — por defecto usuarios
$tab = $_GET['tab'] ?? 'usuarios';

// Usuarios con conteo de hilos, ordenados por fecha de registro desc
$usuarios = $pdo->query("
    SELECT
        u.id,
        u.username,
        u.email,
        u.rol,
        u.created_at,
        COUNT(h.id) AS total_hilos
    FROM usuarios u
    LEFT JOIN hilos h ON h.usuario_id = u.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();

// Hilos recientes con autor y sección
$hilos = $pdo->query("
    SELECT
        h.id,
        h.titulo,
        h.created_at,
        u.username   AS autor,
        u.id         AS autor_id,
        s.nombre     AS seccion
    FROM hilos h
    JOIN usuarios u  ON u.id = h.usuario_id
    JOIN secciones s ON s.id = h.seccion_id
    ORDER BY h.created_at DESC
    LIMIT 50
")->fetchAll();

$activeSection = "admin";
$basePath      = "../";
include '../includes/header.php';

// Formato de fecha legible para la tabla
function fechaCorta(string $fecha): string
{
    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $ts    = strtotime($fecha);
    return date('d', $ts) . ' ' . $meses[date('n', $ts) - 1] . ' ' . date('Y', $ts);
}
?>

<!-- =============================================
     CABECERA DEL PANEL
     ============================================= -->
<section class="page-header">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h1 class="page-header-title">
                    <i class="bi bi-shield-lock-fill me-2 text-neon-pink"></i>
                    Panel de Administración
                </h1>
                <p class="page-header-sub">
                    Gestión de usuarios, hilos y estadísticas globales
                </p>
            </div>
            <a href="<?= $basePath ?>forum.php" class="btn btn-neon btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver al Foro
            </a>
        </div>
    </div>
</section>

<!-- =============================================
     CONTENIDO PRINCIPAL
     ============================================= -->
<main class="py-4">
    <div class="container">

        <!-- Pestañas de navegación -->
        <div class="admin-tabs mb-4">
            <a href="?tab=usuarios"
                class="admin-tab <?= $tab === 'usuarios' ? 'admin-tab-active' : '' ?>">
                <i class="bi bi-people-fill me-2"></i>Usuarios
                <span class="ms-2 badge-cyan"><?= count($usuarios) ?></span>
            </a>
            <a href="?tab=hilos"
                class="admin-tab <?= $tab === 'hilos' ? 'admin-tab-active' : '' ?>">
                <i class="bi bi-chat-square-text-fill me-2"></i>Hilos
                <span class="ms-2 badge-pink"><?= count($hilos) ?></span>
            </a>
        </div>

        <!-- ============================================
             PESTAÑA: GESTIÓN DE USUARIOS
             ============================================ -->
        <?php if ($tab === 'usuarios'): ?>
            <div class="sidebar-card">
                <div class="sidebar-card-header">
                    <i class="bi bi-people-fill me-2 text-neon-cyan"></i>
                    Gestión de Usuarios
                    <span class="ms-auto text-muted-gb"><?= count($usuarios) ?> registrados</span>
                </div>
                <div class="table-responsive admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Hilos</th>
                                <th>Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-placeholder flex-shrink-0"
                                                style="width:28px;height:28px;font-size:.6rem;">
                                                <?= strtoupper(substr($u['username'], 0, 2)) ?>
                                            </div>
                                            <a href="<?= $basePath ?>profile.php?user=<?= $u['id'] ?>"
                                                class="text-neon-cyan text-decoration-none">
                                                <?= htmlspecialchars($u['username']) ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="text-muted-gb"><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <?php if ($u['rol'] === 'admin'): ?>
                                            <span class="badge-pink">Admin</span>
                                        <?php else: ?>
                                            <span class="badge-cyan">Usuario</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-neon-cyan"><?= $u['total_hilos'] ?></td>
                                    <td class="text-muted-gb"><?= fechaCorta($u['created_at']) ?></td>
                                    <td>
                                        <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                                            <div class="d-flex gap-2">
                                                <!-- Alternar rol: user ↔ admin -->
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="toggle_rol" value="<?= $u['id'] ?>" />
                                                    <button type="submit"
                                                        class="post-action-btn <?= $u['rol'] === 'admin' ? 'post-action-btn-danger' : '' ?>"
                                                        title="<?= $u['rol'] === 'admin' ? 'Quitar admin' : 'Hacer admin' ?>">
                                                        <i class="bi <?= $u['rol'] === 'admin' ? 'bi-shield-fill-minus' : 'bi-shield-fill-plus' ?>"></i>
                                                    </button>
                                                </form>
                                                <!-- Eliminar solo si no es admin -->
                                                <?php if ($u['rol'] !== 'admin'): ?>
                                                    <form method="POST" style="display:inline;"
                                                        onsubmit="return confirmDelete('usuario', '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
                                                        <input type="hidden" name="eliminar_usuario" value="<?= $u['id'] ?>" />
                                                        <button type="submit"
                                                            class="post-action-btn post-action-btn-danger"
                                                            title="Eliminar usuario">
                                                            <i class="bi bi-trash-fill"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted-gb" style="font-size:.72rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ============================================
             PESTAÑA: GESTIÓN DE HILOS
             ============================================ -->
        <?php elseif ($tab === 'hilos'): ?>
            <div class="sidebar-card">
                <div class="sidebar-card-header">
                    <i class="bi bi-chat-square-text-fill me-2 text-neon-pink"></i>
                    Gestión de Hilos
                    <span class="ms-auto text-muted-gb"><?= count($hilos) ?> hilos</span>
                </div>
                <div class="table-responsive admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Autor</th>
                                <th>Sección</th>
                                <th>Publicado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hilos as $h): ?>
                                <tr>
                                    <td>
                                        <a href="<?= $basePath ?>thread.php?id=<?= $h['id'] ?>"
                                            class="text-neon-cyan text-decoration-none admin-thread-title">
                                            <?= htmlspecialchars($h['titulo']) ?>
                                        </a>
                                    </td>
                                    <td class="text-muted-gb">
                                        <a href="<?= $basePath ?>profile.php?user=<?= $h['autor_id'] ?>"
                                            class="text-muted-gb text-decoration-none">
                                            <?= htmlspecialchars($h['autor']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge-section">
                                            <?= htmlspecialchars($h['seccion']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted-gb"><?= fechaCorta($h['created_at']) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;"
                                            onsubmit="return confirmDelete('hilo', '<?= htmlspecialchars($h['titulo'], ENT_QUOTES) ?>')">
                                            <input type="hidden" name="eliminar_hilo" value="<?= $h['id'] ?>" />
                                            <button type="submit"
                                                class="post-action-btn post-action-btn-danger"
                                                title="Eliminar hilo">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($hilos)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted-gb py-4">
                                        No hay hilos publicados.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php include '../includes/footer.php'; ?>