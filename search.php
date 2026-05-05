<?php
/**
 * search.php — Búsqueda en título Y contenido + paginación
 */
require_once 'includes/db.php';

$query   = trim($_GET['q'] ?? '');
$pagina  = max(1, (int)($_GET['p'] ?? 1));
$porPag  = 10;
$hilos   = [];
$total   = 0; $totalPags = 0;

if ($query !== '') {
    $like = '%' . $query . '%';
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM hilos h WHERE h.titulo LIKE ? OR h.contenido LIKE ?");
    $stmtTotal->execute([$like, $like]);
    $total     = (int)$stmtTotal->fetchColumn();
    $totalPags = (int)ceil($total / $porPag);
    $offset    = ($pagina - 1) * $porPag;

    $stmtH = $pdo->prepare("
        SELECT h.id, h.titulo, h.updated_at,
               u.username AS autor,
               s.nombre AS seccion, s.icono AS seccion_icono,
               COUNT(DISTINCT r.id) AS total_respuestas,
               COUNT(DISTINCT l.id) AS total_likes
        FROM hilos h
        JOIN usuarios u        ON u.id = h.usuario_id
        JOIN secciones s       ON s.id = h.seccion_id
        LEFT JOIN respuestas r ON r.hilo_id = h.id
        LEFT JOIN likes l      ON l.hilo_id = h.id
        WHERE h.titulo LIKE ? OR h.contenido LIKE ?
        GROUP BY h.id
        ORDER BY h.updated_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmtH->execute([$like, $like, $porPag, $offset]);
    $hilos = $stmtH->fetchAll();
}

$activeSection = 'forum'; $basePath = './';
include 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1 class="page-header-title">
            <i class="bi bi-search me-2 text-neon-cyan"></i>Búsqueda
        </h1>
        <?php if ($query): ?>
            <p class="page-header-sub">
                <?= $total ?> resultado<?= $total !== 1 ? 's' : '' ?> para
                "<span class="text-neon-cyan"><?= htmlspecialchars($query) ?></span>"
            </p>
        <?php endif; ?>
    </div>
</section>

<main class="py-4">
<div class="container">

    <?php if ($query === ''): ?>
        <div class="search-empty-state">
            <i class="bi bi-search text-muted-gb" style="font-size:2.5rem;"></i>
            <p class="mt-3 text-muted-gb">Escribe algo en la barra de búsqueda del menú.</p>
            <div class="mt-3 d-flex gap-2 justify-content-center flex-wrap">
                <?php foreach (['Cyberpunk 2077','Neuromante','Shadowrun','Corporaciones','IA'] as $tag): ?>
                    <a href="search.php?q=<?= urlencode($tag) ?>" class="search-tag"><?= htmlspecialchars($tag) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

    <?php elseif (empty($hilos)): ?>
        <div class="search-empty-state">
            <i class="bi bi-slash-circle text-muted-gb" style="font-size:2.5rem;"></i>
            <p class="mt-3 text-muted-gb">
                No se encontraron hilos para "<span class="text-neon-pink"><?= htmlspecialchars($query) ?></span>".
            </p>
        </div>

    <?php else: ?>
        <div class="d-flex flex-column gap-2 mb-4">
            <?php foreach ($hilos as $hilo):
                $tituloH = preg_replace(
                    '/(' . preg_quote(htmlspecialchars($query, ENT_QUOTES), '/') . ')/i',
                    '<mark class="search-highlight">$1</mark>',
                    htmlspecialchars($hilo['titulo'])
                );
            ?>
                <a href="thread.php?id=<?= $hilo['id'] ?>"
                   class="thread-row card-glitch text-decoration-none p-3 d-flex align-items-center gap-3">
                    <div class="thread-icon flex-shrink-0">
                        <i class="bi <?= htmlspecialchars($hilo['seccion_icono']) ?> text-neon-cyan"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <h2 class="thread-row-title mb-1"><?= $tituloH ?></h2>
                        <div class="thread-row-meta d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge-section"><?= htmlspecialchars($hilo['seccion']) ?></span>
                            <span class="text-muted-gb">
                                <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($hilo['autor']) ?>
                            </span>
                            <span class="text-muted-gb">
                                <i class="bi bi-clock me-1"></i><?= tiempoRelativo($hilo['updated_at']) ?>
                            </span>
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

        <?php if ($totalPags > 1):
            $paginas = paginasAMostrar($totalPags, $pagina);
        ?>
            <nav aria-label="Paginación">
                <ul class="pagination-glitch">
                    <li><?php if ($pagina > 1): ?>
                        <a href="?q=<?= urlencode($query) ?>&p=<?= $pagina-1 ?>" class="page-btn">
                            <i class="bi bi-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="page-btn page-btn-disabled"><i class="bi bi-chevron-left"></i></span>
                    <?php endif; ?></li>
                    <?php foreach ($paginas as $p): ?>
                        <li><?php if ($p === null): ?>
                            <span class="page-btn page-btn-disabled">…</span>
                        <?php else: ?>
                            <a href="?q=<?= urlencode($query) ?>&p=<?= $p ?>"
                               class="page-btn <?= $p === $pagina ? 'page-btn-active' : '' ?>"><?= $p ?></a>
                        <?php endif; ?></li>
                    <?php endforeach; ?>
                    <li><?php if ($pagina < $totalPags): ?>
                        <a href="?q=<?= urlencode($query) ?>&p=<?= $pagina+1 ?>" class="page-btn">
                            <i class="bi bi-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="page-btn page-btn-disabled"><i class="bi bi-chevron-right"></i></span>
                    <?php endif; ?></li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>

</div>
</main>
<?php include 'includes/footer.php'; ?>
