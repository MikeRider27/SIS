<?php

function translate($code, $system)
{
    // Definir la URL base para la API de traducción
     $baseUrl = 'https://snowstorm.mspbs.gov.py/fhir/CodeSystem/$lookup';
  // $baseUrl = 'https://149.202.25.58:11034/fhir/CodeSystem/$lookup';
    //$baseUrl = 'https://gazelle.racsel.org:11034/fhir/ConceptMap/$translate';

    // Preparar los parámetros de consulta
    $queryParams = http_build_query([
        'system' => $system,
        'code' => $code        
    ]);

    // Construir la URL completa con los parámetros
    $url = "$baseUrl?$queryParams";

    // Inicializar cURL
    $ch = curl_init();

    // Configurar las opciones de cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Para que devuelva el resultado como una cadena
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

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

    // Inicializar variables
    $system1 = $version = $display1 = null;

    // Recorrer los parámetros y asignar los valores a las variables correspondientes
    foreach ($data['parameter'] as $param) {
        switch ($param['name']) {
            case 'system':
                $system1 = $param['valueString'];
                break;
            case 'version':
                $version = $param['valueString'];
                break;
            case 'display':
                $display1 = $param['valueString'];
                break;
        }
    }



    $result = [
        'system' => $system1,
        'display' => $display1,
        'version' => $version
    ];

    //devolvemos un json
    return $result;


    // En caso de que no sea exitoso o no tenga la estructura esperada
    return 'No se pudo traducir el código.';
}

