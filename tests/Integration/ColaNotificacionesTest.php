<?php

require_once dirname(__DIR__, 2) . '/app/servicios/servicio_notificacion.php';

/**
 * Cubre la cola de reintentos de notificaciones.
 *
 * Antes, si el SMTP fallaba en el momento del envío, la fila quedaba en
 * 'pendiente' para siempre y nadie volvía a intentarlo: el aviso se perdía sin
 * rastro. Estas pruebas fijan el comportamiento de la máquina de estados para
 * que no se pueda volver atrás sin darse cuenta.
 *
 * No se prueba el envío SMTP real: eso dependería de la red y del proveedor.
 * Se prueba el registro de intentos y la selección de la cola, que es la parte
 * que se perdía.
 */
final class ColaNotificacionesTest extends BaseDbTestCase
{
    private ServicioNotificacion $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new ServicioNotificacion();
    }

    /** Crea una notificación pendiente y devuelve su id. */
    private function crearPendiente(int $intentos = 0): int
    {
        return (int) $this->bd->insertar('notificacion', [
            'id_institucion'          => self::ID_INSTITUCION,
            'id_usuario_destinatario' => self::ID_USUARIO,
            'tipo_evento'             => 'prueba_cola',
            'asunto'                  => 'Asunto de prueba',
            'cuerpo_html'             => '<p>Cuerpo de prueba</p>',
            'estado_envio'            => 'pendiente',
            'intentos'                => $intentos,
        ]);
    }

    private function leer(int $id): array
    {
        return $this->bd->obtener_uno(
            'SELECT estado_envio, intentos, razon_fallo FROM notificacion WHERE id_notificacion = :id',
            [':id' => $id]
        );
    }

    public function testCadaFalloIncrementaLosIntentosYGuardaElMotivo(): void
    {
        $id = $this->crearPendiente();

        $this->servicio->registrar_fallo($id, 'SMTP caído');
        $fila = $this->leer($id);

        $this->assertSame(1, (int) $fila['intentos']);
        $this->assertSame('SMTP caído', $fila['razon_fallo']);
        $this->assertSame('pendiente', $fila['estado_envio'], 'Aún debe poder reintentarse.');
    }

    public function testAlAgotarLosIntentosLaNotificacionPasaAFallido(): void
    {
        $id = $this->crearPendiente(ServicioNotificacion::MAX_INTENTOS_ENVIO - 1);

        $this->servicio->registrar_fallo($id, 'Último intento');
        $fila = $this->leer($id);

        $this->assertSame(ServicioNotificacion::MAX_INTENTOS_ENVIO, (int) $fila['intentos']);
        $this->assertSame('fallido', $fila['estado_envio']);
    }

    public function testLaColaIgnoraLasNotificacionesYaAgotadas(): void
    {
        // Una fila que ya alcanzó el máximo no debe volver a intentarse:
        // de lo contrario el cron reintentaría para siempre una dirección
        // inválida, en cada ejecución.
        //
        // La cola mira toda la tabla, así que primero se vacía de pendientes
        // dentro de la transacción de la prueba. Sin esto, el resultado
        // dependería de lo que hubiera en la base de desarrollo en ese momento
        // (y además intentaría enviar correo de verdad).
        $this->bd->ejecutar(
            "UPDATE notificacion SET estado_envio = 'enviado' WHERE estado_envio = 'pendiente'"
        );

        $this->bd->insertar('notificacion', [
            'id_institucion'          => self::ID_INSTITUCION,
            'id_usuario_destinatario' => self::ID_USUARIO,
            'tipo_evento'             => 'prueba_cola',
            'asunto'                  => 'Ya agotada',
            'cuerpo_html'             => '<p>x</p>',
            'estado_envio'            => 'fallido',
            'intentos'                => ServicioNotificacion::MAX_INTENTOS_ENVIO,
        ]);

        $resumen = $this->servicio->reintentar_pendientes(50);

        $this->assertSame(0, $resumen['procesadas']);
    }

    public function testElMotivoDelFalloSeRecortaYNoRompeLaColumna(): void
    {
        $id = $this->crearPendiente();

        $this->servicio->registrar_fallo($id, str_repeat('x', 5000));
        $fila = $this->leer($id);

        $this->assertLessThanOrEqual(1000, mb_strlen($fila['razon_fallo']));
    }

    public function testLosAvisosDemasiadoViejosCaducanEnVezDeEnviarse(): void
    {
        // Entregar un "reporte listo para validación" tres días tarde confunde
        // más de lo que ayuda: para entonces el reporte ya se movió. Se cierra
        // como fallido con el motivo visible, sin enviarlo.
        $this->bd->ejecutar(
            "UPDATE notificacion SET estado_envio = 'enviado' WHERE estado_envio = 'pendiente'"
        );

        $id = $this->crearPendiente();
        $this->bd->ejecutar(
            'UPDATE notificacion SET fecha_programada = DATE_SUB(NOW(), INTERVAL :h HOUR)
             WHERE id_notificacion = :id',
            [
                ':h'  => ServicioNotificacion::HORAS_CADUCIDAD_AVISO + 1,
                ':id' => $id,
            ]
        );

        $resumen = $this->servicio->reintentar_pendientes(50);
        $fila = $this->leer($id);

        $this->assertSame(1, $resumen['caducadas']);
        $this->assertSame(0, $resumen['enviadas']);
        $this->assertSame('fallido', $fila['estado_envio']);
        $this->assertStringContainsString('Caducada', $fila['razon_fallo']);
        $this->assertSame(0, (int) $fila['intentos'], 'No se gastó ningún intento de envío.');
    }
}
