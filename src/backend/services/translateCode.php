<?php

function translateCode($code, $system, $targetSystem)
{
    // Definir la URL base para la API de traducción
    $baseUrl = 'https://snowstorm.mspbs.gov.py/fhir/ConceptMap/$translate';

    // Preparar los parámetros de consulta
    $queryParams = http_build_query([
        'code' => $code,
        'system' => $system,
        'targetsystem' => $targetSystem
    ]);

    // Construir la URL completa con los parámetros
    $url = "$baseUrl?$queryParams";

    // Inicializar cURL
    $ch = curl_init();

    // Configurar las opciones de cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Para que devuelva el resultado como una cadena
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Omitir verificación SSL si es necesario (no recomendado en producción)

    // Ejecutar la solicitud
    $response = curl_exec($ch);

    // Verificar si hubo errores
    if (curl_errno($ch)) {
        return 'Error en la solicitud: ' . curl_error($ch);
    }

    // Cerrar cURL
    curl_close($ch);

    // Decodificar la respuesta JSON
    $data = json_decode($response, true);

    // Verificar si el resultado es exitoso
    if (isset($data['parameter'][0]['valueBoolean']) && $data['parameter'][0]['valueBoolean'] === true) {
        // Extraer el sistema, código y display
        $concept = $data['parameter'][1]['part'][1]['valueCoding'];
        $result = [
            'system' => $concept['system'],
            'code' => $concept['code'],
            'display' => $concept['display']
        ];

        return $result;
    }

    // En caso de que no sea exitoso o no tenga la estructura esperada
    return 'No se pudo traducir el código.';
}
