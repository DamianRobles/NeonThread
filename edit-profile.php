<?php

/**
 * edit-profile.php — Edición del perfil del usuario logueado
 *
 * Permite cambiar username, email, contraseña y foto de perfil.
 * El avatar se guarda en uploads/avatars/ con un nombre único.
 * Tipos permitidos: JPG, PNG, GIF — máximo 10 MB.
 * El cambio de contraseña es opcional.
 *
 * Requiere sesión activa — redirige al login si no la hay.
 */

require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = (int)$_SESSION['usuario_id'];
$error     = "";
$success   = "";

// Cargar datos actuales del usuario incluyendo el avatar
$stmt = $pdo->prepare('SELECT id, username, email, avatar FROM usuarios WHERE id = ?');
$stmt->execute([$usuarioId]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: logout.php');
    exit;
}

// Directorio donde se guardan los avatars
$avatarDir = __DIR__ . '/uploads/avatars/';

// Procesar el formulario antes del include de header.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username    = trim($_POST['username']        ?? '');
    $email       = trim($_POST['email']           ?? '');
    $password    = $_POST['password']             ?? '';
    $passwordNew = $_POST['password_new']         ?? '';
    $passwordCon = $_POST['password_new_confirm'] ?? '';
    $nuevoAvatar = $usuario['avatar']; // Conservar el avatar actual por defecto

    // Validaciones de username y email
    if (empty($username) || empty($email)) {
        $error = "El nombre de usuario y el correo no pueden estar vacíos.";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = "El nombre de usuario debe tener entre 3 y 50 caracteres.";
    } elseif (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
        $error = "El nombre de usuario solo puede contener letras, números, guiones y guiones bajos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido.";
    } else {

        // Procesar el avatar si se subió un archivo
        if (!empty($_FILES['avatar']['name'])) {
            $archivo   = $_FILES['avatar'];
            $maxBytes  = 10 * 1024 * 1024; // 10 MB en bytes

            // Tipos MIME permitidos — se valida el contenido real del archivo,
            // no solo la extensión, para evitar archivos renombrados maliciosamente
            $mimesPermitidos = ['image/jpeg', 'image/png', 'image/gif'];

            if ($archivo['error'] !== UPLOAD_ERR_OK) {
                $error = "Error al subir el archivo. Intenta de nuevo.";
            } elseif ($archivo['size'] > $maxBytes) {
                $error = "La imagen no puede superar los 10 MB.";
            } else {
                // finfo lee los primeros bytes del archivo para detectar el tipo real.
                // Solo se llama aquí, después de confirmar que la subida fue exitosa
                // y que tmp_name contiene un archivo válido.
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $mimeReal = $finfo->file($archivo['tmp_name']);

                if (!in_array($mimeReal, $mimesPermitidos)) {
                    $error = "Solo se permiten imágenes JPG, PNG o GIF.";
                } else {
                    // Crear el directorio si no existe
                    if (!is_dir($avatarDir)) {
                        mkdir($avatarDir, 0755, true);
                    }

                    // Determinar la extensión real según el MIME detectado
                    $extensiones = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/gif'  => 'gif',
                    ];
                    $ext = $extensiones[$mimeReal];

                    // Generar nombre único con el ID del usuario para evitar colisiones
                    $nombreArchivo = 'avatar_' . $usuarioId . '_' . time() . '.' . $ext;
                    $rutaDestino   = $avatarDir . $nombreArchivo;

                    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                        // Eliminar el avatar anterior si existía
                        if (!empty($usuario['avatar'])) {
                            $rutaAnterior = $avatarDir . basename($usuario['avatar']);
                            if (file_exists($rutaAnterior)) {
                                unlink($rutaAnterior);
                            }
                        }
                        // Guardar solo la ruta relativa desde la raíz del proyecto
                        $nuevoAvatar = 'uploads/avatars/' . $nombreArchivo;
                    } else {
                        $error = "No se pudo guardar la imagen. Verifica los permisos de la carpeta.";
                    }
                }
            }
        }

        if (empty($error)) {
            // Verificar que username o email no estén en uso por otro usuario
            $stmtCheck = $pdo->prepare('
                SELECT id FROM usuarios
                WHERE (username = ? OR email = ?) AND id != ?
            ');
            $stmtCheck->execute([$username, $email, $usuarioId]);

            if ($stmtCheck->fetch()) {
                $error = "El nombre de usuario o el correo electrónico ya están en uso.";
            } else {
                // Validar contraseña solo si el usuario quiere cambiarla
                $cambiarPassword = !empty($passwordNew);

                if ($cambiarPassword) {
                    $stmtPass = $pdo->prepare('SELECT password FROM usuarios WHERE id = ?');
                    $stmtPass->execute([$usuarioId]);
                    $hash = $stmtPass->fetchColumn();

                    if (!password_verify($password, $hash)) {
                        $error = "La contraseña actual no es correcta.";
                    } elseif (strlen($passwordNew) < 8) {
                        $error = "La nueva contraseña debe tener al menos 8 caracteres.";
                    } elseif ($passwordNew !== $passwordCon) {
                        $error = "Las nuevas contraseñas no coinciden.";
                    }
                }

                if (empty($error)) {
                    if ($cambiarPassword) {
                        $nuevoHash = password_hash($passwordNew, PASSWORD_BCRYPT);
                        $stmtUp = $pdo->prepare('
                            UPDATE usuarios SET username = ?, email = ?, password = ?, avatar = ?
                            WHERE id = ?
                        ');
                        $stmtUp->execute([$username, $email, $nuevoHash, $nuevoAvatar, $usuarioId]);
                    } else {
                        $stmtUp = $pdo->prepare('
                            UPDATE usuarios SET username = ?, email = ?, avatar = ?
                            WHERE id = ?
                        ');
                        $stmtUp->execute([$username, $email, $nuevoAvatar, $usuarioId]);
                    }

                    // Actualizar sesión para reflejar cambios en el navbar
                    $_SESSION['username'] = $username;

                    $success = "Perfil actualizado correctamente.";

                    // Refrescar datos locales para repoblar el form
                    $usuario['username'] = $username;
                    $usuario['email']    = $email;
                    $usuario['avatar']   = $nuevoAvatar;
                }
            }
        }
    }
}

$activeSection = "";
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
                    <a href="<?= $basePath ?>profile.php" class="text-muted-gb text-decoration-none">
                        <i class="bi bi-person-circle me-1"></i>Mi Perfil
                    </a>
                </li>
                <li class="text-muted-gb mx-2">/</li>
                <li class="text-neon-cyan">Editar Perfil</li>
            </ol>
        </nav>
        <h1 class="page-header-title">
            <i class="bi bi-pencil-fill me-2 text-neon-cyan"></i>Editar Perfil
        </h1>
    </div>
</section>

<!-- =============================================
     FORMULARIO
     enctype="multipart/form-data" es obligatorio para subir archivos
     ============================================= -->
<main class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-7">

                <?php if (!empty($error)): ?>
                    <div class="alert-glitch alert-glitch-error mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert-glitch alert-glitch-success mb-4" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <div class="card-glitch p-4 mb-4">
                    <form action="edit-profile.php" method="POST"
                        id="editProfileForm"
                        enctype="multipart/form-data"
                        novalidate>

                        <!-- Avatar -->
                        <h2 class="forum-section-heading mb-4">
                            <span class="text-neon-cyan">//</span> Foto de Perfil
                        </h2>

                        <div class="mb-4 d-flex align-items-center gap-4 flex-wrap">

                            <!-- Preview del avatar actual o placeholder -->
                            <div class="avatar-preview-wrap">
                                <?php if (!empty($usuario['avatar'])): ?>
                                    <img
                                        id="avatarPreview"
                                        src="<?= htmlspecialchars($basePath . $usuario['avatar']) ?>"
                                        alt="Avatar actual"
                                        class="avatar-preview rounded-circle" />
                                <?php else: ?>
                                    <div class="avatar-preview-placeholder rounded-circle" id="avatarPlaceholder">
                                        <?= strtoupper(substr($usuario['username'], 0, 2)) ?>
                                    </div>
                                    <img id="avatarPreview"
                                        src="" alt="Preview"
                                        class="avatar-preview rounded-circle d-none" />
                                <?php endif; ?>
                            </div>

                            <div class="flex-grow-1">
                                <label for="avatar" class="auth-label">
                                    <i class="bi bi-image-fill me-1 text-neon-cyan"></i>
                                    Subir nueva imagen
                                </label>
                                <!-- accept filtra en el selector de archivos del sistema operativo -->
                                <input
                                    type="file"
                                    id="avatar"
                                    name="avatar"
                                    class="form-control auth-input"
                                    accept="image/jpeg,image/png,image/gif" />
                                <div class="auth-hint mt-1">
                                    JPG, PNG o GIF — máximo 10 MB.
                                    Dejar en blanco para conservar la imagen actual.
                                </div>
                            </div>

                        </div>

                        <!-- Datos de la cuenta -->
                        <h2 class="forum-section-heading mb-4">
                            <span class="text-neon-cyan">//</span> Datos de la Cuenta
                        </h2>

                        <div class="mb-3">
                            <label for="username" class="auth-label">
                                <i class="bi bi-person-badge-fill me-1 text-neon-cyan"></i>
                                Nombre de Usuario
                            </label>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-control auth-input"
                                value="<?= htmlspecialchars($usuario['username']) ?>"
                                maxlength="50"
                                required />
                            <div class="auth-hint mt-1">3–50 caracteres. Solo letras, números, _ y -.</div>
                        </div>

                        <div class="mb-4">
                            <label for="email" class="auth-label">
                                <i class="bi bi-envelope-fill me-1 text-neon-cyan"></i>
                                Correo Electrónico
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control auth-input"
                                value="<?= htmlspecialchars($usuario['email']) ?>"
                                maxlength="100"
                                required />
                        </div>

                        <!-- Cambio de contraseña -->
                        <h2 class="forum-section-heading mb-3 mt-2">
                            <span class="text-neon-pink">//</span> Cambiar Contraseña
                            <span class="auth-hint ms-2" style="font-size:0.7rem; text-transform:none; letter-spacing:0;">
                                (opcional — dejar en blanco para no cambiarla)
                            </span>
                        </h2>

                        <div class="mb-3">
                            <label for="password" class="auth-label">
                                <i class="bi bi-lock-fill me-1 text-neon-cyan"></i>
                                Contraseña Actual
                            </label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control auth-input"
                                    placeholder="Requerida solo si vas a cambiar la contraseña"
                                    maxlength="255"
                                    autocomplete="current-password" />
                                <button class="btn auth-toggle-btn" type="button"
                                    data-toggle="password" data-target="password"
                                    aria-label="Mostrar u ocultar contraseña">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password_new" class="auth-label">
                                <i class="bi bi-lock-fill me-1 text-neon-cyan"></i>
                                Nueva Contraseña
                            </label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    id="password_new"
                                    name="password_new"
                                    class="form-control auth-input"
                                    placeholder="Mínimo 8 caracteres"
                                    maxlength="255"
                                    autocomplete="new-password" />
                                <button class="btn auth-toggle-btn" type="button"
                                    data-toggle="password" data-target="password_new"
                                    aria-label="Mostrar u ocultar contraseña">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password_new_confirm" class="auth-label">
                                <i class="bi bi-lock-fill me-1 text-neon-cyan"></i>
                                Confirmar Nueva Contraseña
                            </label>
                            <input
                                type="password"
                                id="password_new_confirm"
                                name="password_new_confirm"
                                class="form-control auth-input"
                                placeholder="Repite la nueva contraseña"
                                maxlength="255"
                                autocomplete="new-password" />
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a href="<?= $basePath ?>profile.php" class="btn-neon-sm-outline">
                                <i class="bi bi-arrow-left me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-neon px-4">
                                <i class="bi bi-floppy-fill me-2"></i>Guardar Cambios
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
</main>

<script>
    // Preview en tiempo real del avatar seleccionado antes de guardar.
    // FileReader lee el archivo localmente sin necesidad de subirlo primero.
    document.getElementById('avatar').addEventListener('change', function() {
        const file = this.files[0];
        const preview = document.getElementById('avatarPreview');
        const placeholder = document.getElementById('avatarPlaceholder');

        if (!file) return;

        // Validación del lado del cliente: tipo y tamaño
        const tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
        const maxBytes = 10 * 1024 * 1024;

        if (!tiposPermitidos.includes(file.type)) {
            mostrarAlerta('Solo se permiten imágenes JPG, PNG o GIF.');
            this.value = '';
            return;
        }

        if (file.size > maxBytes) {
            mostrarAlerta('La imagen no puede superar los 10 MB.');
            this.value = '';
            return;
        }

        // Mostrar preview inmediato con FileReader
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            if (placeholder) placeholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });
</script>

<?php include 'includes/footer.php'; ?>