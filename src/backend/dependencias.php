<?php
session_start();
include('../core/connection.php');
$dbconn = getConnection();

/*
* Add a new Event
*/

// Check if user is logged
if ($_SESSION['idUsuario']) {


  // Check if the form was sent and the action is SAVE
  if (isset($_POST['accion']) and $_POST['accion'] == "agregar") {
    // var_dump($_POST);
    $result = 0;
    $descripcion = mb_strtoupper(trim($_POST['descripcion']), 'UTF-8');
    $acronimo = empty($_POST['acronimo']) ? NULL : mb_strtoupper(trim($_POST['acronimo']), 'UTF-8') ; 

    try {
      // start transaction
      $dbconn->beginTransaction();
      // prepare statement for insert
      $sql = 'INSERT INTO public.dependencia (dependencia_descripcion, dependencia_acronimo)
              VALUES(:descripcion, :acronimo);';
      $stmt = $dbconn->prepare($sql);

      // pass values to the statement
      $stmt->bindValue(':descripcion', $descripcion);
      $stmt->bindValue(':acronimo', $acronimo);     
      // execute the insert statement
      $result = $stmt->execute();
      $lastId = $dbconn->lastInsertId();

      // commit transaction
      $dbconn->commit();
    } catch (Exception $e) {
      $result = FALSE;
      // rollback transaction
      $dbconn->rollBack();
      var_dump($e->getMessage());
    }

    $message = $result ? "Se registró correctamente el Salon." : "Ocurrio un error intentado resolver la solicitud";

    $status = $result ? "success" : "error";
    print json_encode(array("status" => $status, "message" => $message));
  } else // FORM NOT SENT
  {
    print json_encode(array("status" => "error", "message" => "Formulario no enviado"));
  }
} else // NOT LOGGED
{
  print json_encode(array("status" => "error", "message" => "No autorizado"));
}
