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

        // Determine if the input is an email or a username
       
        $user = $input_user;
        $password = sha1($input_password);

        // Prepare SQL statement
        $sql = "SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.role_id, r.name, r.description, u.is_active FROM users u inner join roles r on u.role_id = r.id  
                WHERE u.username = :login AND u.password_hash = :password";

        // Prepare statement for searching user
        $stmt = $dbcon->prepare($sql);
        // Bind values to the parameters
        $stmt->bindValue(':login', $user, PDO::PARAM_STR);
        $stmt->bindValue(':password', $password, PDO::PARAM_STR);
        // Execute the statement
        $stmt->execute();
        // Return the result set as an object
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if ($user !== false) {    // Authorized access
            $_SESSION['idUsuario'] = $user->id;
            $_SESSION['idRol'] = $user->role_id;
            $_SESSION['rol'] = $user->name;
            $_SESSION['email'] = $user->email;
            $_SESSION['nombre'] = $user->first_name . ' ' . $user->last_name;
            $_SESSION['first_name'] = $user->first_name;
            $_SESSION['last_name'] = $user->last_name;
            $_SESSION['is_active'] = $user->is_active;

            if (!$user->is_active) {
                // User is not active
                $response = array('status' => 'error', 'message' => 'Usuario inactivo');
                echo json_encode($response);
                exit;
            }

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