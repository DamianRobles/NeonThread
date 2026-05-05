<?php
/**
 * thread.php — Hilo individual con respuestas
 * CORRECCIONES: session_start al inicio; CSRF; eliminar respuesta funcional;
 * likes conectados al API; validación longitud máxima en servidor;
 * tiempoRelativo/fechaLegible vienen de db.php.
 */
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$hiloId     = (int)($_GET['id'] ?? 0);
$isLoggedIn = isset($_SESSION['usuario_id']);
$usuarioId  = (int)($_SESSION['usuario_id'] ?? 0);
$esAdmin    = ($_SESSION['rol'] ?? '') === 'admin';

if ($hiloId <= 0) { header('Location: forum.php'); exit; }

$stmt = $pdo->prepare("
    SELECT h.*, u.username AS autor, u.id AS autor_id, u.avatar,
           s.nombre AS seccion, s.id AS seccion_id,
           COUNT(DISTINCT l.id) AS total_likes
    FROM hilos h
    JOIN usuarios u  ON u.id = h.usuario_id
    JOIN secciones s ON s.id = h.seccion_id
    LEFT JOIN likes l ON l.hilo_id = h.id
    WHERE h.id = ? GROUP BY h.id
");
$stmt->execute([$hiloId]);
$hilo = $stmt->fetch();
if (!$hilo) { header('Location: forum.php'); exit; }

$errorRespuesta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();

    // Publicar respuesta
    if (isset($_POST['contenido']) && !isset($_POST['eliminar_respuesta'])) {
        if (!$isLoggedIn) { header('Location: login.php'); exit; }
        $contenido = trim($_POST['contenido'] ?? '');
        if (!$contenido) {
            $errorRespuesta = 'La respuesta no puede estar vacía.';
        } elseif (strlen($contenido) < 10) {
            $errorRespuesta = 'La respuesta debe tener al menos 10 caracteres.';
        } elseif (strlen($contenido) > 5000) {
            $errorRespuesta = 'La respuesta no puede superar los 5000 caracteres.';
        } else {
            $pdo->prepare('INSERT INTO respuestas (contenido, usuario_id, hilo_id) VALUES (?,?,?)')
                ->execute([$contenido, $usuarioId, $hiloId]);
            header("Location: thread.php?id={$hiloId}#respuestas"); exit;
        }
    }

    // Eliminar respuesta
    if (isset($_POST['eliminar_respuesta'])) {
        if (!$isLoggedIn) { header('Location: login.php'); exit; }
        $rid  = (int)$_POST['eliminar_respuesta'];
        $stmR = $pdo->prepare('SELECT usuario_id FROM respuestas WHERE id = ? AND hilo_id = ?');
        $stmR->execute([$rid, $hiloId]);
        $r = $stmR->fetch();
        if ($r && ($r['usuario_id'] === $usuarioId || $esAdmin)) {
            $pdo->prepare('DELETE FROM respuestas WHERE id = ?')->execute([$rid]);
        }
        header("Location: thread.php?id={$hiloId}#respuestas"); exit;
    }
}

