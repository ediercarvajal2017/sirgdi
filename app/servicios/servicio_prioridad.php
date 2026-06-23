<?php
// Servicio de Priorización (Auto-Escalación)
// RN-06: Categorías críticas escalan automáticamente a URGENTE

class ServicioPrioridad {
    private $bd;
    private $modelo_reporte;
    private $modelo_categoria;
    private $servicio_notificacion;

    public function __construct() {
        $this->bd = BaseDatos::obtener();
        require_once APP_PATH . '/modelos/modelo_reporte.php';
        require_once APP_PATH . '/modelos/modelo_categoria.php';
        require_once APP_PATH . '/servicios/servicio_notificacion.php';

        $this->modelo_reporte = new ModeloReporte();
        $this->modelo_categoria = new ModeloCategoria();
        $this->servicio_notificacion = new ServicioNotificacion();
    }

    /**
     * Evaluar y aplicar escalación de urgencia para un reporte
     * RN-06: Si categoría es crítica, forzar urgencia a URGENTE
     */
    public function evaluar_escalacion($id_reporte, $id_institucion) {
        $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);

        if (!$reporte) {
            return false;
        }

        // Verificar si categoría es crítica
        $es_critica = $this->modelo_categoria->es_critica($reporte['id_categoria'], $id_institucion);

        if ($es_critica && $reporte['id_urgencia_calculada'] != URGENCIA_URGENTE) {
            // Escalar a URGENTE
            $this->modelo_reporte->actualizar($id_reporte, $id_institucion, [
                'id_urgencia_calculada' => URGENCIA_URGENTE,
            ]);

            // Registrar en auditoría
            $this->registrar_escalacion($id_reporte, $id_institucion, $reporte['id_urgencia_calculada'], URGENCIA_URGENTE, 'Categoría crítica');

            // Notificar a gestor y rector
            $this->servicio_notificacion->notificar_sla_vencimiento_proximo($id_reporte, $id_institucion, $reporte['numero_ticket']);

            return true;
        }

