<?php
// Variables que header.php necesita para personalizar el título y la ruta base
$pageTitle     = "Inicio";
$activeSection = "";   // Ninguna sección del navbar se resalta en el inicio
$basePath      = "./"; // Las páginas en la raíz usan "./" como ruta base
include 'includes/header.php';

// Datos de las secciones del foro
// TODO: reemplazar con consulta SELECT a la tabla secciones cuando se conecte la BD
$secciones = [
    [
        "id"          => 1,
        "nombre"      => "Videojuegos",
        "descripcion" => "Cyberpunk 2077, Deus Ex, Observer y todo el gaming distópico.",
        "icono"       => "bi-controller",
        "hilos"       => 142,
        "color"       => "cyan",
    ],
    [
        "id"          => 2,
        "nombre"      => "Libros",
        "descripcion" => "Neuromante, Snow Crash, ¿Sueñan los androides? y más literatura.",
        "icono"       => "bi-book-fill",
        "hilos"       => 98,
        "color"       => "pink",
    ],
    [
        "id"          => 3,
        "nombre"      => "Juegos de Mesa",
        "descripcion" => "Shadowrun, Android Netrunner, Cyberpunk RED y otros TTRPGs.",
        "icono"       => "bi-dice-5-fill",
        "hilos"       => 57,
        "color"       => "cyan",
    ],
    [
        "id"          => 4,
        "nombre"      => "Lore Cyberpunk",
        "descripcion" => "Corporaciones, tecnología, filosofía y estética del género.",
        "icono"       => "bi-cpu-fill",
        "hilos"       => 203,
        "color"       => "pink",
    ],
];

// Hilos recientes para mostrar en la landing
// TODO: reemplazar con consulta SELECT a hilos ordenados por fecha DESC cuando se conecte la BD
$hilosRecientes = [
    [
        "id"         => 1,
        "titulo"     => "¿Cuál es el mejor mod gráfico para Cyberpunk 2077 en 2025?",
        "autor"      => "NetRunner_77",
        "seccion"    => "Videojuegos",
        "seccion_id" => 1,
        "respuestas" => 24,
        "likes"      => 15,
        "tiempo"     => "hace 2 horas",
    ],
    [
        "id"         => 2,
        "titulo"     => "Reseña: Neuromante de William Gibson — el libro que lo inició todo",
        "autor"      => "GhostShell_X",
        "seccion"    => "Libros",
        "seccion_id" => 2,
        "respuestas" => 18,
        "likes"      => 32,
        "tiempo"     => "hace 5 horas",
    ],
    [
        "id"         => 3,
        "titulo"     => "Guía de inicio para Shadowrun 6ª Edición — recursos en español",
        "autor"      => "Fixer_Ramos",
        "seccion"    => "Juegos de Mesa",
        "seccion_id" => 3,
        "respuestas" => 9,
        "likes"      => 21,
        "tiempo"     => "hace 1 día",
    ],
    [
        "id"         => 4,
        "titulo"     => "Debate: ¿Es el cyberpunk un género optimista o pesimista?",
        "autor"      => "VoidWalker",
        "seccion"    => "Lore Cyberpunk",
        "seccion_id" => 4,
        "respuestas" => 41,
        "likes"      => 67,
        "tiempo"     => "hace 1 día",
    ],
    [
        "id"         => 5,
        "titulo"     => "Android: Netrunner — diferencias entre la edición original y Revised Core",
        "autor"      => "IceBreaker_9",
        "seccion"    => "Juegos de Mesa",
        "seccion_id" => 3,
        "respuestas" => 7,
        "likes"      => 11,
        "tiempo"     => "hace 2 días",
    ],
    [
        "id"         => 6,
        "titulo"     => "Megacorporaciones reales vs ficticias: ¿ya llegamos al cyberpunk?",
        "autor"      => "SynthEtica",
        "seccion"    => "Lore Cyberpunk",
        "seccion_id" => 4,
        "respuestas" => 55,
        "likes"      => 89,
        "tiempo"     => "hace 3 días",
    ],
];
?>

<!-- =============================================
     HERO
     Sección principal con título y botones de acción.
     ============================================= -->
<section class="hero-section" id="inicio">
    <div class="hero-overlay"></div>

    <div class="container position-relative z-1 text-center py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">

                <!-- Texto decorativo en japonés estilo furigana -->
                <p class="hero-furigana">ネオンスレッド</p>

                <!-- Título principal dividido en dos colores -->
                <h1 class="hero-title">
                    <span class="text-neon-cyan">Neon</span><span class="text-neon-pink">Thread</span>
                </h1>

                <!-- Botones de acción principales -->
                <div class="d-flex gap-3 justify-content-center flex-wrap mt-4">
                    <a href="<?= $basePath ?>forum.php" class="btn btn-neon px-4 py-2">
                        <i class="bi bi-grid-3x3-gap-fill me-2"></i>Explorar el Foro
                    </a>
                    <a href="<?= $basePath ?>register.php" class="btn btn-neon-pink px-4 py-2">
                        <i class="bi bi-person-plus-fill me-2"></i>Unirse a la Red
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- =============================================
     SECCIONES DEL FORO
     Se itera sobre $secciones para generar una card por cada una.
     Cada card enlaza a section.php pasando el id por GET.
     ============================================= -->
