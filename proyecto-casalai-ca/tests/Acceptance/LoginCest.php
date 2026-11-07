<?php

declare(strict_types=1);


namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

final class LoginCest
{
    public function _before(AcceptanceTester $I): void
    {
        // Code here will be executed before each test.
    }

    public function homePageLoads(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->seeResponseCodeIs(200);
    }

    public function unregisteredUserCannotLogin(AcceptanceTester $I): void
    {
        // Ruta real de login: index.php?pagina=login
        $I->amOnPage('/index.php?pagina=login');
        $I->seeResponseCodeIs(200);

        // Intenta con credenciales inexistentes
        $email = 'noexiste+' . time() . '@example.com';
        $I->seeElement('form');
        $I->submitForm('form', [
            'username' => $email,
            'password' => 'contraseña_incorrecta_123',
            // Controlador espera accion=acceder
            'accion' => 'acceder',
        ]);

        // Debe permanecer o volver a una página con error
        $I->seeResponseCodeIs(200);
        // Verificar el contenedor de mensajes server-rendered
        $I->seeElement('#mensajes');
        $tipo = $I->grabAttributeFrom('#mensajes', 'data-tipo');
        \PHPUnit\Framework\Assert::assertSame('error', $tipo, 'Se esperaba data-tipo="error" en #mensajes');
        $mensaje = $I->grabAttributeFrom('#mensajes', 'data-mensaje');
        \PHPUnit\Framework\Assert::assertNotEmpty($mensaje, 'Se esperaba data-mensaje con detalle del error');
    }

    public function loginSuccessRedirectsToCatalog(AcceptanceTester $I): void
    {
        // Login con credenciales válidas de Cliente
        $I->amOnPage('/index.php?pagina=login');
        $I->seeResponseCodeIs(200);
        $I->seeElement('form');
        $I->submitForm('form', [
            'username' => 'BrayntCliente',
            'password' => '123456',
            'accion'   => 'acceder',
        ]);

        // Debe redirigir a catálogo para rol Cliente
        $I->seeResponseCodeIs(200);
        $I->seeInCurrentUrl('pagina=catalogo');

        // Comprobación ligera de contenido (ajustable según tu vista de catálogo)
        // Evitamos acoplar a textos específicos si no son estables
        // $I->see('Catálogo');
    }

    public function logoutEndsSession(AcceptanceTester $I): void
    {
        // Primero: iniciar sesión
        $I->amOnPage('/index.php?pagina=login');
        $I->seeResponseCodeIs(200);
        $I->seeElement('form');
        $I->submitForm('form', [
            'username' => 'BrayntCliente',
            'password' => '123456',
            'accion'   => 'acceder',
        ]);
        $I->seeResponseCodeIs(200);

        // Cerrar sesión (index.php lee pagina=cerrar y llama a validalogin::destruyesesion())
        $I->amOnPage('/index.php?pagina=cerrar');
        $I->seeResponseCodeIs(200);

        // Verificar que se muestra la página base o que el login está accesible sin sesión
        $I->amOnPage('/index.php?pagina=login');
        $I->seeResponseCodeIs(200);
        $I->see('Iniciar Sesión');
        $I->seeElement('form');
    }

    public function emptyUsernameShowsError(AcceptanceTester $I): void
    {
        $I->amOnPage('/index.php?pagina=login');
        $I->seeResponseCodeIs(200);
        $I->seeElement('form');
        $I->submitForm('form', [
            'username' => '',
            'password' => 'algo',
            'accion'   => 'acceder',
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeElement('#mensajes');
        $tipo = $I->grabAttributeFrom('#mensajes', 'data-tipo');
        \PHPUnit\Framework\Assert::assertSame('error', $tipo);
        $mensaje = $I->grabAttributeFrom('#mensajes', 'data-mensaje');
        \PHPUnit\Framework\Assert::assertNotEmpty($mensaje);
    }

    public function emptyPasswordShowsError(AcceptanceTester $I): void
    {
        $I->amOnPage('/index.php?pagina=login');
        $I->seeResponseCodeIs(200);
        $I->seeElement('form');
        $I->submitForm('form', [
            'username' => 'alguien',
            'password' => '',
            'accion'   => 'acceder',
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeElement('#mensajes');
        $tipo = $I->grabAttributeFrom('#mensajes', 'data-tipo');
        \PHPUnit\Framework\Assert::assertSame('error', $tipo);
        $mensaje = $I->grabAttributeFrom('#mensajes', 'data-mensaje');
        \PHPUnit\Framework\Assert::assertNotEmpty($mensaje);
    }
}
