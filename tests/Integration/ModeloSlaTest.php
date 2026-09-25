<?php

require_once dirname(__DIR__, 2) . '/app/modelos/modelo_sla.php';
require_once dirname(__DIR__, 2) . '/app/modelos/modelo_reporte.php';

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

    /**
     * Con el horario de 24 horas: estas pruebas verifican qué SLA se elige y
     * cómo se descuenta la pausa, y no pueden depender del día y la hora en
     * que se ejecutan. El horario laboral tiene sus propias pruebas.
     */
    private function calcular(array $reporte): array
    {
        return $this->modelo->calcular_vencimiento($reporte, null, null, HorarioLaboral::siempre());
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

        $resultado = $this->calcular($reporte);

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
        $resultado = $this->calcular($reporte);

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
        $resultado = $this->calcular($reporte);

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
        $resultado = $this->calcular($reporte);

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
        $resultado = $this->calcular($reporte);

        $this->assertEqualsWithDelta(2.0, $resultado['horas_transcurridas'], 0.1);
        $this->assertSame('en_tiempo', $resultado['estado_sla']);
    }

    // ------------------------------------------------------ horas hábiles

    /** SLA de 8 h para la categoría de prueba, y un reporte con fecha fija. */
    private function reporteConSla8h(string $registro, ?string $pausa = null): array
    {
        $this->modelo->crear([
            'id_institucion' => self::ID_INSTITUCION,
            'id_categoria' => self::ID_CATEGORIA,
            'id_urgencia' => URGENCIA_URGENTE,
            'tiempo_respuesta_horas' => 1,
            'tiempo_resolucion_horas' => 8,
        ]);

        return [
            'id_institucion' => self::ID_INSTITUCION,
            'id_categoria' => self::ID_CATEGORIA,
            'id_urgencia_calculada' => URGENCIA_URGENTE,
            'fecha_hora_registro' => $registro,
            'fecha_pausa_sla' => $pausa,
        ];
    }

    private function sinHorarioPropio(): void
    {
        $this->bd->ejecutar('DELETE FROM configuracion_institucion WHERE id_institucion = ?', [self::ID_INSTITUCION]);
    }

    public function testUnReporteDelSabadoPorLaNocheYaNoVenceElDomingo(): void
    {
        // Caso real de producción: SIR-202600004, sábado 19/09/2026 23:19.
        // En horas de calendario, con 8 h, vencía el domingo a las 7:19.
        $this->sinHorarioPropio();
        $reporte = $this->reporteConSla8h('2026-09-19 23:19:00');

        $domingo = $this->modelo->calcular_vencimiento($reporte, null, new DateTimeImmutable('2026-09-20 12:00:00'));
        $this->assertSame('en_tiempo', $domingo['estado_sla'], 'El domingo nadie trabaja: no puede estar vencido.');
        $this->assertEqualsWithDelta(0.0, $domingo['horas_transcurridas'], 0.001);
        $this->assertSame('2026-09-21 15:00:00', $domingo['fecha_vencimiento'], 'Lunes 7:00 + 8 h hábiles.');

        $lunesTarde = $this->modelo->calcular_vencimiento($reporte, null, new DateTimeImmutable('2026-09-21 16:00:00'));
        $this->assertSame('vencido', $lunesTarde['estado_sla']);
        $this->assertEqualsWithDelta(9.0, $lunesTarde['horas_transcurridas'], 0.001);
    }

    public function testUsaElHorarioGuardadoPorLaInstitucion(): void
    {
        // Colegio que trabaja lunes a viernes de 6 a 18, sin sábado.
        $semana = array_fill_keys(['1', '2', '3', '4', '5'], ['06:00', '18:00']) + ['6' => null, '7' => null];
        $this->bd->ejecutar(
            'INSERT INTO configuracion_institucion (id_institucion, horario_semanal_json) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE horario_semanal_json = VALUES(horario_semanal_json)',
            [self::ID_INSTITUCION, json_encode($semana)]
        );
        $reporte = $this->reporteConSla8h('2026-09-25 16:00:00'); // viernes

        $r = $this->modelo->calcular_vencimiento($reporte, null, new DateTimeImmutable('2026-09-28 12:00:00'));

        // Viernes 16-18 (2 h) + lunes 6-12 (6 h) = 8 h justas.
        $this->assertEqualsWithDelta(8.0, $r['horas_transcurridas'], 0.001);
        $this->assertSame('2026-09-28 12:00:00', $r['fecha_vencimiento']);

        // El mapa del tablero trae el mismo horario y da lo mismo.
        $mapa = $this->modelo->mapa_por_institucion(self::ID_INSTITUCION);
        $this->assertSame($r, $this->modelo->calcular_vencimiento($reporte, $mapa, new DateTimeImmutable('2026-09-28 12:00:00')));
    }

    public function testLaPausaDetieneElRelojEnHorasHabiles(): void
    {
        // Registrado viernes 16:00, pausado el lunes 8:00 (8 h hábiles con el
        // horario por defecto), consultado el miércoles.
        $this->sinHorarioPropio();
        $reporte = $this->reporteConSla8h('2026-09-25 16:00:00', '2026-09-28 08:00:00');

        $r = $this->modelo->calcular_vencimiento($reporte, null, new DateTimeImmutable('2026-09-30 12:00:00'));

        $this->assertEqualsWithDelta(8.0, $r['horas_transcurridas'], 0.001, 'Lo pausado no cuenta.');
        $this->assertSame('cerca', $r['estado_sla'], 'Justo en el límite: 0 h restantes.');
    }

    public function testUnReporteDeVariosMesesCuentaTodosSusDias(): void
    {
        // El cálculo anterior tomaba días y horas de la diferencia de fechas
        // sin sus meses: 40 días contaban como 9.
        $reporte = $this->reporteFalso(0);
        $reporte['fecha_hora_registro'] = '2026-06-01 00:00:00';

        $r = $this->modelo->calcular_vencimiento($reporte, null, new DateTimeImmutable('2026-07-11 00:00:00'), HorarioLaboral::siempre());

        $this->assertEqualsWithDelta(960.0, $r['horas_transcurridas'], 0.001);
    }

    // ------------------------------------------------------ pausas acumuladas

    public function testLasPausasTerminadasNoVuelvenAContar(): void
    {
        // Viernes 16:00 registrado; el técnico lo soluciona el lunes a las
        // 8:00 (pausa); el gestor lo rechaza el miércoles a las 12:00
        // (reanuda). Consultado el miércoles a las 14:00.
        //   Transcurridas en bruto: vie 1 + sáb 6 + lun 10 + mar 10 + mié 7 = 34 h.
        //   En pausa: lun 8-17 (9) + mar 10 + mié 7-12 (5) = 24 h.
        //   Del técnico: 10 h.
        // Antes, al reanudar se perdía la pausa y contaban las 34.
        $this->sinHorarioPropio();
        $reporte = $this->reporteConSla8h('2026-09-25 16:00:00');
        $modelo_reporte = new ModeloReporte();
        $id = (int) $modelo_reporte->crear($this->datosReporteValido());
        // Solo la pausa: la fecha de registro la protege un trigger (RN-12), y
        // el cálculo la toma del arreglo $reporte, que ya la tiene fija.
        $this->bd->ejecutar('UPDATE reporte SET fecha_pausa_sla = ? WHERE id_reporte = ?', ['2026-09-28 08:00:00', $id]);

        $sumadas = $modelo_reporte->reanudar_sla($id, self::ID_INSTITUCION, new DateTimeImmutable('2026-09-30 12:00:00'));
        $this->assertEqualsWithDelta(24.0, $sumadas, 0.001);

        $fila = $this->bd->obtener_uno('SELECT fecha_pausa_sla, horas_pausa_sla FROM reporte WHERE id_reporte = ?', [$id]);
        $this->assertNull($fila['fecha_pausa_sla']);
        $this->assertEqualsWithDelta(24.0, (float) $fila['horas_pausa_sla'], 0.001);

        $reporte['horas_pausa_sla'] = $fila['horas_pausa_sla'];
        $r = $this->modelo->calcular_vencimiento($reporte, null, new DateTimeImmutable('2026-09-30 14:00:00'));
        $this->assertEqualsWithDelta(10.0, $r['horas_transcurridas'], 0.001);
        $this->assertSame('vencido', $r['estado_sla'], 'Plazo de 8 h: el técnico lleva 10.');
        $this->assertEqualsWithDelta(-2.0, $r['horas_restantes'], 0.001);
    }

    public function testPausarDosVecesNoReiniciaLaPausa(): void
    {
        $modelo_reporte = new ModeloReporte();
        $id = (int) $modelo_reporte->crear($this->datosReporteValido());
        $this->bd->ejecutar('UPDATE reporte SET fecha_pausa_sla = ? WHERE id_reporte = ?', ['2026-09-28 08:00:00', $id]);

        $modelo_reporte->pausar_sla($id, self::ID_INSTITUCION);

        $this->assertSame('2026-09-28 08:00:00',
            $this->bd->obtener_valor('SELECT fecha_pausa_sla FROM reporte WHERE id_reporte = ?', [$id]),
            'Se sobrescribió la pausa en curso: lo ya pausado volvería a contar.');
    }

    public function testReanudarSinPausaNoHaceNada(): void
    {
        $modelo_reporte = new ModeloReporte();
        $id = (int) $modelo_reporte->crear($this->datosReporteValido());

        $this->assertSame(0.0, $modelo_reporte->reanudar_sla($id, self::ID_INSTITUCION));
        $this->assertEqualsWithDelta(0.0, (float) $this->bd->obtener_valor(
            'SELECT horas_pausa_sla FROM reporte WHERE id_reporte = ?', [$id]), 0.0001);
    }
}
