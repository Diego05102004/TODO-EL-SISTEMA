<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Config\Encryption;
use PDO;
use PDOException;
class Recepcion extends BD{
    private $idproveedor;
    private $correlativo;
    private $desc;
    private $fecha;
    private $costo;
    private $estado;
    private $tablerecepcion = 'tbl_recepcion_productos';
    private $encryption;
    
    // Campos cifrados de proveedores (para descifrar en reportes)
    const CAMPOS_CIFRADOS_PROVEEDORES = ['nombre_proveedor', 'nombre_representante', 'telefono_1', 'telefono_2', 'direccion_proveedor', 'correo_proveedor'];
    
    // Constantes de validación
    const MAX_REGISTROS_PAGINA = 100;
    const MAX_RANGO_FECHAS_DIAS = 365;
    const CAMPOS_OBLIGATORIOS = ['idproveedor', 'correlativo'];
    
    const MAX_ID_PROVEEDOR = 999999999;
    const MIN_ID_PROVEEDOR = 1;
    const MAX_CORRELATIVO = 50;
    const MIN_CORRELATIVO = 3;
    const MAX_DESCRIPCION = 500;
    const MIN_DESCRIPCION = 0;
    const MAX_COSTO = 999999.99;
    const MIN_COSTO = 0.01;
    const MAX_ID_RECEPCION = 999999999;
    const MIN_ID_RECEPCION = 1;
    const MAX_ID_PRODUCTO = 999999999;
    const MIN_ID_PRODUCTO = 1;
    const MAX_CANTIDAD = 99999;
    const MIN_CANTIDAD = 1;
    const ESTADOS_VALIDOS = ['habilitado', 'anulado', 'deshabilitado'];
    const ESTADOS_VALIDOS_CAMBIO = ['habilitado', 'anulado'];
    const MAX_ANIO = 2099;
    const MIN_ANIO = 2000;
    const MAX_MES = 12;
    const MIN_MES = 1;
    
    const FORMATOS_REPORTE = ['pdf', 'excel', 'csv'];

    public function getidproveedor() {
        return $this->idproveedor;
    }
    public function setidproveedor($idproveedor) {
        $this->idproveedor = $idproveedor;
    }

    public function getcorrelativo() {
        return $this->correlativo;
    }
    public function setcorrelativo($correlativo) {
        $this->correlativo = $correlativo;
    }

    public function getdesc() {
        return $this->desc;
    }
    public function setdesc($desc) {
        $this->desc = $desc;
    }
    
    public function getfecha() {
        return $this->fecha;
    }
    public function setfecha($fecha) {
        $this->fecha = $fecha;
    }

    public function setcosto($costo) {
        $this->costo = $costo;
    }
    public function getcosto() {
        return $this->costo;
    }

    public function getestado() {
        return $this->estado;
    }
    public function setestado($estado) {
        $this->estado = $estado;
    }

