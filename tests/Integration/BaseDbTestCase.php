<?php

use PHPUnit\Framework\TestCase;

/**
 * Base para pruebas de integración que tocan la BD local real. Cada prueba
 * queda envuelta en una transacción que se revierte en tearDown, así que no
 * deja datos de prueba en la base de datos, sin importar si la prueba pasa o
 * falla (o lanza una excepción a medio camino).
 *
 * Requiere que exista un usuario/contraseña de BD local configurado en el
 * .env del proyecto (el mismo que usa la app para desarrollo).
 */
abstract class BaseDbTestCase extends TestCase
{
    protected BaseDatos $bd;

    // Fixture fija del seed local: institución "Institución Demo SIRGDI".
    protected const ID_INSTITUCION = 1;
    protected const ID_SEDE = 2;
    protected const ID_CATEGORIA = 2; // Electricidad
    protected const ID_SUBCATEGORIA = 2;
    protected const ID_USUARIO = 2; // prueba@local.test, institución 1

    protected function setUp(): void
    {
        $this->bd = BaseDatos::obtener();
        $this->bd->iniciar_transaccion();
    }

    protected function tearDown(): void
    {
        $this->bd->revertir_transaccion();
    }

    /**
     * Datos mínimos válidos para ModeloReporte::crear(), sobrescribibles.
     */
    protected function datosReporteValido(array $overrides = []): array
    {
        return array_merge([
            'id_institucion' => self::ID_INSTITUCION,
            'id_sede' => self::ID_SEDE,
            'id_area' => null,
            'referencia_ubicacion_libre' => 'Patio central',
            'id_categoria' => self::ID_CATEGORIA,
            'id_subcategoria' => self::ID_SUBCATEGORIA,
            'descripcion_problema' => 'Prueba automatizada: lámpara del pasillo no enciende.',
            'nombre_reportante' => 'Prueba PHPUnit',
            'correo_reportante' => 'prueba-phpunit@local.test',
            'es_anonimo' => 0,
        ], $overrides);
    }
}
