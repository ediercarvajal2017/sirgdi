<?php
/** @var array $semana día ISO => ['HH:MM','HH:MM'] o null */
$nombres_dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
$meses = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
?>
<script src="<?php echo asset_url('js/toast.js'); ?>"></script>

<div class="container horario-container">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-business-time"></i></div>
        <div class="page-banner__text">
            <h2>Horario laboral</h2>
            <p>Los plazos del SLA cuentan solo las horas en que la institución trabaja.</p>
        </div>
    </div>

    <div class="card horario-card">
        <h3><i class="fas fa-circle-info"></i> Cómo se usa</h3>
        <p class="horario-ayuda">
            Un reporte que llega fuera de este horario empieza a contar en la siguiente
            franja. Por ejemplo, con el horario por defecto, un reporte del sábado a las
            23:00 con un plazo de 8 horas vence el lunes a las 15:00, no el domingo por la mañana.
            Los festivos de Colombia no cuentan nunca, y se calculan solos cada año.
        </p>
        <p class="horario-ayuda">
            <?php if ($es_propio): ?>
                <strong>Esta institución tiene un horario propio.</strong>
            <?php else: ?>
                <strong>Esta institución usa el horario por defecto:</strong> lunes a viernes de 7:00 a 17:00 y sábado de 7:00 a 13:00.
            <?php endif; ?>
            Cambiarlo recalcula al momento los plazos de todos los reportes, incluidos los ya cerrados en los informes.
        </p>
    </div>

    <div class="card horario-card">
        <h3><i class="fas fa-calendar-week"></i> Días y horas</h3>
        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=administrador&accion=horario_laboral" id="form-horario">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="accion" value="guardar">

            <div class="horario-dias">
                <?php foreach ($nombres_dias as $d => $nombre):
                    $franja = $semana[(string) $d] ?? null;
                    $activo = $franja !== null;
                ?>
                <div class="horario-dia<?php echo $activo ? '' : ' horario-dia--libre'; ?>">
                    <label class="horario-dia__nombre">
                        <input type="checkbox" name="dia[<?php echo $d; ?>][activo]" value="1" class="js-dia-activo" <?php echo $activo ? 'checked' : ''; ?>>
                        <?php echo $nombre; ?>
                    </label>
                    <label class="horario-dia__hora">
                        <span>Desde</span>
                        <input type="time" name="dia[<?php echo $d; ?>][inicio]" class="input-modern"
                               value="<?php echo htmlspecialchars($franja[0] ?? '07:00'); ?>" <?php echo $activo ? '' : 'disabled'; ?>>
                    </label>
                    <label class="horario-dia__hora">
                        <span>Hasta</span>
                        <input type="time" name="dia[<?php echo $d; ?>][fin]" class="input-modern"
                               value="<?php echo htmlspecialchars($franja[1] ?? '17:00'); ?>" <?php echo $activo ? '' : 'disabled'; ?>>
                    </label>
                    <span class="horario-dia__estado"><?php echo $activo ? '' : 'No laboral'; ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="horario-acciones">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar horario</button>
            </div>
        </form>

        <?php if ($es_propio): ?>
        <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=administrador&accion=horario_laboral"
              onsubmit="return confirm('¿Volver al horario por defecto (L–V 7:00–17:00, sábado 7:00–13:00)?');" class="horario-restablecer">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="accion" value="restablecer">
            <button type="submit" class="btn btn-secondary"><i class="fas fa-rotate-left"></i> Volver al horario por defecto</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="card horario-card">
        <h3><i class="fas fa-flag"></i> Festivos que no cuentan</h3>
        <ul class="horario-festivos">
            <?php foreach ($festivos as $f):
                $t = strtotime($f); ?>
                <li><?php echo (int) date('j', $t) . ' ' . $meses[(int) date('n', $t)] . ' ' . date('Y', $t); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script>
document.querySelectorAll('.js-dia-activo').forEach(function (casilla) {
    casilla.addEventListener('change', function () {
        var fila = casilla.closest('.horario-dia');
        fila.classList.toggle('horario-dia--libre', !casilla.checked);
        fila.querySelectorAll('input[type=time]').forEach(function (h) { h.disabled = !casilla.checked; });
        fila.querySelector('.horario-dia__estado').textContent = casilla.checked ? '' : 'No laboral';
    });
});
</script>


<style>
.horario-container { max-width: 860px; }
.horario-card { margin-bottom: 18px; }
.horario-card h3 { margin: 0 0 12px; color: var(--color-text); }
.horario-ayuda { color: var(--color-text-muted); line-height: 1.6; margin: 0 0 10px; }
.horario-dias { display: flex; flex-direction: column; gap: 8px; }
.horario-dia {
    display: grid; grid-template-columns: 150px 1fr 1fr 110px; gap: 12px; align-items: center;
    padding: 10px 12px; border: 1px solid var(--color-border); border-radius: 10px;
    background: var(--color-bg-subtle);
}
.horario-dia--libre { opacity: .6; }
.horario-dia__nombre { display: flex; align-items: center; gap: 8px; font-weight: 600; color: var(--color-text); cursor: pointer; }
.horario-dia__nombre input { width: 18px; height: 18px; }
.horario-dia__hora { display: flex; align-items: center; gap: 8px; color: var(--color-text-muted); font-size: 14px; }
.horario-dia__hora input { flex: 1; min-width: 0; }
.horario-dia__estado { color: var(--color-text-muted); font-size: 13px; text-align: right; }
.horario-acciones { margin-top: 16px; }
.horario-restablecer { margin-top: 10px; }
.horario-festivos { display: flex; flex-wrap: wrap; gap: 8px; list-style: none; padding: 0; margin: 0; }
.horario-festivos li {
    padding: 4px 10px; border-radius: 999px; font-size: 13px;
    background: var(--color-bg-subtle); border: 1px solid var(--color-border); color: var(--color-text);
}
@media (max-width: 640px) {
    .horario-dia { grid-template-columns: 1fr 1fr; }
    .horario-dia__nombre { grid-column: 1 / -1; }
    .horario-dia__estado { display: none; }
}
</style>
