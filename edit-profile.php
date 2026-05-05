<?php
/**
 * edit-profile.php — Edición del perfil
 * CORRECCIÓN: protección CSRF añadida.
 */
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }

$usuarioId = (int)$_SESSION['usuario_id'];
$error = $success = '';

$stmt = $pdo->prepare('SELECT id, username, email, avatar, password FROM usuarios WHERE id = ?');
$stmt->execute([$usuarioId]);
$usuario = $stmt->fetch();
if (!$usuario) { header('Location: logout.php'); exit; }

$avatarDir = __DIR__ . '/uploads/avatars/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $username    = trim($_POST['username']        ?? '');
    $email       = trim($_POST['email']           ?? '');
    $password    =      $_POST['password']        ?? '';
    $passwordNew =      $_POST['password_new']    ?? '';
    $passwordCon =      $_POST['password_new_confirm'] ?? '';
    $nuevoAvatar = $usuario['avatar'];

    if (!$username || !$email) {
        $error = 'El nombre de usuario y el correo no pueden estar vacíos.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'El nombre de usuario debe tener entre 3 y 50 caracteres.';
    } elseif (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
        $error = 'El nombre de usuario solo puede contener letras, números, _ y -.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del correo no es válido.';
    } else {
        // Procesar avatar
        if (!empty($_FILES['avatar']['name'])) {
            $archivo  = $_FILES['avatar'];
            $maxBytes = 10 * 1024 * 1024;
            $mimesPerm = ['image/jpeg', 'image/png', 'image/gif'];
            if ($archivo['error'] !== UPLOAD_ERR_OK) {
                $error = 'Error al subir el archivo.';
            } elseif ($archivo['size'] > $maxBytes) {
                $error = 'La imagen no puede superar los 10 MB.';
            } else {
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $mimeReal = $finfo->file($archivo['tmp_name']);
                if (!in_array($mimeReal, $mimesPerm)) {
                    $error = 'Solo se permiten imágenes JPG, PNG o GIF.';
                } else {
                    if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);
                    $ext  = ['image/jpeg' => 'jpg','image/png' => 'png','image/gif' => 'gif'][$mimeReal];
                    $name = 'avatar_' . $usuarioId . '_' . time() . '.' . $ext;
                    $dest = $avatarDir . $name;
                    if (move_uploaded_file($archivo['tmp_name'], $dest)) {
                        if (!empty($usuario['avatar'])) {
                            $prev = $avatarDir . basename($usuario['avatar']);
                            if (file_exists($prev)) unlink($prev);
                        }
                        $nuevoAvatar = 'uploads/avatars/' . $name;
                    } else {
                        $error = 'No se pudo guardar la imagen.';
                    }
                }
            }
        }

        if (empty($error)) {
            $chk = $pdo->prepare('SELECT id FROM usuarios WHERE (username=? OR email=?) AND id!=?');
            $chk->execute([$username, $email, $usuarioId]);
            if ($chk->fetch()) {
                $error = 'El nombre de usuario o el correo ya están en uso.';
            } else {
                // Cambio de contraseña (opcional)
                if ($passwordNew) {
                    if (!password_verify($password, $usuario['password'])) {
                        $error = 'La contraseña actual no es correcta.';
                    } elseif (strlen($passwordNew) < 8) {
                        $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
                    } elseif ($passwordNew !== $passwordCon) {
                        $error = 'Las nuevas contraseñas no coinciden.';
                    }
                }

                if (empty($error)) {
                    if ($passwordNew) {
                        $pdo->prepare('UPDATE usuarios SET username=?,email=?,avatar=?,password=? WHERE id=?')
                            ->execute([$username, $email, $nuevoAvatar,
                                       password_hash($passwordNew, PASSWORD_BCRYPT), $usuarioId]);
                    } else {
                        $pdo->prepare('UPDATE usuarios SET username=?,email=?,avatar=? WHERE id=?')
                            ->execute([$username, $email, $nuevoAvatar, $usuarioId]);
                    }
                    $_SESSION['username'] = $username;
                    $success = 'Perfil actualizado correctamente.';
                    $usuario['username'] = $username;
                    $usuario['email']    = $email;
                    $usuario['avatar']   = $nuevoAvatar;
                }
            }
        }
    }
}

$activeSection = ''; $basePath = './';
include 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb-glitch">
                <li><a href="profile.php" class="text-muted-gb text-decoration-none">
                    <i class="bi bi-person-circle me-1"></i>Mi Perfil</a></li>
                <li class="text-muted-gb mx-2">/</li>
                <li class="text-neon-cyan">Editar Perfil</li>
            </ol>
        </nav>
        <h1 class="page-header-title">
            <i class="bi bi-pencil-fill me-2 text-neon-cyan"></i>Editar Perfil
        </h1>
    </div>
</section>

