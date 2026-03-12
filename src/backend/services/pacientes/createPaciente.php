<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

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
// 1. Captura de datos del formulario
// ===============================
$tipo_documento = $_POST['tipo_documento'] ?? null;
$cedula   = $_POST['cedula'] ?? null;
$pnombre  = $_POST['pnombre'] ?? null;
$snombre  = $_POST['snombre'] ?? null;
$papellido = $_POST['papellido'] ?? null;
$sapellido = $_POST['sapellido'] ?? null;
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
$sexo     = $_POST['sexo'] ?? null;

// Validar campos obligatorios
if (!$cedula || !$tipo_documento) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Cédula y tipo de documento son obligatorios'
    ]);
    exit;
}

// Conexión a la base de datos local
$dbconn = getConnection();

// Mapear sexo
$sexoFHIR = "unknown";
if ($sexo == "1") $sexoFHIR = "female";
if ($sexo == "2") $sexoFHIR = "male";
if ($sexo == "3") $sexoFHIR = "other";
if ($sexo == "4") $sexoFHIR = "unknown";

if($tipo_documento == 1){
    $display = "Cédula de Identidad";
} elseif($tipo_documento == 2){
    $display = "Cédula Extranjera";
} else {
    $display = "Pasaporte";
}

// ===============================
// 2. COMPROBAR SI YA EXISTE EN BASE LOCAL
// ===============================
$check_local_sql = "SELECT id, code FROM patient WHERE type_code = :type AND document = :documento";
$check_local_stmt = $dbconn->prepare($check_local_sql);
$check_local_stmt->bindValue(':type', "0".$tipo_documento, PDO::PARAM_STR);
$check_local_stmt->bindValue(':documento', $cedula, PDO::PARAM_STR);
$check_local_stmt->execute();
$existing_local = $check_local_stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_local) {
    echo json_encode([
        'status' => 'error',
        'message' => 'El paciente ya existe en la base de datos local',
        'existing_patient' => [
            'local_id' => $existing_local['id'],
            'code' => $existing_local['code']
        ]
    ]);
    exit;
}

// ===============================
// 3. COMPROBAR SI YA EXISTE EN FHIR
// ===============================
// Buscar por identificador (tipo_documento + cédula)
$fhir_search_url = APP_FHIR_SERVER . "/Patient?identifier=" . urlencode("0" . $tipo_documento . "|" . $cedula);
$fhir_search = sendFHIRRequest($fhir_search_url, null, 'GET');

if ($fhir_search['status'] == 200) {
    $search_response = json_decode($fhir_search['body'], true);
    
    // Verificar si encontró algún paciente
    if (isset($search_response['total']) && $search_response['total'] > 0) {
        // Obtener el primer paciente encontrado
        $existing_fhir_patient = $search_response['entry'][0]['resource'];
        
        echo json_encode([
            'status' => 'error',
            'message' => 'El paciente ya existe en el servidor FHIR',
            'existing_patient' => [
                'fhir_id' => $existing_fhir_patient['id'],
                'full_url' => APP_FHIR_SERVER . "/Patient/" . $existing_fhir_patient['id']
            ]
        ]);
        exit;
    }
}

// Generar código UUID solo si no existe
$code = Uuid::uuid4()->toString();

// ===============================
// 4. Construir recurso Patient para FHIR
// ===============================
$patientResource = [
    "resourceType" => "Patient",
    "id" => $code,
    "meta" => [
        "profile" => ["https://mspbs.gov.py/fhir/StructureDefinition/PacientePy"]
    ],
    "text" => [
        "status" => "generated",
        "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\">
                    <p class=\"res-header-id\"><b>Generated Narrative: Patient</b></p>
                    <div style=\"background-color: #e6e6ff; padding: 10px; border: 1px solid #661aff;\">
                        {$pnombre} {$papellido} " . ucfirst($sexoFHIR) . 
                        ", DoB: {$fecha_nacimiento} ( {$display}: {$cedula} )
                    </div>
                 </div>"
    ],
    "identifier" => [[
        "type" => [
            "coding" => [[
                "system" => "https://mspbs.gov.py/fhir/CodeSystem/IdentificadoresPersonaCS",
                "code" => "0".$tipo_documento,
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

try {
    // Iniciar transacción para la base de datos local
    $dbconn->beginTransaction();

    // ===============================
    // 5. Guardar en base de datos local PRIMERO
    // ===============================
    $sql_local = "INSERT INTO patient (type_code, document , first_name, middle_name, last_name, second_last_name, birth_date, gender, code)
                  VALUES(:type, :documento, :pnombre, :snombre, :papellido, :sapellido, :fechanac, :sexo, :code)";

    $stmt_local = $dbconn->prepare($sql_local);
    $stmt_local->bindValue(':type', "0".$tipo_documento, PDO::PARAM_STR);
    $stmt_local->bindValue(':documento', $cedula, PDO::PARAM_STR);
    $stmt_local->bindValue(':pnombre', mb_strtoupper(trim($pnombre), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':snombre', mb_strtoupper(trim($snombre), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':papellido', mb_strtoupper(trim($papellido), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':sapellido', mb_strtoupper(trim($sapellido), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':fechanac', $fecha_nacimiento, PDO::PARAM_STR);
    $stmt_local->bindValue(':sexo', $sexoFHIR, PDO::PARAM_STR);
    $stmt_local->bindValue(':code', $code, PDO::PARAM_STR);
    
    $local_success = $stmt_local->execute();
    $local_id = $dbconn->lastInsertId();

    if (!$local_success || !$local_id) {
        throw new Exception("Error al guardar en base de datos local");
    }

    // ===============================
    // 6. Validar en FHIR
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
    // 7. Crear paciente en FHIR
    // ===============================
    $createUrl = APP_FHIR_SERVER . "/Patient/" . urlencode($code);
    $creation = sendFHIRRequest($createUrl, $patientResource, 'PUT');

    $fhir_response = json_decode($creation['body'], true);
    
    if ($creation['status'] == 200 || $creation['status'] == 201) {
        // Confirmar ambas operaciones
        $dbconn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Paciente creado correctamente en ambas bases de datos',
            'local_id' => $local_id,
            'fhir_id' => $code,
            'fhir_response' => $fhir_response
        ]);
    } else {
        throw new Exception('Error al crear paciente en FHIR: ' . $creation['body']);
    }

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