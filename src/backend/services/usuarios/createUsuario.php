<?php
session_start();
header('Content-Type: application/json');

include('/var/www/html/core/connection.php');
require_once('/var/www/html/core/Auth.php');

requirePermission('usuarios');

$username   = strtoupper(trim($_POST['username'] ?? ''));
$email      = trim($_POST['email'] ?? '');
$first_name = mb_strtoupper(trim($_POST['first_name'] ?? ''), 'UTF-8');
$last_name  = mb_strtoupper(trim($_POST['last_name'] ?? ''), 'UTF-8');
$password   = trim($_POST['password'] ?? '');
$role_id    = $_POST['role_id'] ?? null;
$permisos   = isset($_POST['permisos']) && is_array($_POST['permisos']) ? $_POST['permisos'] : [];

if (!$username || !$email || !$first_name || !$last_name || !$password || !$role_id) {
    echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'El correo electrónico no es válido']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['status' => 'error', 'message' => 'La contraseña debe tener al menos 8 caracteres']);
    exit;
}

$dbconn = getConnection();
if (!$dbconn) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'No se pudo conectar a la base de datos local']);
    exit;
}

try {
    $check = $dbconn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $check->bindValue(':username', $username, PDO::PARAM_STR);
    $check->bindValue(':email', $email, PDO::PARAM_STR);
    $check->execute();

    if ($check->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Ya existe un usuario con ese nombre de usuario o correo']);
        exit;
    }

    $roleCheck = $dbconn->prepare("SELECT id FROM roles WHERE id = :id");
    $roleCheck->bindValue(':id', $role_id, PDO::PARAM_INT);
    $roleCheck->execute();
    if (!$roleCheck->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'El rol seleccionado no existe']);
        exit;
    }

    $dbconn->beginTransaction();

    $stmt = $dbconn->prepare(
        "INSERT INTO users (username, email, password_hash, first_name, last_name, role_id, is_active)
         VALUES (:username, :email, :password_hash, :first_name, :last_name, :role_id, TRUE)"
    );
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->bindValue(':password_hash', password_hash($password, PASSWORD_BCRYPT), PDO::PARAM_STR);
    $stmt->bindValue(':first_name', $first_name, PDO::PARAM_STR);
    $stmt->bindValue(':last_name', $last_name, PDO::PARAM_STR);
    $stmt->bindValue(':role_id', $role_id, PDO::PARAM_INT);
    $stmt->execute();
    $newUserId = $dbconn->lastInsertId();

    if (!empty($permisos)) {
        $permStmt = $dbconn->prepare(
            "INSERT INTO user_permissions (user_id, permission_id)
             SELECT :user_id, id FROM permissions WHERE code = :code"
        );
        foreach ($permisos as $code) {
            $permStmt->execute([':user_id' => $newUserId, ':code' => (string)$code]);
        }
    }

    $dbconn->commit();

    echo json_encode(['status' => 'success', 'message' => 'Usuario creado correctamente', 'id' => $newUserId]);
} catch (PDOException $e) {
    if ($dbconn->inTransaction()) {
        $dbconn->rollBack();
    }
    error_log('[usuarios] createUsuario: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error al crear el usuario']);
}
