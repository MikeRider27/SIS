<?php
session_start();
include('../core/connection.php');
$dbcon = getConnection();
$dbconFHIR = getConnectionFHIR();

/*
* LOGIN
*/

// Check if the form was sent and the action is LOGIN
if (isset($_POST['accion'])) {
    if ($_POST['accion'] == "ingresar") {
        // Sanitize input
        $input_user = trim($_POST['user']);
        $input_password = trim($_POST['password']);

        // Determine if the input is an email or a username
       
        $user = $input_user;
        $password = md5($input_password);

        // Prepare SQL statement
        $sql = "SELECT u.codusu, u.nombres, u.apellidos, u.estado, u.codreg, u.subcreg, u.coddist, u.codserv, u.codtusuario, t.nomtusuario
                FROM usuarios u INNER JOIN tiposusuarios t ON u.codtusuario = t.codtusuario  
                WHERE u.codusu = :login AND u.passusu = :password";

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
            $_SESSION['idUsuario'] = $user->codusu;
            $_SESSION['idRol'] = $user->codtusuario;
            $_SESSION['rol'] = $user->nomtusuario;      
            $nombre = explode(" ", $user->nombres);
            $apellido = explode(" ", $user->apellidos);
            $_SESSION['nombre'] = $nombre[0] . ' ' . $apellido[0];
            $_SESSION["region"] = $user->codreg;
            $_SESSION["subregion"] = $user->subcreg;
            $_SESSION['distrito'] = $user->coddist;
            $_SESSION['servicio'] = $user->codserv;
            $_SESSION['es_invitado'] = false; // No es un usuario invitado

            $response = array('status' => 'success');
        } else { // User not authorized
            $response = array('status' => 'error', 'message' => 'Usuario no autorizado');
        }
        echo json_encode($response);
        
    } elseif ($_POST['accion'] == "ingresar_invitado") {
        // Acceso como invitado
        $_SESSION['idUsuario'] = 'invitado_' . uniqid();
        $_SESSION['idRol'] = 99; // Asumiendo que 99 es el código para invitado
        $_SESSION['rol'] = 'Spartan';
        $_SESSION['nombre'] = 'Master Chief';
        $_SESSION["region"] = 0;
        $_SESSION["subregion"] = 0;
        $_SESSION['distrito'] = 0;
        $_SESSION['servicio'] = 0;
        $_SESSION['es_invitado'] = true; // Marcar como usuario invitado
        $fecha_actual = date('Y-m-d H:i:s');
        
        // Registrar acceso de invitado si es necesario
        $sql_log = "INSERT INTO logs_acceso (usuario, tipo_acceso, fecha_acceso, ip_address) 
                    VALUES (:usuario, 'invitado', :fecha_acceso, :ip_address)";
        $stmt_log = $dbconFHIR->prepare($sql_log);
        $stmt_log->bindValue(':usuario', $_SESSION['idUsuario'], PDO::PARAM_STR);
        $stmt_log->bindValue(':fecha_acceso', $fecha_actual, PDO::PARAM_STR);
        $stmt_log->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
        $stmt_log->execute();

        $response = array('status' => 'success', 'message' => 'Acceso como invitado exitoso');
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