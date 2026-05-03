<?php

/**
 * new-thread.php — Crear un nuevo hilo
 * URL opcional: new-thread.php?seccion=1 (preselecciona la sección)
 *
 * Requiere sesión activa. Valida los campos, verifica que la sección
 * exista en la BD e inserta el hilo. Redirige al hilo recién creado.
 */

require_once 'includes/db.php';

// Redirigir al login si el usuario no tiene sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Sección preseleccionada si viene desde section.php o forum.php
$seccionPreseleccionada = (int)($_GET['seccion'] ?? 0);

// Cargar las secciones desde la BD para el selector de radio buttons
$secciones = $pdo->query('SELECT id, nombre, icono FROM secciones ORDER BY id')->fetchAll();

// Mensaje de error y campos para repoblar el formulario si hay errores
$error  = "";
$campos = [];

// Procesamiento del formulario antes del include de header.php
// para poder usar header() en la redirección tras crear el hilo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo    = trim($_POST['titulo']      ?? '');
    $contenido = trim($_POST['contenido']   ?? '');
    $seccionId = (int)($_POST['seccion_id'] ?? 0);

    // Conservar valores para repoblar el form si hay error
    $campos = [
        'titulo'     => $titulo,
        'contenido'  => $contenido,
        'seccion_id' => $seccionId,
    ];

    // Validaciones del lado del servidor
    if (empty($titulo) || empty($contenido) || $seccionId === 0) {
        $error = "Por favor completa todos los campos.";
    } elseif (strlen($titulo) < 10) {
        $error = "El título debe tener al menos 10 caracteres.";
    } elseif (strlen($titulo) > 255) {
        $error = "El título no puede superar los 255 caracteres.";
    } elseif (strlen($contenido) < 20) {
        $error = "El contenido debe tener al menos 20 caracteres.";
    } else {
        // Verificar que la sección exista en la BD
        $stmtSec = $pdo->prepare('SELECT id FROM secciones WHERE id = ?');
        $stmtSec->execute([$seccionId]);

        if (!$stmtSec->fetch()) {
            $error = "La sección seleccionada no es válida.";
        } else {
            // Insertar el hilo en la BD
            $stmt = $pdo->prepare('
                INSERT INTO hilos (titulo, contenido, usuario_id, seccion_id)
                VALUES (?, ?, ?, ?)
            ');
            $stmt->execute([$titulo, $contenido, $_SESSION['usuario_id'], $seccionId]);

            // Redirigir al hilo recién creado usando el ID generado por SQLite
            $nuevoId = $pdo->lastInsertId();
            header("Location: thread.php?id={$nuevoId}");
            exit;
        }
    }
}

$activeSection = "forum";
$basePath      = "./";
include 'includes/header.php';
?>

<!-- =============================================
     CABECERA
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
                <li class="text-neon-cyan">Nuevo Hilo</li>
            </ol>
        </nav>

        <h1 class="page-header-title">
            <i class="bi bi-plus-circle-fill me-2 text-neon-cyan"></i>Crear Nuevo Hilo
        </h1>

    </div>
</section>

<!-- =============================================
     FORMULARIO
     ============================================= -->
