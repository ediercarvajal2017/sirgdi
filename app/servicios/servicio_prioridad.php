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
        $r = $this->revisar_sla($id_reporte, $id_institucion);
        return $r['escalado'];
    }

    /**
     * Revisa el SLA de un reporte: escala la urgencia si procede y avisa.
     *
     * Antes, escalar y avisar eran la misma cosa: solo se notificaba cuando la
     * urgencia subía. Eso dejaba fuera justo el caso más grave — un reporte que
     * ya estaba en URGENTE y además incumplía su SLA no generaba ningún aviso,
     * porque no había nada que escalar. En producción había reportes vencidos
     * por más de 600 horas sin que se avisara a nadie.
     *
     * El aviso se manda una sola vez por reporte y por estado de SLA. La marca
     * se guarda en registro_auditoria, que ya existe y es visible en la
     * aplicación, en lugar de añadir una tabla nueva solo para esto.
     *
     * @return array [estado_sla, escalado, avisado]
     */
    public function revisar_sla($id_reporte, $id_institucion) {
        require_once APP_PATH . '/modelos/modelo_sla.php';
        require_once APP_PATH . '/servicios/servicio_auditoria.php';
        $modelo_sla = new ModeloSLA();

        $resultado = ['estado_sla' => null, 'escalado' => false, 'avisado' => false];

        $reporte = $this->modelo_reporte->obtener_por_id($id_reporte, $id_institucion);
        if (!$reporte) {
            return $resultado;
        }

        $sla_info = $modelo_sla->calcular_vencimiento($reporte);
        $estado = $sla_info['estado_sla'] ?? null;
        $resultado['estado_sla'] = $estado;

        if ($estado !== 'cerca' && $estado !== 'vencido') {
            return $resultado;
        }

        $razon = $estado === 'vencido' ? 'SLA vencido' : 'SLA por vencer';

        // 1) Escalar la urgencia, si todavía no está al máximo.
        if ($reporte['id_urgencia_calculada'] != URGENCIA_URGENTE) {
            $this->modelo_reporte->actualizar($id_reporte, $id_institucion, [
                'id_urgencia_calculada' => URGENCIA_URGENTE,
            ]);
            $this->registrar_escalacion(
                $id_reporte, $id_institucion,
                $reporte['id_urgencia_calculada'], URGENCIA_URGENTE, $razon
            );
            $resultado['escalado'] = true;
        }

        // 2) Avisar, una sola vez por estado de SLA.
        $accion_aviso = 'aviso_sla_' . $estado;
        if ($this->ya_avisado($id_reporte, $id_institucion, $accion_aviso)) {
            return $resultado;
        }

        if ($estado === 'vencido') {
            $this->servicio_notificacion->notificar_sla_vencido(
                $id_reporte, $id_institucion, $reporte['numero_ticket']
            );
        } else {
            $this->servicio_notificacion->notificar_sla_vencimiento_proximo(
                $id_reporte, $id_institucion, $reporte['numero_ticket']
            );
        }

        ServicioAuditoria::registrar(
            $accion_aviso, 'reporte', $id_reporte, null,
            ['numero_ticket' => $reporte['numero_ticket'], 'estado_sla' => $estado],
            ['id_usuario' => null, 'id_institucion' => $id_institucion]
        );

        $resultado['avisado'] = true;
        return $resultado;
    }

    /** ¿Ya se envió este aviso para este reporte? */
    private function ya_avisado($id_reporte, $id_institucion, $accion) {
        $fila = $this->bd->obtener_uno(
            'SELECT 1 AS existe FROM registro_auditoria
             WHERE accion = :accion AND entidad = :entidad
               AND id_entidad = :id AND id_institucion = :inst
             LIMIT 1',
            [
                ':accion'  => $accion,
                ':entidad' => 'reporte',
                ':id'      => $id_reporte,
                ':inst'    => $id_institucion,
            ]
        );

        return !empty($fila);
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
        // Antes esto insertaba en una tabla `escalacion_urgencia` que nunca
        // llegó a crearse (no está en el esquema ni en producción), y el fallo
        // se tragaba con @error_log: las escalaciones no dejaban rastro alguno.
        // Se registran en registro_auditoria, que sí existe, ya se usa y tiene
        // pantalla propia en la aplicación, así que el gestor puede verlas.
        require_once APP_PATH . '/servicios/servicio_auditoria.php';

        ServicioAuditoria::registrar(
            'escalar_urgencia',
            'reporte',
            $id_reporte,
            ['id_urgencia_calculada' => $urgencia_anterior],
            ['id_urgencia_calculada' => $urgencia_nueva, 'razon' => $razon],
            // El cron no tiene sesión: la escalación es del sistema, no de una persona.
            ['id_usuario' => null, 'id_institucion' => $id_institucion]
        );
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
