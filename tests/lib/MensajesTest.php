<?php

use PHPUnit\Framework\TestCase;

/**
 * Cubre el puente entre el &error=/&exito= de la URL y la sesión.
 *
 * Es un puente de ocho líneas, pero su ausencia dejaba mudas todas las
 * acciones del Kanban y del cierre: el controlador redirigía con el motivo
 * en la URL y la plantilla solo miraba $_SESSION, así que el gestor pulsaba
 * "Rechazar solución", la pantalla se recargaba igual que estaba, y no había
 * manera de saber si había funcionado.
 */
final class MensajesTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = [];
        $_SESSION = [];
    }

    public function testElErrorDeLaUrlLlegaALaSesion(): void
    {
        $_GET['error'] = 'La solución no puede rechazarse sin motivo.';

        mensajes_de_la_url();

        $this->assertSame('La solución no puede rechazarse sin motivo.', $_SESSION['error']);
    }

    public function testElExitoNumericoSeTraduceAUnTextoLegible(): void
    {
        // Varias acciones redirigen con "exito=1", que es una señal interna.
        // Enseñarle un "1" al usuario no es enseñarle nada.
        $_GET['exito'] = '1';

        mensajes_de_la_url();

        $this->assertSame('Cambio guardado correctamente.', $_SESSION['exito']);
    }

    public function testUnMensajeDeExitoConTextoSeRespeta(): void
    {
        $_GET['exito'] = 'Reporte cerrado correctamente';

        mensajes_de_la_url();

        $this->assertSame('Reporte cerrado correctamente', $_SESSION['exito']);
    }

    public function testNoPisaUnMensajeQueYaVeniaEnLaSesion(): void
    {
        // El de la sesión lo puso la acción que acaba de ejecutarse; el de la
        // URL puede ser un resto de una navegación anterior.
        $_SESSION['error'] = 'El de la acción real';
        $_GET['error']     = 'El de la URL';

        mensajes_de_la_url();

        $this->assertSame('El de la acción real', $_SESSION['error']);
    }

    public function testSinParametrosNoInventaMensajes(): void
    {
        mensajes_de_la_url();

        $this->assertArrayNotHasKey('error', $_SESSION);
        $this->assertArrayNotHasKey('exito', $_SESSION);
    }

    public function testUnMensajeEnormeSeRecorta(): void
    {
        // La URL la controla quien la escribe: sin tope, cualquiera podría
        // inyectar un texto kilométrico en la pantalla de otro usuario.
        $_GET['error'] = str_repeat('a', 2000);

        mensajes_de_la_url();

        $this->assertSame(500, mb_strlen($_SESSION['error']));
    }
}
