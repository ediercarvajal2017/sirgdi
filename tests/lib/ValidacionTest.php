<?php

use PHPUnit\Framework\TestCase;

final class ValidacionTest extends TestCase
{
    // --- validar_email ---

    public function testValidarEmailAceptaCorreoValido(): void
    {
        $this->assertTrue(Validacion::validar_email('gestor@instituciondemo.edu.co'));
    }

    public function testValidarEmailRechazaCorreoInvalido(): void
    {
        $this->assertFalse(Validacion::validar_email('no-es-un-correo'));
    }

    // --- validar_contrasena (RNF-02: min 8 caracteres, mayúscula, minúscula, número) ---

    public function testValidarContrasenaAceptaContrasenaQueCumpleTodosLosRequisitos(): void
    {
        $this->assertTrue(Validacion::validar_contrasena('Cambiar@2026!'));
    }

    public function testValidarContrasenaRechazaMenosDeOchoCaracteres(): void
    {
        $this->assertFalse(Validacion::validar_contrasena('Ab1'));
    }

    public function testValidarContrasenaRechazaSinMayuscula(): void
    {
        $this->assertFalse(Validacion::validar_contrasena('cambiar2026!'));
    }

    public function testValidarContrasenaRechazaSinMinuscula(): void
    {
        $this->assertFalse(Validacion::validar_contrasena('CAMBIAR2026!'));
    }

    public function testValidarContrasenaRechazaSinNumero(): void
    {
        $this->assertFalse(Validacion::validar_contrasena('CambiarClave!'));
    }

    // --- validar_uuid (protege el token_seguimiento_publico usado en endpoints públicos) ---

    public function testValidarUuidAceptaUuidV4Valido(): void
    {
        $this->assertTrue(Validacion::validar_uuid('f32398a9-c8fb-4ffc-a9cd-d036ed62a15b'));
    }

    public function testValidarUuidRechazaCadenaMalformada(): void
    {
        $this->assertFalse(Validacion::validar_uuid('no-es-un-uuid'));
    }

    public function testValidarUuidRechazaTextoLibreQueIntentaInyeccion(): void
    {
        $this->assertFalse(Validacion::validar_uuid("1' OR '1'='1"));
    }

    // --- sanitizar_texto (defensa contra XSS en campos de texto libre) ---

    public function testSanitizarTextoRemueveEtiquetasScript(): void
    {
        $resultado = Validacion::sanitizar_texto('<script>alert(1)</script>Hola');
        $this->assertStringNotContainsString('<script>', $resultado);
        $this->assertStringContainsString('Hola', $resultado);
    }

    public function testSanitizarTextoRecortaEspaciosExtras(): void
    {
        $this->assertSame('texto', Validacion::sanitizar_texto('  texto  '));
    }

    // --- validar_fecha ---

    public function testValidarFechaAceptaFormatoCorrecto(): void
    {
        $this->assertTrue(Validacion::validar_fecha('2026-09-21'));
    }

    public function testValidarFechaRechazaFechaInexistente(): void
    {
        $this->assertFalse(Validacion::validar_fecha('2026-02-30'));
    }

    public function testValidarFechaRechazaOtroFormato(): void
    {
        $this->assertFalse(Validacion::validar_fecha('21/09/2026'));
    }

    // --- validar_rango / validar_enum ---

    public function testValidarRangoAceptaValorDentroDelRango(): void
    {
        $this->assertTrue(Validacion::validar_rango(3, 1, 4));
    }

    public function testValidarRangoRechazaValorFueraDelRango(): void
    {
        $this->assertFalse(Validacion::validar_rango(9, 1, 4));
    }

    public function testValidarEnumRechazaValorNoPermitido(): void
    {
        $this->assertFalse(Validacion::validar_enum('otro', ['Registrado', 'Cerrado']));
    }

    public function testLaContrasenaTemporalGeneradaSiempreCumpleLaPolitica(): void
    {
        // Antes se usaba bin2hex(random_bytes(5)): 10 caracteres hexadecimales,
        // sin mayúsculas. Era más débil que lo que el sistema exige al usuario.
        for ($i = 0; $i < 50; $i++) {
            $generada = Validacion::generar_contrasena_temporal();
            $this->assertTrue(
                Validacion::validar_contrasena($generada),
                'No cumple la política: ' . $generada
            );
        }
    }

    public function testLaContrasenaTemporalEvitaCaracteresQueSeConfundenAlDictarla(): void
    {
        // Se entregan por teléfono o WhatsApp: O/0 y l/I/1 se confunden.
        for ($i = 0; $i < 50; $i++) {
            $generada = Validacion::generar_contrasena_temporal();
            $this->assertDoesNotMatchRegularExpression('/[Ol0I1]/', $generada);
        }
    }

    public function testLaContrasenaTemporalRespetaLaLongitudMinima(): void
    {
        // Aunque se pida menos de 8, nunca baja del mínimo de la política.
        $this->assertGreaterThanOrEqual(8, strlen(Validacion::generar_contrasena_temporal(4)));
        $this->assertSame(16, strlen(Validacion::generar_contrasena_temporal(16)));
    }
}
