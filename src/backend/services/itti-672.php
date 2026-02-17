<?php
include('/var/www/html/core/connection.php');

// Función para hacer una solicitud GET al servidor FHIR
function fetchPatientData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/fhir+json'    
    ));
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode(['error' => 'Error de cURL: ' . curl_error($ch)]);
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    return $response;
}

// Configura los encabezados
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Recupera el parámetro
$identifier = isset($_GET['identifier']) ? $_GET['identifier'] : '';

// Construye la URL para DocumentReference
$url = "https://fhir-conectaton.mspbs.gov.py/fhir/DocumentReference/?patient.identifier=$identifier&_format=json&status=current";

// 1. Obtener DocumentReference
$data = fetchPatientData($url);

if ($data) {
    $bundle = json_decode($data, true);

    $orgAdded = false;

    // 2. Revisar si hay custodio
    if (isset($bundle['entry'][0]['resource']['custodian']['reference'])) {
        $custodianRef = $bundle['entry'][0]['resource']['custodian']['reference']; 

        // 3. Hacer GET al Organization
        $orgUrl = "https://fhir-conectaton.mspbs.gov.py/fhir/$custodianRef";
        $orgData = fetchPatientData($orgUrl);
        if ($orgData) {
            $orgResource = json_decode($orgData, true);

            // 4. Agregar Organization al bundle
            $bundle['entry'][] = [
                "fullUrl" => $orgUrl,
                "resource" => $orgResource
            ];
            $orgAdded = true;
        }
    }

    // 5. Si no existe custodian o falló la consulta, agregar Organización por defecto
    if (!$orgAdded) {
        $defaultOrg = [
            "resourceType" => "Organization",
            "id" => "MSPBS",
            "identifier" => [
                [ "value" => "MSPBS" ]
            ],
            "type" => [
                [ "text" => "Gobierno" ]
            ],
            "name" => "MINISTERIO DE SALUD"
        ];

        $bundle['entry'][] = [
            "fullUrl" => "https://mspbs.gov.py/fhir/Organization/MSPBS",
            "resource" => $defaultOrg
        ];
    }

    echo json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener datos.']);
}
?>
