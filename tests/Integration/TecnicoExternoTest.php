<?php

require_once dirname(__DIR__, 2) . '/app/modelos/modelo_reporte.php';
require_once dirname(__DIR__, 2) . '/app/modelos/modelo_usuario.php';
require_once dirname(__DIR__, 2) . '/app/modelos/modelo_institucion.php';
require_once dirname(__DIR__, 2) . '/app/servicios/servicio_autorizacion.php';
require_once dirname(__DIR__, 2) . '/app/servicios/servicio_autenticacion.php';

/**
 * Un técnico de una empresa de mantenimiento que atiende uno o varios
 * colegios a la vez.
 *
 * El superadministrador podía vincularlo y el técnico elegía colegio al
 * entrar, pero nada de lo que venía después reconocía el vínculo: en cada
 * colegio tenía cero permisos, no aparecía en la lista de asignar, y si se le
 * asignaba igualmente respondía "Técnico no encontrado".
 *
 * Se siembra una empresa con un técnico vinculado a dos colegios, y un tercer
 * colegio sin vínculo, todo dentro de la transacción que se revierte.
 */
final class TecnicoExternoTest extends BaseDbTestCase
{
    private int $empresa;
    private int $colegio1;
    private int $colegio2;
    private int $colegio3;
    private int $tecnico;
    private ModeloUsuario $usuarios;

    /** @var array<int, array{sede:int, categoria:int}> */
    private array $catalogo = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuarios = new ModeloUsuario();

        $this->empresa  = $this->institucion('Empresa de mantenimiento', 'empresa_mantenimiento');
        $this->colegio1 = $this->institucion('Colegio 1');
        $this->colegio2 = $this->institucion('Colegio 2');
        $this->colegio3 = $this->institucion('Colegio 3, sin vínculo');

