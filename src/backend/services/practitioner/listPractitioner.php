<?php
header('Content-Type: application/json');

function fetchFHIR($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/fhir+json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        echo json_encode(['status' => 'error', 'message' => $error]);
        exit;
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => $response];
}

$url = "https://fhir-conectaton.mspbs.gov.py/fhir/Practitioner?_count=50&_sort=-_lastUpdated";
$result = fetchFHIR($url);

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
