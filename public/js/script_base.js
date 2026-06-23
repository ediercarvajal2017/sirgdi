// Base JavaScript - SIRGDI

console.log('SIRGDI v2.0 - Sistema de Reportes de Daños');

// Utility Functions
const SIRGDI = {
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
