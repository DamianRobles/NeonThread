<?php
// Valores por defecto para las variables que cada página puede personalizar
// El operador ?? asigna el valor de la derecha solo si la variable no existe.
$basePath      = $basePath      ?? "./";         // Ruta base para construir links (cambia si la página está en una subcarpeta)
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <!-- Hace que el diseño sea responsivo en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="NeonThread — El foro underground de la cultura cyberpunk" />

    <!-- htmlspecialchars() previene inyección de código HTML en el título -->
    <title>NeonThread</title>

    <!-- Bootstrap CSS desde CDN: proporciona el sistema de grilla y componentes base -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous" />

    <!-- Bootstrap Icons desde CDN: librería de íconos usados en toda la interfaz -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.12.1/font/bootstrap-icons.min.css"
        rel="stylesheet" />

    <!-- Estilos propios del proyecto: variables de color, componentes y estilos de cada página -->
    <link href="<?= $basePath ?>css/main.css" rel="stylesheet" />
</head>

<body>

    <!-- Navbar fija en la parte superior (fixed-top) -->
    <!-- navbar-expand-lg: el menú se colapsa en pantallas menores a 992px -->
    <nav class="navbar navbar-expand-lg navbar-glitch fixed-top" id="mainNav">
        <div class="container">

            <!-- Logo / nombre del sitio: lleva al inicio -->
            <a class="navbar-brand glitch-brand" href="<?= $basePath ?>index.php">
                <i class="bi bi-terminal-fill me-2"></i>NeonThread
            </a>

            <!-- Botón hamburguesa: visible solo en móvil, controla el colapso del menú -->
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

            <!-- Contenido del navbar: se oculta en móvil y se muestra al presionar hamburguesa -->
            <div class="collapse navbar-collapse" id="navbarMain">

                <!-- Links del lado izquierdo -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                    <!-- Dropdown de secciones del foro -->
                    <!-- data-bs-toggle="dropdown" activa el comportamiento de Bootstrap -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-glitch dropdown-toggle" href="#"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-collection-fill me-1"></i>Secciones
                        </a>
                        <!-- Cada item lleva a section.php con un id distinto por parámetro GET -->
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

                <!-- Links del lado derecho: acciones de sesión -->
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-2">
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
                </ul>

            </div>
        </div>
    </nav>

    <!-- Espacio para compensar la altura del navbar fixed-top (66px) -->
    <!-- Sin esto el contenido de la página quedaría tapado por el navbar -->
    <div style="padding-top: 66px;"></div>