// Cargar respuestas con bandera yo_di_like
$stmtResp = $pdo->prepare("
    SELECT r.*, u.username AS autor, u.id AS autor_id, u.avatar,
           COUNT(DISTINCT l.id) AS total_likes,
           MAX(CASE WHEN l.usuario_id = ? THEN 1 ELSE 0 END) AS yo_di_like
    FROM respuestas r
    JOIN usuarios u   ON u.id = r.usuario_id
    LEFT JOIN likes l ON l.respuesta_id = r.id
    WHERE r.hilo_id = ?
    GROUP BY r.id ORDER BY r.created_at ASC
");
$stmtResp->execute([$usuarioId, $hiloId]);
$respuestas = $stmtResp->fetchAll();

// ¿El usuario ya dio like al hilo?
$yoDiLikeAlHilo = false;
if ($isLoggedIn) {
    $sl = $pdo->prepare('SELECT id FROM likes WHERE usuario_id = ? AND hilo_id = ?');
    $sl->execute([$usuarioId, $hiloId]);
    $yoDiLikeAlHilo = (bool)$sl->fetch();
}

$activeSection = 'forum'; $basePath = './';
include 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb-glitch">
                <li><a href="forum.php" class="text-muted-gb text-decoration-none">
                    <i class="bi bi-grid-3x3-gap-fill me-1"></i>Foro</a></li>
                <li class="text-muted-gb mx-2">/</li>
                <li><a href="section.php?id=<?= $hilo['seccion_id'] ?>" class="text-muted-gb text-decoration-none">
                    <?= htmlspecialchars($hilo['seccion']) ?></a></li>
                <li class="text-muted-gb mx-2">/</li>
                <li class="text-neon-cyan thread-breadcrumb-title"><?= htmlspecialchars($hilo['titulo']) ?></li>
            </ol>
        </nav>
    </div>
</section>

<main class="py-4">
<div class="container">
<div class="row g-4">

    <div class="col-12 col-lg-9">

        <!-- POST ORIGINAL -->
        <article class="post-card card-glitch mb-4" id="post-original">
            <div class="post-header d-flex justify-content-between align-items-start">
                <div>
                    <a href="profile.php?user=<?= $hilo['autor_id'] ?>" class="post-author text-decoration-none">
                        <?php if ($hilo['avatar']): ?>
                            <img src="<?= htmlspecialchars($hilo['avatar']) ?>"
                                 class="rounded-circle me-2" style="width:28px;height:28px;object-fit:cover;" alt="">
                        <?php endif; ?>
                        <?= htmlspecialchars($hilo['autor']) ?>
                    </a>
                    <div class="post-meta">
                        <i class="bi bi-clock me-1"></i><?= fechaLegible($hilo['created_at']) ?>
                        <?php if ($hilo['updated_at'] !== $hilo['created_at']): ?>
                            <span class="ms-2 text-muted-gb">(editado <?= tiempoRelativo($hilo['updated_at']) ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($isLoggedIn && ($usuarioId == $hilo['autor_id'] || $esAdmin)): ?>
                    <div class="d-flex gap-2">
                        <a href="edit-thread.php?id=<?= $hiloId ?>" class="post-action-btn" title="Editar hilo">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="post-body" style="border-bottom:1px solid var(--gb-border);padding-bottom:.75rem;">
                <strong style="font-size:1.05rem;"><?= htmlspecialchars($hilo['titulo']) ?></strong>
            </div>

            <div class="post-body">
                <?php foreach (explode("\n", htmlspecialchars($hilo['contenido'])) as $p):
                    if (trim($p)): ?><p><?= $p ?></p><?php endif;
                endforeach; ?>
            </div>

            <div class="post-footer d-flex justify-content-between align-items-center">
                <button class="like-btn <?= $yoDiLikeAlHilo ? 'liked' : '' ?>"
                        data-tipo="hilo" data-id="<?= $hiloId ?>"
                        <?= !$isLoggedIn ? 'disabled title="Inicia sesión para dar like"' : '' ?>>
                    <i class="bi <?= $yoDiLikeAlHilo ? 'bi-heart-fill' : 'bi-heart' ?> me-1"></i>
                    <span class="like-count"><?= $hilo['total_likes'] ?></span>&nbsp;likes
                </button>
                <span class="text-muted-gb" style="font-size:0.72rem;"><?= count($respuestas) ?> respuestas</span>
            </div>
        </article>

        <!-- RESPUESTAS -->
        <div id="respuestas">
            <h2 class="forum-section-heading mb-3">
                <span class="text-neon-pink">//</span> <?= count($respuestas) ?> Respuestas
            </h2>

            <?php if (!empty($respuestas)): ?>
                <?php foreach ($respuestas as $i => $resp): ?>
                    <article class="post-card card-glitch mb-3" id="resp-<?= $resp['id'] ?>">
                        <div class="post-header d-flex justify-content-between align-items-start">
                            <div>
                                <a href="profile.php?user=<?= $resp['autor_id'] ?>"
                                   class="post-author text-decoration-none">
                                    <?php if ($resp['avatar']): ?>
                                        <img src="<?= htmlspecialchars($resp['avatar']) ?>"
                                             class="rounded-circle me-2" style="width:24px;height:24px;object-fit:cover;" alt="">
                                    <?php endif; ?>
                                    <?= htmlspecialchars($resp['autor']) ?>
                                </a>
                                <div class="post-meta">
                                    <i class="bi bi-clock me-1"></i><?= tiempoRelativo($resp['created_at']) ?>
                                    <span class="ms-2 text-muted-gb">#<?= $i + 1 ?></span>
                                </div>
                            </div>
                            <?php if ($isLoggedIn && ($usuarioId == $resp['autor_id'] || $esAdmin)): ?>
                                <form method="POST" action="thread.php?id=<?= $hiloId ?>"
                                      onsubmit="return confirmarEliminar(event, 'respuesta #<?= $i + 1 ?>')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="eliminar_respuesta" value="<?= $resp['id'] ?>">
                                    <button type="submit" class="post-action-btn post-action-btn-danger" title="Eliminar">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <div class="post-body">
                            <p><?= nl2br(htmlspecialchars($resp['contenido'])) ?></p>
                        </div>

                        <div class="post-footer">
                            <button class="like-btn <?= $resp['yo_di_like'] ? 'liked' : '' ?>"
                                    data-tipo="respuesta" data-id="<?= $resp['id'] ?>"
                                    <?= !$isLoggedIn ? 'disabled title="Inicia sesión para dar like"' : '' ?>>
                                <i class="bi <?= $resp['yo_di_like'] ? 'bi-heart-fill' : 'bi-heart' ?> me-1"></i>
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
            <h2 class="forum-section-heading mb-3"><span class="text-neon-cyan">//</span> Tu Respuesta</h2>
            <?php if ($isLoggedIn): ?>
                <?php if ($errorRespuesta): ?>
                    <div class="alert-glitch alert-glitch-error mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorRespuesta) ?>
                    </div>
                <?php endif; ?>
                <form action="thread.php?id=<?= $hiloId ?>#responder" method="POST" class="card-glitch p-3">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label for="contenido" class="auth-label">
                            <i class="bi bi-chat-dots-fill me-1 text-neon-cyan"></i>Escribe tu respuesta
                        </label>
                        <textarea id="contenido" name="contenido" class="form-control auth-input"
                                  rows="5" placeholder="Comparte tu opinión con la comunidad..."
                                  maxlength="5000" data-counter="charCount"
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
                    <p class="mb-3 text-muted-gb">Debes iniciar sesión para responder en este hilo.</p>
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="login.php" class="btn btn-neon">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                        </a>
                        <a href="register.php" class="btn btn-neon-pink">
                            <i class="bi bi-person-plus-fill me-2"></i>Registrarse
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Sidebar -->
    <div class="col-12 col-lg-3">
        <div class="sidebar-card mb-3">
            <div class="sidebar-card-header">
                <i class="bi bi-info-circle me-2 text-neon-cyan"></i>Info del Hilo
            </div>
            <div class="sidebar-card-body">
                <div class="thread-info-row">
                    <span class="text-muted-gb">Sección</span>
                    <a href="section.php?id=<?= $hilo['seccion_id'] ?>" class="text-neon-cyan text-decoration-none">
                        <?= htmlspecialchars($hilo['seccion']) ?>
                    </a>
                </div>
                <div class="thread-info-row">
                    <span class="text-muted-gb">Autor</span>
                    <a href="profile.php?user=<?= $hilo['autor_id'] ?>" class="text-neon-cyan text-decoration-none">
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
                <a href="#respuestas" class="quick-link"><i class="bi bi-chat-dots me-2 text-neon-cyan"></i>Ver respuestas</a>
                <?php if ($isLoggedIn): ?>
                    <a href="#responder" class="quick-link"><i class="bi bi-reply-fill me-2 text-neon-cyan"></i>Responder</a>
                <?php endif; ?>
                <a href="section.php?id=<?= $hilo['seccion_id'] ?>" class="quick-link">
                    <i class="bi bi-arrow-left me-2 text-neon-cyan"></i>Volver a <?= htmlspecialchars($hilo['seccion']) ?>
                </a>
                <a href="forum.php" class="quick-link">
                    <i class="bi bi-grid-3x3-gap-fill me-2 text-neon-cyan"></i>Ir al foro
                </a>
            </div>
        </div>
    </div>

</div>
</div>
</main>

<?php include 'includes/footer.php'; ?>
