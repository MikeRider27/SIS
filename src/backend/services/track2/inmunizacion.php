<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('/var/www/html/core/connection.php');
$dbconnFHIR = getConnectionFHIR();
include('generateIPSICVP.php');
include('sendToFhir.php');
require_once('/var/www/html/vendor/autoload.php');

// Asegúrate de cargar el autoload de Composer
use Ramsey\Uuid\Uuid;


// Verificar si el usuario está logueado
if (isset($_SESSION['idUsuario'])) {
  if (isset($_POST['accion']) && $_POST['accion'] === "agregar") {
    // Inicializar variables
    $id_medico = $_POST['id_medico'];
    $id_servicio = $_POST['id_servicio'];
    $id_paciente = $_POST['id_paciente'];
    $fecha_actual = date('Y-m-d');
    $hora_actual = date('H:i:s');

    // Obtener y limpiar datos de vacunas
    $codigoVacunas = isset($_POST['codigo_vacuna']) ? array_filter($_POST['codigo_vacuna'], 'trim') : [];
    
   

    try {
      // Iniciar transacción
      $dbconnFHIR->beginTransaction();

      //Insertar la consulta
      $sql = "INSERT INTO consultas (id_paciente, id_medico, id_servicio, fecha_registro)
              VALUES(:id_paciente, :id_medico, :id_servicio, :fecha_registro)";
      $stmt = $dbconnFHIR->prepare($sql);
      $stmt->bindParam(':id_paciente', $id_paciente);
      $stmt->bindParam(':id_medico', $id_medico);
      $stmt->bindParam(':id_servicio', $id_servicio);
      $stmt->bindParam(':fecha_registro', $fecha_actual);
      $stmt->execute();
      $consultaID = $dbconnFHIR->lastInsertId();

      // Insertar vacunacion
      foreach ($codigoVacunas as $index => $vacuna) {
        //CONSULTAMOS LOS LOTES
        $sql = 'SELECT lote FROM vacunas WHERE local_code = :code';
        $stmt = $dbconnFHIR->prepare($sql);
        $stmt->bindParam(':code', $vacuna);
        $stmt->execute();
        $lote = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Generar un nuevo UUID para cada inmunización
        $immunizationID = Uuid::uuid4()->toString();
        $sql6 = 'INSERT INTO consulta_vacunacion (id_paciente, id_consulta, codigo_vacuna, lote, fecha_aplicacion, code)
                 VALUES(:id_paciente, :id_consulta, :codigo_vacuna, :lote, :fecha_aplicacion, :code);';
        $stmt6 = $dbconnFHIR->prepare($sql6);
        $stmt6->bindParam(':id_paciente', $id_paciente);
        $stmt6->bindParam(':id_consulta', $consultaID);
        $stmt6->bindParam(':codigo_vacuna', $vacuna);
        $stmt6->bindParam(':lote', $lote['lote']);
        $stmt6->bindParam(':fecha_aplicacion', $fecha_actual);
        $stmt6->bindParam(':code', $immunizationID);
        $stmt6->execute();
        }
      

    

      // Enviamos al servidor FHIR
      // Genera el FHIR Bundle EN JSON
      $jsonOutput = generarFhirBundle($id_paciente, $consultaID, $dbconnFHIR);

      // Verifica si $jsonOutput es nulo o vacío
      if (empty($jsonOutput)) {
        echo json_encode(['error' => 'No se pudo generar el FHIR Bundle']);
        exit();
      }

      // Enviar el FHIR Bundle al servidor
      $response = sendToFhirServer($dbconnFHIR, $jsonOutput);
      if (is_array($response) && isset($response['error'])) {
          echo json_encode(['status' => 'error', 'message' => $response['error']]);
          exit();
      }
      

      // Procesar la respuesta del servidor FHIR
      $processedData = processFhirResponse($response); // 🔹 ahora devuelve array directo

      // Confirmar la transacción
      $dbconnFHIR->commit();

      $status = "success";

      // Construir mensaje con los recursos creados
      if (isset($processedData['resources']) && is_array($processedData['resources'])) {
        $ids = [];
        foreach ($processedData['resources'] as $resource) {
          $ids[] = "{$resource['type']}: {$resource['id']} ({$resource['status']})";
        }
        $message = "Se registró correctamente. " . implode(" | ", $ids);
      } else {
        $message = "Se registró correctamente, pero no se pudieron extraer los IDs.";
      }

    } catch (Exception $e) {
      // rollback transaction
      $dbconnFHIR->rollBack();
      $status = "error";
      $message = "Ocurrió un error: " . $e->getMessage();
    }

    print json_encode(array("status" => $status, "message" => $message));
  } else {
    echo json_encode(array("status" => "error", "message" => "Acción no permitida."));
  }
} else {
  echo json_encode(array("status" => "error", "message" => "No autorizado."));
}
