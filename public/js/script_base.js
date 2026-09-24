// Base JavaScript - ANA

console.log('ANA v2.0 - Asistente de Necesidades de Ambientes Escolares');

// Utility Functions
const ANA = {
    /**
     * Make API call
     */
    api: async function(endpoint, method = 'GET', data = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        };

        if (data) {
            options.body = new URLSearchParams(data).toString();
        }

        try {
            const response = await fetch(endpoint, options);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    /**
     * Show alert message
     */
    alert: function(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;

        const mainContent = document.querySelector('.main-content') || document.body;
        mainContent.insertBefore(alertDiv, mainContent.firstChild);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    },

    /**
     * Format date
     */
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    /**
     * Escape HTML
     */
    escapeHtml: function(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    },

    /**
     * Check if user is authenticated
     */
    isAuthenticated: function() {
        return document.body.classList.contains('authenticated') ||
               document.querySelector('[data-user-id]') !== null;
    }
};

// Log API calls in development
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        console.log('API Call:', args[0]);
        return originalFetch.apply(this, args);
    };
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Alt + L = Logout
    if (e.altKey && e.key === 'l') {
        if (confirm('¿Cerrar sesión?')) {
            window.location.href = '/?controlador=autenticacion&accion=logout';
        }
    }
});

console.log('Base utilities loaded');

/* =====================================================================
 * Protección contra el doble envío
 * =====================================================================
 * Varias acciones (asignar técnico, validar, cerrar, cambiar estado) mandan
 * hasta cinco correos por SMTP dentro de la misma petición. Eso son varios
 * segundos en los que la pantalla no reacciona, y lo natural es volver a
 * pulsar: el resultado eran asignaciones y correos duplicados.
 *
 * Tres decisiones que no son obvias:
 *
 *  - El botón NO se desactiva en el acto, sino en el siguiente ciclo del
 *    navegador. Un botón desactivado no manda su name/value, y varios
 *    formularios de la aplicación distinguen la acción justamente por el
 *    botón pulsado (<button name="accion" value="...">). Desactivarlo antes
 *    de tiempo rompería el envío en lugar de protegerlo.
 *  - Si otra validación ya canceló el envío, aquí no se hace nada: de lo
 *    contrario el formulario quedaría bloqueado para siempre tras un error
 *    de validación.
 *  - Se reactiva sola a los 20 segundos. Un formulario que descarga un
 *    archivo no cambia de página, y sin esto su botón quedaría muerto.
 *
 * Un formulario puede excluirse con data-sin-bloqueo.
 */
(function () {
    'use strict';

    var ESPERA_REACTIVAR = 20000;

    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return;

        var form = e.target;
        if (!form || form.tagName !== 'FORM') return;
        if (form.hasAttribute('data-sin-bloqueo')) return;
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;

        if (form.dataset.enviando === '1') {
            e.preventDefault();
            return;
        }
        form.dataset.enviando = '1';

        var boton = e.submitter
            || form.querySelector('button[type="submit"], input[type="submit"]');
        if (!boton) return;

        var textoOriginal = boton.innerHTML;
        var anchoOriginal = boton.offsetWidth;

        setTimeout(function () {
            // Fijar el ancho evita que el botón se encoja al cambiar el texto
            // y que la fila entera dé un salto.
            if (anchoOriginal) boton.style.minWidth = anchoOriginal + 'px';
            boton.disabled = true;
            boton.setAttribute('aria-busy', 'true');

            if (boton.tagName === 'BUTTON') {
                boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando…';
            }
        }, 0);

        setTimeout(function () {
            form.dataset.enviando = '';
            boton.disabled = false;
            boton.removeAttribute('aria-busy');
            boton.style.minWidth = '';
            if (boton.tagName === 'BUTTON') boton.innerHTML = textoOriginal;
        }, ESPERA_REACTIVAR);
    });
})();
