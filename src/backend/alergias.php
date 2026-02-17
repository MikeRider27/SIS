<?php
session_start();
include('../core/connection.php');
$dbconn = getConnectionFHIR();

// Check if user is logged in
if (isset($_SESSION['idUsuario'])) {

  // Check if the request is for fetching allergies
  if (isset($_POST['accion']) && $_POST['accion'] == "AlergiasList") {

    // Get the search term (can be code or name)
    $term = isset($_POST['term']) ? '%' . strtoupper($_POST['term']) . '%' : '';
    $regional = false;

    try { 

      // Prepare the SQL statement with UPPER() to ensure all data is in uppercase
      $sql = 'SELECT UPPER(code) AS codigo, UPPER(alergias) AS nombre, type FROM alergias WHERE regional = :regional AND (UPPER(code) LIKE :term OR UPPER(alergias) LIKE :term)';
      $stmt = $dbconn->prepare($sql);
      $stmt->bindParam(':regional', $regional, PDO::PARAM_BOOL);
      $stmt->bindParam(':term', $term);

      // Execute the statement
      $stmt->execute();
      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Return the data in JSON format
      print json_encode($data);

    } catch (Exception $e) {
      // Rollback transaction in case of error
      $dbconn->rollBack();
      print json_encode(array("status" => "error", "message" => "Error: " . $e->getMessage()));
    }

  } else {
    // No action or incorrect action
    print json_encode(array("status" => "error", "message" => "Acción no válida o formulario no enviado"));
  }

} else {
  // User not logged in
  print json_encode(array("status" => "error", "message" => "No autorizado"));
}
?>
