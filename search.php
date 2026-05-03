<?php

/**
 * search.php — Buscador de hilos
 * URL esperada: search.php?q=cyberpunk
 *
 * Realiza una búsqueda por título usando LIKE con el término
 * recibido por GET. El operador % en ambos lados permite encontrar
 * el término en cualquier posición del título.
 */

require_once 'includes/db.php';

$query      = trim($_GET['q'] ?? '');
$resultados = [];

if (!empty($query)) {
    $stmt = $pdo->prepare("
        SELECT
            h.id,
            h.titulo,
            h.updated_at,
            u.username              AS autor,
            s.nombre                AS seccion,
            s.id                    AS seccion_id,
            s.icono                 AS seccion_icono,
            COUNT(DISTINCT r.id)    AS total_respuestas,
            COUNT(DISTINCT l.id)    AS total_likes
        FROM hilos h
        JOIN usuarios u    ON u.id = h.usuario_id
        JOIN secciones s   ON s.id = h.seccion_id
        LEFT JOIN respuestas r ON r.hilo_id = h.id
        LEFT JOIN likes l      ON l.hilo_id = h.id
        WHERE h.titulo LIKE ?
        GROUP BY h.id
        ORDER BY h.updated_at DESC
        LIMIT 30
    ");
    // Los % se concatenan en PHP, no dentro del prepare(), para evitar confusión con parámetros
    $stmt->execute(['%' . $query . '%']);
    $resultados = $stmt->fetchAll();
}

$activeSection = "";
$basePath      = "./";
include 'includes/header.php';

$isLoggedIn = isset($_SESSION['usuario_id']);

// Tiempo relativo desde un datetime de SQLite
function tiempoRelativo(string $fecha): string
{
    $diff = time() - strtotime($fecha);
    if ($diff < 60)     return 'hace unos segundos';
    if ($diff < 3600)   return 'hace ' . floor($diff / 60)   . ' min';
    if ($diff < 86400)  return 'hace ' . floor($diff / 3600) . ' h';
    if ($diff < 604800) return 'hace ' . floor($diff / 86400) . ' días';
    return date('d M Y', strtotime($fecha));
}
?>

<!-- =============================================
     CABECERA
     ============================================= -->
<section class="page-header">
    <div class="container">
        <h1 class="page-header-title mb-3">
            <i class="bi bi-search me-2 text-neon-cyan"></i>Buscar Hilos
        </h1>

        <form action="search.php" method="GET" role="search">
            <div class="d-flex gap-2" style="max-width: 600px;">
                <input
                    type="search"
                    name="q"
                    id="q"
                    class="form-control auth-input flex-grow-1"
                    placeholder="Escribe para buscar hilos..."
                    value="<?= htmlspecialchars($query) ?>"
                    maxlength="100"
                    autofocus
                    aria-label="Buscar hilos" />
                <button type="submit" class="btn btn-neon px-4">
                    <i class="bi bi-search me-1"></i>Buscar
                </button>
            </div>
        </form>
    </div>
</section>

<!-- =============================================
     RESULTADOS
     ============================================= -->
<main class="py-4">
    <div class="container">

        <?php if (empty($query)): ?>

            <!-- Estado inicial: sin término de búsqueda -->
            <div class="search-empty-state">
                <i class="bi bi-search text-neon-cyan" style="font-size:2.5rem;"></i>
                <h2 class="mt-3 mb-1" style="font-size:1rem; text-transform:uppercase; letter-spacing:2px;">
                    Ingresa un término de búsqueda
                </h2>
                <p class="text-muted-gb" style="font-size:0.82rem;">
                    Busca por título entre todos los hilos del foro.
                </p>

                <!-- Sugerencias de búsqueda como atajos rápidos -->
                <div class="search-suggestions mt-4">
                    <p class="text-muted-gb mb-2" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">
                        Búsquedas populares:
                    </p>
                    <div class="d-flex gap-2 flex-wrap justify-content-center">
                        <?php
                        $sugerencias = ['Cyberpunk 2077', 'Neuromante', 'Shadowrun', 'Deus Ex', 'Netrunner', 'Megacorporaciones'];
                        foreach ($sugerencias as $s):
                        ?>
                            <a href="search.php?q=<?= urlencode($s) ?>" class="search-tag">
                                <?= htmlspecialchars($s) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php elseif (empty($resultados)): ?>

            <!-- Sin resultados para el término buscado -->
            <div class="search-empty-state">
                <i class="bi bi-exclamation-circle text-neon-pink" style="font-size:2.5rem;"></i>
                <h2 class="mt-3 mb-1" style="font-size:1rem; text-transform:uppercase; letter-spacing:2px;">
                    Sin resultados
                </h2>
                <p class="text-muted-gb" style="font-size:0.82rem;">
                    No se encontraron hilos que contengan
                    <span class="text-neon-cyan">"<?= htmlspecialchars($query) ?>"</span>
                </p>
                <div class="mt-3 d-flex gap-3 justify-content-center flex-wrap">
                    <a href="search.php" class="btn btn-neon btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Nueva búsqueda
                    </a>
                    <?php if ($isLoggedIn): ?>
                        <a href="new-thread.php" class="btn btn-neon-pink btn-sm">
                            <i class="bi bi-plus-circle-fill me-1"></i>Crear hilo sobre este tema
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>

            <!-- Resultados encontrados -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <p class="text-muted-gb mb-0" style="font-size:0.82rem;">
                    <span class="text-neon-cyan"><?= count($resultados) ?></span>
                    resultado<?= count($resultados) !== 1 ? 's' : '' ?> para
                    <span class="text-neon-cyan">"<?= htmlspecialchars($query) ?>"</span>
                </p>
                <a href="search.php" class="sort-btn">
                    <i class="bi bi-x me-1"></i>Limpiar
                </a>
            </div>

            <div class="d-flex flex-column gap-2">
                <?php foreach ($resultados as $hilo):
                    // Resaltar el término buscado dentro del título con una etiqueta <mark>
                    $tituloResaltado = preg_replace(
                        '/(' . preg_quote(htmlspecialchars($query), '/') . ')/i',
                        '<mark class="search-highlight">$1</mark>',
                        htmlspecialchars($hilo['titulo'])
                    );
                ?>
                    <a href="<?= $basePath ?>thread.php?id=<?= $hilo['id'] ?>"
                        class="thread-row card-glitch text-decoration-none p-3 d-flex align-items-center gap-3">

                        <!-- Ícono de la sección del hilo -->
                        <div class="thread-icon flex-shrink-0">
                            <i class="bi <?= htmlspecialchars($hilo['seccion_icono']) ?> text-neon-cyan"></i>
                        </div>

                        <!-- Título con término resaltado y metadatos -->
                        <div class="flex-grow-1 min-w-0">
                            <h2 class="thread-row-title mb-1">
                                <?= $tituloResaltado ?>
                            </h2>
                            <div class="thread-row-meta d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge-section">
                                    <?= htmlspecialchars($hilo['seccion']) ?>
                                </span>
                                <span class="text-muted-gb">
                                    <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($hilo['autor']) ?>
                                </span>
                                <span class="text-muted-gb">
                                    <i class="bi bi-clock me-1"></i><?= tiempoRelativo($hilo['updated_at']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="thread-row-stats flex-shrink-0 text-end d-none d-sm-block">
                            <div class="thread-stat-num text-neon-cyan">
                                <?= $hilo['total_respuestas'] ?>
                            </div>
                            <div class="thread-stat-lbl">respuestas</div>
                            <div class="thread-stat-num text-neon-pink mt-1">
                                <?= $hilo['total_likes'] ?>
                            </div>
                            <div class="thread-stat-lbl">likes</div>
                        </div>

                    </a>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php include 'includes/footer.php'; ?>