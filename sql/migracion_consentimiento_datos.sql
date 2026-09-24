-- ---------------------------------------------------------------------------
-- Constancia del consentimiento para el tratamiento de datos personales
-- ---------------------------------------------------------------------------
-- El formulario público recoge nombre, correo y teléfono de ciudadanos. La Ley
-- 1581 de 2012 exige que la autorización sea previa, expresa e informada, y
-- que el responsable pueda demostrar que la obtuvo. El propio ERS del proyecto
-- ya lo pedía (RNF-06) y nunca se implementó.
--
-- La constancia va en la fila del reporte y no solo en el registro de
-- auditoría: tiene que durar exactamente lo que duren los datos que justifica.
--
-- Los reportes anteriores quedan en 0. Es lo correcto: no se obtuvo ninguna
-- autorización, y marcarlos como aceptados sería fabricar una prueba.
-- ---------------------------------------------------------------------------

ALTER TABLE reporte
  ADD COLUMN acepto_tratamiento_datos TINYINT(1) NOT NULL DEFAULT 0
      COMMENT 'Ley 1581: autorizacion del titular para tratar sus datos'
      AFTER es_anonimo,
  ADD COLUMN fecha_aceptacion_datos DATETIME NULL
      COMMENT 'Momento exacto en que se otorgo la autorizacion'
      AFTER acepto_tratamiento_datos;
