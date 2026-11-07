<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;

function getNotificacionesUsuario($id_usuario) {
    try {
        $notificacion = new NotificacionModel();
        
        // Validar datos antes de consultar
        $datosValidacion = [
            'id_usuario' => $id_usuario
        ];
        
        $errores = $notificacion->validarConsultarNotificaciones($datosValidacion);
        if (!empty($errores)) {
            error_log('Errores de validación en getNotificacionesUsuario: ' . json_encode($errores));
            return [];
        }
        
        return $notificacion->obtenerNotificacionesUsuario($id_usuario);
    } catch (Exception $e) {
        error_log('Error en getNotificacionesUsuario: ' . $e->getMessage());
        return [];
    }
}

// Función para marcar una notificación como leída
function marcarNotificacionLeida($id_notificacion, $id_usuario) {
    try {
        $notificacion = new NotificacionModel();
        
        // Validar datos antes de marcar como leída
        $datosValidacion = [
            'id_notificacion' => $id_notificacion,
            'id_usuario' => $id_usuario
        ];
        
        $errores = $notificacion->validarMarcarLeida($datosValidacion);
        if (!empty($errores)) {
            error_log('Errores de validación en marcarNotificacionLeida: ' . json_encode($errores));
            return false;
        }
        
        return $notificacion->marcarComoLeida($id_notificacion, $id_usuario);
    } catch (Exception $e) {
        error_log('Error en marcarNotificacionLeida: ' . $e->getMessage());
        return false;
    }
}

// Función para marcar todas las notificaciones como leídas
function marcarTodasLeidas($id_usuario) {
    try {
        $notificacion = new NotificacionModel();
        
        // Validar datos antes de marcar todas como leídas
        $datosValidacion = [
            'id_usuario' => $id_usuario
        ];
        
        $errores = $notificacion->validarMarcarTodasLeidas($datosValidacion);
        if (!empty($errores)) {
            error_log('Errores de validación en marcarTodasLeidas: ' . json_encode($errores));
            return false;
        }
        
        return $notificacion->marcarTodasComoLeidas($id_usuario);
    } catch (Exception $e) {
        error_log('Error en marcarTodasLeidas: ' . $e->getMessage());
        return false;
    }
}

// Función para crear notificación con validación
function crearNotificacion($id_usuario, $tipo, $titulo, $mensaje, $prioridad, $id_modulo = null, $accion = null, $id_referencia = null) {
    try {
        $notificacion = new NotificacionModel();
        
        // Validar datos antes de crear
        $datosValidacion = [
            'id_usuario' => $id_usuario,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'prioridad' => $prioridad,
            'id_modulo' => $id_modulo,
            'accion' => $accion,
            'id_referencia' => $id_referencia
        ];
        
        $errores = $notificacion->validarNotificacion($datosValidacion);
        if (!empty($errores)) {
            error_log('Errores de validación en crearNotificacion: ' . json_encode($errores));
            return false;
        }
        
        return $notificacion->crear($id_usuario, $tipo, $titulo, $mensaje, $prioridad, $id_modulo, $accion, $id_referencia);
    } catch (Exception $e) {
        error_log('Error en crearNotificacion: ' . $e->getMessage());
        return false;
    }
}

// Función para notificar pago con validación
function notificarPago($id_usuario, $id_pago, $estado) {
    try {
        $notificacion = new NotificacionModel();
        
        // Validar datos antes de notificar pago
        $datosValidacion = [
            'id_usuario' => $id_usuario,
            'tipo' => 'pago',
            'titulo' => 'Estado de pago actualizado',
            'mensaje' => 'Su pago ha sido ' . ($estado == 'procesado' ? "aprobado" : ($estado == 'pendiente' ? "recibido" : "rechazado")),
            'prioridad' => 'alta',
            'id_pago' => $id_pago,
            'estado' => $estado
        ];
        
        $errores = $notificacion->validarNotificacionPago($datosValidacion);
        if (!empty($errores)) {
            error_log('Errores de validación en notificarPago: ' . json_encode($errores));
            return false;
        }
        
        return $notificacion->notificarPago($id_usuario, $id_pago, $estado);
    } catch (Exception $e) {
        error_log('Error en notificarPago: ' . $e->getMessage());
        return false;
    }
}

