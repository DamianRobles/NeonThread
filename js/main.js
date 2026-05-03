// main.js — Scripts globales de NeonThread
// Se carga en todas las páginas a través de footer.php

document.addEventListener('DOMContentLoaded', function () {

    // ================================================
    // NAVBAR — Resaltar el link activo según la URL
    // endsWith() compara el pathname con el href de cada link.
    // Ejemplo: "/NeonThread/forum.php".endsWith("forum.php") → true
    // ================================================
    const currentPath = window.location.pathname;

    document.querySelectorAll('.nav-link').forEach(function (link) {
        if (link.href && currentPath.endsWith(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });


    // ================================================
    // ALERTA IN-PAGE
    // Reemplaza los alert() nativos con notificaciones estilizadas
    // que usan las clases .alert-glitch ya definidas en main.css.
    // Se inserta al inicio de .auth-card y desaparece en 4 segundos.
    // ================================================
    window.mostrarAlerta = function (mensaje, tipo = 'error') {
        const contenedor = document.querySelector('.auth-card') ||
            document.querySelector('form');
        if (!contenedor) return;

        // Eliminar alerta previa para no acumularlas
        const previa = contenedor.querySelector('.alert-glitch-js');
        if (previa) previa.remove();

        const icono = tipo === 'error'
            ? 'bi-exclamation-triangle-fill'
            : 'bi-check-circle-fill';

        const alerta = document.createElement('div');
        alerta.className = `alert-glitch alert-glitch-${tipo} alert-glitch-js mb-4`;
        alerta.setAttribute('role', 'alert');
        alerta.innerHTML = `<i class="bi ${icono} me-2"></i>${mensaje}`;

        // Insertar antes del primer hijo del contenedor
        contenedor.insertBefore(alerta, contenedor.firstChild);

        // Auto-eliminar con fade out después de 4 segundos
        setTimeout(function () {
            alerta.style.transition = 'opacity 0.4s ease';
            alerta.style.opacity = '0';
            setTimeout(function () { alerta.remove(); }, 400);
        }, 4000);
    };


    // ================================================
    // TOGGLE DE CONTRASEÑA
    // Alterna el type del input entre "password" y "text".
    // Busca todos los botones con data-toggle="password" para
    // reutilizarlo en login.php y register.php sin duplicar código.
    // ================================================
    document.querySelectorAll('[data-toggle="password"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash-fill';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye-fill';
            }
        });
    });


    // ================================================
    // FORTALEZA DE CONTRASEÑA
    // Evalúa 4 criterios y actualiza la barra y etiqueta
    // en tiempo real. Solo actúa si el elemento existe.
    // ================================================
    const passwordInput = document.getElementById('password');
    const strengthFill = document.getElementById('strengthFill');
    const strengthLabel = document.getElementById('strengthLabel');

    if (passwordInput && strengthFill && strengthLabel) {
        passwordInput.addEventListener('input', function () {
            const val = this.value;
            let score = 0;

            // Un punto por cada criterio de complejidad cumplido
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^a-zA-Z0-9]/.test(val)) score++;

            const levels = [
                { w: '0%', color: 'transparent', text: 'Ingresa una contraseña' },
                { w: '25%', color: '#ff0090', text: 'Muy débil' },
                { w: '50%', color: '#ff6600', text: 'Débil' },
                { w: '75%', color: '#ffcc00', text: 'Aceptable' },
                { w: '100%', color: '#00f5ff', text: 'Fuerte' },
            ];

            strengthFill.style.width = levels[score].w;
            strengthFill.style.backgroundColor = levels[score].color;
            strengthLabel.textContent = levels[score].text;
            strengthLabel.style.color = levels[score].color;
        });
    }


    // ================================================
    // COINCIDENCIA DE CONTRASEÑAS
    // Muestra un ícono de check o X al escribir la confirmación.
    // Solo actúa si el campo de confirmación existe (register.php).
    // ================================================
    const passwordConfirm = document.getElementById('password_confirm');
    const matchIcon = document.getElementById('matchIcon');

    if (passwordConfirm && matchIcon && passwordInput) {
        passwordConfirm.addEventListener('input', function () {
            if (this.value === '') {
                matchIcon.innerHTML = '';
            } else if (this.value === passwordInput.value) {
                matchIcon.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#00f5ff;padding:0 10px;"></i>';
            } else {
                matchIcon.innerHTML = '<i class="bi bi-x-circle-fill" style="color:#ff0090;padding:0 10px;"></i>';
            }
        });
    }


    // ================================================
    // CONTADOR DE CARACTERES
    // Actualiza el texto "N / máx" al escribir en un textarea
    // con el atributo data-counter="true".
    // Usado en thread.php y new-thread.php.
    // ================================================
    document.querySelectorAll('textarea[data-counter]').forEach(function (textarea) {
        const counterId = textarea.dataset.counter;
        const counter = document.getElementById(counterId);
        if (!counter) return;

        const max = textarea.getAttribute('maxlength') || '∞';

        textarea.addEventListener('input', function () {
            counter.textContent = this.value.length + ' / ' + max;
        });
    });

});


// ================================================
// TOGGLE LIKE
// Se declara fuera del DOMContentLoaded porque se llama
// desde atributos onclick en el HTML de thread.php.
// Manejo visual únicamente — el INSERT real se hará con AJAX.
// ================================================
function toggleLike(btn, id) {
    const countEl = btn.querySelector('.like-count');
    const icon = btn.querySelector('i');
    let count = parseInt(countEl.textContent);

    if (btn.classList.contains('liked')) {
        btn.classList.remove('liked');
        icon.className = 'bi bi-heart me-1';
        countEl.textContent = count - 1;
    } else {
        btn.classList.add('liked');
        icon.className = 'bi bi-heart-fill me-1';
        countEl.textContent = count + 1;
    }
}