        return false;
    }

    /**
     * Evaluar escalación por SLA próximo a vencer (RN-13)
     * Escalar a URGENTE si SLA está dentro de 1 hora
     */
    public function evaluar_escalacion_sla($id_reporte, $id_institucion) {
        require_once APP_PATH . '/modelos/modelo_sla.php';
        $modelo_sla = new ModeloSLA();

        $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
        if (!$reporte) {
            return false;
        }

        $sla_info = $modelo_sla->calcular_vencimiento($reporte);

        // Si SLA está por vencer o vencido, escalar
        if (($sla_info['estado_sla'] === 'cerca' || $sla_info['estado_sla'] === 'vencido') &&
            $reporte['id_urgencia_calculada'] != URGENCIA_URGENTE) {

            $this->modelo_reporte->actualizar($id_reporte, $id_institucion, [
                'id_urgencia_calculada' => URGENCIA_URGENTE,
            ]);

            $this->registrar_escalacion($id_reporte, $id_institucion, $reporte['id_urgencia_calculada'], URGENCIA_URGENTE, 'SLA por vencer');

            // Notificar
            if ($sla_info['estado_sla'] === 'vencido') {
                $this->servicio_notificacion->notificar_sla_vencido($id_reporte, $id_institucion, $reporte['numero_ticket']);
            } else {
                $this->servicio_notificacion->notificar_sla_vencimiento_proximo($id_reporte, $id_institucion, $reporte['numero_ticket']);
            }

            return true;
        }

        return false;
    }

    /**
     * Calcular prioridad numérica (para ordenamiento)
     * Mayor número = mayor prioridad
     * Factores:
     *   - Urgencia (40% del peso)
     *   - Tiempo en sistema (30%)
     *   - SLA vencido (30%)
     */
    public function calcular_puntuacion_prioridad($reporte, $sla_info = null) {
        $puntuacion = 0;

        // Factor 1: Urgencia (0-40 puntos)
        $puntuacion_urgencia = [
            URGENCIA_NO_URGENTE => 10,
            URGENCIA_MODERADO => 20,
            URGENCIA_IMPORTANTE => 30,
            URGENCIA_URGENTE => 40,
        ];
        $puntuacion += $puntuacion_urgencia[$reporte['id_urgencia_calculada']] ?? 10;

        // Factor 2: Tiempo en sistema (0-30 puntos)
        $fecha_registro = new DateTime($reporte['fecha_hora_registro']);
        $ahora = new DateTime();
        $dias_transcurridos = $fecha_registro->diff($ahora)->d;

        if ($dias_transcurridos >= 7) {
            $puntuacion += 30;
        } elseif ($dias_transcurridos >= 3) {
            $puntuacion += 20;
        } elseif ($dias_transcurridos >= 1) {
            $puntuacion += 10;
        }

        // Factor 3: SLA (0-30 puntos)
        if ($sla_info === null) {
            require_once APP_PATH . '/modelos/modelo_sla.php';
            $modelo_sla = new ModeloSLA();
            $sla_info = $modelo_sla->calcular_vencimiento($reporte);
        }

        if ($sla_info['estado_sla'] === 'vencido') {
            $puntuacion += 30;
        } elseif ($sla_info['estado_sla'] === 'cerca') {
            $puntuacion += 15;
        }

        return min($puntuacion, 100); // Max 100 puntos
    }

    /**
     * Listar reportes ordenados por prioridad
     */
    public function listar_por_prioridad($id_institucion, $filtro_estado = null) {
        $reportes = $this->modelo_reporte->listar_por_institucion($id_institucion, [], 1000, 0);

        require_once APP_PATH . '/modelos/modelo_sla.php';
        $modelo_sla = new ModeloSLA();

        // Calcular puntuación para cada reporte
        $reportes_con_puntuacion = [];
        foreach ($reportes as $reporte) {
            if ($filtro_estado && $reporte['id_estado'] != $filtro_estado) {
                continue;
            }

            $sla_info = $modelo_sla->calcular_vencimiento($reporte);
            $puntuacion = $this->calcular_puntuacion_prioridad($reporte, $sla_info);

            $reportes_con_puntuacion[] = [
                'reporte' => $reporte,
                'sla_info' => $sla_info,
                'puntuacion_prioridad' => $puntuacion,
            ];
        }

        // Ordenar por puntuación (descendente)
        usort($reportes_con_puntuacion, function($a, $b) {
            return $b['puntuacion_prioridad'] <=> $a['puntuacion_prioridad'];
        });

        return $reportes_con_puntuacion;
    }

    /**
     * Registrar escalación en auditoría
     */
    private function registrar_escalacion($id_reporte, $id_institucion, $urgencia_anterior, $urgencia_nueva, $razon) {
        $datos_escalacion = [
            'id_reporte' => $id_reporte,
            'id_institucion' => $id_institucion,
            'urgencia_anterior' => $urgencia_anterior,
            'urgencia_nueva' => $urgencia_nueva,
            'razon_escalacion' => $razon,
            'id_usuario_sistema' => null, // Escalación automática
            'fecha_hora_escalacion' => date('Y-m-d H:i:s'),
        ];

        // Crear tabla de auditoría si no existe (o insertar en auditoría existente)
        $sql = 'INSERT INTO escalacion_urgencia (id_reporte, id_institucion, urgencia_anterior, urgencia_nueva, razon_escalacion, fecha_hora_escalacion)
                VALUES (:id_reporte, :id_institucion, :urgencia_anterior, :urgencia_nueva, :razon, :fecha)';

        try {
            $this->bd->ejecutar($sql, [
                ':id_reporte' => $datos_escalacion['id_reporte'],
                ':id_institucion' => $datos_escalacion['id_institucion'],
                ':urgencia_anterior' => $datos_escalacion['urgencia_anterior'],
                ':urgencia_nueva' => $datos_escalacion['urgencia_nueva'],
                ':razon' => $datos_escalacion['razon_escalacion'],
                ':fecha' => $datos_escalacion['fecha_hora_escalacion'],
            ]);
        } catch (Exception $e) {
            // Log silenciosamente si la tabla no existe aún
            @error_log('Escalación: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas de priorización (para dashboard)
     */
    public function obtener_estadisticas_prioridad($id_institucion) {
        $reportes = $this->modelo_reporte->listar_por_institucion($id_institucion, [], 1000, 0);

        $stats = [
            'total_reportes' => count($reportes),
            'reportes_urgentes' => 0,
            'reportes_sla_vencido' => 0,
            'reportes_sla_por_vencer' => 0,
            'reportes_por_asignar' => 0,
        ];

        require_once APP_PATH . '/modelos/modelo_sla.php';
        $modelo_sla = new ModeloSLA();

        foreach ($reportes as $reporte) {
            if ($reporte['id_urgencia_calculada'] == URGENCIA_URGENTE) {
                $stats['reportes_urgentes']++;
            }

            if (!isset($reporte['id_tecnico']) || !$reporte['id_tecnico']) {
                $stats['reportes_por_asignar']++;
            }

            $sla_info = $modelo_sla->calcular_vencimiento($reporte);
            if ($sla_info['estado_sla'] === 'vencido') {
                $stats['reportes_sla_vencido']++;
            } elseif ($sla_info['estado_sla'] === 'cerca') {
                $stats['reportes_sla_por_vencer']++;
            }
        }

        return $stats;
    }
}
