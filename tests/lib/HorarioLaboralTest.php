<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/lib/horario_laboral.php';

/**
 * Horas hábiles con fechas fijas: el resultado no puede depender del día en
 * que se ejecuta la prueba.
 *
 * Horario por defecto: lunes a viernes 7:00–17:00, sábado 7:00–13:00,
 * domingo y festivos no. Calendario de referencia, septiembre–octubre 2026:
 * viernes 25/09, sábado 26/09, lunes 28/09; lunes 12/10 es festivo.
 */
final class HorarioLaboralTest extends TestCase
{
    private function f(string $fecha): DateTimeImmutable
    {
        return new DateTimeImmutable($fecha, new DateTimeZone('America/Bogota'));
    }

    private function vence(string $desde, float $horas): string
    {
        return HorarioLaboral::por_defecto()->sumar_horas($this->f($desde), $horas)->format('Y-m-d H:i');
    }

    // ------------------------------------------------------------ festivos

    public function testFestivosDe2026SegunElCalendarioOficial(): void
    {
        $this->assertSame([
            '2026-01-01', '2026-01-12', '2026-03-23', '2026-04-02', '2026-04-03',
            '2026-05-01', '2026-05-18', '2026-06-08', '2026-06-15', '2026-06-29',
            '2026-07-20', '2026-08-07', '2026-08-17', '2026-10-12', '2026-11-02',
            '2026-11-16', '2026-12-08', '2026-12-25',
        ], FestivosColombia::del_anio(2026));
    }

    public function testDosFestivosElMismoDiaCuentanUnaVez(): void
    {
        // 2025: San Pedro (29/06, domingo -> lunes 30) y Sagrado Corazón
        // coincidieron el lunes 30 de junio.
        $fechas = FestivosColombia::del_anio(2025);
        $this->assertCount(17, $fechas);
        $this->assertContains('2025-06-30', $fechas);
        $this->assertContains('2025-04-18', $fechas, 'Viernes Santo 2025');
    }

    // ------------------------------------------------------ sumar horas

    public function testUnReporteDelSabadoPorLaNocheEmpiezaAContarElLunes(): void
    {
        // Caso real: SIR-202600004 entró el sábado 19/09/2026 a las 23:19.
        // Con 8 h de plazo vencía el domingo a las 7:19. Ahora: lunes 7:00 + 8 h.
        $this->assertSame('2026-09-21 15:00', $this->vence('2026-09-19 23:19', 8));
    }

    public function testUrgenteDelViernesALasCuatroVenceElLunesALasOcho(): void
    {
        // 1 h el viernes + 6 h el sábado (7–13) + 1 h el lunes.
        $this->assertSame('2026-09-28 08:00', $this->vence('2026-09-25 16:00', 8));
    }

    public function testElFestivoNoCuenta(): void
    {
        // Viernes 9/10: 1 h. Sábado 10/10: 6 h. Lunes 12/10 es festivo.
        // Martes 13/10: la hora que falta.
        $this->assertSame('2026-10-13 08:00', $this->vence('2026-10-09 16:00', 8));
    }

    public function testDentroDelMismoDia(): void
    {
        $this->assertSame('2026-09-29 11:30', $this->vence('2026-09-29 09:00', 2.5));
    }

    public function testAntesDeAbrirEmpiezaAlAbrir(): void
    {
        $this->assertSame('2026-09-29 08:00', $this->vence('2026-09-29 05:00', 1));
    }

    public function testCeroHorasEsElMismoInstante(): void
    {
        $this->assertSame('2026-09-27 03:00', $this->vence('2026-09-27 03:00', 0));
    }

    // ------------------------------------------------------ horas entre

    public function testHorasEntreEsLoInversoDeSumar(): void
    {
        $h = HorarioLaboral::por_defecto();
        $this->assertEqualsWithDelta(8.0, $h->horas_entre($this->f('2026-09-25 16:00'), $this->f('2026-09-28 08:00')), 0.0001);
        $this->assertEqualsWithDelta(0.0, $h->horas_entre($this->f('2026-09-26 13:00'), $this->f('2026-09-28 07:00')), 0.0001,
            'De sábado 13:00 a lunes 7:00 no hay ninguna hora hábil.');
    }

    public function testUnaSemanaCompletaTiene56HorasHabiles(): void
    {
        // 5 días × 10 h + sábado 6 h. Semana sin festivos: 28/09 a 05/10.
        $h = HorarioLaboral::por_defecto();
        $this->assertEqualsWithDelta(56.0, $h->horas_entre($this->f('2026-09-28 00:00'), $this->f('2026-10-05 00:00')), 0.0001);
    }

    public function testVariosMesesSeCuentanEnteros(): void
    {
        // El cálculo anterior usaba los días de la diferencia sin los meses:
        // 60 días se contaban como 29. Con el horario "siempre", 60 días son
        // 1440 horas exactas.
        $h = HorarioLaboral::siempre();
        $this->assertEqualsWithDelta(1440.0, $h->horas_entre($this->f('2026-06-01 00:00'), $this->f('2026-07-31 00:00')), 0.0001);
    }

    public function testElOrdenInversoDaCero(): void
    {
        $this->assertSame(0.0, HorarioLaboral::por_defecto()->horas_entre($this->f('2026-09-29 10:00'), $this->f('2026-09-29 09:00')));
    }

    // ------------------------------------------------------ configuración

    public function testValidacionDelHorario(): void
    {
        $this->assertNull(HorarioLaboral::validar(HorarioLaboral::semana_por_defecto()));
        $this->assertNotNull(HorarioLaboral::validar(array_fill_keys(['1','2','3','4','5','6','7'], null)), 'Sin días laborales');
        $this->assertNotNull(HorarioLaboral::validar(['1' => ['17:00', '07:00']]), 'Fin antes que inicio');
        $this->assertNotNull(HorarioLaboral::validar(['1' => ['7', '17:00']]), 'Hora mal escrita');
    }

    public function testUnJsonDanadoUsaElHorarioPorDefecto(): void
    {
        $this->assertSame(HorarioLaboral::semana_por_defecto(), HorarioLaboral::desde_json('{esto no es json')->a_arreglo());
        $this->assertSame(HorarioLaboral::semana_por_defecto(), HorarioLaboral::desde_json(null)->a_arreglo());
    }

    public function testUnHorarioPropioSeRespeta(): void
    {
        // Colegio que solo trabaja lunes a viernes de 6 a 18.
        $semana = array_fill_keys(['1','2','3','4','5'], ['06:00', '18:00']) + ['6' => null, '7' => null];
        $h = HorarioLaboral::desde_json(json_encode($semana));

        $this->assertSame('2026-09-28 07:00', $h->sumar_horas($this->f('2026-09-25 16:00'), 3)->format('Y-m-d H:i'),
            'Viernes 16–18 son 2 h; sin sábado, la tercera cae el lunes 6–7.');
    }
}
