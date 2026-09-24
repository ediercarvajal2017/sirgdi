<?php

require_once dirname(__DIR__, 2) . '/app/servicios/servicio_prioridad.php';
require_once dirname(__DIR__, 2) . '/app/modelos/modelo_sla.php';

/**
 * Fija que el tablero no vuelva a preguntar a la base una vez por reporte.
 *
 * El Kanban hacía dos recorridos completos y, dentro de cada uno, una o dos
 * consultas por reporte para resolver su SLA: 2 + 4N consultas por carga de
 * pantalla. Medido en producción, 42 con diez reportes; 4.002 al llegar al
 * tope de mil.
 *
 * Lo que se prueba no es que sea "rápido" —eso depende de la máquina— sino que
 * el coste NO crece con el número de reportes. Esa es la propiedad que se
 * rompe si alguien vuelve a meter una consulta dentro del bucle.
 */
final class TableroEscalaTest extends BaseDbTestCase
{
    private ServicioPrioridad $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new ServicioPrioridad();
    }

    private function consultasHastaAhora(): int
    {
        $fila = $this->bd->obtener_uno("SHOW SESSION STATUS LIKE 'Questions'");
        return (int) $fila['Value'];
    }

    private function sembrar(int $cuantos): void
    {
        $sql = 'INSERT INTO reporte (id_institucion, numero_ticket, token_seguimiento_publico,
                    id_sede, referencia_ubicacion_libre, id_categoria, id_urgencia_declarada,
                    id_urgencia_calculada, descripcion_problema, id_estado, fecha_hora_registro)
                VALUES (?, ?, UUID(), ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? HOUR)';

        for ($i = 0; $i < $cuantos; $i++) {
            $this->bd->ejecutar($sql, [
                self::ID_INSTITUCION,
                'ESC-' . uniqid('', true),
                self::ID_SEDE,
                'siembra de prueba',
                self::ID_CATEGORIA,
                URGENCIA_MODERADO,
                URGENCIA_MODERADO,
                'Reporte sembrado para medir el coste del tablero.',
                ESTADO_REGISTRADO,
                $i % 90,
            ]);
        }
    }

    /** Cuenta las consultas de una carga de tablero, descontando la medición. */
    private function costeDeUnaCarga(): int
    {
        $antes = $this->consultasHastaAhora();
        $items = $this->servicio->listar_por_prioridad(self::ID_INSTITUCION);
        $this->servicio->obtener_estadisticas_prioridad(self::ID_INSTITUCION, $items);
        $despues = $this->consultasHastaAhora();

        return $despues - $antes - 1; // -1: la propia consulta de medición
    }

    public function testElCosteDelTableroNoCreceConElNumeroDeReportes(): void
    {
        $this->sembrar(10);
        $con10 = $this->costeDeUnaCarga();

        $this->sembrar(190);
        $con200 = $this->costeDeUnaCarga();

        $this->assertSame(
            $con10,
            $con200,
            "El tablero costó {$con10} consultas con 10 reportes y {$con200} con 200. "
            . 'Alguien volvió a consultar dentro del bucle.'
        );
    }

    public function testUnaCargaDeTableroCuestaUnPunadoDeConsultas(): void
    {
        $this->sembrar(200);

        $this->assertLessThanOrEqual(
            5,
            $this->costeDeUnaCarga(),
            'Una carga de tablero debe costar unas pocas consultas, no una por reporte.'
        );
    }

    public function testElMapaDeSlaDaLoMismoQueConsultarUnoAUno(): void
    {
        // El atajo tiene que producir exactamente el mismo resultado que la
        // consulta directa, o habremos cambiado los plazos sin querer.
        $modelo = new ModeloSLA();
        $mapa = $modelo->mapa_por_institucion(self::ID_INSTITUCION);

        $reporte = [
            'id_institucion'        => self::ID_INSTITUCION,
            'id_categoria'          => self::ID_CATEGORIA,
            'id_urgencia_calculada' => URGENCIA_MODERADO,
            'fecha_hora_registro'   => date('Y-m-d H:i:s', time() - 3600),
            'fecha_pausa_sla'       => null,
        ];

        $con_mapa = $modelo->calcular_vencimiento($reporte, $mapa);
        $sin_mapa = $modelo->calcular_vencimiento($reporte);

        $this->assertSame($sin_mapa['horas_slaurado'], $con_mapa['horas_slaurado']);
        $this->assertSame($sin_mapa['estado_sla'], $con_mapa['estado_sla']);
    }

    public function testElContadorDePorAsignarNoCuentaLosQueSiTienenTecnico(): void
    {
        // Se leía $reporte['id_tecnico'], columna que no existe —se llama
        // id_tecnico_asignado—, así que la comprobación era siempre cierta y
        // el tablero daba por asignar TODOS los reportes. En producción decía
        // 10 cuando solo 2 estaban realmente sin técnico.
        $this->sembrar(4);

        $this->bd->ejecutar(
            'UPDATE reporte SET id_tecnico_asignado = ?
              WHERE id_institucion = ? AND numero_ticket LIKE ?',
            [self::ID_USUARIO, self::ID_INSTITUCION, 'ESC-%']
        );

        $items = $this->servicio->listar_por_prioridad(self::ID_INSTITUCION);
        $stats = $this->servicio->obtener_estadisticas_prioridad(self::ID_INSTITUCION, $items);

        $sin_tecnico = 0;
        foreach ($items as $item) {
            if (empty($item['reporte']['id_tecnico_asignado'])) $sin_tecnico++;
        }

        $this->assertSame($sin_tecnico, $stats['reportes_por_asignar']);
        $this->assertLessThan($stats['total_reportes'], $stats['reportes_por_asignar']);
    }
}
