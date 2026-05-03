<?php

/**
 * section.php — Lista de hilos de una sección
 * URL esperada: section.php?id=1
 * Parámetros GET opcionales:
 *   p      — número de página (default: 1)
 *   orden  — reciente | populares (default: reciente)
 */

require_once 'includes/db.php';

// Leer y sanitizar el ID de sección desde la URL
$seccionId = (int)($_GET['id'] ?? 0);

// Redirigir al foro si no se proporcionó un ID válido
if ($seccionId <= 0) {
    header('Location: forum.php');
    exit;
}

// Cargar los datos de la sección
$stmt = $pdo->prepare('SELECT * FROM secciones WHERE id = ?');
$stmt->execute([$seccionId]);
$seccion = $stmt->fetch();

// Redirigir si la sección no existe
if (!$seccion) {
    header('Location: forum.php');
    exit;
}

// Asignar color alternado: secciones pares = pink, impares = cyan
$seccion['color'] = ($seccionId % 2 === 0) ? 'pink' : 'cyan';

// Parámetros de paginación y ordenación
$pagina    = max(1, (int)($_GET['p']     ?? 1));
$orden     = ($_GET['orden'] ?? 'reciente') === 'populares' ? 'populares' : 'reciente';
$porPagina = 10;

// ORDER BY según el criterio seleccionado
$orderBy = ($orden === 'populares')
    ? 'total_likes DESC, total_respuestas DESC'
    : 'h.updated_at DESC';

// Contar total de hilos para calcular páginas
$stmtTotal = $pdo->prepare('SELECT COUNT(*) FROM hilos WHERE seccion_id = ?');
$stmtTotal->execute([$seccionId]);
$total     = (int)$stmtTotal->fetchColumn();
$totalPags = (int)ceil($total / $porPagina);
$offset    = ($pagina - 1) * $porPagina;

