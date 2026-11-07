<?php


// 3. Obtener los datos JSON enviados por la App Móvil
$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, true);

// Extraer el mensaje enviado desde React Native
$mensajeApp = isset($input['mensaje']) ? $input['mensaje'] : 'No se recibió ningún mensaje';

// 4. Preparar la respuesta del servidor
$respuesta = [
    "status" => "success",
    "code" => 200,
    "mensaje_servidor" => "¡Hola desde el hosting! La API recibió tu mensaje correctamente.",
    "datos_recibidos" => $mensajeApp,
    "timestamp" => date("Y-m-d H:i:s")
];

// 5. Retornar el objeto JSON
echo json_encode($respuesta);
?>