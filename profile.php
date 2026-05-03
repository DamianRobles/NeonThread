<?php

/**
 * profile.php — Perfil público de un usuario
 *
 * Acepta dos formas de acceso:
 *   profile.php           → muestra el perfil del usuario logueado
 *   profile.php?user=3    → muestra el perfil del usuario con id=3
 *
 * Si no hay sesión y no se pasa ?user, redirige al login.
 */

require_once 'includes/db.php';

// Resolver qué perfil mostrar antes de imprimir HTML
// para poder redirigir si no existe el usuario
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si viene ?user=ID se muestra ese perfil; si no, el del usuario logueado
if (isset($_GET['user'])) {
    $userId = (int)$_GET['user'];
} elseif (isset($_SESSION['usuario_id'])) {
    $userId = (int)$_SESSION['usuario_id'];
} else {
    header('Location: login.php');
    exit;
}

// Cargar datos del usuario con estadísticas reales
$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.username,
        u.avatar,
        u.rol,
        u.created_at,
        COUNT(DISTINCT h.id)  AS total_hilos,
        COUNT(DISTINCT r.id)  AS total_respuestas,
        COUNT(DISTINCT l.id)  AS total_likes_recibidos
    FROM usuarios u
    LEFT JOIN hilos      h ON h.usuario_id = u.id
    LEFT JOIN respuestas r ON r.usuario_id = u.id
    LEFT JOIN likes      l ON l.hilo_id IN (
                                SELECT id FROM hilos WHERE usuario_id = u.id
                             )
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$userId]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: forum.php');
    exit;
}

