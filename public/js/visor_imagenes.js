/**
 * Visor de imágenes ampliadas — ANA
 *
 * Convierte en ampliables todos los enlaces con atributo data-visor:
 *   <a href="ruta/imagen.jpg" data-visor="antes" title="Descripción">
 *       <img src="ruta/imagen.jpg" alt="...">
 *   </a>
 *
 * Los enlaces con el mismo valor de data-visor forman un grupo por el que se
 * puede navegar con las flechas. Cierra con Esc, con la X o pulsando fuera.
 * Autocontenido: inyecta su propio CSS y funciona en ambos temas.
 */
(function () {
    'use strict';

    var visor = null, imagen = null, pie = null, contador = null, enlaceAbrir = null, enlaceDescargar = null;
    var grupo = [], indice = 0, ultimoFoco = null;

    function inyectarEstilos() {
        if (document.getElementById('estilos-visor-imagenes')) return;
        var s = document.createElement('style');
        s.id = 'estilos-visor-imagenes';
        s.textContent =
            '.visor-img{position:fixed;inset:0;z-index:100000;display:none;align-items:center;justify-content:center;' +
            'background:rgba(6,10,16,.92);backdrop-filter:blur(4px);padding:56px 72px 76px;box-sizing:border-box;}' +
            '.visor-img.abierto{display:flex;}' +
            '.visor-img img{max-width:100%;max-height:100%;object-fit:contain;border-radius:8px;box-shadow:0 20px 60px rgba(0,0,0,.6);' +
            'background:#111;user-select:none;}' +
            '.visor-img button{position:absolute;border:0;border-radius:50%;width:46px;height:46px;cursor:pointer;' +
            'background:rgba(255,255,255,.12);color:#fff;font-size:20px;display:flex;align-items:center;justify-content:center;' +
            'transition:background .15s;font-family:inherit;}' +
            '.visor-img button:hover{background:rgba(255,255,255,.26);}' +
            '.visor-img button:focus-visible{outline:2px solid #4da3e0;outline-offset:2px;}' +
            '.visor-img .visor-cerrar{top:14px;right:16px;}' +
            '.visor-img .visor-ant{left:14px;top:50%;transform:translateY(-50%);}' +
            '.visor-img .visor-sig{right:14px;top:50%;transform:translateY(-50%);}' +
            '.visor-img .visor-ant[hidden],.visor-img .visor-sig[hidden]{display:none;}' +
            '.visor-img .visor-pie{position:absolute;left:0;right:0;bottom:0;padding:14px 20px;display:flex;align-items:center;' +
            'justify-content:space-between;gap:16px;color:#e8edf2;font-size:13px;background:linear-gradient(transparent,rgba(0,0,0,.6));}' +
            '.visor-img .visor-titulo{flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}' +
            '.visor-img .visor-contador{opacity:.75;font-variant-numeric:tabular-nums;}' +
            '.visor-img .visor-acciones{display:flex;gap:8px;}' +
            '.visor-img .visor-acciones a{color:#fff;text-decoration:none;background:rgba(255,255,255,.12);padding:6px 12px;' +
            'border-radius:16px;font-size:12px;font-weight:600;white-space:nowrap;}' +
            '.visor-img .visor-acciones a:hover{background:rgba(255,255,255,.26);}' +
            '@media (max-width:640px){.visor-img{padding:56px 12px 96px;}.visor-img .visor-ant,.visor-img .visor-sig{top:auto;bottom:52px;transform:none;}' +
            '.visor-img .visor-pie{flex-wrap:wrap;}}' +
            'body.visor-abierto{overflow:hidden;}';
        document.head.appendChild(s);
    }

    function crear() {
        if (visor) return;
        inyectarEstilos();
        visor = document.createElement('div');
        visor.className = 'visor-img';
        visor.setAttribute('role', 'dialog');
        visor.setAttribute('aria-modal', 'true');
        visor.setAttribute('aria-label', 'Imagen ampliada');
        visor.innerHTML =
            '<button type="button" class="visor-cerrar" aria-label="Cerrar (Esc)"><i class="fas fa-xmark"></i></button>' +
            '<button type="button" class="visor-ant" aria-label="Anterior"><i class="fas fa-chevron-left"></i></button>' +
            '<img alt="">' +
            '<button type="button" class="visor-sig" aria-label="Siguiente"><i class="fas fa-chevron-right"></i></button>' +
            '<div class="visor-pie"><span class="visor-titulo"></span><span class="visor-contador"></span>' +
            '<span class="visor-acciones"><a class="visor-abrir" target="_blank" rel="noopener">Abrir original</a>' +
            '<a class="visor-descargar">Descargar</a></span></div>';
        document.body.appendChild(visor);

        imagen = visor.querySelector('img');
        pie = visor.querySelector('.visor-titulo');
        contador = visor.querySelector('.visor-contador');
        enlaceAbrir = visor.querySelector('.visor-abrir');
        enlaceDescargar = visor.querySelector('.visor-descargar');

        visor.querySelector('.visor-cerrar').addEventListener('click', cerrar);
        visor.querySelector('.visor-ant').addEventListener('click', function () { mover(-1); });
        visor.querySelector('.visor-sig').addEventListener('click', function () { mover(1); });
        visor.addEventListener('click', function (e) { if (e.target === visor) cerrar(); });
        document.addEventListener('keydown', function (e) {
            if (!visor.classList.contains('abierto')) return;
            if (e.key === 'Escape') cerrar();
            else if (e.key === 'ArrowLeft') mover(-1);
            else if (e.key === 'ArrowRight') mover(1);
        });
    }

    function mostrar() {
        var a = grupo[indice];
        var src = a.getAttribute('href');
        imagen.src = src;
        imagen.alt = a.getAttribute('title') || '';
        pie.textContent = a.getAttribute('data-titulo') || a.getAttribute('title') || '';
        contador.textContent = grupo.length > 1 ? (indice + 1) + ' / ' + grupo.length : '';
        enlaceAbrir.href = src;
        enlaceDescargar.href = src + (src.indexOf('?') === -1 ? '?' : '&') + 'descargar=1';
        var unica = grupo.length <= 1;
        visor.querySelector('.visor-ant').hidden = unica;
        visor.querySelector('.visor-sig').hidden = unica;
    }

    function abrir(enlace) {
        crear();
        var clave = enlace.getAttribute('data-visor');
        grupo = Array.prototype.slice.call(document.querySelectorAll('a[data-visor="' + clave + '"]'));
        indice = Math.max(0, grupo.indexOf(enlace));
        ultimoFoco = document.activeElement;
        mostrar();
        visor.classList.add('abierto');
        document.body.classList.add('visor-abierto');
        visor.querySelector('.visor-cerrar').focus();
    }

    function mover(paso) {
        if (grupo.length <= 1) return;
        indice = (indice + paso + grupo.length) % grupo.length;
        mostrar();
    }

    function cerrar() {
        if (!visor) return;
        visor.classList.remove('abierto');
        document.body.classList.remove('visor-abierto');
        imagen.src = '';
        if (ultimoFoco && ultimoFoco.focus) ultimoFoco.focus();
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a[data-visor]') : null;
        if (!a) return;
        if (e.ctrlKey || e.metaKey || e.button !== 0) return; // dejar abrir en pestaña nueva si el usuario lo pide
        e.preventDefault();
        abrir(a);
    });

    window.VisorImagenes = { abrir: abrir, cerrar: cerrar };
})();
