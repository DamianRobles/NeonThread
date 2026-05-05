<?php
/**
 * section.php — Lista de hilos de una sección
 * CORRECCIONES: orderBy con whitelist; paginación inteligente; sin tiempoRelativo local.
 */
require_once 'includes/db.php';

$seccionId = (int)($_GET['id'] ?? 0);
if ($seccionId <= 0) { header('Location: forum.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM secciones WHERE id = ?');
$stmt->execute([$seccionId]);
$seccion = $stmt->fetch();
if (!$seccion) { header('Location: forum.php'); exit; }

$seccion['color'] = ($seccionId % 2 === 0) ? 'pink' : 'cyan';

$pagina    = max(1, (int)($_GET['p']    ?? 1));
$orden     = ($_GET['orden'] ?? 'reciente') === 'populares' ? 'populares' : 'reciente';
$porPagina = 10;

// Whitelist — nunca interpolar input del usuario en SQL
$orderMap = [
    'populares' => 'total_likes DESC, total_respuestas DESC',
    'reciente'  => 'h.updated_at DESC',
];
$orderBy = $orderMap[$orden];

$stmtTotal = $pdo->prepare('SELECT COUNT(*) FROM hilos WHERE seccion_id = ?');
$stmtTotal->execute([$seccionId]);
$total     = (int)$stmtTotal->fetchColumn();
$totalPags = (int)ceil($total / $porPagina);
$offset    = ($pagina - 1) * $porPagina;

$stmtHilos = $pdo->prepare("
    SELECT h.id, h.titulo, h.contenido, h.created_at, h.updated_at,
           u.username AS autor, u.id AS autor_id, u.avatar,
           COUNT(DISTINCT r.id) AS total_respuestas,
           COUNT(DISTINCT l.id) AS total_likes
    FROM hilos h
    JOIN usuarios u        ON u.id = h.usuario_id
    LEFT JOIN respuestas r ON r.hilo_id = h.id
    LEFT JOIN likes l      ON l.hilo_id = h.id
    WHERE h.seccion_id = ?
    GROUP BY h.id
    ORDER BY $orderBy
    LIMIT ? OFFSET ?
");
$stmtHilos->execute([$seccionId, $porPagina, $offset]);
$hilos = $stmtHilos->fetchAll();

$activeSection = 'forum'; $basePath = './';
include 'includes/header.php';
$isLoggedIn = isset($_SESSION['usuario_id']);
?>

<section class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb-glitch">
                <li><a href="forum.php" class="text-muted-gb text-decoration-none">
                    <i class="bi bi-grid-3x3-gap-fill me-1"></i>Foro</a></li>
                <li class="text-muted-gb mx-2">/</li>
                <li class="<?= $seccion['color'] === 'cyan' ? 'text-neon-cyan' : 'text-neon-pink' ?>">
                    <?= htmlspecialchars($seccion['nombre']) ?>
                </li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="section-icon <?= $seccion['color'] === 'cyan' ? 'section-icon-cyan' : 'section-icon-pink' ?>">
                    <i class="bi <?= htmlspecialchars($seccion['icono']) ?>"></i>
                </div>
                <div>
                    <h1 class="page-header-title"><?= htmlspecialchars($seccion['nombre']) ?></h1>
                    <p class="page-header-sub"><?= htmlspecialchars($seccion['descripcion']) ?></p>
                </div>
            </div>
            <?php if ($isLoggedIn): ?>
                <a href="new-thread.php?seccion=<?= $seccionId ?>" class="btn btn-neon">
                    <i class="bi bi-plus-circle-fill me-2"></i>Nuevo Hilo
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-neon">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Inicia sesión para postear
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<main class="py-4">
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <span class="text-muted-gb" style="font-size:0.78rem;"><?= $total ?> hilos en esta sección</span>
        <div class="d-flex gap-2">
            <a href="?id=<?= $seccionId ?>&orden=reciente"
               class="sort-btn <?= $orden === 'reciente' ? 'sort-btn-active' : '' ?>">
                <i class="bi bi-clock me-1"></i>Recientes
            </a>
            <a href="?id=<?= $seccionId ?>&orden=populares"
               class="sort-btn <?= $orden === 'populares' ? 'sort-btn-active' : '' ?>">
                <i class="bi bi-fire me-1"></i>Populares
            </a>
        </div>
    </div>

    <?php if (empty($hilos)): ?>
        <div class="empty-state">
            <i class="bi bi-chat-square-dots text-muted-gb" style="font-size:2rem;"></i>
            <p class="mt-2 text-muted-gb">
                Aún no hay hilos en esta sección.
                <?php if ($isLoggedIn): ?>
                    <a href="new-thread.php?seccion=<?= $seccionId ?>" class="text-neon-cyan">¡Sé el primero!</a>
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($hilos as $hilo): ?>
                <a href="thread.php?id=<?= $hilo['id'] ?>"
                   class="thread-row card-glitch text-decoration-none p-3 d-flex align-items-center gap-3">
                    <div class="thread-icon flex-shrink-0">
                        <?php if ($hilo['avatar']): ?>
                            <img src="<?= htmlspecialchars($hilo['avatar']) ?>"
                                 class="rounded-circle" style="width:36px;height:36px;object-fit:cover;" alt="">
                        <?php else: ?>
                            <div class="avatar-placeholder" style="width:36px;height:36px;font-size:.65rem;">
                                <?= strtoupper(substr($hilo['autor'], 0, 2)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <h2 class="thread-row-title mb-1"><?= htmlspecialchars($hilo['titulo']) ?></h2>
                        <p class="thread-row-preview mb-1"><?= htmlspecialchars($hilo['contenido']) ?></p>
                        <div class="thread-row-meta">
                            <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($hilo['autor']) ?>
                            <span class="mx-2 text-muted-gb">·</span>
                            <i class="bi bi-clock me-1"></i><?= tiempoRelativo($hilo['updated_at']) ?>
                        </div>
                    </div>
                    <div class="thread-row-stats flex-shrink-0 text-end d-none d-sm-block">
                        <div class="thread-stat-num text-neon-cyan"><?= $hilo['total_respuestas'] ?></div>
                        <div class="thread-stat-lbl">respuestas</div>
                        <div class="thread-stat-num text-neon-pink mt-1"><?= $hilo['total_likes'] ?></div>
                        <div class="thread-stat-lbl">likes</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Paginación inteligente: 1 … 4 5 [6] 7 8 … 50 -->
    <?php if ($totalPags > 1):
        $paginas = paginasAMostrar($totalPags, $pagina);
    ?>
        <nav class="mt-4" aria-label="Paginación">
            <ul class="pagination-glitch">
                <li>
                    <?php if ($pagina > 1): ?>
                        <a href="?id=<?= $seccionId ?>&p=<?= $pagina-1 ?>&orden=<?= $orden ?>" class="page-btn">
                            <i class="bi bi-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="page-btn page-btn-disabled"><i class="bi bi-chevron-left"></i></span>
                    <?php endif; ?>
                </li>
                <?php foreach ($paginas as $p): ?>
                    <li>
                        <?php if ($p === null): ?>
                            <span class="page-btn page-btn-disabled">…</span>
                        <?php else: ?>
                            <a href="?id=<?= $seccionId ?>&p=<?= $p ?>&orden=<?= $orden ?>"
                               class="page-btn <?= $p === $pagina ? 'page-btn-active' : '' ?>">
                                <?= $p ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                <li>
                    <?php if ($pagina < $totalPags): ?>
                        <a href="?id=<?= $seccionId ?>&p=<?= $pagina+1 ?>&orden=<?= $orden ?>" class="page-btn">
                            <i class="bi bi-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="page-btn page-btn-disabled"><i class="bi bi-chevron-right"></i></span>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

</div>
</main>
<?php include 'includes/footer.php'; ?>
