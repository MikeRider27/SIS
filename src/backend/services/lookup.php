<?php

include('/var/www/html/core/connection.php');
$dbconnFHIR = getConnection();

// Consultamos el servidor terminológico disponible
$sql = "SELECT endpoint_url FROM fhir_server_endpoint WHERE endpoint_version = 'R8' AND endpoint_activo = TRUE;";
$stmt = $dbconnFHIR->prepare($sql);
$stmt->execute();
$terminology_server = $stmt->fetch(PDO::FETCH_ASSOC);

function translateCode($code, $system)
{
    global $terminology_server; // 👈 acceso a variable externa
    // Definir la URL base para la API de traducción
     $baseUrl = $terminology_server['endpoint_url'] .'/fhir/CodeSystem/$lookup';
 
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

// Configura los encabezados para permitir solicitudes desde el frontend
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Ajusta según sea necesario
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Verificar el método de la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir el JSON desde el frontend
    $body = file_get_contents('php://input');
    $inputData = json_decode($body, true);


    // Extraer los datos del cuerpo de la solicitud
    $code = $inputData['code'] ?? null;
    $system = $inputData['system'] ?? null;

    // Verificar que todos los parámetros estén presentes
    if ($code && $system) {
        // Llamar a la función para traducir el código
        $data = translateCode($code, $system);

        if (is_array($data)) {
            // Devolver la respuesta al frontend en formato JSON
            echo json_encode($data);
        } else {
            http_response_code(500);
            echo json_encode(['error' => $data]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Parámetros incompletos.']);
    }
} else {
    http_response_code(405); // Método no permitido
    echo json_encode(['error' => 'Método no permitido.']);
}
