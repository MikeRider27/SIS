<?php
header('Content-Type: application/json');

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Falta ID de Practitioner']);
    exit;
}

$url = "https://fhir-conectaton.mspbs.gov.py/fhir/Practitioner/" . urlencode($id);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/fhir+json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status == 200 || $status == 204) {
    echo json_encode(['status' => 'success', 'message' => 'Practitioner eliminado']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al eliminar', 'details' => json_decode($response, true)]);
}
