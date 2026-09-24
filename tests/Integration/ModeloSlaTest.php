<?php

require_once dirname(__DIR__, 2) . '/app/modelos/modelo_sla.php';

final class ModeloSlaTest extends BaseDbTestCase
{
    private ModeloSLA $modelo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modelo = new ModeloSLA();
    }

    // strtotime('-9.5 hours') no interpreta bien las fracciones de hora (puede
    // devolver incluso una fecha futura) — se calcula el timestamp a mano.
    private function horasAtras(float $horas): string
    {
        return date('Y-m-d H:i:s', (int) round(time() - $horas * 3600));
    }

    private function reporteFalso(float $horasAtras, ?float $horasEnPausa = null, ?int $idCategoria = null): array
    {
        return [
            'id_institucion' => self::ID_INSTITUCION,
            'id_categoria' => $idCategoria ?? self::ID_CATEGORIA,
            'id_urgencia_calculada' => URGENCIA_MODERADO,
            'fecha_hora_registro' => $this->horasAtras($horasAtras),
            'fecha_pausa_sla' => $horasEnPausa !== null ? $this->horasAtras($horasEnPausa) : null,
        ];
    }

    public function testUsaElFallbackDe48HorasCuandoNoHaySlaConfigurado(): void
    {
        // La categoría 99999 no tiene SLA, pero puede haberlo por urgencia: los
        // datos semilla del repositorio traen uno de 72 horas para Moderado.
        // Se desactivan aquí dentro, en la transacción de la prueba, para que
        // el fallback se ejercite de verdad.
        //
        // Antes esta prueba daba por hecho que no existía ninguno, lo cual era
        // cierto en la base de este equipo y falso en una recién instalada.
        $this->bd->ejecutar(
            'UPDATE sla SET activo = 0 WHERE id_institucion = ? AND id_urgencia = ?',
            [self::ID_INSTITUCION, URGENCIA_MODERADO]
        );

        $reporte = $this->reporteFalso('0.1', null, 99999);

        $resultado = $this->modelo->calcular_vencimiento($reporte);

        $this->assertSame(48, $resultado['horas_slaurado']);
        $this->assertSame('en_tiempo', $resultado['estado_sla']);
    }

    public function testUsaElSlaConfiguradoPorCategoriaEnVezDelFallback(): void
    {
        $this->modelo->crear([
            'id_institucion' => self::ID_INSTITUCION,
            'id_categoria' => self::ID_CATEGORIA,
            'id_urgencia' => URGENCIA_MODERADO,
            'tiempo_respuesta_horas' => 2,
            'tiempo_resolucion_horas' => 10,
        ]);

        $reporte = $this->reporteFalso('0.1');
        $resultado = $this->modelo->calcular_vencimiento($reporte);

        $this->assertSame(10, $resultado['horas_slaurado']);
        $this->assertSame('en_tiempo', $resultado['estado_sla']);
    }

    public function testMarcaVencidoCuandoYaSePasoElTiempoDeResolucion(): void
    {
        $this->modelo->crear([
            'id_institucion' => self::ID_INSTITUCION,
            'id_categoria' => self::ID_CATEGORIA,
            'id_urgencia' => URGENCIA_MODERADO,
            'tiempo_respuesta_horas' => 2,
            'tiempo_resolucion_horas' => 10,
        ]);

        // Registrado hace 50h con SLA de 10h: vencido hace 40h
        $reporte = $this->reporteFalso('50');
        $resultado = $this->modelo->calcular_vencimiento($reporte);

        $this->assertSame('vencido', $resultado['estado_sla']);
        $this->assertLessThan(0, $resultado['horas_restantes']);
    }

    public function testMarcaCercaCuandoQuedaMenosDeUnaHora(): void
    {
        // RN-13: alerta cuando falta <1h
        $this->modelo->crear([
            'id_institucion' => self::ID_INSTITUCION,
            'id_categoria' => self::ID_CATEGORIA,
            'id_urgencia' => URGENCIA_MODERADO,
            'tiempo_respuesta_horas' => 2,
            'tiempo_resolucion_horas' => 10,
        ]);

        // Registrado hace 9h30min con SLA de 10h: quedan 30 min
        $reporte = $this->reporteFalso('9.5');
        $resultado = $this->modelo->calcular_vencimiento($reporte);

        $this->assertSame('cerca', $resultado['estado_sla']);
    }

    public function testDescuentaElTiempoEnPausaDelCalculo(): void
    {
        // RN-10: el tiempo en pausa (estado Devuelto) no debe contar como
        // tiempo transcurrido del SLA. Cubre directamente el bug de
        // marcar_solucionado() corregido esta misma sesión.
        $this->modelo->crear([
            'id_institucion' => self::ID_INSTITUCION,
            'id_categoria' => self::ID_CATEGORIA,
            'id_urgencia' => URGENCIA_MODERADO,
            'tiempo_respuesta_horas' => 2,
            'tiempo_resolucion_horas' => 10,
        ]);

        // Registrado hace 5h, pero en pausa desde hace 3h: solo 2h deberían
        // contar como transcurridas, no las 5h completas.
        $reporte = $this->reporteFalso('5', '3');
        $resultado = $this->modelo->calcular_vencimiento($reporte);

        $this->assertEqualsWithDelta(2.0, $resultado['horas_transcurridas'], 0.1);
        $this->assertSame('en_tiempo', $resultado['estado_sla']);
    }
}
