<?php
include('/var/www/html/core/connection.php');

// Función para hacer una solicitud GET al servidor FHIR
function fetchPatientData($url) {
    // Iniciar cURL
    $ch = curl_init();

    // Establecer la URL y las opciones de cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // Devolver la respuesta como string
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/fhir+json'  // Encabezado para aceptar FHIR JSON    
    ));

    // Ejecutar la solicitud y capturar la respuesta
    $response = curl_exec($ch);

    // Verificar si hubo errores
    if (curl_errno($ch)) {
        echo json_encode(['error' => 'Error de cURL: ' . curl_error($ch)]);
        curl_close($ch);
        return false;
    }

    // Cerrar cURL
    curl_close($ch);

    // Devolver la respuesta en formato JSON
    return $response;
}

// Configura los encabezados para permitir solicitudes desde el frontend
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Ajusta según sea necesario
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Recupera los parámetros de la solicitud
$identifier = isset($_GET['identifier']) ? $_GET['identifier'] : '';


// Construye la URL final
$url = "http://lacpass.create.cl:5001/fhir/DocumentReference/?patient.identifier=$identifier&_format=json&status=current";


// Llamar a la función para obtener los datos

$data = fetchPatientData($url);

if ($data) {
    // Devolver la respuesta al frontend
    echo $data;
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener datos.']);
}
?>
