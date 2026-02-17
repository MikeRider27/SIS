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
    $region = $_POST['region'];
    // Extraer las partes de la región
    $codigo_region = substr($region, 0, 2);
    $subregion = substr($region, 2);

    $codigo_distrito = $_POST['distrito'];
    $codigo_establecimiento = $_POST['establecimiento'];
    $tipo_consulta = $_POST['tipo_consulta'];
    $profesion = explode('-', $_POST['profesionales']);
    $codigo_profesional = $profesion[0];
    $especialidades = $_POST['especialidades'];
    $codigo_usuario = $_SESSION['idUsuario'];
    $estado = true;

    



    try {
      // start transaction
      $dbconn->beginTransaction();
      // prepare statement for insert
      $sql = 'INSERT INTO header (codigo_region, subregion, codigo_distrito, codigo_establecimiento, tipo_consulta, codigo_profesional, especialidad, codigo_usuario, estado)
              VALUES (:codigo_region, :subregion, :codigo_distrito, :codigo_establecimiento, :tipo_consulta, :codigo_profesional, :especialidad, :codigo_usuario, :estado);';
      $stmt = $dbconn->prepare($sql);

      // pass values to the statement
      $stmt->bindValue(':codigo_region', $codigo_region);
      $stmt->bindValue(':subregion', $subregion);
      $stmt->bindValue(':codigo_distrito', $codigo_distrito);
      $stmt->bindValue(':codigo_establecimiento', $codigo_establecimiento);
      $stmt->bindValue(':tipo_consulta', $tipo_consulta);
      $stmt->bindValue(':codigo_profesional', $codigo_profesional);
      $stmt->bindValue(':especialidad', $especialidad);
      $stmt->bindValue(':codigo_usuario', $codigo_usuario);
      $stmt->bindValue(':estado', $estado);

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

    $message = $result ? "Se registró correctamente el Salon." : "Ocurrio un error intentado resolver la solicitud1";

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