    public function __construct($tipo = 'P') {
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

    protected function ejecutarConConexionSegura($operation, $usarTransaccion = true) {
        try {
            parent::__construct('P'); 
            $pdo = parent::getConexion(); 

            if (!$pdo instanceof \PDO) {
                throw new \RuntimeException("La conexión PDO no es válida o es nula.");
            }

            if ($usarTransaccion) {
                $pdo->beginTransaction();
            }
            $resultado = $operation($pdo);
            if ($usarTransaccion && $pdo->inTransaction()) {
                $pdo->commit();
            }
            
            return $resultado;
        } catch (\Exception $e) {
            $pdo = parent::getConexion();
            if ($pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new \RuntimeException("Error en operación de base de datos: " . $e->getMessage());
        } finally {
            $this->cerrar();
        }
    }

    private function ejecutarProcedimiento($pdo, $sql, $parametros = []) {
        $stmt = $pdo->prepare($sql);
        foreach ($parametros as $nombre => $valor) {
            $stmt->bindValue(is_int($nombre) ? $nombre + 1 : $nombre, $valor);
        }
        $stmt->execute();

        $filas = $stmt->columnCount() > 0 ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        while ($stmt->nextRowset()) {
            while ($stmt->fetch(PDO::FETCH_ASSOC)) {
                // Consumir resultsets adicionales que MariaDB devuelve al llamar SPs.
            }
        }
        $stmt->closeCursor();

        return $filas;
    }

    // Helper validation methods
    private function sanitizarDatos($datos) {
        if (!is_array($datos)) {
            return $datos;
        }
        
        $datos_sanitizados = [];
        
        // Sanitizar campos de texto
        $campos_texto = ['correlativo', 'desc', 'estado'];
        foreach ($campos_texto as $campo) {
            if (isset($datos[$campo])) {
                $datos_sanitizados[$campo] = trim((string)$datos[$campo]);
            }
        }
        
        // Sanitizar campos numéricos
        $campos_numericos = ['idproveedor', 'costo'];
        foreach ($campos_numericos as $campo) {
            if (isset($datos[$campo])) {
                $datos_sanitizados[$campo] = is_numeric($datos[$campo]) ? $datos[$campo] : 0;
            }
        }
        
        // Sanitizar arrays de productos
        if (isset($datos['idproducto']) && is_array($datos['idproducto'])) {
            $datos_sanitizados['idproducto'] = array_map('intval', $datos['idproducto']);
        }
        if (isset($datos['cantidad']) && is_array($datos['cantidad'])) {
            $datos_sanitizados['cantidad'] = array_map('intval', $datos['cantidad']);
        }
        if (isset($datos['costo']) && is_array($datos['costo'])) {
            $datos_sanitizados['costo'] = array_map('floatval', $datos['costo']);
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
            $errores['datos'] = 'Los datos deben ser un arreglo';
            return $errores;
        }
        
        // Validar campos obligatorios según la operación
        if ($operacion === 'registrar') {
            foreach (self::CAMPOS_OBLIGATORIOS as $campo) {
                if (!isset($datos[$campo]) || $datos[$campo] === '' || $datos[$campo] === null) {
                    $errores[$campo] = 'El campo ' . $campo . ' es obligatorio';
                }
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
        
        // Validar ID del proveedor
        if (isset($datos['idproveedor'])) {
            $idproveedor = (int)$datos['idproveedor'];
            if ($idproveedor < self::MIN_ID_PROVEEDOR || $idproveedor > self::MAX_ID_PROVEEDOR) {
                $errores['idproveedor'] = 'El ID del proveedor debe ser un número entre ' . self::MIN_ID_PROVEEDOR . ' y ' . self::MAX_ID_PROVEEDOR;
            }
        }
        
        // Validar correlativo
        if (isset($datos['correlativo'])) {
            $correlativo = trim((string)$datos['correlativo']);
            if ($correlativo !== '' && mb_strlen($correlativo) < self::MIN_CORRELATIVO) {
                $errores['correlativo'] = 'El N° de Factura debe tener al menos ' . self::MIN_CORRELATIVO . ' caracteres';
            } elseif (mb_strlen($correlativo) > self::MAX_CORRELATIVO) {
                $errores['correlativo'] = 'El N° de Factura no debe exceder los ' . self::MAX_CORRELATIVO . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $correlativo)) {
                $errores['correlativo'] = 'El N° de Factura solo puede contener letras, números y guiones';
            }
        }
        
        // Validar descripción
        if (isset($datos['desc'])) {
            $desc = trim((string)$datos['desc']);
            if ($desc !== '' && mb_strlen($desc) > self::MAX_DESCRIPCION) {
                $errores['desc'] = 'La descripción no debe exceder los ' . self::MAX_DESCRIPCION . ' caracteres';
            }
        }
        
        // Validar fecha
        if (isset($datos['fecha'])) {
            $fecha = trim((string)$datos['fecha']);
            if ($fecha !== '' && !$this->validarFormatoFecha($fecha)) {
                $errores['fecha'] = 'La fecha debe tener el formato AAAA-MM-DD';
            }
        }
        
        // Validar costo
        if (isset($datos['costo'])) {
            $costo = (float)$datos['costo'];
            if ($costo < self::MIN_COSTO || $costo > self::MAX_COSTO) {
                $errores['costo'] = 'El costo debe estar entre ' . self::MIN_COSTO . ' y ' . self::MAX_COSTO;
            }
        }
        
        // Validar estado
        if (isset($datos['estado'])) {
            $estado = trim((string)$datos['estado']);
            if (!in_array($estado, self::ESTADOS_VALIDOS)) {
                $errores['estado'] = 'El estado no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS);
            }
        }
        
        return $errores;
    }
    
    private function validarFiltros($filtros) {
        $errores = [];
        
        if (!is_array($filtros)) {
            $errores['filtros'] = 'Los filtros deben ser un arreglo';
            return $errores;
        }
        
        // Validar página
        if (isset($filtros['pagina'])) {
            $pagina = (int)$filtros['pagina'];
            if ($pagina < 1) {
                $errores['pagina'] = 'La página debe ser un número mayor a 0';
            }
        }
        
        // Validar límite
        if (isset($filtros['limite'])) {
            $limite = (int)$filtros['limite'];
            if ($limite < 1 || $limite > self::MAX_REGISTROS_PAGINA) {
                $errores['limite'] = 'El límite debe estar entre 1 y ' . self::MAX_REGISTROS_PAGINA;
            }
        }
        
        // Validar ID de recepción
        if (isset($filtros['id_recepcion'])) {
            $id_recepcion = (int)$filtros['id_recepcion'];
            if ($id_recepcion < self::MIN_ID_RECEPCION || $id_recepcion > self::MAX_ID_RECEPCION) {
                $errores['id_recepcion'] = 'El ID de la recepción debe ser un número entre ' . self::MIN_ID_RECEPCION . ' y ' . self::MAX_ID_RECEPCION;
            }
        }
        
        // Validar correlativo
        if (isset($filtros['correlativo'])) {
            $correlativo = trim((string)$filtros['correlativo']);
            if ($correlativo !== '' && mb_strlen($correlativo) > self::MAX_CORRELATIVO) {
                $errores['correlativo'] = 'El correlativo no debe exceder los ' . self::MAX_CORRELATIVO . ' caracteres';
            }
        }
        
        // Validar fechas
        if (isset($filtros['fecha_inicio'])) {
            $fecha_inicio = trim((string)$filtros['fecha_inicio']);
            if ($fecha_inicio !== '' && !$this->validarFormatoFecha($fecha_inicio)) {
                $errores['fecha_inicio'] = 'La fecha de inicio debe tener el formato AAAA-MM-DD';
            }
        }
        
        if (isset($filtros['fecha_fin'])) {
            $fecha_fin = trim((string)$filtros['fecha_fin']);
            if ($fecha_fin !== '' && !$this->validarFormatoFecha($fecha_fin)) {
                $errores['fecha_fin'] = 'La fecha de fin debe tener el formato AAAA-MM-DD';
            }
        }
        
        // Validar rango de fechas
        if (isset($filtros['fecha_inicio']) && isset($filtros['fecha_fin'])) {
            $fecha_inicio = strtotime($filtros['fecha_inicio']);
            $fecha_fin = strtotime($filtros['fecha_fin']);
            if ($fecha_inicio && $fecha_fin && $fecha_inicio > $fecha_fin) {
                $errores['rango_fechas'] = 'La fecha de inicio no puede ser mayor a la fecha de fin';
            }
            
            if ($fecha_inicio && $fecha_fin) {
                $dias_diferencia = ($fecha_fin - $fecha_inicio) / (60 * 60 * 24);
                if ($dias_diferencia > self::MAX_RANGO_FECHAS_DIAS) {
                    $errores['rango_fechas'] = 'El rango de fechas no puede exceder los ' . self::MAX_RANGO_FECHAS_DIAS . ' días';
                }
            }
        }
        
        // Validar ID de proveedor
        if (isset($filtros['id_proveedor'])) {
            $id_proveedor = (int)$filtros['id_proveedor'];
            if ($id_proveedor < self::MIN_ID_PROVEEDOR || $id_proveedor > self::MAX_ID_PROVEEDOR) {
                $errores['id_proveedor'] = 'El ID del proveedor debe ser un número entre ' . self::MIN_ID_PROVEEDOR . ' y ' . self::MAX_ID_PROVEEDOR;
            }
        }
        
        return $errores;
    }
    
    private function validarId($id_recepcion) {
        $errores = [];
        
        if ($id_recepcion === null || $id_recepcion === '') {
            $errores['id_recepcion'] = 'El ID de la recepción es obligatorio';
        } else {
            $id_recepcion = (int)$id_recepcion;
            if ($id_recepcion < self::MIN_ID_RECEPCION || $id_recepcion > self::MAX_ID_RECEPCION) {
                $errores['id_recepcion'] = 'El ID de la recepción debe ser un número entre ' . self::MIN_ID_RECEPCION . ' y ' . self::MAX_ID_RECEPCION;
            }
        }
        
        return $errores;
    }
    
    private function validarCorrelativo($correlativo) {
        $errores = [];
        
        if ($correlativo === null || $correlativo === '') {
            $errores['correlativo'] = 'El correlativo de la recepción es obligatorio';
        } else {
            $correlativo = trim((string)$correlativo);
            if (mb_strlen($correlativo) < self::MIN_CORRELATIVO || mb_strlen($correlativo) > self::MAX_CORRELATIVO) {
                $errores['correlativo'] = 'El correlativo debe tener entre ' . self::MIN_CORRELATIVO . ' y ' . self::MAX_CORRELATIVO . ' caracteres';
            }
            
            // Validar formato alfanumérico
            if (!preg_match('/^[A-Z0-9\-]+$/', $correlativo)) {
                $errores['correlativo'] = 'El correlativo solo puede contener letras mayúsculas, números y guiones';
            }
        }
        
        return $errores;
    }
    
    private function validarIntegridadReferencial($id_recepcion, $pdo) {
        $errores = [];
        
        $filas = $this->ejecutarProcedimiento(
            $pdo,
            'CALL sp_consultar_recepciones(?, ?, ?, ?, ?, ?, ?, ?, @total_validacion_recepcion)',
            [(int)$id_recepcion, null, null, null, null, null, 1, 1]
        );
        if (!$filas) {
            $errores['id_recepcion'] = 'La recepción no existe';
            return $errores;
        }

        if (($filas[0]['estado'] ?? '') === 'anulado') {
            $errores['estado'] = 'La recepción ya está anulada';
        }
        
        return $errores;
    }
    
    private function validarReporte($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['datos'] = 'Los datos deben ser un arreglo';
            return $errores;
        }
        
        // Validar tipo de reporte
        if (isset($datos['tipo_reporte'])) {
            if (!in_array($datos['tipo_reporte'], self::FORMATOS_REPORTE)) {
                $errores['tipo_reporte'] = 'El tipo de reporte no es válido. Tipos permitidos: ' . implode(', ', self::FORMATOS_REPORTE);
            }
        }
        
        // Validar fechas si vienen
        if (isset($datos['fechaInicio'])) {
            $fechaInicio = trim((string)$datos['fechaInicio']);
            if ($fechaInicio !== '' && !$this->validarFormatoFecha($fechaInicio)) {
                $errores['fechaInicio'] = 'La fecha de inicio debe tener el formato AAAA-MM-DD';
            }
        }
        
        if (isset($datos['fechaFin'])) {
            $fechaFin = trim((string)$datos['fechaFin']);
            if ($fechaFin !== '' && !$this->validarFormatoFecha($fechaFin)) {
                $errores['fechaFin'] = 'La fecha de fin debe tener el formato AAAA-MM-DD';
            }
        }
        
        // Validar año si viene
        if (isset($datos['anio'])) {
            $anio = (int)$datos['anio'];
            if ($anio < self::MIN_ANIO || $anio > self::MAX_ANIO) {
                $errores['anio'] = 'El año debe estar entre ' . self::MIN_ANIO . ' y ' . self::MAX_ANIO;
            }
        }
        
        // Validar ID de proveedor si viene
        if (isset($datos['proveedorId'])) {
            $proveedorId = (int)$datos['proveedorId'];
            if ($proveedorId < self::MIN_ID_PROVEEDOR || $proveedorId > self::MAX_ID_PROVEEDOR) {
                $errores['proveedorId'] = 'El ID del proveedor debe ser un número entre ' . self::MIN_ID_PROVEEDOR . ' y ' . self::MAX_ID_PROVEEDOR;
            }
        }
        
        return $errores;
    }
    
    // Main validation methods
    
    private function validarFormatoFecha($fecha) {
        // Validar formato AAAA-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }
        
        $partes = explode('-', $fecha);
        $anio = (int)$partes[0];
        $mes = (int)$partes[1];
        $dia = (int)$partes[2];
        
        // Validar rango de año
        if ($anio < self::MIN_ANIO || $anio > self::MAX_ANIO) {
            return false;
        }
        
        // Validar rango de mes
        if ($mes < self::MIN_MES || $mes > self::MAX_MES) {
            return false;
        }
        
        // Validar día según mes (validación básica)
        $diasPorMes = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        if (($anio % 4 == 0 && $anio % 100 != 0) || ($anio % 400 == 0)) {
            $diasPorMes[1] = 29; // Año bisiesto
        }
        
        if ($dia < 1 || $dia > $diasPorMes[$mes - 1]) {
            return false;
        }
        
        return true;
    }
    
    private function validarDetalleProductos($productos) {
        $errores = [];
        
        if (!is_array($productos)) {
            $errores['productos'] = 'Los datos de productos deben ser un arreglo';
            return $errores;
        }
        
        // Validar que los arrays no estén vacíos
        if (empty($productos['idproducto']) || !is_array($productos['idproducto'])) {
            $errores['productos'] = 'Debe agregar al menos un producto';
            return $errores;
        }
        
        // Validar que todos los arrays tengan la misma cantidad de elementos
        if (count($productos['idproducto']) !== count($productos['cantidad']) || 
            count($productos['idproducto']) !== count($productos['costo'])) {
            $errores['productos'] = 'La información de productos está incompleta';
            return $errores;
        }
        
        // Validar cada producto
        for ($i = 0; $i < count($productos['idproducto']); $i++) {
            $index = $i + 1; // Para mostrar en mensajes de error
            
            // Validar ID de producto
            $idProd = (int)$productos['idproducto'][$i];
            if ($idProd < self::MIN_ID_PRODUCTO || $idProd > self::MAX_ID_PRODUCTO) {
                $errores["producto_{$i}"] = "El producto {$index} no es válido";
            }
            
            // Validar cantidad
            $cant = (int)$productos['cantidad'][$i];
            if ($cant < self::MIN_CANTIDAD || $cant > self::MAX_CANTIDAD) {
                $errores["cantidad_{$i}"] = "La cantidad del producto {$index} debe estar entre " . self::MIN_CANTIDAD . " y " . self::MAX_CANTIDAD;
            }
            
            // Validar costo
            $cost = (float)$productos['costo'][$i];
            if ($cost < self::MIN_COSTO || $cost > self::MAX_COSTO) {
                $errores["costo_{$i}"] = "El costo del producto {$index} debe estar entre " . self::MIN_COSTO . " y " . self::MAX_COSTO;
            }
        }
        
        return $errores;
    }
    
    public function validarRegistrar($datos) {
        $datos = $this->sanitizarDatos($datos);
        
        $errores = $this->validarEsquema($datos, 'registrar');
        if (!empty($errores)) {
            return $errores;
        }
        
        $errores = $this->validarFormato($datos);
        if (!empty($errores)) {
            return $errores;
        }
        
        return $errores;
    }
    
    public function validarConsultar($filtros = []) {
        return $this->validarFiltros($filtros);
    }
    
    public function validarDetallar($id_recepcion) {
        $errores = $this->validarId($id_recepcion);
        if (!empty($errores)) {
            return $errores;
        }
        
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_recepcion) {
            return $this->validarIntegridadReferencial($id_recepcion, $pdo);
        });
    }
    
