<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('/var/www/html/core/connection.php');
$dbconn = getConnectionFHIR();

if (isset($_SESSION['idUsuario'])) {

  if (isset($_POST['accion']) && $_POST['accion'] === "VacunacionList") {

    // Parámetros del formulario
    $type = isset($_POST['type']) ? $_POST['type'] : 'name'; // 'code' o 'name'
    $term = isset($_POST['term']) ? $_POST['term'] : '';

    // Condición y formato de búsqueda
    if ($type === 'code') {
      $where = 'TRIM(local_code) = :term';
    } else {
      $where = 'UPPER(local_term) LIKE :term';
      $term = '%' . strtoupper($term) . '%';
    }

    try {
      $sql = "SELECT local_code AS codigo, local_term AS nombre
              FROM vacunas
              WHERE $where";

      $stmt = $dbconn->prepare($sql);
      $stmt->bindParam(':term', $term);
      $stmt->execute();

      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

      print json_encode($data);

    } catch (Exception $e) {
      print json_encode([
        "status" => "error",
        "message" => "Error: " . $e->getMessage()
      ]);
    }

  } else {
    print json_encode(["status" => "error", "message" => "Acción no válida o formulario no enviado"]);
  }

} else {
  print json_encode(["status" => "error", "message" => "No autorizado"]);
}
?>
