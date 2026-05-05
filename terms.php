<?php
/**
 * terms.php — Términos y Condiciones de NeonThread
 */
require_once 'includes/db.php';

$activeSection = '';
$basePath      = './';
include 'includes/header.php';
?>

<style>
.terms-section       { margin-bottom: 2.5rem; }
.terms-section-title {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: var(--gb-cyan);
    border-bottom: 1px solid var(--gb-border);
    padding-bottom: 0.6rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.terms-body          { font-size: 0.88rem; line-height: 1.9; color: var(--gb-text); }
.terms-body p        { margin-bottom: 0.85rem; }
.terms-body p:last-child { margin-bottom: 0; }
.terms-list          { list-style: none; padding: 0; margin: 0 0 0.85rem; display: flex; flex-direction: column; gap: 0.5rem; }
.terms-list li       { display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.88rem; line-height: 1.6; }
.terms-list li i     { flex-shrink: 0; margin-top: 3px; font-size: 0.8rem; }
.terms-highlight     { background-color: var(--gb-surface-2); border-left: 3px solid var(--gb-cyan); padding: 0.85rem 1rem; border-radius: 0 4px 4px 0; font-size: 0.85rem; line-height: 1.7; margin-bottom: 0.85rem; }
.terms-highlight.pink { border-left-color: var(--gb-pink); }
.terms-index         { background-color: var(--gb-surface); border: 1px solid var(--gb-border); border-radius: 4px; padding: 1.25rem; margin-bottom: 2rem; }
.terms-index-title   { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 2px; color: var(--gb-text-muted); margin-bottom: 0.75rem; }
.terms-index-list    { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.3rem; }
.terms-index-list a  { color: var(--gb-text-muted); text-decoration: none; font-size: 0.82rem; transition: color 0.15s; display: flex; align-items: center; gap: 0.5rem; }
.terms-index-list a:hover { color: var(--gb-cyan); }
</style>

<!-- CABECERA -->
<section class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1 class="page-header-title">
                    <i class="bi bi-file-text-fill me-2 text-neon-cyan"></i>
                    Términos y Condiciones
                </h1>
                <p class="page-header-sub">
                    Protocolo de uso de la red NeonThread ·
                    <span class="text-muted-gb" style="font-size:0.72rem;">
                        Última actualización: <?= date('d M Y') ?>
                    </span>
                </p>
            </div>
            <a href="<?= $basePath ?>register.php" class="btn btn-neon btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver al registro
            </a>
        </div>
    </div>
</section>

<main class="py-4">
<div class="container">
<div class="row justify-content-center">
<div class="col-12 col-lg-9">

    <div class="terms-highlight mb-4">
        <i class="bi bi-info-circle-fill text-neon-cyan me-2"></i>
        Al crear una cuenta en NeonThread aceptas cumplir con las reglas descritas
        en este documento. Léelas antes de registrarte.
    </div>

    <!-- Índice -->
    <div class="terms-index">
        <p class="terms-index-title"><i class="bi bi-list-ul me-2"></i>Contenido</p>
        <ol class="terms-index-list">
            <li><a href="#que-es"><i class="bi bi-chevron-right"></i>1. Qué es NeonThread</a></li>
            <li><a href="#cuenta"><i class="bi bi-chevron-right"></i>2. Tu cuenta</a></li>
            <li><a href="#datos"><i class="bi bi-chevron-right"></i>3. Qué datos almacenamos</a></li>
            <li><a href="#contenido"><i class="bi bi-chevron-right"></i>4. Publicar contenido</a></li>
            <li><a href="#conducta"><i class="bi bi-chevron-right"></i>5. Normas de conducta</a></li>
            <li><a href="#moderacion"><i class="bi bi-chevron-right"></i>6. Moderación</a></li>
            <li><a href="#limitaciones"><i class="bi bi-chevron-right"></i>7. Limitaciones del servicio</a></li>
        </ol>
    </div>

    <div class="card-glitch p-4">

        <!-- 1 -->
        <div class="terms-section" id="que-es">
            <h2 class="terms-section-title">
                <i class="bi bi-terminal-fill text-neon-cyan"></i>
                <span>01</span> Qué es NeonThread
            </h2>
            <div class="terms-body">
                <p>
                    NeonThread es un foro de discusión temático sobre cultura cyberpunk.
                    Los usuarios registrados pueden crear hilos de debate, responder a
                    publicaciones de otros usuarios y dar likes al contenido, dentro de
                    cuatro secciones: <strong>Videojuegos</strong>, <strong>Libros</strong>,
                    <strong>Juegos de Mesa</strong> y <strong>Lore Cyberpunk</strong>.
                </p>
                <p>
                    Es un proyecto académico en desarrollo. Algunas funciones que podrías
                    esperar en un foro convencional — mensajes privados, notificaciones,
                    recuperación de contraseña, sistema de reportes — actualmente
                    no están implementadas.
                </p>
            </div>
        </div>

        <!-- 2 -->
        <div class="terms-section" id="cuenta">
            <h2 class="terms-section-title">
                <i class="bi bi-person-fill text-neon-cyan"></i>
                <span>02</span> Tu cuenta
            </h2>
            <div class="terms-body">
                <p>Para participar necesitas registrarte con:</p>
                <ul class="terms-list mb-3">
                    <li>
                        <i class="bi bi-check2 text-neon-cyan"></i>
                        Un nombre de usuario de entre 3 y 50 caracteres
                        (solo letras, números, guiones y guiones bajos).
                    </li>
                    <li>
                        <i class="bi bi-check2 text-neon-cyan"></i>
                        Un correo electrónico válido. No será visible para otros usuarios.
                    </li>
                    <li>
                        <i class="bi bi-check2 text-neon-cyan"></i>
                        Una contraseña de al menos 8 caracteres.
                    </li>
                </ul>
                <p>
                    Eres responsable de la seguridad de tu contraseña.
                    <strong>No existe recuperación de contraseña por correo</strong> —
                    si la pierdes, deberás contactar directamente a un administrador
                    dentro del foro para que te ayude.
                </p>
                <p>
                    Puedes editar tu nombre de usuario, correo, contraseña y foto de perfil
                    en cualquier momento desde la página de edición de perfil. Si quieres
                    eliminar tu cuenta, también debes pedírselo a un administrador.
                </p>
            </div>
        </div>

        <!-- 3 -->
        <div class="terms-section" id="datos">
            <h2 class="terms-section-title">
                <i class="bi bi-lock-fill text-neon-cyan"></i>
                <span>03</span> Qué datos almacenamos
            </h2>
            <div class="terms-body">
                <p>
                    NeonThread guarda únicamente lo que tú introduces directamente:
                </p>
                <ul class="terms-list mb-3">
                    <li>
                        <i class="bi bi-check2 text-neon-cyan"></i>
                        <div>
                            <strong>Nombre de usuario</strong> — visible en tu perfil,
                            en todos tus hilos y respuestas.
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-check2 text-neon-cyan"></i>
                        <div>
                            <strong>Correo electrónico</strong> — visible solo para
                            administradores. No aparece en tu perfil público.
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-check2 text-neon-cyan"></i>
                        <div>
                            <strong>Contraseña</strong> — almacenada como hash bcrypt.
                            Nadie puede leer tu contraseña original, ni los administradores.
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-check2 text-neon-cyan"></i>
                        <div>
                            <strong>Foto de perfil</strong> — opcional. Si la subes,
                            se guarda en el servidor y es pública.
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-check2 text-neon-cyan"></i>
                        <div>
                            <strong>Contenido publicado</strong> — tus hilos, respuestas
                            y likes quedan registrados en la base de datos asociados
                            a tu cuenta.
                        </div>
                    </li>
                </ul>
                <div class="terms-highlight">
                    <i class="bi bi-info-circle-fill text-neon-cyan me-2"></i>
                    No se usan cookies de terceros, no se recopilan datos de navegación y
                    no se comparte información con servicios externos.
                    La base de datos reside únicamente en el servidor del proyecto.
                </div>
            </div>
        </div>

        <!-- 4 -->
        <div class="terms-section" id="contenido">
            <h2 class="terms-section-title">
                <i class="bi bi-chat-square-text-fill text-neon-cyan"></i>
                <span>04</span> Publicar contenido
            </h2>
            <div class="terms-body">
                <p>
                    Puedes crear hilos en cualquiera de las cuatro secciones del foro
                    y responder a los hilos de otros usuarios. Los títulos tienen un límite
                    de 255 caracteres y el contenido de 5000 caracteres por publicación.
                </p>
                <p>
                    Puedes editar tus propios hilos y eliminar tus propias respuestas
                    después de publicarlas. Los hilos que tengan respuestas de otros usuarios
                    seguirán siendo visibles aunque edites o elimines tu contenido original.
                </p>
                <ul class="terms-list">
                    <li>
                        <i class="bi bi-x-circle-fill text-neon-pink"></i>
                        No publiques en secciones que no correspondan al tema de tu hilo.
                    </li>
                    <li>
                        <i class="bi bi-x-circle-fill text-neon-pink"></i>
                        No publiques el mismo mensaje repetidamente en distintos hilos.
                    </li>
                    <li>
                        <i class="bi bi-x-circle-fill text-neon-pink"></i>
                        No publiques información personal de otras personas sin su consentimiento.
                    </li>
                </ul>
            </div>
        </div>

        <!-- 5 -->
        <div class="terms-section" id="conducta">
            <h2 class="terms-section-title">
                <i class="bi bi-shield-fill text-neon-cyan"></i>
                <span>05</span> Normas de conducta
            </h2>
            <div class="terms-body">
                <p>
                    El debate intenso sobre cultura cyberpunk es bienvenido.
                    El acoso y la falta de respeto no lo son.
                </p>
                <ul class="terms-list">
                    <li>
                        <i class="bi bi-check2 text-neon-cyan"></i>
                        Respeta a los demás usuarios aunque no compartas su opinión.
                    </li>
                    <li>
                        <i class="bi bi-check2 text-neon-cyan"></i>
                        Usa títulos descriptivos que expliquen de qué trata tu hilo.
                    </li>
                    <li>
                        <i class="bi bi-check2 text-neon-cyan"></i>
                        Advierte sobre spoilers al inicio de tu publicación cuando aplique.
                    </li>
                    <li>
                        <i class="bi bi-x-circle-fill text-neon-pink"></i>
                        No insultes, amenaces ni acoses a otros usuarios.
                    </li>
                    <li>
                        <i class="bi bi-x-circle-fill text-neon-pink"></i>
                        No hagas spam ni publiques contenido sin relación con el foro.
                    </li>
                </ul>
            </div>
        </div>

        <!-- 6 -->
        <div class="terms-section" id="moderacion">
            <h2 class="terms-section-title">
                <i class="bi bi-shield-lock-fill text-neon-pink"></i>
                <span>06</span> Moderación
            </h2>
            <div class="terms-body">
                <p>
                    Los administradores pueden eliminar cualquier hilo, respuesta o cuenta
                    de usuario cuando lo consideren necesario. También pueden cambiar el rol
                    de un usuario de normal a administrador y viceversa.
                </p>
                <div class="terms-highlight pink">
                    <i class="bi bi-exclamation-triangle-fill text-neon-pink me-2"></i>
                    No existe sistema de advertencias previas, suspensiones temporales
                    ni proceso de apelación. Si tu cuenta es eliminada, no hay forma
                    automática de recuperarla.
                </div>
                <p>
                    Si ves contenido que incumple estas normas, puedes comentárselo
                    directamente a un usuario con rol de administrador a través del foro.
                </p>
            </div>
        </div>

        <!-- 7 -->
        <div class="terms-section mb-0" id="limitaciones">
            <h2 class="terms-section-title">
                <i class="bi bi-exclamation-triangle-fill text-neon-cyan"></i>
                <span>07</span> Limitaciones del servicio
            </h2>
            <div class="terms-body">
                <p>
                    NeonThread es un proyecto académico. No garantizamos disponibilidad
                    continua ni la conservación permanente de los datos. El sitio puede
                    estar caído o reiniciarse sin aviso previo.
                </p>
                <p>Funciones que <strong>no existen</strong> en esta plataforma:</p>
                <ul class="terms-list">
                    <li><i class="bi bi-dash text-muted-gb"></i>Recuperación de contraseña por correo electrónico</li>
                    <li><i class="bi bi-dash text-muted-gb"></i>Mensajes privados entre usuarios</li>
                    <li><i class="bi bi-dash text-muted-gb"></i>Notificaciones de respuestas o likes</li>
                    <li><i class="bi bi-dash text-muted-gb"></i>Sistema de reportes de contenido</li>
                    <li><i class="bi bi-dash text-muted-gb"></i>Verificación de correo al registrarse</li>
                </ul>
            </div>
        </div>

    </div><!-- /card-glitch -->

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
        <span class="text-muted-gb" style="font-size:0.75rem;">
            <i class="bi bi-calendar3 me-1"></i>Vigentes desde <?= date('d M Y') ?>
        </span>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= $basePath ?>register.php" class="btn btn-neon-pink">
                <i class="bi bi-person-plus-fill me-2"></i>Crear mi cuenta
            </a>
            <a href="<?= $basePath ?>forum.php" class="btn btn-neon">
                <i class="bi bi-grid-3x3-gap-fill me-2"></i>Ir al foro
            </a>
        </div>
    </div>

</div>
</div>
</div>
</main>

<?php include 'includes/footer.php'; ?>
