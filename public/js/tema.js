/**
 * Selector de tema claro/oscuro — SIRGDI
 *
 * Orden de prioridad para decidir el tema:
 *   1. La elección explícita del usuario (guardada en localStorage).
 *   2. La preferencia del sistema operativo (prefers-color-scheme).
 *   3. Oscuro, que es el tema por defecto del producto.
 *
 * Mientras el usuario no elija manualmente, la aplicación queda en modo
 * automático y sigue los cambios del sistema operativo en vivo (por ejemplo
 * cuando Windows cambia a modo oscuro al anochecer).
 *
 * Es autocontenido: inyecta su propio botón flotante y su propio CSS, así que
 * basta con incluir este script en cualquier página.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'sirgdi_tema';
    var CLASE_TRANSICION = 'tema-transicion';
    var CONSULTA_CLARO = '(prefers-color-scheme: light)';

    function soportaMatchMedia() {
        return typeof window.matchMedia === 'function';
    }

    /** Devuelve 'light' o 'dark' solo si el usuario eligió explícitamente. */
    function leerEleccionUsuario() {
        try {
            var valor = localStorage.getItem(STORAGE_KEY);
            return (valor === 'light' || valor === 'dark') ? valor : null;
        } catch (e) {
            return null;
        }
    }

    function guardarEleccionUsuario(tema) {
        try {
            localStorage.setItem(STORAGE_KEY, tema);
        } catch (e) {
            /* localStorage bloqueado (modo privado): el tema no persiste
               entre visitas, pero el cambio sigue funcionando en esta. */
        }
    }

    /** Tema que pide el sistema operativo. Oscuro salvo que pida claro. */
    function temaDelSistema() {
        if (soportaMatchMedia() && window.matchMedia(CONSULTA_CLARO).matches) {
            return 'light';
        }
        return 'dark';
    }

    function temaEfectivo() {
        return leerEleccionUsuario() || temaDelSistema();
    }

    function temaActual() {
        return document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
    }

    /**
     * Aplica el tema. Con animar=true se activa una transición breve de color;
     * en la carga inicial se omite para que la página no "parpadee" de claro
     * a oscuro delante del usuario.
     */
    function aplicarTema(tema, animar) {
        var raiz = document.documentElement;
        var normalizado = (tema === 'light') ? 'light' : 'dark';

        if (animar) {
            raiz.classList.add(CLASE_TRANSICION);
            window.setTimeout(function () {
                raiz.classList.remove(CLASE_TRANSICION);
            }, 320);
        }

        raiz.setAttribute('data-theme', normalizado);
        actualizarBoton(normalizado);
    }

    // Se aplica de inmediato por si esta página no incluyó el snippet
    // anti-parpadeo en el <head>.
    aplicarTema(temaEfectivo(), false);

    // Mientras no haya elección manual, seguimos al sistema operativo en vivo.
    if (soportaMatchMedia()) {
        var consulta = window.matchMedia(CONSULTA_CLARO);
        var alCambiarSistema = function () {
            if (!leerEleccionUsuario()) {
                aplicarTema(temaDelSistema(), true);
            }
        };
        if (typeof consulta.addEventListener === 'function') {
            consulta.addEventListener('change', alCambiarSistema);
        } else if (typeof consulta.addListener === 'function') {
            consulta.addListener(alCambiarSistema); // Safari antiguo
        }
    }

    function inyectarEstilosBoton() {
        if (document.getElementById('estilos-boton-tema')) return;
        var estilo = document.createElement('style');
        estilo.id = 'estilos-boton-tema';
        estilo.textContent =
            '.boton-tema-flotante{position:fixed;bottom:22px;right:22px;width:48px;height:48px;' +
            'border-radius:50%;border:1px solid var(--border-color,#3a4757);' +
            'background:var(--bg-elevated,#1a212b);color:var(--accent,#4da3e0);' +
            'font-size:19px;line-height:1;cursor:pointer;display:flex;align-items:center;' +
            'justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,.35);z-index:99999;' +
            'transition:transform .2s ease,background-color .2s ease,color .2s ease;padding:0;}' +
            '.boton-tema-flotante:hover{transform:scale(1.08);}' +
            '.boton-tema-flotante:active{transform:scale(0.96);}' +
            '.boton-tema-flotante:focus-visible{outline:2px solid var(--accent,#4da3e0);outline-offset:3px;}' +
            '@media (prefers-reduced-motion: reduce){.boton-tema-flotante{transition:none;}' +
            '.boton-tema-flotante:hover,.boton-tema-flotante:active{transform:none;}}' +
            '@media (max-width:600px){.boton-tema-flotante{bottom:16px;right:16px;width:44px;height:44px;font-size:17px;}}';
        document.head.appendChild(estilo);
    }

    function actualizarBoton(tema) {
        var boton = document.getElementById('boton-tema');
        if (!boton) return;

        var esOscuro = tema === 'dark';
        boton.innerHTML = esOscuro ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';

        var etiqueta = esOscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
        if (!leerEleccionUsuario()) {
            etiqueta += ' (ahora sigue al sistema)';
        }
        boton.setAttribute('aria-label', etiqueta);
        boton.setAttribute('aria-pressed', esOscuro ? 'true' : 'false');
        boton.title = etiqueta;
    }

    function alternarTema() {
        var nuevo = temaActual() === 'dark' ? 'light' : 'dark';
        guardarEleccionUsuario(nuevo);
        aplicarTema(nuevo, true);
    }

    /** Vuelve a seguir la preferencia del sistema operativo. */
    function usarAutomatico() {
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch (e) {
            /* sin persistencia disponible */
        }
        aplicarTema(temaDelSistema(), true);
    }

    function crearBoton() {
        if (document.getElementById('boton-tema')) return;
        inyectarEstilosBoton();
        var boton = document.createElement('button');
        boton.id = 'boton-tema';
        boton.type = 'button';
        boton.className = 'boton-tema-flotante';
        boton.addEventListener('click', alternarTema);
        document.body.appendChild(boton);
        actualizarBoton(temaActual());
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', crearBoton);
    } else {
        crearBoton();
    }

    window.SirgdiTema = {
        alternar: alternarTema,
        actual: temaActual,
        usarAutomatico: usarAutomatico,
        esAutomatico: function () { return !leerEleccionUsuario(); }
    };
})();
