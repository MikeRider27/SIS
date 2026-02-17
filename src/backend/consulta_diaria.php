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
  if (isset($_POST['accion']) and $_POST['accion'] == "header") {
    // var_dump($_POST);
    $result = 0;
    $titulo = mb_strtoupper(trim($_POST['titulo']), 'UTF-8');
    $detalle = mb_strtoupper(trim($_POST['detalle']), 'UTF-8');
    $color = $_POST['color'];
    $usuario = $_SESSION['idUsuario'];
    $estado = 1;
    $salon = $_POST['salon'];
    $motivo = $_POST['motivo'];
    $fecha = date('Y-m-d H:i:s');
    // Dividir la cadena de fecha y hora en partes
    $inicio_array = explode(" ", $_POST['inicio']);

    // Separar la parte de la fecha en día, mes y año
    $fecha_array = explode("/", $inicio_array[0]);
    $dia = $fecha_array[0];
    $mes = $fecha_array[1];
    $año = $fecha_array[2];

    // Separar la parte de la hora en hora y minutos
    $horainicio = $_POST['iniciohr'];


    // Conformamos la fecha de inicio
    $inicio = $año . "-" . $mes . "-" . $dia . " " . $horainicio . ":00";

    // Dividir la cadena de fecha y hora en partes
    $fin_array = explode(" ", $_POST['fin']);

    // Separar la parte de la fecha en día, mes y año
    $fecha_array1 = explode("/", $fin_array[0]);
    $dia1 = $fecha_array1[0];
    $mes1 = $fecha_array1[1];
    $año1 = $fecha_array1[2];

    // Separar la parte de la hora en hora y minutos
    $horafin = $_POST['finhr'];

    // Conformamos la fecha de fin
    $fin = $año1 . "-" . $mes1 . "-" . $dia1 . " " . $horafin . ":00";



    try {
      // start transaction
      $dbconn->beginTransaction();

      // Check if there's an overlapping event in the same room
      $sqlCheck = 'SELECT COUNT(*) FROM eventos WHERE salon_id = :salon AND (evento_inicio < :fin AND evento_fin > :inicio)';
      $stmtCheck = $dbconn->prepare($sqlCheck);
      $stmtCheck->bindValue(':salon', $salon);
      $stmtCheck->bindValue(':inicio', $inicio);
      $stmtCheck->bindValue(':fin', $fin);
      $stmtCheck->execute();
      $count = $stmtCheck->fetchColumn();

      if ($count > 0) {
        print json_encode(array("status" => "error", "message" => "Ya existe un evento en el mismo salón en el rango de tiempo especificado"));
        exit();
      }

      // prepare statement for insert
      $sql = 'INSERT INTO eventos(evento_titulo, evento_detalle, evento_color, evento_inicio, evento_fin, usuario_id, estado_id, salon_id, evento_fecha_creacion, motivo_id)
        VALUES (:titulo, :detalle, :color, :inicio, :fin, :usuario, :estado, :salon, :fecha_creacion, :motivo);';
      $stmt = $dbconn->prepare($sql);

      // pass values to the statement
      $stmt->bindValue(':titulo', $titulo);
      $stmt->bindValue(':detalle', $detalle);
      $stmt->bindValue(':color', $color);
      $stmt->bindValue(':inicio', $inicio);
      $stmt->bindValue(':fin', $fin);
      $stmt->bindValue(':usuario', $usuario);
      $stmt->bindValue(':estado', $estado);
      $stmt->bindValue(':salon', $salon);
      $stmt->bindValue(':fecha_creacion', $fecha);
      $stmt->bindValue(':motivo', $motivo);

      // execute the insert statement
      $result = $stmt->execute();
      $lastId = $dbconn->lastInsertId();

      $code = md5($lastId);

      $sql2 = 'UPDATE public.eventos SET evento_code= :code WHERE evento_id = :id;';
      $stmt2 = $dbconn->prepare($sql2);
      $stmt2->bindValue(':code', $code);
      $stmt2->bindValue(':id', $lastId);
      $result2 = $stmt2->execute();

      // commit transaction
      $dbconn->commit();
    } catch (Exception $e) {
      $result = FALSE;
      // rollback transaction
      $dbconn->rollBack();
      var_dump($e->getMessage());
    }

    $message = $result ? "Se registró correctamente el evento." : "Ocurrio un error intentado resolver la solicitud";

    $status = $result ? "success" : "error";
    print json_encode(array("status" => $status, "message" => $message));
  } else if (isset($_POST['accion']) and $_POST['accion'] == "modificar") {

    $id = $_POST['id'];
    $titulo = mb_strtoupper(trim($_POST['title']), 'UTF-8');
    $detalle = mb_strtoupper(trim($_POST['detail']), 'UTF-8');
    $color = $_POST['colo'];
    $usuario = $_SESSION['idUsuario'];
    $estado = empty($_POST['anular']) ? 1 : 2;
    $salon = $_POST['lounge'];
    $fecha = date('Y-m-d H:i:s');
    // Dividir la cadena de fecha y hora en partes
    $inicio_array = explode(" ", $_POST['iniciom']);

    // Separar la parte de la fecha en día, mes y año
    $fecha_array = explode("/", $inicio_array[0]);
    $dia = $fecha_array[0];
    $mes = $fecha_array[1];
    $año = $fecha_array[2];

    // Separar la parte de la hora en hora y minutos
    $hora_array = explode(":", $inicio_array[1]);
    $hora = $hora_array[0];
    $minutos = $hora_array[1];

    // Conformamos la fecha de inicio
    $inicio = $año . "-" . $mes . "-" . $dia . " " . $hora . ":" . $minutos . ":00";

    // Dividir la cadena de fecha y hora en partes
    $fin_array = explode(" ", $_POST['finm']);

    // Separar la parte de la fecha en día, mes y año
    $fecha_array1 = explode("/", $fin_array[0]);
    $dia1 = $fecha_array1[0];
    $mes1 = $fecha_array1[1];
    $año1 = $fecha_array1[2];

    // Separar la parte de la hora en hora y minutos
    $hora_array1 = explode(":", $fin_array[1]);
    $hora1 = $hora_array1[0];
    $minutos1 = $hora_array1[1];

    // Conformamos la fecha de fin
    $fin = $año1 . "-" . $mes1 . "-" . $dia1 . " " . $hora1 . ":" . $minutos1 . ":00";





    try {
      // start transaction
      $dbconn->beginTransaction();
      // prepare statement for insert
      $sql = 'UPDATE public.eventos
      SET evento_titulo=:titulo, evento_detalle=:detalle, evento_color=:color, evento_inicio=:inicio, evento_fin=:fin, usuario_id=:usuario, estado_id=:estado, salon_id=:salon, evento_fecha_modificacion=:fecha_modificacion
      WHERE evento_id = :id';
      $stmt = $dbconn->prepare($sql);

      // pass values to the statement
      $stmt->bindValue(':titulo', $titulo);
      $stmt->bindValue(':detalle', $detalle);
      $stmt->bindValue(':color', $color);
      $stmt->bindValue(':inicio', $inicio);
      $stmt->bindValue(':fin', $fin);
      $stmt->bindValue(':usuario', $usuario);
      $stmt->bindValue(':estado', $estado);
      $stmt->bindValue(':salon', $salon);
      $stmt->bindValue(':fecha_modificacion', $fecha);
      $stmt->bindValue(':id', $id);

      // execute the insert statement
      $result = $stmt->execute();


      // commit transaction
      $dbconn->commit();
    } catch (Exception $e) {
      $result = FALSE;
      // rollback transaction
      $dbconn->rollBack();
      var_dump($e->getMessage());
    }

    $messa = $estado == 1 ? "Se actualizo correctamente el evento." : "Se anulo correctamente el evento.";

    $message = $result ? $messa : "Ocurrio un error intentado resolver la solicitud";

    $status = $result ? "success" : "error";
    print json_encode(array("status" => $status, "message" => $message, "estado" => $estado));
  } else // FORM NOT SENT
  {
    print json_encode(array("status" => "error", "message" => "Formulario no enviado"));
  }
} else // NOT LOGGED
{
  print json_encode(array("status" => "error", "message" => "No autorizado"));
}
