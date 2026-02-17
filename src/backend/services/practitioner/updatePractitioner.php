<?php
header('Content-Type: application/json');

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Falta ID de Practitioner']);
    exit;
}

$rawData = file_get_contents("php://input");
if (!$rawData) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibió JSON']);
    exit;
}

$url = "https://fhir-conectaton.mspbs.gov.py/fhir/Practitioner/" . urlencode($id);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/fhir+json',
    'Accept: application/fhir+json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status >= 200 && $status < 300) {
    echo json_encode(['status' => 'success', 'message' => 'Practitioner actualizado', 'fhir_response' => json_decode($response, true)]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar', 'details' => json_decode($response, true)]);
}
