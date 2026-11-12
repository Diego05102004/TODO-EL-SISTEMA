<?php
namespace Usuario\ProyectoCasalaiCa;
use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Config\Encryption;
use PDO;
use PDOException;
use RuntimeException;
class Usuarios extends BD {
    
    private $id_usuario;
    private $username;
    private $clave;
    private $id_rol;
    private $activo = 'habilitado';
    private $tableusuarios = 'tbl_usuarios';
    private $nombre;
    private $apellido;
    private $correo;
    private $telefono;
    private $estatus = 1;
    private $usuarios;
    private $cedula;
    private $encryption;
    
    // Campos que deben ser cifrados (NOTA: cédula NO se cifra porque se usa para búsquedas)
    // Los nombres de campos deben coincidir con los de la base de datos (nombres, apellidos)
    const CAMPOS_CIFRADOS = ['nombres', 'apellidos', 'correo', 'telefono'];

    // Constantes de validación
    const MAX_ID_USUARIO = 999999999;
    const MIN_ID_USUARIO = 1;
    const MAX_USERNAME = 255;
    const MIN_USERNAME = 3;
    const MAX_CLAVE = 255;
    const MIN_CLAVE = 8;
    const MAX_NOMBRE = 255;
    const MIN_NOMBRE = 2;
    const MAX_APELLIDO = 255;
    const MIN_APELLIDO = 2;
    const MAX_CORREO = 255;
    const MAX_TELEFONO = 255;
    const MIN_TELEFONO = 7;
    const MAX_CEDULA = 10;
    const MIN_CEDULA = 6;
    const MAX_ID_ROL = 999999999;
    const MIN_ID_ROL = 1;
    const ESTADOS_VALIDOS = ['habilitado', 'inhabilitado'];
    
    // Constantes adicionales para validaciones
    const MAX_REGISTROS_PAGINA = 100;
    const MAX_RANGO_FECHAS_DIAS = 365;
    const FORMATOS_REPORTE = ['pdf', 'excel', 'csv'];
    const MAX_FOTO_PERFIL = 5242880; // 5MB en bytes
    const MIN_FOTO_PERFIL = 100; // 100 bytes mínimo
    const CAMPOS_OBLIGATORIOS = ['username', 'clave', 'nombre', 'apellido', 'cedula', 'id_rol'];

    public function getUsername() { return $this->username; }
    public function setUsername($username) { $this->username = $username; }

    public function getActivo() { return $this->activo; }
    public function setActivo($activo) { $this->activo = $activo; }

    public function getUsuario() { return $this->usuarios; }
    public function setUsuario($usuario) { $this->usuarios = $usuario; }

    public function getEstatus() { return $this->estatus; }
    public function setEstatus($estatus) { $this->estatus = $estatus; }

    public function getClave() { return $this->clave; }
    public function setClave($clave) { $this->clave = $clave; }

    public function getRango() { return $this->id_rol; }
    public function setRango($id_rol) { $this->id_rol = $id_rol; }

    public function getId() { return $this->id_usuario; }
    public function setId($id_usuario) { $this->id_usuario = $id_usuario; }

    public function getNombre() { return $this->nombre; }
    public function setNombre($nombre) { $this->nombre = $nombre; }

    public function getApellido() { return $this->apellido; }
    public function setApellido($apellido) { $this->apellido = $apellido; }

    public function getCorreo() { return $this->correo; }
    public function setCorreo($correo) { $this->correo = $correo; }

    public function getTelefono() { return $this->telefono; }
    public function setTelefono($telefono) { $this->telefono = $telefono; }

    public function getCedula() { return $this->cedula; }
    public function setCedula($cedula) { $this->cedula = $cedula; }

    public function __construct($tipo = 'S') {
        parent::__construct($tipo);
        $this->encryption = new Encryption();
    }

    /**
     * @return PDO
     */
    public function getConexion() {
        return $this->pdo;
    }
    
    /**
     * @param callable
     * @return mixed
     */

    /**
     * @param callable $operation
     * @param bool $usarTransaccion
     * @param string $tipoConexion 'S' para seguridad, 'P' para principal
     * @return mixed
     */
    protected function ejecutarConConexionSegura($operation, $usarTransaccion = true, $tipoConexion = 'S') {
        try {
            parent::__construct($tipoConexion);
            $pdo = parent::getConexion();

            if (!$pdo instanceof \PDO) {
                throw new \RuntimeException("La conexión PDO no es válida o es nula.");
            }

            // SOLO iniciamos transacción si el flag es true
            if ($usarTransaccion) {
                $pdo->beginTransaction();
            }

            $resultado = $operation($pdo);

            // SOLO confirmamos transacción si el flag es true
            if ($usarTransaccion) {
                $pdo->commit();
            }

            return $resultado;
        } catch (\Exception $e) {
            $pdo = parent::getConexion();
            // SOLO hacemos rollback si correspondía usar transacción y sigue activa
            if ($usarTransaccion && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new \RuntimeException($e->getMessage());
        } finally {
            $this->cerrar();
        }
    }
    
    private function sanitizarDatos($datos) {
        if (!is_array($datos)) {
            return $datos;
        }
        
        $datos_sanitizados = [];
        
        // Sanitizar campos de texto
        $campos_texto = ['username', 'nombre', 'apellido', 'correo', 'telefono', 'cedula'];
        foreach ($campos_texto as $campo) {
            if (isset($datos[$campo])) {
                $datos_sanitizados[$campo] = trim((string)$datos[$campo]);
            }
        }
        
        // Sanitizar campos numéricos
        $campos_numericos = ['id_usuario', 'id_rol'];
        foreach ($campos_numericos as $campo) {
            if (isset($datos[$campo])) {
                $datos_sanitizados[$campo] = is_numeric($datos[$campo]) ? (int)$datos[$campo] : 0;
            }
        }
        
        // Sanitizar contraseña (sin trim para mantener caracteres especiales)
        if (isset($datos['clave'])) {
            $datos_sanitizados['clave'] = (string)$datos['clave'];
        }
        
        // Sanitizar estatus
        if (isset($datos['estatus'])) {
            $datos_sanitizados['estatus'] = trim((string)$datos['estatus']);
        }
        
        // Sanitizar foto de perfil
        if (isset($datos['foto_perfil'])) {
            $datos_sanitizados['foto_perfil'] = $datos['foto_perfil'];
        }
        
        // Mantener otros campos no especificados
        foreach ($datos as $clave => $valor) {
            if (!isset($datos_sanitizados[$clave])) {
                $datos_sanitizados[$clave] = $valor;
            }
        }
        
        return $datos_sanitizados;
    }
    
    private function validarEsquema($datos, $operacion = 'registrar') {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['esquema'] = 'Los datos deben ser un arreglo';
            return $errores;
        }
        
        // Campos obligatorios según la operación
        if ($operacion === 'registrar') {
            foreach (self::CAMPOS_OBLIGATORIOS as $campo) {
                if (!isset($datos[$campo]) || $datos[$campo] === '' || $datos[$campo] === null) {
                    $errores[$campo] = 'El campo ' . $campo . ' es obligatorio';
                }
            }
        } elseif ($operacion === 'modificar') {
            if (!isset($datos['id_usuario']) || $datos['id_usuario'] === '' || $datos['id_usuario'] === null) {
                $errores['id_usuario'] = 'El ID del usuario es obligatorio para modificar';
            }
            
            $campos_modificar = array_intersect(array_keys($datos), self::CAMPOS_OBLIGATORIOS);
            if (empty($campos_modificar)) {
                $errores['modificacion'] = 'Debe proporcionar al menos un campo para modificar';
            }
        }
        
        return $errores;
    }
    
