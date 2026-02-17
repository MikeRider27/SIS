<?php
session_start();
include('../core/connection.php');
$dbconn = getConnection();

// Check if user is logged
if (isset($_SESSION['idUsuario'])) {
  // Check if the form was sent and the action is SAVE
  if (isset($_POST['accion']) && $_POST['accion'] === "agregar") {
    // Inicialización de variables
    $cedula = trim($_POST['cedula']);
    $nombre = mb_strtoupper(trim($_POST['nombre']), 'UTF-8');
    $apellido = mb_strtoupper(trim($_POST['apellido']), 'UTF-8');
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $genero = $_POST['sexo'] == 2 ? 'M' : 'F';
    $nick = mb_strtoupper(trim($_POST['nick']), 'UTF-8');
    $email = $_POST['email'];
    $pass = sha1($_POST['cedula']);
    $rol = $_POST['rol'];
    $dependencia = $_POST['dependencia'];
    $salones = isset($_POST['salones']) ? $_POST['salones'] : array();
    $estado = 3;

    try {
      // start transaction
      $dbconn->beginTransaction();

      // Check if person already exists
      $sql = "SELECT persona_cedula FROM public.personas WHERE persona_cedula = :cedula";
      $stmt = $dbconn->prepare($sql);
      $stmt->bindParam(':cedula', $cedula);
      $stmt->execute();
      $result = $stmt->fetch();

      if ($result) {
        $status = "error";
        $message = "La persona con cedula $cedula ya está registrada.";
        print json_encode(array("status" => $status, "message" => $message));
        exit();
      }

      // Insert new person
      $sql = 'INSERT INTO public.personas (persona_cedula, persona_nombre, persona_apellido, persona_genero, persona_fechanacimiento) 
                    VALUES (:cedula, :nombre, :apellido, :genero, :fechanacimiento)';
      $stmt = $dbconn->prepare($sql);
      $stmt->bindParam(':cedula', $cedula);
      $stmt->bindParam(':nombre', $nombre);
      $stmt->bindParam(':apellido', $apellido);
      $stmt->bindParam(':genero', $genero);
      $stmt->bindParam(':fechanacimiento', $fecha_nacimiento);

      $stmt->execute();
      $personaId = $dbconn->lastInsertId();

      // Insert user
      $sql = 'INSERT INTO public.usuarios (persona_id, usuario_nick, usuario_email, usuario_password, rol_id, estado_id, dependencia_id)
                    VALUES (:persona, :nick, :email, :password, :rol, :estado, :dependencia)';
      $stmt = $dbconn->prepare($sql);
      $stmt->bindParam(':persona', $personaId);
      $stmt->bindParam(':nick', $nick);
      $stmt->bindParam(':email', $email);
      $stmt->bindParam(':password', $pass);
      $stmt->bindParam(':rol', $rol);
      $stmt->bindParam(':estado', $estado);
      $stmt->bindParam(':dependencia', $dependencia);
      $stmt->execute();
      $userId = $dbconn->lastInsertId();

      // Insert salones (if any)
      foreach ($salones as $salonId) {
        $sql = 'INSERT INTO public.usuarios_salon (usuario_id, salon_id) VALUES (:usuario, :salon)';
        $stmt = $dbconn->prepare($sql);
        $stmt->bindParam(':usuario', $userId);
        $stmt->bindParam(':salon', $salonId);
        $stmt->execute();
      }

      // commit transaction
      $dbconn->commit();
      $status = "success";
      $message = "Se registró correctamente.";
    } catch (Exception $e) {
      // rollback transaction
      $dbconn->rollBack();
      $status = "error";
      $message = "Ocurrió un error: " . $e->getMessage();
    }

    print json_encode(array("status" => $status, "message" => $message));
  } else if (isset($_POST['accion']) && $_POST['accion'] === "modificar-pass") {
    // Initialize variables
    $userId = filter_var($_POST['codigo'], FILTER_SANITIZE_NUMBER_INT);
    $raw_password = trim($_POST['new-password']);
    $hashed_password = sha1($raw_password);
    $estado = 1;

    try {
      // Begin transaction
      $dbconn->beginTransaction();

      // Check if person already exists
      $sql2 = "SELECT persona_cedula FROM public.personas WHERE persona_cedula = :cedula";
      $stmt2 = $dbconn->prepare($sql2);
      $stmt2->bindParam(':cedula', $raw_password);
      $stmt2->execute();
      $result = $stmt2->fetch();

      if ($result) {
        $status = "error";
        $message = "La clave no puede ser su numero de cedula $raw_password.";
        print json_encode(array("status" => $status, "message" => $message));
        exit();
      }

      // Update user
      $sql = 'UPDATE public.usuarios SET usuario_password = :password, estado_id = :estado WHERE usuario_id = :id';
      $stmt = $dbconn->prepare($sql);
      $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
      $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
      $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
      $stmt->execute();

      // Commit transaction
      $dbconn->commit();
      $status = "success";
      $message = "Contraseña actualizada correctamente.";
    } catch (Exception $e) {
      // Rollback transaction on error
      $dbconn->rollBack();
      $status = "error";
      $message = "Ocurrió un error: " . $e->getMessage();
    }
    print json_encode(array("status" => $status, "message" => $message));
  } else if (isset($_POST['accion']) && $_POST['accion'] === "reseteo") {
    // Initialize variables
    $userId = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $estado = 4;

    $sql = "SELECT p.persona_cedula FROM public.usuarios u INNER JOIN personas p ON u.persona_id = p.persona_id WHERE u.usuario_id = :id";
    $stmt = $dbconn->prepare($sql);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $hashed_password = sha1($result['persona_cedula']);

    try {
      // Begin transaction
      $dbconn->beginTransaction();

      // Check if person already exists
      $sql2 = "SELECT persona_cedula FROM public.personas WHERE persona_cedula = :cedula";
      $stmt2 = $dbconn->prepare($sql2);
      $stmt2->bindParam(':cedula', $raw_password);
      $stmt2->execute();
      $result = $stmt2->fetch();

      if ($result) {
        $status = "error";
        $message = "La clave no puede ser su numero de cedula $raw_password.";
        print json_encode(array("status" => $status, "message" => $message));
        exit();
      }

      // Update user
      $sql = 'UPDATE public.usuarios SET usuario_password = :password, estado_id = :estado WHERE usuario_id = :id';
      $stmt = $dbconn->prepare($sql);
      $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
      $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
      $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
      $stmt->execute();

      // Commit transaction
      $dbconn->commit();
      $status = "success";
      $message = "Usuario reseteado correctamente.";
    } catch (Exception $e) {
      // Rollback transaction on error
      $dbconn->rollBack();
      $status = "error";
      $message = "Ocurrió un error: " . $e->getMessage();
    }
    print json_encode(array("status" => $status, "message" => $message));
  } else if (isset($_POST['accion']) && $_POST['accion'] === "desactivar") {
    // Initialize variables
    $userId = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $estado = 2;


    try {
      // Begin transaction
      $dbconn->beginTransaction();

      // Update user
      $sql = 'UPDATE public.usuarios SET estado_id = :estado WHERE usuario_id = :id';
      $stmt = $dbconn->prepare($sql);
      $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
      $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
      $stmt->execute();

      // Commit transaction
      $dbconn->commit();
      $status = "success";
      $message = "Usuario Inactivado correctamente.";
    } catch (Exception $e) {
      // Rollback transaction on error
      $dbconn->rollBack();
      $status = "error";
      $message = "Ocurrió un error: " . $e->getMessage();
    }
    print json_encode(array("status" => $status, "message" => $message));
  } else if (isset($_POST['accion']) && $_POST['accion'] === "activar") {
    // Initialize variables
    $userId = $_POST['id'];
    $estado = 1;


    try {
      // Begin transaction
      $dbconn->beginTransaction();

      // Update user
      $sql = 'UPDATE public.usuarios SET estado_id = :estado WHERE usuario_id = :id';
      $stmt = $dbconn->prepare($sql);
      $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
      $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
      $stmt->execute();

      // Commit transaction
      $dbconn->commit();
      $status = "success";
      $message = "Usuario Activado correctamente.";
    } catch (Exception $e) {
      // Rollback transaction on error
      $dbconn->rollBack();
      $status = "error";
      $message = "Ocurrió un error: " . $e->getMessage();
    }
    print json_encode(array("status" => $status, "message" => $message));
  } else if (isset($_POST['accion']) && $_POST['accion'] === "modificar") {
    // Initialize variables
    $userId = filter_var($_POST['codigo'], FILTER_SANITIZE_NUMBER_INT);
    $raw_password = trim($_POST['new-password']);
    $hashed_password = sha1($raw_password);   

    try {
      // Begin transaction
      $dbconn->beginTransaction();

      // Check if person already exists
      $sql2 = "SELECT persona_cedula FROM public.personas WHERE persona_cedula = :cedula";
      $stmt2 = $dbconn->prepare($sql2);
      $stmt2->bindParam(':cedula', $raw_password);
      $stmt2->execute();
      $result = $stmt2->fetch();

      if ($result) {
        $status = "error";
        $message = "La clave no puede ser su numero de cedula $raw_password.";
        print json_encode(array("status" => $status, "message" => $message));
        exit();
      }

      // Update user
      $sql = 'UPDATE public.usuarios SET usuario_password = :password WHERE usuario_id = :id';
      $stmt = $dbconn->prepare($sql);
      $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);    
      $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
      $stmt->execute();

      // Commit transaction
      $dbconn->commit();
      $status = "success";
      $message = "Contraseña actualizada correctamente.";
    } catch (Exception $e) {
      // Rollback transaction on error
      $dbconn->rollBack();
      $status = "error";
      $message = "Ocurrió un error: " . $e->getMessage();
    }
    print json_encode(array("status" => $status, "message" => $message));
  }else {
    print json_encode(array("status" => "error", "message" => "Formulario no enviado"));
  }
} else {
  print json_encode(array("status" => "error", "message" => "No autorizado"));
}
