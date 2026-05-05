/**
 * js/main.js — NeonThread
 * CORRECCIONES:
 *   - toggleLike() reemplazada por listener que hace fetch() a api/like.php
 *   - confirmarEliminar() recibe el submit event del form directamente
 *   - Contador de caracteres funciona al cargar (repobla el form)
 */

document.addEventListener('DOMContentLoaded', function () {

    // ─── TOGGLE VISIBILIDAD DE CONTRASEÑA ─────────────────────────────────
    document.querySelectorAll('[data-toggle="password"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(this.dataset.target);
            var icon  = this.querySelector('i');
            if (!input) return;
            if (input.type === 'password') {
                input.type    = 'text';
                icon.className = 'bi bi-eye-slash-fill';
            } else {
                input.type    = 'password';
                icon.className = 'bi bi-eye-fill';
            }
        });
    });

    // ─── FORTALEZA DE CONTRASEÑA ──────────────────────────────────────────
    var passInput     = document.getElementById('password');
    var strengthFill  = document.getElementById('strengthFill');
    var strengthLabel = document.getElementById('strengthLabel');
    if (passInput && strengthFill && strengthLabel) {
        passInput.addEventListener('input', function () {
            var v = this.value, score = 0;
            if (v.length >= 8)          score++;
            if (/[A-Z]/.test(v))        score++;
            if (/[0-9]/.test(v))        score++;
            if (/[^a-zA-Z0-9]/.test(v)) score++;
            var lvl = [
                { w:'0%',   c:'transparent', t:'Ingresa una contraseña' },
                { w:'25%',  c:'#ff0090',     t:'Muy débil' },
                { w:'50%',  c:'#ff6600',     t:'Débil' },
                { w:'75%',  c:'#ffcc00',     t:'Aceptable' },
                { w:'100%', c:'#00f5ff',     t:'Fuerte' },
            ][score];
            strengthFill.style.width           = lvl.w;
            strengthFill.style.backgroundColor = lvl.c;
            strengthLabel.textContent          = lvl.t;
            strengthLabel.style.color          = lvl.c;
        });
    }

    // ─── COINCIDENCIA DE CONTRASEÑAS ──────────────────────────────────────
    var passConf  = document.getElementById('password_confirm');
    var matchIcon = document.getElementById('matchIcon');
    if (passInput && passConf && matchIcon) {
        passConf.addEventListener('input', function () {
            matchIcon.style.display = 'flex';
            matchIcon.innerHTML = this.value === passInput.value && this.value
                ? '<i class="bi bi-check-circle-fill text-neon-cyan"></i>'
                : '<i class="bi bi-x-circle-fill text-neon-pink"></i>';
        });
    }

    // ─── CONTADOR DE CARACTERES ───────────────────────────────────────────
    document.querySelectorAll('textarea[data-counter]').forEach(function (ta) {
        var el  = document.getElementById(ta.dataset.counter);
        var max = parseInt(ta.getAttribute('maxlength')) || 5000;
        if (!el) return;
        function upd() {
            var n = ta.value.length;
            el.textContent = n + ' / ' + max;
            el.style.color = n > max * 0.9 ? 'var(--gb-pink)' : '';
        }
        ta.addEventListener('input', upd);
        upd();
    });

    // ─── LIKES — fetch a api/like.php ─────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.like-btn');
        if (!btn || btn.disabled) return;
        var tipo = btn.dataset.tipo;
        var id   = btn.dataset.id;
        if (!tipo || !id) return;

        btn.disabled = true;
        var isAdmin  = window.location.pathname.indexOf('/admin/') !== -1;
        var apiUrl   = (isAdmin ? '../' : '') + 'api/like.php';

        fetch(apiUrl, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ tipo: tipo, id: parseInt(id) }),
        })
        .then(function (res) {
            if (res.status === 401) { window.location.href = 'login.php'; return null; }
            return res.json();
        })
        .then(function (data) {
            if (!data || data.error) return;
            var countEl = btn.querySelector('.like-count');
            var icon    = btn.querySelector('i');
            if (data.liked) {
                btn.classList.add('liked');
                if (icon) icon.className = 'bi bi-heart-fill me-1';
            } else {
                btn.classList.remove('liked');
                if (icon) icon.className = 'bi bi-heart me-1';
            }
            if (countEl) countEl.textContent = data.total;
        })
        .catch(function () {})
        .finally(function () { btn.disabled = false; });
    });

    // ─── PREVIEW DE AVATAR ────────────────────────────────────────────────
    var avatarInput = document.getElementById('avatar');
    if (avatarInput) {
        avatarInput.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            var preview     = document.getElementById('avatarPreview');
            var placeholder = document.getElementById('avatarPlaceholder');
            var reader      = new FileReader();
            reader.onload = function (e) {
                if (preview) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                }
                if (placeholder) placeholder.style.display = 'none';
            };
            reader.readAsDataURL(this.files[0]);
        });
    }

    // ─── VALIDACIÓN — loginForm ───────────────────────────────────────────
    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            var email = document.getElementById('email').value.trim();
            var pass  = document.getElementById('password').value;
            if (!email || !pass) { e.preventDefault(); mostrarAlerta('Por favor completa todos los campos.'); }
        });
    }

    // ─── VALIDACIÓN — registerForm ────────────────────────────────────────
    var regForm = document.getElementById('registerForm');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            var u  = document.getElementById('username').value.trim();
            var em = document.getElementById('email').value.trim();
            var p  = document.getElementById('password').value;
            var pc = document.getElementById('password_confirm').value;
            var t  = document.getElementById('terminos').checked;
            var er = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            var ur = /^[a-zA-Z0-9_\-]+$/;
            if (!u || !em || !p || !pc)       { e.preventDefault(); return mostrarAlerta('Por favor completa todos los campos.'); }
            if (u.length < 3 || u.length > 50){ e.preventDefault(); return mostrarAlerta('El usuario debe tener entre 3 y 50 caracteres.'); }
            if (!ur.test(u))                  { e.preventDefault(); return mostrarAlerta('El usuario solo puede contener letras, números, _ y -.'); }
            if (!er.test(em))                 { e.preventDefault(); return mostrarAlerta('El formato del correo no es válido.'); }
            if (p.length < 8)                 { e.preventDefault(); return mostrarAlerta('La contraseña debe tener al menos 8 caracteres.'); }
            if (p !== pc)                     { e.preventDefault(); return mostrarAlerta('Las contraseñas no coinciden.'); }
            if (!t)                           { e.preventDefault(); return mostrarAlerta('Debes aceptar los términos y condiciones.'); }
        });
    }

    // ─── VALIDACIÓN — newThreadForm / editThreadForm ──────────────────────
    ['newThreadForm', 'editThreadForm'].forEach(function (id) {
        var f = document.getElementById(id);
        if (!f) return;
        f.addEventListener('submit', function (e) {
            var sec = document.querySelector('input[name="seccion_id"]:checked');
            var tit = document.getElementById('titulo').value.trim();
            var con = document.getElementById('contenido').value.trim();
            if (!sec)           { e.preventDefault(); return mostrarAlerta('Por favor selecciona una sección.'); }
            if (!tit)           { e.preventDefault(); return mostrarAlerta('Por favor escribe un título.'); }
            if (tit.length < 10){ e.preventDefault(); return mostrarAlerta('El título debe tener al menos 10 caracteres.'); }
            if (!con)           { e.preventDefault(); return mostrarAlerta('Por favor escribe el contenido del hilo.'); }
            if (con.length < 20){ e.preventDefault(); return mostrarAlerta('El contenido debe tener al menos 20 caracteres.'); }
            if (con.length > 5000){ e.preventDefault(); return mostrarAlerta('El contenido no puede superar los 5000 caracteres.'); }
        });
    });

    // ─── VALIDACIÓN — editProfileForm ─────────────────────────────────────
    var epf = document.getElementById('editProfileForm');
    if (epf) {
        epf.addEventListener('submit', function (e) {
            var u   = document.getElementById('username').value.trim();
            var em  = document.getElementById('email').value.trim();
            var pn  = document.getElementById('password_new').value;
            var pc  = document.getElementById('password_new_confirm').value;
            var er  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            var ur  = /^[a-zA-Z0-9_\-]+$/;
            if (!u || !em)        { e.preventDefault(); return mostrarAlerta('El nombre y el correo no pueden estar vacíos.'); }
            if (u.length<3||u.length>50){ e.preventDefault(); return mostrarAlerta('El usuario debe tener entre 3 y 50 caracteres.'); }
            if (!ur.test(u))      { e.preventDefault(); return mostrarAlerta('El usuario solo puede contener letras, números, _ y -.'); }
            if (!er.test(em))     { e.preventDefault(); return mostrarAlerta('El formato del correo no es válido.'); }
            if (pn && pn.length<8){ e.preventDefault(); return mostrarAlerta('La nueva contraseña debe tener al menos 8 caracteres.'); }
            if (pn && pn!==pc)    { e.preventDefault(); return mostrarAlerta('Las nuevas contraseñas no coinciden.'); }
        });
    }

}); // fin DOMContentLoaded

