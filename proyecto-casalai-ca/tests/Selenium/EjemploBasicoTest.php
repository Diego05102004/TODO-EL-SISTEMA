<?php

namespace Tests\Selenium;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use PHPUnit\Framework\TestCase;

class EjemploBasicoTest extends TestCase
{
    /**
     * @var RemoteWebDriver
     */
    protected $webDriver;

    protected function setUp(): void
    {
        // Configuración del WebDriver
        $host = 'http://localhost:4444';
        $capabilities = DesiredCapabilities::chrome();
        
        // Iniciar el navegador
        $this->webDriver = RemoteWebDriver::create($host, $capabilities);
    }

    public function testBusquedaEnGoogle()
    {
        // Navegar a Google
        $this->webDriver->get('https://www.google.com');
        
        // Aceptar cookies si aparece el banner
        try {
            $aceptarCookies = $this->webDriver->findElement(WebDriverBy::id('L2AGLb'));
            if ($aceptarCookies->isDisplayed()) {
                $aceptarCookies->click();
            }
        } catch (\Exception $e) {
            // Si no encuentra el botón de cookies, continuar
        }
        
        // Buscar el campo de búsqueda y escribir en él
        $busqueda = $this->webDriver->findElement(WebDriverBy::name('q'));
        $busqueda->sendKeys('php-webdriver');
        $busqueda->submit();
        
        // Esperar a que carguen los resultados
        sleep(2);
        
        // Verificar que los resultados contienen el texto esperado
        $this->assertStringContainsString(
            'GitHub - php-webdriver/php-webdriver',
            $this->webDriver->getPageSource()
        );
    }

    protected function tearDown(): void
    {
        // Cerrar el navegador
        if ($this->webDriver) {
            $this->webDriver->quit();
        }
    }
}
