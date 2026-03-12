<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include('/var/www/html/core/connection.php');
require_once('/var/www/html/vendor/autoload.php');

use Ramsey\Uuid\Uuid;

function sendFHIRRequest($url, $resource, $method) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/fhir+json',
        'Accept: application/fhir+json'
    ]);
    
    if ($resource !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($resource));
    }

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = 'Error de cURL: ' . curl_error($ch);
        curl_close($ch);
        return ['status' => 500, 'body' => json_encode(['error' => $error])];
    }
    curl_close($ch);

    return ['status' => $httpcode, 'body' => $response];
}

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

// ===============================
// 2. Obtener datos del PUT
// ===============================
$input = file_get_contents("php://input");
$fhirData = json_decode($input, true); // Esto es el recurso FHIR completo

if (!$fhirData) {
    echo json_encode([
        'status' => 'error',
        'message' => 'JSON requerido o inválido'
    ]);
    exit;
}

// ===============================
// 3. MAPEAR FHIR A FORMATO LOCAL
// ===============================
try {
    // Extraer datos del recurso FHIR
    $identifier = $fhirData['identifier'][0]['value'] ?? null;
    $name = $fhirData['name'] ?? null;
    $type = $fhirData['type'][0]['text'] ?? null;
    
    // ===============================
    // 4. Validar datos mínimos requeridos
    // ===============================
    if (empty($identifier) || empty($name)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Datos incompletos en el recurso FHIR recibido (identifier y name son requeridos)'
        ]);
        exit;
    }

    // Conexión a la base de datos local
    $dbconn = getConnection();

    // Iniciar transacción
    $dbconn->beginTransaction();

    // ===============================
    // 5. Buscar organización en base local
    // ===============================
    $fhir_code = null;
    $local_record_exists = false;
    $local_id = null;
    
    // Buscar por código FHIR (el ID recibido)
    $sql_select = "SELECT id, identifier FROM organization WHERE identifier = :identifier";
    $stmt_select = $dbconn->prepare($sql_select);
    $stmt_select->bindValue(':identifier', $id, PDO::PARAM_STR);
    
    $stmt_select->execute();
    $organization = $stmt_select->fetch(PDO::FETCH_ASSOC);
    
    if ($organization) {
        // Organización existe en base local
        $local_record_exists = true;
        $fhir_code = $organization['identifier'];
        $local_id = $organization['id'];
    } else {
        // No existe en local, usar el ID proporcionado como código FHIR
        $fhir_code = $id;
        
        // Verificar si existe en FHIR (opcional)
        $checkUrl = APP_FHIR_SERVER . "/Organization/" . urlencode($id);
        $check = sendFHIRRequest($checkUrl, null, 'GET');
        
        if ($check['status'] !== 200) {
            // No existe en FHIR, podríamos crearlo
            error_log("Organización con ID {$id} no encontrada en FHIR, se creará");
        }
    }

    // ===============================
    // 6. Construir recurso Organization para FHIR
    // ===============================
    $orgResource = [
        "resourceType" => "Organization",
        "id" => $fhir_code,
        "meta" => [
            "profile" => ["https://mspbs.gov.py/fhir/StructureDefinition/OrganizacionPy"]
        ],
        "text" => [
            "status" => "generated",
            "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\">
                        <p class=\"res-header-id\"><b>Generated Narrative: Organization {$fhir_code}</b></p>
                        <a name=\"{$identifier}\"> </a>
                        <a name=\"hc{$identifier}\"> </a>
                        <div style=\"display: inline-block; background-color: #d9e0e7; padding: 6px; margin: 4px; border: 1px solid #8da1b4; border-radius: 5px; line-height: 60%\">
                            <p style=\"margin-bottom: 0px\"/>
                            <p style=\"margin-bottom: 0px\">Profile: <a href=\"StructureDefinition-OrganizacionPy.html\">Organizacion Paraguay</a></p>
                        </div>
                        <p><b>identifier</b>: {$identifier}</p>
                        <p><b>type</b>: <span title=\"Codes:\">{$type}</span></p>
                        <p><b>name</b>: {$name}</p>
                      </div>"
        ],
        "identifier" => [[ 
            "value" => $identifier 
        ]],
        "type" => [[ 
            "text" => $type 
        ]],
        "name" => $name
    ];

    // ===============================
    // 7. Validar en FHIR antes de actualizar
    // ===============================
    $validateUrl = APP_FHIR_SERVER . "/Organization/\$validate";
    $validation = sendFHIRRequest($validateUrl, $orgResource, 'POST');

    if ($validation['status'] !== 200) {
        throw new Exception('Error en validación FHIR: ' . $validation['body']);
    }

    $validationResponse = json_decode($validation['body'], true);
    if (isset($validationResponse['issue'])) {
        foreach ($validationResponse['issue'] as $issue) {
            if (in_array($issue['severity'], ['error','fatal'])) {
                throw new Exception('Validación FHIR fallida: ' . json_encode($validationResponse));
            }
        }
    }

    // ===============================
    // 8. Actualizar en FHIR (usando PUT)
    // ===============================
    $updateUrl = APP_FHIR_SERVER . "/Organization/" . urlencode($fhir_code);
    $update = sendFHIRRequest($updateUrl, $orgResource, 'PUT');

    $fhir_response = json_decode($update['body'], true);
    
    // Verificar respuesta FHIR (200 OK es éxito para PUT)
    if ($update['status'] !== 200 && $update['status'] !== 201) {
        throw new Exception('Error al actualizar en FHIR: ' . $update['body']);
    }

    // ===============================
    // 9. Actualizar en base de datos local (si existe)
    // ===============================
    if ($local_record_exists) {
        $sql_update = "UPDATE organization SET 
                       name = :name,
                       type = :type
                       WHERE identifier = :identifier";

        $stmt_update = $dbconn->prepare($sql_update);
        $stmt_update->bindValue(':name', mb_strtoupper(trim($name), 'UTF-8'), PDO::PARAM_STR);
        $stmt_update->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt_update->bindValue(':identifier', $identifier, PDO::PARAM_STR);
        
        $update_success = $stmt_update->execute();
        $rows_affected = $stmt_update->rowCount();

        if (!$update_success) {
            throw new Exception("Error al actualizar en base de datos local");
        }
    } else {
        // Insertar en base local si no existe
        $sql_insert = "INSERT INTO organization (identifier, name, type)
                      VALUES (:identifier, :name, :type)";
        
        $stmt_insert = $dbconn->prepare($sql_insert);
        $stmt_insert->bindValue(':identifier', $identifier, PDO::PARAM_STR);
        $stmt_insert->bindValue(':name', mb_strtoupper(trim($name), 'UTF-8'), PDO::PARAM_STR);
        $stmt_insert->bindValue(':type', $type, PDO::PARAM_STR);
        
        $insert_success = $stmt_insert->execute();
        $local_id = $dbconn->lastInsertId();
        $rows_affected = 1;

        if (!$insert_success) {
            throw new Exception("Error al insertar en base de datos local");
        }
    }

    // ===============================
    // 10. Confirmar transacción
    // ===============================
    $dbconn->commit();

    // Construir mensaje de respuesta
    if ($local_record_exists) {
        $message = 'Organización actualizada correctamente en ambas bases de datos';
    } else {
        $message = 'Organización creada correctamente en ambas bases de datos';
    }

    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'details' => [
            'fhir_code' => $fhir_code,
            'local_id' => $local_id,
            'local_record_exists' => $local_record_exists,
            'fhir_status' => $update['status'],
            'rows_affected' => $rows_affected ?? 0,
            'operation' => $local_record_exists ? 'update' : 'create'
        ],
        'fhir_response' => $fhir_response
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($dbconn) && $dbconn->inTransaction()) {
        $dbconn->rollBack();
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en el proceso: ' . $e->getMessage()
    ]);
}
?>