// ─── MODAL DE CONFIRMACIÓN PARA ELIMINAR ──────────────────────────────────
// Llamado desde onsubmit="return confirmarEliminar(event, 'nombre')"
function confirmarEliminar(e, nombre) {
    e.preventDefault();
    var form  = e.currentTarget;
    var modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.75);display:flex;' +
                          'align-items:center;justify-content:center;z-index:9999;font-family:var(--gb-font);';
    modal.innerHTML =
        '<div style="background:var(--gb-surface);border:1px solid var(--gb-pink);border-radius:4px;' +
        'padding:2rem;max-width:380px;width:90%;">' +
        '<p style="color:var(--gb-text);font-size:.9rem;margin-bottom:.75rem;">¿Eliminar definitivamente?</p>' +
        '<p style="color:var(--gb-text-muted);font-size:.8rem;margin-bottom:1.5rem;">"' + nombre + '"</p>' +
        '<p style="color:var(--gb-pink);font-size:.75rem;margin-bottom:1.5rem;">Esta acción no se puede deshacer.</p>' +
        '<div style="display:flex;gap:.75rem;justify-content:flex-end;">' +
        '<button id="ntCancelBtn" style="background:transparent;border:1px solid var(--gb-border);' +
        'color:var(--gb-text-muted);padding:.4rem 1rem;border-radius:2px;cursor:pointer;' +
        'font-family:var(--gb-font);font-size:.78rem;text-transform:uppercase;letter-spacing:1px;">Cancelar</button>' +
        '<button id="ntConfirmBtn" style="background:var(--gb-pink-dim);border:1px solid var(--gb-pink);' +
        'color:var(--gb-pink);padding:.4rem 1rem;border-radius:2px;cursor:pointer;' +
        'font-family:var(--gb-font);font-size:.78rem;text-transform:uppercase;letter-spacing:1px;">Eliminar</button>' +
        '</div></div>';
    document.body.appendChild(modal);
    document.getElementById('ntCancelBtn').onclick  = function () { modal.remove(); };
    document.getElementById('ntConfirmBtn').onclick = function () { modal.remove(); form.submit(); };
    modal.addEventListener('click', function (ev) { if (ev.target === modal) modal.remove(); });
    return false;
}

// ─── MOSTRAR ALERTA INLINE ────────────────────────────────────────────────
function mostrarAlerta(msg) {
    var existing = document.querySelector('.js-alerta-nt');
    if (existing) existing.remove();
    var a = document.createElement('div');
    a.className = 'alert-glitch alert-glitch-error js-alerta-nt mb-3';
    a.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + msg;
    var form = document.querySelector('form');
    if (form) form.insertBefore(a, form.firstChild);
    a.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
