<?php
/**
 * new-thread.php — Crear un nuevo hilo
 * CORRECCIONES: CSRF; validación longitud máxima en servidor.
 */
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }

$seccionPresel = (int)($_GET['seccion'] ?? 0);
$secciones     = $pdo->query('SELECT id, nombre, icono FROM secciones ORDER BY id')->fetchAll();
$error = ''; $campos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $titulo    = trim($_POST['titulo']      ?? '');
    $contenido = trim($_POST['contenido']   ?? '');
    $seccionId = (int)($_POST['seccion_id'] ?? 0);
    $campos    = ['titulo' => $titulo, 'contenido' => $contenido, 'seccion_id' => $seccionId];

    if (!$titulo || !$contenido || !$seccionId) {
        $error = 'Por favor completa todos los campos.';
    } elseif (strlen($titulo) < 10) {
        $error = 'El título debe tener al menos 10 caracteres.';
    } elseif (strlen($titulo) > 255) {
        $error = 'El título no puede superar los 255 caracteres.';
    } elseif (strlen($contenido) < 20) {
        $error = 'El contenido debe tener al menos 20 caracteres.';
    } elseif (strlen($contenido) > 5000) {
        $error = 'El contenido no puede superar los 5000 caracteres.';
    } else {
        $s = $pdo->prepare('SELECT id FROM secciones WHERE id = ?');
        $s->execute([$seccionId]);
        if (!$s->fetch()) {
            $error = 'La sección seleccionada no es válida.';
        } else {
            $pdo->prepare('INSERT INTO hilos (titulo, contenido, usuario_id, seccion_id) VALUES (?,?,?,?)')
                ->execute([$titulo, $contenido, $_SESSION['usuario_id'], $seccionId]);
            header('Location: thread.php?id=' . $pdo->lastInsertId()); exit;
        }
    }
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
                <li class="text-neon-cyan">Nuevo Hilo</li>
            </ol>
        </nav>
        <h1 class="page-header-title">
            <i class="bi bi-plus-circle-fill me-2 text-neon-cyan"></i>Crear Nuevo Hilo
        </h1>
    </div>
</section>

<main class="py-4">
<div class="container">
<div class="row justify-content-center">
<div class="col-12 col-lg-8">

    <?php if ($error): ?>
        <div class="alert-glitch alert-glitch-error mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card-glitch p-4">
        <form action="new-thread.php" method="POST" id="newThreadForm" novalidate>
            <?= csrfField() ?>

            <div class="mb-4">
                <label class="auth-label">
                    <i class="bi bi-collection-fill me-1 text-neon-cyan"></i>Sección
                </label>
                <div class="row g-2" role="group" aria-label="Seleccionar sección">
                    <?php foreach ($secciones as $sec):
                        $sel = isset($campos['seccion_id'])
                            ? $campos['seccion_id'] == $sec['id']
                            : $seccionPresel === $sec['id'];
                    ?>
                        <div class="col-6 col-sm-3">
                            <input type="radio" class="btn-check" name="seccion_id"
                                   id="sec<?= $sec['id'] ?>" value="<?= $sec['id'] ?>"
                                   <?= $sel ? 'checked' : '' ?>>
                            <label class="btn-section-radio w-100" for="sec<?= $sec['id'] ?>">
                                <i class="bi <?= htmlspecialchars($sec['icono']) ?> mb-1" style="font-size:1.2rem;"></i>
                                <span><?= htmlspecialchars($sec['nombre']) ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-4">
                <label for="titulo" class="auth-label">
                    <i class="bi bi-type-h1 me-1 text-neon-cyan"></i>
                    Título <span class="text-muted-gb">(10–255 caracteres)</span>
                </label>
                <input type="text" id="titulo" name="titulo" class="form-control auth-input"
                       placeholder="¿De qué trata tu hilo?"
                       value="<?= htmlspecialchars($campos['titulo'] ?? '') ?>"
                       minlength="10" maxlength="255" required>
            </div>

            <div class="mb-4">
                <label for="contenido" class="auth-label">
                    <i class="bi bi-text-paragraph me-1 text-neon-cyan"></i>
                    Contenido <span class="text-muted-gb">(20–5000 caracteres)</span>
                </label>
                <textarea id="contenido" name="contenido" class="form-control auth-input"
                          rows="10" placeholder="Desarrolla el tema del hilo..."
                          minlength="20" maxlength="5000"
                          data-counter="charCount" required><?= htmlspecialchars($campos['contenido'] ?? '') ?></textarea>
                <div class="d-flex justify-content-end mt-1">
                    <span id="charCount" class="auth-hint">0 / 5000</span>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="forum.php" class="btn-neon-sm-outline">
                    <i class="bi bi-x-circle me-1"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-neon">
                    <i class="bi bi-send-fill me-2"></i>Publicar Hilo
                </button>
            </div>
        </form>
    </div>

</div>
</div>
</div>
</main>
<?php include 'includes/footer.php'; ?>
