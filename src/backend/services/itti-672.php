<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

include('/var/www/html/core/connection.php');
require_once('/var/www/html/core/Auth.php');
require_once('/var/www/html/core/FhirClient.php');

requireLogin();

// ===============================
// 1. Validar parámetro
// ===============================
$identifier = isset($_GET['identifier']) ? trim($_GET['identifier']) : '';
if ($identifier === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Falta el identificador del paciente']);
    exit;
}

// ===============================
// 2. Buscar DocumentReference del paciente (ITI-67)
// ===============================
$url = APP_FHIR_SERVER . "/DocumentReference?patient.identifier=" . urlencode($identifier) . "&_format=json&status=current";
$result = sendFHIRRequest($url, null, 'GET');

if ($result['status'] !== 200) {
    http_response_code(502);
    echo json_encode(['status' => 'error', 'message' => 'No se pudo consultar el servidor FHIR']);
    exit;
}

$bundle = json_decode($result['body'], true);
if (!is_array($bundle)) {
    http_response_code(502);
    echo json_encode(['status' => 'error', 'message' => 'Respuesta inválida del servidor FHIR']);
    exit;
}

// ===============================
// 3. Resolver TODAS las organizaciones custodias y los pacientes referenciados
//    (antes solo se resolvía la organización del primer resultado; con
//    documentos de varias organizaciones, el resto quedaba sin nombre)
// ===============================
function collectReferences(array $entries, string $field): array {
    $refs = [];
    foreach ($entries as $entry) {
        $ref = $entry['resource'][$field]['reference'] ?? null;
        if ($ref) {
            $refs[$ref] = true;
        }
    }
    return array_keys($refs);
}

function appendResolvedReferences(array $bundle, array $refs): array {
    foreach ($refs as $ref) {
        $url = APP_FHIR_SERVER . "/" . $ref;
        $result = sendFHIRRequest($url, null, 'GET');

        if ($result['status'] === 200) {
            $resource = json_decode($result['body'], true);
            if ($resource) {
                $bundle['entry'][] = [
                    "fullUrl" => $url,
                    "resource" => $resource
                ];
            }
        }
    }
    return $bundle;
}

$entries = $bundle['entry'] ?? [];
$bundle = appendResolvedReferences($bundle, collectReferences($entries, 'custodian'));
$bundle = appendResolvedReferences($bundle, collectReferences($entries, 'subject'));

echo json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
