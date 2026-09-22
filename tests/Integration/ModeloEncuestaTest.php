<?php

require_once dirname(__DIR__, 2) . '/app/modelos/modelo_encuesta.php';
require_once dirname(__DIR__, 2) . '/app/modelos/modelo_reporte.php';

final class ModeloEncuestaTest extends BaseDbTestCase
{
    private ModeloEncuesta $modelo;
    private int $idReporte;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modelo = new ModeloEncuesta();
        $this->idReporte = (new ModeloReporte())->crear($this->datosReporteValido());
    }

    public function testCrearQuedaSinResponderPorDefecto(): void
    {
        $id = $this->modelo->crear($this->idReporte, self::ID_INSTITUCION, null);
        $encuesta = $this->modelo->obtener_por_id($id);

        $this->assertSame(0, (int) $encuesta['fue_respondida']);
        $this->assertNull($encuesta['puntuacion']);
    }

    public function testCrearAceptaReportanteInvitadoSinCuenta(): void
    {
        // RF-22: id_usuario_reportante es nullable para invitados (fix de esta sesión)
        $id = $this->modelo->crear($this->idReporte, self::ID_INSTITUCION, null);
        $encuesta = $this->modelo->obtener_por_id($id);

        $this->assertNull($encuesta['id_usuario_reportante']);
    }

    public function testRegistrarRespuestaGuardaLaPuntuacionYElComentario(): void
    {
        $id = $this->modelo->crear($this->idReporte, self::ID_INSTITUCION, null);

        $filas = $this->modelo->registrar_respuesta($id, 5, 'Excelente atención');

        $this->assertSame(1, $filas);
        $encuesta = $this->modelo->obtener_por_id($id);
        $this->assertSame(5, (int) $encuesta['puntuacion']);
        $this->assertSame('Excelente atención', $encuesta['comentario']);
        $this->assertSame(1, (int) $encuesta['fue_respondida']);
    }

    public function testRegistrarRespuestaEsIdempotenteAnteDobleEnvio(): void
    {
        // Condición de carrera corregida esta sesión: el segundo envío (doble
        // clic, doble pestaña) no debe pisar la respuesta ya guardada.
        $id = $this->modelo->crear($this->idReporte, self::ID_INSTITUCION, null);

        $primerEnvio = $this->modelo->registrar_respuesta($id, 5, 'Primera respuesta');
        $segundoEnvio = $this->modelo->registrar_respuesta($id, 1, 'Segunda respuesta (no debería aplicar)');

        $this->assertSame(1, $primerEnvio);
        $this->assertSame(0, $segundoEnvio, 'El segundo envío no debe afectar ninguna fila.');

        $encuesta = $this->modelo->obtener_por_id($id);
        $this->assertSame(5, (int) $encuesta['puntuacion']);
        $this->assertSame('Primera respuesta', $encuesta['comentario']);
    }
}
