<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('/var/www/html/vendor/autoload.php');
use GuzzleHttp\Client;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (!isset($input['qr'])) {
        echo json_encode(['valid' => false, 'message' => 'Falta el campo qr']);
        exit;
    }

    $qr = trim($input['qr']);

    try {
        $client = new Client(['timeout' => 20, 'verify' => false]);
        $response = $client->post('http://lacpass.create.cl:7089/decode/hcert', [
            'json' => ['qr_data' => $qr],
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $status = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        if ($status !== 200) {
            echo json_encode(['valid' => false, 'message' => "Error HTTP: $status"]);
            exit;
        }

        $decoded = json_decode($body, true);

        // Extraer datos legibles del payload (si existen)
        $payload = $decoded['payload']['-260']['-6'] ?? [];
        $vaccine = $payload['v'] ?? [];

        $result = [
            'valid' => true,
            'message' => 'Decodificación exitosa',
            'name' => $payload['n'] ?? 'Desconocido',
            'birthDate' => $payload['dob'] ?? 'Desconocido',
            'gender' => $payload['s'] ?? 'Desconocido',
            'documentType' => $payload['ndt'] ?? 'Desconocido',
            'documentNumber' => $payload['nid'] ?? 'Desconocido',
            'vaccine' => [
                'productCode' => $vaccine['vp'] ?? 'Desconocido',
                'batchNumber' => $vaccine['bo'] ?? 'Desconocido',
                'vaccinationDate' => $vaccine['dt'] ?? 'Desconocido',
                'validUntil' => $vaccine['vls'] ?? 'Desconocido'
            ],
            'decoded' => $decoded // 🔹 Incluye el JSON completo original
        ];

        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'valid' => false,
            'message' => 'Error al conectar con el servicio: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['valid' => false, 'message' => 'Método no permitido. Usa POST.']);
}
