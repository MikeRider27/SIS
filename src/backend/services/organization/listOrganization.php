<?php
header('Content-Type: application/json');

include('/var/www/html/core/connection.php');
require_once('/var/www/html/core/FhirClient.php');

// Traer organizaciones ordenadas por fecha
$url = APP_FHIR_SERVER . "/Organization?_count=50&_sort=-_lastUpdated";
$result = sendFHIRRequest($url, null, 'GET');

if ($result['status'] !== 200) {
    echo json_encode(['status'=>'error','message'=>'No se pudo obtener organizaciones']);
    exit;
}

$data = json_decode($result['body'], true);
$orgs = [];

if (isset($data['entry'])) {
    foreach ($data['entry'] as $entry) {
        $r = $entry['resource'];
        $orgs[] = [
            'id' => $r['id'] ?? '',
            'identifier' => $r['identifier'][0]['value'] ?? '',
            'name' => $r['name'] ?? 'SIN NOMBRE',
            'type' => $r['type'][0]['text'] ?? '',
            'lastUpdated' => $r['meta']['lastUpdated'] ?? '',
            'raw' => $r
        ];
    }
}

echo json_encode(['status'=>'success','organizations'=>$orgs]);
