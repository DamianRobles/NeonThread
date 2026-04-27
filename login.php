<?php
$basePath      = "./";
include 'includes/header.php';

// Mensajes de feedback para el usuario
$error   = "";
$success = "";

// Procesamiento del formulario al recibir POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validaciones del lado del servidor
    if (empty($email) || empty($password)) {
        $error = "Por favor completa todos los campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido.";
    } else {
        // TODO: conectar con la BD aquí
        $success = "Credenciales recibidas correctamente.";
    }
}
?>

<!-- =============================================
     LOGIN — Formulario de acceso a la red
     ============================================= -->
<main class="auth-page">
    <div class="container">

        <!-- Grid centrado verticalmente -->
        <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">

                <!-- Tarjeta principal del formulario -->
                <div class="auth-card">

                    <!-- Cabecera con ícono y título -->
                    <div class="auth-card-header text-center mb-4">
                        <div class="auth-icon mb-3">
                            <i class="bi bi-terminal-fill"></i>
                        </div>
                        <h1 class="auth-title">Iniciar Sesión</h1>
                        <p class="auth-subtitle">Accede a tu cuenta en la red</p>
                    </div>

                    <!-- Alerta de error del servidor -->
                    <?php if (!empty($error)): ?>
                        <div class="alert-glitch alert-glitch-error mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Confirmación de envío -->
                    <?php if (!empty($success)): ?>
                        <div class="alert-glitch alert-glitch-success mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulario de login -->
                    <form action="login.php" method="POST" id="loginForm" novalidate>

                        <!-- Campo email -->
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

                        <!-- Campo contraseña con botón de visibilidad -->
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
                                <!-- Alterna entre text y password para ver la contraseña -->
                                <button
                                    class="btn auth-toggle-btn"
                                    type="button"
                                    id="togglePassword"
                                    aria-label="Mostrar u ocultar contraseña">
                                    <i class="bi bi-eye-fill" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Checkbox para mantener la sesión -->
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

                        <!-- Botón de envío -->
                        <button type="submit" class="btn btn-neon w-100 py-2 mb-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Conectarse a la Red
                        </button>

                    </form>

                    <!-- Separador hacia el registro -->
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
        <!-- /row -->

    </div>
</main>

<script>
    // Alterna visibilidad de la contraseña y cambia el ícono del botón
    document.getElementById('togglePassword').addEventListener('click', function() {
        const input = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash-fill';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye-fill';
        }
    });

    // Validación del lado del cliente antes de enviar el formulario
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();

        // Expresión regular básica para validar formato de email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!email || !password) {
            e.preventDefault();
            alert('Por favor completa todos los campos.');
        } else if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('El formato del correo electrónico no es válido.');
        }
    });
</script>

<?php include 'includes/footer.php'; ?>