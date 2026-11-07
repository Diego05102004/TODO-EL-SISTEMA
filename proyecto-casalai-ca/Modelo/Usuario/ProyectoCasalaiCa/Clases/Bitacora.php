<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;

use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Config\Encryption;
use PDO;
use PDOException;
use RuntimeException;
class Bitacora extends BD {
    // Constantes para validaciones
    const MAX_DESCRIPCION = 1000;
    const MAX_LIMITE_REGISTROS = 1000;
    const PRIORIDADES_PERMITIDAS = ['alta', 'media', 'baja'];
    const ACCIONES_PERMITIDAS = ['ACCESAR', 'CREAR', 'MODIFICAR', 'ELIMINAR', 'RESTAURAR', 'DESCARGAR', 'GENERAR', 'CONSULTAR', 'CAMBIAR_ESTADO', 'EXPORTAR', 'IMPORTAR'];
    const MODULOS_PERMITIDOS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20]; // IDs de módulos válidos
    const NOMBRES_PERMITIDOS = ["Usuario", "Recepcion", "Despacho", "Marcas", "Modelos", "Productos", "Categorias", "Proveedores", "Clientes", "Catalogo", "Carrito", "Pasarela", "Pedidos", "Ordenes de despacho", "Cuentas bancarias", "Finanzas", "Permisos", "Roles", "Bitacora", "Respaldo", "Compra Fisica", "Perfil de Usuario", "Notificaciones"];
    
    // Campos cifrados de usuarios (para descifrar en bitácora)
    const CAMPOS_CIFRADOS_USUARIOS = ['nombres', 'apellidos', 'correo', 'telefono'];
    
    private $encryption;
    
    public function __construct() {
        parent::__construct();
        $this->encryption = new Encryption();
    }

    // Registrar acción en la bitácora
