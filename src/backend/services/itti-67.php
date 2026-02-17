<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('/var/www/html/core/connection.php');
$dbconnFHIR = getConnectionFHIR();

// 1️⃣ Obtener el endpoint activo (puedes dejarlo dinámico o fijarlo al de Gazelle)
$sql = "SELECT endpoint_url 
        FROM fhir_server_endpoint 
        WHERE endpoint_version = 'R11' 
          AND endpoint_activo = TRUE 
        LIMIT 1;";
$stmt = $dbconnFHIR->prepare($sql);
$stmt->execute();
$terminology_server = $stmt->fetch(PDO::FETCH_ASSOC);

// 2️⃣ Función genérica GET
function fetchFHIR($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/fhir+json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        return ['error' => "Error de cURL: $error"];
    }
    if ($code !== 200) {
        return ['error' => "HTTP $code recibido del servidor FHIR."];
    }

    return json_decode($response, true);
}

// 3️⃣ CORS y cabeceras
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// 4️⃣ Obtener parámetro
$identifier = isset($_GET['identifier']) ? trim($_GET['identifier']) : '';
if (empty($identifier)) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el parámetro identifier']);
    exit;
}

// 5️⃣ Armar URL y obtener datos
$baseUrl = 'http://lacpass.create.cl:5001'; //rtrim($terminology_server['endpoint_url'], '/');
$url = "$baseUrl/fhir/DocumentReference?patient.identifier=$identifier&_format=json&status=current";

$result = fetchFHIR($url);

// 6️⃣ Responder JSON completo del Bundle
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
