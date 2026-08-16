<?php
header('Content-Type: application/json');

include('/var/www/html/core/connection.php');
require_once('/var/www/html/core/FhirClient.php');

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Falta ID de Practitioner']);
    exit;
}

$rawData = file_get_contents("php://input");
$practitionerResource = json_decode($rawData, true);
if (!$practitionerResource) {
    echo json_encode(['status' => 'error', 'message' => 'JSON requerido o inválido']);
    exit;
}

$url = APP_FHIR_SERVER . "/Practitioner/" . urlencode($id);
$update = sendFHIRRequest($url, $practitionerResource, 'PUT');
$fhir_response = json_decode($update['body'], true);

if ($update['status'] < 200 || $update['status'] >= 300) {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar', 'details' => $fhir_response]);
    exit;
}

// Mantener sincronizada la base local con los datos actualizados en FHIR
$dbconn = getConnection();
requireDbConnection($dbconn);

$name = $practitionerResource['name'][0] ?? [];
$apellidos = explode(' ', trim($name['family'] ?? ''), 2);
$given = $name['given'] ?? [];

$stmt = $dbconn->prepare(
    "UPDATE professional SET first_name = :pnombre, middle_name = :snombre, last_name = :papellido, second_last_name = :sapellido WHERE code = :code"
);
$stmt->bindValue(':pnombre', mb_strtoupper(trim($given[0] ?? ''), 'UTF-8'), PDO::PARAM_STR);
$stmt->bindValue(':snombre', mb_strtoupper(trim($given[1] ?? ''), 'UTF-8'), PDO::PARAM_STR);
$stmt->bindValue(':papellido', mb_strtoupper($apellidos[0] ?? '', 'UTF-8'), PDO::PARAM_STR);
$stmt->bindValue(':sapellido', mb_strtoupper($apellidos[1] ?? '', 'UTF-8'), PDO::PARAM_STR);
$stmt->bindValue(':code', $id, PDO::PARAM_STR);
$stmt->execute();

echo json_encode([
    'status' => 'success',
    'message' => 'Practitioner actualizado',
    'local_updated' => $stmt->rowCount() > 0,
    'fhir_response' => $fhir_response
]);