// Función para notificar despacho con validación
function notificarDespacho($id_usuario, $id_despacho, $estado) {
    try {
        $notificacion = new NotificacionModel();
        
        // Validar datos antes de notificar despacho
        $datosValidacion = [
            'id_usuario' => $id_usuario,
            'tipo' => 'despacho',
            'titulo' => 'Estado de despacho',
            'mensaje' => 'Su pedido ha sido ' . ($estado == 'enviado' ? "despachado" : "preparado para envío"),
            'prioridad' => 'media',
            'id_despacho' => $id_despacho,
            'estado' => $estado
        ];
        
        $errores = $notificacion->validarNotificacionDespacho($datosValidacion);
        if (!empty($errores)) {
            error_log('Errores de validación en notificarDespacho: ' . json_encode($errores));
            return false;
        }
        
        return $notificacion->notificarDespacho($id_usuario, $id_despacho, $estado);
    } catch (Exception $e) {
        error_log('Error en notificarDespacho: ' . $e->getMessage());
        return false;
    }
}

// Manejo de acciones AJAX
if (isset($_GET['accion']) && !empty($_GET['accion'])) {
    if (!isset($_SESSION['id_usuario'])) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['error' => 'Usuario no autenticado']);
        exit;
    }
    
    $accion = $_GET['accion'];
    $respuesta = ['exito' => false, 'mensaje' => 'Acción no válida'];
    
    switch ($accion) {
        case 'listar':
            $notificaciones = getNotificacionesUsuario($_SESSION['id_usuario']);
            // Formatear fechas para mejor legibilidad (tolerante a campos faltantes)
            foreach ($notificaciones as &$notificacion) {
                try {
                    $src = $notificacion['fecha_hora'] ?? $notificacion['fecha_creacion'] ?? null;
                    if ($src) {
                        $fecha = new DateTime($src);
                        $notificacion['fecha_formateada'] = $fecha->format('Y-m-d H:i:s');
                    } else {
                        $notificacion['fecha_formateada'] = null;
                    }
                } catch (Exception $e) {
                    $notificacion['fecha_formateada'] = null;
                }
            }
            $respuesta = ['exito' => true, 'data' => $notificaciones];
            break;
            
        case 'marcar_leida':
            if (isset($_POST['id_notificacion'])) {
                $resultado = marcarNotificacionLeida($_POST['id_notificacion'], $_SESSION['id_usuario']);
                $respuesta = ['exito' => $resultado, 'mensaje' => $resultado ? 'Notificación marcada como leída' : 'Error al actualizar la notificación'];
            } else {
                $respuesta = ['exito' => false, 'mensaje' => 'ID de notificación no proporcionado'];
            }
            break;
            
        case 'marcar_todas_leidas':
            $resultado = marcarTodasLeidas($_SESSION['id_usuario']);
            $respuesta = ['exito' => $resultado, 'mensaje' => $resultado ? 'Todas las notificaciones marcadas como leídas' : 'Error al actualizar las notificaciones'];
            break;
            
        case 'crear':
            // Validar y crear notificación
            if (isset($_POST['id_usuario']) && isset($_POST['tipo']) && isset($_POST['titulo']) && isset($_POST['mensaje']) && isset($_POST['prioridad'])) {
                $id_modulo = $_POST['id_modulo'] ?? null;
                $accion = $_POST['accion'] ?? null;
                $id_referencia = $_POST['id_referencia'] ?? null;
                
                $resultado = crearNotificacion(
                    $_POST['id_usuario'],
                    $_POST['tipo'],
                    $_POST['titulo'],
                    $_POST['mensaje'],
                    $_POST['prioridad'],
                    $id_modulo,
                    $accion,
                    $id_referencia
                );
                $respuesta = ['exito' => $resultado, 'mensaje' => $resultado ? 'Notificación creada exitosamente' : 'Error al crear la notificación'];
            } else {
                $respuesta = ['exito' => false, 'mensaje' => 'Datos incompletos para crear notificación'];
            }
            break;
            
        case 'notificar_pago':
            // Validar y notificar pago
            if (isset($_POST['id_usuario']) && isset($_POST['id_pago']) && isset($_POST['estado'])) {
                $resultado = notificarPago($_POST['id_usuario'], $_POST['id_pago'], $_POST['estado']);
                $respuesta = ['exito' => $resultado, 'mensaje' => $resultado ? 'Notificación de pago enviada' : 'Error al enviar notificación de pago'];
            } else {
                $respuesta = ['exito' => false, 'mensaje' => 'Datos incompletos para notificar pago'];
            }
            break;
            
        case 'notificar_despacho':
            // Validar y notificar despacho
            if (isset($_POST['id_usuario']) && isset($_POST['id_despacho']) && isset($_POST['estado'])) {
                $resultado = notificarDespacho($_POST['id_usuario'], $_POST['id_despacho'], $_POST['estado']);
                $respuesta = ['exito' => $resultado, 'mensaje' => $resultado ? 'Notificación de despacho enviada' : 'Error al enviar notificación de despacho'];
            } else {
                $respuesta = ['exito' => false, 'mensaje' => 'Datos incompletos para notificar despacho'];
            }
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($respuesta);
    exit;
}

