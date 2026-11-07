<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Para producción, puedes desactivar los errores:
// error_reporting(0);
// ini_set('display_errors', 0);
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Usuarios;
define('MODULO_PERFIL', 22);

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}

$usuarioModel = new Usuarios();
$usuario = $usuarioModel->obtenerUsuarioPorId($_SESSION['id_usuario']);

// Manejo de actualizaciones específicas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? 'general';
    
    switch($tipo) {
        case 'consultar':
            // Preparar datos para validación del modelo
            $datosValidacion = [
                'id_usuario' => $_SESSION['id_usuario']
            ];

            // Validar usando el método del modelo
            $errores = $usuarioModel->validarConsultarUsuario($datosValidacion);
            
            if (!empty($errores)) {
                return [
                    'status' => 'error', 
                    'message' => 'Errores de validación',
                    'errors' => $errores
                ];
            }

            // Aquí iría la lógica de consulta
            $usuario_consultado = $usuarioModel->obtenerUsuarioPorId($_SESSION['id_usuario']);
            
            return [
                'status' => 'success', 
                'message' => 'Usuario consultado exitosamente',
                'usuario' => $usuario_consultado
            ];
            
        case 'personal':
            $respuesta = handlePersonalUpdate($usuarioModel, new Bitacora(), $usuario);
            break;
        case 'email':
            $respuesta = handleEmailUpdate($usuarioModel, new Bitacora(), $usuario);
            break;
        case 'password':
            $respuesta = handlePasswordUpdate($usuarioModel, new Bitacora(), $usuario);
            break;
        case 'avatar':
            $respuesta = handleAvatarUpdate($usuarioModel, new Bitacora(), $usuario);
            break;
        default:
            $respuesta = ['status' => 'error', 'message' => 'Tipo de acción no válido'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($respuesta);
    exit;
}

// Función para actualización de información personal
function handlePersonalUpdate($usuarioModel, $bitacoraModel, $usuario) {
    // Validar contraseña actual
    if (empty($_POST['clave_actual'])) {
        return ['status' => 'error', 'message' => 'La contraseña actual es requerida'];
    }
    
    if (!password_verify($_POST['clave_actual'], $usuario['password'])) {
        return ['status' => 'error', 'message' => 'La contraseña actual es incorrecta'];
    }

    // Preparar datos para validación del modelo
    $datosValidacion = [
        'id_usuario' => $_SESSION['id_usuario'],
        'username' => $_POST['username'] ?? null,
        'nombre' => $_POST['nombres'] ?? null,
        'apellido' => $_POST['apellidos'] ?? null,
        'correo' => $usuario['correo'], // Usar correo actual para validación
        'telefono' => $_POST['telefono'] ?? null,
        'cedula' => $usuario['cedula'], // Usar cédula actual para validación
        'id_rol' => $usuario['id_rol'] // Usar rol actual para validación
    ];

    // Validar usando el método del modelo
    $errores = $usuarioModel->validarModificarUsuario($datosValidacion);
    
    if (!empty($errores)) {
        return [
            'status' => 'error', 
            'message' => 'Errores de validación',
            'errors' => $errores
        ];
    }

    $datosActualizar = [];
    $camposEditables = ['username', 'nombres', 'apellidos', 'telefono'];

    foreach ($camposEditables as $campo) {
        if (isset($_POST[$campo]) && $_POST[$campo] != $usuario[$campo]) {
            $datosActualizar[$campo] = trim($_POST[$campo]);
        }
    }

    // Validar username único
    if (isset($datosActualizar['username']) && $usuarioModel->existeUsuario($datosActualizar['username'], $usuario['id_usuario'])) {
        return ['status' => 'error', 'message' => 'El nombre de usuario ya está en uso'];
    }

    if (empty($datosActualizar)) {
        return ['status' => 'info', 'message' => 'No se realizaron cambios en la información personal'];
    }

    if ($usuarioModel->actualizarPerfil($_SESSION['id_usuario'], $datosActualizar)) {
        // Actualizar sesión
        foreach ($datosActualizar as $campo => $valor) {
            $_SESSION[$campo] = $valor;
        }
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraLocal = new Bitacora();
            $bitacoraLocal->registrarBitacora(
                $_SESSION['id_usuario'],
                MODULO_PERFIL,
                'MODIFICAR',
                'Actualizó su información personal',
                'media'
            );
        }
        return ['status' => 'success', 'message' => 'Información personal actualizada correctamente'];
    }

    return ['status' => 'error', 'message' => 'Error al actualizar la información personal'];
}

