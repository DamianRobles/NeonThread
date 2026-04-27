<?php
// se asigna "./" como valor por defecto para que los links no se rompan
$basePath = $basePath ?? "./";
?>

<!-- Footer del sitio -->
<footer class="footer-glitch mt-auto py-4">
    <div class="container">

        <!-- Flexbox: en móvil los elementos se apilan, en escritorio se alinean en fila -->
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">

            <!-- Logo -->
            <a href="<?= $basePath ?>index.php"
                class="d-flex align-items-center gap-2 text-decoration-none">
                <i class="bi bi-terminal-fill text-neon-cyan"></i>
                <span class="text-neon-cyan fw-bold text-uppercase"
                    style="letter-spacing:3px;">NeonThread</span>
            </a>

            <!-- Descripción corta del sitio -->
            <p class="footer-tagline mb-0 text-center">
                El foro underground de la cultura cyberpunk.
                Conéctate. Discute. Sobrevive a la red.
            </p>

            <!-- Copyright: date('Y') genera el año actual dinámicamente -->
            <p class="footer-copy mb-0">
                &copy; <?= date('Y') ?> NeonThread
            </p>

        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle desde CDN -->
<!-- Incluye Popper.js, necesario para dropdowns y tooltips de Bootstrap -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"></script>

<!-- Script propio del proyecto (main.js) -->
<script src="<?= $basePath ?>js/main.js"></script>

</body>

</html>