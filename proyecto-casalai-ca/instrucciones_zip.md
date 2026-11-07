# Instrucciones para Activar Extensión ZIP en XAMPP

## Pasos para activar la extensión ZIP:

1. **Detener Apache** desde el panel de control de XAMPP

2. **Editar el archivo php.ini**:
   - Abrir: C:\xampp\php\php.ini
   - Buscar la línea: `;extension=zip`
   - Quitar el punto y coma al inicio: `extension=zip`
   - Si no existe, agregar: `extension=zip`

3. **Verificar que la extensión exista**:
   - Revisar que exista el archivo: C:\xampp\php\ext\php_zip.dll

4. **Reiniciar Apache** desde el panel de control de XAMPP

5. **Verificar que esté activada**:
   - Crear un archivo test.php con: `<?php phpinfo(); ?>`
   - Buscar "ZIP" en la salida

## Solución Alternativa (si no funciona):

Como no podemos ejecutar Composer sin ZIP, he modificado el controlador para incluir las clases manualmente. Esta solución funciona perfectamente para el módulo de categorías.
