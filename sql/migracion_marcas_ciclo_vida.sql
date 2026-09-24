-- ---------------------------------------------------------------------------
-- Reconstruir las marcas de tiempo del ciclo de vida
-- ---------------------------------------------------------------------------
-- Las columnas fecha_hora_inicio_tecnico, fecha_hora_solucionado y
-- fecha_hora_cierre existían en el esquema desde el principio y ningún punto
-- del código las escribía. A partir de ahora sí se escriben, pero los reportes
-- que ya están en la base las tienen vacías: la línea de tiempo del ciudadano
-- muestra "Pendiente — Cierre formal" hasta en los que llevan meses cerrados.
--
-- No hace falta inventar nada. La historia completa está en transicion_estado,
-- que se viene registrando desde siempre, y el inicio de la intervención está
-- en informe_intervencion.fecha_hora_inicio.
--
-- Solo se rellenan las que están vacías (IS NULL), así que esto se puede
-- ejecutar más de una vez sin pisar nada de lo que escriba el código nuevo.
--
-- No añade ni cambia columnas: es solo relleno de datos.
-- ---------------------------------------------------------------------------

-- 1. Cuándo empezó el técnico: la apertura de su hoja de trabajo.
UPDATE reporte r
   SET r.fecha_hora_inicio_tecnico = (
         SELECT MIN(i.fecha_hora_inicio)
           FROM informe_intervencion i
          WHERE i.id_reporte = r.id_reporte
            AND i.fecha_hora_inicio IS NOT NULL
       )
 WHERE r.fecha_hora_inicio_tecnico IS NULL;

-- 2. Cuándo se dio por solucionado. Si se devolvió y se rehízo, vale la
--    última vez: es la que cierra el tiempo de resolución.
UPDATE reporte r
   SET r.fecha_hora_solucionado = (
         SELECT MAX(t.fecha_hora_transicion)
           FROM transicion_estado t
          WHERE t.id_reporte = r.id_reporte
            AND t.id_estado_destino = 4  -- Solucionado
       )
 WHERE r.fecha_hora_solucionado IS NULL;

-- 3. Cuándo se cerró o se anuló: ambos terminan el reporte.
UPDATE reporte r
   SET r.fecha_hora_cierre = (
         SELECT MAX(t.fecha_hora_transicion)
           FROM transicion_estado t
          WHERE t.id_reporte = r.id_reporte
            AND t.id_estado_destino IN (7, 8)  -- Cerrado, Anulado
       )
 WHERE r.fecha_hora_cierre IS NULL;

-- 4. Quién asignó el técnico. Sale del registro de auditoría, que es donde
--    constaba hasta ahora. Solo para los reportes que tienen técnico.
UPDATE reporte r
   SET r.id_gestor_asignador = (
         SELECT a.id_usuario
           FROM registro_auditoria a
          WHERE a.entidad = 'reporte'
            AND a.id_entidad = r.id_reporte
            AND a.accion = 'asignar_tecnico'
            AND a.id_usuario IS NOT NULL
          ORDER BY a.fecha_hora_accion DESC
          LIMIT 1
       )
 WHERE r.id_gestor_asignador IS NULL
   AND r.id_tecnico_asignado IS NOT NULL;
