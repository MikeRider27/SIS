<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('/var/www/html/core/connection.php');
$dbconnFHIR = getConnectionFHIR();
$dbconn = getConnection();
include('generateIPS.php');
include('sendToFhir.php');
require_once('/var/www/html/vendor/autoload.php');

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

        // Obtener y limpiar datos de diagnósticos CIE10
        $codigosCIE = isset($_POST['codigoCIE10']) ? array_filter($_POST['codigoCIE10'], 'trim') : [];
        $fechaCIE = isset($_POST['fechaCIE10']) ? array_filter($_POST['fechaCIE10'], 'trim') : [];
        $estadoCIE = isset($_POST['estadoCIE10']) ? array_filter($_POST['estadoCIE10'], 'trim') : [];
        $notaCIE = isset($_POST['notaCIE10']) ? array_filter($_POST['notaCIE10'], 'trim') : [];
        
        // Obtener datos de alergias
        $codigosAlergia = isset($_POST['codigoAlergia']) ? array_filter($_POST['codigoAlergia'], 'trim') : [];
        $tipoAlergia = isset($_POST['tipoAlergia']) ? array_filter($_POST['tipoAlergia'], 'trim') : [];

        // Obtener datos de medicamentos
        $codigosMedicamento = isset($_POST['codigo_medicamento']) ? array_filter($_POST['codigo_medicamento'], 'trim') : [];
        $dosis_medicamento = isset($_POST['dosis_medicamento']) ? array_filter($_POST['dosis_medicamento'], 'trim') : [];
        $via_medicamento = isset($_POST['via_medicamento']) ? array_filter($_POST['via_medicamento'], 'trim') : [];
        $fecha_medicamento = isset($_POST['fecha_medicamento']) ? array_filter($_POST['fecha_medicamento'], 'trim') : [];
        
        try {
            // Iniciar transacción
            $dbconnFHIR->beginTransaction();

            // Insertar la consulta
            $sql = "INSERT INTO consultas (id_paciente, id_medico, id_servicio, fecha_registro)
                    VALUES(:id_paciente, :id_medico, :id_servicio, :fecha_registro)";
            $stmt = $dbconnFHIR->prepare($sql);
            $stmt->bindParam(':id_paciente', $id_paciente);
            $stmt->bindParam(':id_medico', $id_medico);
            $stmt->bindParam(':id_servicio', $id_servicio);
            $stmt->bindParam(':fecha_registro', $fecha_actual);
            $stmt->execute();
            $consultaID = $dbconnFHIR->lastInsertId();

            // Insertar ANTECEDENTE CIE10
            foreach ($codigosCIE as $index => $codigo10) {
                if (!empty($codigo10)) {
                    $fechaCIE_val = isset($fechaCIE[$index]) ? $fechaCIE[$index] : null;
                    $estadoCIE_val = isset($estadoCIE[$index]) ? $estadoCIE[$index] : null;
                    $notaCIE_val = isset($notaCIE[$index]) ? $notaCIE[$index] : null;

                    // Generar un nuevo UUID para cada ANTECEDENTE
                    $antecedente = Uuid::uuid4()->toString();        
                
                    $sql6 = "INSERT INTO consulta_diagnosticos (id_paciente, id_consulta, codigo_cie10, code, fecha, estado, note)
                             VALUES(:id_paciente, :id_consulta, :codigo_cie10, :code, :fecha, :estado, :note)";
                    $stmt6 = $dbconnFHIR->prepare($sql6);
                    $stmt6->bindParam(':id_paciente', $id_paciente);
                    $stmt6->bindParam(':id_consulta', $consultaID);
                    $stmt6->bindParam(':codigo_cie10', $codigo10);
                    $stmt6->bindParam(':code', $antecedente);
                    $stmt6->bindParam(':fecha', $fechaCIE_val);
                    $stmt6->bindParam(':estado', $estadoCIE_val);
                    $stmt6->bindParam(':note', $notaCIE_val);
                    $stmt6->execute();
                }
            }  

            // Insertar alergias
            foreach ($codigosAlergia as $index => $alergia) {
                if (!empty($alergia)) {
                    $tipoAlergia_val = isset($tipoAlergia[$index]) ? $tipoAlergia[$index] : null;
                    // Generar un nuevo UUID para cada Alergias
                    $alergias = Uuid::uuid4()->toString();
                    $sql7 = "INSERT INTO consulta_alergias (id_paciente, id_consulta, codigo_alergia, code, type)
                             VALUES(:id_paciente, :id_consulta, :codigo_alergia, :code, :type)";
                    $stmt7 = $dbconnFHIR->prepare($sql7);
                    $stmt7->bindParam(':id_paciente', $id_paciente);
                    $stmt7->bindParam(':id_consulta', $consultaID);
                    $stmt7->bindParam(':codigo_alergia', $alergia);
                    $stmt7->bindParam(':code', $alergias);
                    $stmt7->bindParam(':type', $tipoAlergia_val);
                    $stmt7->execute();
                }
            }

            // Insertar medicamentos
            foreach ($codigosMedicamento as $index => $medicamento) {
                if (!empty($medicamento)) {
                    $dosis_val = isset($dosis_medicamento[$index]) ? $dosis_medicamento[$index] : null;
                    $via_medicamento_val = isset($via_medicamento[$index]) ? $via_medicamento[$index] : null;
                    $fecha_medicamento_val = isset($fecha_medicamento[$index]) ? $fecha_medicamento[$index] : null;
                    // Generar un nuevo UUID para cada MEDICAMENTOS
                    $medicamentos = Uuid::uuid4()->toString();
                    $sql9 = "INSERT INTO consultas_recetas (id_paciente, id_consulta, codigo_medicamento, code, dosis, via, fecha)
                             VALUES(:id_paciente, :id_consulta, :codigo_medicamento, :code, :dosis, :via, :fecha)";
                    $stmt9 = $dbconnFHIR->prepare($sql9);
                    $stmt9->bindParam(':id_paciente', $id_paciente);
                    $stmt9->bindParam(':id_consulta', $consultaID);
                    $stmt9->bindParam(':codigo_medicamento', $medicamento);
                    $stmt9->bindParam(':code', $medicamentos);
                    $stmt9->bindParam(':dosis', $dosis_val);
                    $stmt9->bindParam(':via', $via_medicamento_val);
                    $stmt9->bindParam(':fecha', $fecha_medicamento_val);
                    $stmt9->execute();
                }
            }

            // Enviamos al servidor FHIR
            // Genera el FHIR Bundle EN JSON
            $jsonOutput = generarFhirBundle($id_paciente, $consultaID, $dbconnFHIR);

            // Verifica si $jsonOutput es nulo o vacío
            if (empty($jsonOutput)) {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo generar el FHIR Bundle']);
                exit();
            }

            // Enviar el FHIR Bundle al servidor
            $fhirResponse = sendToFhirServer($dbconnFHIR, $jsonOutput);
            
            // 🔹 CORRECCIÓN PRINCIPAL: Verificar si hay error
            if (is_array($fhirResponse) && isset($fhirResponse['error'])) {
                echo json_encode(['status' => 'error', 'message' => $fhirResponse['error']]);
                exit();
            }

            // 🔹 DECODIFICAR LA RESPUESTA FHIR
            $fhirData = json_decode($fhirResponse, true);
            
            // Verificar si la decodificación fue exitosa
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error decodificando respuesta FHIR: ' . json_last_error_msg());
            }

            // Confirmar la transacción
            $dbconnFHIR->commit();

            // 🔹 RESPUESTA FINAL CORRECTA
            echo json_encode([
                'status' => 'success',
                'message' => 'Transaction processed successfully',
                'request' => $jsonOutput,  // ← Esto es lo que se envió
                'response' => $fhirData  // ← Esto es lo que mostrará el modal
            ]);
            
        } catch (Exception $e) {
            // rollback transaction
            $dbconnFHIR->rollBack();
            echo json_encode([
                'status' => 'error', 
                'message' => 'Ocurrió un error: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Acción no permitida.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
}