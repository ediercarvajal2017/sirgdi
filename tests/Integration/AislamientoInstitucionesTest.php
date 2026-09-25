<?php

require_once dirname(__DIR__, 2) . '/app/modelos/modelo_reporte.php';
require_once dirname(__DIR__, 2) . '/app/modelos/modelo_usuario.php';
require_once dirname(__DIR__, 2) . '/app/modelos/modelo_evidencia.php';
require_once dirname(__DIR__, 2) . '/app/servicios/servicio_autorizacion.php';
require_once dirname(__DIR__, 2) . '/app/servicios/servicio_exportacion.php';
require_once dirname(__DIR__, 2) . '/app/servicios/servicio_prioridad.php';

/**
 * Lo que una institución no puede ver ni tocar de otra.
 *
 * Todos los clientes comparten una sola base de datos. El fallo más grave que
 * puede tener el sistema no es una pantalla rota: es que el gestor de un
 * colegio vea los reportes, las fotos o los datos de los ciudadanos de otro.
 *
 * Se siembran dos instituciones, A (la del seed local) y B (nueva, dentro de
 * la transacción que se revierte al terminar), y se comprueba cada camino por
 * el que los datos salen: consultas del modelo, permisos, exportación, tablero
 * y evidencias.
 */
final class AislamientoInstitucionesTest extends BaseDbTestCase
{
    private const A = self::ID_INSTITUCION;

    private int $b;
    private int $reporteA;
    private int $reporteB;
    private string $ticketB;
    private int $gestorA;
    private int $tecnicoA;
    private int $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->b = (int) $this->bd->insertar('institucion', ['nombre' => 'Institución B (prueba de aislamiento)']);
        $sedeB = (int) $this->bd->insertar('sede', ['id_institucion' => $this->b, 'nombre' => 'Sede B']);
        $categoriaB = (int) $this->bd->insertar('categoria', ['id_institucion' => $this->b, 'nombre' => 'Categoría B']);

        $modelo = new ModeloReporte();
        $this->reporteA = (int) $modelo->crear($this->datosReporteValido([
            'descripcion_problema' => 'MARCA-A: reporte de la institución A.',
        ]));
        $this->reporteB = (int) $modelo->crear($this->datosReporteValido([
            'id_institucion'       => $this->b,
            'id_sede'              => $sedeB,
            'id_categoria'         => $categoriaB,
            'id_subcategoria'      => null,
            'descripcion_problema' => 'MARCA-B: reporte de la institución B, con datos de un ciudadano.',
            'correo_reportante'    => 'ciudadano-de-b@local.test',
        ]));
        $this->ticketB = (string) $this->bd->obtener_valor(
            'SELECT numero_ticket FROM reporte WHERE id_reporte = ?', [$this->reporteB]
        );

