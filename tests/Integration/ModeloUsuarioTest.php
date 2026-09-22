<?php

require_once dirname(__DIR__, 2) . '/app/modelos/modelo_usuario.php';

final class ModeloUsuarioTest extends BaseDbTestCase
{
    private ModeloUsuario $modelo;

    // Fixture fija del seed local (ver ModeloReporteTest y BaseDbTestCase).
    private const CORREO_SEED = 'prueba@local.test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->modelo = new ModeloUsuario();
    }

    public function testObtenerPorIdRespetaAislamientoMultitenant(): void
    {
        // RN-01: el mismo id_usuario consultado con una institución distinta
        // a la suya no debe devolver nada.
        $usuario = $this->modelo->obtener_por_email(self::CORREO_SEED, self::ID_INSTITUCION);
        $this->assertNotFalse($usuario, 'Precondición: el usuario semilla debe existir.');

        $resultado = $this->modelo->obtener_por_id($usuario['id_usuario'], 999999);
        $this->assertFalse($resultado);
    }

    public function testObtenerPorEmailConInstitucionSoloDevuelveDeEsaInstitucion(): void
    {
        $resultado = $this->modelo->obtener_por_email(self::CORREO_SEED, 999999);
        $this->assertFalse($resultado);
    }

    public function testObtenerPorEmailSinInstitucionBuscaEnTodas(): void
    {
        // Usado en el login inicial, antes de saber a qué institución pertenece
        $resultado = $this->modelo->obtener_por_email(self::CORREO_SEED);
        $this->assertNotFalse($resultado);
        $this->assertSame(self::ID_INSTITUCION, (int) $resultado['id_institucion']);
    }

    public function testCrearRechazaCorreoDuplicadoEnLaMismaInstitucion(): void
    {
        $this->expectException(Exception::class);
        $this->modelo->crear([
            'id_institucion' => self::ID_INSTITUCION,
            'nombre_completo' => 'Duplicado de prueba',
            'correo_electronico' => self::CORREO_SEED,
            'hash_contrasena' => password_hash('Cambiar@2026!', PASSWORD_BCRYPT),
        ]);
    }

    public function testCrearRechazaEmailConFormatoInvalido(): void
    {
        $this->expectException(Exception::class);
        $this->modelo->crear([
            'id_institucion' => self::ID_INSTITUCION,
            'nombre_completo' => 'Correo inválido',
            'correo_electronico' => 'esto-no-es-un-correo',
            'hash_contrasena' => password_hash('Cambiar@2026!', PASSWORD_BCRYPT),
        ]);
    }
}