        $this->tecnico = $this->usuario($this->empresa, ROL_TECNICO, 'tecnico-externo');
        $this->usuarios->vincular_tecnico_institucion($this->tecnico, $this->colegio1);
        $this->usuarios->vincular_tecnico_institucion($this->tecnico, $this->colegio2);
    }

    private function institucion(string $nombre, string $tipo = 'educativa'): int
    {
        $id = (int) $this->bd->insertar('institucion', [
            'nombre'      => $nombre,
            'tipo'        => $tipo,
            'codigo_dane' => (string) random_int(1000000000000, 9999999999999),
        ]);
        $this->catalogo[$id] = [
            'sede'      => (int) $this->bd->insertar('sede', ['id_institucion' => $id, 'nombre' => 'Sede']),
            'categoria' => (int) $this->bd->insertar('categoria', ['id_institucion' => $id, 'nombre' => 'Categoría']),
        ];

        return $id;
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

    private function reporteAsignado(int $institucion): int
    {
        $modelo = new ModeloReporte();
        $id = (int) $modelo->crear($this->datosReporteValido([
            'id_institucion'  => $institucion,
            'id_sede'         => $this->catalogo[$institucion]['sede'],
            'id_categoria'    => $this->catalogo[$institucion]['categoria'],
            'id_subcategoria' => null,
        ]));
        $modelo->asignar_tecnico($id, $institucion, $this->tecnico);

        return $id;
    }

    private function auth(int $institucion): ServicioAutorizacion
    {
        return new ServicioAutorizacion($this->tecnico, $institucion);
    }

    private function idsTecnicos(int $institucion): array
    {
        return array_map('intval', array_column($this->usuarios->tecnicos_de_institucion($institucion), 'id_usuario'));
    }

    // ------------------------------------------------------ al iniciar sesión

    public function testAlEntrarSeLeReconoceComoTecnicoConLosRolesRealesDeLaBase(): void
    {
        // Se comparaba el nombre con 'tecnico', pero el rol se llama 'Técnico':
        // nunca coincidía, y el técnico entraba a su empresa sin poder elegir
        // colegio. Lo encontró la prueba en producción, no esta suite: las
        // demás pruebas parten de una institución ya elegida.
        $roles = $this->usuarios->obtener_roles($this->tecnico, $this->empresa);

        $this->assertNotContains('tecnico', array_column($roles, 'nombre_rol'),
            'Si el rol pasara a llamarse así, esta prueba dejaría de demostrar nada.');
        $this->assertTrue(ServicioAutenticacion::incluye_rol_tecnico($roles));

        $gestor = $this->usuario($this->colegio1, ROL_GESTOR, 'gestor-login');
        $this->assertFalse(ServicioAutenticacion::incluye_rol_tecnico(
            $this->usuarios->obtener_roles($gestor, $this->colegio1)
        ));
    }

    // ------------------------------------------------------ puede trabajar

    public function testTrabajaEnCadaUnoDeSusColegios(): void
    {
        foreach ([$this->colegio1, $this->colegio2] as $colegio) {
            $this->assertTrue($this->auth($colegio)->verificar_permiso(PERMISO_TECNICO));
            $this->assertTrue($this->auth($colegio)->tiene_rol(ROL_TECNICO));
            $this->assertContains(PERMISO_TECNICO, $this->auth($colegio)->obtener_permisos());
        }
    }

    public function testApareceEnLaListaDeAsignarDeCadaColegioConSuEmpresa(): void
    {
        foreach ([$this->colegio1, $this->colegio2] as $colegio) {
            $fila = $this->usuarios->obtener_tecnico_para_institucion($this->tecnico, $colegio);
            $this->assertNotFalse($fila);
            $this->assertSame('Empresa de mantenimiento', $fila['empresa']);
        }
    }

    public function testLosTecnicosPropiosSiguenFuncionando(): void
    {
        $propio = $this->usuario($this->colegio1, ROL_TECNICO, 'tecnico-propio');

        $this->assertTrue((new ServicioAutorizacion($propio, $this->colegio1))->verificar_permiso(PERMISO_TECNICO));
        $fila = $this->usuarios->obtener_tecnico_para_institucion($propio, $this->colegio1);
        $this->assertNotFalse($fila);
        $this->assertNull($fila['empresa'], 'Un técnico propio no lleva nombre de empresa.');
        $this->assertNotContains($propio, $this->idsTecnicos($this->colegio2));
    }

    // ------------------------------------------------------ lo que no puede

    public function testNoTieneNadaEnUnColegioSinVinculo(): void
    {
        $this->assertFalse($this->auth($this->colegio3)->verificar_permiso(PERMISO_TECNICO));
        $this->assertSame([], $this->auth($this->colegio3)->obtener_permisos());
        $this->assertNotContains($this->tecnico, $this->idsTecnicos($this->colegio3));
        $this->assertFalse($this->usuarios->obtener_tecnico_para_institucion($this->tecnico, $this->colegio3));
    }

    public function testTrabajandoEnUnColegioNoVeLosReportesDelOtro(): void
    {
        $delColegio1 = $this->reporteAsignado($this->colegio1);
        $delColegio2 = $this->reporteAsignado($this->colegio2);

        $ids = array_map('intval', array_column(
            (new ModeloReporte())->listar_por_tecnico($this->tecnico, $this->colegio1), 'id_reporte'
        ));

        $this->assertContains($delColegio1, $ids);
        $this->assertNotContains($delColegio2, $ids);
    }

    public function testElVinculoSoloDaElRolTecnicoAunqueTengaOtrosEnSuEmpresa(): void
    {
        // Si en su empresa también fuera Gestor, en el colegio sigue siendo
        // solo Técnico: vincular no puede servir para escalar privilegios.
        $this->bd->insertar('usuario_rol', [
            'id_usuario' => $this->tecnico, 'id_rol' => ROL_GESTOR, 'id_institucion' => $this->empresa,
        ]);
        $auth = $this->auth($this->colegio1);

        $this->assertSame([ROL_TECNICO], array_map('intval', array_column($auth->obtener_roles(), 'id_rol')));
        $this->assertFalse($auth->verificar_permiso(PERMISO_ASIGNAR_TECNICO));
        $this->assertFalse($auth->verificar_permiso(PERMISO_VER_TODOS_REPORTES));
        $this->assertFalse($auth->es_superadmin());
    }

    public function testUnVinculoDeAlguienQueNoEsDeUnaEmpresaNoDaNada(): void
    {
        // Defensa aunque alguien escriba el vínculo directamente en la base:
        // el técnico de un colegio no gana nada en otro.
        $deOtroColegio = $this->usuario($this->colegio2, ROL_TECNICO, 'tecnico-colegio-2');
        $this->bd->insertar('tecnico_institucion', [
            'id_usuario' => $deOtroColegio, 'id_institucion' => $this->colegio1, 'activo' => 1,
        ]);

        $this->assertFalse((new ServicioAutorizacion($deOtroColegio, $this->colegio1))->verificar_permiso(PERMISO_TECNICO));
        $this->assertNotContains($deOtroColegio, $this->idsTecnicos($this->colegio1));
    }

    // -------------------------------------------------- crear la empresa

    public function testSePuedeCrearUnaEmpresaDeMantenimiento(): void
    {
        // Antes el alta no dejaba elegir el tipo: todo salía educativa y la
        // empresa solo se podía crear tocando la base de datos.
        $modelo = new ModeloInstitucion();
        $id = (int) $modelo->crear([
            'nombre'      => 'Empresa creada desde el alta',
            'tipo'        => 'empresa_mantenimiento',
            'codigo_dane' => (string) random_int(1000000000000, 9999999999999),
        ]);

        $this->assertSame('empresa_mantenimiento', $modelo->obtener_por_id($id)['tipo']);

        $this->expectException(Exception::class);
        $modelo->crear(['nombre' => 'Tipo inventado', 'tipo' => 'superadmin', 'codigo_dane' => '99999']);
    }

    // -------------------------------------------------- vincular y desvincular

    public function testSoloSeVinculanTecnicosDeEmpresaHaciaColegios(): void
    {
        $gestor = $this->usuario($this->colegio1, ROL_GESTOR, 'gestor');
        try {
            $this->usuarios->vincular_tecnico_institucion($gestor, $this->colegio3);
            $this->fail('Se vinculó a alguien que no es técnico de una empresa.');
        } catch (Exception $e) {
            $this->assertStringContainsString('empresa de mantenimiento', $e->getMessage());
        }

        $otraEmpresa = $this->institucion('Otra empresa', 'empresa_mantenimiento');
        try {
            $this->usuarios->vincular_tecnico_institucion($this->tecnico, $otraEmpresa);
            $this->fail('Se vinculó un técnico a una empresa.');
        } catch (Exception $e) {
            $this->assertStringContainsString('institución educativa', $e->getMessage());
        }
    }

    public function testDesvincularQuitaEseColegioYConservaElOtro(): void
    {
        $this->usuarios->desvincular_tecnico_institucion($this->tecnico, $this->colegio2);

        $this->assertFalse($this->auth($this->colegio2)->verificar_permiso(PERMISO_TECNICO));
        $this->assertTrue($this->auth($this->colegio1)->verificar_permiso(PERMISO_TECNICO));
        $this->assertNotContains($this->tecnico, $this->idsTecnicos($this->colegio2));
    }

    public function testNoSeDesvinculaMientrasTengaReportesAbiertosAlli(): void
    {
        $abierto = $this->reporteAsignado($this->colegio1);
        $ticket  = $this->bd->obtener_valor('SELECT numero_ticket FROM reporte WHERE id_reporte = ?', [$abierto]);

        try {
            $this->usuarios->desvincular_tecnico_institucion($this->tecnico, $this->colegio1);
            $this->fail('Se desvinculó un técnico con un reporte abierto.');
        } catch (Exception $e) {
            $this->assertStringContainsString((string) $ticket, $e->getMessage());
        }
        $this->assertTrue($this->auth($this->colegio1)->verificar_permiso(PERMISO_TECNICO), 'Debe seguir vinculado.');

        // Una vez cerrado el reporte, ya se puede.
        $this->bd->ejecutar('UPDATE reporte SET id_estado = ? WHERE id_reporte = ?', [ESTADO_CERRADO, $abierto]);
        $this->usuarios->desvincular_tecnico_institucion($this->tecnico, $this->colegio1);
        $this->assertFalse($this->auth($this->colegio1)->verificar_permiso(PERMISO_TECNICO));
    }
}
