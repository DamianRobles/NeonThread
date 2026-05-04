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
        // Se excluye el form del navbar (.navbar-search) porque está primero
        // en el DOM y sería seleccionado por querySelector('form') antes que
        // el formulario real de la página
        const contenedor = document.querySelector('.auth-card') ||
            document.querySelector('.card-glitch form') ||
            document.querySelector('main form');
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
    // Actualiza el texto "N / máx" al escribir en inputs o textareas
    // que tengan el atributo data-counter="id-del-span".
    // Usado en thread.php y new-thread.php.
    // ================================================
    document.querySelectorAll('[data-counter]').forEach(function (el) {
        const counterId = el.dataset.counter;
        const counter = document.getElementById(counterId);
        if (!counter) return;

        const max = el.getAttribute('maxlength') || '∞';

        el.addEventListener('input', function () {
            counter.textContent = this.value.length + ' / ' + max;
        });
    });


    // ================================================
    // VALIDACIÓN — login.php
    // Verifica email y contraseña antes de enviar el formulario.
    // ================================================
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
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
    }


    // ================================================
    // VALIDACIÓN — register.php
    // Verifica username, email, contraseña y términos.
    // ================================================
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const passwordConf = document.getElementById('password_confirm').value;
            const terminos = document.getElementById('terminos').checked;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const usernameRegex = /^[a-zA-Z0-9_\-]+$/;

            if (!username || !email || !password || !passwordConf) {
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
            } else if (password !== passwordConf) {
                e.preventDefault();
                mostrarAlerta('Las contraseñas no coinciden.');
            } else if (!terminos) {
                e.preventDefault();
                mostrarAlerta('Debes aceptar los términos y condiciones para continuar.');
            }
        });
    }


    // ================================================
    // VALIDACIÓN — new-thread.php
    // Verifica sección, título y contenido.
    // ================================================
    const newThreadForm = document.getElementById('newThreadForm');
    if (newThreadForm) {
        newThreadForm.addEventListener('submit', function (e) {
            const seccion = document.querySelector('input[name="seccion_id"]:checked');
            const titulo = document.getElementById('titulo').value.trim();
            const contenido = document.getElementById('contenido').value.trim();

            if (!seccion) {
                e.preventDefault();
                mostrarAlerta('Por favor selecciona una sección.');
            } else if (!titulo) {
                e.preventDefault();
                mostrarAlerta('Por favor escribe un título para el hilo.');
            } else if (titulo.length < 10) {
                e.preventDefault();
                mostrarAlerta('El título debe tener al menos 10 caracteres.');
            } else if (!contenido) {
                e.preventDefault();
                mostrarAlerta('Por favor escribe el contenido del hilo.');
            } else if (contenido.length < 20) {
                e.preventDefault();
                mostrarAlerta('El contenido debe tener al menos 20 caracteres.');
            }
        });
    }


    // ================================================
    // VALIDACIÓN — edit-profile.php
    // Valida username y email. La contraseña es opcional:
    // solo valida si el usuario escribió algo en password_new.
    // ================================================
    const editProfileForm = document.getElementById('editProfileForm');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', function (e) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const passwordNew = document.getElementById('password_new').value;
            const passwordCon = document.getElementById('password_new_confirm').value;
            const password = document.getElementById('password').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const usernameRegex = /^[a-zA-Z0-9_\-]+$/;

            if (!username || !email) {
                e.preventDefault();
                mostrarAlerta('El nombre de usuario y el correo no pueden estar vacíos.');
            } else if (username.length < 3 || username.length > 50) {
                e.preventDefault();
                mostrarAlerta('El nombre de usuario debe tener entre 3 y 50 caracteres.');
            } else if (!usernameRegex.test(username)) {
                e.preventDefault();
                mostrarAlerta('El nombre de usuario solo puede contener letras, números, guiones y guiones bajos.');
            } else if (!emailRegex.test(email)) {
                e.preventDefault();
                mostrarAlerta('El formato del correo electrónico no es válido.');
            } else if (passwordNew && !password) {
                e.preventDefault();
                mostrarAlerta('Debes ingresar tu contraseña actual para poder cambiarla.');
            } else if (passwordNew && passwordNew.length < 8) {
                e.preventDefault();
                mostrarAlerta('La nueva contraseña debe tener al menos 8 caracteres.');
            } else if (passwordNew && passwordNew !== passwordCon) {
                e.preventDefault();
                mostrarAlerta('Las nuevas contraseñas no coinciden.');
            }
        });
    }

});


// ================================================
// CONFIRM DELETE — admin/dashboard.php
// Reemplaza el confirm() nativo con un modal estilizado.
// Devuelve false siempre para cancelar el submit inmediato,
// y envía el form manualmente si el usuario confirma.
// ================================================
function confirmDelete(tipo, nombre) {
    // Eliminar modal previo si existe
    const previo = document.getElementById('confirmModal');
    if (previo) previo.remove();

    const modal = document.createElement('div');
    modal.id = 'confirmModal';
    modal.style.cssText = `
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(0,0,0,0.75);
        display: flex; align-items: center; justify-content: center;
        padding: 1rem;
    `;

    modal.innerHTML = `
        <div style="
            background: var(--gb-surface);
            border: 1px solid var(--gb-pink);
            border-radius: 4px;
            padding: 2rem;
            max-width: 420px;
            width: 100%;
            font-family: var(--gb-font);
        ">
            <div style="font-size:1.5rem; color: var(--gb-pink); margin-bottom:.75rem;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <p style="color: var(--gb-text); font-size:.88rem; margin-bottom:.5rem;">
                ¿Seguro que quieres eliminar este ${tipo}?
            </p>
            <p style="color: var(--gb-text-muted); font-size:.8rem; margin-bottom:1.5rem;">
                "${nombre}"
            </p>
            <p style="color: var(--gb-pink); font-size:.75rem; margin-bottom:1.5rem;">
                Esta acción no se puede deshacer.
            </p>
            <div style="display:flex; gap:.75rem; justify-content:flex-end;">
                <button id="cancelBtn" style="
                    background: transparent;
                    border: 1px solid var(--gb-border);
                    color: var(--gb-text-muted);
                    padding: .4rem 1rem;
                    border-radius: 2px;
                    cursor: pointer;
                    font-family: var(--gb-font);
                    font-size: .78rem;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                ">Cancelar</button>
                <button id="confirmBtn" style="
                    background: var(--gb-pink-dim);
                    border: 1px solid var(--gb-pink);
                    color: var(--gb-pink);
                    padding: .4rem 1rem;
                    border-radius: 2px;
                    cursor: pointer;
                    font-family: var(--gb-font);
                    font-size: .78rem;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                ">Eliminar</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Guardar referencia al form que disparó el evento
    const form = document.activeElement.closest('form');

    document.getElementById('cancelBtn').addEventListener('click', function () {
        modal.remove();
    });

    document.getElementById('confirmBtn').addEventListener('click', function () {
        modal.remove();
        if (form) form.submit();
    });

    // Cerrar al hacer click fuera del modal
    modal.addEventListener('click', function (e) {
        if (e.target === modal) modal.remove();
    });

    // Siempre retorna false para cancelar el submit original
    return false;
}
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