// Hilos del usuario con sección, respuestas y fecha
$stmtHilos = $pdo->prepare("
    SELECT
        h.id,
        h.titulo,
        h.updated_at,
        s.nombre             AS seccion_nombre,
        s.icono              AS seccion_icono,
        COUNT(DISTINCT r.id) AS total_respuestas
    FROM hilos h
    JOIN secciones s        ON s.id = h.seccion_id
    LEFT JOIN respuestas r  ON r.hilo_id = h.id
    WHERE h.usuario_id = ?
    GROUP BY h.id
    ORDER BY h.updated_at DESC
    LIMIT 10
");
$stmtHilos->execute([$userId]);
$hilosUsuario = $stmtHilos->fetchAll();

$activeSection = "";
$basePath      = "./";
include 'includes/header.php';

// Es el propio perfil si el usuario logueado coincide con el perfil que se muestra
$esPropioPerfil = isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $userId;
$totalPosts     = $usuario['total_hilos'] + $usuario['total_respuestas'];

// Tiempo relativo desde un datetime de SQLite
function tiempoRelativo(string $fecha): string
{
    $diff = time() - strtotime($fecha);
    if ($diff < 60)     return 'hace unos segundos';
    if ($diff < 3600)   return 'hace ' . floor($diff / 60)   . ' min';
    if ($diff < 86400)  return 'hace ' . floor($diff / 3600) . ' h';
    if ($diff < 604800) return 'hace ' . floor($diff / 86400) . ' días';
    return date('d M Y', strtotime($fecha));
}

// Mes y año de registro legible: "Mayo 2026"
function fechaRegistro(string $fecha): string
{
    $meses = [
        'Enero',
        'Febrero',
        'Marzo',
        'Abril',
        'Mayo',
        'Junio',
        'Julio',
        'Agosto',
        'Septiembre',
        'Octubre',
        'Noviembre',
        'Diciembre'
    ];
    $ts    = strtotime($fecha);
    return $meses[date('n', $ts) - 1] . ' ' . date('Y', $ts);
}
?>

<!-- =============================================
     CABECERA DEL PERFIL
     ============================================= -->
<section class="profile-header">
    <div class="container">
        <div class="row align-items-center g-4">

            <!-- Avatar -->
            <div class="col-12 col-md-auto text-center text-md-start">
                <div class="profile-avatar-wrap mx-auto mx-md-0">
                    <?php if (!empty($usuario['avatar'])): ?>
                        <img
                            src="<?= htmlspecialchars($usuario['avatar']) ?>"
                            alt="Avatar de <?= htmlspecialchars($usuario['username']) ?>"
                            class="profile-avatar rounded-circle"
                            width="96" height="96" />
                    <?php else: ?>
                        <div class="profile-avatar-placeholder">
                            <?= strtoupper(substr($usuario['username'], 0, 2)) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Nombre y fecha de registro -->
            <div class="col-12 col-md text-center text-md-start">
                <div class="d-flex align-items-center justify-content-center
                            justify-content-md-start gap-3 flex-wrap mb-1">
                    <h1 class="profile-username mb-0">
                        <?= htmlspecialchars($usuario['username']) ?>
                    </h1>
                    <?php if ($usuario['rol'] === 'admin'): ?>
                        <span class="badge-pink">Admin</span>
                    <?php endif; ?>
                </div>
                <p class="text-muted-gb mb-0" style="font-size:0.8rem;">
                    <i class="bi bi-calendar3 me-1"></i>
                    Miembro desde <?= fechaRegistro($usuario['created_at']) ?>
                </p>
            </div>

            <!-- Botón editar solo visible en el propio perfil -->
            <?php if ($esPropioPerfil): ?>
                <div class="col-12 col-md-auto text-center text-md-end">
                    <a href="<?= $basePath ?>edit-profile.php" class="btn btn-neon">
                        <i class="bi bi-pencil-fill me-2"></i>Editar Perfil
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<!-- =============================================
     CONTENIDO PRINCIPAL
     ============================================= -->
<main class="py-4">
    <div class="container">
        <div class="row g-4">

            <!-- Sidebar: estadísticas -->
            <div class="col-12 col-lg-4">
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <i class="bi bi-bar-chart-fill me-2 text-neon-cyan"></i>Estadísticas
                    </div>
                    <div class="sidebar-card-body">

                        <div class="profile-stat-row">
                            <span class="profile-stat-label">
                                <i class="bi bi-chat-square-text-fill me-2 text-neon-cyan"></i>
                                Hilos creados
                            </span>
                            <span class="profile-stat-value text-neon-cyan">
                                <?= $usuario['total_hilos'] ?>
                            </span>
                        </div>

                        <div class="profile-stat-row">
                            <span class="profile-stat-label">
                                <i class="bi bi-reply-fill me-2 text-neon-cyan"></i>
                                Respuestas
                            </span>
                            <span class="profile-stat-value text-neon-cyan">
                                <?= $usuario['total_respuestas'] ?>
                            </span>
                        </div>

                        <div class="profile-stat-row">
                            <span class="profile-stat-label">
                                <i class="bi bi-heart-fill me-2 text-neon-pink"></i>
                                Likes recibidos
                            </span>
                            <span class="profile-stat-value text-neon-pink">
                                <?= $usuario['total_likes_recibidos'] ?>
                            </span>
                        </div>

                        <div class="profile-stat-row">
                            <span class="profile-stat-label">
                                <i class="bi bi-graph-up me-2 text-neon-cyan"></i>
                                Posts totales
                            </span>
                            <span class="profile-stat-value text-neon-cyan">
                                <?= $totalPosts ?>
                            </span>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Columna principal: hilos del usuario -->
            <div class="col-12 col-lg-8">

                <h2 class="forum-section-heading mb-3">
                    <span class="text-neon-pink">//</span>
                    Hilos de <?= htmlspecialchars($usuario['username']) ?>
                </h2>

                <?php if (!empty($hilosUsuario)): ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($hilosUsuario as $hilo): ?>
                            <a href="<?= $basePath ?>thread.php?id=<?= $hilo['id'] ?>"
                                class="thread-row card-glitch text-decoration-none p-3 d-flex align-items-center gap-3">

                                <!-- Ícono de la sección del hilo -->
                                <div class="thread-icon flex-shrink-0">
                                    <i class="bi <?= htmlspecialchars($hilo['seccion_icono']) ?> text-neon-cyan"></i>
                                </div>

                                <!-- Título y metadatos -->
                                <div class="flex-grow-1 min-w-0">
                                    <h3 class="thread-row-title mb-1">
                                        <?= htmlspecialchars($hilo['titulo']) ?>
                                    </h3>
                                    <div class="thread-row-meta">
                                        <span class="badge-section me-2">
                                            <?= htmlspecialchars($hilo['seccion_nombre']) ?>
                                        </span>
                                        <i class="bi bi-clock me-1"></i>
                                        <?= tiempoRelativo($hilo['updated_at']) ?>
                                    </div>
                                </div>

                                <!-- Contador de respuestas -->
                                <div class="thread-row-stats flex-shrink-0 text-end d-none d-sm-block">
                                    <div class="thread-stat-num text-neon-cyan">
                                        <?= $hilo['total_respuestas'] ?>
                                    </div>
                                    <div class="thread-stat-lbl">respuestas</div>
                                </div>

                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-chat-square-dots text-muted-gb" style="font-size:2rem;"></i>
                        <p class="mt-2 text-muted-gb">
                            <?= $esPropioPerfil ? 'Aún no has creado ningún hilo.' : 'Este usuario aún no ha creado ningún hilo.' ?>
                        </p>
                        <?php if ($esPropioPerfil): ?>
                            <a href="<?= $basePath ?>new-thread.php" class="btn btn-neon mt-3">
                                <i class="bi bi-plus-circle-fill me-2"></i>Crear mi primer hilo
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>