<?php

/**
 * thread.php — Hilo individual con respuestas
 * URL esperada: thread.php?id=1
 *
 * Muestra el post original, todas las respuestas y el formulario
 * para responder. El INSERT de respuestas se procesa antes del
 * include de header.php para poder redirigir sin errores de headers.
 */

require_once 'includes/db.php';

$hiloId = (int)($_GET['id'] ?? 0);

if ($hiloId <= 0) {
    header('Location: forum.php');
    exit;
}

// Cargar el hilo con datos del autor, sección y total de likes
$stmt = $pdo->prepare("
    SELECT
        h.*,
        u.username           AS autor,
        u.id                 AS autor_id,
        u.avatar,
        s.nombre             AS seccion,
        s.id                 AS seccion_id,
        COUNT(DISTINCT l.id) AS total_likes
    FROM hilos h
    JOIN usuarios u   ON u.id = h.usuario_id
    JOIN secciones s  ON s.id = h.seccion_id
    LEFT JOIN likes l ON l.hilo_id = h.id
    WHERE h.id = ?
    GROUP BY h.id
");
$stmt->execute([$hiloId]);
$hilo = $stmt->fetch();

if (!$hilo) {
    header('Location: forum.php');
    exit;
}

// Procesar nueva respuesta antes de imprimir HTML
$errorRespuesta = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenido'])) {
    session_start();
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }

    $contenido = trim($_POST['contenido'] ?? '');

    if (empty($contenido)) {
        $errorRespuesta = "La respuesta no puede estar vacía.";
    } elseif (strlen($contenido) < 10) {
        $errorRespuesta = "La respuesta debe tener al menos 10 caracteres.";
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO respuestas (contenido, usuario_id, hilo_id)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([$contenido, $_SESSION['usuario_id'], $hiloId]);

        header("Location: thread.php?id={$hiloId}#respuestas");
        exit;
    }
}

