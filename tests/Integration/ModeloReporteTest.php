<?php

require_once dirname(__DIR__, 2) . '/app/modelos/modelo_reporte.php';
require_once dirname(__DIR__, 2) . '/app/modelos/modelo_categoria.php';

final class ModeloReporteTest extends BaseDbTestCase
{
    private ModeloReporte $modelo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modelo = new ModeloReporte();
    }

    public function testCrearGeneraNumeroDeTicketConElFormatoEsperado(): void
    {
        $id = $this->modelo->crear($this->datosReporteValido());
        $reporte = $this->modelo->obtener_por_id($id, self::ID_INSTITUCION);

        $this->assertNotFalse($reporte);
        $this->assertMatchesRegularExpression('/^SIR-\d{4}\d{5}$/', $reporte['numero_ticket']);
    }

    public function testCrearAsignaEstadoInicialRegistrado(): void
    {
        $id = $this->modelo->crear($this->datosReporteValido());
        $reporte = $this->modelo->obtener_por_id($id, self::ID_INSTITUCION);

        $this->assertSame(ESTADO_REGISTRADO, (int) $reporte['id_estado']);
    }

    public function testCrearGeneraUnTokenDeSeguimientoValido(): void
    {
        $id = $this->modelo->crear($this->datosReporteValido());
        $reporte = $this->modelo->obtener_por_id($id, self::ID_INSTITUCION);

        $this->assertTrue(Validacion::validar_uuid($reporte['token_seguimiento_publico']));
    }

    public function testCrearFallaSiFaltaUnCampoObligatorio(): void
    {
        $this->expectException(Exception::class);
        $this->modelo->crear($this->datosReporteValido(['descripcion_problema' => '']));
    }

    public function testCrearFallaSiNoHayUbicacionNiReferenciaLibre(): void
    {
        $this->expectException(Exception::class);
        $this->modelo->crear($this->datosReporteValido([
            'id_area' => null,
            'referencia_ubicacion_libre' => null,
        ]));
    }

    public function testCambiarEstadoPermiteTransicionValida(): void
    {
        // RN-12: Registrado -> En Proceso es una transición permitida
        $id = $this->modelo->crear($this->datosReporteValido());

        $this->modelo->cambiar_estado($id, self::ID_INSTITUCION, ESTADO_EN_PROCESO, '', self::ID_USUARIO);
        $reporte = $this->modelo->obtener_por_id($id, self::ID_INSTITUCION);

        $this->assertSame(ESTADO_EN_PROCESO, (int) $reporte['id_estado']);
    }

    public function testCambiarEstadoRechazaTransicionNoPermitida(): void
    {
        // RN-12: Registrado -> Cerrado NO está en TRANSICIONES_ESTADO_REPORTE
        $id = $this->modelo->crear($this->datosReporteValido());

        $this->expectException(Exception::class);
        $this->modelo->cambiar_estado($id, self::ID_INSTITUCION, ESTADO_CERRADO);
    }

    public function testCambiarEstadoRechazaDesdeUnEstadoTerminal(): void
    {
        // RN-12: Cerrado (7) y Anulado (8) son terminales, sin transiciones de salida
        $id = $this->modelo->crear($this->datosReporteValido());
        $this->modelo->cambiar_estado($id, self::ID_INSTITUCION, ESTADO_EN_PROCESO, '', self::ID_USUARIO);
        $this->modelo->cambiar_estado($id, self::ID_INSTITUCION, ESTADO_ANULADO, '', self::ID_USUARIO);

        $this->expectException(Exception::class);
        $this->modelo->cambiar_estado($id, self::ID_INSTITUCION, ESTADO_EN_PROCESO, '', self::ID_USUARIO);
    }

    public function testPausarSlaRegistraFechaDePausa(): void
    {
        $id = $this->modelo->crear($this->datosReporteValido());
        $this->modelo->pausar_sla($id, self::ID_INSTITUCION);

        $reporte = $this->modelo->obtener_por_id($id, self::ID_INSTITUCION);
        $this->assertNotNull($reporte['fecha_pausa_sla']);
    }

    public function testReanudarSlaLimpiaFechaDePausa(): void
    {
        $id = $this->modelo->crear($this->datosReporteValido());
        $this->modelo->pausar_sla($id, self::ID_INSTITUCION);
        $this->modelo->reanudar_sla($id, self::ID_INSTITUCION);

        $reporte = $this->modelo->obtener_por_id($id, self::ID_INSTITUCION);
        $this->assertNull($reporte['fecha_pausa_sla']);
    }

    public function testObtenerPorIdRespetaAislamientoMultitenant(): void
    {
        // RN-01: un reporte de la institución 1 no debe verse consultando con otra institución
        $id = $this->modelo->crear($this->datosReporteValido());

        $resultado = $this->modelo->obtener_por_id($id, self::ID_INSTITUCION + 999);
        $this->assertFalse($resultado);
    }
}
