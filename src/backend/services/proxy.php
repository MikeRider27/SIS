<?php
include('/var/www/html/core/connection.php');

// Función para hacer una solicitud GET al servidor FHIR
function fetchPatientData($url, $token) {
    // Iniciar cURL
    $ch = curl_init();

    // Establecer la URL y las opciones de cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // Devolver la respuesta como string
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/fhir+json',  // Encabezado para aceptar FHIR JSON
        'Authorization: Bearer ' . $token, // Encabezado para autenticación Bearer,
        'traceparent: 00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-00',
        'Accept-Encoding: text/plain'
    ));
    // no verificamos los certificados SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


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
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : '';
$identifier = isset($_GET['identifier']) ? $_GET['identifier'] : '';
$given = isset($_GET['given']) ? $_GET['given'] : '';
$family = isset($_GET['family']) ? $_GET['family'] : '';
$active = isset($_GET['active']) ? $_GET['active'] : '';
$country = isset($_GET['country']) ? TRIM($_GET['country']) : '';

$gender = isset($_GET['gender']) ? $_GET['gender'] : '';
$birthdate = isset($_GET['birthdate']) ? $_GET['birthdate'] : '';

// Construye la URL de la solicitud con parámetros
$params = array();
if ($patient_id) {
    $params['_id'] = $patient_id;
}
if ($identifier) {
    $params['identifier'] = $identifier;
}
if ($given) {
    $params['given'] = $given;
}
if ($family) {
    $params['family'] = $family;
}
if ($active) {
    $params['active'] = $active;
}
if ($country) {
    $params['address-country'] = $country;
}
if ($gender) {
    $params['gender'] = $gender;
}

if ($birthdate) {
    $params['birthdate'] = $birthdate;
}

// Construye la URL final
$url = Endpoint;
if (!empty($params)) {
    $url .= '?' . http_build_query($params);
}

// Llamar a la función para obtener los datos
$token = TOKEN;
$data = fetchPatientData($url, $token);

if ($data) {
    // Devolver la respuesta al frontend
    echo $data;
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener los datos del paciente.']);
}
?>
