<?php
/**
 * profile.php — Perfil público de un usuario
 * CORRECCIONES: paginación de hilos; tiempoRelativo/fechaCorta vienen de db.php.
 */
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$userId = isset($_GET['user'])
    ? (int)$_GET['user']
    : (isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0);

if (!$userId) { header('Location: login.php'); exit; }

$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.avatar, u.rol, u.created_at,
           COUNT(DISTINCT h.id) AS total_hilos,
           COUNT(DISTINCT r.id) AS total_respuestas,
           COUNT(DISTINCT l.id) AS total_likes_recibidos
    FROM usuarios u
    LEFT JOIN hilos h      ON h.usuario_id = u.id
    LEFT JOIN respuestas r ON r.usuario_id = u.id
    LEFT JOIN likes l      ON l.hilo_id IN (SELECT id FROM hilos WHERE usuario_id = u.id)
    WHERE u.id = ? GROUP BY u.id
");
$stmt->execute([$userId]);
$usuario = $stmt->fetch();
if (!$usuario) { header('Location: forum.php'); exit; }

// Paginación
$pagina    = max(1, (int)($_GET['p'] ?? 1));
$porPagina = 10;

$stmtTot = $pdo->prepare('SELECT COUNT(*) FROM hilos WHERE usuario_id = ?');
$stmtTot->execute([$userId]);
$totalHilos = (int)$stmtTot->fetchColumn();
$totalPags  = (int)ceil($totalHilos / $porPagina);
$offset     = ($pagina - 1) * $porPagina;

