<?php
header('Content-Type: application/json');

include('/var/www/html/core/connection.php');
require_once('/var/www/html/core/FhirClient.php');

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Falta ID de Practitioner']);
    exit;
}

$dbconn = getConnection();
requireDbConnection($dbconn);

$url = APP_FHIR_SERVER . "/Practitioner/" . urlencode($id);
$deletion = sendFHIRRequest($url, null, 'DELETE');

if (!in_array($deletion['status'], [200, 204, 404], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Error al eliminar', 'details' => json_decode($deletion['body'], true)]);
    exit;
}

$stmt = $dbconn->prepare("DELETE FROM professional WHERE code = :code");
$stmt->bindValue(':code', $id, PDO::PARAM_STR);
$stmt->execute();

echo json_encode(['status' => 'success', 'message' => 'Practitioner eliminado', 'local_deleted' => $stmt->rowCount() > 0]);
