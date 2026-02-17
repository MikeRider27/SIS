<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('/var/www/html/core/connection.php');
$dbconnFHIR = getConnectionFHIR();

// === Buscar servidor FHIR activo ===
$sql = "SELECT endpoint_url 
        FROM fhir_server_endpoint 
        WHERE endpoint_version = 'R8' 
          AND endpoint_activo = TRUE 
        LIMIT 1;";
$stmt = $dbconnFHIR->prepare($sql);
$stmt->execute();
$terminology_server = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$terminology_server || empty($terminology_server['endpoint_url'])) {
    http_response_code(500);
    echo json_encode(['error' => 'No se encontró un servidor FHIR activo.']);
    exit;
}

/**
 * Traduce un código entre sistemas terminológicos usando $translate.
 */
function translateCode($code, $system, $targetSystem)
{
    global $terminology_server;

    $baseUrl = rtrim($terminology_server['endpoint_url'], '/');
    $endpoint = $baseUrl.'/fhir/ConceptMap/$translate';

    $params = http_build_query([
        'code' => $code,
        'system' => $system,
        'targetsystem' => $targetSystem
    ]);

    $url = "$endpoint?$params";

    // 🔹 Ejecutar cURL
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => "Error en la solicitud: $error"];
    }
    curl_close($ch);

    if (!$response) {
        return ['error' => 'No se obtuvo respuesta del servidor FHIR.'];
    }

    $data = json_decode($response, true);
    if (!$data) {
        return ['error' => 'La respuesta del servidor no es JSON válido.'];
    }

    $results = [];

    // 🔹 Caso 1: formato FHIR oficial (Parameters)
    if (isset($data['parameter']) && is_array($data['parameter'])) {
        foreach ($data['parameter'] as $param) {
            if ($param['name'] === 'match' && isset($param['part'])) {
                $system = $code = $display = $equivalence = null;

                foreach ($param['part'] as $part) {
                    if ($part['name'] === 'equivalence' && isset($part['valueCode'])) {
                        $equivalence = $part['valueCode'];
                    }

                    if ($part['name'] === 'concept' && isset($part['valueCoding'])) {
                        $coding = $part['valueCoding'];
                        $system = $coding['system'] ?? '';
                        $code = $coding['code'] ?? '';
                        $display = $coding['display'] ?? '';
                    }
                }

                if ($code && $system) {
                    $results[] = [
                        'system' => $system,
                        'code' => $code,
                        'display' => $display,
                        'equivalence' => $equivalence ?? '-'
                    ];
                }
            }
        }
    }

    // 🔹 Caso 2: formato plano (no estándar pero válido)
    elseif (isset($data['system']) && isset($data['code'])) {
        $results[] = [
            'system' => $data['system'],
            'code' => $data['code'],
            'display' => $data['display'] ?? '',
            'equivalence' => 'equivalent'
        ];
    }

    if (empty($results)) {
        return ['error' => 'No se encontraron traducciones para el código solicitado.'];
    }

    return $results;
}

// === Cabeceras ===
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// === Lógica principal ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $code = $input['code'] ?? null;
    $system = $input['system'] ?? null;
    $targetSystem = $input['targetSystem'] ?? null;

    if (!$code || !$system || !$targetSystem) {
        http_response_code(400);
        echo json_encode(['error' => 'Parámetros incompletos.']);
        exit;
    }

    $result = translateCode($code, $system, $targetSystem);

    if (isset($result['error'])) {
        http_response_code(404);
    }

    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
}
?>