    public function validarAnular($correlativo) {
        $errores = $this->validarCorrelativo($correlativo);
        if (!empty($errores)) {
            return $errores;
        }

        $this->setcorrelativo($correlativo);
        $recepcion = $this->buscar();
        if (($recepcion['resultado'] ?? '') !== 'encontró') {
            return ['correlativo' => 'La recepción no existe'];
        }
        if (($recepcion['estado'] ?? '') === 'anulado') {
            return ['estado' => 'La recepción ya está anulada'];
        }
        return [];
    }
    
    public function validarGenerarReporte($datos) {
        return $this->validarReporte($datos);
    }
    
    public function obtenerRecepcionesConFiltros($filtros = []) {
        $errores = $this->validarConsultar($filtros);
        if (!empty($errores)) {
            return ['error' => $errores];
        }
        
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($filtros) {
            $pagina = max(1, (int)($filtros['pagina'] ?? 1));
            $limite = min(self::MAX_REGISTROS_PAGINA, max(1, (int)($filtros['limite'] ?? self::MAX_REGISTROS_PAGINA)));
            $parametros = [
                $filtros['id_recepcion'] ?? null,
                !empty($filtros['correlativo']) ? $filtros['correlativo'] : null,
                $filtros['id_proveedor'] ?? null,
                $filtros['fecha_inicio'] ?? null,
                $filtros['fecha_fin'] ?? null,
                $filtros['estado'] ?? null,
                $pagina,
                $limite
            ];
            $recepciones = $this->ejecutarProcedimiento(
                $pdo,
                'CALL sp_consultar_recepciones(?, ?, ?, ?, ?, ?, ?, ?, @total_recepciones)',
                $parametros
            );
            $total = (int)$pdo->query('SELECT @total_recepciones')->fetchColumn();
            
            return [
                'data' => $recepciones,
                'total' => $total,
                'pagina' => $pagina,
                'limite' => $limite,
                'total_paginas' => ceil($total / $limite)
            ];
        }, false);
        
