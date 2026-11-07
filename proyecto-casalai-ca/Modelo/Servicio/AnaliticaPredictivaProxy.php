<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Servicio;

use RuntimeException;

class AnaliticaPredictivaProxy
{
    private $baseUrl;
    private $token;

    public function __construct($baseUrl = null, $token = null)
    {
        $this->baseUrl = rtrim($baseUrl ?: (getenv('ANALITICA_PREDICTIVA_URL') ?: 'http://127.0.0.1:8000'), '/');
        $this->token = $token ?: (getenv('ANALITICA_PREDICTIVA_TOKEN') ?: '7u6ssg9js6_hn8rkpIgmSjITmodKqa7TVzTEcz_wi3M');
    }

    public function obtenerDashboard(array $datos)
    {
        return $this->solicitar('/api/v1/analitica/dashboard', $datos, 12);
    }

    public function entrenar(array $datos)
    {
        return $this->solicitar('/api/v1/analitica/entrenar', $datos, 30);
    }

    public function guardarFeedback(array $datos)
    {
        return $this->solicitar('/api/v1/analitica/feedback', $datos, 8);
    }

    private function solicitar($endpoint, array $datos, $timeout)
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('La extensión cURL no está disponible en PHP.');
        }
        if (strlen($this->token) < 32) {
            throw new RuntimeException('Configure ANALITICA_PREDICTIVA_TOKEN con al menos 32 caracteres en PHP y FastAPI.');
        }

        $cuerpo = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $curl = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $cuerpo,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Analitica-Token: ' . $this->token
            ],
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => $timeout
        ]);

        $respuesta = curl_exec($curl);
        $errorCurl = curl_error($curl);
        $codigoHttp = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($respuesta === false) {
            throw new RuntimeException('No se pudo conectar con el microservicio de analítica: ' . $errorCurl);
        }

        $resultado = json_decode($respuesta, true);
        if (!is_array($resultado)) {
            throw new RuntimeException('El microservicio de analítica devolvió una respuesta inválida.');
        }
        if ($codigoHttp < 200 || $codigoHttp >= 300) {
            $detalle = $resultado['detail'] ?? 'Error HTTP ' . $codigoHttp;
            throw new RuntimeException('El microservicio de analítica respondió con error: ' . $detalle);
        }

        return $resultado;
    }
}
