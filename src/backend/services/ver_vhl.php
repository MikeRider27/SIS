<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

require_once('/var/www/html/vendor/autoload.php');
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'valid' => false,
        'message' => 'Método de solicitud no permitido.'
    ]);
    exit;
}

// Leer cuerpo
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Validar campos requeridos
if (empty($input['qr'])) {
    echo json_encode([
        'valid' => false,
        'message' => 'Codigo QR requeridos.'
    ]);
    exit;
}

$codigo = $input['codigo'] ?? '';            // passcode opcional que envía el cliente
$qrCodeContent = $input['qr'];               // contenido del QR

// Respuesta acumulada (siempre devolvemos algo útil)
$responseOut = [
    'valid'            => false,
    'message'          => '',
    'steps'            => null,              // siempre incluiremos validationStatus aquí
    'hasSHL'           => false,             // indica si hay Smart Health Link (shLinkContent.url)
    'manifestURL'      => null,
    'fileLocation'     => null,
    'patientName'      => null,
    'bundle'           => null,
    'rawValidation'    => null,              // respuesta cruda de validación por si necesitas depurar
    'rawManifest'      => null,              // respuesta cruda del manifiesto (si aplica)
    'errors'           => []                 // acumulamos errores no fatales
];

try {
    $client = new Client([
        'verify' => false, // habilitar solo si tienes problemas de CA/SSL en desarrollo
        'timeout' => 30
    ]);

    // Paso 1: Validación del QR contra GDHCN
    $data = [
        "qrCodeContent" => $qrCodeContent,
        "passCode"      => $codigo
    ];

    try {
        $validationResponse = $client->post('https://gdncn.mspbs.gov.py/v2/vshcValidation', [
            'json'    => $data,
            'headers' => ['Content-Type' => 'application/json']
        ]);
        $validationData = json_decode($validationResponse->getBody()->getContents(), true);
    } catch (RequestException $e) {
        // Si falla el request, devolvemos lo que tengamos y el detalle del error
        $responseOut['message'] = 'Error al conectar con vshcValidation.';
        $responseOut['errors'][] = [
            'stage'   => 'validation',
            'type'    => 'RequestException',
            'details' => $e->getResponse() ? (string)$e->getResponse()->getBody() : $e->getMessage()
        ];
        echo json_encode($responseOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Guardamos crudo y pasos SIEMPRE
    $responseOut['rawValidation'] = $validationData;
    $responseOut['steps'] = $validationData['validationStatus'] ?? null;

    // ¿Existe SHL?
    $manifestURL = $validationData['shLinkContent']['url'] ?? null;
    $responseOut['manifestURL'] = $manifestURL;
    $responseOut['hasSHL'] = !empty($manifestURL);

    // Si NO hay SHL, devolvemos igual todos los pasos, sin error fatal
    if (empty($manifestURL)) {
        $responseOut['valid'] = true;
        $responseOut['message'] = 'Validación ejecutada. No se recibió Smart Health Link (shLinkContent) en la respuesta. Revisa el paso 6 (FETCH_PUBLIC_KEY_GDHCN).';
        echo json_encode($responseOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Paso 2: POST al manifiesto (solo si tenemos manifestURL)
    // Usa el passcode que te envió el cliente si lo tienes; si no, puedes setear uno por defecto.
    $manifestBody = [
        'recipient' => 'LACPass postman client',
        'passcode'  => $codigo !== '' ? $codigo : '1234'
    ];

    try {
        $manifestResponse = $client->post($manifestURL, [
            'headers' => ['Content-Type' => 'application/json'],
            'json'    => $manifestBody
        ]);
        $manifestData = json_decode($manifestResponse->getBody()->getContents(), true);
        $responseOut['rawManifest'] = $manifestData;
    } catch (RequestException $e) {
        $responseOut['valid'] = true; // la validación general se hizo; solo falló el manifiesto
        $responseOut['message'] = 'Se obtuvo SHL, pero falló la solicitud al manifiesto.';
        $responseOut['errors'][] = [
            'stage'   => 'manifest',
            'type'    => 'RequestException',
            'details' => $e->getResponse() ? (string)$e->getResponse()->getBody() : $e->getMessage()
        ];
        echo json_encode($responseOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fileLocation = $manifestData['files'][0]['location'] ?? null;
    $responseOut['fileLocation'] = $fileLocation;

    if (empty($fileLocation)) {
        $responseOut['valid'] = true;
        $responseOut['message'] = 'Se obtuvo SHL, pero el manifiesto no incluyó files[0].location.';
        echo json_encode($responseOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Paso 3: Descargar el Bundle
    try {
        $bundleResponse = $client->get($fileLocation, [
            'headers' => ['Accept' => 'application/json']
        ]);
        $bundleData = json_decode($bundleResponse->getBody()->getContents(), true);
        $responseOut['bundle'] = $bundleData;
    } catch (RequestException $e) {
        $responseOut['valid'] = true;
        $responseOut['message'] = 'Se obtuvo SHL y el manifiesto, pero falló la descarga del Bundle.';
        $responseOut['errors'][] = [
            'stage'   => 'bundle_download',
            'type'    => 'RequestException',
            'details' => $e->getResponse() ? (string)$e->getResponse()->getBody() : $e->getMessage()
        ];
        echo json_encode($responseOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Extraer Patient.name
    $patientName = null;
    if (isset($bundleData['entry']) && is_array($bundleData['entry'])) {
        foreach ($bundleData['entry'] as $entry) {
            $res = $entry['resource'] ?? null;
            if (($res['resourceType'] ?? null) === 'Patient') {
                if (!empty($res['name'][0]['text'])) {
                    $patientName = $res['name'][0]['text'];
                } elseif (!empty($res['name'][0]['given']) || !empty($res['name'][0]['family'])) {
                    $given  = isset($res['name'][0]['given']) ? implode(' ', (array)$res['name'][0]['given']) : '';
                    $family = $res['name'][0]['family'] ?? '';
                    $patientName = trim($given . ' ' . $family);
                }
                break;
            }
        }
    }
    $responseOut['patientName'] = $patientName;

    // Éxito total del flujo
    $responseOut['valid']   = true;
    $responseOut['message'] = 'Validación ejecutada. SHL obtenido y Bundle descargado.';

    echo json_encode($responseOut, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'valid'   => false,
        'message' => 'Error inesperado: ' . $e->getMessage(),
        'details' => null
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
