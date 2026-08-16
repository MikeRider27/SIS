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

$accion = $_POST['accion'] ?? null;
$userId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$accion || !$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos requeridos (id, accion)']);
    exit;
}

try {
    switch ($accion) {
        case 'activar':
        case 'desactivar':
            $stmt = $dbconn->prepare("UPDATE users SET is_active = :activo WHERE id = :id");
            $stmt->bindValue(':activo', $accion === 'activar', PDO::PARAM_BOOL);
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
                exit;
            }

            $message = $accion === 'activar' ? 'Usuario activado correctamente' : 'Usuario desactivado correctamente';
            echo json_encode(['status' => 'success', 'message' => $message]);
            break;

        case 'reseteo':
            $tempPassword = substr(bin2hex(random_bytes(6)), 0, 10);

            $stmt = $dbconn->prepare(
                "UPDATE users SET password_hash = :hash, password_changed_at = CURRENT_TIMESTAMP WHERE id = :id"
            );
            $stmt->bindValue(':hash', password_hash($tempPassword, PASSWORD_BCRYPT), PDO::PARAM_STR);
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
                exit;
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Contraseña reseteada correctamente. Comuníquela al usuario por un canal seguro.',
                'temp_password' => $tempPassword
            ]);
            break;

        case 'cambiar_rol':
            $roleId = filter_var($_POST['role_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$roleId) {
                echo json_encode(['status' => 'error', 'message' => 'Rol inválido']);
                exit;
            }

            $stmt = $dbconn->prepare("UPDATE users SET role_id = :role_id WHERE id = :id");
            $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Usuario o rol no encontrado']);
                exit;
            }

            echo json_encode(['status' => 'success', 'message' => 'Rol actualizado correctamente']);
            break;

        case 'actualizar_permisos':
            $permisos = isset($_POST['permisos']) && is_array($_POST['permisos']) ? $_POST['permisos'] : [];

            $dbconn->beginTransaction();

            $del = $dbconn->prepare("DELETE FROM user_permissions WHERE user_id = :id");
            $del->bindValue(':id', $userId, PDO::PARAM_INT);
            $del->execute();

            if (!empty($permisos)) {
                $ins = $dbconn->prepare(
                    "INSERT INTO user_permissions (user_id, permission_id)
                     SELECT :user_id, id FROM permissions WHERE code = :code"
                );
                foreach ($permisos as $code) {
                    $ins->execute([':user_id' => $userId, ':code' => (string)$code]);
                }
            }

            $dbconn->commit();

            echo json_encode(['status' => 'success', 'message' => 'Permisos actualizados correctamente']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    if ($dbconn->inTransaction()) {
        $dbconn->rollBack();
    }
    error_log('[usuarios] updateUsuario: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el usuario']);
}
