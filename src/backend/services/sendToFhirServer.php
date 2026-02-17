<?php

/**
 * Enviar datos a un servidor FHIR.
 *
 * @param string $serverUrl La URL del servidor FHIR.
 * @param string $jsonOutput Los datos en formato JSON para enviar.
 * @return string La respuesta del servidor FHIR.
 */

 function processFhirResponse($response) {
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'error' => 'Error al decodificar JSON: ' . json_last_error_msg()
        ];
    }

    $result = [
        'resourceType' => $data['resourceType'] ?? 'Unknown',
        'entries' => []
    ];

    if (isset($data['entry'])) {
        foreach ($data['entry'] as $entry) {
            $response = $entry['response'] ?? [];
            $result['entries'][] = [
                'status' => $response['status'] ?? 'Unknown',
                'location' => $response['location'] ?? 'Unknown',
                'etag' => $response['etag'] ?? 'Unknown',
                'lastModified' => $response['lastModified'] ?? 'Unknown',
                'outcome' => $response['outcome']['issue'][0]['diagnostics'] ?? 'No details available'
            ];
        }
    }

    return $result;
}




function sendToFhsdirServer($serverUrl, $jsonOutput) {
    $ch = curl_init($serverUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json', // Cambiado a application/json para coincidir con el comando curl
        'Accept: application/json' // Cambiado a application/json para coincidencia
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonOutput);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}


