<?php
session_start();
include(__DIR__ . '/../core/connection.php');
include(__DIR__ . '/helpers/SubirArchivo.php');

$dbconn = getConnection();


// Función para enviar respuestas JSON
function sendJsonResponse($status, $message) {
    print json_encode(array("status" => $status, "message" => $message));
    exit();
}

// Verifica si el usuario está logueado
if (!isset($_SESSION['idUsuario'])) {
    sendJsonResponse("error", "No autorizado");
}

// Verifica si el formulario fue enviado y la acción es agregar
if (!isset($_POST['accion']) || $_POST['accion'] !== "agregar") {
    sendJsonResponse("error", "Formulario no enviado");
}

$personaID = $_POST['code'];
$avatar = $_FILES["avatar"];

try {
    // Inicia la transacción
    $dbconn->beginTransaction();

    $name = md5($personaID . 'avatar');

    // Obtén la imagen actual del usuario
    $sql1 = 'SELECT persona_avatar FROM personas WHERE persona_id = :persona_id';
    $stmt1 = $dbconn->prepare($sql1);
    $stmt1->bindValue(':persona_id', $personaID);
    $stmt1->execute();
    $currentAvatar = $stmt1->fetchColumn();

    // Si existe una imagen actual, elimínala
    if ($currentAvatar) {
        eliminarArchivo($currentAvatar); 
    }

    // Intentamos insertar los archivos
    $result1 = subirArchivo("avatar", $name);
    $nombre_avatar = ($result1['status'] == "success") ? $result1['archivo'] : "";

    // Chequeamos subida de archivos
    if ($result1['status'] == "error") {
        throw new Exception($result1['message']);
    }

    // Actualizamos la tabla personas
    $sql2 = 'UPDATE personas SET persona_avatar = :persona_avatar WHERE persona_id = :persona_id';
    $stmt2 = $dbconn->prepare($sql2);
    $stmt2->bindValue(':persona_avatar', $nombre_avatar);
    $stmt2->bindValue(':persona_id', $personaID);

    $result2 = $stmt2->execute();

    if (!$result2) {
        throw new Exception("Error al actualizar el avatar en la base de datos.");
    }

    // Commit de la transacción
    $dbconn->commit();
    sendJsonResponse("success", "Avatar actualizado");

} catch (Exception $e) {
    // Rollback de la transacción en caso de error
    $dbconn->rollBack();
    sendJsonResponse("error", $e->getMessage());
}
?>