// Cargar respuestas con autor y conteo de likes
$stmtResp = $pdo->prepare("
    SELECT
        r.*,
        u.username           AS autor,
        u.id                 AS autor_id,
        u.avatar,
        COUNT(DISTINCT l.id) AS total_likes
    FROM respuestas r
    JOIN usuarios u   ON u.id = r.usuario_id
    LEFT JOIN likes l ON l.respuesta_id = r.id
    WHERE r.hilo_id = ?
    GROUP BY r.id
    ORDER BY r.created_at ASC
");
$stmtResp->execute([$hiloId]);
$respuestas = $stmtResp->fetchAll();

$activeSection = "forum";
$basePath      = "./";
include 'includes/header.php';

$isLoggedIn = isset($_SESSION['usuario_id']);
$usuarioId  = $_SESSION['usuario_id'] ?? null;

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

// Fecha legible: "09 Abr 2026, 10:32"
function fechaLegible(string $fecha): string
{
    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $ts    = strtotime($fecha);
    return date('d', $ts) . ' ' . $meses[date('n', $ts) - 1] . ' ' . date('Y, H:i', $ts);
}
?>

<!-- =============================================
     CABECERA DEL HILO
     ============================================= -->
<section class="page-header">
    <div class="container">

        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb-glitch">
                <li>
                    <a href="<?= $basePath ?>forum.php" class="text-muted-gb text-decoration-none">
                        <i class="bi bi-grid-3x3-gap-fill me-1"></i>Foro
                    </a>
                </li>
                <li class="text-muted-gb mx-2">/</li>
                <li>
                    <a href="<?= $basePath ?>section.php?id=<?= $hilo['seccion_id'] ?>"
                        class="text-neon-cyan text-decoration-none">
                        <?= htmlspecialchars($hilo['seccion']) ?>
                    </a>
                </li>
                <li class="text-muted-gb mx-2">/</li>
                <li class="text-muted-gb thread-breadcrumb-title">
                    <?= htmlspecialchars(mb_strimwidth($hilo['titulo'], 0, 50, '...')) ?>
                </li>
            </ol>
        </nav>

        <h1 class="page-header-title" style="font-size:1.1rem; text-transform:none; letter-spacing:0;">
            <?= htmlspecialchars($hilo['titulo']) ?>
        </h1>

    </div>
</section>

<!-- =============================================
     CONTENIDO
     ============================================= -->
<main class="py-4">
    <div class="container">
        <div class="row g-4">

            <!-- Columna principal -->
            <div class="col-12 col-lg-9">

                <!-- POST ORIGINAL -->
                <article class="post-card card-glitch mb-3" id="post-original">

                    <div class="post-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">

                            <div class="post-avatar">
                                <?php if (!empty($hilo['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($hilo['avatar']) ?>"
                                        alt="<?= htmlspecialchars($hilo['autor']) ?>"
                                        class="rounded-circle" width="42" height="42"
                                        style="object-fit:cover;" />
                                <?php else: ?>
                                    <div class="avatar-placeholder avatar-placeholder-lg">
                                        <?= strtoupper(substr($hilo['autor'], 0, 2)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div>
                                <a href="<?= $basePath ?>profile.php?user=<?= $hilo['autor_id'] ?>"
                                    class="post-author text-decoration-none">
                                    <?= htmlspecialchars($hilo['autor']) ?>
                                </a>
                                <div class="post-meta">
                                    <i class="bi bi-clock me-1"></i><?= fechaLegible($hilo['created_at']) ?>
                                    <span class="badge-cyan ms-2">OP</span>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción solo para el autor o admin -->
                        <?php if ($isLoggedIn && ($usuarioId == $hilo['autor_id'] || $_SESSION['rol'] === 'admin')): ?>
                            <div class="d-flex gap-2">
                                <button class="post-action-btn" title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <button class="post-action-btn post-action-btn-danger" title="Eliminar">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Cuerpo del hilo: cada salto de línea genera un párrafo -->
                    <div class="post-body">
                        <?php foreach (explode("\n", htmlspecialchars($hilo['contenido'])) as $parrafo): ?>
                            <?php if (!empty(trim($parrafo))): ?>
                                <p><?= $parrafo ?></p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="post-footer d-flex justify-content-between align-items-center">
                        <!-- toggleLike() está definido en main.js -->
                        <button class="like-btn" onclick="toggleLike(this, 'original')">
                            <i class="bi bi-heart me-1"></i>
                            <span class="like-count"><?= $hilo['total_likes'] ?></span>&nbsp;likes
                        </button>
                        <span class="text-muted-gb" style="font-size:0.72rem;">
                            <?= count($respuestas) ?> respuestas
                        </span>
                    </div>

                </article>

                <!-- RESPUESTAS -->
                <div id="respuestas">
                    <h2 class="forum-section-heading mb-3">
                        <span class="text-neon-pink">//</span>
                        <?= count($respuestas) ?> Respuestas
                    </h2>

                    <?php if (!empty($respuestas)): ?>
                        <?php foreach ($respuestas as $i => $resp): ?>
                            <article class="post-card card-glitch mb-3" id="respuesta-<?= $resp['id'] ?>">

                                <div class="post-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-3">

                                        <div class="post-avatar">
                                            <?php if (!empty($resp['avatar'])): ?>
                                                <img src="<?= htmlspecialchars($resp['avatar']) ?>"
                                                    alt="<?= htmlspecialchars($resp['autor']) ?>"
                                                    class="rounded-circle" width="42" height="42"
                                                    style="object-fit:cover;" />
                                            <?php else: ?>
                                                <div class="avatar-placeholder avatar-placeholder-lg">
                                                    <?= strtoupper(substr($resp['autor'], 0, 2)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <a href="<?= $basePath ?>profile.php?user=<?= $resp['autor_id'] ?>"
                                                class="post-author text-decoration-none">
                                                <?= htmlspecialchars($resp['autor']) ?>
                                            </a>
                                            <div class="post-meta">
                                                <i class="bi bi-clock me-1"></i><?= fechaLegible($resp['created_at']) ?>
                                                <span class="text-muted-gb ms-2">#<?= $i + 1 ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($isLoggedIn && ($usuarioId == $resp['autor_id'] || $_SESSION['rol'] === 'admin')): ?>
                                        <div class="d-flex gap-2">
                                            <button class="post-action-btn" title="Editar">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button class="post-action-btn post-action-btn-danger" title="Eliminar">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="post-body">
                                    <p><?= htmlspecialchars($resp['contenido']) ?></p>
                                </div>

                                <div class="post-footer">
                                    <button class="like-btn"
                                        onclick="toggleLike(this, 'resp-<?= $resp['id'] ?>')">
                                        <i class="bi bi-heart me-1"></i>
                                        <span class="like-count"><?= $resp['total_likes'] ?></span>&nbsp;likes
                                    </button>
                                </div>

                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-chat-square-dots text-muted-gb" style="font-size:2rem;"></i>
                            <p class="mt-2 text-muted-gb">Aún no hay respuestas. ¡Sé el primero!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- FORMULARIO DE RESPUESTA -->
                <div class="reply-form-wrap mt-4" id="responder">
                    <h2 class="forum-section-heading mb-3">
                        <span class="text-neon-cyan">//</span> Tu Respuesta
                    </h2>

                    <?php if ($isLoggedIn): ?>

                        <?php if (!empty($errorRespuesta)): ?>
                            <div class="alert-glitch alert-glitch-error mb-3">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?= htmlspecialchars($errorRespuesta) ?>
                            </div>
                        <?php endif; ?>

                        <form action="thread.php?id=<?= $hiloId ?>#responder"
                            method="POST" class="card-glitch p-3">
                            <div class="mb-3">
                                <label for="contenido" class="auth-label">
                                    <i class="bi bi-chat-dots-fill me-1 text-neon-cyan"></i>
                                    Escribe tu respuesta
                                </label>
                                <!-- data-counter apunta al id del span donde main.js escribe el conteo -->
                                <textarea
                                    id="contenido"
                                    name="contenido"
                                    class="form-control auth-input"
                                    rows="5"
                                    placeholder="Comparte tu opinión con la comunidad..."
                                    maxlength="5000"
                                    data-counter="charCount"
                                    required><?= htmlspecialchars($_POST['contenido'] ?? '') ?></textarea>
                                <div class="d-flex justify-content-end mt-1">
                                    <span id="charCount" class="auth-hint">0 / 5000</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <a href="#post-original" class="btn-neon-sm-outline">
                                    <i class="bi bi-arrow-up me-1"></i>Subir al inicio
                                </a>
                                <button type="submit" class="btn btn-neon">
                                    <i class="bi bi-send-fill me-2"></i>Publicar Respuesta
                                </button>
                            </div>
                        </form>

                    <?php else: ?>
                        <div class="login-prompt card-glitch p-4 text-center">
                            <i class="bi bi-lock-fill text-neon-pink mb-2" style="font-size:1.5rem;"></i>
                            <p class="mb-3 text-muted-gb">
                                Debes iniciar sesión para responder en este hilo.
                            </p>
                            <div class="d-flex gap-3 justify-content-center">
                                <a href="<?= $basePath ?>login.php" class="btn btn-neon">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                                </a>
                                <a href="<?= $basePath ?>register.php" class="btn btn-neon-pink">
                                    <i class="bi bi-person-plus-fill me-2"></i>Registrarse
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
            <!-- /Columna principal -->

            <!-- Sidebar -->
            <div class="col-12 col-lg-3">

                <div class="sidebar-card mb-3">
                    <div class="sidebar-card-header">
                        <i class="bi bi-info-circle me-2 text-neon-cyan"></i>Info del Hilo
                    </div>
                    <div class="sidebar-card-body">
                        <div class="thread-info-row">
                            <span class="text-muted-gb">Sección</span>
                            <a href="<?= $basePath ?>section.php?id=<?= $hilo['seccion_id'] ?>"
                                class="text-neon-cyan text-decoration-none">
                                <?= htmlspecialchars($hilo['seccion']) ?>
                            </a>
                        </div>
                        <div class="thread-info-row">
                            <span class="text-muted-gb">Autor</span>
                            <a href="<?= $basePath ?>profile.php?user=<?= $hilo['autor_id'] ?>"
                                class="text-neon-cyan text-decoration-none">
                                <?= htmlspecialchars($hilo['autor']) ?>
                            </a>
                        </div>
                        <div class="thread-info-row">
                            <span class="text-muted-gb">Creado</span>
                            <span><?= fechaLegible($hilo['created_at']) ?></span>
                        </div>
                        <div class="thread-info-row">
                            <span class="text-muted-gb">Respuestas</span>
                            <span class="text-neon-cyan"><?= count($respuestas) ?></span>
                        </div>
                        <div class="thread-info-row">
                            <span class="text-muted-gb">Likes</span>
                            <span class="text-neon-pink"><?= $hilo['total_likes'] ?></span>
                        </div>
                    </div>
                </div>

                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <i class="bi bi-lightning-fill me-2 text-neon-pink"></i>Accesos Rápidos
                    </div>
                    <div class="sidebar-card-body d-flex flex-column gap-2">
                        <a href="#respuestas" class="quick-link">
                            <i class="bi bi-chat-dots me-2 text-neon-cyan"></i>Ver respuestas
                        </a>
                        <a href="#responder" class="quick-link">
                            <i class="bi bi-reply-fill me-2 text-neon-cyan"></i>Responder hilo
                        </a>
                        <a href="<?= $basePath ?>section.php?id=<?= $hilo['seccion_id'] ?>" class="quick-link">
                            <i class="bi bi-arrow-left me-2 text-neon-cyan"></i>
                            Volver a <?= htmlspecialchars($hilo['seccion']) ?>
                        </a>
                        <a href="<?= $basePath ?>forum.php" class="quick-link">
                            <i class="bi bi-grid-3x3-gap-fill me-2 text-neon-cyan"></i>Ir al foro
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>