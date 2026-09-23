<?php

require_once dirname(__DIR__, 2) . '/app/servicios/servicio_notificacion.php';

/**
 * Cubre la campana in-app.
 *
 * El punto delicado es que la campana NO debe depender de si el correo salió:
 * su razón de ser es avisar al usuario precisamente cuando el SMTP falla. La
 * versión anterior filtraba por estado_envio, lo que la dejaba al revés.
 */
final class CampanaNotificacionesTest extends BaseDbTestCase
{
    private ServicioNotificacion $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new ServicioNotificacion();

        // Partir de una campana vacía para este usuario, dentro de la
        // transacción de la prueba.
        $this->bd->ejecutar(
            'UPDATE notificacion SET fecha_leida = NOW()
             WHERE id_usuario_destinatario = ? AND id_institucion = ?',
            [self::ID_USUARIO, self::ID_INSTITUCION]
        );
    }

    private function crear(array $campos = []): int
    {
        return (int) $this->bd->insertar('notificacion', array_merge([
            'id_institucion'          => self::ID_INSTITUCION,
            'id_usuario_destinatario' => self::ID_USUARIO,
            'tipo_evento'             => 'prueba_campana',
            'asunto'                  => 'Aviso de prueba',
            'cuerpo_html'             => '<p>x</p>',
            'estado_envio'            => 'enviado',
        ], $campos));
    }

    public function testLaCampanaMuestraLosAvisosAunqueElCorreoHayaFallado(): void
    {
        // Este es el caso que justifica que la campana exista.
        $this->crear(['estado_envio' => 'fallido', 'asunto' => 'El correo no salió']);

        $no_leidas = $this->servicio->obtener_no_leidas(self::ID_USUARIO, self::ID_INSTITUCION);

        $this->assertCount(1, $no_leidas);
        $this->assertSame('El correo no salió', $no_leidas[0]['asunto']);
    }

    public function testCuentaYListaIgnoranLasYaLeidas(): void
    {
        $id = $this->crear();
        $this->crear();

        $this->assertSame(2, $this->servicio->contar_no_leidas(self::ID_USUARIO, self::ID_INSTITUCION));

        $this->servicio->marcar_leida($id, self::ID_USUARIO, self::ID_INSTITUCION);

        $this->assertSame(1, $this->servicio->contar_no_leidas(self::ID_USUARIO, self::ID_INSTITUCION));
        $this->assertCount(1, $this->servicio->obtener_no_leidas(self::ID_USUARIO, self::ID_INSTITUCION));
    }

    public function testNoSePuedeMarcarComoLeidaLaNotificacionDeOtroUsuario(): void
    {
        // RN-01: el id de usuario e institución van en el WHERE, no solo en una
        // comprobación previa. Manipular el id de la URL no debe servir de nada.
        $id = $this->crear();

        $this->servicio->marcar_leida($id, 999999, self::ID_INSTITUCION);
        $this->assertSame(1, $this->servicio->contar_no_leidas(self::ID_USUARIO, self::ID_INSTITUCION));

        $this->servicio->marcar_leida($id, self::ID_USUARIO, 999999);
        $this->assertSame(1, $this->servicio->contar_no_leidas(self::ID_USUARIO, self::ID_INSTITUCION));

        // Con los datos correctos sí funciona.
        $this->servicio->marcar_leida($id, self::ID_USUARIO, self::ID_INSTITUCION);
        $this->assertSame(0, $this->servicio->contar_no_leidas(self::ID_USUARIO, self::ID_INSTITUCION));
    }

    public function testMarcarTodasDejaLaCampanaEnCero(): void
    {
        $this->crear();
        $this->crear();
        $this->crear();

        $this->servicio->marcar_todas_leidas(self::ID_USUARIO, self::ID_INSTITUCION);

        $this->assertSame(0, $this->servicio->contar_no_leidas(self::ID_USUARIO, self::ID_INSTITUCION));
    }

    public function testLosAvisosLleganEnOrdenDelMasRecienteAlMasAntiguo(): void
    {
        $this->crear(['asunto' => 'Antiguo', 'fecha_creacion' => '2026-01-01 08:00:00']);
        $this->crear(['asunto' => 'Reciente', 'fecha_creacion' => '2026-09-01 08:00:00']);

        $no_leidas = $this->servicio->obtener_no_leidas(self::ID_USUARIO, self::ID_INSTITUCION);

        $this->assertSame('Reciente', $no_leidas[0]['asunto']);
    }
}
