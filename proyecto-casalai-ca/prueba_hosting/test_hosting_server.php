<?php
/**
 * Archivo para probar conectividad HTTPS desde localhost al hosting
 * Sube este archivo a tu hosting y accede a él desde el navegador
 */

// Configuración
$storageFile = __DIR__ . '/test_hosting_responses.json';

// Headers CORS para permitir solicitudes desde localhost
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Request-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar si es una consulta de respuesta (polling)
if (isset($_GET['check_response'])) {
    $requestId = $_GET['check_response'];
    
    if (file_exists($storageFile)) {
        $responses = json_decode(file_get_contents($storageFile), true);
        
        if (isset($responses[$requestId])) {
            echo json_encode([
                'has_response' => true,
                'response' => $responses[$requestId]
            ]);
            exit;
        }
    }
    
    echo json_encode(['has_response' => false]);
    exit;
}

// Verificar si es una solicitud POST desde localhost
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? date('YmdHis');
    $requestData = file_get_contents('php://input');
    
    // Guardar la solicitud recibida
    $receivedData = [
        'request_id' => $requestId,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_origen' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido',
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'Desconocido',
        'datos' => json_decode($requestData, true) ?? $requestData
    ];
    
    // Guardar en archivo para mostrar en la vista
    $receivedFile = __DIR__ . '/test_hosting_received.json';
    file_put_contents($receivedFile, json_encode($receivedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Solicitud recibida correctamente',
        'request_id' => $requestId,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_origen' => $_SERVER['REMOTE_ADDR']
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Si es GET sin parámetros, mostrar la interfaz web
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Conectividad HTTPS - Hosting</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: #f9f9f9;
        }

        .section h2 {
            color: #555;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .status-badge.online {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-badge.offline {
            background: #fed7d7;
            color: #822727;
        }

        .data-display {
            background: #2d3748;
            color: #a0aec0;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 400px;
            overflow-y: auto;
        }

        .success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .error {
            background: #fed7d7;
            color: #822727;
            border: 1px solid #fc8181;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .info {
            background: #ebf8ff;
            border-left: 4px solid #4299e1;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        button {
            background: #f5576c;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.3s;
        }

        button:hover {
            background: #e04a5e;
        }

        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .refresh-btn {
            background: #667eea;
        }

        .refresh-btn:hover {
            background: #5568d3;
        }

        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            min-height: 100px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌐 Prueba de Conectividad HTTPS - Hosting</h1>
        <p class="subtitle">Este archivo está alojado en el hosting y recibe solicitudes desde localhost</p>

        <div class="info">
            <p><strong>Estado del servidor:</strong> <span class="status-badge online">✅ Online</span></p>
            <p><strong>IP del servidor:</strong> <?php echo $_SERVER['SERVER_ADDR'] ?? 'Desconocida'; ?></p>
            <p><strong>Fecha y hora:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>URL actual:</strong> <?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></p>
        </div>

        <div class="section">
            <h2>📥 Última Solicitud Recibida</h2>
            <button class="refresh-btn" onclick="location.reload()">🔄 Actualizar</button>
            <br><br>
            
            <?php
            $receivedFile = __DIR__ . '/test_hosting_received.json';
            if (file_exists($receivedFile)) {
                $receivedData = json_decode(file_get_contents($receivedFile), true);
                $fileTime = filemtime($receivedFile);
                $timeDiff = time() - $fileTime;
                
                echo '<div class="success">';
                echo '<strong>✅ Solicitud recibida hace ' . $timeDiff . ' segundos</strong><br>';
                echo '<small>Request ID: ' . htmlspecialchars($receivedData['request_id']) . '</small>';
                echo '</div>';
                
                echo '<div class="data-display">';
                echo json_encode($receivedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '<strong>❌ No se han recibido solicitudes aún</strong><br>';
                echo '<small>Usa la vista en localhost para enviar una solicitud</small>';
                echo '</div>';
            }
            ?>
        </div>

        <div class="section">
            <h2>📤 Responder al Localhost</h2>
            
            <?php
            if (file_exists($receivedFile)) {
                $receivedData = json_decode(file_get_contents($receivedFile), true);
                $requestId = $receivedData['request_id'];
            ?>
            
            <p><strong>Request ID:</strong> <?php echo htmlspecialchars($requestId); ?></p>
            
            <label for="responseJson">Respuesta JSON a enviar:</label>
            <textarea id="responseJson">{
    "status": "success",
    "message": "El hosting respondió correctamente",
    "timestamp": "<?php echo date('c'); ?>",
    "hosting_info": {
        "server": "<?php echo $_SERVER['SERVER_NAME'] ?? 'Desconocido'; ?>",
        "ip": "<?php echo $_SERVER['SERVER_ADDR'] ?? 'Desconocida'; ?>",
        "php_version": "<?php echo phpversion(); ?>",
        "https": "<?php echo isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'enabled' : 'disabled'; ?>"
    },
    "datos_recibidos": {
        "mensaje": "Confirmación de recepción desde el hosting",
        "estado": "conexion_exitosa"
    }
}</textarea>
            
            <button onclick="enviarRespuesta('<?php echo $requestId; ?>')">📡 Responder al Localhost</button>
            
            <?php
            } else {
                echo '<div class="error">';
                echo '<strong>❌ No hay solicitud pendiente para responder</strong><br>';
                echo '<small>Primero envía una solicitud desde localhost</small>';
                echo '</div>';
            }
            ?>
        </div>

        <div class="section">
            <h2>📋 Historial de Respuestas Enviadas</h2>
            
            <?php
            if (file_exists($storageFile)) {
                $responses = json_decode(file_get_contents($storageFile), true);
                if (!empty($responses)) {
                    echo '<div class="data-display">';
                    echo json_encode($responses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    echo '</div>';
                } else {
                    echo '<p>No hay respuestas enviadas aún.</p>';
                }
            } else {
                echo '<p>No hay respuestas enviadas aún.</p>';
            }
            ?>
        </div>
    </div>

    <script>
        function enviarRespuesta(requestId) {
            const responseJson = document.getElementById('responseJson').value;
            const btn = event.target;
            
            try {
                JSON.parse(responseJson);
            } catch (e) {
                alert('El JSON no es válido');
                return;
            }
            
            btn.disabled = true;
            btn.textContent = 'Enviando...';
            
            fetch(window.location.href + '?save_response=' + requestId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: responseJson
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.textContent = '📡 Responder al Localhost';
                
                if (data.status === 'success') {
                    alert('✅ Respuesta guardada correctamente. El localhost debería recibirla en unos segundos.');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.textContent = '📡 Responder al Localhost';
                alert('Error: ' + error.message);
            });
        }

        // Auto-refresh cada 10 segundos para mostrar nuevas solicitudes
        setTimeout(() => location.reload(), 10000);
    </script>
</body>
</html>

<?php
// Manejar guardado de respuesta
if (isset($_GET['save_response'])) {
    $requestId = $_GET['save_response'];
    $responseData = file_get_contents('php://input');
    
    // Cargar respuestas existentes
    $responses = [];
    if (file_exists($storageFile)) {
        $responses = json_decode(file_get_contents($storageFile), true) ?? [];
    }
    
    // Guardar nueva respuesta
    $responses[$requestId] = json_decode($responseData, true);
    file_put_contents($storageFile, json_encode($responses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Respuesta guardada',
        'request_id' => $requestId
    ]);
    exit;
}
?>
