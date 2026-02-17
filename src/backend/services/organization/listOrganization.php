<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

function fetchFHIR($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/fhir+json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        echo json_encode(['status' => 'error', 'message' => $error_msg]);
        exit;
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => $response];
}

// ===============================
// 1. Pedir lista de organizaciones al servidor FHIR
// ===============================
$url = "https://fhir-conectaton.mspbs.gov.py/fhir/Organization?_count=50&_sort=-_lastUpdated";
$result = fetchFHIR($url);

if ($result['status'] !== 200 || !$result['body']) {
    echo json_encode(['status'=>'error','message'=>'No se pudo obtener organizaciones']);
    exit;
}

$data = json_decode($result['body'], true);
$orgs = [];

// ===============================
// 2. Procesar cada Organization
// ===============================
if (isset($data['entry'])) {
    foreach ($data['entry'] as $entry) {
        $r = $entry['resource'];

        // Sacar el código de identifier[0].value
        $codigo = $r['identifier'][0]['value'] ?? '';

        // Sacar el nombre
        $nombre = $r['name'] ?? 'SIN NOMBRE';

        // Sacar lastUpdated (si existe en meta)
        $lastUpdated = $r['meta']['lastUpdated'] ?? '';

        $orgs[] = [
            'id'        => $r['id'] ?? '',
            'codigo'    => $codigo,
            'nombre'    => $nombre,
            'lastUpdated'=> $lastUpdated,
            'raw'       => $r
        ];
    }
}

echo json_encode(['status'=>'success','organizations'=>$orgs], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
