<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

include('/var/www/html/core/connection.php');
require_once('/var/www/html/core/Auth.php');
require_once('/var/www/html/core/FhirClient.php');

requireLogin();

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($id === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Falta el ID del Bundle']);
    exit;
}

$url = APP_FHIR_SERVER . "/Bundle/" . urlencode($id);
$result = sendFHIRRequest($url, null, 'GET');

if ($result['status'] !== 200) {
    http_response_code($result['status'] === 404 ? 404 : 502);
    echo json_encode(['status' => 'error', 'message' => 'No se pudo obtener el Bundle solicitado']);
    exit;
}

echo $result['body'];
