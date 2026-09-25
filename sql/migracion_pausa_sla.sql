-- Acumular las horas hábiles en que el SLA estuvo en pausa.
--
-- El SLA se pausa cuando el técnico marca un reporte como solucionado y se
-- reanuda si el gestor lo rechaza. Al reanudar solo se borraba
-- fecha_pausa_sla, así que todo el tiempo que el reporte esperó la decisión
-- del gestor volvía a contar como si lo hubiera gastado el técnico.
--
-- Esta columna guarda la suma de las pausas ya terminadas, en horas hábiles
-- de la institución. Empieza en 0 para todos: se comprobó con la copia de
-- producción del 25/09/2026 que los dos únicos reportes con pausas pasadas
-- (SIR-202600004 y SIR-202600007, institución 11) las tuvieron en domingo y
-- de noche, es decir, 0 horas hábiles. No hay nada que reconstruir.
--
-- Aditiva e idempotente. Aplicar en producción ANTES de publicar el código
-- que la lee.

ALTER TABLE reporte
    ADD COLUMN IF NOT EXISTS horas_pausa_sla DECIMAL(10,4) NOT NULL DEFAULT 0
        COMMENT 'Horas hábiles de pausas del SLA ya terminadas'
        AFTER fecha_pausa_sla;
