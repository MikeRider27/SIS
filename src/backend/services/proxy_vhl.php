<?php
include('/var/www/html/core/connection.php');

// Función para hacer una solicitud POST al servidor FHIR
function fetchFHIRData($url, $body)
{
    // Iniciar cURL
    $ch = curl_init();

    // Establecer la URL y las opciones de cURL para POST
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // Devolver la respuesta como string
    curl_setopt($ch, CURLOPT_POST, true);  // Especificar que es una solicitud POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);  // Agregar el cuerpo de la solicitud

    // Encabezados de la solicitud
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',  // Encabezado para enviar FHIR JSON
        'Accept: application/json'         // Encabezado para aceptar JSON si es posible
    ));

    // Ejecutar la solicitud y capturar la respuesta
    $response = curl_exec($ch);

    // Verificar si hubo errores en la solicitud
    if (curl_errno($ch)) {
        echo json_encode(['error' => 'Error de cURL: ' . curl_error($ch)]);
        curl_close($ch);
        return false;
    }

    // Cerrar cURL
    curl_close($ch);

    // Devolver la respuesta tal cual, en formato RAW
    return $response;
}

// Configura los encabezados para permitir solicitudes desde el frontend
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Ajusta según sea necesario, o especifica dominios permitidos
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Manejar las solicitudes OPTIONS para CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener el cuerpo de la solicitud
    $body = file_get_contents('php://input');

    // Construir la URL del recurso FHIR
    $url = "https://gdncn.mspbs.gov.py/v2/vshcIssuance";

    // Llamar a la función para enviar los datos al servidor FHIR
    $result = fetchFHIRData($url, $body);

    if ($result) {
        // Devolver el contenido en RAW (sin encapsular en JSON)
        $result1 = [
            'base64Image' => $result,          
        ];
        echo json_encode($result1, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener datos del servidor FHIR.']);
    }
} else {
    http_response_code(405); // Método no permitido
    echo json_encode(['error' => 'Método no permitido, solo se acepta POST.']);
}