// Cargar la vista de notificaciones
$pagina = "notificacion";

// Buscar primero en Vista/VistaNew/ y luego en Vista/
if (is_file("Vista/VistaNew/" . $pagina . ".php")) {
    if (!isset($_SESSION['id_usuario'])) {
        header("Location: ?pagina=login");
        exit;
    }
    
    try {
        // Registrar en bitácora
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            'Notificaciones', // ID del módulo de notificaciones
            'CONSULTAR',
            'El usuario accedió al módulo de Notificaciones',
            'baja'
        );
        
        // Obtener notificaciones para la vista
        $notificaciones = getNotificacionesUsuario($_SESSION['id_usuario']);
        
        // Verificar si hay un error al obtener las notificaciones
        if ($notificaciones === false) {
            throw new Exception('Error al cargar las notificaciones');
        }
        
        // Cargar la vista
        require_once("Vista/VistaNew/" . $pagina . ".php");
        
    } catch (Exception $e) {
        error_log('Error en el controlador de notificaciones: ' . $e->getMessage());
        // Mostrar un mensaje de error amigable
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }
        echo "<div class='alert alert-danger'>Ocurrió un error al cargar las notificaciones. Por favor, intente más tarde.</div>";
    }
} elseif (is_file("Vista/" . $pagina . ".php")) {
    if (!isset($_SESSION['id_usuario'])) {
        header("Location: ?pagina=login");
        exit;
    }
    
    try {
        // Registrar en bitácora
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            'Notificaciones', // ID del módulo de notificaciones
            'CONSULTAR',
            'El usuario accedió al módulo de Notificaciones',
            'baja'
        );
        
        // Obtener notificaciones para la vista
        $notificaciones = getNotificacionesUsuario($_SESSION['id_usuario']);
        
        // Verificar si hay un error al obtener las notificaciones
        if ($notificaciones === false) {
            throw new Exception('Error al cargar las notificaciones');
        }
        
        // Cargar la vista
        require_once("Vista/" . $pagina . ".php");
        
    } catch (Exception $e) {
        error_log('Error en el controlador de notificaciones: ' . $e->getMessage());
        // Mostrar un mensaje de error amigable
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }
        echo "<div class='alert alert-danger'>Ocurrió un error al cargar las notificaciones. Por favor, intente más tarde.</div>";
    }
} else {
    if (!headers_sent()) {
        header('HTTP/1.1 404 Not Found');
    }
    echo "<div class='alert alert-warning'>La página solicitada no está disponible en este momento.</div>";
}
