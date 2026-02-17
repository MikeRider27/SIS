<?php
session_start();
include('../core/connection.php');
$dbconn = getConnection();

// Check if user is logged in
if (isset($_SESSION['idUsuario'])) {

  // Check if the request is for fetching CIE10 codes
  if (isset($_POST['accion']) && $_POST['accion'] == "CIE10List") {

    // Get the search term (can be code or name)
    $term = isset($_POST['term']) ? '%' . strtoupper($_POST['term']) . '%' : '';

    try {
      // Prepare the SQL statement
      // Concatenate codcie10a and codcie10b when codcie10b is not null or empty
      $sql = "SELECT (codcie10a||'.'||codcie10b) as codigo, nomcie10 as nombre FROM cie10
              WHERE 
                  UPPER(codcie10a||'.'||codcie10b) LIKE :term OR 
                  UPPER(nomcie10) LIKE :term;";

      $stmt = $dbconn->prepare($sql);
      $stmt->bindParam(':term', $term);

      // Execute the statement
      $stmt->execute();
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Return the data in JSON format
      print json_encode($data);
    } catch (Exception $e) {
      // Handle exception by rolling back transaction and returning error
      $dbconn->rollBack();
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
