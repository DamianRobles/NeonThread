<?php
/**
 * forum.php — Vista general del foro
 */
require_once 'includes/db.php';

$activeSection = 'forum'; $basePath = './';
include 'includes/header.php';
$isLoggedIn = isset($_SESSION['usuario_id']);

$secciones = $pdo->query("
    SELECT s.*,
           COUNT(DISTINCT h.id) AS total_hilos,
           COUNT(DISTINCT r.id) AS total_replies
    FROM secciones s
    LEFT JOIN hilos h      ON h.seccion_id = s.id
    LEFT JOIN respuestas r ON r.hilo_id = h.id
    GROUP BY s.id ORDER BY s.id
")->fetchAll();

$stmtUltimo = $pdo->prepare("
    SELECT h.titulo, u.username, h.created_at
    FROM hilos h JOIN usuarios u ON u.id = h.usuario_id
    WHERE h.seccion_id = ? ORDER BY h.created_at DESC LIMIT 1
");
foreach ($secciones as &$sec) {
    $stmtUltimo->execute([$sec['id']]);
    $sec['ultimo_hilo'] = $stmtUltimo->fetch();
    $sec['color']       = (($sec['id'] - 1) % 2 === 0) ? 'cyan' : 'pink';
}
unset($sec);

$hilosDestacados = $pdo->query("
    SELECT h.id, h.titulo, u.username AS autor,
           s.nombre AS seccion, s.id AS seccion_id, s.icono AS seccion_icono,
           COUNT(DISTINCT r.id) AS respuestas, COUNT(DISTINCT l.id) AS likes
    FROM hilos h
    JOIN usuarios u   ON u.id = h.usuario_id
    JOIN secciones s  ON s.id = h.seccion_id
    LEFT JOIN respuestas r ON r.hilo_id = h.id
    LEFT JOIN likes l      ON l.hilo_id = h.id
    GROUP BY h.id ORDER BY likes DESC, respuestas DESC LIMIT 3
")->fetchAll();

$totalHilos    = array_sum(array_column($secciones, 'total_hilos'));
$totalReplies  = array_sum(array_column($secciones, 'total_replies'));
$totalUsuarios = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
?>

<section class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="page-header-title">
                    <i class="bi bi-grid-3x3-gap-fill me-2 text-neon-cyan"></i>Foro
                </h1>
                <p class="page-header-sub">
                    <?= $totalUsuarios ?> usuarios · <?= $totalHilos ?> hilos · <?= $totalReplies ?> respuestas
                </p>
            </div>
            <?php if ($isLoggedIn): ?>
                <a href="<?= $basePath ?>new-thread.php" class="btn btn-neon">
                    <i class="bi bi-plus-circle-fill me-2"></i>Nuevo Hilo
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<main class="py-4">
<div class="container">
<div class="row g-4">

    <!-- Lista de secciones -->
    <div class="col-12 col-lg-8">
        <div class="d-flex flex-column gap-3">
            <?php foreach ($secciones as $i => $sec): ?>
                <a href="<?= $basePath ?>section.php?id=<?= $sec['id'] ?>"
                   class="forum-section-row card-glitch text-decoration-none p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="forum-sec-icon flex-shrink-0
                            <?= $sec['color'] === 'cyan' ? 'section-icon-cyan' : 'section-icon-pink' ?>">
                            <i class="bi <?= htmlspecialchars($sec['icono']) ?>"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <span class="forum-sec-name <?= $sec['color'] === 'cyan' ? 'text-neon-cyan' : 'text-neon-pink' ?>
                                         text-decoration-none">
                                <?= htmlspecialchars($sec['nombre']) ?>
                            </span>
                            <p class="forum-sec-desc mb-0 mt-1"><?= htmlspecialchars($sec['descripcion']) ?></p>
                        </div>
                        <div class="forum-sec-stats text-end flex-shrink-0 d-none d-sm-block">
                            <div class="stat-pill"><i class="bi bi-chat-square-dots me-1"></i><?= $sec['total_hilos'] ?> hilos</div>
                            <div class="stat-pill mt-1"><i class="bi bi-reply-all me-1"></i><?= $sec['total_replies'] ?> resp.</div>
                        </div>
                    </div>
                    <?php if ($sec['ultimo_hilo']): ?>
                        <div class="forum-last-thread mt-2 pt-2" style="border-top:1px solid var(--gb-border);">
                            <span class="text-muted-gb" style="font-size:0.7rem;">Último:</span>
                            <span class="forum-last-title text-decoration-none ms-1">
                                <?= htmlspecialchars(mb_strimwidth($sec['ultimo_hilo']['titulo'], 0, 60, '…')) ?>
                            </span>
                            <span class="text-muted-gb ms-2" style="font-size:0.7rem;">
                                por <?= htmlspecialchars($sec['ultimo_hilo']['username']) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-12 col-lg-4">
        <div class="sidebar-card mb-3">
            <div class="sidebar-card-header">
                <i class="bi bi-fire me-2 text-neon-pink"></i>Hilos Destacados
            </div>
            <div class="sidebar-card-body">
                <?php if (empty($hilosDestacados)): ?>
                    <p class="text-muted-gb" style="font-size:0.8rem;">Aún no hay likes.</p>
                <?php else: ?>
                    <?php foreach ($hilosDestacados as $i => $hilo): ?>
                        <?php if ($i > 0): ?><hr style="border-color:var(--gb-border);margin:.75rem 0;"><?php endif; ?>
                        <div class="sidebar-thread">
                            <div class="d-flex align-items-start gap-2">
                                <div class="sidebar-thread-icon flex-shrink-0">
                                    <i class="bi <?= htmlspecialchars($hilo['seccion_icono']) ?> text-neon-cyan"></i>
                                </div>
                                <div class="min-w-0">
                                    <a href="<?= $basePath ?>thread.php?id=<?= $hilo['id'] ?>"
                                       class="sidebar-thread-title text-decoration-none d-block">
                                        <?= htmlspecialchars($hilo['titulo']) ?>
                                    </a>
                                    <div class="sidebar-thread-meta">
                                        <span class="text-muted-gb"><?= htmlspecialchars($hilo['autor']) ?></span>
                                        <span class="text-neon-cyan ms-2">
                                            <i class="bi bi-chat-dots me-1"></i><?= $hilo['respuestas'] ?>
                                        </span>
                                        <span class="text-neon-pink ms-2">
                                            <i class="bi bi-heart-fill me-1"></i><?= $hilo['likes'] ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="sidebar-card">
            <div class="sidebar-card-header">
                <i class="bi bi-shield-check text-neon-cyan me-2"></i>Reglas de la Red
            </div>
            <div class="sidebar-card-body">
                <ul class="forum-rules-list">
                    <li><i class="bi bi-check2 text-neon-cyan me-2"></i>Respeta a los demás usuarios</li>
                    <li><i class="bi bi-check2 text-neon-cyan me-2"></i>Publica en la sección correcta</li>
                    <li><i class="bi bi-check2 text-neon-cyan me-2"></i>No spam ni publicidad no autorizada</li>
                    <li><i class="bi bi-check2 text-neon-cyan me-2"></i>Usa títulos descriptivos en tus hilos</li>
                    <li><i class="bi bi-check2 text-neon-cyan me-2"></i>Spoilers entre etiquetas cuando aplique</li>
                </ul>
            </div>
        </div>
    </div>

</div>
</div>
</main>

<?php include 'includes/footer.php'; ?>
