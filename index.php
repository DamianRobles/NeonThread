<?php

/**
 * index.php — Landing page de NeonThread
 *
 * Muestra las secciones del foro con conteo de hilos reales
 * y los 6 hilos más recientes de la BD.
 */

require_once 'includes/db.php';

$activeSection = "";
$basePath      = "./";
include 'includes/header.php';

$isLoggedIn = isset($_SESSION['usuario_id']);

// Colores alternados por posición: impar = cyan, par = pink
$colores = ['cyan', 'pink'];

// Secciones con conteo real de hilos desde la BD
$secciones = $pdo->query("
    SELECT
        s.*,
        COUNT(h.id) AS total_hilos
    FROM secciones s
    LEFT JOIN hilos h ON h.seccion_id = s.id
    GROUP BY s.id
    ORDER BY s.id
")->fetchAll();

// Asignar color alternado a cada sección
foreach ($secciones as $i => &$sec) {
    $sec['color'] = $colores[$i % 2];
}
unset($sec);

// 6 hilos más recientes con datos del autor, sección y contadores
$hilosRecientes = $pdo->query("
    SELECT
        h.id,
        h.titulo,
        h.updated_at,
        u.username              AS autor,
        s.nombre                AS seccion,
        s.id                    AS seccion_id,
        s.icono                 AS seccion_icono,
        COUNT(DISTINCT r.id)    AS respuestas,
        COUNT(DISTINCT l.id)    AS likes
    FROM hilos h
    JOIN usuarios u    ON u.id = h.usuario_id
    JOIN secciones s   ON s.id = h.seccion_id
    LEFT JOIN respuestas r ON r.hilo_id = h.id
    LEFT JOIN likes l      ON l.hilo_id = h.id
    GROUP BY h.id
    ORDER BY h.updated_at DESC
    LIMIT 6
")->fetchAll();

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
     HERO
     ============================================= -->
<section class="hero-section" id="inicio">
    <div class="hero-overlay"></div>

    <div class="container position-relative z-1 text-center py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">

                <!-- Texto decorativo en japonés estilo furigana -->
                <p class="hero-furigana">ネオンスレッド</p>

                <!-- Título principal dividido en dos colores -->
                <h1 class="hero-title">
                    <span class="text-neon-cyan">Neon</span><span class="text-neon-pink">Thread</span>
                </h1>

                <!-- Botones de acción -->
                <div class="d-flex gap-3 justify-content-center flex-wrap mt-4">
                    <a href="<?= $basePath ?>forum.php" class="btn btn-neon px-4 py-2">
                        <i class="bi bi-grid-3x3-gap-fill me-2"></i>Explorar el Foro
                    </a>
                    <?php if (!$isLoggedIn): ?>
                        <a href="<?= $basePath ?>register.php" class="btn btn-neon-pink px-4 py-2">
                            <i class="bi bi-person-plus-fill me-2"></i>Unirse a la Red
                        </a>
                    <?php else: ?>
                        <a href="<?= $basePath ?>new-thread.php" class="btn btn-neon-pink px-4 py-2">
                            <i class="bi bi-plus-circle-fill me-2"></i>Nuevo Hilo
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- =============================================
     SECCIONES DEL FORO
     Cards que enlazan a cada sección con conteo real de hilos.
     ============================================= -->
<section class="py-5" id="secciones">
    <div class="container">

        <div class="section-header text-center mb-5">
            <h2 class="section-title">
                <span class="text-neon-pink">//</span>
                Secciones del Foro
                <span class="text-neon-pink">//</span>
            </h2>
        </div>

        <!-- Grid: 1 columna en móvil, 2 en tablet, 4 en escritorio -->
        <div class="row g-4">
            <?php foreach ($secciones as $seccion): ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <a href="<?= $basePath ?>section.php?id=<?= $seccion['id'] ?>"
                        class="card-glitch section-card text-decoration-none d-flex flex-column h-100 p-4">

                        <div class="section-icon mb-3 <?= $seccion['color'] === 'cyan' ? 'section-icon-cyan' : 'section-icon-pink' ?>">
                            <i class="bi <?= htmlspecialchars($seccion['icono']) ?>"></i>
                        </div>

                        <h5 class="section-card-title <?= $seccion['color'] === 'cyan' ? 'text-neon-cyan' : 'text-neon-pink' ?>">
                            <?= htmlspecialchars($seccion['nombre']) ?>
                        </h5>

                        <p class="section-card-desc flex-grow-1">
                            <?= htmlspecialchars($seccion['descripcion']) ?>
                        </p>

                        <div class="section-card-footer mt-3">
                            <span class="text-muted-gb" style="font-size:0.78rem;">
                                <i class="bi bi-chat-square-dots me-1"></i>
                                <?= $seccion['total_hilos'] ?> hilos
                            </span>
                            <span class="section-arrow <?= $seccion['color'] === 'cyan' ? 'text-neon-cyan' : 'text-neon-pink' ?>">
                                <i class="bi bi-arrow-right-circle-fill"></i>
                            </span>
                        </div>

                    </a>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<!-- =============================================
     HILOS RECIENTES
     Los 6 últimos hilos ordenados por fecha de actualización.
     ============================================= -->
<section class="py-5 recent-section" id="recientes">
    <div class="container">

        <div class="section-header d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h2 class="section-title mb-0">
                <span class="text-neon-cyan">//</span>
                Hilos Recientes
            </h2>
            <a href="<?= $basePath ?>forum.php" class="btn btn-neon btn-sm">
                Ver todos <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if (empty($hilosRecientes)): ?>
            <!-- Estado vacío mientras no hay hilos en la BD -->
            <div class="text-center py-5">
                <p class="text-muted-gb">Aún no hay hilos publicados. ¡Sé el primero!</p>
                <?php if ($isLoggedIn): ?>
                    <a href="<?= $basePath ?>new-thread.php" class="btn btn-neon mt-2">
                        <i class="bi bi-plus-circle-fill me-2"></i>Crear el primer hilo
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Grid de 2 columnas en escritorio, 1 en móvil -->
            <div class="row g-3 align-items-stretch">
                <?php foreach ($hilosRecientes as $hilo): ?>
                    <div class="col-12 col-lg-6">
                        <a href="<?= $basePath ?>thread.php?id=<?= $hilo['id'] ?>"
                            class="thread-card card-glitch text-decoration-none d-flex align-items-center gap-3 p-3 h-100">

                            <!-- Ícono de la sección del hilo -->
                            <div class="thread-icon flex-shrink-0">
                                <i class="bi <?= htmlspecialchars($hilo['seccion_icono']) ?> text-neon-cyan"></i>
                            </div>

                            <!-- Título y metadatos -->
                            <div class="flex-grow-1 min-w-0">
                                <h6 class="thread-title mb-1">
                                    <?= htmlspecialchars($hilo['titulo']) ?>
                                </h6>
                                <div class="thread-meta d-flex align-items-center gap-3 flex-wrap">
                                    <span class="text-muted-gb">
                                        <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($hilo['autor']) ?>
                                    </span>
                                    <span class="badge-section">
                                        <?= htmlspecialchars($hilo['seccion']) ?>
                                    </span>
                                    <span class="text-muted-gb">
                                        <i class="bi bi-clock me-1"></i><?= tiempoRelativo($hilo['updated_at']) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Contadores de respuestas y likes -->
                            <div class="thread-stats flex-shrink-0 text-end">
                                <div class="text-muted-gb" style="font-size:0.75rem;">
                                    <i class="bi bi-chat-dots me-1"></i><?= $hilo['respuestas'] ?>
                                </div>
                                <div class="text-neon-pink" style="font-size:0.75rem;">
                                    <i class="bi bi-heart-fill me-1"></i><?= $hilo['likes'] ?>
                                </div>
                            </div>

                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- =============================================
     CTA — Llamada a la acción
     Botones dinámicos según el estado de sesión.
     ============================================= -->
<section class="cta-section py-5">
    <div class="container">
        <div class="cta-card text-center p-5">
            <h2 class="cta-title mb-3">¿Listo para conectarte a la red?</h2>
            <p class="cta-subtitle mb-4">
                Únete a la comunidad, crea hilos, comenta y da likes.<br />
                El acceso es gratuito. No se requiere implante neural.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= $basePath ?>new-thread.php" class="btn btn-neon px-5 py-2">
                        <i class="bi bi-plus-circle-fill me-2"></i>Crear un Hilo
                    </a>
                    <a href="<?= $basePath ?>forum.php" class="btn btn-neon-pink px-5 py-2">
                        <i class="bi bi-grid-3x3-gap-fill me-2"></i>Ir al Foro
                    </a>
                <?php else: ?>
                    <a href="<?= $basePath ?>register.php" class="btn btn-neon px-5 py-2">
                        <i class="bi bi-person-plus-fill me-2"></i>Crear Cuenta
                    </a>
                    <a href="<?= $basePath ?>login.php" class="btn btn-neon-pink px-5 py-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Ya tengo cuenta
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>