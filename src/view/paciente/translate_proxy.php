<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *'); // Permite acceso desde tu frontend local

// Validar parámetros
$required = ['url', 'system', 'source', 'target', 'code'];
foreach ($required as $p) {
    if (empty($_GET[$p]) && $_GET[$p] !== '0') {
        http_response_code(400);
        echo json_encode(['error' => "Falta parámetro: $p"]);
        exit;
    }
}

// Construir URL destino
$baseUrl = "https://gazelle.racsel.org:11040/fhir/ConceptMap/\$translate";
//$baseUrl = "https://snowstorm.mspbs.gov.py/fhir/ConceptMap/\$translate";
$params = http_build_query([
    'url' => $_GET['url'],
    'system' => $_GET['system'],
    'source' => $_GET['source'],
    'target' => $_GET['target'],
    'code' => $_GET['code']
]);

$fullUrl = "{$baseUrl}?{$params}";

// Realizar petición HTTP desde el servidor
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Accept: application/fhir+json\r\n",
        'timeout' => 30
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$response = @file_get_contents($fullUrl, false, $context);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al contactar con el servidor FHIR']);
    exit;
}

// Devolver la respuesta JSON original del servidor
echo $response;
