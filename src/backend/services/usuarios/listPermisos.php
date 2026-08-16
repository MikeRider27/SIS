<?php
session_start();
header('Content-Type: application/json');

include('/var/www/html/core/connection.php');
require_once('/var/www/html/core/Auth.php');

requirePermission('usuarios');

$dbconn = getConnection();
if (!$dbconn) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'No se pudo conectar a la base de datos local']);
    exit;
}

$stmt = $dbconn->query("SELECT code, label FROM permissions ORDER BY label");
echo json_encode(['status' => 'success', 'permisos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
