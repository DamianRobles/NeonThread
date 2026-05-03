<?php

/**
 * register.php — Formulario de registro de nuevos usuarios
 *
 * Valida los datos del formulario y, si todo es correcto,
 * inserta el nuevo usuario en la BD con la contraseña hasheada.
 * Redirige al login tras un registro exitoso.
 *
 * Variables para header.php:
 *   $activeSection — resalta el item activo en el navbar
 *   $basePath      — prefijo de rutas relativas (páginas en raíz usan "./")
 */

require_once 'includes/db.php';

// Mensaje de error y array para repoblar el formulario si hay errores
$error  = "";
$campos = [];

// Procesamiento del formulario antes del include de header.php
// para poder usar header() si el registro es exitoso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']         ?? '');
    $email     = trim($_POST['email']            ?? '');
    $password  = trim($_POST['password']         ?? '');
    $password2 = trim($_POST['password_confirm'] ?? '');
    $terminos  = isset($_POST['terminos']);

    // Conservar campos de texto para repoblar el form si hay error (nunca la contraseña)
    $campos = [
        'username' => $username,
        'email'    => $email,
    ];

    if (empty($username) || empty($email) || empty($password) || empty($password2)) {
        $error = "Por favor completa todos los campos.";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = "El nombre de usuario debe tener entre 3 y 50 caracteres.";
    } elseif (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
        $error = "El nombre de usuario solo puede contener letras, números, guiones y guiones bajos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido.";
    } elseif (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($password !== $password2) {
        $error = "Las contraseñas no coinciden.";
    } elseif (!$terminos) {
        $error = "Debes aceptar los términos y condiciones para continuar.";
    } else {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $error = "El nombre de usuario o el correo electrónico ya están en uso.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare('
                INSERT INTO usuarios (username, email, password)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([$username, $email, $hash]);

            header('Location: login.php?registered=1');
            exit;
        }
    }
}

$activeSection = "";
$basePath      = "./";
include 'includes/header.php';
?>

<!-- =============================================
     REGISTER — Formulario de alta de cuenta
     ============================================= -->
<main class="auth-page">
    <div class="container">

        <div class="row justify-content-center align-items-center" style="min-height: 85vh;">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6">

                <div class="auth-card">

                    <div class="auth-card-header text-center mb-4">
                        <div class="auth-icon auth-icon-pink mb-3">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <h1 class="auth-title">Crear Cuenta</h1>
                        <p class="auth-subtitle">Únete a la red underground</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert-glitch alert-glitch-error mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="register.php" method="POST" id="registerForm" novalidate>

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
                                placeholder="NetRunner_77"
                                value="<?= htmlspecialchars($campos['username'] ?? '') ?>"
                                maxlength="50"
                                autocomplete="username"
                                required />
                            <div class="auth-hint">
                                3–50 caracteres. Solo letras, números, _ y -.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="auth-label">
                                <i class="bi bi-envelope-fill me-1 text-neon-cyan"></i>
                                Correo Electrónico
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control auth-input"
                                placeholder="usuario@correo.com"
                                value="<?= htmlspecialchars($campos['email'] ?? '') ?>"
                                maxlength="100"
                                autocomplete="email"
                                required />
                        </div>

                        <div class="mb-3">
                            <label for="password" class="auth-label">
                                <i class="bi bi-lock-fill me-1 text-neon-cyan"></i>
                                Contraseña
                            </label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control auth-input"
                                    placeholder="Mínimo 8 caracteres"
                                    maxlength="255"
                                    autocomplete="new-password"
                                    required />
                                <!-- data-toggle y data-target son leídos por main.js -->
                                <button
                                    class="btn auth-toggle-btn"
                                    type="button"
                                    data-toggle="password"
                                    data-target="password"
                                    aria-label="Mostrar u ocultar contraseña">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>

                            <!-- La barra y etiqueta son actualizadas por main.js -->
                            <div class="password-strength mt-2">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <span class="strength-label" id="strengthLabel">
                                    Ingresa una contraseña
                                </span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirm" class="auth-label">
                                <i class="bi bi-lock-fill me-1 text-neon-cyan"></i>
                                Confirmar Contraseña
                            </label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    id="password_confirm"
                                    name="password_confirm"
                                    class="form-control auth-input"
                                    placeholder="Repite tu contraseña"
                                    maxlength="255"
                                    autocomplete="new-password"
                                    required />
                                <!-- El ícono de check/X es insertado por main.js -->
                                <span class="auth-match-icon" id="matchIcon"></span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check auth-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="terminos"
                                    name="terminos"
                                    required />
                                <label class="form-check-label" for="terminos">
                                    Acepto los
                                    <a href="#" class="auth-link">términos y condiciones</a>
                                    de la red
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-neon-pink w-100 py-2 mb-3">
                            <i class="bi bi-person-check-fill me-2"></i>Unirse a la Red
                        </button>

                    </form>

                    <div class="auth-divider my-3">
                        <span>¿Ya tienes una cuenta?</span>
                    </div>

                    <a href="<?= $basePath ?>login.php" class="btn btn-neon w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                    </a>

                </div>
                <!-- /auth-card -->

                <p class="auth-security-note text-center mt-3">
                    <i class="bi bi-shield-lock-fill me-1 text-neon-cyan"></i>
                    Tu contraseña se almacena encriptada &bull; Nunca la compartimos
                </p>

            </div>
        </div>

    </div>
</main>

<script>
    // Validación del cliente — se mantiene inline porque es específica de este formulario
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const password2 = document.getElementById('password_confirm').value;
        const terminos = document.getElementById('terminos').checked;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const usernameRegex = /^[a-zA-Z0-9_\-]+$/;

        if (!username || !email || !password || !password2) {
            e.preventDefault();
            mostrarAlerta('Por favor completa todos los campos.');
        } else if (username.length < 3 || username.length > 50) {
            e.preventDefault();
            mostrarAlerta('El nombre de usuario debe tener entre 3 y 50 caracteres.');
        } else if (!usernameRegex.test(username)) {
            e.preventDefault();
            mostrarAlerta('El nombre de usuario solo puede contener letras, números, guiones y guiones bajos.');
        } else if (!emailRegex.test(email)) {
            e.preventDefault();
            mostrarAlerta('El formato del correo electrónico no es válido.');
        } else if (password.length < 8) {
            e.preventDefault();
            mostrarAlerta('La contraseña debe tener al menos 8 caracteres.');
        } else if (password !== password2) {
            e.preventDefault();
            mostrarAlerta('Las contraseñas no coinciden.');
        } else if (!terminos) {
            e.preventDefault();
            mostrarAlerta('Debes aceptar los términos y condiciones para continuar.');
        }
    });
</script>

<?php include 'includes/footer.php'; ?>