<?php

require_once dirname(__DIR__, 2) . '/app/modelos/modelo_reporte.php';

/**
 * Cubre las marcas de tiempo del ciclo de vida.
 *
 * Las cuatro columnas llevaban desde el primer día en el esquema sin que nadie
 * las escribiera. Eso tenía dos efectos visibles: la línea de tiempo que ve el
 * ciudadano decía "Pendiente — Cierre formal" hasta en reportes cerrados hacía
 * meses, y el promedio de días de resolución se calculaba sobre
 * fecha_actualizacion, una columna con ON UPDATE CURRENT_TIMESTAMP que se mueve
 * con cualquier edición posterior al cierre.
 */
final class CicloVidaReporteTest extends BaseDbTestCase
{
    private ModeloReporte $modelo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modelo = new ModeloReporte();
    }

    private function crearYLlevarA(int ...$estados): array
    {
        $id = $this->modelo->crear($this->datosReporteValido());
        foreach ($estados as $estado) {
            $this->modelo->cambiar_estado($id, self::ID_INSTITUCION, $estado, 'prueba', self::ID_USUARIO);
        }
        return [$id, $this->modelo->obtener_por_id($id, self::ID_INSTITUCION)];
    }

    public function testUnReporteReciénCreadoNoTieneNingunaMarcaDeCierre(): void
    {
        [, $reporte] = $this->crearYLlevarA();

        $this->assertNull($reporte['fecha_hora_solucionado']);
        $this->assertNull($reporte['fecha_hora_cierre']);
        $this->assertNull($reporte['fecha_hora_inicio_tecnico']);
    }

    public function testMarcarSolucionadoEscribeSuFecha(): void
    {
        [, $reporte] = $this->crearYLlevarA(ESTADO_EN_PROCESO, ESTADO_SOLUCIONADO);

        $this->assertNotNull($reporte['fecha_hora_solucionado']);
    }

    public function testCerrarEscribeLaFechaDeCierre(): void
    {
        [, $reporte] = $this->crearYLlevarA(
            ESTADO_EN_PROCESO, ESTADO_SOLUCIONADO, ESTADO_EN_VALIDACION, ESTADO_CERRADO
        );

        $this->assertNotNull($reporte['fecha_hora_cierre'],
            'Sin esto la línea de tiempo pública sigue diciendo "Pendiente — Cierre formal".');
    }

    public function testAnularTambienCierraElReporte(): void
    {
        // Anulado es terminal igual que Cerrado. Sin marca de cierre no habría
        // forma de saber cuándo dejó de estar abierto.
        [, $reporte] = $this->crearYLlevarA(ESTADO_ANULADO);

        $this->assertNotNull($reporte['fecha_hora_cierre']);
    }

    public function testAlRehacerUnaSolucionDevueltaGanaLaUltimaFecha(): void
    {
        // Solucionado -> Devuelto -> En Proceso -> Solucionado. La fecha que
        // cuenta para el tiempo de resolución es la del segundo intento, que
        // es cuando el problema quedó realmente resuelto.
        [$id, $reporte] = $this->crearYLlevarA(ESTADO_EN_PROCESO, ESTADO_SOLUCIONADO);
        $primera = $reporte['fecha_hora_solucionado'];

        // Retroceder la primera marca para que las dos fechas sean distintas
        // aunque la prueba se ejecute en menos de un segundo.
        $this->bd->ejecutar(
            'UPDATE reporte SET fecha_hora_solucionado = DATE_SUB(NOW(), INTERVAL 2 DAY) WHERE id_reporte = ?',
            [$id]
        );

        $this->modelo->cambiar_estado($id, self::ID_INSTITUCION, ESTADO_DEVUELTO, 'insuficiente', self::ID_USUARIO);
        $this->modelo->cambiar_estado($id, self::ID_INSTITUCION, ESTADO_EN_PROCESO, 'retoma', self::ID_USUARIO);
        $this->modelo->cambiar_estado($id, self::ID_INSTITUCION, ESTADO_SOLUCIONADO, 'rehecho', self::ID_USUARIO);

        $final = $this->modelo->obtener_por_id($id, self::ID_INSTITUCION);

        $this->assertNotNull($primera);
        $this->assertGreaterThan(
            strtotime('-1 day'),
            strtotime($final['fecha_hora_solucionado']),
            'La segunda solución debe sobrescribir a la primera.'
        );
    }

    public function testAsignarGuardaQuienAsigno(): void
    {
        // id_gestor_asignador existía sin que nadie la escribiera: quién había
        // asignado solo constaba en el registro de auditoría.
        $id = $this->modelo->crear($this->datosReporteValido());

        $this->modelo->asignar_tecnico($id, self::ID_INSTITUCION, self::ID_USUARIO, self::ID_USUARIO);

        $reporte = $this->modelo->obtener_por_id($id, self::ID_INSTITUCION);
        $this->assertSame(self::ID_USUARIO, (int) $reporte['id_gestor_asignador']);
        $this->assertNotNull($reporte['fecha_hora_asignacion']);
    }

    public function testElListadoTraeElNombreDeLaCategoriaYNoSuNumero(): void
    {
        // La consulta era un SELECT * sin JOIN, así que la vista nunca
        // encontraba nombre_categoria y caía al id: la columna "Categoría"
        // mostraba un número.
        $this->modelo->crear($this->datosReporteValido());

        $lista = $this->modelo->listar_por_institucion(self::ID_INSTITUCION, [], 5, 0);

        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('nombre_categoria', $lista[0]);
        $this->assertNotSame('', (string) $lista[0]['nombre_categoria']);
    }

    public function testElFiltroDeUrgenciaFiltraDeVerdad(): void
    {
        // El controlador escribía la clave id_urgencia_calculada y el modelo
        // leía id_urgencia: el desplegable no hacía nada en absoluto.
        $this->modelo->crear($this->datosReporteValido([
            'id_urgencia_declarada' => URGENCIA_URGENTE,
        ]));

        $urgentes = $this->modelo->listar_por_institucion(
            self::ID_INSTITUCION, ['id_urgencia' => URGENCIA_URGENTE], 100, 0
        );
        $no_urgentes = $this->modelo->listar_por_institucion(
            self::ID_INSTITUCION, ['id_urgencia' => URGENCIA_NO_URGENTE], 100, 0
        );

        foreach ($urgentes as $r) {
            $this->assertSame(URGENCIA_URGENTE, (int) $r['id_urgencia_calculada']);
        }
        foreach ($no_urgentes as $r) {
            $this->assertSame(URGENCIA_NO_URGENTE, (int) $r['id_urgencia_calculada']);
        }
    }
}
