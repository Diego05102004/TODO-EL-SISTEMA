<?php 
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Config\Encryption;
use PDO;
use PDOException;
class Usuarios extends BD {
    
    private $id_usuario;
    private $username;
    private $clave;
    private $id_rol;
    private $activo = 1;
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
     * @return mixed
     */
    protected function ejecutarConConexionSegura($operation, $usarTransaccion = true) {
        try {
            parent::__construct('S'); 
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
            throw new \RuntimeException("Error en operación de base de datos: " . $e->getMessage());
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
            } /*elseif (mb_strlen($username) < self::MIN_USERNAME || mb_strlen($username) > self::MAX_USERNAME) {
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
            }/* elseif (mb_strlen($username) < self::MIN_USERNAME || mb_strlen($username) > self::MAX_USERNAME) {
                $errores['username'] = 'El nombre de usuario debe tener entre ' . self::MIN_USERNAME . ' y ' . self::MAX_USERNAME . ' caracteres';
            } */ elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
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
            
            $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE username = ?";
            $params = [$username];

            if ($excluir_id !== null) {
                $sql .= " AND id_usuario != ?";
                $params[] = $excluir_id;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;

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
                    // Descifrar datos personales
        $resultado = $this->encryption->decryptResults($usuarios, self::CAMPOS_CIFRADOS);
        error_log("Usuarios obtenidos: " . print_r($resultado, true)); 
            return $resultado;
        }, false);
        
        // Descifrar datos personales
        $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS);
        error_log("Usuarios obtenidos: " . print_r($resultado, true)); // Log de depuración
        return $resultado;
    }
}