<?php
declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

/*
 * Pruebas de aceptación: Ventas Presenciales (Compra Física)
 *
 * - Suite: definida en `tests/Acceptance.suite.yml` con PhpBrowser y URL base.
 * - Objetivo: validar accesibilidad de la página de compra física y
 *   comportamiento ante acciones inválidas.
 */
final class CompraFisicaCest
{
    /*
     * VP-ACC-001: Abrir página de compra física
     * Verifica que la ruta `?pagina=comprafisica` responda con HTTP 2xx.
     */
    public function abrirPaginaCompraFisica(AcceptanceTester $I): void
    {
        $I->amOnPage('/index.php?pagina=comprafisica');
        $I->seeResponseCodeIsSuccessful();
    }

    /*
     * VP-ACC-002: Acción inválida en compra física
     * Verifica que una acción no existente no rompa la respuesta del sistema.
     */
    public function rutaInvalidaAccionDevuelveAlgo(AcceptanceTester $I): void
    {
        $I->amOnPage('/index.php?pagina=comprafisica&accion=accion_que_no_existe');
        $I->seeResponseCodeIsSuccessful();
    }
}
