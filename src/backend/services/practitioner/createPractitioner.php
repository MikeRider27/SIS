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
// 1. Captura de datos del formulario
// ===============================
$cedula     = $_POST['cedula'] ?? null;
$pnombre    = $_POST['pnombre'] ?? null;
$snombre    = $_POST['snombre'] ?? null;
$papellido  = $_POST['papellido'] ?? null;
$sapellido  = $_POST['sapellido'] ?? null;
$fechanac   = $_POST['fecha_nacimiento'] ?? null;
$sexo       = $_POST['sexo'] ?? null;
$pais       = $_POST['pais'] ?? 'PY';
$id_colegio = $_POST['id_colegio'] ?? null;
$tipo       = $_POST['tipo'] ?? 'medico';

// Conexión a la base de datos local
$dbconn = getConnectionFHIR();

// Generar código UUID
$code = Uuid::uuid4()->toString();
$fecha_actual = date('Y-m-d H:i:s');

// ===============================
// 2. Construir recurso Practitioner para FHIR
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
                        {$pnombre} {$papellido} 
                        ( Cédula de Identidad: {$cedula} )
                    </div>
                 </div>"
    ],
    "identifier" => [[
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
        "family" => trim($papellido . " " . $sapellido),
        "given" => array_filter([$pnombre, $snombre])
    ]]
];

try {
    // Iniciar transacción para la base de datos local
    $dbconn->beginTransaction();

    // ===============================
    // 3. Guardar en base de datos local PRIMERO
    // ===============================
    $sql_local = "INSERT INTO profesional2025 (documento, pnombre, snombre, papellido, sapellido, fechanac, sexo, pais, id_colegio, fecha, tipo, code)
                    VALUES (:documento, :pnombre, :snombre, :papellido, :sapellido, :fechanac, :sexo, :pais, :id_colegio, :fecha, :tipo, :code)";

    $stmt_local = $dbconn->prepare($sql_local);
    $stmt_local->bindValue(':documento', $cedula, PDO::PARAM_STR);
    $stmt_local->bindValue(':pnombre', mb_strtoupper(trim($pnombre), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':snombre', mb_strtoupper(trim($snombre), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':papellido', mb_strtoupper(trim($papellido), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':sapellido', mb_strtoupper(trim($sapellido), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':fechanac', $fechanac, PDO::PARAM_STR);
    $stmt_local->bindValue(':sexo', $sexo, PDO::PARAM_STR);
    $stmt_local->bindValue(':pais', $pais, PDO::PARAM_STR);
    $stmt_local->bindValue(':id_colegio', $id_colegio, PDO::PARAM_STR);
    $stmt_local->bindValue(':fecha', $fecha_actual, PDO::PARAM_STR);
    $stmt_local->bindValue(':tipo', $tipo, PDO::PARAM_STR);
    $stmt_local->bindValue(':code', $code, PDO::PARAM_STR);
    
    $local_success = $stmt_local->execute();
    $local_id = $dbconn->lastInsertId();

    if (!$local_success || !$local_id) {
        throw new Exception("Error al guardar en base de datos local");
    }

    // ===============================
    // 4. Validar en FHIR
    // ===============================
    $validateUrl = "https://fhir-conectaton.mspbs.gov.py/fhir/Practitioner/\$validate";
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
    // 5. Crear Practitioner en FHIR
    // ===============================
    $createUrl = "https://fhir-conectaton.mspbs.gov.py/fhir/Practitioner/" . urlencode($code);
    $creation = sendFHIRRequest($createUrl, $practitionerResource, 'PUT');

    $fhir_response = json_decode($creation['body'], true);
    
    if ($creation['status'] == 201) {
        // Confirmar ambas operaciones
        $dbconn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Profesional creado correctamente en ambas bases de datos',
            'local_id' => $local_id,
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