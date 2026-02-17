<?php
header('Content-Type: application/json');

function fetchFHIR($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/fhir+json']);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => $response];
}

// Traer pacientes ordenados por fecha
$url = "https://fhir-conectaton.mspbs.gov.py/fhir/Patient?_count=50&_sort=-_lastUpdated";
$result = fetchFHIR($url);

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
            'nombre' => ($r['name'][0]['given'][0] ?? '') . ' ' . ($r['name'][0]['family'] ?? ''),
            'lastUpdated' => $r['meta']['lastUpdated'] ?? '',
            'raw' => $r
        ];
    }
}

echo json_encode(['status'=>'success','pacientes'=>$pacientes]);
