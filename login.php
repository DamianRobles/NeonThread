<?php

/**
 * login.php — Formulario de inicio de sesión
 *
 * Verifica las credenciales contra la BD, inicia la sesión PHP
 * y redirige al foro si son correctas.
 * También muestra un mensaje de bienvenida si viene de register.php.
 *
 * Variables para header.php:
 *   $activeSection — resalta el item activo en el navbar
 *   $basePath      — prefijo de rutas relativas (páginas en raíz usan "./")
 */

require_once 'includes/db.php';

session_start();

// Si el usuario ya tiene sesión activa, redirigir directo al foro
if (isset($_SESSION['usuario_id'])) {
    header('Location: forum.php');
    exit;
}

$error   = "";
$success = "";

// Mensaje de bienvenida al venir de un registro exitoso
if (isset($_GET['registered'])) {
    $success = "Cuenta creada correctamente. Ya puedes iniciar sesión.";
}

// Procesamiento del formulario antes del include de header.php
// para poder usar header() si el login es exitoso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Por favor completa todos los campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido.";
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password, rol FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        // password_verify() compara el texto plano con el hash bcrypt almacenado
        if (!$usuario || !password_verify($password, $usuario['password'])) {
            $error = "Correo electrónico o contraseña incorrectos.";
        } else {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['username']   = $usuario['username'];
            $_SESSION['rol']        = $usuario['rol'];

            header('Location: forum.php');
            exit;
        }
    }
}

$activeSection = "";
$basePath      = "./";
include 'includes/header.php';
?>

<!-- =============================================
     LOGIN — Formulario de acceso a la red
     ============================================= -->
<main class="auth-page">
    <div class="container">

        <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">

                <div class="auth-card">

                    <div class="auth-card-header text-center mb-4">
                        <div class="auth-icon mb-3">
                            <i class="bi bi-terminal-fill"></i>
                        </div>
                        <h1 class="auth-title">Iniciar Sesión</h1>
                        <p class="auth-subtitle">Accede a tu cuenta en la red</p>
                    </div>

                    <?php if (!empty($success)): ?>
                        <div class="alert-glitch alert-glitch-success mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert-glitch alert-glitch-error mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST" id="loginForm" novalidate>

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
                                placeholder="usuario@correo.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                maxlength="100"
                                autocomplete="email"
                                required />
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="auth-label mb-0">
                                    <i class="bi bi-lock-fill me-1 text-neon-cyan"></i>
                                    Contraseña
                                </label>
                                <a href="#" class="auth-link-small">¿Olvidaste tu contraseña?</a>
                            </div>
                            <div class="input-group">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control auth-input"
                                    placeholder="••••••••"
                                    maxlength="255"
                                    autocomplete="current-password"
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
                        </div>

                        <div class="mb-4">
                            <div class="form-check auth-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="remember"
                                    name="remember" />
                                <label class="form-check-label" for="remember">
                                    Mantener sesión iniciada
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-neon w-100 py-2 mb-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Conectarse a la Red
                        </button>

                    </form>

                    <div class="auth-divider my-3">
                        <span>¿Aún no tienes cuenta?</span>
                    </div>

                    <a href="<?= $basePath ?>register.php" class="btn btn-neon-pink w-100 py-2">
                        <i class="bi bi-person-plus-fill me-2"></i>Crear una Cuenta
                    </a>

                </div>
                <!-- /auth-card -->

            </div>
        </div>

    </div>
</main>

<script>
    // Validación del cliente — se mantiene inline porque es específica de este formulario
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!email || !password) {
            e.preventDefault();
            mostrarAlerta('Por favor completa todos los campos.');
        } else if (!emailRegex.test(email)) {
            e.preventDefault();
            mostrarAlerta('El formato del correo electrónico no es válido.');
        }
    });
</script>

<?php include 'includes/footer.php'; ?>