<?php
use Usuario\ProyectoCasalaiCa\Config\BD;
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_notificacion = $_POST['id_notificacion'] ?? null;

    if ($id_notificacion) {
        try {
            $bd_seguridad = new BD('S');
            $pdo_seguridad = $bd_seguridad->getConexion();
            if (!$pdo_seguridad) {
                echo json_encode(['success' => false, 'error' => 'No se pudo conectar a la base de datos']);
                exit;
            }

            $stmt = $pdo_seguridad->prepare("UPDATE tbl_notificaciones SET leido = 1 WHERE id_notificacion = ?");
            if (!$stmt) {
                $errorInfo = $pdo_seguridad->errorInfo();
                echo json_encode(['success' => false, 'error' => 'Error al preparar la consulta']);
                exit;
            }

            $result = $stmt->execute([$id_notificacion]);
 
            echo json_encode(['success' => $result, 'rows_affected' => $stmt->rowCount()]);
        } catch (PDOException $e) {
           echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID de notificación no proporcionado']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}

?>