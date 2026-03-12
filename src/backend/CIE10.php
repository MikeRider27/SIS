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
      // Concatenate icd10_code_part_a and icd10_code_part_b when icd10_code_part_b is not null or empty
      $sql = "SELECT 
                  CASE 
                      WHEN icd10_code_part_b = 'X' THEN icd10_code_part_a
                      ELSE icd10_code_part_a || '.' || icd10_code_part_b 
                  END AS codigo, 
                  name AS nombre 
              FROM icd10
              WHERE 
                  UPPER(icd10_code_part_a || '.' || icd10_code_part_b) LIKE :term OR 
                  UPPER(name) LIKE :term;";

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
