<?php
// session_start() debe llamarse antes de cualquier output HTML.
// if(!isset) evita el error de "session already started" si alguna página
// ya llamó a session_start() antes de incluir este archivo.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Valores por defecto para las variables que cada página puede personalizar
// antes de hacer include de este archivo.
$activeSection = $activeSection ?? "";
$basePath      = $basePath      ?? "./";
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="NeonThread — El foro underground de la cultura cyberpunk" />
    <title>NeonThread</title>

    <!-- Bootstrap CSS desde CDN: proporciona el sistema de grid y componentes base -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous" />

    <!-- Bootstrap Icons desde CDN: librería de íconos usados en toda la interfaz -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.12.1/font/bootstrap-icons.min.css"
        rel="stylesheet" />

    <!-- Estilos propios del proyecto -->
    <link href="<?= $basePath ?>css/main.css" rel="stylesheet" />
</head>

<body>

    <!-- Navbar fija en la parte superior -->
    <!-- navbar-expand-lg: el menú se colapsa en pantallas menores a 992px -->
    <nav class="navbar navbar-expand-lg navbar-glitch fixed-top" id="mainNav">
        <div class="container">

            <!-- Logo -->
            <a class="navbar-brand glitch-brand" href="<?= $basePath ?>index.php">
                <i class="bi bi-terminal-fill me-2"></i>NeonThread
            </a>

            <!-- Botón hamburguesa: visible solo en móvil -->
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarMain"
                aria-controls="navbarMain"
                aria-expanded="false"
                aria-label="Abrir menú">
                <i class="bi bi-list text-neon-cyan fs-4"></i>
            </button>

            <!-- Contenido del navbar -->
            <div class="collapse navbar-collapse" id="navbarMain">

                <!-- Links del lado izquierdo -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                    <!-- Dropdown de secciones del foro -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-glitch dropdown-toggle" href="#"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-collection-fill me-1"></i>Secciones
                        </a>
                        <ul class="dropdown-menu dropdown-glitch">
                            <li>
                                <a class="dropdown-item" href="<?= $basePath ?>section.php?id=1">
                                    <i class="bi bi-controller me-2 text-neon-cyan"></i>Videojuegos
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $basePath ?>section.php?id=2">
                                    <i class="bi bi-book-fill me-2 text-neon-cyan"></i>Libros
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $basePath ?>section.php?id=3">
                                    <i class="bi bi-dice-5-fill me-2 text-neon-cyan"></i>Juegos de Mesa
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $basePath ?>section.php?id=4">
                                    <i class="bi bi-cpu-fill me-2 text-neon-cyan"></i>Lore Cyberpunk
                                </a>
                            </li>
                        </ul>
                    </li>

                </ul>

                <!-- Barra de búsqueda central: envía a search.php con el parámetro ?q= -->
                <form action="<?= $basePath ?>search.php" method="GET"
                    class="navbar-search mx-lg-3 my-2 my-lg-0" role="search">
                    <div class="input-group input-group-sm">
                        <input
                            type="search"
                            name="q"
                            class="form-control navbar-search-input"
                            placeholder="Buscar hilos..."
                            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                            maxlength="100"
                            aria-label="Buscar hilos" />
                        <button type="submit" class="btn btn-neon-sm" aria-label="Buscar">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>

                <!-- Links del lado derecho: cambian según el estado de sesión -->
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-2">

                    <?php if (isset($_SESSION['usuario_id'])): ?>

                        <!-- Usuario autenticado: mostrar su nombre y opciones de cuenta -->

                        <!-- Enlace al perfil con el nombre del usuario -->
                        <li class="nav-item">
                            <a class="nav-link nav-glitch" href="<?= $basePath ?>profile.php">
                                <i class="bi bi-person-circle me-1 text-neon-cyan"></i>
                                <?= htmlspecialchars($_SESSION['username']) ?>
                            </a>
                        </li>

                        <!-- Enlace al panel de admin solo si el rol es 'admin' -->
                        <?php if ($_SESSION['rol'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link nav-glitch" href="<?= $basePath ?>admin/dashboard.php">
                                    <i class="bi bi-shield-fill me-1 text-neon-pink"></i>Admin
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Botón para cerrar sesión -->
                        <li class="nav-item">
                            <a class="btn btn-neon btn-sm" href="<?= $basePath ?>logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i>Salir
                            </a>
                        </li>

                    <?php else: ?>

                        <!-- Usuario no autenticado: mostrar login y registro -->
                        <li class="nav-item">
                            <a class="nav-link nav-glitch" href="<?= $basePath ?>login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-neon btn-sm" href="<?= $basePath ?>register.php">
                                <i class="bi bi-person-plus-fill me-1"></i>Registrarse
                            </a>
                        </li>

                    <?php endif; ?>

                </ul>

            </div>
        </div>
    </nav>

    <!-- Espacio para compensar la altura del navbar fixed-top (66px) -->
    <div style="padding-top: 66px;"></div>