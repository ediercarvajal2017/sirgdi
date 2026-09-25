-- Horario laboral por día de la semana, para que el SLA cuente horas hábiles.
--
-- La tabla solo tenía una hora de inicio y una de fin para toda la semana
-- (horas_laborales_inicio / _fin), así que no podía expresar un sábado de
-- media jornada. Esta columna guarda, para cada día ISO (1 = lunes), la franja
-- ["HH:MM","HH:MM"] o null si no se trabaja. Si es NULL, o la institución no
-- tiene fila en esta tabla, se usa el horario por defecto: lunes a viernes
-- 7:00-17:00 y sábado 7:00-13:00 (ver lib/horario_laboral.php).
--
-- Aditiva e idempotente. Aplicar en producción ANTES de publicar el código
-- que la lee.

ALTER TABLE configuracion_institucion
    ADD COLUMN IF NOT EXISTS horario_semanal_json JSON NULL
        COMMENT 'Franja por día ISO: {"1":["07:00","17:00"],...,"7":null}. NULL = horario por defecto'
        AFTER dias_no_laborales_json;
