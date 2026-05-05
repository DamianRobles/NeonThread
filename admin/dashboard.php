<?php
/**
 * admin/dashboard.php — Panel de administración
 * CORRECCIONES: CSRF en todas las acciones; pestaña Estadísticas implementada;
 * funciones de fecha vienen de db.php.
 */
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: ../login.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();

    if (isset($_POST['eliminar_usuario'])) {
        $uid = (int)$_POST['eliminar_usuario'];
        $pdo->prepare("DELETE FROM usuarios WHERE id=? AND rol!='admin' AND id!=?")
            ->execute([$uid, $_SESSION['usuario_id']]);
        header('Location: dashboard.php?tab=usuarios'); exit;
    }
    if (isset($_POST['toggle_rol'])) {
        $uid = (int)$_POST['toggle_rol'];
        if ($uid !== (int)$_SESSION['usuario_id']) {
            $r = $pdo->prepare('SELECT rol FROM usuarios WHERE id=?');
            $r->execute([$uid]);
            $rol = $r->fetchColumn();
            $pdo->prepare('UPDATE usuarios SET rol=? WHERE id=?')
                ->execute([$rol === 'admin' ? 'user' : 'admin', $uid]);
        }
        header('Location: dashboard.php?tab=usuarios'); exit;
    }
    if (isset($_POST['eliminar_hilo'])) {
        $pdo->prepare("DELETE FROM hilos WHERE id=?")->execute([(int)$_POST['eliminar_hilo']]);
        header('Location: dashboard.php?tab=hilos'); exit;
    }
}

$tab = $_GET['tab'] ?? 'stats';

