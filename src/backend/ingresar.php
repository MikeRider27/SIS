<?php
session_start();
include('../core/connection.php');
$dbcon = getConnection();

/*
* LOGIN
*/


// Check if the form was sent and the action is LOGIN
if (isset($_POST['accion'])) {
    if ($_POST['accion'] == "ingresar") {
        // Sanitize input
        $input_user = strtoupper(trim($_POST['user']));
        $input_password = trim($_POST['password']);

        // Prepare SQL statement (la contraseña se verifica en PHP, no en SQL)
        $sql = "SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.role_id, u.password_hash, r.name, r.description, u.is_active FROM users u inner join roles r on u.role_id = r.id
                WHERE u.username = :login";

        // Prepare statement for searching user
        $stmt = $dbcon->prepare($sql);
        // Bind values to the parameters
        $stmt->bindValue(':login', $input_user, PDO::PARAM_STR);
        // Execute the statement
        $stmt->execute();
        // Return the result set as an object
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        $passwordMatches = false;
        if ($user !== false) {
            if (password_verify($input_password, $user->password_hash)) {
                $passwordMatches = true;
            } elseif (hash_equals(sha1($input_password), $user->password_hash)) {
                // Hash heredado (SHA1): validar y migrar a bcrypt de forma transparente
                $passwordMatches = true;
                $newHash = password_hash($input_password, PASSWORD_BCRYPT);
                $upgrade = $dbcon->prepare("UPDATE users SET password_hash = :hash, password_changed_at = CURRENT_TIMESTAMP WHERE id = :id");
                $upgrade->bindValue(':hash', $newHash, PDO::PARAM_STR);
                $upgrade->bindValue(':id', $user->id, PDO::PARAM_INT);
                $upgrade->execute();
            }
        }

        if ($user !== false && $passwordMatches && !$user->is_active) {
            // Credenciales válidas pero cuenta desactivada: no se abre sesión
            $response = array('status' => 'error', 'message' => 'Usuario inactivo');
        } elseif ($user !== false && $passwordMatches) {    // Authorized access
            $_SESSION['idUsuario'] = $user->id;
            $_SESSION['idRol'] = $user->role_id;
            $_SESSION['rol'] = $user->name;
            $_SESSION['email'] = $user->email;
            $_SESSION['nombre'] = $user->first_name . ' ' . $user->last_name;
            $_SESSION['first_name'] = $user->first_name;
            $_SESSION['last_name'] = $user->last_name;
            $_SESSION['is_active'] = $user->is_active;

            // Cargar los permisos del usuario (controlan qué secciones del menú ve)
            $permStmt = $dbcon->prepare(
                "SELECT p.code FROM user_permissions up
                 INNER JOIN permissions p ON p.id = up.permission_id
                 WHERE up.user_id = :id"
            );
            $permStmt->bindValue(':id', $user->id, PDO::PARAM_INT);
            $permStmt->execute();
            $_SESSION['permisos'] = $permStmt->fetchAll(PDO::FETCH_COLUMN);

            $response = array('status' => 'success');
        } else { // User not authorized
            $response = array('status' => 'error', 'message' => 'Usuario no autorizado');
        }
        echo json_encode($response);
        
    } else {
        // Acción no reconocida
        $response = array('status' => 'error', 'message' => 'Acción no válida');
        echo json_encode($response);
    }
} else { // Form not sent
    echo json_encode(array("status" => "error", "message" => "Formulario no enviado"));
}
?>