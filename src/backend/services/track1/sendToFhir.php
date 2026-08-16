<?php
// Función para enviar el JSON al servidor FHIR
function sendToFhirServer($fhirServerUrl, $jsonOutput)
{
    $ch = curl_init($fhirServerUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/fhir+json',
            'Accept: application/fhir+json'
        ],
        CURLOPT_POSTFIELDS => $jsonOutput,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30, // evita bloqueos si el servidor no responde
    ]);

    $response = curl_exec($ch);

    // Validar errores de cURL
    if ($response === false) {
        $errorMsg = 'Error al enviar los datos al servidor FHIR: ' . curl_error($ch);
        curl_close($ch);
        return ['status' => 0, 'error' => $errorMsg];
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $status, 'body' => $response];
}

// Función para procesar la respuesta del servidor FHIR
function processFhirResponse($response)
{
    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'error' => 'Error al decodificar JSON: ' . json_last_error_msg()
        ];
    }

    $result = [
        'id' => $data['id'] ?? null,
        'type' => $data['type'] ?? null,
        'resources' => []
    ];

    // Recorremos las entradas del Bundle para obtener los IDs creados
    if (!empty($data['entry']) && is_array($data['entry'])) {
        foreach ($data['entry'] as $entry) {
            if (!empty($entry['response']['location'])) {
                $location = $entry['response']['location'];

                // Ejemplo: "List/1326/_history/1"
                if (preg_match('/^([^\/]+)\/([^\/]+)/', $location, $matches)) {
                    $resourceType = $matches[1];
                    $resourceId = $matches[2];
                    $status = $entry['response']['status'] ?? 'Desconocido';

                    $result['resources'][] = [
                        'type' => $resourceType,
                        'id' => $resourceId,
                        'status' => $status
                    ];
                }
            }
        }
    }

    // 👉 devolvemos array directamente (no JSON codificado)
    return $result;
}