// Función para cambio de foto de perfil
function handleAvatarUpdate($usuarioModel, $bitacoraModel, $usuario) {
    // Validar contraseña actual
    if (empty($_POST['clave_actual'])) {
        return ['status' => 'error', 'message' => 'La contraseña actual es requerida'];
    }
    
    if (!password_verify($_POST['clave_actual'], $usuario['password'])) {
        return ['status' => 'error', 'message' => 'La contraseña actual es incorrecta'];
    }

    // Preparar datos para validación del modelo
    $datosValidacion = [
        'foto_perfil' => $_FILES['foto_perfil'] ?? null
    ];

    // Validar usando el nuevo método del modelo
    $errores = $usuarioModel->validarActualizarAvatar($datosValidacion);
    
    if (!empty($errores)) {
        return [
            'status' => 'error', 
            'message' => 'Errores de validación',
            'errors' => $errores
        ];
    }

    // Manejo de la imagen de perfil
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        // Asegurar que el directorio de subidas exista
        $uploadDir = __DIR__ . '/../../assets/img/uploads/';
        
        // Crear directorio si no existe
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                return ['status' => 'error', 'message' => 'No se pudo crear el directorio de carga'];
            }
        }
        
        // Validar que el directorio sea escribible
        if (!is_writable($uploadDir)) {
            return ['status' => 'error', 'message' => 'El directorio de carga no tiene permisos de escritura'];
        }
        
        // Generar nombre de archivo seguro
        $nombreArchivo = uniqid('avatar_') . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($_FILES['foto_perfil']['name']));
        $rutaDestino = $uploadDir . $nombreArchivo;
        
        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $rutaDestino)) {
            // Eliminar foto anterior si existe
            if (!empty($usuario['foto_perfil']) && file_exists($uploadDir . $usuario['foto_perfil'])) {
                unlink($uploadDir . $usuario['foto_perfil']);
            }
            
            if ($usuarioModel->actualizarPerfil($_SESSION['id_usuario'], ['foto_perfil' => $nombreArchivo])) {
                $_SESSION['foto_perfil'] = $nombreArchivo;
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraLocal = new Bitacora();
                    $bitacoraLocal->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_PERFIL,
                        'MODIFICAR',
                        'Cambió su foto de perfil',
                        'media'
                    );
                }
                return ['status' => 'success', 'message' => 'Foto de perfil actualizada correctamente'];
            }
        }
        
        return ['status' => 'error', 'message' => 'Error al guardar la imagen de perfil'];
    }

    return ['status' => 'error', 'message' => 'No se seleccionó ninguna imagen'];
}

