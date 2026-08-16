<?php
header('Content-Type: application/json');

include('/var/www/html/core/connection.php');
require_once('/var/www/html/core/FhirClient.php');

// Traer pacientes ordenados por fecha
$url = APP_FHIR_SERVER . "/Patient?_count=50&_sort=-_lastUpdated";
$result = sendFHIRRequest($url, null, 'GET');

if ($result['status'] !== 200) {
    echo json_encode(['status'=>'error','message'=>'No se pudo obtener pacientes']);
    exit;
}

$data = json_decode($result['body'], true);
$pacientes = [];

if (isset($data['entry'])) {
    foreach ($data['entry'] as $entry) {
        $r = $entry['resource'];
        $pacientes[] = [
            'id' => $r['id'],
            'cedula' => $r['identifier'][0]['value'] ?? '',
            'nombre' => ($r['name'][0]['given'][0] ?? '') .' '.($r['name'][0]['given'][1] ?? ''). ' ' . ($r['name'][0]['family'] ?? ''),
            'lastUpdated' => $r['meta']['lastUpdated'] ?? '',
            'raw' => $r
        ];
    }
}

echo json_encode(['status'=>'success','pacientes'=>$pacientes]);
