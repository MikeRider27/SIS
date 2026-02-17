<?php

/**
 * Procesar la respuesta del servidor FHIR.
 *
 * @param string $response La respuesta en formato JSON del servidor FHIR.
 * @return array Un array con la información extraída.
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


