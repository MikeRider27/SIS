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
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($resource));

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
    // Extraer tipo de documento del identifier
    $identifier = $fhirData['identifier'][0] ?? null;
    $tipo_documento_code = $identifier['type']['coding'][0]['code'] ?? '01';
    $cedula = $identifier['value'] ?? '';
    
    // Mapear código a ID numérico (01 -> 1, 02 -> 2, etc.)
    $tipo_documento = (int)ltrim($tipo_documento_code, '0');
    if ($tipo_documento === 0) $tipo_documento = 1; // Default a CI si no se puede determinar
    
    // Extraer nombres del array name
    $name = $fhirData['name'][0] ?? [];
    $papellido = '';
    $sapellido = '';
    
    if (isset($name['family'])) {
        $apellidos = explode(' ', trim($name['family']), 2);
        $papellido = $apellidos[0] ?? '';
        $sapellido = $apellidos[1] ?? '';
    }
    
    $given = $name['given'] ?? [];
    $pnombre = $given[0] ?? '';
    $snombre = $given[1] ?? '';
    
    // Extraer género y mapearlo de FHIR a local (male/female/other/unknown -> 1/2/3/4)
    $sexoFHIR = $fhirData['gender'] ?? 'unknown';
    $sexoMap = [
        'female' => 1,
        'male' => 2,
        'other' => 3,
        'unknown' => 4
    ];
    $sexo = $sexoMap[$sexoFHIR] ?? 4;
    
    // Extraer fecha de nacimiento
    $fecha_nacimiento = $fhirData['birthDate'] ?? '';
    
    // ===============================
    // 4. Validar datos mínimos requeridos
    // ===============================
    if (empty($tipo_documento) || empty($cedula) || empty($pnombre) || 
        empty($papellido) || empty($fecha_nacimiento) || empty($sexo)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Datos incompletos en el recurso FHIR recibido'
        ]);
        exit;
    }

    // Conexión a la base de datos local
    $dbconn = getConnection();

    // Iniciar transacción
    $dbconn->beginTransaction();

    // ===============================
    // 5. Buscar paciente en base local
    // ===============================
    $fhir_code = null;
    $local_record_exists = false;
    $local_id = null;
    
  
    // Buscar por código FHIR (alfanumérico - UUID)
    $sql_select = "SELECT id, code FROM patient WHERE code = :code";
    $stmt_select = $dbconn->prepare($sql_select);
    $stmt_select->bindValue(':code', $id, PDO::PARAM_STR);
    
    
    $stmt_select->execute();
    $patient = $stmt_select->fetch(PDO::FETCH_ASSOC);
    
    if ($patient) {
        // Paciente existe en base local
        $local_record_exists = true;
        $fhir_code = $patient['code'];
        $local_id = $patient['id'];
    } else {
        // No existe en local, verificar si el ID es un UUID válido para FHIR
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            // Es un UUID válido, lo usamos como código FHIR
            $fhir_code = $id;
            
            // Verificar si existe en FHIR (opcional, pero recomendado)
            $checkUrl = APP_FHIR_SERVER . "/Patient/" . urlencode($id);
            $check = sendFHIRRequest($checkUrl, null, 'GET');
            
            if ($check['status'] !== 200) {
                // No existe en FHIR, podríamos crearlo o lanzar error
                // Dependiendo de tu lógica de negocio
                error_log("Paciente con UUID {$id} no encontrado en FHIR");
            }
        } else {
            throw new Exception("ID inválido: no es numérico (ID local) ni UUID válido (código FHIR)");
        }
    }

    // ===============================
    // 6. Preparar datos para actualización
    // ===============================
    // Mapear sexo de vuelta a FHIR (para el recurso)
    $sexoFHIR = "unknown";
    if ($sexo == 1) $sexoFHIR = "female";
    if ($sexo == 2) $sexoFHIR = "male";
    if ($sexo == 3) $sexoFHIR = "other";
    if ($sexo == 4) $sexoFHIR = "unknown";

    // Determinar display para el tipo de documento
    $displayMap = [
        1 => "Cédula de Identidad",
        2 => "Cédula Extranjera",
        3 => "Pasaporte"
    ];
    $display = $displayMap[$tipo_documento] ?? "Cédula de Identidad";

    // ===============================
    // 7. Construir recurso Patient para FHIR
    // ===============================
    $patientResource = [
        "resourceType" => "Patient",
        "id" => $fhir_code,
        "meta" => [
            "profile" => ["https://mspbs.gov.py/fhir/StructureDefinition/PacientePy"]
        ],
        "text" => [
            "status" => "generated",
            "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\">
                        <p class=\"res-header-id\"><b>Generated Narrative: Patient</b></p>
                        <div style=\"background-color: #e6e6ff; padding: 10px; border: 1px solid #661aff;\">
                            " . htmlspecialchars($pnombre . " " . $papellido) . " " . ucfirst($sexoFHIR) . 
                            ", DoB: {$fecha_nacimiento} ( {$display}: {$cedula} )
                        </div>
                     </div>"
        ],
        "identifier" => [[
            "type" => [
                "coding" => [[
                    "system" => "https://mspbs.gov.py/fhir/CodeSystem/IdentificadoresPersonaCS",
                    "code" => str_pad($tipo_documento, 2, '0', STR_PAD_LEFT),
                    "display" => $display
                ]]
            ],
            "value" => $cedula
        ]],
        "name" => [[
            "family" => trim($papellido . " " . $sapellido),
            "given" => array_filter([$pnombre, $snombre])
        ]],
        "gender" => $sexoFHIR,
        "birthDate" => $fecha_nacimiento
    ];

    // ===============================
    // 8. Validar en FHIR antes de actualizar
    // ===============================
    $validateUrl = APP_FHIR_SERVER . "/Patient/\$validate";
    $validation = sendFHIRRequest($validateUrl, $patientResource, 'POST');

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
    // 9. Actualizar en FHIR (usando PUT)
    // ===============================
    $updateUrl = APP_FHIR_SERVER . "/Patient/" . urlencode($fhir_code);
    $update = sendFHIRRequest($updateUrl, $patientResource, 'PUT');

    $fhir_response = json_decode($update['body'], true);
    
    // Verificar respuesta FHIR (200 OK es éxito para PUT)
    if ($update['status'] !== 200 && $update['status'] !== 201) {
        throw new Exception('Error al actualizar en FHIR: ' . $update['body']);
    }

    // ===============================
    // 10. Actualizar en base de datos local (si existe)
    // ===============================
    if ($local_record_exists) {
        $sql_update = "UPDATE patient SET 
                       type_code = :type,
                       document = :documento,
                       first_name = :pnombre,
                       middle_name = :snombre,
                       last_name = :papellido,
                       second_last_name = :sapellido,
                       birth_date = :fechanac,
                       gender = :sexo
                       WHERE code = :code";

        $stmt_update = $dbconn->prepare($sql_update);
        $stmt_update->bindValue(':type', str_pad($tipo_documento, 2, '0', STR_PAD_LEFT), PDO::PARAM_STR);
        $stmt_update->bindValue(':documento', $cedula, PDO::PARAM_STR);
        $stmt_update->bindValue(':pnombre', mb_strtoupper(trim($pnombre), 'UTF-8'), PDO::PARAM_STR);
        $stmt_update->bindValue(':snombre', mb_strtoupper(trim($snombre), 'UTF-8'), PDO::PARAM_STR);
        $stmt_update->bindValue(':papellido', mb_strtoupper(trim($papellido), 'UTF-8'), PDO::PARAM_STR);
        $stmt_update->bindValue(':sapellido', mb_strtoupper(trim($sapellido), 'UTF-8'), PDO::PARAM_STR);
        $stmt_update->bindValue(':fechanac', $fecha_nacimiento, PDO::PARAM_STR);
        $stmt_update->bindValue(':sexo', $sexoFHIR, PDO::PARAM_STR);
        $stmt_update->bindValue(':code', $fhir_code, PDO::PARAM_STR);
        
        $update_success = $stmt_update->execute();
        $rows_affected = $stmt_update->rowCount();

        if (!$update_success) {
            throw new Exception("Error al actualizar en base de datos local");
        }
    } else {
        // Opcional: Insertar en base local si no existe y quieres mantener sincronización
        // Depende de tu lógica de negocio
        error_log("Paciente con código FHIR {$fhir_code} no existe en base local, solo se actualizó en FHIR");
    }

    // ===============================
    // 11. Confirmar transacción
    // ===============================
    $dbconn->commit();

    // Construir mensaje de respuesta
    if ($local_record_exists) {
        $message = 'Paciente actualizado correctamente en ambas bases de datos';
    } else {
        $message = 'Paciente actualizado correctamente en FHIR (no existía en base local)';
    }

    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'details' => [
            'fhir_code' => $fhir_code,
            'local_id' => $local_id,
            'local_record_exists' => $local_record_exists,
            'fhir_status' => $update['status'],
            'rows_updated' => $rows_affected ?? 0
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