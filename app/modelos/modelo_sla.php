<?php
// Modelo SLA (Configuración de SLA por categoría y urgencia)
// RN-13: SLA alerta cuando falta <1h
// RN-10: SLA se pausa en estado Devuelto

class ModeloSLA {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtener();
    }

    /**
     * Obtener configuración SLA por categoría e institución
     */
    public function obtener_por_categoria($id_categoria, $id_institucion) {
        $sql = 'SELECT * FROM sla
                WHERE id_categoria = :id_categoria
                AND id_institucion = :id_institucion
                AND activo = 1';

        return $this->bd->obtener_uno($sql, [
            ':id_categoria' => $id_categoria,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Obtener SLA por urgencia (fallback si no existe SLA por categoría)
     */
    public function obtener_por_urgencia($id_urgencia, $id_institucion) {
        $sql = 'SELECT * FROM sla
                WHERE id_urgencia = :id_urgencia
                AND id_institucion = :id_institucion
                AND id_categoria IS NULL
                AND activo = 1';

        return $this->bd->obtener_uno($sql, [
            ':id_urgencia' => $id_urgencia,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Listar todas las configuraciones SLA de una institución
     */
    public function listar_por_institucion($id_institucion) {
        $sql = 'SELECT * FROM sla
                WHERE id_institucion = :id_institucion
                AND activo = 1
                ORDER BY tiempo_resolucion_horas DESC';

        return $this->bd->obtener_todos($sql, [
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Crear configuración SLA
     */
    public function crear($datos) {
        if (!isset($datos['id_institucion']) || !isset($datos['tiempo_resolucion_horas'])) {
            throw new Exception('id_institucion y tiempo_resolucion_horas son requeridos.');
        }

        return $this->bd->insertar('sla', $datos);
    }

    /**
     * Actualizar SLA
     */
    public function actualizar($id_sla, $id_institucion, $datos) {
        $where = 'id_sla = :id_sla AND id_institucion = :id_inst';
        $parametros = [
            ':id_sla' => $id_sla,
            ':id_inst' => $id_institucion,
        ];

        return $this->bd->actualizar('sla', $datos, $where, $parametros);
    }

    /**
     * Eliminar SLA (soft delete)
     */
    public function eliminar($id_sla, $id_institucion) {
        return $this->actualizar($id_sla, $id_institucion, [
            'activo' => 0,
        ]);
    }

    /**
     * Calcular fecha de vencimiento de SLA para un reporte
     * Toma en cuenta pausa (RN-10)
     * Retorna: ["fecha_vencimiento" => "2026-06-19 10:30:00", "horas_restantes" => 5.5, "estado_sla" => "en_tiempo|cerca|vencido"]
     */
    public function calcular_vencimiento($reporte) {
        // Obtener configuración SLA
        $sla = $this->obtener_por_categoria($reporte['id_categoria'], $reporte['id_institucion']);

        if (!$sla) {
            // Fallback a urgencia
            $sla = $this->obtener_por_urgencia($reporte['id_urgencia_calculada'], $reporte['id_institucion']);
        }

        if (!$sla) {
            // Default: 48 horas (emergency fallback)
            $horas_sla = 48;
        } else {
            $horas_sla = intval($sla['tiempo_resolucion_horas']);
        }

        // Calcular tiempo transcurrido (excluyendo pausa RN-10)
        $fecha_registro = new DateTime($reporte['fecha_hora_registro']);
        $ahora = new DateTime();

        $tiempo_total = $fecha_registro->diff($ahora);
        $horas_transcurridas = ($tiempo_total->d * 24) + $tiempo_total->h + ($tiempo_total->i / 60);

        // Si hay pausa SLA, restar ese tiempo
        if ($reporte['fecha_pausa_sla']) {
            $fecha_pausa = new DateTime($reporte['fecha_pausa_sla']);
            $tiempo_pausa = $fecha_pausa->diff($ahora);
            $horas_pausa = ($tiempo_pausa->d * 24) + $tiempo_pausa->h + ($tiempo_pausa->i / 60);

            // No restar más de lo transcurrido
            $horas_pausa = min($horas_pausa, $horas_transcurridas);
            $horas_transcurridas -= $horas_pausa;
        }

        // Calcular vencimiento
        $horas_restantes = $horas_sla - $horas_transcurridas;
        $fecha_vencimiento = clone $fecha_registro;
        $fecha_vencimiento->add(new DateInterval('PT' . intval($horas_sla) . 'H'));

        // Si hay pausa, ajustar fecha de vencimiento
        if ($reporte['fecha_pausa_sla']) {
            $fecha_pausa = new DateTime($reporte['fecha_pausa_sla']);
            $ahora = new DateTime();
            $diferencia_pausa = $ahora->diff($fecha_pausa);
            $fecha_vencimiento->add($diferencia_pausa);
        }

        // Determinar estado del SLA
        $estado_sla = 'en_tiempo';
        if ($horas_restantes < 0) {
            $estado_sla = 'vencido';
        } elseif ($horas_restantes <= 1) {
            $estado_sla = 'cerca'; // RN-13: Alerta a <1h
        }

        return [
            'fecha_vencimiento' => $fecha_vencimiento->format('Y-m-d H:i:s'),
            'horas_restantes' => round($horas_restantes, 2),
            'estado_sla' => $estado_sla,
            'horas_slaurado' => $horas_sla,
            'horas_transcurridas' => round($horas_transcurridas, 2),
        ];
    }

    /**
     * Listar reportes cuyo SLA está por vencer (<1h) - para alertas
     */
    public function reportes_sla_por_vencer($id_institucion) {
        // Esta lógica requiere cálculo, así que se hace en PHP (no es una query pura)
        // En producción, considerar usar trigger/event de MySQL para esto

        $modelo_reporte = new ModeloReporte();
        $reportes_activos = $modelo_reporte->listar_por_institucion($id_institucion, [], 1000, 0);

        $reportes_por_vencer = [];
        foreach ($reportes_activos as $reporte) {
            if (in_array($reporte['id_estado'], [ESTADO_REGISTRADO, ESTADO_EN_PROCESO, ESTADO_DEVUELTO])) {
                $sla_info = $this->calcular_vencimiento($reporte);
                if ($sla_info['estado_sla'] === 'cerca' || $sla_info['estado_sla'] === 'vencido') {
                    $reportes_por_vencer[] = array_merge($reporte, $sla_info);
                }
            }
        }

        return $reportes_por_vencer;
    }

    /**
     * Reportes con SLA vencido
     */
    public function reportes_sla_vencido($id_institucion) {
        $modelo_reporte = new ModeloReporte();
        $reportes_activos = $modelo_reporte->listar_por_institucion($id_institucion, [], 1000, 0);

        $reportes_vencidos = [];
        foreach ($reportes_activos as $reporte) {
            if (in_array($reporte['id_estado'], [ESTADO_REGISTRADO, ESTADO_EN_PROCESO, ESTADO_DEVUELTO])) {
                $sla_info = $this->calcular_vencimiento($reporte);
                if ($sla_info['estado_sla'] === 'vencido') {
                    $reportes_vencidos[] = array_merge($reporte, $sla_info);
                }
            }
        }

        return $reportes_vencidos;
    }
}
