<?php
/**
 * forum.php — Vista general del foro
 *
 * Muestra todas las secciones con sus estadísticas (total de hilos
 * y respuestas, último hilo publicado) y un sidebar con los hilos
 * con más likes y las reglas del foro.
 *
 * Consultas realizadas:
 *   - SELECT sobre secciones con COUNT de hilos y respuestas
 *   - SELECT del último hilo por sección
 *   - SELECT de los 3 hilos con más likes (destacados)
 */

require_once 'includes/db.php';

$activeSection = "forum";
$basePath      = "./";
include 'includes/header.php';

// Evaluar sesión después del include porque header.php es quien llama session_start()
$isLoggedIn = isset($_SESSION['usuario_id']);

// Colores alternados para las secciones: par = cyan, impar = pink
$colores = ['cyan', 'pink'];

// Obtener todas las secciones con conteo de hilos y respuestas
$secciones = $pdo->query("
    SELECT
        s.*,
        COUNT(DISTINCT h.id)  AS total_hilos,
        COUNT(DISTINCT r.id)  AS total_replies
    FROM secciones s
    LEFT JOIN hilos h     ON h.seccion_id = s.id
    LEFT JOIN respuestas r ON r.hilo_id    = h.id
    GROUP BY s.id
    ORDER BY s.id
")->fetchAll();

// Para cada sección, obtener el hilo más reciente en una consulta separada
$stmtUltimo = $pdo->prepare("
    SELECT h.titulo, u.username, h.created_at
    FROM hilos h
    JOIN usuarios u ON u.id = h.usuario_id
    WHERE h.seccion_id = ?
    ORDER BY h.created_at DESC
    LIMIT 1
");

foreach ($secciones as &$sec) {
    $stmtUltimo->execute([$sec['id']]);
    $sec['ultimo_hilo'] = $stmtUltimo->fetch();
    // Asignar color alternado según la posición (índice base 0)
    $sec['color'] = $colores[($sec['id'] - 1) % 2];
}
unset($sec); // Limpiar la referencia del foreach

// Hilos destacados: los 3 con más likes
$hilosDestacados = $pdo->query("
    SELECT
        h.id,
        h.titulo,
        u.username           AS autor,
        s.nombre             AS seccion,
        s.id                 AS seccion_id,
        s.icono              AS seccion_icono,
        COUNT(DISTINCT r.id) AS respuestas,
        COUNT(DISTINCT l.id) AS likes
    FROM hilos h
    JOIN usuarios u   ON u.id = h.usuario_id
    JOIN secciones s  ON s.id = h.seccion_id
    LEFT JOIN respuestas r ON r.hilo_id  = h.id
    LEFT JOIN likes l      ON l.hilo_id  = h.id
    GROUP BY h.id
    ORDER BY likes DESC, respuestas DESC
    LIMIT 3
")->fetchAll();

// Totales globales para el subtítulo de la cabecera
$totalHilos   = array_sum(array_column($secciones, 'total_hilos'));
$totalReplies = array_sum(array_column($secciones, 'total_replies'));
?>

<!-- =============================================
     CABECERA DE PÁGINA
     ============================================= -->
<section class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div>
                <h1 class="page-header-title">
                    <i class="bi bi-grid-3x3-gap-fill me-2 text-neon-cyan"></i>Foro
                </h1>
                <p class="page-header-sub">
                    <?= $totalHilos ?> hilos &bull;
                    <?= $totalReplies ?> respuestas &bull;
                    <?= count($secciones) ?> secciones
                </p>
            </div>

            <!-- Botón que cambia según si el usuario tiene sesión -->
            <?php if ($isLoggedIn): ?>
                <a href="<?= $basePath ?>new-thread.php" class="btn btn-neon">
                    <i class="bi bi-plus-circle-fill me-2"></i>Nuevo Hilo
                </a>
            <?php else: ?>
                <a href="<?= $basePath ?>login.php" class="btn btn-neon">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Inicia sesión para postear
                </a>
            <?php endif; ?>

        </div>
    </div>
</section>

<!-- =============================================
     BARRA DE BÚSQUEDA
     ============================================= -->
<section class="py-3" style="background-color: var(--gb-surface); border-bottom: 1px solid var(--gb-border);">
    <div class="container">
        <form action="<?= $basePath ?>search.php" method="GET" role="search"
            class="d-flex gap-2 align-items-center">
            <div class="flex-grow-1" style="max-width: 500px;">
                <input
                    type="search"
                    name="q"
                    class="form-control auth-input"
                    placeholder="Buscar en el foro..."
                    value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" />
            </div>
            <button type="submit" class="btn btn-neon">
                <i class="bi bi-search me-1"></i>Buscar
            </button>
        </form>
    </div>
</section>

<!-- =============================================
     CONTENIDO PRINCIPAL
     ============================================= -->
<main class="py-4">
    <div class="container">
        <div class="row g-4">

            <!-- Columna principal: secciones -->
            <div class="col-12 col-lg-8">

                <h2 class="forum-section-heading mb-3">
                    <span class="text-neon-pink">//</span> Secciones
                </h2>

                <div class="d-flex flex-column gap-3">
                    <?php foreach ($secciones as $sec): ?>
                        <div class="forum-section-row card-glitch p-0">
                            <div class="d-flex align-items-center gap-0">

                                <!-- Bloque de ícono lateral con color alternado -->
                                <div class="forum-sec-icon-wrap <?= $sec['color'] === 'cyan' ? 'icon-wrap-cyan' : 'icon-wrap-pink' ?>">
                                    <i class="bi <?= htmlspecialchars($sec['icono']) ?>"></i>
                                </div>

                                <!-- Información central -->
                                <div class="flex-grow-1 p-3 min-w-0">
                                    <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">

                                        <div>
                                            <a href="<?= $basePath ?>section.php?id=<?= $sec['id'] ?>"
                                                class="forum-sec-title <?= $sec['color'] === 'cyan' ? 'text-neon-cyan' : 'text-neon-pink' ?> text-decoration-none">
                                                <?= htmlspecialchars($sec['nombre']) ?>
                                            </a>
                                            <p class="forum-sec-desc mb-0 mt-1">
                                                <?= htmlspecialchars($sec['descripcion']) ?>
                                            </p>
                                        </div>

                                        <!-- Stats: solo visibles desde tablet en adelante -->
                                        <div class="forum-sec-stats text-end flex-shrink-0 d-none d-sm-block">
                                            <div class="stat-pill">
                                                <i class="bi bi-chat-square-dots me-1"></i>
                                                <?= $sec['total_hilos'] ?> hilos
                                            </div>
                                            <div class="stat-pill mt-1">
                                                <i class="bi bi-reply-all me-1"></i>
                                                <?= $sec['total_replies'] ?> respuestas
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Último hilo publicado en la sección -->
                                    <div class="forum-last-thread mt-2 pt-2"
                                        style="border-top: 1px solid var(--gb-border);">
                                        <?php if ($sec['ultimo_hilo']): ?>
                                            <span class="text-muted-gb" style="font-size:0.7rem;">Último:</span>
                                            <a href="<?= $basePath ?>section.php?id=<?= $sec['id'] ?>"
                                                class="forum-last-title text-decoration-none ms-1">
                                                <?= htmlspecialchars($sec['ultimo_hilo']['titulo']) ?>
                                            </a>
                                            <span class="text-muted-gb ms-2" style="font-size:0.7rem;">
                                                — <?= htmlspecialchars($sec['ultimo_hilo']['username']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted-gb" style="font-size:0.7rem;">
                                                <i class="bi bi-dash me-1"></i>Sin hilos aún
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                </div>

                                <!-- Flecha de navegación -->
                                <a href="<?= $basePath ?>section.php?id=<?= $sec['id'] ?>"
                                    class="forum-sec-arrow <?= $sec['color'] === 'cyan' ? 'text-neon-cyan' : 'text-neon-pink' ?> text-decoration-none px-3">
                                    <i class="bi bi-chevron-right"></i>
                                </a>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
            <!-- /Columna principal -->

            <!-- Columna lateral -->
            <div class="col-12 col-lg-4">

                <!-- Hilos destacados -->
                <div class="sidebar-card mb-4">
                    <div class="sidebar-card-header">
                        <i class="bi bi-fire text-neon-pink me-2"></i>Hilos Destacados
                    </div>
                    <div class="sidebar-card-body">
                        <?php if (empty($hilosDestacados)): ?>
                            <p class="text-muted-gb mb-0" style="font-size:0.78rem;">
                                Aún no hay hilos publicados.
                            </p>
                        <?php else: ?>
                            <?php foreach ($hilosDestacados as $i => $hilo): ?>
                                <?php if ($i > 0): ?>
                                    <hr style="border-color: var(--gb-border); margin: 0.75rem 0;" />
                                <?php endif; ?>
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
                                                <span class="text-muted-gb">
                                                    <?= htmlspecialchars($hilo['autor']) ?>
                                                </span>
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

                <!-- Reglas del foro -->
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
            <!-- /Columna lateral -->

        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>