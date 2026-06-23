/**
 * Sistema de Notificaciones Toast
 * Proporciona feedback visual al usuario con mensajes emergentes
 */

class Toast {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        // Crear contenedor de toasts si no existe
        if (!document.querySelector('.toast-container')) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        } else {
            this.container = document.querySelector('.toast-container');
        }
    }

    /**
     * Mostrar notificación Toast
     * @param {string} tipo - Tipo: 'success', 'error', 'warning', 'info'
     * @param {string} titulo - Título del mensaje
     * @param {string} mensaje - Mensaje detallado
     * @param {number} duracion - Duración en milisegundos (default: 4000)
     */
    show(tipo = 'info', titulo = '', mensaje = '', duracion = 4000) {
        const toastEl = document.createElement('div');
        toastEl.className = `toast toast-${tipo}`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'polite');

        // Mapeo de iconos
        const iconos = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const icono = iconos[tipo] || iconos.info;

        // HTML del Toast
        toastEl.innerHTML = `
            <div class="toast-icon">
                <i class="fas ${icono}"></i>
            </div>
            <div class="toast-content">
                <p class="toast-title">${this.sanitize(titulo)}</p>
                ${mensaje ? `<p class="toast-message">${this.sanitize(mensaje)}</p>` : ''}
            </div>
            <button class="toast-close" type="button" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
            <div class="toast-progress" style="animation: progress ${duracion}ms linear forwards;"></div>
        `;

        // Agregar evento al botón cerrar
        toastEl.querySelector('.toast-close').addEventListener('click', () => {
            this.remove(toastEl);
        });

        // Agregar al contenedor
        this.container.appendChild(toastEl);

        // Auto-remover después de la duración
        setTimeout(() => {
            this.remove(toastEl);
        }, duracion);

        return toastEl;
    }

    /**
     * Mostrar notificación de éxito
     */
    success(titulo, mensaje = '', duracion = 4000) {
        return this.show('success', titulo, mensaje, duracion);
    }

    /**
     * Mostrar notificación de error
     */
    error(titulo, mensaje = '', duracion = 5000) {
        return this.show('error', titulo, mensaje, duracion);
    }

    /**
     * Mostrar notificación de advertencia
     */
    warning(titulo, mensaje = '', duracion = 4000) {
        return this.show('warning', titulo, mensaje, duracion);
    }

    /**
     * Mostrar notificación de información
     */
    info(titulo, mensaje = '', duracion = 4000) {
        return this.show('info', titulo, mensaje, duracion);
    }

    /**
     * Remover toast con animación
     */
    remove(toastEl) {
        if (!toastEl || !toastEl.parentElement) return;

        toastEl.classList.add('toast-exit');
        setTimeout(() => {
            if (toastEl.parentElement) {
                toastEl.remove();
            }
        }, 300);
    }

    /**
     * Sanitizar HTML para evitar XSS
     */
    sanitize(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Remover todos los toasts
     */
    clearAll() {
        const toasts = this.container.querySelectorAll('.toast');
        toasts.forEach(toast => this.remove(toast));
    }
}

// Crear instancia global
const toast = new Toast();

// Agregar estilos de animación si no existen
if (!document.querySelector('style[data-toast]')) {
    const style = document.createElement('style');
    style.setAttribute('data-toast', 'true');
    style.textContent = `
        @keyframes progress {
            from { width: 100%; }
            to { width: 0%; }
        }
    `;
    document.head.appendChild(style);
}

// Exposer globalmente para uso en HTML inline
window.showToast = {
    success: (titulo, mensaje) => toast.success(titulo, mensaje),
    error: (titulo, mensaje) => toast.error(titulo, mensaje),
    warning: (titulo, mensaje) => toast.warning(titulo, mensaje),
    info: (titulo, mensaje) => toast.info(titulo, mensaje)
};
