<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function translateCode($dbconnFHIR, $conceptMap, $system, $source, $target, $code)
{
    // === 1️⃣ Buscar servidor FHIR activo (terminology server R8) ===
    $sql = "SELECT endpoint_url 
            FROM fhir_server_endpoint 
            WHERE endpoint_version = 'R8' 
              AND endpoint_activo = TRUE 
            LIMIT 1;";
    $stmt = $dbconnFHIR->prepare($sql);
    $stmt->execute();
    $terminology_server = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$terminology_server || empty($terminology_server['endpoint_url'])) {
        return ['error' => 'No se encontró un servidor FHIR activo (R8).'];
    }

    // === 2️⃣ Construir endpoint base ===
    $baseUrl = rtrim($terminology_server['endpoint_url'], '/') . '/fhir/ConceptMap/$translate';

    // === 3️⃣ Construir parámetros ===
    $params = array_filter([
        'url'    => $conceptMap,
        'system' => $system,
        'source' => $source,
        'target' => $target,
        'code'   => $code
    ]);

    $fullUrl = $baseUrl . '?' . http_build_query($params);

    // === 4️⃣ Ejecutar solicitud HTTP ===
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $fullUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false, // ⚠️ Solo desactivar en entorno de prueba
        CURLOPT_HTTPHEADER     => ['Accept: application/fhir+json']
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => "Error en la solicitud: $error"];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => "Error HTTP $httpCode", 'response' => $response];
    }

    // === 5️⃣ Decodificar respuesta ===
    $data = json_decode($response, true);

    if (!isset($data['parameter'][0]['valueBoolean']) || $data['parameter'][0]['valueBoolean'] !== true) {
        return ['code' => null, 'display' => null];
    }

    // === 6️⃣ Extraer solo code y display ===
    foreach ($data['parameter'] as $param) {
        if ($param['name'] === 'match' && isset($param['part'])) {
            foreach ($param['part'] as $part) {
                if ($part['name'] === 'concept' && isset($part['valueCoding'])) {
                    return [
                        'code'    => $part['valueCoding']['code'] ?? null,
                        'display' => $part['valueCoding']['display'] ?? null
                    ];
                }
            }
        }
    }

    // 🔹 Si no hay coincidencias válidas
    return ['code' => null, 'display' => null];
}
