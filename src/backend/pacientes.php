<?php

include('../core/connection.php');
$dbconn = getConnectionFHIR();


// Check if the form was sent and the action is 'search'
if (isset($_POST['accion']) && $_POST['accion'] === "search") {
    $tipo_documento = $_POST['tipo_documento'] ?? null;
    $documento = $_POST['documento'] ?? null;
   

    if ($tipo_documento && $documento) {
        try {
            // Start transaction
            $dbconn->beginTransaction();

            // Query to check if the participant already exists
            $sql = "SELECT p.id_paciente, p.pnombre, p.snombre, p.papellido, p.sapellido, p.fechanac, p.sexo, p.telefono, p.correo,
                    doc_nacional.numero_documento AS documento_nacional, doc_pasaporte.numero_documento AS pasaporte
                    FROM paciente p
                LEFT JOIN documento doc_nacional ON p.id_paciente = doc_nacional.id_paciente AND doc_nacional.tipo_documento = 'cedula'
                LEFT JOIN documento doc_pasaporte ON p.id_paciente = doc_pasaporte.id_paciente AND doc_pasaporte.tipo_documento = 'pasaporte'
                WHERE
                    (doc_nacional.tipo_documento = :tipo_documento AND doc_nacional.numero_documento = :documento) 
                    OR 
                    (doc_pasaporte.tipo_documento = :tipo_documento AND doc_pasaporte.numero_documento = :documento)
                ";

            $stmt = $dbconn->prepare($sql);
            $stmt->bindParam(':tipo_documento', $tipo_documento, PDO::PARAM_STR);
            $stmt->bindParam(':documento', $documento, PDO::PARAM_STR);

            // Execute the query
            $stmt->execute();
            $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

            // Commit transaction
            $dbconn->commit();

            // Check if patient was found
            $status = $paciente ? "success" : "error";
            $message = $paciente ? "" : "Paciente no encontrado.";
        } catch (Exception $e) {
            // Rollback transaction in case of error
            $dbconn->rollBack();
            $status = "error";
            $message = "Error: " . $e->getMessage();
            $paciente = null;
        }
    } else {
        // Validation error: Missing parameters
        $status = "error";
        $message = "Faltan datos necesarios: tipo_documento o documento.";
        $paciente = null;
    }

    // Return the result as JSON
    echo json_encode(array("status" => $status, "message" => $message, "paciente" => $paciente));
} else {
    // Form was not sent
    echo json_encode(array("status" => "error", "message" => "Formulario no enviado"));
}