        // Descifrar datos personales del proveedor
        if (isset($resultado['data']) && is_array($resultado['data'])) {
            $resultado['data'] = $this->encryption->decryptResults($resultado['data'], self::CAMPOS_CIFRADOS_PROVEEDORES);
        }
        
        return $resultado;
    }
    
    private function verificarRecepcionExistente($id_recepcion) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_recepcion) {
            $filas = $this->ejecutarProcedimiento(
                $pdo,
                'CALL sp_consultar_recepciones(?, ?, ?, ?, ?, ?, ?, ?, @total_validacion_recepcion)',
                [(int)$id_recepcion, null, null, null, null, null, 1, 1]
            );
            return !empty($filas);
        }, false);
    }
    
    private function verificarProveedorExistente($id_proveedor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_proveedor) {
            foreach ($this->ejecutarProcedimiento($pdo, 'CALL sp_consultar_proveedores_recepcion()') as $proveedor) {
                if ((int)$proveedor['id_proveedor'] === (int)$id_proveedor) {
                    return true;
                }
            }
            return false;
        }, false);
    }

    public function registrarRecepcion($idproducto, $cantidad, $costo, $porcentajeIva, $id_usuario_auditor) {
        return $this->r_recepcion($idproducto, $cantidad, $costo, $porcentajeIva, $id_usuario_auditor);
    }

    private function r_recepcion($idproducto, $cantidad, $costo, $porcentajeIva, $id_usuario_auditor) {
        $productos = [];
        foreach ($idproducto as $indice => $id) {
            $productos[] = [
                'id_producto' => (int)$id,
                'cantidad' => (int)($cantidad[$indice] ?? 0),
                'costo' => (float)($costo[$indice] ?? 0)
            ];
        }
        $productosJson = json_encode($productos, JSON_UNESCAPED_UNICODE);
        if ($productosJson === false) {
            throw new \RuntimeException('No se pudieron preparar los productos de la recepción.');
        }

        return $this->ejecutarConConexionSegura(function($pdo) use ($productosJson, $porcentajeIva, $id_usuario_auditor) {
            $stmt = $pdo->prepare('CALL sp_registrar_recepcion(:proveedor, :correlativo, :estado, :iva, :productos, :id_usuario_auditor, @id_recepcion)');
            $stmt->bindValue(':proveedor', (int)$this->idproveedor, PDO::PARAM_INT);
            $stmt->bindValue(':correlativo', (string)$this->correlativo, PDO::PARAM_STR);
            $stmt->bindValue(':estado', (string)$this->estado, PDO::PARAM_STR);
            $stmt->bindValue(':iva', number_format((float)$porcentajeIva, 2, '.', ''), PDO::PARAM_STR);
            $stmt->bindValue(':productos', $productosJson, PDO::PARAM_STR);
            $stmt->bindValue(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $idRecepcion = (int)$pdo->query('SELECT @id_recepcion')->fetchColumn();
            if ($idRecepcion < 1) {
                throw new \RuntimeException('El procedimiento no devolvió el ID de la recepción creada.');
            }

            $recepcion = $this->ejecutarProcedimiento($pdo, 'CALL sp_obtener_recepcion_por_correlativo(?)', [$this->correlativo]);
            $productosRegistrados = $this->ejecutarProcedimiento($pdo, 'CALL sp_obtener_productos_recepcion(?)', [$idRecepcion]);
            $datosRecepcion = $recepcion[0] ?? null;
            if ($datosRecepcion) {
                $datosRecepcion = $this->encryption->decryptArray($datosRecepcion, self::CAMPOS_CIFRADOS_PROVEEDORES);
            }

            return [
                'id_recepcion' => $idRecepcion,
                'productos' => $productosRegistrados,
                'recepcion' => $datosRecepcion
            ];
        }, false);
    }

    public function existeCorrelativo($r) {
        return $this->ex_correlativo($r); 
    }

    private function ex_correlativo($r) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($r) {
            $filas = $this->ejecutarProcedimiento(
                $pdo,
                'CALL sp_consultar_recepciones(?, ?, ?, ?, ?, ?, ?, ?, @total_correlativo)',
                [null, $r['correlativo'] ?? null, null, null, null, 'habilitado', 1, 1]
            );
            return !empty($filas);
        }, false);
    }

    public function obtenerUltimaRecepcion() {
        return $this->obtUltimaRecepcion(); 
    }
    private function obtUltimaRecepcion() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $filas = $this->ejecutarProcedimiento($pdo, 'CALL sp_obtener_ultima_recepcion()');
            if (!$filas) {
                return null;
            }
            return $this->encryption->decryptArray($filas[0], self::CAMPOS_CIFRADOS_PROVEEDORES);
        }, false);
    }

    public function getrecepcion(){
        return $this->g_recepcion();
    }
    private function g_recepcion(){
        $resultado = $this->obtenerRecepcionesConFiltros([
            'estado' => 'habilitado',
            'pagina' => 1,
            'limite' => self::MAX_REGISTROS_PAGINA
        ]);
        return $resultado['data'] ?? [];
    }

    public function obtenerProductosPorRecepcion($id_recepcion) {
        return $this->obt_productos_recepcion($id_recepcion); 
    }
    private function obt_productos_recepcion($id_recepcion) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_recepcion){
            return $this->ejecutarProcedimiento($pdo, 'CALL sp_obtener_productos_recepcion(?)', [(int)$id_recepcion]);
        }, false);
    }

    public function anularRecepcion($correlativo, $id_usuario_auditor) {
        return $this->an_recepcion($correlativo, $id_usuario_auditor);
    }
    private function an_recepcion($correlativo, $id_usuario_auditor) {
        try {
            return $this->ejecutarConConexionSegura(function($pdo) use ($correlativo, $id_usuario_auditor) {
                $stmt = $pdo->prepare('CALL sp_anular_recepcion(:correlativo, :id_usuario_auditor, @resultado_anulacion)');
                $stmt->bindValue(':correlativo', (string)$correlativo, PDO::PARAM_STR);
                $stmt->bindValue(':id_usuario_auditor', $id_usuario_auditor, \PDO::PARAM_INT);
                $stmt->execute();
                $stmt->closeCursor();
                $resultado = (int)$pdo->query('SELECT @resultado_anulacion')->fetchColumn();
                return $resultado === 1
                    ? ['status' => 'success', 'message' => 'Recepción Anulada Correctamente']
                    : ['status' => 'error', 'message' => 'No se pudo anular la recepción'];
            }, false);
        } catch (\Throwable $e) {
            error_log('Error al anular recepción mediante sp_anular_recepcion: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'No se pudo anular la recepción. Inténtelo nuevamente.'];
        }
    }

    public function obtenerIdRecepcionPorCorrelativo($correlativo) {
        return $this->obt_id_recepcion_por_correlativo($correlativo);
    }
    private function obt_id_recepcion_por_correlativo($correlativo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($correlativo) {
            $filas = $this->ejecutarProcedimiento($pdo, 'CALL sp_obtener_recepcion_por_correlativo(?)', [$correlativo]);
            return isset($filas[0]['id_recepcion']) ? (int)$filas[0]['id_recepcion'] : null;
        }, false);
    }

    public function obtenerproveedor() {
        return $this->obt_proveedor(); 
    }
    private function obt_proveedor() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            return $this->ejecutarProcedimiento($pdo, 'CALL sp_consultar_proveedores_recepcion()');
        }, false);
        
        // Descifrar datos personales del proveedor
        $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS_PROVEEDORES);
        
        return $resultado;
    }

    public function listadoproductos() {
        return $this->list_productos(); 
    }
    private function list_productos() {
        $respuesta = '';
        foreach ($this->consultarproductos() as $producto) {
            $id = htmlspecialchars((string)$producto['id_producto'], ENT_QUOTES, 'UTF-8');
            $nombre = htmlspecialchars((string)$producto['nombre_producto'], ENT_QUOTES, 'UTF-8');
            $modelo = htmlspecialchars((string)$producto['nombre_modelo'], ENT_QUOTES, 'UTF-8');
            $marca = htmlspecialchars((string)$producto['nombre_marca'], ENT_QUOTES, 'UTF-8');
            $serial = htmlspecialchars((string)$producto['serial'], ENT_QUOTES, 'UTF-8');
            $respuesta .= "<tr style='cursor:pointer' onclick='colocaproducto(this);'>";
            $respuesta .= "<td style='display:none'>{$id}</td><td>{$id}</td><td>{$nombre}</td>";
            $respuesta .= "<td>{$modelo}</td><td>{$marca}</td><td>{$serial}</td></tr>";
        }
        return ['resultado' => 'listado', 'mensaje' => $respuesta];
    }

    public function consultarproductos() {
        return $this->consul_productos(); 
    }
    private function consul_productos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            return $this->ejecutarProcedimiento($pdo, 'CALL sp_consultar_productos_recepcion()');
        }, false);
    }

    public function buscar() {
        return $this->bus(true);
    }

    public function obtenerDetalle() {
        return $this->bus(false);
    }

    private function bus($incluirEstado) {
        try {
            return $this->ejecutarConConexionSegura(function($pdo) use ($incluirEstado) {
                $filas = $this->ejecutarProcedimiento($pdo, 'CALL sp_obtener_recepcion_por_correlativo(?)', [$this->correlativo]);
                if (empty($filas)) {
                    return ['resultado' => 'no_encontro', 'mensaje' => 'No se encontró la recepción.'];
                }
                $recepcion = $filas[0];
                if (!$incluirEstado && isset($recepcion['estado'])) {
                    unset($recepcion['estado']);
                }
                $recepcion = $this->encryption->decryptArray($recepcion, self::CAMPOS_CIFRADOS_PROVEEDORES);
                $recepcion['resultado'] = 'encontró';
                $recepcion['mensaje'] = 'Recepción encontrada.';
                return $recepcion;
            }, false);
        } catch (\Throwable $e) {
            return ['resultado' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public function getRecepcionesPorProveedor($fechaInicio = null, $fechaFin = null) {
        return $this->getRecepPorProveedor($fechaInicio, $fechaFin);
    }
    private function getRecepPorProveedor($fechaInicio = null, $fechaFin = null) {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($fechaInicio, $fechaFin) {
            return $this->ejecutarProcedimiento($pdo, 'CALL sp_reporte_recepciones_proveedor(?, ?)', [$fechaInicio, $fechaFin]);
        }, false);
        
        // Descifrar datos personales del proveedor
        // Incluimos el alias 'label' que corresponde a nombre_proveedor
        $camposADescifrar = array_merge(self::CAMPOS_CIFRADOS_PROVEEDORES, ['label']);
        if (is_array($resultado) && !empty($resultado)) {
            $resultado = $this->encryption->decryptResults($resultado, $camposADescifrar);
        }
        
        return $resultado;
    }

    public function getProductosMasRecibidos($fechaInicio = null, $fechaFin = null, $proveedor = null) {
        return $this->getProdMasRecibidos($fechaInicio, $fechaFin, $proveedor);
    }
    private function getProdMasRecibidos($fechaInicio = null, $fechaFin = null, $proveedor = null) {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($fechaInicio, $fechaFin, $proveedor) {
            return $this->ejecutarProcedimiento(
                $pdo,
                'CALL sp_reporte_productos_recepcion(?, ?, ?)',
                [$fechaInicio, $fechaFin, $proveedor]
            );
        }, false);
        
        // Descifrar datos personales del proveedor
        // Incluimos el alias 'proveedor' que corresponde a nombre_proveedor
        $camposADescifrar = array_merge(self::CAMPOS_CIFRADOS_PROVEEDORES, ['proveedor']);
        if (is_array($resultado) && !empty($resultado)) {
            $resultado = $this->encryption->decryptResults($resultado, $camposADescifrar);
        }
        
        return $resultado;
    }

    public function getRecepcionesMensuales($anio = null) {
        return $this->getRecepMensuales($anio);
    }
    private function getRecepMensuales($anio = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($anio) {
            $resultados = $this->ejecutarProcedimiento($pdo, 'CALL sp_reporte_recepciones_mensuales(?)', [$anio]);

            $meses = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];

            foreach ($resultados as &$fila) {
                $fila['label'] = $meses[$fila['mes_num']] ?? 'Desconocido';
                // conservar mes_num y anio para filtrado en frontend
            }

            return $resultados;
        }, false);
    }

    public function agregarFeedbackDataset($datosFeedback) {
        return $this->agg_FeedbackDataset($datosFeedback);
    }

    private function agg_FeedbackDataset($datosFeedback) {
        // Ruta hacia el feedback_dataset.csv dentro del microservicio
        $rutaCsv = dirname(__DIR__, 4) . '/microservicio/entrenamiento/feedback_dataset.csv';

        if (!file_exists($rutaCsv)) {
            error_log("CRítico: No se encontró el feedback_dataset.csv en: " . $rutaCsv);
            return false;
        }

        $fp = fopen($rutaCsv, 'a');
        if ($fp === false) {
            error_log("CRítico: No se pudo abrir el archivo feedback_dataset.csv para escritura.");
            return false;
        }

        $timestamp = date('Y-m-d H:i:s');
        $idRecepcion = $datosFeedback['id_recepcion'] ?? 0;
        $idFactura = $datosFeedback['id_factura'] ?? '';
        
        // Datos de predicciones vs ground truth
        $predProveedor = $datosFeedback['prediccion_proveedor'] ?? '';
        $gtProveedor = $datosFeedback['ground_truth_proveedor'] ?? '';
        $errorProveedor = $this->calcularErrorCampo($predProveedor, $gtProveedor);
        
        $predCorrelativo = $datosFeedback['prediccion_correlativo'] ?? '';
        $gtCorrelativo = $datosFeedback['ground_truth_correlativo'] ?? '';
        $errorCorrelativo = $this->calcularErrorCampo($predCorrelativo, $gtCorrelativo);
        
        $predProductos = json_encode($datosFeedback['prediccion_productos'] ?? [], JSON_UNESCAPED_UNICODE);
        $gtProductos = json_encode($datosFeedback['ground_truth_productos'] ?? [], JSON_UNESCAPED_UNICODE);
        $errorProductos = $this->calcularErrorProductos($datosFeedback['prediccion_productos'] ?? [], $datosFeedback['ground_truth_productos'] ?? []);
        
        $predSubtotal = $datosFeedback['prediccion_subtotal'] ?? 0;
        $gtSubtotal = $datosFeedback['ground_truth_subtotal'] ?? 0;
        $errorSubtotal = $this->calcularErrorNumerico($predSubtotal, $gtSubtotal);
        
        $predTotal = $datosFeedback['prediccion_total'] ?? 0;
        $gtTotal = $datosFeedback['ground_truth_total'] ?? 0;
        $errorTotal = $this->calcularErrorNumerico($predTotal, $gtTotal);
        
        $discrepanciasCorregidas = $datosFeedback['discrepancias_corregidas'] ?? [];

        $fila = [
            $timestamp,
            $idRecepcion,
            $idFactura,
            $predProveedor,
            $gtProveedor,
            $errorProveedor,
            $predCorrelativo,
            $gtCorrelativo,
            $errorCorrelativo,
            $predProductos,
            $gtProductos,
            $errorProductos,
            $predSubtotal,
            $gtSubtotal,
            $errorSubtotal,
            $predTotal,
            $gtTotal,
            $errorTotal,
            json_encode($discrepanciasCorregidas, JSON_UNESCAPED_UNICODE),
            $datosFeedback['confianza_ocr_promedio'] ?? 0
        ];

        fputcsv($fp, $fila);
        fclose($fp);
        return true;
    }

    public function eliminarFeedbackDataset($idRecepcion) {
        return $this->del_FeedbackDataset($idRecepcion);
    }

    private function del_FeedbackDataset($idRecepcion) {
        // Ruta hacia el feedback_dataset.csv dentro del microservicio
        $rutaCsv = dirname(__DIR__, 4) . '/microservicio/entrenamiento/feedback_dataset.csv';

        if (!file_exists($rutaCsv)) {
            error_log("ADVERTENCIA: No se encontró el feedback_dataset.csv en: " . $rutaCsv);
            return false;
        }

        // Leer todo el archivo
        $filas = file($rutaCsv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($filas === false) {
            error_log("CRítico: No se pudo leer el archivo feedback_dataset.csv.");
            return false;
        }

        // Convertir a array de arrays
        $datos = [];
        $encabezado = null;
        foreach ($filas as $indice => $linea) {
            $fila = str_getcsv($linea);
            if ($indice === 0) {
                $encabezado = $fila; // Guardar encabezado
                continue;
            }
            // El id_recepcion está en la columna 1 (índice 1)
            if (isset($fila[1]) && $fila[1] != $idRecepcion) {
                $datos[] = $fila; // Mantener filas que no coinciden con el id_recepcion
            }
        }

        // Reescribir el archivo con los datos filtrados
        $fp = fopen($rutaCsv, 'w');
        if ($fp === false) {
            error_log("CRítico: No se pudo abrir el archivo feedback_dataset.csv para escritura.");
            return false;
        }

        // Escribir encabezado
        if ($encabezado !== null) {
            fputcsv($fp, $encabezado);
        }

        // Escribir datos filtrados
        foreach ($datos as $fila) {
            fputcsv($fp, $fila);
        }

        fclose($fp);
        
        // Contar registros restantes (sin encabezado)
        $registrosRestantes = count($datos);
        error_log("Eliminado registro de feedback para recepción ID: $idRecepcion. Registros restantes: $registrosRestantes");
        
        // Si hay más de 5 registros, reentrenar el modelo
        if ($registrosRestantes >= 5) {
            $this->reentrenarModelo();
        }
        
        return true;
    }

    private function reentrenarModelo() {
        // Ruta hacia el script de reentrenamiento
        $rutaScript = dirname(__DIR__, 4) . '/microservicio/retrain.py';
        
        if (!file_exists($rutaScript)) {
            error_log("ADVERTENCIA: No se encontró el script retrain.py en: " . $rutaScript);
            return false;
        }

        // Ejecutar el script de reentrenamiento
        $comando = 'python "' . $rutaScript . '"';
        $salida = [];
        $codigoRetorno = 0;
        
        exec($comando, $salida, $codigoRetorno);
        
        if ($codigoRetorno !== 0) {
            error_log("CRítico: Error al reentrenar el modelo. Código de retorno: $codigoRetorno");
            error_log("Salida: " . implode("\n", $salida));
            return false;
        }
        
        error_log("Modelo reentrenado exitosamente después de eliminar feedback.");
        return true;
    }

    private function calcularErrorCampo($prediccion, $groundTruth) {
        if (empty($prediccion) && empty($groundTruth)) return 0;
        if (empty($prediccion) || empty($groundTruth)) return 1;
        
        $pred = strtolower(trim($prediccion));
        $gt = strtolower(trim($groundTruth));
        
        if ($pred === $gt) return 0;
        
        // Calcular similitud de Levenshtein
        $distancia = levenshtein($pred, $gt);
        $maxLen = max(strlen($pred), strlen($gt));
        return $maxLen > 0 ? ($distancia / $maxLen) : 1;
    }

    private function calcularErrorNumerico($prediccion, $groundTruth) {
        $pred = floatval($prediccion);
        $gt = floatval($groundTruth);
        
        if ($gt == 0) return $pred == 0 ? 0 : 1;
        
        $diferencia = abs($pred - $gt);
        return $diferencia / $gt;
    }

    private function calcularErrorProductos($predProductos, $gtProductos) {
        if (empty($predProductos) && empty($gtProductos)) return 0;
        if (empty($predProductos) || empty($gtProductos)) return 1;
        
        $totalError = 0;
        $count = 0;
        
        foreach ($gtProductos as $gtProd) {
            $gtNombre = strtolower($gtProd['nombre'] ?? $gtProd['descripcion'] ?? '');
            $gtCantidad = intval($gtProd['cantidad'] ?? 0);
            $gtCosto = floatval($gtProd['costo'] ?? 0);
            
            // Buscar producto correspondiente en predicciones
            $encontrado = false;
            foreach ($predProductos as $predProd) {
                $predNombre = strtolower($predProd['nombre'] ?? $predProd['descripcion'] ?? '');
                
                if (levenshtein($predNombre, $gtNombre) <= 3) {
                    $predCantidad = intval($predProd['cantidad'] ?? 0);
                    $predCosto = floatval($predProd['costo'] ?? 0);
                    
                    $errorCantidad = $gtCantidad > 0 ? abs($predCantidad - $gtCantidad) / $gtCantidad : 0;
                    $errorCosto = $gtCosto > 0 ? abs($predCosto - $gtCosto) / $gtCosto : 0;
                    
                    $totalError += ($errorCantidad + $errorCosto) / 2;
                    $encontrado = true;
                    break;
                }
            }
            
            if (!$encontrado) {
                $totalError += 1; // Producto no encontrado = 100% error
            }
            $count++;
        }
        
        return $count > 0 ? ($totalError / $count) : 1;
    }
}
?>