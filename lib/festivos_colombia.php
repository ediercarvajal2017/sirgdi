<?php
/**
 * Festivos de Colombia (Ley 51 de 1983, "Ley Emiliani").
 *
 * Se calculan, no se cargan a mano: así nadie tiene que acordarse de
 * actualizarlos cada año, y el cálculo del SLA no deja de funcionar en enero.
 *
 * Son 18 al año, de tres tipos:
 *  - Fijos: se celebran en su fecha.
 *  - Trasladables: si no caen en lunes, pasan al lunes siguiente.
 *  - Ligados a la Pascua: Jueves y Viernes Santo en su fecha; Ascensión,
 *    Corpus Christi y Sagrado Corazón trasladados a lunes.
 *
 * Dos festivos pueden caer el mismo día (en 2025, San Pedro y el Sagrado
 * Corazón coincidieron el 30 de junio): se devuelven fechas únicas.
 */
final class FestivosColombia
{
    /** @var array<int, array<string, true>> */
    private static $cache = [];

    /** Mes y día de los festivos que se celebran en su fecha. */
    private const FIJOS = [
        [1, 1],   // Año Nuevo
        [5, 1],   // Día del Trabajo
        [7, 20],  // Independencia
        [8, 7],   // Batalla de Boyacá
        [12, 8],  // Inmaculada Concepción
        [12, 25], // Navidad
    ];

    /** Mes y día de los que se trasladan al lunes siguiente. */
    private const TRASLADABLES = [
        [1, 6],   // Reyes Magos
        [3, 19],  // San José
        [6, 29],  // San Pedro y San Pablo
        [8, 15],  // Asunción de la Virgen
        [10, 12], // Día de la Raza
        [11, 1],  // Todos los Santos
        [11, 11], // Independencia de Cartagena
    ];

    /** @return string[] Fechas 'Y-m-d' ordenadas. */
    public static function del_anio(int $anio): array
    {
        $fechas = array_keys(self::mapa($anio));
        sort($fechas);
        return $fechas;
    }

    public static function es_festivo(DateTimeInterface $dia): bool
    {
        return isset(self::mapa((int) $dia->format('Y'))[$dia->format('Y-m-d')]);
    }

    /** @return array<string, true> */
    private static function mapa(int $anio): array
    {
        if (isset(self::$cache[$anio])) {
            return self::$cache[$anio];
        }

        $fechas = [];
        $agregar = function (DateTimeImmutable $d) use (&$fechas) {
            $fechas[$d->format('Y-m-d')] = true;
        };

        foreach (self::FIJOS as [$mes, $dia]) {
            $agregar(self::fecha($anio, $mes, $dia));
        }
        foreach (self::TRASLADABLES as [$mes, $dia]) {
            $agregar(self::al_lunes(self::fecha($anio, $mes, $dia)));
        }

        $pascua = self::domingo_de_pascua($anio);
        $agregar($pascua->modify('-3 days'));                  // Jueves Santo
        $agregar($pascua->modify('-2 days'));                  // Viernes Santo
        $agregar(self::al_lunes($pascua->modify('+39 days'))); // Ascensión
        $agregar(self::al_lunes($pascua->modify('+60 days'))); // Corpus Christi
        $agregar(self::al_lunes($pascua->modify('+68 days'))); // Sagrado Corazón

        return self::$cache[$anio] = $fechas;
    }

    private static function fecha(int $anio, int $mes, int $dia): DateTimeImmutable
    {
        return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $anio, $mes, $dia));
    }

    /** El mismo día si ya es lunes; si no, el lunes siguiente. */
    private static function al_lunes(DateTimeImmutable $d): DateTimeImmutable
    {
        $n = (int) $d->format('N'); // 1 = lunes … 7 = domingo
        return $d->modify('+' . ((8 - $n) % 7) . ' days');
    }

    /**
     * Domingo de Pascua (calendario gregoriano, algoritmo de Meeus).
     *
     * Se calcula a mano y no con easter_date(): esa función vive en una
     * extensión (calendar) que no todos los alojamientos tienen activada.
     */
    public static function domingo_de_pascua(int $anio): DateTimeImmutable
    {
        $a = $anio % 19;
        $b = intdiv($anio, 100);
        $c = $anio % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mes = intdiv($h + $l - 7 * $m + 114, 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;

        return self::fecha($anio, $mes, $dia);
    }
}
