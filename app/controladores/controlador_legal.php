<?php
/**
 * Páginas legales públicas.
 *
 * Existe por una razón concreta: el formulario público recoge nombre, correo y
 * teléfono de ciudadanos, y la Ley 1581 de 2012 exige que la autorización para
 * tratarlos sea previa, expresa e informada. Sin un sitio donde leer qué se
 * hace con esos datos, la casilla de aceptación no informa de nada y la
 * autorización no vale.
 *
 * El propio ERS del proyecto ya lo pedía (RNF-06) desde la primera versión.
 */

require_once APP_PATH . '/modelos/modelo_institucion.php';

class ControladorLegal
{
    /**
     * Política de tratamiento de datos personales.
     *
     * Acepta ?inst= para nombrar a la institución responsable. El responsable
     * del tratamiento es la institución, no la plataforma: es quien decide
     * para qué se recogen los datos. La plataforma es el encargado, y eso
     * tiene que quedar dicho.
     */
    public function privacidad()
    {
        $id_institucion = intval($_GET['inst'] ?? 0);
        $institucion = null;
        $correo_contacto = null;

        if ($id_institucion) {
            $modelo = new ModeloInstitucion();
            $institucion = $modelo->obtener_por_id($id_institucion);
            if ($institucion) {
                $correo_contacto = $this->correo_responsable($id_institucion);
            }
        }

        $datos = [
            'titulo'          => 'Tratamiento de datos personales — ' . config('app.app_name'),
            'institucion'     => $institucion,
            'correo_contacto' => $correo_contacto ?: config('smtp.from_email'),
            'id_institucion'  => $id_institucion,
        ];

        $this->renderizar_publica('legal/vista_privacidad', $datos);
    }

    /**
     * A quién escribe el ciudadano para ejercer sus derechos.
     *
     * Se busca al Admin de Institución o Rector, que es quien responde por los
     * datos de esa institución. Por id de rol y no por nombre: los nombres
     * llevan acentos y una base mal importada rompería la búsqueda en
     * silencio, que es justo lo que pasó con las notificaciones.
     */
    private function correo_responsable($id_institucion)
    {
        try {
            $bd = BaseDatos::obtener();
            $fila = $bd->obtener_uno(
                'SELECT u.correo_electronico
                   FROM usuario u
                   JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
                  WHERE u.id_institucion = :inst
                    AND u.activo = 1
                    AND ur.id_rol IN (' . ROL_ADMIN . ', ' . ROL_RECTOR . ')
                  ORDER BY ur.id_rol DESC
                  LIMIT 1',
                [':inst' => $id_institucion]
            );

            return $fila['correo_electronico'] ?? null;
        } catch (Throwable $e) {
            // Que falte el contacto no puede impedir que se lea la política:
            // abajo se cae al correo de soporte de la plataforma.
            error_log('No se pudo resolver el responsable de datos: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * La vista es un documento HTML completo por sí misma, como el formulario
     * público: no se envuelve en el layout autenticado.
     */
    private function renderizar_publica($vista, $datos = [])
    {
        extract($datos);
        $archivo_vista = APP_PATH . '/vistas/' . $vista . '.php';

        if (!file_exists($archivo_vista)) {
            responder_error_interno('Vista no encontrada: ' . $archivo_vista);
        }

        require $archivo_vista;
    }
}
