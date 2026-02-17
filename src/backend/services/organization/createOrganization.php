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
$identifier = $_POST['identifier'] ?? null;
$name       = $_POST['name'] ?? null;
$type       = $_POST['type'] ?? null;
$departamento = $_POST['departamento'] ?? 'ASUNCIÓN';
$pais       = $_POST['pais'] ?? 'PRY';
$code       =  $_POST['identifier'] ?? null;

if(!$identifier || !$name){
    echo json_encode(['status'=>'error','message'=>'Faltan datos obligatorios (identifier y name)']);
    exit;
}

// Conexión a la base de datos local
$dbconn = getConnectionFHIR();




// ===============================
// 2. Construir recurso Organization para FHIR
// ===============================
$orgResource = [
    "resourceType" => "Organization",
    "id" => $code,
    "meta" => [
        "profile" => ["https://mspbs.gov.py/fhir/StructureDefinition/OrganizacionPy"]
    ],
    "text" => [
        "status" => "generated",
        "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\">
                    <p class=\"res-header-id\"><b>Generated Narrative: Organization {$code}</b></p>
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

try {
    // Iniciar transacción para la base de datos local
    $dbconn->beginTransaction();

    // ===============================
    // 3. Guardar en base de datos local PRIMERO
    // ===============================
    $sql_local = "INSERT INTO establecimiento2025 (id_establecimiento, nombre, direccion, departamento, pais, code, type)
                    VALUES (:id_establecimiento, :nombre, :direccion, :departamento, :pais, :code, :type)";

    $stmt_local = $dbconn->prepare($sql_local);
    $stmt_local->bindValue(':id_establecimiento', $identifier, PDO::PARAM_STR);
    $stmt_local->bindValue(':nombre', mb_strtoupper(trim($name), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':direccion', mb_strtoupper(trim($direccion), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':departamento', mb_strtoupper(trim($departamento), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':pais', mb_strtoupper(trim($pais), 'UTF-8'), PDO::PARAM_STR);
    $stmt_local->bindValue(':code', $code, PDO::PARAM_STR);
    $stmt_local->bindValue(':type', $type, PDO::PARAM_STR);
    
    $local_success = $stmt_local->execute();
    $local_id = $dbconn->lastInsertId();

    if (!$local_success || !$local_id) {
        throw new Exception("Error al guardar en base de datos local");
    }

    // ===============================
    // 4. Validar en FHIR
    // ===============================
    $validateUrl = "https://fhir-conectaton.mspbs.gov.py/fhir/Organization/\$validate";
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
    // 5. Crear Organization en FHIR con PUT
    // ===============================
    $createUrl = "https://fhir-conectaton.mspbs.gov.py/fhir/Organization/" . urlencode($code);
    $creation = sendFHIRRequest($createUrl, $orgResource, 'PUT');

    $fhir_response = json_decode($creation['body'], true);
    
    if (in_array($creation['status'], [200, 201])) {
        // Confirmar ambas operaciones
        $dbconn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Organización creada correctamente en ambas bases de datos',
            'local_id' => $local_id,
            'fhir_id' => $uuid,
            'fhir_response' => $fhir_response
        ]);
    } else {
        throw new Exception('Error al crear organización en FHIR: ' . $creation['body']);
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