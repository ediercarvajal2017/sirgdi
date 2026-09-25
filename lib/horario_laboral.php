<?php
/**
 * Horas hábiles de una institución.
 *
 * El SLA contaba horas de calendario: un reporte del sábado por la noche con
 * un plazo de 8 horas vencía el domingo por la mañana, antes de que nadie
 * pudiera atenderlo. Medido en producción, el 75 % de los reportes entraba
 * fuera de horario, así que los informes marcaban como incumplidos reportes
 * que nadie había dejado de atender.
 *
 * Un horario es, para cada día de la semana, una franja de inicio y fin, o
 * nada si ese día no se trabaja. Los festivos de Colombia no se trabajan
 * nunca (ver FestivosColombia).
 *
 * Todas las fechas se interpretan en la zona horaria de la aplicación.
 */

require_once __DIR__ . '/festivos_colombia.php';

final class HorarioLaboral
{
    /** Tope de días que se recorren al sumar horas: evita un bucle sin fin
     *  con un horario vacío o absurdo. Diez años sobra para cualquier SLA. */
    private const MAX_DIAS = 3660;

    /** @var array<int, array{0:int,1:int}|null> día ISO (1=lunes) => [inicio, fin] en minutos */
    private $semana;

    /** @var bool */
    private $con_festivos;

    /**
     * @param array<int, array{0:int,1:int}|null> $semana
     */
    private function __construct(array $semana, bool $con_festivos = true)
    {
        $this->semana = $semana;
        $this->con_festivos = $con_festivos;
    }

    /** Lunes a viernes de 7:00 a 17:00 y sábado de 7:00 a 13:00. */
    public static function por_defecto(): self
    {
        return self::desde_arreglo(self::semana_por_defecto());
    }

    /** @return array<int, array{0:string,1:string}|null> */
    public static function semana_por_defecto(): array
    {
        $semana = [];
        for ($d = 1; $d <= 5; $d++) {
            $semana[(string) $d] = ['07:00', '17:00'];
        }
        $semana['6'] = ['07:00', '13:00'];
        $semana['7'] = null;

        return $semana;
    }

    /**
     * Las 24 horas, todos los días, sin festivos: el comportamiento anterior.
     * Lo usan las pruebas que verifican otras partes del SLA y no deben
     * depender del día en que se ejecutan.
     */
    public static function siempre(): self
    {
        $semana = [];
        for ($d = 1; $d <= 7; $d++) {
            $semana[$d] = [0, 24 * 60];
        }

        return new self($semana, false);
    }

    /**
     * Desde el JSON guardado en configuracion_institucion. Si falta o no es
     * válido, el horario por defecto: un dato mal guardado no puede dejar a
     * una institución sin cálculo de SLA.
     */
    public static function desde_json(?string $json): self
    {
        if ($json === null || trim($json) === '') {
            return self::por_defecto();
        }

        $datos = json_decode($json, true);
        if (!is_array($datos) || self::validar($datos) !== null) {
            return self::por_defecto();
        }

        return self::desde_arreglo($datos);
    }

    /**
     * @param array<string|int, array{0:string,1:string}|null> $datos día ISO => ['HH:MM','HH:MM'] o null
     */
    public static function desde_arreglo(array $datos): self
    {
        $semana = [];
        for ($d = 1; $d <= 7; $d++) {
            $franja = $datos[$d] ?? $datos[(string) $d] ?? null;
            $semana[$d] = $franja ? [self::minutos($franja[0]), self::minutos($franja[1])] : null;
        }

        return new self($semana);
    }

    /**
     * Devuelve un mensaje de error si el horario no sirve, o null si es válido.
     *
     * @param array<string|int, mixed> $datos
     */
    public static function validar(array $datos): ?string
    {
        $alguno = false;
        for ($d = 1; $d <= 7; $d++) {
            $franja = $datos[$d] ?? $datos[(string) $d] ?? null;
            if ($franja === null) {
                continue;
            }
            if (!is_array($franja) || count($franja) !== 2
                || !self::es_hora($franja[0]) || !self::es_hora($franja[1])) {
                return 'Cada día laboral necesita una hora de inicio y una de fin (HH:MM).';
            }
            if (self::minutos($franja[0]) >= self::minutos($franja[1])) {
                return 'En cada día, la hora de fin tiene que ser posterior a la de inicio.';
            }
            $alguno = true;
        }

        return $alguno ? null : 'Tiene que haber al menos un día laboral.';
    }

