<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('../core/connection.php');
$dbconn = getConnectionFHIR();

// Check if user is logged in
if (isset($_SESSION['idUsuario'])) {

  // Check if the request is for fetching CIE10 codes
  if (isset($_POST['accion']) && $_POST['accion'] == "MedicamentosList") {

    // Get the search term (can be code or name)
    $term = isset($_POST['term']) ? '%' . strtoupper($_POST['term']) . '%' : '';

    try {
      // Prepare the SQL statement
      // Concatenate codcie10a and codcie10b when codcie10b is not null or empty
      $sql = "SELECT UPPER(local_code) AS codigo, UPPER(local_term) AS nombre FROM medicacion 
      WHERE UPPER(local_code) LIKE :term OR UPPER(local_term) LIKE :term;";

      $stmt = $dbconn->prepare($sql);
      $stmt->bindParam(':term', $term);

      // Execute the statement
      $stmt->execute();
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Return the data in JSON format
      print json_encode($data);
    } catch (Exception $e) {
      // Handle exception by rolling back transaction and returning error
      print json_encode(array("status" => "error", "message" => "Error: " . $e->getMessage()));
    }
  } else {
    // Incorrect or missing action
    print json_encode(array("status" => "error", "message" => "Acción no válida o formulario no enviado"));
  }
} else {
  // User not logged in
  print json_encode(array("status" => "error", "message" => "No autorizado"));
}
