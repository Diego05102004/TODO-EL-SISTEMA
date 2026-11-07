<?php
// tests/graybox_cuenta_test.php
// Ejecutar: php -S localhost:8001 -t . (opcional) o php tests/graybox_cuenta_test.php

// Pequeño runner de aserciones
function assertEquals($expected, $actual, $message) {
    if ($expected !== $actual) {
        echo "[FAIL] $message\nEsperado: ";
        var_export($expected);
        echo "\nActual: ";
        var_export($actual);
        echo "\n\n";
        exit(1);
    } else {
        echo "[OK] $message\n";
    }
}

// Iniciar sesión simulada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['id_usuario'] = 1;
$_SESSION['id_rol'] = 2;

// Dobles de prueba (stubs) inyectados vía $GLOBALS y usados por resolve*()
$GLOBALS['__test_permisos'] = new class {
    public function getPermisosPorRolModulo() { return []; }
    public function getPermisosUsuarioModulo($idRol, $modulo) { return ['leer' => true, 'escribir' => true]; }
};

class SpyBitacora {
    public $entries = [];
    public function registrarBitacora($idUsuario, $modulo, $accion, $detalle, $nivel) {
        $this->entries[] = compact('idUsuario','modulo','accion','detalle','nivel');
        return true;
    }
}
$GLOBALS['__test_bitacora'] = new SpyBitacora();

class StubCuentabanco {
    private $data = [];
    private $lastId = 101;
    private $exists = false;

    public function setNombreBanco($v) { $this->data['nombre_banco'] = $v; }
    public function setNumeroCuenta($v) { $this->data['numero_cuenta'] = $v; }
    public function setRifCuenta($v) { $this->data['rif_cuenta'] = $v; }
    public function setTelefonoCuenta($v) { $this->data['telefono_cuenta'] = $v; }
    public function setCorreoCuenta($v) { $this->data['correo_cuenta'] = $v; }
    public function setMetodosPago($arr) { $this->data['metodos_pago'] = $arr; }

    public function existeNumeroCuenta($numero, $exceptId = null) {
        return $this->exists; // configurable si se desea
    }

    public function registrarCuentabanco() {
        $this->data['id_cuenta'] = $this->lastId;
        return true;
    }

    public function obtenerUltimaCuenta() {
        return $this->data + [ 'id_cuenta' => $this->lastId ];
    }

    public function consultarCuentabanco() {
        return [ ['id_cuenta' => 1, 'nombre_banco' => 'A'] ];
    }

    public function setIdCuenta($id) { $this->data['id_cuenta'] = $id; }
    public function modificarCuentabanco($id) { return true; }
    public function obtenerCuentaPorId($id) { return ['id_cuenta' => $id, 'nombre_banco' => 'Mock']; }
    public function eliminarCuentabanco($id) { return ['status' => 'success']; }
    public function cambiarEstado($estado) { return true; }
}
$GLOBALS['__test_cuentabanco'] = new StubCuentabanco();

function runController(array $post) {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = $post;
    ob_start();
    require __DIR__ . '/../controlador/cuenta.php';
    $out = ob_get_clean();
    // Si el controlador ya imprimió JSON y salió, out puede estar vacío por ob_clean; capturamos salida directa
    if (empty($out) && function_exists('http_response_code')) {
        // no-op; intentamos leer del buffer ya enviado
    }
    // Intentar decodificar último bloque de salida
    $json = json_decode($out, true);
    if ($json === null) {
        // Puede que se haya enviado por echo antes de cambiar buffers; volvemos a ejecutar capturando directamente
        return $out;
    }
    return $json;
}

// Caso 1: registrar cuenta (debe devolver success y escribir bitácora)
$response = runController([
    'accion' => 'registrar',
    'nombre_banco' => 'Banco Prueba',
    'numero_cuenta' => '0102-123456-78-9012345678',
    'rif_cuenta' => 'J-12345678-9',
    'telefono_cuenta' => '04120000000',
    'correo_cuenta' => 'banco@prueba.com',
    'metodos_pago' => ['pago_movil','transferencia']
]);
assertEquals('success', $response['status'] ?? null, 'Registrar cuenta devuelve success');
assertEquals('Banco Prueba', $response['cuenta']['nombre_banco'] ?? null, 'El payload contiene la cuenta registrada');

$bitacora = $GLOBALS['__test_bitacora'];
assertEquals(true, !empty($bitacora->entries), 'Se registró entrada en bitácora');

// Caso 2: obtener_cuenta debe retornar el objeto correcto (valida fix de variable)
$response2 = runController([
    'accion' => 'obtener_cuenta',
    'id_cuenta' => 77
]);
assertEquals(77, $response2['id_cuenta'] ?? null, 'obtener_cuenta devuelve el ID solicitado');

echo "\nTodas las pruebas de caja gris pasaron.\n";
