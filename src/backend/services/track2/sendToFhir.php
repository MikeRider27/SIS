<?php
// Función para enviar el JSON al servidor FHIR
function sendToFhirServer($dbconnFHIR, $jsonOutput)
{
    // Consultar el servidor FHIR activo
    $sql = "SELECT endpoint_url 
            FROM fhir_server_endpoint 
            WHERE endpoint_version = 'R4' 
              AND endpoint_activo = TRUE 
            LIMIT 1;";
    $stmt = $dbconnFHIR->prepare($sql);
    $stmt->execute();
    $terminology_server = $stmt->fetch(PDO::FETCH_ASSOC);

    // Define la URL del servidor FHIR
    $fhirUrl = rtrim($terminology_server['endpoint_url'], '/') . '/fhir';
   
    // Configurar la solicitud cURL
    $ch = curl_init($fhirUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_POSTFIELDS => $jsonOutput,
        CURLOPT_TIMEOUT => 30, // evita bloqueos si el servidor no responde
    ]);

    $response = curl_exec($ch);

    // Validar errores de cURL
    if ($response === false) {
        $errorMsg = 'Error al enviar los datos al servidor FHIR: ' . curl_error($ch);
        curl_close($ch);
        return ['error' => $errorMsg];
    }

    curl_close($ch);
    return $response;
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
