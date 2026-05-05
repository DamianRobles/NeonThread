<?php
/**
 * register.php — Registro de nuevos usuarios
 * CORRECCIONES: sin trim() en password/password_confirm; protección CSRF.
 */
require_once 'includes/db.php';

$error = ''; $campos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $username  = trim($_POST['username']         ?? '');
    $email     = trim($_POST['email']            ?? '');
    $password  =      $_POST['password']         ?? '';  // SIN trim()
    $password2 =      $_POST['password_confirm'] ?? '';  // SIN trim()
    $terminos  = isset($_POST['terminos']);
    $campos    = ['username' => $username, 'email' => $email];

    if (!$username || !$email || !$password || !$password2) {
        $error = 'Por favor completa todos los campos.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'El nombre de usuario debe tener entre 3 y 50 caracteres.';
    } elseif (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
        $error = 'El nombre de usuario solo puede contener letras, números, _ y -.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del correo no es válido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (!$terminos) {
        $error = 'Debes aceptar los términos y condiciones.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'El nombre de usuario o correo ya están en uso.';
        } else {
            $pdo->prepare('INSERT INTO usuarios (username, email, password) VALUES (?,?,?)')
                ->execute([$username, $email, password_hash($password, PASSWORD_BCRYPT)]);
            header('Location: login.php?registered=1'); exit;
        }
    }
}

$activeSection = ''; $basePath = './';
include 'includes/header.php';
?>
<main class="auth-page">
<div class="container">
<div class="row justify-content-center align-items-center" style="min-height:80vh;">
<div class="col-12 col-sm-10 col-md-8 col-lg-6">
<div class="auth-card">

    <div class="auth-card-header text-center mb-4">
        <div class="auth-icon auth-icon-pink mb-3"><i class="bi bi-person-plus-fill"></i></div>
        <h1 class="auth-title">Crear Cuenta</h1>
        <p class="auth-subtitle">Únete a la comunidad cyberpunk</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-glitch alert-glitch-error mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST" id="registerForm" novalidate>
        <?= csrfField() ?>

        <div class="mb-3">
            <label for="username" class="auth-label">
                <i class="bi bi-person-fill me-1 text-neon-cyan"></i>Nombre de Usuario
            </label>
            <input type="text" id="username" name="username" class="form-control auth-input"
                   placeholder="tu_usuario_cyberpunk"
                   value="<?= htmlspecialchars($campos['username'] ?? '') ?>"
                   minlength="3" maxlength="50" required autocomplete="username">
            <span class="auth-hint">3–50 caracteres. Solo letras, números, _ y -</span>
        </div>

        <div class="mb-3">
            <label for="email" class="auth-label">
                <i class="bi bi-envelope-fill me-1 text-neon-cyan"></i>Correo Electrónico
            </label>
            <input type="email" id="email" name="email" class="form-control auth-input"
                   placeholder="usuario@correo.com"
                   value="<?= htmlspecialchars($campos['email'] ?? '') ?>"
                   required autocomplete="email">
        </div>

        <div class="mb-3">
            <label for="password" class="auth-label">
                <i class="bi bi-lock-fill me-1 text-neon-cyan"></i>Contraseña
            </label>
            <div class="input-group">
                <input type="password" id="password" name="password" class="form-control auth-input"
                       placeholder="Mínimo 8 caracteres" minlength="8" maxlength="255"
                       required autocomplete="new-password">
                <button class="btn auth-toggle-btn" type="button"
                        data-toggle="password" data-target="password" aria-label="Ver contraseña">
                    <i class="bi bi-eye-fill"></i>
                </button>
            </div>
            <div class="mt-2">
                <div class="strength-bar-bg">
                    <div id="strengthFill" class="strength-bar-fill" style="width:0%"></div>
                </div>
                <span id="strengthLabel" class="auth-hint"></span>
            </div>
        </div>

        <div class="mb-4">
            <label for="password_confirm" class="auth-label">
                <i class="bi bi-lock-fill me-1 text-neon-cyan"></i>Confirmar Contraseña
            </label>
            <div class="input-group">
                <input type="password" id="password_confirm" name="password_confirm"
                       class="form-control auth-input" placeholder="Repite tu contraseña"
                       maxlength="255" required autocomplete="new-password">
                <button class="btn auth-toggle-btn" type="button"
                        data-toggle="password" data-target="password_confirm" aria-label="Ver contraseña">
                    <i class="bi bi-eye-fill"></i>
                </button>
                <span id="matchIcon" class="input-group-text auth-match-icon" style="display:none;"></span>
            </div>
        </div>

        <div class="mb-4">
            <div class="form-check">
                <input class="form-check-input auth-check" type="checkbox" id="terminos" name="terminos" required>
                <label class="form-check-label auth-check-label" for="terminos">
                    Acepto los <a href="#" class="text-neon-cyan">términos y condiciones</a>
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-neon-pink w-100 mb-3">
            <i class="bi bi-person-plus-fill me-2"></i>Crear Cuenta
        </button>
        <p class="text-center auth-hint">
            ¿Ya tienes cuenta?
            <a href="login.php" class="text-neon-cyan text-decoration-none">Inicia sesión</a>
        </p>
    </form>

</div>
</div>
</div>
</div>
</main>
<?php include 'includes/footer.php'; ?>
