<?php
// Modelo Avance de intervención — bitácora del técnico por reporte.
//
// Es la nota breve con fecha que el técnico deja mientras trabaja ("Falta material,
// regreso mañana"). A diferencia de comentario_interno (hilo privado gestor/técnico),
// estos avances se muestran al reportante en el seguimiento público.

class ModeloAvance {
    private $bd;

    public function __construct() {
        $this->bd = BaseDatos::obtener();
    }


    public function crear($datos) {
        return $this->bd->insertar('avance_intervencion', [
            'id_reporte'       => $datos['id_reporte'],
            'id_institucion'   => $datos['id_institucion'],
            'id_informe'       => $datos['id_informe'] ?? null,
            'id_usuario_autor' => $datos['id_usuario_autor'],
            // Todo texto se guarda plano: las vistas que lo muestran ya aplican
            // htmlspecialchars() al leerlo. Los llamadores (agregar_avance del técnico,
            // rechazo del gestor) pasan el texto por Validacion::sanitizar_texto(), que
            // también escapa HTML; sin revertirlo aquí, comillas y "&" quedaban escapados
            // dos veces y se veían literales (p. ej. "&quot;") en pantalla.
            'texto'            => htmlspecialchars_decode($datos['texto'], ENT_QUOTES),
        ]);
    }

    /** Avances de un reporte, del más reciente al más antiguo, con el nombre del autor */
    public function listar_por_reporte($id_reporte, $id_institucion) {
        $sql = 'SELECT a.id_avance, a.texto, a.fecha_creacion, u.nombre_completo AS autor
                FROM avance_intervencion a
                JOIN usuario u ON u.id_usuario = a.id_usuario_autor
                WHERE a.id_reporte = :r AND a.id_institucion = :i
                ORDER BY a.fecha_creacion DESC, a.id_avance DESC';
        return $this->bd->obtener_todos($sql, [':r' => $id_reporte, ':i' => $id_institucion]);
    }

    /**
     * Versión para el seguimiento público: sin filtro de institución por sesión
     * (el acceso ya se validó por token) y sin datos del autor más allá del nombre.
     */
    public function listar_publicos($id_reporte) {
        $sql = 'SELECT a.texto, a.fecha_creacion, u.nombre_completo AS autor
                FROM avance_intervencion a
                JOIN usuario u ON u.id_usuario = a.id_usuario_autor
                WHERE a.id_reporte = :r
                ORDER BY a.fecha_creacion DESC, a.id_avance DESC';
        return $this->bd->obtener_todos($sql, [':r' => $id_reporte]);
    }
}
