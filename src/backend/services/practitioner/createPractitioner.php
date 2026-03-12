<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

include('/var/www/html/core/connection.php');
require_once('/var/www/html/vendor/autoload.php');

use Ramsey\Uuid\Uuid;

function sendFHIRRequest($url, $resource, $method = 'POST') {
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
$cedula     = $_POST['cedula'] ?? null;
$pnombre    = $_POST['pnombre'] ?? null;
$snombre    = $_POST['snombre'] ?? null;
$papellido  = $_POST['papellido'] ?? null;
$sapellido  = $_POST['sapellido'] ?? null;

// Validar campos obligatorios
if (!$cedula) {
    echo json_encode([
        'status' => 'error',
        'message' => 'La cédula es obligatoria'
    ]);
    exit;
}

// Conexión a la base de datos local
$dbconn = getConnection();

// ===============================
// 2. COMPROBAR SI YA EXISTE EN BASE LOCAL
// ===============================
$check_local_sql = "SELECT id, code FROM professional WHERE document = :documento";
$check_local_stmt = $dbconn->prepare($check_local_sql);
$check_local_stmt->bindValue(':documento', $cedula, PDO::PARAM_STR);
$check_local_stmt->execute();
$existing_local = $check_local_stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_local) {
    echo json_encode([
        'status' => 'error',
        'message' => 'El profesional ya existe en la base de datos local',
        'existing_professional' => [
            'local_id' => $existing_local['id'],
            'code' => $existing_local['code']
        ]
    ]);
    exit;
}

// ===============================
// 3. COMPROBAR SI YA EXISTE EN FHIR
// ===============================
// Buscar por identificador (cédula)
$fhir_search_url = APP_FHIR_SERVER . "/Practitioner?identifier=" . urlencode($cedula);
$fhir_search = sendFHIRRequest($fhir_search_url, null, 'GET');

if ($fhir_search['status'] == 200) {
    $search_response = json_decode($fhir_search['body'], true);
    
    // Verificar si encontró algún profesional
    if (isset($search_response['total']) && $search_response['total'] > 0) {
        // Obtener el primer profesional encontrado
        $existing_fhir_practitioner = $search_response['entry'][0]['resource'];
        
        echo json_encode([
            'status' => 'error',
            'message' => 'El profesional ya existe en el servidor FHIR',
            'existing_professional' => [
                'fhir_id' => $existing_fhir_practitioner['id'],
                'full_url' => APP_FHIR_SERVER . "/Practitioner/" . $existing_fhir_practitioner['id']
            ]
        ]);
        exit;
    }
}

// Generar código UUID solo si no existe
$code = Uuid::uuid4()->toString();
$fecha_actual = date('Y-m-d H:i:s');

// ===============================
// 4. Construir recurso Practitioner para FHIR
// ===============================
$practitionerResource = [
    "resourceType" => "Practitioner",
    "id" => $code,
    "meta" => [
        "profile" => ["https://mspbs.gov.py/fhir/StructureDefinition/PractitionerPy"]
    ],
    "text" => [
        "status" => "generated",
        "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\">
                    <p class=\"res-header-id\"><b>Generated Narrative: Practitioner</b></p>
                    <div style=\"background-color: #e6e6ff; padding: 10px; border: 1px solid #661aff;\">
                        " . trim($pnombre . " " . $snombre . " " . $papellido . " " . $sapellido) . "
                        ( Cédula de Identidad: {$cedula} )
                    </div>
                 </div>"
    ],
    "identifier" => [[
        "system" => "https://mspbs.gov.py/fhir/CodeSystem/IdentificadoresProfesionalCS",
        "type" => [
            "coding" => [[
                "system" => "https://mspbs.gov.py/fhir/CodeSystem/IdentificadoresProfesionalCS",
                "code" => "01",
                "display" => "Cédula de Identidad"
            ]]
        ],
        "value" => $cedula
    ]],
    "name" => [[
        "use" => "official",
        "family" => trim($papellido . " " . $sapellido),
        "given" => array_filter([$pnombre, $snombre])
    ]]
];

try {
    // Iniciar transacción para la base de datos local
    $dbconn->beginTransaction();

    // ===============================
    // 5. Guardar en base de datos local PRIMERO
    // ===============================
    $sql_local = "INSERT INTO professional (document, first_name, middle_name, last_name, second_last_name, code)
                  VALUES(:documento, :pnombre, :snombre, :papellido, :sapellido, :code)";

    $stmt_local = $dbconn->prepare($sql_local);
    $stmt_local->bindValue(':documento', $cedula, PDO::PARAM_STR);
    $stmt_local->bindValue(':pnombre', mb_strtoupper(trim($pnombre), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':snombre', mb_strtoupper(trim($snombre), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':papellido', mb_strtoupper(trim($papellido), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':sapellido', mb_strtoupper(trim($sapellido), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':code', $code, PDO::PARAM_STR);
    
    $local_success = $stmt_local->execute();
    $local_id = $dbconn->lastInsertId();

    if (!$local_success || !$local_id) {
        throw new Exception("Error al guardar en base de datos local");
    }

    // ===============================
    // 6. Validar en FHIR
    // ===============================
    $validateUrl = APP_FHIR_SERVER . "/Practitioner/\$validate";
    $validation = sendFHIRRequest($validateUrl, $practitionerResource, 'POST');

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
    // 7. Crear Practitioner en FHIR
    // ===============================
    $createUrl = APP_FHIR_SERVER . "/Practitioner/" . urlencode($code);
    $creation = sendFHIRRequest($createUrl, $practitionerResource, 'PUT');

    $fhir_response = json_decode($creation['body'], true);
    
    if ($creation['status'] == 200 || $creation['status'] == 201) {
        // Confirmar ambas operaciones
        $dbconn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Profesional creado correctamente en ambas bases de datos',
            'local_id' => $local_id,
            'fhir_id' => $code,
            'fhir_response' => $fhir_response
        ]);
    } else {
        throw new Exception('Error al crear profesional en FHIR: ' . $creation['body']);
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