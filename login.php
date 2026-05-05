<?php
/**
 * login.php — Inicio de sesión
 * CORRECCIONES: sin trim() en password; protección CSRF.
 */
require_once 'includes/db.php';

session_start();
if (isset($_SESSION['usuario_id'])) { header('Location: forum.php'); exit; }

$error = $success = '';
if (isset($_GET['registered'])) $success = 'Cuenta creada. Ya puedes iniciar sesión.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';   // SIN trim()

    if (!$email || !$password) {
        $error = 'Por favor completa todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del correo no es válido.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password, rol FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if (!$u || !password_verify($password, $u['password'])) {
            $error = 'Correo o contraseña incorrectos.';
        } else {
            $_SESSION['usuario_id'] = $u['id'];
            $_SESSION['username']   = $u['username'];
            $_SESSION['rol']        = $u['rol'];
            header('Location: forum.php'); exit;
        }
    }
}

$activeSection = ''; $basePath = './';
include 'includes/header.php';
?>
<main class="auth-page">
<div class="container">
<div class="row justify-content-center align-items-center" style="min-height:80vh;">
<div class="col-12 col-sm-10 col-md-7 col-lg-5">
<div class="auth-card">

    <div class="auth-card-header text-center mb-4">
        <div class="auth-icon mb-3"><i class="bi bi-terminal-fill"></i></div>
        <h1 class="auth-title">Iniciar Sesión</h1>
        <p class="auth-subtitle">Accede a tu cuenta en la red</p>
    </div>

    <?php if ($success): ?>
        <div class="alert-glitch alert-glitch-success mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-glitch alert-glitch-error mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST" id="loginForm" novalidate>
        <?= csrfField() ?>

        <div class="mb-4">
            <label for="email" class="auth-label">
                <i class="bi bi-envelope-fill me-1 text-neon-cyan"></i>Correo Electrónico
            </label>
            <input type="email" id="email" name="email" class="form-control auth-input"
                   placeholder="usuario@correo.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   required autocomplete="email">
        </div>

        <div class="mb-4">
            <label for="password" class="auth-label">
                <i class="bi bi-lock-fill me-1 text-neon-cyan"></i>Contraseña
            </label>
            <div class="input-group">
                <input type="password" id="password" name="password" class="form-control auth-input"
                       placeholder="Tu contraseña" maxlength="255" required autocomplete="current-password">
                <button class="btn auth-toggle-btn" type="button"
                        data-toggle="password" data-target="password" aria-label="Ver contraseña">
                    <i class="bi bi-eye-fill"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-neon w-100 mb-3">
            <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
        </button>
        <p class="text-center auth-hint">
            ¿No tienes cuenta?
            <a href="register.php" class="text-neon-cyan text-decoration-none">Regístrate</a>
        </p>
    </form>

</div>
</div>
</div>
</div>
</main>
<?php include 'includes/footer.php'; ?>
