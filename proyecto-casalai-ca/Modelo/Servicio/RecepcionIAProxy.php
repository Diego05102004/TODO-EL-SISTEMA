<?php

namespace Usuario\ProyectoCasalaiCa\Modelo\Servicio;

class RecepcionIAProxy
{
    private $baseUrl;
    private $estadoCircuitoPath;
    private $maxFallos = 3;
    private $enfriamientoSegundos = 120;
    private $authToken;

    public function __construct($baseUrl = null, $estadoCircuitoPath = null)
    {
        $this->baseUrl = rtrim($baseUrl ?: (getenv('RECEPCION_IA_URL') ?: 'http://127.0.0.1:8000'), '/');
        $this->estadoCircuitoPath = $estadoCircuitoPath ?: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'casalai-recepcion-ia-circuit.json';
        $this->authToken = getenv('AUDITOR_RECEPCION_TOKEN') ?: '';
    }

    public function health()
    {
        return $this->solicitar('/health', null, [], false);
    }

    public function extraer($archivo)
    {
        if (!isset($archivo['tmp_name'], $archivo['name'], $archivo['type']) || $archivo['error'] !== UPLOAD_ERR_OK) {
            return $this->respuestaError(400, 'No se recibió una factura válida.');
        }

        $formulario = [
            'imagen' => new \CURLFile($archivo['tmp_name'], $archivo['type'], basename($archivo['name']))
        ];
        return $this->solicitar('/fase1/extraer', $formulario);
    }

    public function verificar($solicitud)
    {
        return $this->solicitar('/fase1/verificar', json_encode($solicitud, JSON_UNESCAPED_UNICODE), ['Content-Type: application/json']);
    }

    public function comparar($archivo, $datosJson)
    {
        if (!isset($archivo['tmp_name'], $archivo['name'], $archivo['type']) || $archivo['error'] !== UPLOAD_ERR_OK) {
            return $this->respuestaError(400, 'No se recibió una factura válida.');
        }

        return $this->solicitar('/fase1/comparar-directo', [
            'imagen' => new \CURLFile($archivo['tmp_name'], $archivo['type'], basename($archivo['name'])),
            'datos_json' => $datosJson
        ]);
    }

    private function solicitar($endpoint, $cuerpo = null, $headers = [], $post = true)
    {
        $estado = $this->actualizarEstadoCircuito(function ($actual) {
            if (($actual['abierto_hasta'] ?? 0) > 0 && $actual['abierto_hasta'] <= time()) {
                return ['fallos' => 0, 'abierto_hasta' => 0];
            }
            return $actual;
        });

        if (($estado['abierto_hasta'] ?? 0) > time()) {
            return $this->respuestaError(503, 'Circuit breaker abierto; el servicio de lectura está en período de enfriamiento.');
        }
        if (!function_exists('curl_init')) {
            return $this->respuestaError(503, 'La extensión cURL no está disponible en PHP.');
        }

        $url = $this->baseUrl . $endpoint;
        $limite = microtime(true) + 17.5;
        $respuesta = false;
        $codigoHttp = 0;
        $errorCurl = '';
        $intento = 0;

        // Agregar header de autenticación si está disponible
        if (!empty($this->authToken)) {
            $headers[] = 'X-Auditor-Token: ' . $this->authToken;
        }

        do {
            $intento++;
            $restanteMs = (int)max(1, ($limite - microtime(true)) * 1000);
            $curl = curl_init($url);
            $opciones = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => $post,
                CURLOPT_CONNECTTIMEOUT_MS => min(2000, $restanteMs),
                CURLOPT_TIMEOUT_MS => $restanteMs,
                CURLOPT_HTTPHEADER => $headers
            ];
            if ($cuerpo !== null) {
                $opciones[CURLOPT_POSTFIELDS] = $cuerpo;
            }
            curl_setopt_array($curl, $opciones);
            $respuesta = curl_exec($curl);
            $errorCurl = curl_error($curl);
            $numeroErrorCurl = curl_errno($curl);
            $codigoHttp = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            $reintentar = $intento < 2 && microtime(true) < $limite - 0.25 &&
                ($numeroErrorCurl === CURLE_COULDNT_CONNECT || in_array($codigoHttp, [502, 503, 504], true));
            if ($reintentar) {
                usleep(200000);
            }
        } while ($reintentar);

        if ($respuesta === false || $codigoHttp === 0 || $codigoHttp === 408 || $codigoHttp === 429 || $codigoHttp >= 500) {
            $this->registrarFalloCircuito();
            $mensaje = $errorCurl ?: 'El microservicio de recepción no está disponible temporalmente.';
            return $this->respuestaError(503, $mensaje);
        }

        $this->registrarExitoCircuito();
        $datos = json_decode($respuesta, true);
        if (!is_array($datos)) {
            return $this->respuestaError(502, 'El microservicio devolvió una respuesta inválida.');
        }

        return ['http_status' => $codigoHttp, 'data' => $datos];
    }

    private function registrarFalloCircuito()
    {
        $this->actualizarEstadoCircuito(function ($estado) {
            $estado['fallos'] = (int)($estado['fallos'] ?? 0) + 1;
            if ($estado['fallos'] >= $this->maxFallos) {
                $estado['abierto_hasta'] = time() + $this->enfriamientoSegundos;
            }
            return $estado;
        });
    }

    private function registrarExitoCircuito()
    {
        $this->actualizarEstadoCircuito(function () {
            return ['fallos' => 0, 'abierto_hasta' => 0];
        });
    }

    private function actualizarEstadoCircuito($actualizar)
    {
        $archivo = @fopen($this->estadoCircuitoPath, 'c+');
        if (!$archivo) {
            return ['fallos' => 0, 'abierto_hasta' => 0];
        }

        flock($archivo, LOCK_EX);
        rewind($archivo);
        $estado = json_decode(stream_get_contents($archivo), true);
        if (!is_array($estado)) {
            $estado = ['fallos' => 0, 'abierto_hasta' => 0];
        }
        $estado = $actualizar($estado);
        rewind($archivo);
        ftruncate($archivo, 0);
        fwrite($archivo, json_encode($estado));
        fflush($archivo);
        flock($archivo, LOCK_UN);
        fclose($archivo);

        return $estado;
    }

    private function respuestaError($codigo, $mensaje)
    {
        return [
            'http_status' => $codigo,
            'data' => ['detail' => $mensaje, 'contingencia' => true]
        ];
    }
}