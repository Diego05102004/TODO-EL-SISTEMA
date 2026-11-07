const { Builder, By, until } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');

(async function pruebaSelenium() {
  console.log('Iniciando prueba de Selenium...');
  
  try {
    // Configuración del navegador
    const options = new chrome.Options();
    options.addArguments('--headless');
    options.addArguments('--disable-gpu');
    options.addArguments('--window-size=1920,1080');

    console.log('Inicializando el navegador...');
    const driver = await new Builder()
      .forBrowser('chrome')
      .setChromeOptions(options)
      .build();

    try {
      console.log('Navegando a Google...');
      await driver.get('https://www.google.com');
      
      const title = await driver.getTitle();
      console.log('Título de la página:', title);
      
      if (title.includes('Google')) {
        console.log('✅ Prueba exitosa: El título contiene "Google"');
      } else {
        console.error('❌ Error: El título no es el esperado');
      }
    } finally {
      console.log('Cerrando el navegador...');
      await driver.quit();
    }
  } catch (error) {
    console.error('❌ Error durante la prueba:', error);
  }
})();