$usuarios = $pdo->query("
    SELECT u.id, u.username, u.email, u.rol, u.created_at, COUNT(h.id) AS total_hilos
    FROM usuarios u LEFT JOIN hilos h ON h.usuario_id = u.id
    GROUP BY u.id ORDER BY u.created_at DESC
")->fetchAll();

$hilos = $pdo->query("
    SELECT h.id, h.titulo, h.created_at, u.username AS autor, u.id AS autor_id, s.nombre AS seccion
    FROM hilos h
    JOIN usuarios u  ON u.id = h.usuario_id
    JOIN secciones s ON s.id = h.seccion_id
    ORDER BY h.created_at DESC LIMIT 50
")->fetchAll();

$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM usuarios)   AS total_usuarios,
        (SELECT COUNT(*) FROM hilos)      AS total_hilos,
        (SELECT COUNT(*) FROM respuestas) AS total_respuestas,
        (SELECT COUNT(*) FROM likes)      AS total_likes
")->fetch();
$stats['nuevos_7d'] = (int)$pdo->query(
    "SELECT COUNT(*) FROM usuarios WHERE created_at >= datetime('now','-7 days')"
)->fetchColumn();

$hilosPorSeccion = $pdo->query("
    SELECT s.nombre, s.icono, COUNT(h.id) AS total
    FROM secciones s LEFT JOIN hilos h ON h.seccion_id = s.id
    GROUP BY s.id ORDER BY total DESC
")->fetchAll();

$activeSection = 'admin'; $basePath = '../';
include '../includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h1 class="page-header-title">
                    <i class="bi bi-shield-lock-fill me-2 text-neon-pink"></i>Panel de Administración
                </h1>
                <p class="page-header-sub">Gestión de usuarios, hilos y estadísticas globales</p>
            </div>
            <a href="../forum.php" class="btn btn-neon btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver al Foro
            </a>
        </div>
    </div>
</section>

<main class="py-4">
<div class="container">

    <div class="admin-tabs mb-4">
        <a href="?tab=stats"    class="admin-tab <?= $tab==='stats'    ? 'admin-tab-active':'' ?>">
            <i class="bi bi-bar-chart-fill me-2"></i>Estadísticas
        </a>
        <a href="?tab=usuarios" class="admin-tab <?= $tab==='usuarios' ? 'admin-tab-active':'' ?>">
            <i class="bi bi-people-fill me-2"></i>Usuarios
            <span class="ms-2 badge-cyan"><?= count($usuarios) ?></span>
        </a>
        <a href="?tab=hilos"    class="admin-tab <?= $tab==='hilos'    ? 'admin-tab-active':'' ?>">
            <i class="bi bi-chat-square-text-fill me-2"></i>Hilos
            <span class="ms-2 badge-pink"><?= count($hilos) ?></span>
        </a>
    </div>

    <?php if ($tab === 'stats'): ?>
        <!-- ── ESTADÍSTICAS ── -->
        <div class="row g-3 mb-4">
            <?php
            $metricas = [
                ['Usuarios registrados', $stats['total_usuarios'],   'bi-people-fill',           'text-neon-cyan'],
                ['Hilos publicados',     $stats['total_hilos'],      'bi-chat-square-text-fill', 'text-neon-pink'],
                ['Respuestas totales',   $stats['total_respuestas'], 'bi-reply-all-fill',        'text-neon-cyan'],
                ['Likes dados',          $stats['total_likes'],      'bi-heart-fill',            'text-neon-pink'],
                ['Nuevos usuarios (7d)', $stats['nuevos_7d'],        'bi-person-plus-fill',      'text-neon-cyan'],
            ];
            foreach ($metricas as [$label, $valor, $icono, $color]):
            ?>
                <div class="col-6 col-md-4 col-lg">
                    <div class="sidebar-card h-100">
                        <div class="sidebar-card-body text-center py-3">
                            <i class="bi <?= $icono ?> <?= $color ?>" style="font-size:1.5rem;"></i>
                            <div style="font-size:1.8rem;font-weight:700;margin:.5rem 0;" class="<?= $color ?>">
                                <?= number_format($valor) ?>
                            </div>
                            <div class="text-muted-gb" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:1px;">
                                <?= $label ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-card">
            <div class="sidebar-card-header">
                <i class="bi bi-collection-fill me-2 text-neon-cyan"></i>Actividad por sección
            </div>
            <div class="sidebar-card-body">
                <?php $max = max(array_column($hilosPorSeccion, 'total')) ?: 1; ?>
                <?php foreach ($hilosPorSeccion as $sec): ?>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <i class="bi <?= htmlspecialchars($sec['icono']) ?> text-neon-cyan"
                           style="font-size:1.1rem;width:20px;"></i>
                        <span class="flex-grow-1" style="font-size:0.85rem;"><?= htmlspecialchars($sec['nombre']) ?></span>
                        <div style="flex:0 0 180px;">
                            <div style="background:var(--gb-surface-2);border-radius:2px;height:6px;overflow:hidden;">
                                <div style="width:<?= round($sec['total']/$max*100) ?>%;height:100%;background:var(--gb-cyan);"></div>
                            </div>
                        </div>
                        <span class="text-neon-cyan" style="font-size:0.85rem;min-width:30px;text-align:right;">
                            <?= $sec['total'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php elseif ($tab === 'usuarios'): ?>
        <!-- ── USUARIOS ── -->
        <div class="sidebar-card">
            <div class="sidebar-card-header">
                <i class="bi bi-people-fill me-2 text-neon-cyan"></i>Gestión de Usuarios
                <span class="ms-auto text-muted-gb"><?= count($usuarios) ?> registrados</span>
            </div>
            <div class="table-responsive admin-table-wrap">
                <table class="admin-table">
                    <thead><tr>
                        <th>Usuario</th><th>Email</th><th>Rol</th>
                        <th>Hilos</th><th>Registro</th><th>Acciones</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-placeholder flex-shrink-0"
                                         style="width:28px;height:28px;font-size:.6rem;">
                                        <?= strtoupper(substr($u['username'],0,2)) ?>
                                    </div>
                                    <a href="../profile.php?user=<?= $u['id'] ?>"
                                       class="text-neon-cyan text-decoration-none">
                                        <?= htmlspecialchars($u['username']) ?>
                                    </a>
                                </div>
                            </td>
                            <td class="text-muted-gb"><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= $u['rol']==='admin' ? '<span class="badge-pink">Admin</span>' : '<span class="badge-cyan">Usuario</span>' ?></td>
                            <td class="text-neon-cyan"><?= $u['total_hilos'] ?></td>
                            <td class="text-muted-gb"><?= fechaCorta($u['created_at']) ?></td>
                            <td>
                                <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                                    <div class="d-flex gap-2">
                                        <form method="POST" style="display:inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="toggle_rol" value="<?= $u['id'] ?>">
                                            <button type="submit" class="post-action-btn <?= $u['rol']==='admin' ? 'post-action-btn-danger':'' ?>"
                                                    title="<?= $u['rol']==='admin' ? 'Quitar admin':'Hacer admin' ?>">
                                                <i class="bi <?= $u['rol']==='admin' ? 'bi-shield-slash':'bi-shield-fill' ?>"></i>
                                            </button>
                                        </form>
                                        <?php if ($u['rol'] !== 'admin'): ?>
                                            <form method="POST" style="display:inline;"
                                                  onsubmit="return confirmarEliminar(event,'<?= htmlspecialchars($u['username'],ENT_QUOTES) ?>')">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="eliminar_usuario" value="<?= $u['id'] ?>">
                                                <button type="submit" class="post-action-btn post-action-btn-danger" title="Eliminar">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted-gb" style="font-size:0.72rem;">Tú</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($tab === 'hilos'): ?>
        <!-- ── HILOS ── -->
        <div class="sidebar-card">
            <div class="sidebar-card-header">
                <i class="bi bi-chat-square-text-fill me-2 text-neon-pink"></i>Gestión de Hilos
                <span class="ms-auto text-muted-gb"><?= count($hilos) ?> hilos</span>
            </div>
            <div class="table-responsive admin-table-wrap">
                <table class="admin-table">
                    <thead><tr>
                        <th>Título</th><th>Autor</th><th>Sección</th><th>Publicado</th><th>Acciones</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($hilos as $h): ?>
                        <tr>
                            <td>
                                <a href="../thread.php?id=<?= $h['id'] ?>"
                                   class="text-neon-cyan text-decoration-none admin-thread-title">
                                    <?= htmlspecialchars($h['titulo']) ?>
                                </a>
                            </td>
                            <td class="text-muted-gb">
                                <a href="../profile.php?user=<?= $h['autor_id'] ?>"
                                   class="text-muted-gb text-decoration-none">
                                    <?= htmlspecialchars($h['autor']) ?>
                                </a>
                            </td>
                            <td class="text-muted-gb"><?= htmlspecialchars($h['seccion']) ?></td>
                            <td class="text-muted-gb"><?= fechaCorta($h['created_at']) ?></td>
                            <td>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirmarEliminar(event,'<?= htmlspecialchars($h['titulo'],ENT_QUOTES) ?>')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="eliminar_hilo" value="<?= $h['id'] ?>">
                                    <button type="submit" class="post-action-btn post-action-btn-danger" title="Eliminar">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
</main>
<?php include '../includes/footer.php'; ?>