$stmtH = $pdo->prepare("
    SELECT h.id, h.titulo, h.updated_at,
           s.nombre AS seccion_nombre, s.icono AS seccion_icono,
           COUNT(DISTINCT r.id) AS total_respuestas
    FROM hilos h
    JOIN secciones s       ON s.id = h.seccion_id
    LEFT JOIN respuestas r ON r.hilo_id = h.id
    WHERE h.usuario_id = ?
    GROUP BY h.id ORDER BY h.updated_at DESC
    LIMIT ? OFFSET ?
");
$stmtH->execute([$userId, $porPagina, $offset]);
$hilosUsuario = $stmtH->fetchAll();

$activeSection  = ''; $basePath = './';
include 'includes/header.php';
$esPropio    = isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $userId;
$totalPosts  = $usuario['total_hilos'] + $usuario['total_respuestas'];
$userParam   = isset($_GET['user']) ? 'user=' . $userId . '&' : '';
?>

<section class="page-header">
    <div class="container">
        <div class="d-flex align-items-center gap-4 flex-wrap">
            <div>
                <?php if ($usuario['avatar']): ?>
                    <img src="<?= htmlspecialchars($usuario['avatar']) ?>" class="rounded"
                         style="width:80px;height:80px;object-fit:cover;border:2px solid var(--gb-cyan);" alt="">
                <?php else: ?>
                    <div class="avatar-placeholder" style="width:80px;height:80px;font-size:1.4rem;border-radius:4px;">
                        <?= strtoupper(substr($usuario['username'], 0, 2)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <h1 class="page-header-title mb-1">
                    <?= htmlspecialchars($usuario['username']) ?>
                    <?php if ($usuario['rol'] === 'admin'): ?>
                        <span class="badge-pink ms-2" style="font-size:.65rem;">Admin</span>
                    <?php endif; ?>
                </h1>
                <p class="page-header-sub mb-0">
                    <i class="bi bi-calendar3 me-1"></i>Miembro desde <?= fechaCorta($usuario['created_at']) ?>
                </p>
            </div>
            <?php if ($esPropio): ?>
                <a href="edit-profile.php" class="btn btn-neon btn-sm ms-auto">
                    <i class="bi bi-pencil-fill me-1"></i>Editar Perfil
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<main class="py-4">
<div class="container">
<div class="row g-4">

    <div class="col-12 col-md-4 col-lg-3">
        <div class="sidebar-card">
            <div class="sidebar-card-header">
                <i class="bi bi-bar-chart-fill me-2 text-neon-cyan"></i>Estadísticas
            </div>
            <div class="sidebar-card-body">
                <div class="thread-info-row">
                    <span class="text-muted-gb">Hilos publicados</span>
                    <span class="text-neon-cyan"><?= $usuario['total_hilos'] ?></span>
                </div>
                <div class="thread-info-row">
                    <span class="text-muted-gb">Respuestas</span>
                    <span class="text-neon-cyan"><?= $usuario['total_respuestas'] ?></span>
                </div>
                <div class="thread-info-row">
                    <span class="text-muted-gb">Total posts</span>
                    <span class="text-neon-cyan"><?= $totalPosts ?></span>
                </div>
                <div class="thread-info-row">
                    <span class="text-muted-gb">Likes recibidos</span>
                    <span class="text-neon-pink"><?= $usuario['total_likes_recibidos'] ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-8 col-lg-9">
        <h2 class="forum-section-heading mb-3">
            <span class="text-neon-cyan">//</span>
            Hilos de <?= htmlspecialchars($usuario['username']) ?>
            <span class="text-muted-gb ms-2" style="font-size:.8rem;">(<?= $totalHilos ?>)</span>
        </h2>

        <?php if (empty($hilosUsuario)): ?>
            <div class="empty-state">
                <i class="bi bi-chat-square-dots text-muted-gb" style="font-size:2rem;"></i>
                <p class="mt-2 text-muted-gb">
                    <?= $esPropio ? 'Aún no has publicado ningún hilo.' : 'Este usuario no ha publicado ningún hilo.' ?>
                </p>
                <?php if ($esPropio): ?>
                    <a href="new-thread.php" class="btn btn-neon btn-sm mt-2">
                        <i class="bi bi-plus-circle-fill me-1"></i>Crear primer hilo
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($hilosUsuario as $hilo): ?>
                    <a href="thread.php?id=<?= $hilo['id'] ?>"
                       class="thread-row card-glitch text-decoration-none p-3 d-flex align-items-center gap-3">
                        <div class="thread-icon flex-shrink-0">
                            <i class="bi <?= htmlspecialchars($hilo['seccion_icono']) ?> text-neon-cyan"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <h3 class="thread-row-title mb-1"><?= htmlspecialchars($hilo['titulo']) ?></h3>
                            <div class="thread-row-meta">
                                <span class="badge-section me-2"><?= htmlspecialchars($hilo['seccion_nombre']) ?></span>
                                <i class="bi bi-clock me-1"></i><?= tiempoRelativo($hilo['updated_at']) ?>
                            </div>
                        </div>
                        <div class="thread-row-stats flex-shrink-0 text-end d-none d-sm-block">
                            <div class="thread-stat-num text-neon-cyan"><?= $hilo['total_respuestas'] ?></div>
                            <div class="thread-stat-lbl">respuestas</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPags > 1):
                $paginas = paginasAMostrar($totalPags, $pagina);
            ?>
                <nav class="mt-4" aria-label="Paginación">
                    <ul class="pagination-glitch">
                        <li><?php if ($pagina > 1): ?>
                            <a href="?<?= $userParam ?>p=<?= $pagina-1 ?>" class="page-btn">
                                <i class="bi bi-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="page-btn page-btn-disabled"><i class="bi bi-chevron-left"></i></span>
                        <?php endif; ?></li>
                        <?php foreach ($paginas as $p): ?>
                            <li><?php if ($p === null): ?>
                                <span class="page-btn page-btn-disabled">…</span>
                            <?php else: ?>
                                <a href="?<?= $userParam ?>p=<?= $p ?>"
                                   class="page-btn <?= $p === $pagina ? 'page-btn-active' : '' ?>"><?= $p ?></a>
                            <?php endif; ?></li>
                        <?php endforeach; ?>
                        <li><?php if ($pagina < $totalPags): ?>
                            <a href="?<?= $userParam ?>p=<?= $pagina+1 ?>" class="page-btn">
                                <i class="bi bi-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="page-btn page-btn-disabled"><i class="bi bi-chevron-right"></i></span>
                        <?php endif; ?></li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</div>
</div>
</main>
<?php include 'includes/footer.php'; ?>