// Obtener hilos de la página actual con stats de respuestas y likes
$stmtHilos = $pdo->prepare("
    SELECT
        h.id,
        h.titulo,
        h.contenido,
        h.created_at,
        h.updated_at,
        u.username                AS autor,
        u.id                      AS autor_id,
        u.avatar,
        COUNT(DISTINCT r.id)      AS total_respuestas,
        COUNT(DISTINCT l.id)      AS total_likes
    FROM hilos h
    JOIN usuarios u    ON u.id = h.usuario_id
    LEFT JOIN respuestas r ON r.hilo_id = h.id
    LEFT JOIN likes l      ON l.hilo_id = h.id
    WHERE h.seccion_id = ?
    GROUP BY h.id
    ORDER BY $orderBy
    LIMIT ? OFFSET ?
");
$stmtHilos->execute([$seccionId, $porPagina, $offset]);
$hilos = $stmtHilos->fetchAll();

$activeSection = "forum";
$basePath      = "./";
include 'includes/header.php';

$isLoggedIn = isset($_SESSION['usuario_id']);

// Función auxiliar para mostrar tiempo relativo desde un datetime de SQLite
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
     CABECERA DE SECCIÓN
     ============================================= -->
<section class="page-header">
    <div class="container">

        <!-- Breadcrumb: Foro > Sección -->
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb-glitch">
                <li>
                    <a href="<?= $basePath ?>forum.php" class="text-muted-gb text-decoration-none">
                        <i class="bi bi-grid-3x3-gap-fill me-1"></i>Foro
                    </a>
                </li>
                <li class="text-muted-gb mx-2">/</li>
                <li class="<?= $seccion['color'] === 'cyan' ? 'text-neon-cyan' : 'text-neon-pink' ?>">
                    <?= htmlspecialchars($seccion['nombre']) ?>
                </li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">

                <!-- Ícono de sección -->
                <div class="section-icon <?= $seccion['color'] === 'cyan' ? 'section-icon-cyan' : 'section-icon-pink' ?>">
                    <i class="bi <?= htmlspecialchars($seccion['icono']) ?>"></i>
                </div>

                <div>
                    <h1 class="page-header-title">
                        <?= htmlspecialchars($seccion['nombre']) ?>
                    </h1>
                    <p class="page-header-sub">
                        <?= htmlspecialchars($seccion['descripcion']) ?>
                    </p>
                </div>

            </div>

            <!-- Botón según estado de sesión -->
            <?php if ($isLoggedIn): ?>
                <a href="<?= $basePath ?>new-thread.php?seccion=<?= $seccionId ?>" class="btn btn-neon">
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
     LISTA DE HILOS
     ============================================= -->
<main class="py-4">
    <div class="container">

        <!-- Barra de ordenación -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <span class="text-muted-gb" style="font-size:0.78rem;">
                <?= $total ?> hilos en esta sección
            </span>
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

        <!-- Hilos -->
        <?php if (empty($hilos)): ?>
            <div class="empty-state">
                <i class="bi bi-chat-square-dots text-muted-gb" style="font-size:2rem;"></i>
                <p class="mt-2 text-muted-gb">
                    Aún no hay hilos en esta sección. ¡Sé el primero!
                </p>
                <?php if ($isLoggedIn): ?>
                    <a href="<?= $basePath ?>new-thread.php?seccion=<?= $seccionId ?>"
                        class="btn btn-neon mt-3">
                        <i class="bi bi-plus-circle-fill me-2"></i>Crear el primer hilo
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($hilos as $hilo): ?>
                    <a href="<?= $basePath ?>thread.php?id=<?= $hilo['id'] ?>"
                        class="thread-row card-glitch text-decoration-none p-3 d-flex align-items-center gap-3">

                        <!-- Avatar con iniciales si no tiene imagen -->
                        <div class="thread-avatar flex-shrink-0">
                            <?php if (!empty($hilo['avatar'])): ?>
                                <img src="<?= htmlspecialchars($hilo['avatar']) ?>"
                                    alt="<?= htmlspecialchars($hilo['autor']) ?>"
                                    class="rounded-circle" width="38" height="38"
                                    style="object-fit:cover;" />
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <?= strtoupper(substr($hilo['autor'], 0, 2)) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Título y metadatos -->
                        <div class="flex-grow-1 min-w-0">
                            <h2 class="thread-row-title mb-1">
                                <?= htmlspecialchars($hilo['titulo']) ?>
                            </h2>
                            <!-- Vista previa del contenido truncada a 1 línea -->
                            <p class="thread-row-preview mb-1">
                                <?= htmlspecialchars($hilo['contenido']) ?>
                            </p>
                            <div class="thread-row-meta">
                                <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($hilo['autor']) ?>
                                <span class="mx-2 text-muted-gb">·</span>
                                <i class="bi bi-clock me-1"></i><?= tiempoRelativo($hilo['updated_at']) ?>
                            </div>
                        </div>

                        <!-- Stats (ocultos en móvil) -->
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

        <!-- Paginación: solo se muestra si hay más de una página -->
        <?php if ($totalPags > 1): ?>
            <nav class="mt-4" aria-label="Paginación de hilos">
                <ul class="pagination-glitch">

                    <li>
                        <?php if ($pagina > 1): ?>
                            <a href="?id=<?= $seccionId ?>&p=<?= $pagina - 1 ?>&orden=<?= $orden ?>"
                                class="page-btn">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-btn page-btn-disabled">
                                <i class="bi bi-chevron-left"></i>
                            </span>
                        <?php endif; ?>
                    </li>

                    <?php for ($i = 1; $i <= $totalPags; $i++): ?>
                        <li>
                            <a href="?id=<?= $seccionId ?>&p=<?= $i ?>&orden=<?= $orden ?>"
                                class="page-btn <?= $i === $pagina ? 'page-btn-active' : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li>
                        <?php if ($pagina < $totalPags): ?>
                            <a href="?id=<?= $seccionId ?>&p=<?= $pagina + 1 ?>&orden=<?= $orden ?>"
                                class="page-btn">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-btn page-btn-disabled">
                                <i class="bi bi-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </li>

                </ul>
            </nav>
        <?php endif; ?>

    </div>
</main>

<?php include 'includes/footer.php'; ?>