<main class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">

                <?php if (!empty($error)): ?>
                    <div class="alert-glitch alert-glitch-error mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="card-glitch p-4">
                    <form action="new-thread.php" method="POST" id="newThreadForm" novalidate>

                        <!-- Selector de sección como radio buttons con estilos de card -->
                        <div class="mb-4">
                            <label class="auth-label">
                                <i class="bi bi-collection-fill me-1 text-neon-cyan"></i>
                                Sección
                            </label>
                            <div class="row g-2" role="group" aria-label="Seleccionar sección">
                                <?php foreach ($secciones as $sec):
                                    $seleccionado = ($campos['seccion_id'] ?? $seccionPreseleccionada) === $sec['id'];
                                ?>
                                    <div class="col-6 col-sm-3">
                                        <input
                                            type="radio"
                                            class="btn-check"
                                            name="seccion_id"
                                            id="sec_<?= $sec['id'] ?>"
                                            value="<?= $sec['id'] ?>"
                                            <?= $seleccionado ? 'checked' : '' ?>
                                            required />
                                        <label class="btn-section-radio w-100" for="sec_<?= $sec['id'] ?>">
                                            <i class="bi <?= htmlspecialchars($sec['icono']) ?> d-block mb-1 fs-5"></i>
                                            <?= htmlspecialchars($sec['nombre']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Título con contador de caracteres -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="auth-label mb-0" for="titulo">
                                    <i class="bi bi-fonts me-1 text-neon-cyan"></i>
                                    Título del Hilo
                                </label>
                                <!-- data-counter apunta al span donde main.js escribe el conteo -->
                                <span class="auth-hint" id="tituloCount">0 / 255</span>
                            </div>
                            <input
                                type="text"
                                id="titulo"
                                name="titulo"
                                class="form-control auth-input"
                                placeholder="Escribe un título claro y descriptivo..."
                                value="<?= htmlspecialchars($campos['titulo'] ?? '') ?>"
                                maxlength="255"
                                data-counter="tituloCount"
                                required />
                            <div class="auth-hint mt-1">Mínimo 10 caracteres.</div>
                        </div>

                        <!-- Contenido con contador de caracteres -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="auth-label mb-0" for="contenido">
                                    <i class="bi bi-body-text me-1 text-neon-cyan"></i>
                                    Contenido
                                </label>
                                <span class="auth-hint" id="contenidoCount">0 / 10000</span>
                            </div>
                            <textarea
                                id="contenido"
                                name="contenido"
                                class="form-control auth-input"
                                rows="10"
                                placeholder="Desarrolla tu tema con detalle. Cuanta más información, mejor debate..."
                                maxlength="10000"
                                data-counter="contenidoCount"
                                required><?= htmlspecialchars($campos['contenido'] ?? '') ?></textarea>
                            <div class="auth-hint mt-1">Mínimo 20 caracteres.</div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a href="<?= $basePath ?>forum.php" class="btn-neon-sm-outline">
                                <i class="bi bi-arrow-left me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-neon px-4">
                                <i class="bi bi-send-fill me-2"></i>Publicar Hilo
                            </button>
                        </div>

                    </form>
                </div>

                <!-- Consejos para redactar un buen hilo -->
                <div class="sidebar-card mt-4">
                    <div class="sidebar-card-header">
                        <i class="bi bi-lightbulb-fill me-2 text-neon-pink"></i>Consejos para un buen hilo
                    </div>
                    <div class="sidebar-card-body">
                        <ul class="forum-rules-list">
                            <li><i class="bi bi-check2 text-neon-cyan me-2"></i>Usa un título específico que resuma bien el tema.</li>
                            <li><i class="bi bi-check2 text-neon-cyan me-2"></i>Aporta contexto: explica tu pregunta o postura con detalle.</li>
                            <li><i class="bi bi-check2 text-neon-cyan me-2"></i>Verifica que no exista ya un hilo similar antes de crear uno.</li>
                            <li><i class="bi bi-check2 text-neon-cyan me-2"></i>Elige la sección correcta para llegar al público adecuado.</li>
                            <li><i class="bi bi-check2 text-neon-cyan me-2"></i>Usa etiquetas de spoiler si tu contenido los contiene.</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<script>
    // Validación del cliente antes de enviar el formulario
    document.getElementById('newThreadForm').addEventListener('submit', function(e) {
        const titulo = document.getElementById('titulo').value.trim();
        const contenido = document.getElementById('contenido').value.trim();
        const seccion = document.querySelector('input[name="seccion_id"]:checked');

        if (!seccion) {
            e.preventDefault();
            mostrarAlerta('Por favor selecciona una sección.');
        } else if (!titulo) {
            e.preventDefault();
            mostrarAlerta('Por favor escribe un título para el hilo.');
        } else if (titulo.length < 10) {
            e.preventDefault();
            mostrarAlerta('El título debe tener al menos 10 caracteres.');
        } else if (!contenido) {
            e.preventDefault();
            mostrarAlerta('Por favor escribe el contenido del hilo.');
        } else if (contenido.length < 20) {
            e.preventDefault();
            mostrarAlerta('El contenido debe tener al menos 20 caracteres.');
        }
    });
</script>

<?php include 'includes/footer.php'; ?>