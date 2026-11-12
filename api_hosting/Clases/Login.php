<?php

declare(strict_types=1);

namespace Usuario\ProyectoCasalaiCa;

use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
use RuntimeException;

class Login extends BD
{
    private ?string $username = null;
    private ?string $password = null;
    private ?PDO $pdo = null;
    
    const MAX_USERNAME = 50;
    const MIN_USERNAME = 3;
    const MAX_PASSWORD = 255;
    const MIN_PASSWORD = 8;
    const MAX_EMAIL = 100;
    const MAX_NOMBRE = 50;
    const MAX_APELLIDO = 50;
    const MAX_TELEFONO = 20;
    const MAX_CEDULA = 20;
    const MIN_CEDULA = 5;
    const MAX_DIRECCION = 200;

    public function __construct($tipo = 'S') {
        parent::__construct($tipo);
    }

    /**
     * @return PDO
     */

    public function getConexion() {
        return parent::getConexion();
    }

    protected function ejecutarConConexionSegura($operation, $tipo = 'S') {
        $db = new BD($tipo); 
        $pdo = $db->getConexion();

        try {
            if (!$pdo instanceof \PDO) {
                throw new \RuntimeException("La conexión PDO [$tipo] no es válida.");
            }

            $pdo->beginTransaction();
            $resultado = $operation($pdo);
            $pdo->commit();
            
            return $resultado;
        } catch (\Exception $e) {
            if ($pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new \RuntimeException("Error en base de datos [$tipo]: " . $e->getMessage());
        } finally {
            $db->cerrar();
        }
    }

    public function __destruct(){
        $this->pdo = null;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }


    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function existe() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $r = array();
            
            // Cargar instancia de Encryption para desencriptar username de la BD
            $encryption = new \Usuario\ProyectoCasalaiCa\Config\Encryption();
            
            $sql = "SELECT 
                        u.id_usuario, 
                        u.id_rol,
                        r.nombre_rol, 
                        u.username, 
                        u.password,
                        u.cedula,
                        u.foto_perfil,
                        u.intentos_fallidos
                    FROM 
                        tbl_usuarios u 
                    INNER JOIN 
                        tbl_rol r ON r.id_rol = u.id_rol";

            $p = $pdo->prepare($sql);
            $p->execute();

            // Obtener todos los usuarios para comparar desencriptando
            $filas = $p->fetchAll(PDO::FETCH_ASSOC);
            $filaEncontrada = null;

            foreach ($filas as $fila) {
                try {
                    // Intentar desencriptar el username de la BD
                    $usernameBD = $encryption->decrypt($fila['username']);
                    
                    // Comparar username desencriptado con el username recibido (ya desencriptado en login.php)
                    if ($usernameBD === $this->username) {
                        $filaEncontrada = $fila;
                        break;
                    }
                } catch (\Throwable $e) {
                    // Si falla el desencriptado, comparar directamente (para compatibilidad con datos no cifrados)
                    if ($fila['username'] === $this->username) {
                        $filaEncontrada = $fila;
                        break;
                    }
                }
            }

            if ($filaEncontrada) {
                // Verificar si el usuario está bloqueado por intentos fallidos
                if ($filaEncontrada['intentos_fallidos'] >= 3) {
                    $r['resultado'] = 'bloqueado';
                    $r['mensaje']   = "Usuario bloqueado por exceder el número de intentos fallidos. Contacte al administrador.";
                } else {
                    if (password_verify($this->password, $filaEncontrada['password'])) {
                        // Contraseña correcta: reiniciar intentos fallidos y permitir acceso
                        $this->reiniciarIntentosFallidos($this->username);
                        
                        $r['resultado']   = 'existe';
                        $r['mensaje']     = $this->username; // Retornar username desencriptado
                        $r['nombre_rol']  = $filaEncontrada['nombre_rol'];
                        $r['id_usuario']  = $filaEncontrada['id_usuario']; 
                        $r['id_rol']      = $filaEncontrada['id_rol']; 
                        $r['cedula']      = $filaEncontrada['cedula'];
                        $r['foto_perfil'] = $filaEncontrada['foto_perfil'];
                    } else {
                        // Contraseña incorrecta: incrementar intentos fallidos
                        $this->incrementarIntentosFallidos($this->username);
                        $nuevosIntentos = $filaEncontrada['intentos_fallidos'] + 1;
                        $intentosRestantes = 3 - $nuevosIntentos;
                        
                        if ($intentosRestantes > 0) {
                            $r['resultado'] = 'noexiste';
                            $r['mensaje']   = "Error en usuario o contraseña. Intentos restantes: $intentosRestantes";
                        } else {
                            $r['resultado'] = 'bloqueado';
                            $r['mensaje']   = "Usuario bloqueado por exceder el número de intentos fallidos. Contacte al administrador.";
                        }
                    }
                }
            } else {
                $r['resultado'] = 'noexiste';
                $r['mensaje']   = "Error en usuario o contraseña!!!";
            }

            return $r;
        }, 'S');
    }

    public function solicitarRecuperacion($email) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($email) {
            
            $p = $pdo->prepare("SELECT id_usuario FROM tbl_usuarios WHERE correo = ?");
            $p->execute([$email]);
            $usuario = $p->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                return ['status' => 'error', 'mensaje' => 'El correo no está registrado'];
            }
            
            $id_usuario = $usuario['id_usuario'];
            
            $token = bin2hex(random_bytes(32));
            $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour')); 
            
            $pDelete = $pdo->prepare("DELETE FROM tbl_recuperar WHERE id_usuario = ?");
            $pDelete->execute([$id_usuario]);
            
            $pInsert = $pdo->prepare("INSERT INTO tbl_recuperar (id_usuario, token, expiracion, fecha, hora) 
                                    VALUES (?, ?, ?, CURDATE(), CURTIME())");
            $pInsert->execute([$id_usuario, $token, $expiracion]);
            
            return [
                'status' => 'success', 
                'token' => $token,
                'id_usuario' => $id_usuario
            ];
            
        }, 'S');
    }

    public function validarToken($id_usuario, $token) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario, $token) {
            
            $sql = "SELECT * FROM tbl_recuperar 
                    WHERE id_usuario = ? 
                    AND token = ? 
                    AND utilizado = 0 
                    AND expiracion > NOW()";

            $p = $pdo->prepare($sql);
            $p->execute([$id_usuario, $token]);
            $resultado = $p->fetch(PDO::FETCH_ASSOC);
            return $resultado ?: null;
        }, 'S');
    }

    public function actualizarPassword($id_usuario, $nueva_password) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario, $nueva_password) {
            
            $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
            
            $p1 = $pdo->prepare("UPDATE tbl_usuarios SET password = ? WHERE id_usuario = ?");
            $p1->execute([$hash, $id_usuario]);
            
            $p2 = $pdo->prepare("UPDATE tbl_recuperar SET utilizado = 1 WHERE id_usuario = ?");
            $p2->execute([$id_usuario]);
            
            return true;
            
        }, 'S');
    }

    public function registrarUsuarioYCliente($datos) {
        return $this->ejecutarConConexionSegura(function($pdoS) use ($datos) {
            
            // Cargar instancia de Encryption para desencriptar username de la BD
            $encryption = new \Usuario\ProyectoCasalaiCa\Config\Encryption();
            
            $p = $pdoS->prepare("SELECT id_usuario, username FROM tbl_usuarios");
            $p->execute();
            $filas = $p->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($filas as $fila) {
                try {
                    // Intentar desencriptar el username de la BD
                    $usernameBD = $encryption->decrypt($fila['username']);
                    
                    // Comparar username desencriptado con el username recibido
                    if ($usernameBD === $datos['nombre_usuario']) {
                        return ['status' => 'error', 'mensaje' => 'El nombre de usuario ya está en uso.'];
                    }
                } catch (\Throwable $e) {
                    // Si falla el desencriptado, comparar directamente
                    if ($fila['username'] === $datos['nombre_usuario']) {
                        return ['status' => 'error', 'mensaje' => 'El nombre de usuario ya está en uso.'];
                    }
                }
            }

            $hash = password_hash($datos['clave'], PASSWORD_DEFAULT);
            $id_rol_cliente = 3;

            $sqlU = "INSERT INTO tbl_usuarios 
                    (username, password, cedula, nombres, apellidos, correo, telefono, id_rol, estatus)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'habilitado')";
            
            $pdoS->prepare($sqlU)->execute([
                $datos['nombre_usuario'],
                $hash,
                $datos['cedula'],
                $datos['nombre'],
                $datos['apellido'],
                $datos['correo'],
                $datos['telefono'],
                $id_rol_cliente
            ]);

            $registroP = $this->insertarClienteEnP($datos);

            if ($registroP['status'] === 'error') {
                throw new \RuntimeException($registroP['mensaje']);
            }

            return [
                'status' => 'success', 
                'mensaje' => 'Usuario y cliente registrados correctamente.'
            ];

        }, 'S');
    }

    private function insertarClienteEnP($datos) {
        return $this->ejecutarConConexionSegura(function($pdoP) use ($datos) {
            $sqlC = "INSERT INTO tbl_clientes 
                    (nombre, cedula, telefono, direccion, correo, activo)
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $pdoP->prepare($sqlC)->execute([
                $datos['nombre'] . ' ' . $datos['apellido'],
                $datos['cedula'],
                $datos['telefono'],
                $datos['direccion'],
                $datos['correo'],
                1
            ]);
            return ['status' => 'success'];
        }, 'P');
    }
    
    // ==================== VALIDACIONES DE BACKEND ====================
    
    /**
     * Valida los datos para iniciar sesión
     */
    private function validarInicioSesion($datos) {
        $errores = [];
        
        // Validación básica - solo verificar que los campos existan y no estén vacíos
        // NOTA: Las validaciones de formato y longitud se hacen en login.php DESPUÉS de desencriptar
        // Este método se mantiene por compatibilidad pero no hace validaciones estrictas
        
        if (!isset($datos['username']) || trim($datos['username']) === '') {
            $errores['username'] = 'El nombre de usuario es obligatorio';
        }
        
        if (!isset($datos['password']) || trim($datos['password']) === '') {
            $errores['password'] = 'La contraseña es obligatoria';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para registrar un usuario
     */
    private function validarRegistroUsuario($datos) {
        $errores = [];
        
        // Validar nombre de usuario
        if (!isset($datos['nombre_usuario'])) {
            $errores['nombre_usuario'] = 'El nombre de usuario es obligatorio';
        } else {
            $nombreUsuario = trim($datos['nombre_usuario']);
            if (empty($nombreUsuario)) {
                $errores['nombre_usuario'] = 'El nombre de usuario es obligatorio';
            } /* elseif (mb_strlen($nombreUsuario) < self::MIN_USERNAME || mb_strlen($nombreUsuario) > self::MAX_USERNAME) {
                $errores['nombre_usuario'] = 'El nombre de usuario debe tener entre ' . self::MIN_USERNAME . ' y ' . self::MAX_USERNAME . ' caracteres';
            }*/ elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $nombreUsuario)) {
                $errores['nombre_usuario'] = 'El nombre de usuario solo puede contener letras, números y guiones bajos';
            }
        }
        
        // Validar contraseña
        if (!isset($datos['clave'])) {
            $errores['clave'] = 'La contraseña es obligatoria';
        } else {
            $clave = $datos['clave'];
            if (empty($clave)) {
                $errores['clave'] = 'La contraseña es obligatoria';
            } elseif (mb_strlen($clave) < 6 || mb_strlen($clave) > 15) {
                $errores['clave'] = 'La contraseña debe tener entre 6 y 15 caracteres';
            } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\{}\[\]|:;"\'<>,.?\/\\\\]).+$/', $clave)) {
                $errores['clave'] = 'La contraseña debe tener al menos una mayúscula, un número y un carácter especial';
            }
        }
        
        // Validar nombre
        if (!isset($datos['nombre'])) {
            $errores['nombre'] = 'El nombre es obligatorio';
        } else {
            $nombre = trim($datos['nombre']);
            if (empty($nombre)) {
                $errores['nombre'] = 'El nombre es obligatorio';
            } elseif (mb_strlen($nombre) > self::MAX_NOMBRE) {
                $errores['nombre'] = 'El nombre no debe exceder los ' . self::MAX_NOMBRE . ' caracteres';
            } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) {
                $errores['nombre'] = 'El nombre solo puede contener letras y espacios';
            }
        }
        
        // Validar apellido
        if (!isset($datos['apellido'])) {
            $errores['apellido'] = 'El apellido es obligatorio';
        } else {
            $apellido = trim($datos['apellido']);
            if (empty($apellido)) {
                $errores['apellido'] = 'El apellido es obligatorio';
            } elseif (mb_strlen($apellido) > self::MAX_APELLIDO) {
                $errores['apellido'] = 'El apellido no debe exceder los ' . self::MAX_APELLIDO . ' caracteres';
            } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $apellido)) {
                $errores['apellido'] = 'El apellido solo puede contener letras y espacios';
            }
        }
        
        // Validar correo
        if (!isset($datos['correo'])) {
            $errores['correo'] = 'El correo electrónico es obligatorio';
        } else {
            $correo = trim($datos['correo']);
            if (empty($correo)) {
                $errores['correo'] = 'El correo electrónico es obligatorio';
            } elseif (mb_strlen($correo) > self::MAX_EMAIL) {
                $errores['correo'] = 'El correo electrónico no debe exceder los ' . self::MAX_EMAIL . ' caracteres';
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $errores['correo'] = 'El formato del correo electrónico no es válido';
            }
        }
        
        // Validar teléfono
        if (!isset($datos['telefono'])) {
            $errores['telefono'] = 'El teléfono es obligatorio';
        } else {
            $telefono = trim($datos['telefono']);
            if (empty($telefono)) {
                $errores['telefono'] = 'El teléfono es obligatorio';
            } elseif (mb_strlen($telefono) > self::MAX_TELEFONO) {
                $errores['telefono'] = 'El teléfono no debe exceder los ' . self::MAX_TELEFONO . ' caracteres';
            } elseif (!preg_match('/^[0-9\-\+\(\)\s]+$/', $telefono)) {
                $errores['telefono'] = 'El teléfono solo puede contener números, guiones, paréntesis y el signo +';
            }
        }
        
        // Validar cédula
        if (!isset($datos['cedula'])) {
            $errores['cedula'] = 'La cédula es obligatoria';
        } else {
            $cedula = trim($datos['cedula']);
            if (empty($cedula)) {
                $errores['cedula'] = 'La cédula es obligatoria';
            } elseif (mb_strlen($cedula) < self::MIN_CEDULA || mb_strlen($cedula) > self::MAX_CEDULA) {
                $errores['cedula'] = 'La cédula debe tener entre ' . self::MIN_CEDULA . ' y ' . self::MAX_CEDULA . ' caracteres';
            } elseif (!preg_match('/^[0-9\.]+$/', $cedula)) {
                $errores['cedula'] = 'La cédula solo puede contener números y puntos';
            }
        }
        
        // Validar dirección (opcional)
        if (isset($datos['direccion'])) {
            $direccion = trim($datos['direccion']);
            if (!empty($direccion) && mb_strlen($direccion) > self::MAX_DIRECCION) {
                $errores['direccion'] = 'La dirección no debe exceder los ' . self::MAX_DIRECCION . ' caracteres';
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para iniciar sesión (método público)
     */
    public function validarInicioSesionDatos($datos) {
        return $this->validarInicioSesion($datos);
    }
    
    /**
     * Valida los datos para registrar usuario (método público)
     */
    public function validarRegistroUsuarioDatos($datos) {
        return $this->validarRegistroUsuario($datos);
    }
    
    /**
     * Verifica si un nombre de usuario ya existe
     */
    private function verificarUsernameExistente($username) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($username) {
            try {
                // Cargar instancia de Encryption para desencriptar username de la BD
                $encryption = new \Usuario\ProyectoCasalaiCa\Config\Encryption();
                
                $p = $pdo->prepare("SELECT id_usuario, username FROM tbl_usuarios");
                $p->execute();
                $filas = $p->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($filas as $fila) {
                    try {
                        // Intentar desencriptar el username de la BD
                        $usernameBD = $encryption->decrypt($fila['username']);
                        
                        // Comparar username desencriptado con el username recibido
                        if ($usernameBD === $username) {
                            return true;
                        }
                    } catch (\Throwable $e) {
                        // Si falla el desencriptado, comparar directamente
                        if ($fila['username'] === $username) {
                            return true;
                        }
                    }
                }
                
                return false;
            } catch (PDOException $e) {
                error_log('Error en verificarUsernameExistente: ' . $e->getMessage());
                return false;
            }
        }, 'S');
    }
    
    /**
     * Verifica si un correo electrónico ya existe
     */
    private function verificarCorreoExistente($correo) {
        try {
            $p = $this->co->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE correo = ?");
            $p->execute([$correo]);
            return $p->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Error en verificarCorreoExistente: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si una cédula ya existe
     */
    private function verificarCedulaExistente($cedula) {
        try {
            $p = $this->co->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE cedula = ?");
            $p->execute([$cedula]);
            return $p->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Error en verificarCedulaExistente: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene los intentos fallidos de un usuario por username
     */
    public function obtenerIntentosFallidos($username) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($username) {
            // Cargar instancia de Encryption para desencriptar username de la BD
            $encryption = new \Usuario\ProyectoCasalaiCa\Config\Encryption();
            
            $sql = "SELECT id_usuario, username, intentos_fallidos FROM tbl_usuarios";
            $p = $pdo->prepare($sql);
            $p->execute();
            
            $filas = $p->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($filas as $fila) {
                try {
                    // Intentar desencriptar el username de la BD
                    $usernameBD = $encryption->decrypt($fila['username']);
                    
                    // Comparar username desencriptado con el username recibido
                    if ($usernameBD === $username) {
                        return (int)$fila['intentos_fallidos'];
                    }
                } catch (\Throwable $e) {
                    // Si falla el desencriptado, comparar directamente
                    if ($fila['username'] === $username) {
                        return (int)$fila['intentos_fallidos'];
                    }
                }
            }
            
            return 0;
        }, 'S');
    }
    
    /**
     * Incrementa los intentos fallidos de un usuario
     */
    public function incrementarIntentosFallidos($username) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($username) {
            // Cargar instancia de Encryption para desencriptar username de la BD
            $encryption = new \Usuario\ProyectoCasalaiCa\Config\Encryption();
            
            $sql = "SELECT id_usuario, username FROM tbl_usuarios";
            $p = $pdo->prepare($sql);
            $p->execute();
            
            $filas = $p->fetchAll(PDO::FETCH_ASSOC);
            $idUsuarioEncontrado = null;
            
            foreach ($filas as $fila) {
                try {
                    // Intentar desencriptar el username de la BD
                    $usernameBD = $encryption->decrypt($fila['username']);
                    
                    // Comparar username desencriptado con el username recibido
                    if ($usernameBD === $username) {
                        $idUsuarioEncontrado = $fila['id_usuario'];
                        break;
                    }
                } catch (\Throwable $e) {
                    // Si falla el desencriptado, comparar directamente
                    if ($fila['username'] === $username) {
                        $idUsuarioEncontrado = $fila['id_usuario'];
                        break;
                    }
                }
            }
            
            if ($idUsuarioEncontrado) {
                $sql = "UPDATE tbl_usuarios SET intentos_fallidos = intentos_fallidos + 1 WHERE id_usuario = :id_usuario";
                $p = $pdo->prepare($sql);
                $p->bindParam(':id_usuario', $idUsuarioEncontrado);
                $p->execute();
                
                return $p->rowCount() > 0;
            }
            
            return false;
        }, 'S');
    }
    
    /**
     * Reinicia los intentos fallidos de un usuario (cuando inicia sesión correctamente)
     */
    public function reiniciarIntentosFallidos($username) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($username) {
            // Cargar instancia de Encryption para desencriptar username de la BD
            $encryption = new \Usuario\ProyectoCasalaiCa\Config\Encryption();
            
            $sql = "SELECT id_usuario, username FROM tbl_usuarios";
            $p = $pdo->prepare($sql);
            $p->execute();
            
            $filas = $p->fetchAll(PDO::FETCH_ASSOC);
            $idUsuarioEncontrado = null;
            
            foreach ($filas as $fila) {
                try {
                    // Intentar desencriptar el username de la BD
                    $usernameBD = $encryption->decrypt($fila['username']);
                    
                    // Comparar username desencriptado con el username recibido
                    if ($usernameBD === $username) {
                        $idUsuarioEncontrado = $fila['id_usuario'];
                        break;
                    }
                } catch (\Throwable $e) {
                    // Si falla el desencriptado, comparar directamente
                    if ($fila['username'] === $username) {
                        $idUsuarioEncontrado = $fila['id_usuario'];
                        break;
                    }
                }
            }
            
            if ($idUsuarioEncontrado) {
                $sql = "UPDATE tbl_usuarios SET intentos_fallidos = 0 WHERE id_usuario = :id_usuario";
                $p = $pdo->prepare($sql);
                $p->bindParam(':id_usuario', $idUsuarioEncontrado);
                $p->execute();
                
                return $p->rowCount() > 0;
            }
            
            return false;
        }, 'S');
    }
    
    /**
     * Verifica si un usuario está bloqueado por intentos fallidos
     */
    public function estaUsuarioBloqueado($username) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($username) {
            // Cargar instancia de Encryption para desencriptar username de la BD
            $encryption = new \Usuario\ProyectoCasalaiCa\Config\Encryption();
            
            $sql = "SELECT id_usuario, username, intentos_fallidos FROM tbl_usuarios";
            $p = $pdo->prepare($sql);
            $p->execute();
            
            $filas = $p->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($filas as $fila) {
                try {
                    // Intentar desencriptar el username de la BD
                    $usernameBD = $encryption->decrypt($fila['username']);
                    
                    // Comparar username desencriptado con el username recibido
                    if ($usernameBD === $username) {
                        return $fila['intentos_fallidos'] >= 3;
                    }
                } catch (\Throwable $e) {
                    // Si falla el desencriptado, comparar directamente
                    if ($fila['username'] === $username) {
                        return $fila['intentos_fallidos'] >= 3;
                    }
                }
            }
            
            return false;
            
            $resultado = $p->fetch(PDO::FETCH_ASSOC);
            return $resultado ? (int)$resultado['intentos_fallidos'] >= 3 : false;
        }, 'S');
    }
}