<main class="py-4">
<div class="container">
<div class="row justify-content-center">
<div class="col-12 col-lg-7">

    <?php if ($error): ?>
        <div class="alert-glitch alert-glitch-error mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert-glitch alert-glitch-success mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="card-glitch p-4">
        <form action="edit-profile.php" method="POST" id="editProfileForm"
              enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>

            <!-- Avatar -->
            <h2 class="forum-section-heading mb-4">
                <span class="text-neon-cyan">//</span> Foto de Perfil
            </h2>
            <div class="mb-4 d-flex align-items-center gap-4 flex-wrap">
                <div class="avatar-preview-wrap">
                    <?php if (!empty($usuario['avatar'])): ?>
                        <img id="avatarPreview"
                             src="<?= htmlspecialchars($basePath . $usuario['avatar']) ?>"
                             alt="Avatar actual" class="avatar-preview rounded-circle">
                    <?php else: ?>
                        <div class="avatar-preview-placeholder rounded-circle" id="avatarPlaceholder">
                            <?= strtoupper(substr($usuario['username'], 0, 2)) ?>
                        </div>
                        <img id="avatarPreview" src="" alt="Preview"
                             class="avatar-preview rounded-circle d-none">
                    <?php endif; ?>
                </div>
                <div class="flex-grow-1">
                    <label for="avatar" class="auth-label">
                        <i class="bi bi-image-fill me-1 text-neon-cyan"></i>Subir nueva imagen
                    </label>
                    <input type="file" id="avatar" name="avatar"
                           class="form-control auth-input" accept="image/jpeg,image/png,image/gif">
                    <div class="auth-hint mt-1">JPG, PNG o GIF — máximo 10 MB.</div>
                </div>
            </div>

            <!-- Datos de cuenta -->
            <h2 class="forum-section-heading mb-4">
                <span class="text-neon-cyan">//</span> Datos de la Cuenta
            </h2>

            <div class="mb-3">
                <label for="username" class="auth-label">
                    <i class="bi bi-person-fill me-1 text-neon-cyan"></i>Nombre de Usuario
                </label>
                <input type="text" id="username" name="username" class="form-control auth-input"
                       value="<?= htmlspecialchars($usuario['username']) ?>"
                       minlength="3" maxlength="50" required>
            </div>
            <div class="mb-4">
                <label for="email" class="auth-label">
                    <i class="bi bi-envelope-fill me-1 text-neon-cyan"></i>Correo Electrónico
                </label>
                <input type="email" id="email" name="email" class="form-control auth-input"
                       value="<?= htmlspecialchars($usuario['email']) ?>" required>
            </div>

            <!-- Cambio de contraseña -->
            <h2 class="forum-section-heading mb-4">
                <span class="text-neon-cyan">//</span> Cambiar Contraseña <span class="text-muted-gb" style="font-size:.75rem;">(opcional)</span>
            </h2>
            <div class="mb-3">
                <label for="password" class="auth-label">
                    <i class="bi bi-lock-fill me-1 text-neon-cyan"></i>Contraseña Actual
                </label>
                <div class="input-group">
                    <input type="password" id="password" name="password" class="form-control auth-input"
                           placeholder="Requerida solo si vas a cambiar la contraseña"
                           maxlength="255" autocomplete="current-password">
                    <button class="btn auth-toggle-btn" type="button"
                            data-toggle="password" data-target="password" aria-label="Ver">
                        <i class="bi bi-eye-fill"></i></button>
                </div>
            </div>
            <div class="mb-3">
                <label for="password_new" class="auth-label">
                    <i class="bi bi-lock-fill me-1 text-neon-cyan"></i>Nueva Contraseña
                </label>
                <div class="input-group">
                    <input type="password" id="password_new" name="password_new"
                           class="form-control auth-input" placeholder="Mínimo 8 caracteres"
                           maxlength="255" autocomplete="new-password">
                    <button class="btn auth-toggle-btn" type="button"
                            data-toggle="password" data-target="password_new" aria-label="Ver">
                        <i class="bi bi-eye-fill"></i></button>
                </div>
            </div>
            <div class="mb-4">
                <label for="password_new_confirm" class="auth-label">
                    <i class="bi bi-lock-fill me-1 text-neon-cyan"></i>Confirmar Nueva Contraseña
                </label>
                <div class="input-group">
                    <input type="password" id="password_new_confirm" name="password_new_confirm"
                           class="form-control auth-input" placeholder="Repite la nueva contraseña"
                           maxlength="255" autocomplete="new-password">
                    <button class="btn auth-toggle-btn" type="button"
                            data-toggle="password" data-target="password_new_confirm" aria-label="Ver">
                        <i class="bi bi-eye-fill"></i></button>
                </div>
            </div>

            <div class="d-flex justify-content-between gap-2 flex-wrap">
                <a href="profile.php" class="btn-neon-sm-outline">
                    <i class="bi bi-x-circle me-1"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-neon">
                    <i class="bi bi-floppy-fill me-2"></i>Guardar Cambios
                </button>
            </div>

        </form>
    </div>

</div>
</div>
</div>
</main>
<?php include 'includes/footer.php'; ?>
