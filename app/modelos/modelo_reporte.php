<?php
// Modelo Reporte (Entidad central - ciclo de vida completo)
// RN-02: Campos obligatorios (sede, area, categoria, descripcion)
// RN-07: Ticket único por institución
// RN-11: UUID para seguimiento público (sin auth)

class ModeloReporte {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtener();
    }

    /**
     * Obtener reporte por ID (RN-01: con filtro id_institucion)
     */
    public function obtener_por_id($id_reporte, $id_institucion) {
        $sql = 'SELECT * FROM reporte
                WHERE id_reporte = :id_reporte
                AND id_institucion = :id_institucion';

        return $this->bd->obtener_uno($sql, [
            ':id_reporte' => $id_reporte,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Reporte con los nombres de sede, categoría, subcategoría, urgencia y estado
     * ya resueltos (para pantallas que deben mostrarlo sin más consultas).
     */
    public function obtener_detallado($id_reporte, $id_institucion) {
        $sql = 'SELECT r.*,
                       s.nombre  AS sede_nombre,
                       c.nombre  AS categoria_nombre,
                       sc.nombre AS subcategoria_nombre,
                       u.nombre  AS urgencia_nombre,
                       e.nombre  AS estado_nombre
                FROM reporte r
                LEFT JOIN sede         s  ON s.id_sede = r.id_sede
                LEFT JOIN categoria    c  ON c.id_categoria = r.id_categoria
                LEFT JOIN subcategoria sc ON sc.id_subcategoria = r.id_subcategoria
                LEFT JOIN urgencia     u  ON u.id_urgencia = r.id_urgencia_calculada
                LEFT JOIN estado       e  ON e.id_estado = r.id_estado
                WHERE r.id_reporte = :id_reporte AND r.id_institucion = :id_institucion';

        return $this->bd->obtener_uno($sql, [
            ':id_reporte' => $id_reporte,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Eliminar un reporte y todos sus registros relacionados (transacción).
     * Preserva la base de conocimiento (plantilla_solucion) poniendo su origen en NULL.
     * Devuelve las rutas de archivos de evidencia para que el llamador las borre del disco.
     */
    public function eliminar($id_reporte, $id_institucion) {
        // Verificar pertenencia (aislamiento multitenant RN-01)
        $reporte = $this->obtener_por_id($id_reporte, $id_institucion);
        if (!$reporte) {
            throw new Exception('Reporte no encontrado.');
        }

        // Recolectar archivos de evidencia para borrarlos del disco tras el commit
        $evidencias = $this->bd->obtener_todos(
            'SELECT url_archivo FROM evidencia WHERE id_reporte = :id',
            [':id' => $id_reporte]
        );
        $archivos = array_column($evidencias ?? [], 'url_archivo');

        $this->bd->transaccion(function ($bd) use ($id_reporte) {
            // Preservar la base de conocimiento: desvincular en vez de borrar
            $bd->actualizar('plantilla_solucion', ['id_reporte_origen' => null],
                'id_reporte_origen = :id', [':id' => $id_reporte]);

            // Borrar registros hijos
            foreach (['comentario_interno', 'encuesta_satisfaccion', 'evidencia',
                      'informe_intervencion', 'notificacion', 'transicion_estado'] as $tabla) {
                $bd->eliminar($tabla, 'id_reporte = :id', [':id' => $id_reporte]);
            }

            // Borrar el reporte
            $bd->eliminar('reporte', 'id_reporte = :id', [':id' => $id_reporte]);
        });

        return $archivos;
    }

    /**
     * Obtener reporte por número de ticket (RN-07)
     */
    public function obtener_por_numero_ticket($numero_ticket, $id_institucion) {
        $sql = 'SELECT * FROM reporte
                WHERE numero_ticket = :numero_ticket
                AND id_institucion = :id_institucion';

        return $this->bd->obtener_uno($sql, [
            ':numero_ticket' => $numero_ticket,
            ':id_institucion' => $id_institucion,
        ]);
    }

    /**
     * Obtener reporte por token de seguimiento público (RN-11, sin auth)
     */
    public function obtener_por_token_seguimiento($token) {
        $sql = 'SELECT * FROM reporte
                WHERE token_seguimiento_publico = :token';

        return $this->bd->obtener_uno($sql, [
            ':token' => $token,
        ]);
    }

    /**
     * Listar reportes de una institución (con filtros opcionales)
     */
    public function listar_por_institucion($id_institucion, $filtros = [], $limite = 50, $offset = 0) {
        // El JOIN con categoria no es decorativo: la vista del listado pinta
        // nombre_categoria y, al no venir nunca, caía al id y mostraba un
        // número crudo en la columna "Categoría".
        $sql = 'SELECT r.*, c.nombre AS nombre_categoria
                  FROM reporte r
                  LEFT JOIN categoria c
                    ON c.id_categoria = r.id_categoria
                   AND c.id_institucion = r.id_institucion
                 WHERE r.id_institucion = :id_institucion';
        $parametros = [':id_institucion' => $id_institucion];

        // Filtro por estado
        if (!empty($filtros['id_estado'])) {
            $sql .= ' AND r.id_estado = :id_estado';
            $parametros[':id_estado'] = $filtros['id_estado'];
        }

        // Filtro por categoría
        if (!empty($filtros['id_categoria'])) {
            $sql .= ' AND r.id_categoria = :id_categoria';
            $parametros[':id_categoria'] = $filtros['id_categoria'];
        }

        // Filtro por urgencia
        if (!empty($filtros['id_urgencia'])) {
            $sql .= ' AND r.id_urgencia_calculada = :id_urgencia';
            $parametros[':id_urgencia'] = $filtros['id_urgencia'];
        }

        // Filtro por reportante
        if (!empty($filtros['id_reportante'])) {
            $sql .= ' AND r.id_reportante = :id_reportante';
            $parametros[':id_reportante'] = $filtros['id_reportante'];
        }

        // Filtro por rango de fechas
        if (!empty($filtros['fecha_desde'])) {
            $sql .= ' AND r.fecha_hora_registro >= :fecha_desde';
            $parametros[':fecha_desde'] = $filtros['fecha_desde'] . ' 00:00:00';
        }

        if (!empty($filtros['fecha_hasta'])) {
            $sql .= ' AND r.fecha_hora_registro <= :fecha_hasta';
            $parametros[':fecha_hasta'] = $filtros['fecha_hasta'] . ' 23:59:59';
        }

        // Ordenamiento y paginación
        $sql .= ' ORDER BY r.fecha_hora_registro DESC LIMIT :limite OFFSET :offset';
        $parametros[':limite'] = $limite;
        $parametros[':offset'] = $offset;

        return $this->bd->obtener_todos($sql, $parametros);
    }

    /**
     * Listar reportes asignados a un técnico
     */
    public function listar_por_tecnico($id_tecnico, $id_institucion, $id_estado = null) {
        $sql = 'SELECT * FROM reporte
                WHERE id_tecnico_asignado = :id_tecnico
                AND id_institucion = :id_institucion';
        $parametros = [
            ':id_tecnico' => $id_tecnico,
            ':id_institucion' => $id_institucion,
        ];

        if ($id_estado) {
            $sql .= ' AND id_estado = :id_estado';
            $parametros[':id_estado'] = $id_estado;
        }

        $sql .= ' ORDER BY fecha_hora_registro DESC';

        return $this->bd->obtener_todos($sql, $parametros);
    }

    /**
     * Crear reporte (RF-06: nueva solicitud)
     * RN-02: Valida campos obligatorios
     * RN-07: Genera número de ticket único
     * RN-11: Genera UUID para seguimiento público
     */
    public function crear($datos) {
        // Validar campos obligatorios (RN-02)
        $campos_obligatorios = ['id_institucion', 'id_sede', 'id_categoria', 'descripcion_problema'];
        foreach ($campos_obligatorios as $campo) {
            if (empty($datos[$campo])) {
                throw new Exception("Campo obligatorio faltante: $campo");
            }
        }

        // Validar que se proporcione ubicación: ya sea id_area o referencia_ubicacion_libre
        if (empty($datos['id_area']) && empty($datos['referencia_ubicacion_libre'])) {
            throw new Exception("Debe proporcionar ubicación (área o referencia).");
        }

        // Generar número de ticket único (RN-07)
        $numero_ticket = $this->generar_numero_ticket($datos['id_institucion']);
        $datos['numero_ticket'] = $numero_ticket;

        // Generar UUID para seguimiento público (RN-11)
        $datos['token_seguimiento_publico'] = $this->generar_uuid();

        // Timestamps
        $datos['fecha_hora_registro'] = date('Y-m-d H:i:s');

        // Estado inicial: Registrado (id=1)
        $datos['id_estado'] = ESTADO_REGISTRADO;

        // Si no viene urgencia, usar la declarada o default No urgente
        if (empty($datos['id_urgencia_declarada'])) {
            $datos['id_urgencia_declarada'] = URGENCIA_NO_URGENTE;
        }

        // Calcular urgencia: si categoría es crítica, escalara a URGENTE (RN-06)
        $modelo_categoria = new ModeloCategoria();
        if ($modelo_categoria->es_critica($datos['id_categoria'], $datos['id_institucion'])) {
            $datos['id_urgencia_calculada'] = URGENCIA_URGENTE;
        } else {
            $datos['id_urgencia_calculada'] = $datos['id_urgencia_declarada'];
        }

        return $this->bd->insertar('reporte', $datos);
    }

    /**
     * Actualizar reporte
     */
    public function actualizar($id_reporte, $id_institucion, $datos) {
        $datos['fecha_actualizacion'] = date('Y-m-d H:i:s');

        $where = 'id_reporte = :id_reporte AND id_institucion = :id_institucion';
        $parametros_where = [
            ':id_reporte' => $id_reporte,
            ':id_institucion' => $id_institucion,
        ];

        return $this->bd->actualizar('reporte', $datos, $where, $parametros_where);
    }

    /**
     * Cambiar estado del reporte (con registro en transicion_estado)
     */
    public function cambiar_estado($id_reporte, $id_institucion, $id_estado_nuevo, $justificacion = '', $id_usuario = null) {
        // Obtener estado actual
        $reporte = $this->obtener_por_id($id_reporte, $id_institucion);
        if (!$reporte) {
            throw new Exception('Reporte no encontrado.');
        }

        $id_estado_actual = intval($reporte['id_estado']);
        $id_estado_nuevo = intval($id_estado_nuevo);

        // RN-12: validar que la transición sea una de las permitidas (o que el estado no cambie).
        // Única fuente de verdad: todos los flujos (asignación, intervención, cierre, cambio
        // manual de estado) pasan por este método, así que la regla se aplica de forma uniforme.
        if ($id_estado_actual !== $id_estado_nuevo) {
            $destinos_validos = TRANSICIONES_ESTADO_REPORTE[$id_estado_actual] ?? [];
            if (!in_array($id_estado_nuevo, $destinos_validos, true)) {
                throw new Exception('Transición de estado no válida.');
            }
        }

        // Actualizar estado y, con él, las marcas de tiempo del ciclo de vida.
        //
        // Las cuatro columnas existen en el esquema desde el principio y no las
        // escribía nadie. Dos consecuencias medibles: la línea de tiempo que ve
        // el ciudadano decía "Pendiente — Cierre formal" hasta en los reportes
        // ya cerrados, y el indicador de días de resolución se calculaba sobre
        // fecha_actualizacion, que lleva ON UPDATE CURRENT_TIMESTAMP y por
        // tanto se mueve con cualquier edición posterior al cierre.
        //
        // Se escriben aquí porque todos los flujos —asignar, intervenir,
        // validar, cerrar, cambio manual— pasan por este método.
        $campos = ['id_estado' => $id_estado_nuevo];
        $ahora  = date('Y-m-d H:i:s');

        // fecha_hora_inicio_tecnico NO se escribe aquí a propósito. Asignar un
        // reporte ya lo pasa a En Proceso sin esperar al técnico (ver el
        // comentario en ControladorGestion::procesar_asignar), así que ponerla
        // en esta transición la dejaría siempre igual a la de asignación y la
        // línea de tiempo mostraría dos hitos con la misma hora. Se escribe
        // cuando el técnico abre de verdad la hoja de trabajo.

        if ($id_estado_nuevo === ESTADO_SOLUCIONADO) {
            // Esta sí se sobrescribe: si la solución se devolvió y se rehízo,
            // la que cuenta para el tiempo de resolución es la última.
            $campos['fecha_hora_solucionado'] = $ahora;
        }

        if ($id_estado_nuevo === ESTADO_CERRADO || $id_estado_nuevo === ESTADO_ANULADO) {
            // Anulado también termina el reporte. Sin esta marca no habría
            // forma de saber cuándo dejó de estar abierto.
            $campos['fecha_hora_cierre'] = $ahora;
        }

        $this->actualizar($id_reporte, $id_institucion, $campos);

        // Registrar transición en BD (auditoría)
        $this->registrar_transicion_estado(
            $id_reporte,
            $id_institucion,
            $reporte['id_estado'],
            $id_estado_nuevo,
            $justificacion,
            $id_usuario
        );

        return true;
    }

    /**
     * Registrar transición de estado (auditoría)
     */
    private function registrar_transicion_estado($id_reporte, $id_institucion, $estado_anterior, $id_estado_nuevo, $justificacion = '', $id_usuario = null) {
        $datos_transicion = [
            'id_reporte' => $id_reporte,
            'id_institucion' => $id_institucion,
            'id_estado_origen' => $estado_anterior,
            'id_estado_destino' => $id_estado_nuevo,
            'comentario' => $justificacion,
            'id_usuario_ejecutor' => $id_usuario ?? ($_SESSION['id_usuario'] ?? null),
            'ip_origen' => $_SERVER['REMOTE_ADDR'] ?? null,
            'fecha_hora_transicion' => date('Y-m-d H:i:s'),
        ];

        $this->bd->insertar('transicion_estado', $datos_transicion);
    }

    /**
     * Asignar técnico a un reporte.
     *
     * Guarda también quién asignó. La columna id_gestor_asignador existía sin
     * que nadie la escribiera, así que el registro de auditoría era el único
     * sitio donde constaba, y desde el reporte no se podía saber.
     */
    public function asignar_tecnico($id_reporte, $id_institucion, $id_tecnico, $id_gestor = null) {
        return $this->actualizar($id_reporte, $id_institucion, [
            'id_tecnico_asignado'   => $id_tecnico,
            'id_gestor_asignador'   => $id_gestor ?? ($_SESSION['id_usuario'] ?? null),
            'fecha_hora_asignacion' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Remover asignación de técnico
     */
    public function remover_tecnico($id_reporte, $id_institucion) {
        return $this->actualizar($id_reporte, $id_institucion, [
            'id_tecnico_asignado' => null,
        ]);
    }

    /**
     * Pausar SLA (RN-10: cuando reporte se devuelve)
     */
    public function pausar_sla($id_reporte, $id_institucion) {
        return $this->actualizar($id_reporte, $id_institucion, [
            'fecha_pausa_sla' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Reanudar SLA (cuando se retoma el trabajo)
     */
    public function reanudar_sla($id_reporte, $id_institucion) {
        return $this->actualizar($id_reporte, $id_institucion, [
            'fecha_pausa_sla' => null,
        ]);
    }

    /**
     * Contar reportes por estado (para dashboard)
     */
    public function contar_por_estado($id_institucion, $id_estado) {
        $sql = 'SELECT COUNT(*) as total FROM reporte
                WHERE id_institucion = :id_institucion
                AND id_estado = :id_estado';

        return intval($this->bd->obtener_valor($sql, [
            ':id_institucion' => $id_institucion,
            ':id_estado' => $id_estado,
        ]));
    }

    /**
     * Contar reportes por urgencia
     */
    public function contar_por_urgencia($id_institucion, $id_urgencia) {
        $sql = 'SELECT COUNT(*) as total FROM reporte
                WHERE id_institucion = :id_institucion
                AND id_urgencia_calculada = :id_urgencia';

        return intval($this->bd->obtener_valor($sql, [
            ':id_institucion' => $id_institucion,
            ':id_urgencia' => $id_urgencia,
        ]));
    }

    // ===== HELPERS =====

    /**
     * Generar número de ticket único por institución (RN-07)
     * Formato: SIR-{año}{número secuencial}
     * Ej: SIR-202600001
     */
    private function generar_numero_ticket($id_institucion) {
        $anio = date('Y');
        $sql = 'SELECT MAX(CAST(SUBSTRING(numero_ticket, 9) AS UNSIGNED)) as ultimo_numero
                FROM reporte
                WHERE id_institucion = :id_institucion
                AND numero_ticket LIKE :patron';

        $patron = 'SIR-' . $anio . '%';
        $resultado = $this->bd->obtener_uno($sql, [
            ':id_institucion' => $id_institucion,
            ':patron' => $patron,
        ]);

        $siguiente = ($resultado['ultimo_numero'] ?? 0) + 1;
        return TICKET_PREFIX . '-' . $anio . str_pad($siguiente, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generar UUID v4 (RFC 4122) para seguimiento público (RN-11)
     */
    private function generar_uuid() {
        $bytes = random_bytes(16);
        // Version 4 (random)
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        // Variant 1
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return implode('-', [
            bin2hex(substr($bytes, 0, 4)),
            bin2hex(substr($bytes, 4, 2)),
            bin2hex(substr($bytes, 6, 2)),
            bin2hex(substr($bytes, 8, 2)),
            bin2hex(substr($bytes, 10, 6))
        ]);
    }
}
