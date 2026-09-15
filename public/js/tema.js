/**
 * Selector de tema claro/oscuro — SIRGDI
 * Por defecto: oscuro. El usuario puede cambiarlo; la elección se guarda en
 * localStorage y se respeta en visitas futuras. Autocontenido: inyecta su
 * propio botón flotante y su propio CSS, así que basta con incluir este
 * script en cualquier página (no depende de estilos_base.css).
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'sirgdi_tema';

    function leerTemaGuardado() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            return null;
        }
    }

    function guardarTema(tema) {
        try {
            localStorage.setItem(STORAGE_KEY, tema);
        } catch (e) {
            /* localStorage no disponible (modo privado, etc.) — el tema no persiste, pero sigue funcionando */
        }
    }

    function aplicarTema(tema) {
        document.documentElement.setAttribute('data-theme', tema === 'light' ? 'light' : 'dark');
    }

    function temaActual() {
        return document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
    }

    // Aplicar de inmediato (por si esta página no incluyó el snippet inline anti-parpadeo en <head>)
    aplicarTema(leerTemaGuardado() === 'light' ? 'light' : 'dark');

    function inyectarEstilosBoton() {
        if (document.getElementById('estilos-boton-tema')) return;
        var estilo = document.createElement('style');
        estilo.id = 'estilos-boton-tema';
        estilo.textContent =
            '.boton-tema-flotante{position:fixed;bottom:22px;right:22px;width:48px;height:48px;' +
            'border-radius:50%;border:1px solid var(--color-border,#3a4552);' +
            'background:var(--color-bg-elevated,#1e242b);color:var(--color-primary,#4da3e0);' +
            'font-size:19px;line-height:1;cursor:pointer;display:flex;align-items:center;' +
            'justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,.35);z-index:99999;' +
            'transition:transform .2s ease,background .2s ease;padding:0;}' +
            '.boton-tema-flotante:hover{transform:scale(1.08);}' +
            '.boton-tema-flotante:active{transform:scale(0.96);}' +
            '@media (max-width:600px){.boton-tema-flotante{bottom:16px;right:16px;width:44px;height:44px;font-size:17px;}}';
        document.head.appendChild(estilo);
    }

    function actualizarIcono(boton, tema) {
        var esOscuro = tema === 'dark';
        boton.innerHTML = esOscuro ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        var etiqueta = esOscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
        boton.setAttribute('aria-label', etiqueta);
        boton.title = etiqueta;
    }

    function alternarTema() {
        var nuevo = temaActual() === 'dark' ? 'light' : 'dark';
        aplicarTema(nuevo);
        guardarTema(nuevo);
        var boton = document.getElementById('boton-tema');
        if (boton) actualizarIcono(boton, nuevo);
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
        actualizarIcono(boton, temaActual());
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', crearBoton);
    } else {
        crearBoton();
    }

    window.SirgdiTema = { alternar: alternarTema, actual: temaActual };
})();