        $this->gestorA  = $this->usuario(self::A, ROL_GESTOR, 'gestor-a');
        $this->tecnicoA = $this->usuario(self::A, ROL_TECNICO, 'tecnico-a');
        $this->adminB   = $this->usuario($this->b, ROL_ADMIN, 'admin-b');
    }

    private function usuario(int $institucion, int $rol, string $nombre): int
    {
        $id = (int) $this->bd->insertar('usuario', [
            'id_institucion'     => $institucion,
            'nombre_completo'    => "Prueba $nombre",
            'correo_electronico' => $nombre . '-' . uniqid() . '@local.test',
            'hash_contrasena'    => 'no-se-usa',
        ]);
        $this->bd->insertar('usuario_rol', [
            'id_usuario' => $id, 'id_rol' => $rol, 'id_institucion' => $institucion,
        ]);

        return $id;
    }

    // ------------------------------------------------------------ lecturas

    public function testElModeloNoDevuelveUnReporteDeOtraInstitucion(): void
    {
        $modelo = new ModeloReporte();

        // Control: con su propia institución, el reporte sí aparece. Sin esto,
        // las comprobaciones de abajo pasarían aunque la siembra hubiera fallado.
        $this->assertNotEmpty($modelo->obtener_por_id($this->reporteB, $this->b));

        $this->assertEmpty($modelo->obtener_por_id($this->reporteB, self::A));
        $this->assertEmpty($modelo->obtener_detallado($this->reporteB, self::A));

        // El número de ticket es por institución: A puede tener su propio
        // reporte con el mismo número que B (el primero de cada una es el
        // mismo). Lo que no puede pasar es que la búsqueda devuelva el de B.
        $porTicket = $modelo->obtener_por_numero_ticket($this->ticketB, self::A);
        if ($porTicket) {
            $this->assertSame(self::A, (int) $porTicket['id_institucion']);
            $this->assertNotSame($this->reporteB, (int) $porTicket['id_reporte']);
        }
    }

    public function testElListadoDeUnaInstitucionNoIncluyeReportesDeOtra(): void
    {
        $ids = array_map('intval', array_column(
            (new ModeloReporte())->listar_por_institucion(self::A, [], 1000, 0), 'id_reporte'
        ));

        $this->assertContains($this->reporteA, $ids);
        $this->assertNotContains($this->reporteB, $ids);
    }

    public function testElTableroNoIncluyeReportesDeOtraInstitucion(): void
    {
        $ids = [];
        foreach ((new ServicioPrioridad())->listar_por_prioridad(self::A) as $item) {
            $ids[] = (int) $item['reporte']['id_reporte'];
        }

        $this->assertContains($this->reporteA, $ids);
        $this->assertNotContains($this->reporteB, $ids);
    }

    public function testLaExportacionNoIncluyeReportesDeOtraInstitucion(): void
    {
        $csv = (new ServicioExportacion(self::A))->exportar_reportes_csv();

        $this->assertStringContainsString('MARCA-A', $csv);
        $this->assertStringNotContainsString('MARCA-B', $csv);
        $this->assertStringNotContainsString('ciudadano-de-b@local.test', $csv);
    }

    public function testUnaEvidenciaDeOtraInstitucionNoSePuedeObtener(): void
    {
        // Las fotos son lo más sensible: salen de aulas y baños.
        $evidencia = (int) $this->bd->insertar('evidencia', [
            'id_reporte'              => $this->reporteB,
            'id_institucion'          => $this->b,
            'id_etapa'                => 1,
            'url_archivo'             => '/no/existe/foto-b.jpg',
            'nombre_archivo_original' => 'foto-b.jpg',
            'tipo_mime'               => 'image/jpeg',
            'tamanio_bytes'           => 1,
        ]);
        $modelo = new ModeloEvidencia();

        $this->assertNotEmpty($modelo->obtener_por_id($evidencia, $this->b));
        $this->assertEmpty($modelo->obtener_por_id($evidencia, self::A));
        $this->assertEmpty($modelo->listar_por_reporte($this->reporteB, self::A));
    }

    // ----------------------------------------------------------- escrituras

    public function testNoSePuedeModificarNiBorrarUnReporteDeOtraInstitucion(): void
    {
        $modelo = new ModeloReporte();
        $antes = $modelo->obtener_por_id($this->reporteB, $this->b);

        $modelo->actualizar($this->reporteB, self::A, ['descripcion_problema' => 'ALTERADO desde A']);
        $modelo->asignar_tecnico($this->reporteB, self::A, $this->tecnicoA);

        // Borrar lo rechaza en voz alta, que es lo correcto.
        try {
            $modelo->eliminar($this->reporteB, self::A);
            $this->fail('Se esperaba que eliminar() rechazara un reporte de otra institución.');
        } catch (Exception $e) {
            $this->assertSame('Reporte no encontrado.', $e->getMessage());
        }

        $despues = $modelo->obtener_por_id($this->reporteB, $this->b);
        $this->assertNotEmpty($despues, 'Un reporte de B se borró desde A.');
        $this->assertSame($antes['descripcion_problema'], $despues['descripcion_problema']);
        $this->assertEmpty($despues['id_tecnico_asignado']);
    }

    public function testUnAdminNoPuedeDarleRolEnSuInstitucionAUnUsuarioDeOtra(): void
    {
        // Esto sí fallaba: la edición de usuarios no comprobaba a qué
        // institución pertenecía el usuario, y le creaba el rol igualmente.
        $modelo = new ModeloUsuario();

        $this->assertFalse($modelo->reemplazar_rol($this->adminB, self::A, ROL_GESTOR));
        $this->assertFalse($this->bd->existe(
            'usuario_rol', 'id_usuario = ? AND id_institucion = ?', [$this->adminB, self::A]
        ));

        // Control: con un usuario propio sí funciona.
        $this->assertTrue($modelo->reemplazar_rol($this->tecnicoA, self::A, ROL_GESTOR));
    }

    // ------------------------------------------------------------- permisos

    public function testLosPermisosDeUnGestorNoValenEnOtraInstitucion(): void
    {
        $enSuInstitucion = new ServicioAutorizacion($this->gestorA, self::A);
        $enOtra          = new ServicioAutorizacion($this->gestorA, $this->b);

        $this->assertTrue($enSuInstitucion->verificar_permiso(PERMISO_VER_TODOS_REPORTES));
        $this->assertFalse($enOtra->verificar_permiso(PERMISO_VER_TODOS_REPORTES));
        $this->assertSame([], $enOtra->obtener_permisos());
    }

    public function testUnAdminDeOtraInstitucionNoGestionaUsuariosAqui(): void
    {
        $this->assertTrue((new ServicioAutorizacion($this->adminB, $this->b))
            ->verificar_permiso(PERMISO_GESTIONAR_USUARIOS));
        $this->assertFalse((new ServicioAutorizacion($this->adminB, self::A))
            ->verificar_permiso(PERMISO_GESTIONAR_USUARIOS));
    }

    public function testUnTecnicoNoVeTodosLosReportesNiEsSuperadmin(): void
    {
        $auth = new ServicioAutorizacion($this->tecnicoA, self::A);

        $this->assertFalse($auth->verificar_permiso(PERMISO_VER_TODOS_REPORTES));
        $this->assertFalse($auth->es_superadmin());
        $this->assertFalse($auth->verificar_permiso('gestionar_instituciones'));
    }
}
