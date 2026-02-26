<?php
header('Content-Type: application/json');

include('/var/www/html/core/connection.php');

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
$url = APP_FHIR_SERVER . "/Organization?_count=50&_sort=-_lastUpdated";
$result = fetchFHIR($url);

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
