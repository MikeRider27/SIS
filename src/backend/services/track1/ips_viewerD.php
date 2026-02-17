<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=UTF-8');

require_once('/var/www/html/core/connection.php');
$dbconnFHIR = getConnectionFHIR();

$identifier = isset($_GET['identifier']) ? trim($_GET['identifier']) : '';

if (!$identifier) {
    echo json_encode(["status" => "error", "message" => "Falta el parámetro patient.identifier"]);
    exit;
}

// 🔹 Obtener el servidor FHIR activo
$sql = "SELECT endpoint_url 
        FROM fhir_server_endpoint 
        WHERE endpoint_version = 'R4' 
          AND endpoint_activo = TRUE 
        LIMIT 1;";
$stmt = $dbconnFHIR->prepare($sql);
$stmt->execute();
$terminology_server = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$terminology_server || empty($terminology_server['endpoint_url'])) {
    echo json_encode(["status" => "error", "message" => "No se encontró un servidor FHIR activo en la base de datos."]);
    exit;
}

$baseUrl = rtrim($terminology_server['endpoint_url'], '/') . '/fhir';

// Configurar timeout para las llamadas
$context = stream_context_create([
    'http' => [
        'timeout' => 10 // segundos
    ]
]);

// 🔹 1. Buscar los DocumentReference asociados
$docUrl = "$baseUrl/DocumentReference?patient.identifier=" . urlencode($identifier) . "&status=current&_format=json";
$docResponse = @file_get_contents($docUrl, false, $context);
if ($docResponse === false) {
    echo json_encode(["status" => "error", "message" => "No se pudo obtener DocumentReference del servidor FHIR."]);
    exit;
}

$bundle = json_decode($docResponse, true);
if (!isset($bundle['entry'])) {
    echo json_encode(["status" => "error", "message" => "Sin resultados para el identificador especificado."]);
    exit;
}

$results = [];

foreach ($bundle['entry'] as $entry) {
    $res = $entry['resource'] ?? null;
    if (!$res) continue;

    $patientRef = $res['subject']['reference'] ?? null;
    $bundleUrl = $res['content'][0]['attachment']['url'] ?? null;
    $bundleId = $bundleUrl ? basename($bundleUrl) : 'N/A';

    // 🔹 Obtener fecha: primero "date", si no existe usar "meta.lastUpdated"
    $fechaRegistro = $res['date'] ?? ($res['meta']['lastUpdated'] ?? 'No disponible');
    if ($fechaRegistro !== 'No disponible') {
        $timestamp = strtotime($fechaRegistro);
        if ($timestamp) {
            $fechaRegistro = date('d/m/Y H:i', $timestamp);
        }
    }

    // 🔹 Obtener datos del paciente
    $nombre = "N/A";
    $documento = "N/A";

    if ($patientRef) {
        $patientUrl = $baseUrl . '/' . $patientRef . "?_format=json";
        $patientResponse = @file_get_contents($patientUrl, false, $context);

        if ($patientResponse !== false) {
            $patientData = json_decode($patientResponse, true);
            if ($patientData) {
                if (isset($patientData['name'][0])) {
                    $n = $patientData['name'][0];
                    $nombre = isset($n['text'])
                        ? $n['text']
                        : trim(($n['given'][0] ?? '') . ' ' . ($n['family'] ?? ''));
                }
                if (isset($patientData['identifier'][0]['value'])) {
                    $documento = $patientData['identifier'][0]['value'];
                }
            }
        }
    }

    $results[] = [
        "documento" => $documento,
        "nombre" => $nombre,
        "bundle" => $bundleId,
        "fecha_registro" => $fechaRegistro
    ];
}

// 🔹 Ordenar por fecha descendente
usort($results, function($a, $b) {
    return strtotime($b['fecha_registro']) <=> strtotime($a['fecha_registro']);
});

// 🔹 3. Respuesta final
echo json_encode([
    "status" => "success",
    "total" => count($results),
    "data" => $results
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