// Función para cambio de correo
function handleEmailUpdate($usuarioModel, $bitacoraModel, $usuario) {
    $nuevo_correo = trim($_POST['new_email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Preparar datos para validación del modelo
    $datosValidacion = [
        'id_usuario' => $_SESSION['id_usuario'],
        'username' => $usuario['username'], // Usar username actual para validación
        'nombre' => $usuario['nombres'], // Usar nombre actual para validación
        'apellido' => $usuario['apellidos'], // Usar apellido actual para validación
        'correo' => $nuevo_correo,
        'telefono' => $usuario['telefono'], // Usar teléfono actual para validación
        'cedula' => $usuario['cedula'], // Usar cédula actual para validación
        'id_rol' => $usuario['id_rol'] // Usar rol actual para validación
    ];

    // Validar usando el método del modelo
    $errores = $usuarioModel->validarModificarUsuario($datosValidacion);
    
    if (!empty($errores)) {
        return [
            'status' => 'error', 
            'message' => 'Errores de validación',
            'errors' => $errores
        ];
    }

    // Validaciones adicionales específicas para correo
    if (empty($nuevo_correo) || empty($password)) {
        return ['status' => 'error', 'message' => 'Todos los campos son obligatorios'];
    }

    if (!filter_var($nuevo_correo, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'error', 'message' => 'El formato del correo no es válido'];
    }

    // Verificar contraseña actual
    if (!password_verify($password, $usuario['password'])) {
        return ['status' => 'error', 'message' => 'La contraseña actual es incorrecta'];
    }

    // Verificar si el correo ya existe
    if ($usuarioModel->existeCorreo($nuevo_correo, $usuario['id_usuario'])) {
        return ['status' => 'error', 'message' => 'El correo electrónico ya está en uso'];
    }

    // Actualizar correo
    if ($usuarioModel->actualizarPerfil($_SESSION['id_usuario'], ['correo' => $nuevo_correo])) {
        // Actualizar sesión
        $_SESSION['correo'] = $nuevo_correo;
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraLocal = new Bitacora();
            $bitacoraLocal->registrarBitacora(
                $_SESSION['id_usuario'],
                MODULO_PERFIL,
                'MODIFICAR',
                'Cambió su correo electrónico',
                'media'
            );
        }
        return ['status' => 'success', 'message' => 'Correo electrónico actualizado correctamente'];
    }

    return ['status' => 'error', 'message' => 'Error al actualizar el correo electrónico'];
}

// Función para cambio de contraseña
function handlePasswordUpdate($usuarioModel, $bitacoraModel, $usuario) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Preparar datos para validación del modelo
    $datosValidacion = [
        'id_usuario' => $_SESSION['id_usuario'],
        'username' => $usuario['username'], // Usar username actual para validación
        'nombre' => $usuario['nombres'], // Usar nombre actual para validación
        'apellido' => $usuario['apellidos'], // Usar apellido actual para validación
        'correo' => $usuario['correo'], // Usar correo actual para validación
        'telefono' => $usuario['telefono'], // Usar teléfono actual para validación
        'cedula' => $usuario['cedula'], // Usar cédula actual para validación
        'id_rol' => $usuario['id_rol'], // Usar rol actual para validación
        'clave' => $new_password // Incluir nueva contraseña para validación
    ];

    // Validar usando el método del modelo
    $errores = $usuarioModel->validarModificarUsuario($datosValidacion);
    
    if (!empty($errores)) {
        return [
            'status' => 'error', 
            'message' => 'Errores de validación',
            'errors' => $errores
        ];
    }

    // Validaciones adicionales específicas para contraseña
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        return ['status' => 'error', 'message' => 'Todos los campos son obligatorios'];
    }

    // Verificar contraseña actual
    if (!password_verify($current_password, $usuario['password'])) {
        return ['status' => 'error', 'message' => 'La contraseña actual es incorrecta'];
    }

    // Validar que las contraseñas coincidan
    if ($new_password !== $confirm_password) {
        return ['status' => 'error', 'message' => 'Las contraseñas nuevas no coinciden'];
    }

    // Validar fortaleza de la nueva contraseña
    if (strlen($new_password) < 6) {
        return ['status' => 'error', 'message' => 'La nueva contraseña debe tener al menos 6 caracteres'];
    }

    // Verificar que no sea igual a la actual
    if (password_verify($new_password, $usuario['password'])) {
        return ['status' => 'error', 'message' => 'La nueva contraseña no puede ser igual a la actual'];
    }

    // Actualizar contraseña (el modelo realizará el hash)
    if ($usuarioModel->actualizarPerfil($_SESSION['id_usuario'], ['password' => $new_password])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraLocal = new Bitacora();
            $bitacoraLocal->registrarBitacora(
                $_SESSION['id_usuario'],
                MODULO_PERFIL,
                'MODIFICAR',
                'Cambió su contraseña',
                'media'
            );
        }
        return ['status' => 'success', 'message' => 'Contraseña actualizada correctamente'];
    }

    return ['status' => 'error', 'message' => 'Error al actualizar la contraseña'];
}

// Cargar vista
$pagina = "perfil";

// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS')) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_PERFIL,
            'ACCESAR',
            'El usuario accedió al módulo de perfil',
            'baja'
        );
    }
    require_once("Vista/VistaNew/" . $pagina . ".php");
} elseif (is_file("Vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS')) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_PERFIL,
            'ACCESAR',
            'El usuario accedió al módulo de perfil',
            'baja'
        );
    }
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}