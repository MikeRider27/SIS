<?php
header('Content-Type: application/json');

include('/var/www/html/core/connection.php');
require_once('/var/www/html/core/FhirClient.php');

$url = APP_FHIR_SERVER . "/Practitioner?_count=50&_sort=-_lastUpdated";
$result = sendFHIRRequest($url, null, 'GET');

if ($result['status'] !== 200) {
    echo json_encode(['status' => 'error', 'message' => 'No se pudo obtener profesionales']);
    exit;
}

$data = json_decode($result['body'], true);
$practitioner = [];

if (isset($data['entry'])) {
    foreach ($data['entry'] as $entry) {
        $r = $entry['resource'];
        $nombre = "";
        if (isset($r['name'][0])) {
            $nombre = ($r['name'][0]['given'][0] ?? '') . " " . ($r['name'][0]['family'] ?? '');
        }
        $cedula = $r['identifier'][0]['value'] ?? '';
        $practitioner[] = [
            'id' => $r['id'] ?? '',
            'nombre' => trim($nombre),
            'cedula' => $cedula,
            'lastUpdated' => $r['meta']['lastUpdated'] ?? '',
            'raw' => $r
        ];
    }
}

echo json_encode(['status' => 'success', 'practitioner' => $practitioner], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