    /** @return array<int, array{0:string,1:string}|null> Para guardar como JSON (PHP convierte las claves "1".."7" en enteros). */
    public function a_arreglo(): array
    {
        $datos = [];
        foreach ($this->semana as $d => $franja) {
            $datos[(string) $d] = $franja ? [self::hora($franja[0]), self::hora($franja[1])] : null;
        }

        return $datos;
    }

    /** Horas hábiles entre dos instantes (0 si el segundo no es posterior). */
    public function horas_entre(DateTimeInterface $desde, DateTimeInterface $hasta): float
    {
        $a = $desde->getTimestamp();
        $b = $hasta->getTimestamp();
        if ($b <= $a) {
            return 0.0;
        }

        $segundos = 0;
        $dia = (new DateTimeImmutable('@' . $a))->setTimezone(self::zona())->setTime(0, 0);
        $ultimo = (new DateTimeImmutable('@' . $b))->setTimezone(self::zona())->setTime(0, 0);

        while ($dia <= $ultimo) {
            $franja = $this->franja($dia);
            if ($franja !== null) {
                $inicio = max($a, $franja[0]);
                $fin = min($b, $franja[1]);
                if ($fin > $inicio) {
                    $segundos += $fin - $inicio;
                }
            }
            $dia = $dia->modify('+1 day');
        }

        return $segundos / 3600;
    }

    /**
     * El instante en que se cumplen $horas hábiles contadas desde $desde.
     *
     * Si $desde cae fuera de horario, el reloj empieza con la siguiente
     * franja: un reporte del domingo empieza a contar el lunes a primera hora.
     */
    public function sumar_horas(DateTimeInterface $desde, float $horas): DateTimeImmutable
    {
        $inicio = (new DateTimeImmutable('@' . $desde->getTimestamp()))->setTimezone(self::zona());
        $restantes = (int) round($horas * 3600);
        if ($restantes <= 0) {
            return $inicio;
        }

        $dia = $inicio->setTime(0, 0);
        for ($n = 0; $n < self::MAX_DIAS; $n++) {
            $franja = $this->franja($dia);
            if ($franja !== null) {
                $desde_ts = max($inicio->getTimestamp(), $franja[0]);
                $disponible = $franja[1] - $desde_ts;
                if ($disponible > 0) {
                    if ($restantes <= $disponible) {
                        return (new DateTimeImmutable('@' . ($desde_ts + $restantes)))->setTimezone(self::zona());
                    }
                    $restantes -= $disponible;
                }
            }
            $dia = $dia->modify('+1 day');
        }

        // Solo con un horario sin ningún día laboral: no hay plazo posible.
        throw new RuntimeException('El horario laboral no tiene ningún día con horas hábiles.');
    }

    /** @return array{0:int,1:int}|null Franja del día como timestamps, o null si no se trabaja. */
    private function franja(DateTimeImmutable $dia): ?array
    {
        $minutos = $this->semana[(int) $dia->format('N')] ?? null;
        if ($minutos === null || ($this->con_festivos && FestivosColombia::es_festivo($dia))) {
            return null;
        }

        $base = $dia->getTimestamp(); // medianoche local de ese día
        return [$base + $minutos[0] * 60, $base + $minutos[1] * 60];
    }

    private static function zona(): DateTimeZone
    {
        return new DateTimeZone(date_default_timezone_get());
    }

    private static function es_hora($valor): bool
    {
        return is_string($valor)
            && (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$|^24:00$/', $valor);
    }

    private static function minutos(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', $hora));
        return $h * 60 + $m;
    }

    private static function hora(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }
}