public function registrarBitacora($id_usuario, $modulo, $accion, $descripcion,$prioridad, $datos_anteriores = null, $datos_nuevos = null)
{
    return $this->r_registrarBitacora($id_usuario, $modulo, $accion, $descripcion, $prioridad, $datos_anteriores, $datos_nuevos);
}
private function r_registrarBitacora($id_usuario, $modulo, $accion, $descripcion,$prioridad, $datos_anteriores = null, $datos_nuevos = null)
{
    if (defined('SKIP_SIDE_EFFECTS') && SKIP_SIDE_EFFECTS) {
        return true;
    }
    $conexion = new BD('S');
    $co = $conexion->getConexion();
    try {
        $sql = "
            INSERT INTO tbl_bitacora 
            (id_usuario, nombre_modulo, accion, descripcion, datos_viejos, datos_nuevos, fecha_hora, prioridad)
            VALUES 
            (:id_usuario, :modulo, :accion, :descripcion, :datos_anteriores, :datos_nuevos, NOW(), :prioridad)
        ";
        $stmt = $co->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':modulo', $modulo, PDO::PARAM_STR);
        $stmt->bindParam(':accion', $accion, PDO::PARAM_STR);
        $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
        $datos_anteriores_json = $datos_anteriores ? json_encode($datos_anteriores) : null;
        $datos_nuevos_json = $datos_nuevos ? json_encode($datos_nuevos) : null;
        $stmt->bindParam(':datos_anteriores', $datos_anteriores_json, PDO::PARAM_STR);
        $stmt->bindParam(':datos_nuevos', $datos_nuevos_json, PDO::PARAM_STR);
        $stmt->bindParam(':prioridad', $prioridad, PDO::PARAM_STR);
        return $stmt->execute();
    } finally {
        if (isset($conexion)) { 
            $conexion->cerrar(); 
        }
        $co = null;
    }
}


    // Obtener registros detallados de la bitácora (con usuario y módulo)
    public function obtenerRegistrosDetallados($limit = 100) {
        return $this->o_registrosDetallados($limit);
    }
    private function o_registrosDetallados($limit = 100) {
        $conexion = new BD('S');
        $co = $conexion->getConexion();
        try {
            $sql = "SELECT 
                    b.id_bitacora,
                    b.fecha_hora,
                    b.accion,
                    b.nombre_modulo,
                    b.descripcion,
                    b.datos_viejos,
                    b.datos_nuevos,
                    b.prioridad,
                    b.id_usuario,
                    u.username,
                    u.nombres,
                    u.apellidos,
                    m.nombre_modulo AS modulo
                FROM tbl_bitacora b
                INNER JOIN tbl_usuarios u ON b.id_usuario = u.id_usuario
                INNER JOIN tbl_modulos m ON b.nombre_modulo = m.nombre_modulo
                ORDER BY b.fecha_hora DESC
                LIMIT :limite";
            $stmt = $co->prepare($sql);
            $stmt->bindValue(':limite', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Descifrar datos personales de los usuarios
            if (is_array($registros) && !empty($registros)) {
                $registros = $this->encryption->decryptResults($registros, self::CAMPOS_CIFRADOS_USUARIOS);
                
                // Descifrar datos dentro de los campos JSON (datos_viejos y datos_nuevos)
                // y también descifrar la descripción que puede contener datos cifrados
                foreach ($registros as &$registro) {
                    // Descifrar descripción (puede contener datos cifrados en texto)
                    if (isset($registro['descripcion']) && !empty($registro['descripcion'])) {
                        $registro['descripcion'] = $this->descifrarDescripcion($registro['descripcion']);
                    }
                    
                    if (isset($registro['datos_viejos']) && !empty($registro['datos_viejos'])) {
                        $registro['datos_viejos'] = $this->descifrarDatosJson($registro['datos_viejos']);
                    }
                    if (isset($registro['datos_nuevos']) && !empty($registro['datos_nuevos'])) {
                        $registro['datos_nuevos'] = $this->descifrarDatosJson($registro['datos_nuevos']);
                    }
                }
            }
            
            return $registros;
        } catch (PDOException $e) {
            return [];
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar(); 
            }
            $co = null;
        }
    }
    
    /**
     * Descifra datos cifrados dentro de una descripción de texto
     * @param string $descripcion Descripción que puede contener datos cifrados
     * @return string Descripción con datos descifrados
     */
    private function descifrarDescripcion($descripcion) {
        if (empty($descripcion)) {
            return $descripcion;
        }
        
        // Buscar patrones de datos cifrados (base64 largo)
        // Los datos cifrados con AES-256-CBC + Base64 suelen tener longitud > 40
        $resultado = preg_replace_callback('/([A-Za-z0-9+\/=]{40,})/', function($matches) {
            $textoCifrado = $matches[1];
            $descifrado = $this->encryption->decrypt($textoCifrado);
            // Si el descifrado es diferente al original, usar el descifrado
            if ($descifrado !== $textoCifrado) {
                return $descifrado;
            }
            return $textoCifrado;
        }, $descripcion);
        
        return $resultado;
    }
    
    /**
     * Descifra datos dentro de un JSON que puede contener campos cifrados
     * @param string $jsonString JSON con datos cifrados
     * @return string JSON con datos descifrados
     */
    private function descifrarDatosJson($jsonString) {
        if (empty($jsonString)) {
            return $jsonString;
        }
        
        // Decodificar JSON
        $datos = json_decode($jsonString, true);
        
        if (!is_array($datos)) {
            // Si no es JSON válido, intentar descifrar directamente
            return $this->encryption->decrypt($jsonString);
        }
        
        // Campos que pueden estar cifrados (combinación de todos los módulos)
        $camposCifrados = [
            'nombres', 'apellidos', 'correo', 'telefono',
            'nombre_proveedor', 'nombre_representante', 'telefono_1', 'telefono_2', 'direccion_proveedor', 'correo_proveedor',
            'nombre_banco', 'telefono_cuenta', 'correo_cuenta',
            'nombre', 'direccion', 'telefono', 'correo'
        ];
        
        // Función recursiva para descifrar arrays anidados
        $descifrarRecursivo = function($data) use ($camposCifrados, &$descifrarRecursivo) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        $data[$key] = $descifrarRecursivo($value);
                    } elseif (is_string($value) && !empty($value)) {
                        // Si la clave está en la lista de campos cifrados, descifrar
                        if (in_array($key, $camposCifrados)) {
                            $data[$key] = $this->encryption->decrypt($value);
                        }
                        // También intentar descifrar valores que parezcan cifrados (base64 largo)
                        elseif (strlen($value) > 40 && preg_match('/^[A-Za-z0-9+\/=]+$/', $value)) {
                            $descifrado = $this->encryption->decrypt($value);
                            // Si el descifrado es diferente al original, usar el descifrado
                            if ($descifrado !== $value) {
                                $data[$key] = $descifrado;
                            }
                        }
                    }
                }
            }
            return $data;
        };
        
        $datos = $descifrarRecursivo($datos);
        
        // Volver a codificar como JSON
        return json_encode($datos);
    }

    // Estadísticas de accesos semanales al catálogo
    public function obtenerEstadisticasAccesos() {
        return $this->o_estadisticasAccesos();
    }
    private function o_estadisticasAccesos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT 
                        YEAR(fecha_hora) * 100 + WEEK(fecha_hora, 1) AS semana,
                        COUNT(*) AS total_accesos,
                        COUNT(DISTINCT id_usuario) AS usuarios_unicos,
                        ROUND(COUNT(*) / 7, 1) AS promedio_diario
                    FROM tbl_bitacora
                    WHERE nombre_modulo = 'Usuario'
                    GROUP BY semana
                    ORDER BY semana DESC
                    LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $semanas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Totales generales
            $sqlTotal = "SELECT COUNT(*) AS total, COUNT(DISTINCT id_usuario) AS unicos FROM tbl_bitacora WHERE nombre_modulo = 'Usuario'";
            $stmtTotal = $co->prepare($sqlTotal);
            $stmtTotal->execute();
            $totales = $stmtTotal->fetch(PDO::FETCH_ASSOC);

            // Promedio diario global
            $sqlDias = "SELECT DATEDIFF(MAX(fecha_hora), MIN(fecha_hora)) + 1 AS dias FROM tbl_bitacora WHERE nombre_modulo = 'Usuario'";
            $stmtDias = $co->prepare($sqlDias);
            $stmtDias->execute();
            $dias = $stmtDias->fetchColumn();
            $promedio_diario = ($dias > 0 && $totales['total'] > 0) ? round($totales['total'] / $dias, 1) : 0;

            return [
                'total' => $totales['total'],
                'unicos' => $totales['unicos'],
                'promedio_diario' => $promedio_diario,
                'semanas' => $semanas
            ];
        });
    }

    public function obtenerUsuariosMasActivos($limite = 10) {
        return $this->o_usuariosMasActivos($limite);
    }
    private function o_usuariosMasActivos($limite = 10) {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($limite){
            $sql = "SELECT 
                        u.id_usuario,
                        u.username,
                        u.nombres,
                        u.apellidos,
                        COUNT(b.id_bitacora) AS total_accesos,
                        MIN(b.fecha_hora) AS primer_acceso
                    FROM tbl_bitacora b
                    JOIN tbl_usuarios u ON b.id_usuario = u.id_usuario
                    WHERE b.nombre_modulo = 'Usuario'
                    GROUP BY u.id_usuario
                    ORDER BY total_accesos DESC
                    LIMIT :limite";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalAccesos = array_sum(array_column($usuarios, 'total_accesos'));
            foreach ($usuarios as &$usuario) {
                $usuario['porcentaje'] = $totalAccesos > 0 ? round(($usuario['total_accesos'] / $totalAccesos) * 100, 2) : 0;
            }
            return $usuarios;
        });
        
        // Descifrar datos personales de los usuarios
        if (is_array($resultado) && !empty($resultado)) {
            $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS_USUARIOS);
        }
        
        return $resultado;
    }

    // ==================== VALIDACIONES DE BACKEND ====================
    
    /**
     * Valida los datos para registrar en la bitácora
     */
    private function validarRegistrarBitacora($datos) {
        $errores = [];
        
        // Validar ID de usuario
        if (!isset($datos['id_usuario']) || !is_numeric($datos['id_usuario']) || $datos['id_usuario'] <= 0) {
            $errores['id_usuario'] = 'El ID de usuario debe ser un número positivo';
        }
        
        // Validar módulo
        if (!isset($datos['modulo']) || !is_numeric($datos['modulo']) || !in_array((int)$datos['modulo'], self::MODULOS_PERMITIDOS)) {
            $errores['modulo'] = 'El módulo especificado no es válido';
        }
        
        // Validar acción
        if (!isset($datos['accion']) || empty($datos['accion'])) {
            $errores['accion'] = 'La acción es obligatoria';
        } else {
            $accion = strtoupper(trim($datos['accion']));
            if (!in_array($accion, self::ACCIONES_PERMITIDAS)) {
                $errores['accion'] = 'La acción especificada no es válida';
            }
        }
        
        // Validar descripción
        if (!isset($datos['descripcion']) || empty($datos['descripcion'])) {
            $errores['descripcion'] = 'La descripción es obligatoria';
        } else {
            $descripcion = trim($datos['descripcion']);
            if (mb_strlen($descripcion) > self::MAX_DESCRIPCION) {
                $errores['descripcion'] = 'La descripción no debe exceder los ' . self::MAX_DESCRIPCION . ' caracteres';
            }
            
            // Validar caracteres peligrosos
            if (preg_match('/[<>"\|\&\$\*\?]/', $descripcion)) {
                $errores['descripcion'] = 'La descripción contiene caracteres no permitidos';
            }
        }
        
        // Validar prioridad
        if (!isset($datos['prioridad']) || empty($datos['prioridad'])) {
            $errores['prioridad'] = 'La prioridad es obligatoria';
        } else {
            $prioridad = strtolower(trim($datos['prioridad']));
            if (!in_array($prioridad, self::PRIORIDADES_PERMITIDAS)) {
                $errores['prioridad'] = 'La prioridad debe ser: alta, media o baja';
            }
        }
        
        // Validar datos anteriores y nuevos (opcional)
        if (isset($datos['datos_anteriores']) && $datos['datos_anteriores'] !== null) {
            if (!is_array($datos['datos_anteriores'])) {
                $errores['datos_anteriores'] = 'Los datos anteriores deben ser un array';
            }
        }
        
        if (isset($datos['datos_nuevos']) && $datos['datos_nuevos'] !== null) {
            if (!is_array($datos['datos_nuevos'])) {
                $errores['datos_nuevos'] = 'Los datos nuevos deben ser un array';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para consultar registros de la bitácora
     */
    private function validarConsultarBitacora($datos) {
        $errores = [];
        
        // Validar límite de registros
        if (isset($datos['limite'])) {
            if (!is_numeric($datos['limite']) || $datos['limite'] <= 0) {
                $errores['limite'] = 'El límite debe ser un número positivo';
            } elseif ($datos['limite'] > self::MAX_LIMITE_REGISTROS) {
                $errores['limite'] = 'El límite no debe exceder los ' . self::MAX_LIMITE_REGISTROS . ' registros';
            }
        }
        
        // Validar filtros de fecha
        if (isset($datos['fecha_inicio'])) {
            $fechaInicio = $datos['fecha_inicio'];
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
                $errores['fecha_inicio'] = 'La fecha de inicio debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fechaInicio);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_inicio'] = 'La fecha de inicio no es válida';
                }
            }
        }
        
        if (isset($datos['fecha_fin'])) {
            $fechaFin = $datos['fecha_fin'];
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
                $errores['fecha_fin'] = 'La fecha de fin debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fechaFin);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_fin'] = 'La fecha de fin no es válida';
                }
            }
        }
        
        // Validar que la fecha de fin no sea anterior a la de inicio
        if (isset($datos['fecha_inicio']) && isset($datos['fecha_fin']) && !isset($errores['fecha_inicio']) && !isset($errores['fecha_fin'])) {
            $fechaInicio = new \DateTime($datos['fecha_inicio']);
            $fechaFin = new \DateTime($datos['fecha_fin']);
            if ($fechaFin < $fechaInicio) {
                $errores['fecha_fin'] = 'La fecha de fin no puede ser anterior a la fecha de inicio';
            }
        }
        
        // Validar filtros de usuario
        if (isset($datos['id_usuario'])) {
            if (!is_numeric($datos['id_usuario']) || $datos['id_usuario'] <= 0) {
                $errores['id_usuario'] = 'El ID de usuario debe ser un número positivo';
            }
        }
        
        // Validar filtros de módulo
        if (isset($datos['nombre_modulo'])) {
            $accion = strtoupper(trim($datos['nombre_modulo']));
            if (!in_array($accion, self::NOMBRES_PERMITIDOS)) {
                $errores['nombre_modulo'] = 'El nombre del modulo no es valido';
            }
        }
        
        // Validar filtros de acción
        if (isset($datos['accion'])) {
            $accion = strtoupper(trim($datos['accion']));
            if (!in_array($accion, self::ACCIONES_PERMITIDAS)) {
                $errores['accion'] = 'La acción especificada no es válida';
            }
        }
        
        // Validar filtros de prioridad
        if (isset($datos['prioridad'])) {
            $prioridad = strtolower(trim($datos['prioridad']));
            if (!in_array($prioridad, self::PRIORIDADES_PERMITIDAS)) {
                $errores['prioridad'] = 'La prioridad debe ser: alta, media o baja';
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para registrar en la bitácora (método público)
     */
    public function validarRegistrar($datos) {
        return $this->validarRegistrarBitacora($datos);
    }
    
    /**
     * Valida los datos para consultar registros de la bitácora (método público)
     */
    public function validarConsultar($datos) {
        return $this->validarConsultarBitacora($datos);
    }
    
    /**
     * Limpia y sanitiza una descripción
     */
    private function sanitizarDescripcion($descripcion) {
        // Eliminar caracteres peligrosos
        $descripcion = preg_replace('/[<>"\|\&\$\*\?]/', '', $descripcion);
        
        // Eliminar espacios múltiples
        $descripcion = preg_replace('/\s+/', ' ', $descripcion);
        
        // Limitar longitud
        if (mb_strlen($descripcion) > self::MAX_DESCRIPCION) {
            $descripcion = mb_substr($descripcion, 0, self::MAX_DESCRIPCION);
        }
        
        return trim($descripcion);
    }
}

?>

