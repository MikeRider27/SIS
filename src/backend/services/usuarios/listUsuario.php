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

$stmt = $dbconn->query(
    "SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.is_active, u.role_id, r.name AS role_name
     FROM users u
     INNER JOIN roles r ON u.role_id = r.id
     ORDER BY u.created_at DESC"
);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$permStmt = $dbconn->prepare(
    "SELECT p.code FROM user_permissions up INNER JOIN permissions p ON p.id = up.permission_id WHERE up.user_id = :id"
);

$usuarios = array_map(function ($u) use ($permStmt) {
    $permStmt->execute([':id' => $u['id']]);

    return [
        'id' => (int)$u['id'],
        'username' => $u['username'],
        'nombre' => trim($u['first_name'] . ' ' . $u['last_name']),
        'email' => $u['email'],
        'rol' => $u['role_name'],
        'role_id' => (int)$u['role_id'],
        'estado' => $u['is_active'] ? 'Activo' : 'Inactivo',
        'is_active' => (bool)$u['is_active'],
        'permisos' => $permStmt->fetchAll(PDO::FETCH_COLUMN),
    ];
}, $rows);

echo json_encode(['status' => 'success', 'usuarios' => $usuarios]);
