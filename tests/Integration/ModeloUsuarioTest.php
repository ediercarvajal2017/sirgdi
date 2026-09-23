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

    public function testCambiarContrasenaIncrementaVersionDeCredenciales(): void
    {
        // Hallazgo M2: al cambiar la contraseña, las sesiones abiertas debían
        // dejar de ser válidas. El mecanismo es esta versión: si sube, la
        // sesión que guarda la anterior se cierra en la siguiente petición.
        $usuario = $this->modelo->obtener_por_email(self::CORREO_SEED, self::ID_INSTITUCION);
        $this->assertNotFalse($usuario, 'Precondición: el usuario semilla debe existir.');

        $antes = $this->modelo->obtener_estado_sesion($usuario['id_usuario'], self::ID_INSTITUCION);

        $this->modelo->actualizar($usuario['id_usuario'], self::ID_INSTITUCION, [
            'hash_contrasena' => password_hash('OtraClave@2026!', PASSWORD_BCRYPT),
        ]);

        $despues = $this->modelo->obtener_estado_sesion($usuario['id_usuario'], self::ID_INSTITUCION);

        $this->assertSame(
            (int) $antes['version_credenciales'] + 1,
            (int) $despues['version_credenciales']
        );
    }

    public function testActualizarSinTocarLaContrasenaNoInvalidaSesiones(): void
    {
        // Editar el teléfono o el cargo no debe expulsar a nadie.
        $usuario = $this->modelo->obtener_por_email(self::CORREO_SEED, self::ID_INSTITUCION);
        $antes = $this->modelo->obtener_estado_sesion($usuario['id_usuario'], self::ID_INSTITUCION);

        $this->modelo->actualizar($usuario['id_usuario'], self::ID_INSTITUCION, [
            'cargo_descripcion' => 'Cargo de prueba',
        ]);

        $despues = $this->modelo->obtener_estado_sesion($usuario['id_usuario'], self::ID_INSTITUCION);

        $this->assertSame(
            (int) $antes['version_credenciales'],
            (int) $despues['version_credenciales']
        );
    }

    public function testUnUsuarioNuevoNaceObligadoACambiarLaContrasena(): void
    {
        // La contraseña inicial la elige un administrador y se entrega por
        // fuera del sistema (WhatsApp, teléfono). Debe cambiarse al entrar.
        $id = $this->modelo->crear([
            'id_institucion' => self::ID_INSTITUCION,
            'nombre_completo' => 'Usuario recien creado',
            'numero_documento' => '778899001',
            'correo_electronico' => 'nace.obligado@local.test',
            'hash_contrasena' => password_hash('Temporal@2026', PASSWORD_BCRYPT),
        ]);

        $usuario = $this->modelo->obtener_por_id($id, self::ID_INSTITUCION);

        $this->assertSame(1, (int) $usuario['debe_cambiar_contrasena']);
    }

    public function testElRestablecimientoPorEnlaceLevantaLaObligacion(): void
    {
        // Si el usuario eligió la contraseña desde su propio correo, ya no hay
        // motivo para volver a pedírsela al entrar.
        $id = $this->modelo->crear([
            'id_institucion' => self::ID_INSTITUCION,
            'nombre_completo' => 'Usuario que restablece',
            'numero_documento' => '778899002',
            'correo_electronico' => 'restablece@local.test',
            'hash_contrasena' => password_hash('Temporal@2026', PASSWORD_BCRYPT),
        ]);

        $token = $this->modelo->generar_token_reset($id, self::ID_INSTITUCION);
        $this->modelo->usar_token_reset($token, 'Elegida@2026');

        $usuario = $this->modelo->obtener_por_id($id, self::ID_INSTITUCION);

        $this->assertSame(0, (int) $usuario['debe_cambiar_contrasena']);
    }

    public function testElSecretoDe2faSobreviveAlViajeDeIdaYVueltaALaBaseDeDatos(): void
    {
        // Regresión: la columna totp_secret era VARCHAR(100) y el secreto
        // cifrado ocupa 128 caracteres. MySQL lo truncaba en silencio al
        // guardarlo y la desencriptación fallaba siempre con "Decryption
        // failed", así que la verificación en dos pasos no podía funcionar.
        // El fallo estuvo oculto porque nada llamaba a habilitar_2fa().
        require_once dirname(__DIR__, 2) . '/lib/encriptacion.php';

        $id = $this->modelo->crear([
            'id_institucion' => self::ID_INSTITUCION,
            'nombre_completo' => 'Usuario con 2FA',
            'numero_documento' => '778899003',
            'correo_electronico' => 'con.dosfa@local.test',
            'hash_contrasena' => password_hash('Temporal@2026', PASSWORD_BCRYPT),
        ]);

        $secreto = $this->modelo->preparar_2fa($id, self::ID_INSTITUCION);
        $recuperado = $this->modelo->obtener_secreto_totp($id, self::ID_INSTITUCION);

        $this->assertSame($secreto, $recuperado, 'El secreto no sobrevivió intacto.');

        // Y con él se puede validar un código real, que es lo que hará la app.
        $codigo = Encriptacion::generar_codigo_totp($recuperado);
        $this->assertTrue(Encriptacion::validar_totp($recuperado, $codigo));
    }

    public function testPrepararDosFactoresNoLoActivaTodavia(): void
    {
        // Si activase de inmediato, quien abandonara a mitad de la
        // configuración quedaría fuera de su propia cuenta sin forma de entrar.
        $id = $this->modelo->crear([
            'id_institucion' => self::ID_INSTITUCION,
            'nombre_completo' => 'Usuario a medias',
            'numero_documento' => '778899004',
            'correo_electronico' => 'a.medias@local.test',
            'hash_contrasena' => password_hash('Temporal@2026', PASSWORD_BCRYPT),
        ]);

        $this->modelo->preparar_2fa($id, self::ID_INSTITUCION);
        $usuario = $this->modelo->obtener_por_id($id, self::ID_INSTITUCION);
        $this->assertSame(0, (int) $usuario['requiere_2fa'], 'No debe activarse hasta confirmar.');

        $this->modelo->confirmar_2fa($id, self::ID_INSTITUCION);
        $usuario = $this->modelo->obtener_por_id($id, self::ID_INSTITUCION);
        $this->assertSame(1, (int) $usuario['requiere_2fa']);
    }
}
