<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;

use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
use RuntimeException;

class DolarService {
    private $url = 'https://www.bcv.org.ve';

    public function obtenerPrecioDolar() {
        try {
            // Usar cURL para obtener el contenido de la página
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new \RuntimeException('Error de cURL: ' . curl_error($ch));
            }
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new \RuntimeException('El BCV denegó el acceso. Código HTTP: ' . $httpCode);
            }
            
            if (preg_match('/id=["\']dolar["\'].*?([\d]+[\.,][\d]+)/s', $html, $matches)) {
                // Limpiar el formato (quitar puntos de miles si existen y cambiar coma decimal por punto)
                $precioDolar = str_replace(',', '.', str_replace('.', '', $matches[1]));
                $valor = floatval($precioDolar);
                
                // Guardar en la base de datos
                $this->guardarPrecioCache($valor);
                return $valor;
            } else {
                file_put_contents(__DIR__ . '/bcv_debug.html', $html);
                throw new \RuntimeException('La estructura HTML del BCV cambió. No se encontró ningún valor numérico cerca de id="dolar".');
            }
            
        } catch (\Exception $e) {
            error_log('Fallo Crítico en Módulo Dólar: ' . $e->getMessage());
            return $this->obtenerPrecioCache();
        }
    }

    public function obtenerRegistroDelDia() {
        try {
            $conexion = new BD('P');
            $db = $conexion->getConexion();
            $stmt = $db->prepare("SELECT precio, fecha FROM dolar_cache WHERE DATE(fecha) = CURDATE() ORDER BY fecha DESC LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
            // Si no hay registro del día, obtener desde fuente y devolver el nuevo registro
            $precio = $this->obtenerPrecioDolar();
            // Reconsultar para obtener fecha actualizada
            $stmt = $db->prepare("SELECT precio, fecha FROM dolar_cache WHERE DATE(fecha) = CURDATE() ORDER BY fecha DESC LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        } catch (\Exception $e) {
            error_log('Error al obtener registro del día del dólar: ' . $e->getMessage());
        }
        // Fallback: último registro disponible
        return $this->obtenerUltimoRegistro();
    }

    public function obtenerPrecioDelDia() {
        $registro = $this->obtenerRegistroDelDia();
        return isset($registro['precio']) ? floatval($registro['precio']) : $this->obtenerPrecioCache();
    }

    private function obtenerUltimoRegistro() {
        try {
            $conexion = new BD('P');
            $db = $conexion->getConexion();
            $stmt = $db->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row;
        } catch (\Exception $e) {
            error_log('Error al obtener último registro del dólar: ' . $e->getMessage());
        }
        return ['precio' => 35.50, 'fecha' => date('Y-m-d H:i:s')];
    }
    
    private function obtenerPrecioCache() {
        // Intentar obtener de la base de datos
        try {
            $conexion = new BD('P');
            $db = $conexion->getConexion();
            
            $stmt = $db->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && (time() - strtotime($result['fecha'])) < 86400) { // Usar cache si tiene menos de 24 horas
                return floatval($result['precio']);
            }
        } catch (\Exception $e) {
            error_log('Error al obtener cache del dólar: ' . $e->getMessage());
        }
        
        // Valor por defecto si no hay cache válido
        return 35.50;
    }
    
    public function guardarPrecioCache($precio) {
        try {
            $conexion = new BD('P');
            $db = $conexion->getConexion();
            
            $stmt = $db->prepare("SELECT id FROM dolar_cache WHERE DATE(fecha) = CURDATE() ORDER BY fecha DESC LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && isset($row['id'])) {
                $update = $db->prepare("UPDATE dolar_cache SET precio = ?, fecha = NOW() WHERE id = ?");
                $update->execute([$precio, $row['id']]);
            } else {
                $insert = $db->prepare("INSERT INTO dolar_cache (precio, fecha) VALUES (?, NOW())");
                $insert->execute([$precio]);
            }
            return true;
        } catch (\Exception $e) {
            error_log('Error al guardar cache del dólar: ' . $e->getMessage());
            return false;
        }
    }
}
?>