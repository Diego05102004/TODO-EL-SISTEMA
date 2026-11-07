# Script de PowerShell para corregir rutas en archivos PHP
$rootDir = $PSScriptRoot

# Función para corregir rutas en un archivo
function Fix-FilePaths {
    param (
        [string]$filePath
    )
    
    $content = Get-Content -Path $filePath -Raw
    $originalContent = $content
    
    # Corregir rutas relativas
    $content = $content -replace "(?<=require|include)(_once)?\s*[\(]?\s*['\"](?:\.\./|/)?(?:[mM]odelo/|Modelo/)", "`$1 __DIR__ . '/../modelo/"
    
    # Corregir rutas con directorio actual
    $content = $content -replace "(?<=require|include)(_once)?\s*[\(]?\s*['\"]\./(?:[mM]odelo/|Modelo/)", "`$1 __DIR__ . '/../modelo/"
    
    # Corregir rutas sin directorio
    $content = $content -replace "(?<=require|include)(_once)?\s*[\(]?\s*['\"](?:[mM]odelo/|Modelo/)", "`$1 __DIR__ . '/../modelo/"
    
    # Solo escribir si hubo cambios
    if ($content -ne $originalContent) {
        Set-Content -Path $filePath -Value $content -NoNewline
        Write-Host "Corregido: $filePath"
    }
}

# Recorrer directorios
Get-ChildItem -Path $rootDir -Recurse -Filter "*.php" | ForEach-Object {
    Fix-FilePaths -filePath $_.FullName
}

Write-Host "Proceso de corrección de rutas completado."