    private function validarFormato($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['datos'] = 'Los datos deben ser un arreglo';
            return $errores;
        }
        
        // Validar username
        if (isset($datos['username'])) {
            $username = trim((string)$datos['username']);
            if ($username === '') {
                $errores['username'] = 'El nombre de usuario es obligatorio';
            }/* elseif (mb_strlen($username) < self::MIN_USERNAME || mb_strlen($username) > self::MAX_USERNAME) {
                $errores['username'] = 'El nombre de usuario debe tener entre ' . self::MIN_USERNAME . ' y ' . self::MAX_USERNAME . ' caracteres';
            }*/ elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $errores['username'] = 'El nombre de usuario solo puede contener letras, números y guiones bajos';
            }
        }
        
        // Validar clave
        if (isset($datos['clave'])) {
            $clave = (string)$datos['clave'];
            if ($clave !== '') {
                if (mb_strlen($clave) < self::MIN_CLAVE) {
                    $errores['clave'] = 'La contraseña debe tener al menos ' . self::MIN_CLAVE . ' caracteres';
                } elseif (!preg_match('/[A-Z]/', $clave)) {
                    $errores['clave'] = 'La contraseña debe incluir al menos una letra mayúscula';
                } elseif (!preg_match('/[a-z]/', $clave)) {
                    $errores['clave'] = 'La contraseña debe incluir al menos una letra minúscula';
                } elseif (!preg_match('/[0-9]/', $clave)) {
                    $errores['clave'] = 'La contraseña debe incluir al menos un número';
                }
            }
        }
        
        // Validar nombre
        if (isset($datos['nombre'])) {
            $nombre = trim((string)$datos['nombre']);
            if ($nombre === '') {
                $errores['nombre'] = 'El nombre es obligatorio';
            } elseif (mb_strlen($nombre) < self::MIN_NOMBRE || mb_strlen($nombre) > self::MAX_NOMBRE) {
                $errores['nombre'] = 'El nombre debe tener entre ' . self::MIN_NOMBRE . ' y ' . self::MAX_NOMBRE . ' caracteres';
            }
        }
        
        // Validar apellido
        if (isset($datos['apellido'])) {
            $apellido = trim((string)$datos['apellido']);
            if ($apellido === '') {
                $errores['apellido'] = 'El apellido es obligatorio';
            } elseif (mb_strlen($apellido) < self::MIN_APELLIDO || mb_strlen($apellido) > self::MAX_APELLIDO) {
                $errores['apellido'] = 'El apellido debe tener entre ' . self::MIN_APELLIDO . ' y ' . self::MAX_APELLIDO . ' caracteres';
            }
        }
        
        // Validar correo
        if (isset($datos['correo'])) {
            $correo = trim((string)$datos['correo']);
            if ($correo === '') {
                $errores['correo'] = 'El correo es obligatorio';
            } elseif (mb_strlen($correo) > self::MAX_CORREO || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $errores['correo'] = 'El correo no es válido';
            }
        }
        
        // Validar teléfono
        if (isset($datos['telefono'])) {
            $telefono = preg_replace('/\D+/', '', (string)$datos['telefono']);
            if ($telefono === '') {
                $errores['telefono'] = 'El teléfono es obligatorio';
            } elseif (strlen($telefono) < self::MIN_TELEFONO || strlen($telefono) > self::MAX_TELEFONO) {
                $errores['telefono'] = 'El teléfono debe tener entre ' . self::MIN_TELEFONO . ' y ' . self::MAX_TELEFONO . ' dígitos';
            }
        }
        
        // Validar cédula
        if (isset($datos['cedula'])) {
            $cedula = preg_replace('/\D+/', '', (string)$datos['cedula']);
            if ($cedula === '') {
                $errores['cedula'] = 'La cédula es obligatoria';
            } elseif (strlen($cedula) < self::MIN_CEDULA || strlen($cedula) > self::MAX_CEDULA) {
                $errores['cedula'] = 'La cédula debe tener entre ' . self::MIN_CEDULA . ' y ' . self::MAX_CEDULA . ' dígitos';
            }
        }
        
        // Validar ID del rol
        if (isset($datos['id_rol'])) {
            $id_rol = (int)$datos['id_rol'];
            if ($id_rol < self::MIN_ID_ROL || $id_rol > self::MAX_ID_ROL) {
                $errores['id_rol'] = 'Debe seleccionar un rol válido';
            }
        }
        
        // Validar estatus
        if (isset($datos['estatus'])) {
            $estatus = trim((string)$datos['estatus']);
            if (!in_array($estatus, self::ESTADOS_VALIDOS)) {
                $errores['estatus'] = 'El estatus no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS);
            }
        }
        
        // Validar foto de perfil
        if (isset($datos['foto_perfil'])) {
            $foto_perfil = $datos['foto_perfil'];
            
            // Validar que sea un array
            if (!is_array($foto_perfil)) {
                $errores['foto_perfil'] = 'Los datos de la foto deben ser un arreglo';
            } else {
                // Validar tamaño del archivo
                if (isset($foto_perfil['size']) && $foto_perfil['size'] < self::MIN_FOTO_PERFIL) {
                    $errores['foto_perfil'] = 'El archivo parece demasiado pequeño para ser una imagen';
                } elseif ($foto_perfil['size'] > self::MAX_FOTO_PERFIL) {
                    $errores['foto_perfil'] = 'El archivo no debe exceder los 5MB';
                }
                
                // Validar tipo de archivo
                if (isset($foto_perfil['type'])) {
                    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!in_array($foto_perfil['type'], $tipos_permitidos)) {
                        $errores['foto_perfil'] = 'La extensión del archivo no es permitida';
                    }
                }
            }
        }
        
        return $errores;
    }
    
    private function validarUsuario($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['usuario'] = 'Los datos del usuario deben ser un arreglo';
            return $errores;
        }
        
        // Validar ID del usuario
        if (isset($datos['id_usuario'])) {
            $id_usuario = (int)$datos['id_usuario'];
            if ($id_usuario < self::MIN_ID_USUARIO || $id_usuario > self::MAX_ID_USUARIO) {
                $errores['id_usuario'] = 'El ID del usuario debe ser un número entre ' . self::MIN_ID_USUARIO . ' y ' . self::MAX_ID_USUARIO;
            }
        }
        
        // Validar username
        if (isset($datos['username'])) {
            $username = trim((string)$datos['username']);
            if ($username === '') {
                $errores['username'] = 'El nombre de usuario es obligatorio';
            } /* elseif (mb_strlen($username) < self::MIN_USERNAME || mb_strlen($username) > self::MAX_USERNAME) {
                $errores['username'] = 'El nombre de usuario debe tener entre ' . self::MIN_USERNAME . ' y ' . self::MAX_USERNAME . ' caracteres';
            }*/ elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $errores['username'] = 'El nombre de usuario solo puede contener letras, números y guiones bajos';
            }
        }
        
        // Validar clave
        if (isset($datos['clave'])) {
            $clave = (string)$datos['clave'];
            if ($clave !== '') {
                if (mb_strlen($clave) < self::MIN_CLAVE) {
                    $errores['clave'] = 'La contraseña debe tener al menos ' . self::MIN_CLAVE . ' caracteres';
                } elseif (!preg_match('/[A-Z]/', $clave)) {
                    $errores['clave'] = 'La contraseña debe incluir al menos una letra mayúscula';
                } elseif (!preg_match('/[a-z]/', $clave)) {
                    $errores['clave'] = 'La contraseña debe incluir al menos una letra minúscula';
                } elseif (!preg_match('/[0-9]/', $clave)) {
                    $errores['clave'] = 'La contraseña debe incluir al menos un número';
                }
            }
        }
        
        // Validar nombre
        if (isset($datos['nombre'])) {
            $nombre = trim((string)$datos['nombre']);
            if ($nombre === '') {
                $errores['nombre'] = 'El nombre es obligatorio';
            } elseif (mb_strlen($nombre) < self::MIN_NOMBRE || mb_strlen($nombre) > self::MAX_NOMBRE) {
                $errores['nombre'] = 'El nombre debe tener entre ' . self::MIN_NOMBRE . ' y ' . self::MAX_NOMBRE . ' caracteres';
            }
        }
        
        // Validar apellido
        if (isset($datos['apellido'])) {
            $apellido = trim((string)$datos['apellido']);
            if ($apellido === '') {
                $errores['apellido'] = 'El apellido es obligatorio';
            } elseif (mb_strlen($apellido) < self::MIN_APELLIDO || mb_strlen($apellido) > self::MAX_APELLIDO) {
                $errores['apellido'] = 'El apellido debe tener entre ' . self::MIN_APELLIDO . ' y ' . self::MAX_APELLIDO . ' caracteres';
            }
        }
        
        // Validar correo
        if (isset($datos['correo'])) {
            $correo = trim((string)$datos['correo']);
            if ($correo === '') {
                $errores['correo'] = 'El correo es obligatorio';
            } elseif (mb_strlen($correo) > self::MAX_CORREO || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $errores['correo'] = 'El correo no es válido';
            }
        }
        
        // Validar teléfono
        if (isset($datos['telefono'])) {
            $telefono = preg_replace('/\D+/', '', (string)$datos['telefono']);
            if ($telefono === '') {
                $errores['telefono'] = 'El teléfono es obligatorio';
            } elseif (strlen($telefono) < self::MIN_TELEFONO || strlen($telefono) > self::MAX_TELEFONO) {
                $errores['telefono'] = 'El teléfono debe tener entre ' . self::MIN_TELEFONO . ' y ' . self::MAX_TELEFONO . ' dígitos';
            }
        }
        
        // Validar cédula
        if (isset($datos['cedula'])) {
            $cedula = preg_replace('/\D+/', '', (string)$datos['cedula']);
            if ($cedula === '') {
                $errores['cedula'] = 'La cédula es obligatoria';
            } elseif (strlen($cedula) < self::MIN_CEDULA || strlen($cedula) > self::MAX_CEDULA) {
                $errores['cedula'] = 'La cédula debe tener entre ' . self::MIN_CEDULA . ' y ' . self::MAX_CEDULA . ' dígitos';
            }
        }
        
        // Validar ID del rol
        if (isset($datos['id_rol'])) {
            $id_rol = (int)$datos['id_rol'];
            if ($id_rol < self::MIN_ID_ROL || $id_rol > self::MAX_ID_ROL) {
                $errores['id_rol'] = 'Debe seleccionar un rol válido';
            }
        }
        
        // Validar estatus
        if (isset($datos['estatus'])) {
            $estatus = trim((string)$datos['estatus']);
            if (!in_array($estatus, self::ESTADOS_VALIDOS)) {
                $errores['estatus'] = 'El estatus no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS);
            }
        }
        
        return $errores;
    }
    
    // Métodos públicos de validación
    public function validarConsultarUsuario($datos) {
        $errores = [];
        
        // Para consultar, podemos validar por ID o sin filtros
        if (isset($datos['id_usuario'])) {
            $id_usuario = (int)$datos['id_usuario'];
            if ($id_usuario < self::MIN_ID_USUARIO || $id_usuario > self::MAX_ID_USUARIO) {
                $errores['id_usuario'] = 'El ID del usuario debe ser un número entre ' . self::MIN_ID_USUARIO . ' y ' . self::MAX_ID_USUARIO;
            }
        }
        
        // Validar estatus si viene
        if (isset($datos['estatus'])) {
            $estatus = trim((string)$datos['estatus']);
            if (!in_array($estatus, self::ESTADOS_VALIDOS) && $estatus !== 'todos') {
                $errores['estatus'] = 'El estatus no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS) . ', todos';
            }
        }
        
        return $errores;
    }
    
    public function validarRegistrarUsuario($datos) {
        $errores = [];
        
        // Para registrar, requerimos campos obligatorios
        $campos_obligatorios = ['username', 'clave', 'nombre', 'apellido', 'correo', 'telefono', 'cedula', 'id_rol'];
        foreach ($campos_obligatorios as $campo) {
            if (!isset($datos[$campo])) {
                $errores[$campo] = 'El campo ' . $campo . ' es obligatorio';
            }
        }
        
        // Validar el usuario completo
        $errores_usuario = $this->validarUsuario($datos);
        if (!empty($errores_usuario)) {
            $errores = array_merge($errores, $errores_usuario);
        }
        
        return $errores;
    }
    
    public function validarModificarUsuario($datos) {
        $errores = [];
        
        // Para modificar, el ID es obligatorio
        if (!isset($datos['id_usuario'])) {
            $errores['id_usuario'] = 'El ID del usuario es obligatorio';
        }
        
        // Para modificar, requerimos campos obligatorios (excepto clave que es opcional)
        $campos_obligatorios = ['username', 'nombre', 'apellido', 'correo', 'telefono', 'cedula', 'id_rol'];
        foreach ($campos_obligatorios as $campo) {
            if (!isset($datos[$campo])) {
                $errores[$campo] = 'El campo ' . $campo . ' es obligatorio';
            }
        }
        
        // Validar el usuario completo
        $errores_usuario = $this->validarUsuario($datos);
        if (!empty($errores_usuario)) {
            $errores = array_merge($errores, $errores_usuario);
        }
        
        return $errores;
    }
    
    public function validarEliminarUsuario($datos) {
        $errores = [];
        
        // Para eliminar, el ID es obligatorio
        if (!isset($datos['id_usuario'])) {
            $errores['id_usuario'] = 'El ID del usuario es obligatorio';
        } else {
            $id_usuario = (int)$datos['id_usuario'];
            if ($id_usuario < self::MIN_ID_USUARIO || $id_usuario > self::MAX_ID_USUARIO) {
                $errores['id_usuario'] = 'El ID del usuario debe ser un número entre ' . self::MIN_ID_USUARIO . ' y ' . self::MAX_ID_USUARIO;
            }
        }
        
        return $errores;
    }
    
    public function validarCambiarEstatus($datos) {
        $errores = [];
        
        // Para cambiar estatus, el ID es obligatorio
        if (!isset($datos['id_usuario'])) {
            $errores['id_usuario'] = 'El ID del usuario es obligatorio';
        } else {
            $id_usuario = (int)$datos['id_usuario'];
            if ($id_usuario < self::MIN_ID_USUARIO || $id_usuario > self::MAX_ID_USUARIO) {
                $errores['id_usuario'] = 'El ID del usuario debe ser un número entre ' . self::MIN_ID_USUARIO . ' y ' . self::MAX_ID_USUARIO;
            }
        }
        
        // Validar estatus
        if (!isset($datos['estatus'])) {
            $errores['estatus'] = 'El estatus es obligatorio';
        } else {
            $estatus = trim((string)$datos['estatus']);
            if (!in_array($estatus, self::ESTADOS_VALIDOS)) {
                $errores['estatus'] = 'El estatus no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS);
            }
        }
        
        return $errores;
    }
    
    public function validarReporte($datos) {
        $errores = [];
        
        // Para reporte, validar parámetros opcionales
        if (isset($datos['estatus'])) {
            $estatus = trim((string)$datos['estatus']);
            if (!in_array($estatus, self::ESTADOS_VALIDOS) && $estatus !== 'todos') {
                $errores['estatus'] = 'El estatus no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS) . ', todos';
            }
        }
        
        return $errores;
    }
    
    public function validarDescarga($datos) {
        $errores = [];
        
        // Para descarga, validar tipo de descarga
        if (isset($datos['tipo_descarga'])) {
            $tipos_validos = ['pdf', 'excel', 'csv'];
            if (!in_array($datos['tipo_descarga'], $tipos_validos)) {
                $errores['tipo_descarga'] = 'El tipo de descarga no es válido. Tipos permitidos: ' . implode(', ', $tipos_validos);
            }
        }
        
        // Validar parámetros adicionales según el tipo
        if (isset($datos['parametros']) && is_array($datos['parametros'])) {
            foreach ($datos['parametros'] as $parametro => $valor) {
                if (is_string($valor) && mb_strlen($valor) > 100) {
                    $errores[$parametro] = 'El parámetro ' . $parametro . ' es demasiado largo';
                }
            }
        }
        
        return $errores;
    }
    
    public function validarActualizarAvatar($datos) {
        $errores = [];
        
        // Validar que se haya subido un archivo
        if (!isset($datos['foto_perfil']) || empty($datos['foto_perfil'])) {
            $errores['foto_perfil'] = 'Debe seleccionar una imagen para el avatar';
            return $errores;
        }
        
        // Validar que el archivo sea un array (información de $_FILES)
        if (!is_array($datos['foto_perfil'])) {
            $errores['foto_perfil'] = 'El formato del archivo no es válido';
            return $errores;
        }
        
        $archivo = $datos['foto_perfil'];
        
        // Validar que no haya error en la subida
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $errores_mensajes = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'No existe directorio temporal',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en disco',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
            ];
            
            $error_msg = $errores_mensajes[$archivo['error']] ?? 'Error desconocido al subir el archivo';
            $errores['foto_perfil'] = $error_msg;
            return $errores;
        }
        
        // Validar tipo de archivo
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($archivo['type'], $tipos_permitidos)) {
            $errores['foto_perfil'] = 'Solo se permiten archivos JPG, PNG, GIF o WebP';
        }
        
        // Validar tamaño (2MB máximo)
        $tamano_maximo = 2 * 1024 * 1024; // 2MB en bytes
        if ($archivo['size'] > $tamano_maximo) {
            $errores['foto_perfil'] = 'La imagen debe ser menor a 2MB';
        }
        
        // Validar extensión del archivo
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $extensiones_permitidas)) {
            $errores['foto_perfil'] = 'La extensión del archivo no es permitida';
        }
        
        // Validar que sea una imagen real (usando getimagesize) - solo si hay tmp_name
        if (!empty($archivo['tmp_name']) && file_exists($archivo['tmp_name'])) {
            $info_imagen = @getimagesize($archivo['tmp_name']);
            if ($info_imagen === false) {
                // Si getimagesize falla, verificar con exif_imagetype (más simple)
                if (function_exists('exif_imagetype')) {
                    $tipo_imagen = @exif_imagetype($archivo['tmp_name']);
                    if ($tipo_imagen === false) {
                        $errores['foto_perfil'] = 'El archivo no es una imagen válida';
                    }
                } else {
                    // Si tampoco funciona, verificar por extensión y tamaño mínimo
                    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
                    if (!in_array($extension, $extensiones_permitidas)) {
                        $errores['foto_perfil'] = 'La extensión del archivo no es permitida';
                    } elseif ($archivo['size'] < 100) { // Mínimo 100 bytes
                        $errores['foto_perfil'] = 'El archivo parece demasiado pequeño para ser una imagen';
                    }
                }
            }
        }
        
        return $errores;
    }

    public function ingresarUsuario($id_usuario_auditor) {
        return $this->i_ingresarUsuario($id_usuario_auditor);
    }
    private function i_ingresarUsuario($id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario_auditor) {
            
            $claveEncriptada = password_hash($this->clave, PASSWORD_BCRYPT);
            
            // Cifrar datos personales antes de insertar
            $nombre_cifrado = $this->encryption->encrypt($this->nombre);
            $apellido_cifrado = $this->encryption->encrypt($this->apellido);
            $correo_cifrado = $this->encryption->encrypt($this->correo);
            $telefono_cifrado = $this->encryption->encrypt($this->telefono);

            $sql = "CALL sp_incluir_usuario(
                :username, 
                :clave, 
                :cedula, 
                :id_rol, 
                :correo, 
                :nombres, 
                :apellidos,
                :telefono, 
                :id_usuario_auditor
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':username', $this->username);
            $stmt->bindParam(':clave', $claveEncriptada);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->bindParam(':id_rol', $this->id_rol);
            $stmt->bindParam(':correo', $correo_cifrado);
            $stmt->bindParam(':nombres', $nombre_cifrado);
            $stmt->bindParam(':apellidos', $apellido_cifrado);
            $stmt->bindParam(':telefono', $telefono_cifrado);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);

            $resultado = $stmt->execute();
            $stmt->closeCursor();
            return $resultado ? true : false;
        }, false);
    }

    public function modificarUsuario($id_usuario, $id_usuario_auditor) {
        return $this->m_modificarUsuario($id_usuario, $id_usuario_auditor);
    }

    private function m_modificarUsuario($id_usuario, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario, $id_usuario_auditor) {
            
            // 1. Cifrar datos personales antes de actualizar
            $nombre_cifrado   = $this->encryption->encrypt($this->nombre);
            $apellido_cifrado = $this->encryption->encrypt($this->apellido);
            $correo_cifrado   = $this->encryption->encrypt($this->correo);
            $telefono_cifrado = $this->encryption->encrypt($this->telefono);
            
            // 2. Sentencia SQL fija con 9 parámetros (Sin clave)
            $sql = "CALL sp_modificar_usuario(
                :id_usuario,
                :username,  
                :cedula, 
                :id_rol, 
                :correo, 
                :nombres, 
                :apellidos,
                :telefono, 
                :id_usuario_auditor
            )";
            
            $stmt = $pdo->prepare($sql);
            
            // 3. Vinculación exacta de los 9 parámetros
            $stmt->bindValue(':id_usuario', $id_usuario, \PDO::PARAM_INT);
            $stmt->bindValue(':username', $this->username);
            $stmt->bindValue(':cedula', $this->cedula);
            $stmt->bindValue(':id_rol', $this->id_rol, \PDO::PARAM_INT);
            $stmt->bindValue(':correo', $correo_cifrado);
            $stmt->bindValue(':nombres', $nombre_cifrado);
            $stmt->bindValue(':apellidos', $apellido_cifrado);
            $stmt->bindValue(':telefono', $telefono_cifrado);
            $stmt->bindValue(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
            
            // 4. Ejecución
            $resultado = $stmt->execute();
            $stmt->closeCursor();
            
            return $resultado;
        }, false);
    }

    public function existeUsuario($username, $excluir_id = null) {
        return $this->e_existeUsuario($username, $excluir_id);
    }
    private function e_existeUsuario($username, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($username, $excluir_id) {
            
            $sql = "SELECT id_usuario, username FROM tbl_usuarios";
            $params = [];

            if ($excluir_id !== null) {
                $sql .= " WHERE id_usuario != ?";
                $params[] = $excluir_id;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($filas as $fila) {
                try {
                    // Intentar desencriptar el username de la BD
                    $usernameBD = $this->encryption->decrypt($fila['username']);
                    
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

        });
    }

    public function existeCedula($cedula, $excluir_id = null) {
        return $this->e_existeCedula($cedula, $excluir_id);
    }
    private function e_existeCedula($cedula, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($cedula, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE cedula = ?";
            $params = [$cedula];

            if ($excluir_id !== null) {
                $sql .= " AND id_usuario != ?";
                $params[] = $excluir_id;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        });
    }

    public function existeCorreo($correo, $excluir_id = null) {
        return $this->e_existeCorreo($correo, $excluir_id);
    }

    private function e_existeCorreo($correo, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($correo, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE correo = ?";
            $params = [$correo];

            if ($excluir_id !== null) {
                $sql .= " AND id_usuario != ?";
                $params[] = $excluir_id;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        });
    }

    public function obtenerUltimoUsuario() {
        return $this->o_ultimoUsuario();
    }
    private function o_ultimoUsuario() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT usuarios.*, rol.nombre_rol 
                    FROM tbl_usuarios AS usuarios
                    INNER JOIN tbl_rol AS rol ON usuarios.id_rol = rol.id_rol
                    ORDER BY usuarios.id_usuario DESC LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $usuario ?: null;
        });
        
        // Descifrar datos personales
        if ($resultado) {
            $resultado = $this->encryption->decryptArray($resultado, self::CAMPOS_CIFRADOS);
        }
        
        return $resultado;
    }

    public function obtenerUsuarioPorId($id_usuario) {
        return $this->o_usuarioPorId($id_usuario);
    }
    private function o_usuarioPorId($id_usuario) {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario) {
            // Cambiamos la consulta estructurada por la invocación al procedimiento
            $query = "CALL sp_obtener_usuario_por_id(:id_usuario)";
            $stmt = $pdo->prepare($query);

            // Pasamos el ID de forma asociativa y segura
            $stmt->execute([':id_usuario' => $id_usuario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            // CRUCIAL: Liberar el cursor para que no tranque futuras consultas en la misma petición PHP
            $stmt->closeCursor(); 

            return $usuario;
        }, false);
        
        // Descifrar datos personales
        if ($resultado) {
            $resultado = $this->encryption->decryptArray($resultado, self::CAMPOS_CIFRADOS);
        }
        
        return $resultado;
    }

    public function eliminarUsuario($id_usuario, $id_usuario_auditor) {
        return $this->d_eliminarUsuario($id_usuario, $id_usuario_auditor);
    }
    private function d_eliminarUsuario($id_usuario, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario, $id_usuario_auditor) {
            $sql = "CALL sp_eliminar_usuario(:id_usuario, :id_usuario_auditor)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':id_usuario', $id_usuario, \PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);

            $result = $stmt->execute();
            $stmt->closeCursor();
            return $result;
        }, false);
    }

    public function cambiarEstatus($nuevoEstatus, $id_usuario_auditor) {
        return $this->c_cambiarEstatus($nuevoEstatus, $id_usuario_auditor);
    }
    private function c_cambiarEstatus($nuevoEstatus, $id_usuario_auditor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nuevoEstatus, $id_usuario_auditor) {
            $sql = "CALL sp_cambiar_estatus_usuario(:id_usuario, :estatus, :id_usuario_auditor)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':estatus', $nuevoEstatus);
            $stmt->bindParam(':id_usuario', $this->id_usuario, \PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);

            $resultado = $stmt->execute();
            $stmt->closeCursor();
            return $resultado;
        }, false);
    }

    public function obtenerReporteRoles() {
        return $this->r_reporteRoles();
    }
    private function r_reporteRoles() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT rol.nombre_rol, COUNT(u.id_usuario) as cantidad
                    FROM tbl_rol rol
                    LEFT JOIN tbl_usuarios u ON rol.id_rol = u.id_rol
                    GROUP BY rol.id_rol, rol.nombre_rol
                    ORDER BY cantidad DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function actualizarPerfil($id_usuario, $datos) {
        return $this->a_actualizarPerfil($id_usuario, $datos);
    }

    private function a_actualizarPerfil($id_usuario, $datos) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario, $datos) {
            $params = [':id_usuario' => $id_usuario];
            $updates = [];

            foreach ($datos as $campo => $valor) {
                if ($valor !== '' || $campo === 'password') {
                    $updates[] = "$campo = :$campo";
                    $params[":$campo"] = ($campo === 'password') 
                        ? password_hash($valor, PASSWORD_BCRYPT) 
                        : $valor;
                }
            }

            if (empty($updates)) {
                return false;
            }

            $sql = "UPDATE tbl_usuarios SET " . implode(", ", $updates) . " WHERE id_usuario = :id_usuario";

            $stmt = $pdo->prepare($sql);
            return $stmt->execute($params);
            
        });
    }

    public function getusuarios($estatus = 'habilitado') {
        return $this->g_getusuarios($estatus);
    }
    private function g_getusuarios($estatus = 'habilitado') {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($estatus){
            $queryusuarios = "CALL sp_consultar_usuario(:estatus)";

            $stmtusuarios = $pdo->prepare($queryusuarios);
            $stmtusuarios->bindParam(':estatus', $estatus, PDO::PARAM_STR);

            $stmtusuarios->execute();
            $usuarios = $stmtusuarios->fetchAll(PDO::FETCH_ASSOC);
            $stmtusuarios->closeCursor();

            return $usuarios;
        }, false);

        // Descifrar datos personales
        $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS);

        return $resultado;
    }

    // Métodos para la API de registro de cliente
    public function registrarCliente($data) {
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;
        $nombres = $data['nombres'] ?? null;
        $apellidos = $data['apellidos'] ?? null;
        $cedula = $data['cedula'] ?? null;
        $correo = $data['correo'] ?? null;
        $telefono = $data['telefono'] ?? null;
        $direccion = $data['direccion'] ?? null;
        
        // Validaciones básicas
        if (empty($username) || empty($password)) {
            throw new RuntimeException('El nombre de usuario y contraseña son obligatorios');
        }
        
        if (empty($nombres) || empty($apellidos)) {
            throw new RuntimeException('Los nombres y apellidos son obligatorios');
        }
        
        if (empty($cedula)) {
            throw new RuntimeException('La cédula es obligatoria');
        }
        
        if (empty($correo)) {
            throw new RuntimeException('El correo es obligatorio');
        }
        
        // Validar formato de email
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El formato del correo electrónico no es válido');
        }
        
        // Validar longitud de contraseña
        if (strlen($password) < 6) {
            throw new RuntimeException('La contraseña debe tener al menos 6 caracteres');
        }
        
        // Limpiar cédula: quitar puntos, guiones y espacios
        $cedula = preg_replace('/[^0-9]/', '', $cedula);
        
        // Limpiar teléfono: quitar espacios
        $telefono = preg_replace('/\s+/', '', $telefono ?? '');
        
        error_log("Cédula limpia: $cedula");
        error_log("Teléfono limpio: $telefono");
        
        // Verificar que la cédula no esté vacía después de limpiar
        if (empty($cedula)) {
            throw new RuntimeException('La cédula no es válida');
        }
        
        // Usar la misma lógica que Login.php: insertar en ambas bases de datos
        return $this->ejecutarConConexionSegura(function($pdoS) use ($username, $password, $nombres, $apellidos, $cedula, $correo, $telefono, $direccion) {
            try {
                error_log("Iniciando registro de cliente - Username: $username");
                
                // 1. Verificar que el username no exista en tbl_usuarios (base S)
                $p = $pdoS->prepare("SELECT id_usuario, username FROM tbl_usuarios");
                $p->execute();
                $filas = $p->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($filas as $fila) {
                    try {
                        // Intentar desencriptar el username de la BD
                        $usernameBD = $this->encryption->decrypt($fila['username']);
                        
                        // Comparar username desencriptado con el username recibido
                        if ($usernameBD === $username) {
                            throw new RuntimeException('El nombre de usuario ya está en uso');
                        }
                    } catch (\Throwable $e) {
                        // Si falla el desencriptado, comparar directamente
                        if ($fila['username'] === $username) {
                            throw new RuntimeException('El nombre de usuario ya está en uso');
                        }
                    }
                }
                
                // 2. Insertar usuario en tbl_usuarios (base S) - incluyendo cédula como en Login.php
                error_log("Registrando usuario en tbl_usuarios");
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $id_rol_cliente = 3;
                
                // Cifrar campos sensibles según CAMPOS_CIFRADOS
                $nombres_cifrado = $this->encryption->encrypt($nombres);
                $apellidos_cifrado = $this->encryption->encrypt($apellidos);
                $correo_cifrado = $this->encryption->encrypt($correo);
                $telefono_cifrado = $this->encryption->encrypt($telefono);
                
                $sqlU = "INSERT INTO tbl_usuarios 
                        (username, password, cedula, nombres, apellidos, correo, telefono, id_rol, estatus)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'habilitado')";
                
                $pdoS->prepare($sqlU)->execute([
                    $username,
                    $password_hash,
                    $cedula,
                    $nombres_cifrado,
                    $apellidos_cifrado,
                    $correo_cifrado,
                    $telefono_cifrado,
                    $id_rol_cliente
                ]);
                
                $id_usuario = $pdoS->lastInsertId();
                error_log("Usuario registrado con ID: $id_usuario");
                
                // 3. Insertar cliente en tbl_clientes (base P) - INSERT directo sin cifrar ni stored procedure
                error_log("Registrando cliente en tbl_clientes");
                $nombre_completo = trim($nombres . ' ' . $apellidos);
                
                $registroP = $this->insertarClienteEnP([
                    'nombre' => $nombre_completo,
                    'cedula' => $cedula,
                    'telefono' => $telefono,
                    'direccion' => $direccion ?? '',
                    'correo' => $correo
                ]);
                
                if ($registroP['status'] === 'error') {
                    throw new RuntimeException($registroP['mensaje']);
                }
                
                error_log("Cliente registrado exitosamente");
                
                return [
                    'status' => 'success',
                    'message' => 'Cliente registrado correctamente',
                    'usuario' => [
                        'id_usuario' => $id_usuario,
                        'username' => $username,
                        'nombres' => $nombres,
                        'apellidos' => $apellidos,
                        'correo' => $correo
                    ],
                    'cliente' => [
                        'cedula' => $cedula
                    ]
                ];
                
            } catch (Exception $e) {
                error_log("Error en registro de cliente: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                throw new RuntimeException('Error al registrar el cliente: ' . $e->getMessage());
            }
        }, 'S');
    }
    
    private function insertarClienteEnP($datos) {
        return $this->ejecutarConConexionSegura(function($pdoP) use ($datos) {
            error_log("Insertando cliente en base P");
            $sqlC = "INSERT INTO tbl_clientes
                    (nombre, cedula, telefono, direccion, correo, activo)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $pdoP->prepare($sqlC)->execute([
                $datos['nombre'],
                $datos['cedula'],
                $datos['telefono'],
                $datos['direccion'],
                $datos['correo'],
                1
            ]);
            error_log("Cliente insertado en base P exitosamente");
            return ['status' => 'success'];
        }, false, 'P');
    }

    // Métodos para la API de perfil
    private function obtenerPasswordHash($id_usuario) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_usuario) {
            $query = "SELECT password FROM tbl_usuarios WHERE id_usuario = :id_usuario";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':id_usuario' => $id_usuario]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result ? $result['password'] : null;
        }, false);
    }

    public function obtenerPerfil($data) {
        $id_usuario = $data['id_usuario'] ?? null;

        if (empty($id_usuario)) {
            throw new RuntimeException('El ID de usuario es obligatorio');
        }

        $usuario = $this->obtenerUsuarioPorId($id_usuario);

        if (!$usuario) {
            throw new RuntimeException('Usuario no encontrado');
        }

        // Remover campos sensibles (puede venir como password o clave)
        unset($usuario['password']);
        unset($usuario['clave']);
        unset($usuario['id_rol']);
        unset($usuario['nombre_rol']);
        unset($usuario['estatus']);
        unset($usuario['id_usuario']);

        // Encriptar datos sensibles del usuario
        $encryption = new \Usuario\ProyectoCasalaiCa\Config\Encryption();

        $camposEncriptar = ['username','cedula', 'correo', 'nombres', 'apellidos', 'telefono'];
        foreach ($camposEncriptar as $campo) {
            if (isset($usuario[$campo]) && $usuario[$campo] !== null) {
                try {
                    $usuario[$campo] = $encryption->encrypt($usuario[$campo]);
                } catch (\Throwable $e) {
                    error_log("[USUARIOS] Error encriptando campo '$campo': " . $e->getMessage());
                    // Mantener el valor original si falla la encriptación
                }
            }
        }

        return [
            'status' => 'success',
            'message' => 'Perfil obtenido correctamente',
            'usuario' => $usuario
        ];
    }

    /**
     * Obtiene la URL base configurada para la API
     * @return string URL base
     */
    private function getBaseUrl() {
        // Intentar incluir la configuración centralizada
        $configFile = __DIR__ . '/../api_config.php';
        if (file_exists($configFile)) {
            require_once $configFile;
            if (function_exists('getBaseUrl')) {
                return getBaseUrl();
            }
        }
        // Valor por defecto para desarrollo
        return 'http://localhost/proyecto-casalai-ca';
    }

    public function cambiarPersonal($data) {
        $id_usuario = $data['id_usuario'] ?? null;
        $encryption = new Encryption();
        $apellidos = $encryption->decrypt($data['apellidos'] ?? null);
        $nombres = $encryption->decrypt($data['nombres'] ?? null);
        $telefono = $encryption->decrypt($data['telefono'] ?? null);
        $clave_actual = $encryption->decrypt($data['clave_actual'] ?? null);

        if (empty($id_usuario)) {
            throw new RuntimeException('El ID de usuario es obligatorio');
        }
        
        // Validar contraseña actual
        if (empty($clave_actual)) {
            throw new RuntimeException('La contraseña actual es requerida');
        }

        // Obtener hash de contraseña directamente sin descifrar
        $password_hash = $this->obtenerPasswordHash($id_usuario);
        
        if (!$password_hash) {
            throw new RuntimeException('Usuario no encontrado');
        }

        if (!password_verify($clave_actual, $password_hash)) {
            throw new RuntimeException('La contraseña actual es incorrecta');
        }

        // Preparar datos para actualizar
        $datosActualizar = [];
        $camposEditables = ['username', 'nombres', 'apellidos', 'telefono'];

        foreach ($camposEditables as $campo) {
            if (isset($data[$campo]) && !empty($data[$campo])) {
                $datosActualizar[$campo] = trim($data[$campo]);
            }
        }

        // Validar username único

        if (empty($datosActualizar)) {
            throw new RuntimeException('No se proporcionaron datos para actualizar');
        }

        if ($this->actualizarPerfil($id_usuario, $datosActualizar)) {
            return [
                'status' => 'success',
                'message' => 'Información personal actualizada correctamente'
            ];
        }

        throw new RuntimeException('Error al actualizar la información personal');
    }

    public function cambiarPassword($data) {
        $id_usuario = $data['id_usuario'] ?? null;
        $clave_actual = $data['clave_actual'] ?? null;
        $clave_nueva = $data['clave_nueva'] ?? null;
        $clave_confirmar = $data['clave_confirmar'] ?? null;
        $encryption = new Encryption;

        $clave_actual = $encryption->decrypt($clave_actual);
        $clave_nueva = $encryption->decrypt($clave_nueva);
        $clave_confirmar = $encryption->decrypt($clave_confirmar);
        
        if (empty($id_usuario)) {
            throw new RuntimeException('El ID de usuario es obligatorio');
        }
        
        if (empty($clave_actual) || empty($clave_nueva) || empty($clave_confirmar)) {
            throw new RuntimeException('Todos los campos de contraseña son obligatorios');
        }

        if ($clave_nueva !== $clave_confirmar) {
            throw new RuntimeException('Las contraseñas nuevas no coinciden');
        }

        if (strlen($clave_nueva) < 6) {
            throw new RuntimeException('La nueva contraseña debe tener al menos 6 caracteres');
        }

        // Obtener hash de contraseña directamente sin descifrar
        $password_hash = $this->obtenerPasswordHash($id_usuario);
        
        if (!$password_hash) {
            throw new RuntimeException('Usuario no encontrado');
        }

        if (!password_verify($clave_actual, $password_hash)) {
            throw new RuntimeException('La contraseña actual es incorrecta');
        }

        if ($this->actualizarPerfil($id_usuario, ['password' => $clave_nueva])) {
            return [
                'status' => 'success',
                'message' => 'Contraseña actualizada correctamente'
            ];
        }

        throw new RuntimeException('Error al actualizar la contraseña');
    }

    public function cambiarCorreo($data) {
        $id_usuario = $data['id_usuario'] ?? null;
        $clave_actual = $data['clave_actual'] ?? null;
        $correo_nuevo = $data['correo_nuevo'] ?? $data['correo'] ?? $data['email'] ?? $data['email_nuevo'] ?? $data['nuevo_correo'] ?? null;
        $encryption = new Encryption();
        $correo_nuevo = $encryption->decrypt($correo_nuevo);
        $clave_actual = $encryption->decrypt($clave_actual);
        

        if (empty($id_usuario)) {
            throw new RuntimeException('El ID de usuario es obligatorio');
        }
        
        if (empty($clave_actual)) {
            throw new RuntimeException('La contraseña actual es requerida');
        }

        if (empty($correo_nuevo)) {
            throw new RuntimeException('El correo nuevo es obligatorio');
        }

        // Validar formato de email
        if (!filter_var($correo_nuevo, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El formato del correo electrónico no es válido');
        }

        // Obtener hash de contraseña para verificar
        $password_hash = $this->obtenerPasswordHash($id_usuario);
        
        if (!$password_hash) {
            throw new RuntimeException('Usuario no encontrado');
        }

        if (!password_verify($clave_actual, $password_hash)) {
            throw new RuntimeException('La contraseña actual es incorrecta');
        }

        // Verificar que el correo nuevo no esté en uso por otro usuario
        if ($this->existeCorreo($correo_nuevo, $id_usuario)) {
            throw new RuntimeException('El correo electrónico ya está en uso por otro usuario');
        }

        if ($this->actualizarPerfil($id_usuario, ['correo' => $correo_nuevo])) {
            return [
                'status' => 'success',
                'message' => 'Correo actualizado correctamente'
            ];
        }

        throw new RuntimeException('Error al actualizar el correo');
    }

    public function cambiarAvatar($data) {
        $id_usuario = $data['id_usuario'] ?? null;
        $clave_actual = $data['clave_actual'] ?? null;
        $encryption = new Encryption;
        $clave_actual = $encryption->decrypt($clave_actual);
        
        if (empty($id_usuario)) {
            throw new RuntimeException('El ID de usuario es obligatorio');
        }
        
        if (empty($clave_actual)) {
            throw new RuntimeException('La contraseña actual es requerida');
        }

        // Obtener hash de contraseña directamente sin descifrar
        $password_hash = $this->obtenerPasswordHash($id_usuario);
        
        if (!$password_hash) {
            throw new RuntimeException('Usuario no encontrado');
        }

        if (!password_verify($clave_actual, $password_hash)) {
            throw new RuntimeException('La contraseña actual es incorrecta');
        }

        // Asegurar que el directorio de subidas exista
        $uploadDir = __DIR__ . '/../../assets/img/uploads/';
        
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new RuntimeException('No se pudo crear el directorio de carga');
            }
        }
        
        if (!is_writable($uploadDir)) {
            throw new RuntimeException('El directorio de carga no tiene permisos de escritura');
        }

        $nombreArchivo = null;
        $rutaDestino = null;

        // Manejo de la imagen de perfil - soportar ambos formatos
        // 1. Subida tradicional con multipart/form-data
        if (isset($_FILES['foto_perfil'])) {
            $archivo = $_FILES['foto_perfil'];
            if ($archivo['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('La carga de la imagen falló. Código: ' . $archivo['error']);
            }

            if (!is_uploaded_file($archivo['tmp_name'])) {
                throw new RuntimeException('El archivo recibido no es una carga válida');
            }

            if ($archivo['size'] < self::MIN_FOTO_PERFIL || $archivo['size'] > self::MAX_FOTO_PERFIL) {
                throw new RuntimeException('La imagen debe pesar entre 100 bytes y 5MB');
            }

            // Se detecta el tipo leyendo el temporal. $_FILES[type] viene del cliente y no
            // garantiza que el contenido recibido sea realmente una imagen.
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($archivo['tmp_name']);
            $tiposPermitidos = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/bmp' => 'bmp',
                'image/x-ms-bmp' => 'bmp',
                'image/avif' => 'avif',
                'image/heic' => 'heic',
                'image/heif' => 'heif',
            ];

            if (!isset($tiposPermitidos[$mimeType])) {
                throw new RuntimeException('El contenido recibido no es un formato de imagen compatible');
            }

            // Las cabeceras permiten rechazar archivos truncados o dañados antes de guardarlos.
            if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/x-ms-bmp'], true)
                && @getimagesize($archivo['tmp_name']) === false) {
                throw new RuntimeException('El archivo de imagen está dañado o incompleto');
            }
            
            // Generar nombre de archivo seguro
            $extension = $tiposPermitidos[$mimeType];
            $nombreArchivo = uniqid('avatar_') . '_' . time() . '.' . $extension;
            $rutaDestino = $uploadDir . $nombreArchivo;
            
            if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                throw new RuntimeException('Error al subir la imagen');
            }
        }
        // 2. Subida como base64 en el cuerpo JSON
        elseif (isset($data['foto_perfil']) && !empty($data['foto_perfil'])) {
            $imageData = $data['foto_perfil'];
            $extension = 'png';
            
            // Log detallado de los datos recibidos
            error_log("Tipo de datos recibidos: " . gettype($imageData));
            
            // Si es un objeto/array, intentar extraer la imagen de propiedades comunes
            if (is_array($imageData) || is_object($imageData)) {
                error_log("foto_perfil es un objeto/array");
                $imageObj = (array)$imageData;
                error_log("Propiedades del objeto: " . implode(', ', array_keys($imageObj)));
                
                // Buscar propiedades comunes donde podría estar la imagen
                $possibleKeys = ['base64', 'data', 'uri', 'url', 'path', 'content', 'imageData'];
                foreach ($possibleKeys as $key) {
                    if (isset($imageObj[$key]) && !empty($imageObj[$key])) {
                        $imageData = $imageObj[$key];
                        error_log("Imagen encontrada en propiedad: $key");
                        error_log("Tipo de dato en propiedad: " . gettype($imageData));
                        break;
                    }
                }
                
                // Si no se encontró en propiedades específicas, intentar con el primer valor string
                if (is_array($imageData) || is_object($imageData)) {
                    foreach ($imageObj as $value) {
                        if (is_string($value) && strlen($value) > 100) {
                            $imageData = $value;
                            error_log("Usando primer valor string largo encontrado");
                            break;
                        }
                    }
                }
            }
            
            // Si después de procesar el objeto sigue siendo un objeto, error
            if (is_array($imageData) || is_object($imageData)) {
                error_log("Error: foto_perfil sigue siendo un objeto después de procesar");
                error_log("Contenido del objeto: " . json_encode($imageData));
                throw new RuntimeException('El formato de la imagen no es válido: se recibió un objeto pero no se pudo extraer la imagen');
            }
            
            error_log("Longitud: " . strlen($imageData));
            error_log("Primeros 200 caracteres (hex): " . bin2hex(substr($imageData, 0, 200)));
            error_log("Primeros 100 caracteres (raw): " . substr($imageData, 0, 100));
            
            // Verificar si los datos ya son binarios (no es un string base64)
            if (is_string($imageData)) {
                // Intentar detectar si es base64 válido
                $isBase64 = preg_match('/^[a-zA-Z0-9\/\+=]+$/', $imageData) && (strlen($imageData) % 4 === 0);
                error_log("¿Es base64 válido? " . ($isBase64 ? 'Sí' : 'No'));
                
                if ($isBase64) {
                    // Procesar como base64
                    error_log("Procesando como base64");
                    
                    // Decodificar URL encoding si está presente
                    $imageData = urldecode($imageData);
                    
                    // Eliminar espacios y saltos de línea
                    $imageData = preg_replace('/\s+/', '', $imageData);
                    
                    // Verificar si es un data URI
                    if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $imageData, $matches)) {
                        $extension = $matches[1];
                        $imageData = $matches[2];
                        error_log("Data URI detectado, extensión: $extension");
                    }
                    
                    // Eliminar caracteres no base64
                    $imageData = preg_replace('/[^a-zA-Z0-9\/\+=]/', '', $imageData);
                    
                    // Decodificar
                    $imageData = base64_decode($imageData);
                    if ($imageData === false) {
                        throw new RuntimeException('Error al decodificar la imagen base64');
                    }
                } else {
                    // Si no es base64, podría ser datos binarios ya decodificados
                    error_log("No es base64, intentando como datos binarios");
                    
                    // Verificar si es una imagen válida directamente
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->buffer($imageData);
                    error_log("MIME type de datos binarios: " . $mimeType);
                    
                    if (strpos($mimeType, 'image/') === 0) {
                        // Ya es una imagen válida, usar directamente
                        error_log("Datos ya son una imagen válida: " . $mimeType);
                        
                        // Detectar extensión desde MIME type
                        $mimeToExt = [
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/gif' => 'gif',
                            'image/webp' => 'webp'
                        ];
                        $extension = $mimeToExt[$mimeType] ?? 'png';
                    } else {
                        // Guardar los datos recibidos para inspección
                        $tempFile = $uploadDir . 'debug_received_' . time() . '.txt';
                        file_put_contents($tempFile, $imageData);
                        error_log("Datos recibidos guardados en: " . $tempFile);
                        
                        throw new RuntimeException('Los datos no son base64 válido ni una imagen binaria válida. MIME type: ' . $mimeType . '. Datos guardados en: ' . $tempFile);
                    }
                }
            } else {
                throw new RuntimeException('El formato de la imagen no es válido');
            }
            
            // Validar que los datos sean una imagen válida
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData);
            error_log("MIME type final: " . $mimeType);
            
            if (strpos($mimeType, 'image/') !== 0) {
                throw new RuntimeException('Los datos no corresponden a una imagen válida. MIME type: ' . $mimeType);
            }
            
            // Validar tamaño
            if (strlen($imageData) > 5 * 1024 * 1024) { // 5MB
                throw new RuntimeException('La imagen no puede superar 5MB');
            }
            
            // Generar nombre de archivo seguro
            $nombreArchivo = uniqid('avatar_') . '_' . time() . '.' . $extension;
            $rutaDestino = $uploadDir . $nombreArchivo;
            
            if (!file_put_contents($rutaDestino, $imageData)) {
                throw new RuntimeException('Error al guardar la imagen');
            }
        } else {
            throw new RuntimeException('No se proporcionó una imagen válida (debe ser archivo o base64)');
        }
        
        // Eliminar foto anterior si existe
        $usuario_actual = $this->obtenerUsuarioPorId($id_usuario);
        if (!empty($usuario_actual['foto_perfil']) && file_exists($uploadDir . $usuario_actual['foto_perfil'])) {
            unlink($uploadDir . $usuario_actual['foto_perfil']);
        }
        
        if ($this->actualizarPerfil($id_usuario, ['foto_perfil' => $nombreArchivo])) {
            return [
                'status' => 'success',
                'message' => 'Foto de perfil actualizada correctamente',
                'foto_perfil' => $nombreArchivo
            ];
        }

        throw new RuntimeException('Error al actualizar la foto de perfil');
    }
}