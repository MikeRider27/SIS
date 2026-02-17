<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('/var/www/html/core/connection.php');
$dbconnFHIR = getConnectionFHIR();

// 1️⃣ Obtener endpoint activo
$sql = "SELECT endpoint_url 
        FROM fhir_server_endpoint 
        WHERE endpoint_version = 'R11' 
          AND endpoint_activo = TRUE 
        LIMIT 1;";
$stmt = $dbconnFHIR->prepare($sql);
$stmt->execute();
$terminology_server = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$terminology_server) {
    http_response_code(500);
    echo json_encode(['error' => 'No se encontró un endpoint FHIR activo.']);
    exit;
}

// 2️⃣ Función GET genérica
function fetchFHIR($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Accept: application/fhir+json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 30
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) return ['error' => "Error de cURL: $error"];
    if ($code !== 200) return ['error' => "HTTP $code recibido del servidor FHIR."];
    return json_decode($response, true);
}

// 3️⃣ Cabeceras CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// 4️⃣ Parámetro
$identifier = isset($_GET['identifier']) ? trim($_GET['identifier']) : '';
if (empty($identifier)) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el parámetro identifier']);
    exit;
}

// 5️⃣ URL base
$baseUrl = rtrim($terminology_server['endpoint_url'], '/');
$url = "$baseUrl/fhir/DocumentReference?patient.identifier=$identifier&_format=json&status=current";

// 6️⃣ Obtener bundle principal
$bundle = fetchFHIR($url);
if (isset($bundle['error'])) {
    echo json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// 🔹 Diccionario cache para no repetir llamados de pacientes
$patientCache = [];
$finalResponse = [];

// 🔹 Función para obtener datos del paciente
function getPatientData($server, $patientRef) {
    static $cache = [];

    $key = $server . '|' . $patientRef;
    if (isset($cache[$key])) return $cache[$key];

    $patientId = str_replace('Patient/', '', $patientRef);
    $url = rtrim($server, '/') . "/Patient/" . $patientId . "?_format=json";
    $data = fetchFHIR($url);

    if (isset($data['error'])) {
        $cache[$key] = [
            'name' => "No disponible",
            'gender' => "No disponible",
            'birthDate' => "No disponible"
        ];
        return $cache[$key];
    }

    // si viene bundle (ej. search result)
    if (isset($data['resourceType']) && $data['resourceType'] === 'Bundle' && isset($data['entry'][0]['resource'])) {
        $data = $data['entry'][0]['resource'];
    }

    $name = "No disponible";
    if (isset($data['name'][0]['text'])) {
        $name = trim($data['name'][0]['text']);
    } elseif (isset($data['name'][0]['given']) || isset($data['name'][0]['family'])) {
        $given = isset($data['name'][0]['given']) ? implode(' ', $data['name'][0]['given']) : '';
        $family = $data['name'][0]['family'] ?? '';
        $name = trim("$given $family");
    }

    $cache[$key] = [
        'name' => $name,
        'gender' => $data['gender'] ?? "No disponible",
        'birthDate' => $data['birthDate'] ?? "No disponible"
    ];
    return $cache[$key];
}

// 7️⃣ Procesar todos los entries
if (isset($bundle['entry']) && is_array($bundle['entry'])) {
    foreach ($bundle['entry'] as $item) {
        if (!isset($item['resource']['resourceType']) || $item['resource']['resourceType'] !== 'DocumentReference') continue;

        $res = $item['resource'];
        $fullUrl = $item['fullUrl'] ?? '';

        // 🧩 detectar base del servidor (ej: http://186.179.201.48:8080/fhir)
        $server = preg_replace('#^(https?://[^/]+/fhir)/.*$#', '$1', $fullUrl);

        $docId = $res['id'] ?? null;
        $patientRef = $res['subject']['reference'] ?? null;
        $bundleUrl = $res['content'][0]['attachment']['url'] ?? null;
        $bundleNum = $bundleUrl ? basename($bundleUrl) : null;
        $lastUpdated = $res['meta']['lastUpdated'] ?? "No disponible";

        if ($docId && $bundleNum && $server && $patientRef) {
            $patientData = getPatientData($server, $patientRef);
            $finalResponse[] = [
                "DocumentReference" => $docId,
                "server" => $server . '/',
                "bundle" => $bundleNum,
                "patient" => $patientRef,
                "lastUpdated" => $lastUpdated,
                "patient_name" => $patientData['name'],
                "gender" => $patientData['gender'],
                "birthDate" => $patientData['birthDate']
            ];
        }
    }
}

// 8️⃣ Enviar salida final
if (empty($finalResponse)) {
    http_response_code(204);
    echo json_encode([]);
    exit;
}

echo json_encode($finalResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