<section class="py-5" id="secciones">
    <div class="container">

        <div class="section-header text-center mb-5">
            <h2 class="section-title">
                <span class="text-neon-pink">//</span>
                Secciones del Foro
                <span class="text-neon-pink">//</span>
            </h2>
        </div>

        <!-- Grilla: 1 columna en móvil, 2 en tablet, 4 en escritorio -->
        <div class="row g-4">
            <?php foreach ($secciones as $seccion): ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <a href="<?= $basePath ?>section.php?id=<?= $seccion['id'] ?>"
                        class="card-glitch section-card text-decoration-none d-flex flex-column h-100 p-4">

                        <!-- Icono con color dinámico según el campo 'color' del array -->
                        <div class="section-icon mb-3 <?= $seccion['color'] === 'cyan' ? 'section-icon-cyan' : 'section-icon-pink' ?>">
                            <i class="bi <?= htmlspecialchars($seccion['icono']) ?>"></i>
                        </div>

                        <!-- Nombre de la sección -->
                        <h5 class="section-card-title <?= $seccion['color'] === 'cyan' ? 'text-neon-cyan' : 'text-neon-pink' ?>">
                            <?= htmlspecialchars($seccion['nombre']) ?>
                        </h5>

                        <!-- Descripción: flex-grow-1 empuja el footer hacia abajo -->
                        <p class="section-card-desc flex-grow-1">
                            <?= htmlspecialchars($seccion['descripcion']) ?>
                        </p>

                        <!-- Pie de card: número de hilos y flecha -->
                        <div class="section-card-footer mt-3">
                            <span class="text-muted-gb" style="font-size:0.78rem;">
                                <i class="bi bi-chat-square-dots me-1"></i>
                                <?= $seccion['hilos'] ?> hilos
                            </span>
                            <span class="section-arrow <?= $seccion['color'] === 'cyan' ? 'text-neon-cyan' : 'text-neon-pink' ?>">
                                <i class="bi bi-arrow-right-circle-fill"></i>
                            </span>
                        </div>

                    </a>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<!-- =============================================
     HILOS RECIENTES
     Muestra los últimos 6 hilos del foro.
     TODO: reemplazar $hilosRecientes con un SELECT ordenado por fecha DESC.
     ============================================= -->
<section class="py-5 recent-section" id="recientes">
    <div class="container">

        <div class="section-header d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h2 class="section-title mb-0">
                <span class="text-neon-cyan">//</span>
                Hilos Recientes
            </h2>
            <a href="<?= $basePath ?>forum.php" class="btn btn-neon btn-sm">
                Ver todos <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <!-- Grilla de 2 columnas en escritorio, 1 en móvil -->
        <div class="row g-3 align-items-stretch">
            <?php foreach ($hilosRecientes as $hilo): ?>
                <div class="col-12 col-lg-6">
                    <a href="<?= $basePath ?>thread.php?id=<?= $hilo['id'] ?>"
                        class="thread-card card-glitch text-decoration-none d-flex align-items-center gap-3 p-3 h-100">
                        <!-- Icono de la sección a la que pertenece el hilo -->
                        <div class="thread-icon flex-shrink-0">
                            <?php
                            // Mapa de seccion_id → icono de Bootstrap Icons
                            $iconos = [
                                1 => 'bi-controller',
                                2 => 'bi-book-fill',
                                3 => 'bi-dice-5-fill',
                                4 => 'bi-cpu-fill'
                            ];
                            // Si el id no existe en el mapa se usa un icono genérico
                            $icono = $iconos[$hilo['seccion_id']] ?? 'bi-chat-dots';
                            ?>
                            <i class="bi <?= $icono ?> text-neon-cyan"></i>
                        </div>

                        <!-- Título y metadatos del hilo -->
                        <div class="flex-grow-1 min-w-0">
                            <h6 class="thread-title mb-1">
                                <?= htmlspecialchars($hilo['titulo']) ?>
                            </h6>
                            <div class="thread-meta d-flex align-items-center gap-3 flex-wrap">
                                <span class="text-muted-gb">
                                    <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($hilo['autor']) ?>
                                </span>
                                <span class="badge-section">
                                    <?= htmlspecialchars($hilo['seccion']) ?>
                                </span>
                                <span class="text-muted-gb">
                                    <i class="bi bi-clock me-1"></i><?= $hilo['tiempo'] ?>
                                </span>
                            </div>
                        </div>

                        <!-- Contadores de respuestas y likes -->
                        <div class="thread-stats flex-shrink-0 text-end">
                            <div class="text-muted-gb" style="font-size:0.75rem;">
                                <i class="bi bi-chat-dots me-1"></i><?= $hilo['respuestas'] ?>
                            </div>
                            <div class="text-neon-pink" style="font-size:0.75rem;">
                                <i class="bi bi-heart-fill me-1"></i><?= $hilo['likes'] ?>
                            </div>
                        </div>

                    </a>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<!-- =============================================
     CTA — LLAMADA A LA ACCIÓN
     Invita al usuario a registrarse o iniciar sesión.
     ============================================= -->
<section class="cta-section py-5">
    <div class="container">
        <div class="cta-card text-center p-5">
            <h2 class="cta-title mb-3">¿Listo para conectarte a la red?</h2>
            <p class="cta-subtitle mb-4">
                Únete a la comunidad, crea hilos, comenta y da likes.<br />
                El acceso es gratuito. No se requiere implante neural.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="<?= $basePath ?>register.php" class="btn btn-neon px-5 py-2">
                    <i class="bi bi-person-plus-fill me-2"></i>Crear Cuenta
                </a>
                <a href="<?= $basePath ?>login.php" class="btn btn-neon-pink px-5 py-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Ya tengo cuenta
                </a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>