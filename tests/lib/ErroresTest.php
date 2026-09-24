<?php

use PHPUnit\Framework\TestCase;

/**
 * Cubre las dos decisiones de la página de error que pueden romperse sin que
 * se note: a quién se le responde JSON en vez de HTML, y qué enlace de vuelta
 * se considera seguro.
 *
 * responder_error() no se prueba aquí porque termina el proceso con exit.
 */
final class ErroresTest extends TestCase
{
    private array $servidor_original;

    protected function setUp(): void
    {
        $this->servidor_original = $_SERVER;
        unset(
            $_SERVER['HTTP_X_REQUESTED_WITH'],
            $_SERVER['HTTP_ACCEPT'],
            $_SERVER['HTTP_REFERER'],
            $_SERVER['HTTP_HOST'],
            $_SERVER['REQUEST_URI']
        );
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->servidor_original;
    }

    // --- error_espera_json ---

    public function testUnaPeticionDeNavegadorRecibeHtml(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';

        $this->assertFalse(error_espera_json());
    }

    public function testUnaLlamadaXhrRecibeJson(): void
    {
        // Devolver una página HTML a un fetch() la mete dentro de un
        // JSON.parse() y produce un fallo peor que el original.
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $this->assertTrue(error_espera_json());
    }

    public function testUnClienteQueSoloAceptaJsonRecibeJson(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $this->assertTrue(error_espera_json());
    }

    public function testUnNavegadorQueAceptaAmbosRecibeHtml(): void
    {
        // Algunos navegadores anuncian application/json en la lista. Si hay
        // text/html, hay una persona mirando.
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/json;q=0.9';

        $this->assertFalse(error_espera_json());
    }

    public function testUnEndpointJsonRecibeJsonAunqueElNavegadorPidaHtml(): void
    {
        // Los fetch() de la aplicación no mandan X-Requested-With y dejan el
        // Accept por defecto. Sin esta regla, un error en uno de estos
        // endpoints devolvería HTML y reventaría dentro de JSON.parse().
        $_SERVER['HTTP_ACCEPT'] = 'text/html,*/*';
        $_GET['accion'] = 'cargar_subcategorias_publico_json';

        $this->assertTrue(error_espera_json());

        unset($_GET['accion']);
    }

    // --- error_enlace_volver ---

    public function testSinReferenteNoHayEnlaceDeVuelta(): void
    {
        $_SERVER['HTTP_HOST'] = 'mto.jlcserviciosintegrales.com';

        $this->assertNull(error_enlace_volver());
    }

    public function testSeAceptaElReferenteDelPropioSitio(): void
    {
        $_SERVER['HTTP_HOST']    = 'mto.jlcserviciosintegrales.com';
        $_SERVER['REQUEST_URI']  = '/?controlador=cierre&accion=cerrar_reporte';
        $_SERVER['HTTP_REFERER'] = 'https://mto.jlcserviciosintegrales.com/?controlador=gestion&accion=kanban';

        $this->assertSame(
            'https://mto.jlcserviciosintegrales.com/?controlador=gestion&accion=kanban',
            error_enlace_volver()
        );
    }

    public function testSeRechazaUnReferenteDeOtroDominio(): void
    {
        // Pintar ese enlace convertiría la página de error en un redirector
        // abierto hacia donde quiera quien manda el Referer.
        $_SERVER['HTTP_HOST']    = 'mto.jlcserviciosintegrales.com';
        $_SERVER['REQUEST_URI']  = '/?controlador=gestion';
        $_SERVER['HTTP_REFERER'] = 'https://sitio-de-phishing.example/entrar';

        $this->assertNull(error_enlace_volver());
    }

    public function testNoSeOfreceVolverALaMismaPaginaQueFallo(): void
    {
        // Pulsarlo repetiría el error, que es la definición de callejón sin
        // salida.
        $_SERVER['HTTP_HOST']    = 'mto.jlcserviciosintegrales.com';
        $_SERVER['REQUEST_URI']  = '/?controlador=cierre&accion=cerrar_reporte&id=7';
        $_SERVER['HTTP_REFERER'] = 'https://mto.jlcserviciosintegrales.com/?controlador=cierre&accion=cerrar_reporte&id=7';

        $this->assertNull(error_enlace_volver());
    }

    public function testElPuertoNoRompeLaComparacionDeDominio(): void
    {
        // En local el host llega como "localhost" y el Referer también, pero
        // con puerto la comparación literal fallaría y nadie tendría botón de
        // volver durante el desarrollo.
        $_SERVER['HTTP_HOST']    = 'localhost:8080';
        $_SERVER['REQUEST_URI']  = '/reporte_danos/public/?controlador=gestion';
        $_SERVER['HTTP_REFERER'] = 'http://localhost:8080/reporte_danos/public/?controlador=dashboard';

        $this->assertSame(
            'http://localhost:8080/reporte_danos/public/?controlador=dashboard',
            error_enlace_volver()
        );
    }

    // --- error_referencia ---

    public function testLaReferenciaEsCortaYDistintaCadaVez(): void
    {
        // Es lo que el usuario dicta por teléfono al pedir ayuda: tiene que
        // caber en una frase y no repetirse entre dos incidentes.
        $a = error_referencia();
        $b = error_referencia();

        $this->assertSame(8, strlen($a));
        $this->assertMatchesRegularExpression('/^[0-9A-F]{8}$/', $a);
        $this->assertNotSame($a, $b);
    }
}
