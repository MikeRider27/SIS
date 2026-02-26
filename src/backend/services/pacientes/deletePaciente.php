<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

include('/var/www/html/core/connection.php');

// ===============================
// 1. Obtener y validar ID
// ===============================
$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ID requerido'
    ]);
    exit;
}

// Conexión a la base de datos local
$dbconn = getConnection();

try {
    // Iniciar transacción para la base de datos local
    $dbconn->beginTransaction();

    // ===============================
    // 2. Determinar si el ID es numérico (ID local) o UUID (código FHIR)
    // ===============================
    $is_numeric = is_numeric($id);
    $fhir_code = null;
    $local_record_exists = false;
    $local_id = null;
    
    // ===============================
    // 3. Intentar obtener el código FHIR de la base local
    // ===============================
    if ($is_numeric) {
        // Si es numérico, buscar por ID local
        $sql_select = "SELECT id, code FROM patient WHERE id = :id";
        $stmt_select = $dbconn->prepare($sql_select);
        $stmt_select->bindValue(':id', $id, PDO::PARAM_INT);
    } else {
        // Si no es numérico, buscar por código FHIR (UUID)
        $sql_select = "SELECT id, code FROM patient WHERE code = :code";
        $stmt_select = $dbconn->prepare($sql_select);
        $stmt_select->bindValue(':code', $id, PDO::PARAM_STR);
    }
    
    $stmt_select->execute();
    $patient = $stmt_select->fetch(PDO::FETCH_ASSOC);
    
    if ($patient) {
        $local_record_exists = true;
        $fhir_code = $patient['code'];
        $local_id = $patient['id'];
    } else {
        // Si no se encuentra en base local, usar el ID proporcionado como código FHIR
        // (asumiendo que es un UUID válido)
        $fhir_code = $id;
    }

    // ===============================
    // 4. Eliminar en FHIR (siempre intentamos)
    // ===============================
    $fhir_delete_success = false;
    $fhir_status = null;
    $fhir_response = null;
    
    if ($fhir_code) {
        $fhirUrl = APP_FHIR_SERVER . "/Patient/" . urlencode($fhir_code);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fhirUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/fhir+json',
            'Content-Type: application/fhir+json'
        ]);
        
        $fhir_response = curl_exec($ch);
        $fhir_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = 'Error de cURL: ' . curl_error($ch);
            curl_close($ch);
            throw new Exception($error);
        }
        curl_close($ch);

        // Verificar respuesta FHIR (204 No Content o 200 OK son éxito para DELETE)
        // También aceptamos 404 como éxito si el recurso no existía
        if ($fhir_status === 204 || $fhir_status === 200) {
            $fhir_delete_success = true;
        } elseif ($fhir_status === 404) {
            // El recurso no existía en FHIR, consideramos esto como éxito
            $fhir_delete_success = true;
            $fhir_response = json_encode(['message' => 'Recurso no encontrado en FHIR, considerado como eliminado']);
        } else {
            // Error real en FHIR
            $error_response = json_decode($fhir_response, true);
            $error_message = isset($error_response['issue'][0]['diagnostics']) 
                            ? $error_response['issue'][0]['diagnostics'] 
                            : "Error al eliminar en FHIR (Status: $fhir_status)";
            throw new Exception($error_message);
        }
    }

    // ===============================
    // 5. Eliminar en base de datos local (si existe)
    // ===============================
    $local_delete_success = false;
    $rows_affected = 0;
    
    if ($local_record_exists) {
        $sql_delete = "DELETE FROM patient WHERE code = :code";
        $stmt_delete = $dbconn->prepare($sql_delete);
        $stmt_delete->bindValue(':code', $fhir_code, PDO::PARAM_STR);
        
        $delete_success = $stmt_delete->execute();
        $rows_affected = $stmt_delete->rowCount();

        if ($delete_success && $rows_affected > 0) {
            $local_delete_success = true;
        } else {
            // Si no se pudo eliminar pero el registro existía, es un error
            throw new Exception("Error al eliminar en base de datos local");
        }
    }

    // ===============================
    // 6. Confirmar transacción
    // ===============================
    $dbconn->commit();

    // Construir mensaje de respuesta según lo que se eliminó
    if ($fhir_delete_success && $local_delete_success) {
        $message = 'Paciente eliminado correctamente de ambas bases de datos';
    } elseif ($fhir_delete_success && !$local_delete_success && !$local_record_exists) {
        $message = 'Paciente eliminado solo del servidor FHIR (no existía en base local)';
    } elseif ($fhir_delete_success && !$local_delete_success) {
        $message = 'Paciente eliminado del servidor FHIR pero hubo problemas con la base local';
    } else {
        $message = 'No se pudo eliminar el paciente';
    }

    echo json_encode([
        'status' => ($fhir_delete_success) ? 'success' : 'error',
        'message' => $message,
        'details' => [
            'fhir_deleted' => $fhir_delete_success,
            'local_deleted' => $local_delete_success,
            'local_record_exists' => $local_record_exists,
            'fhir_code' => $fhir_code,
            'local_id' => $local_id,
            'fhir_status' => $fhir_status,
            'rows_deleted' => $rows_affected
        ]
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($dbconn->inTransaction()) {
        $dbconn->rollBack();
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en el proceso: ' . $e->getMessage()
    ]);
}
?>