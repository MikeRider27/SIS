<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 🔹 Validar parámetro URL
$url = isset($_GET['url']) ? trim($_GET['url']) : '';

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el parámetro "url"']);
    exit;
}

// 🔹 Inicializar cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_CONNECTTIMEOUT => 10, // tiempo para conectar
    CURLOPT_TIMEOUT => 20,        // tiempo total permitido
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // fuerza IPv4
    CURLOPT_NOSIGNAL => true,                // evita bloqueos internos
    CURLOPT_PROXY => '',                     // desactiva proxy si lo hay
    CURLOPT_HTTPHEADER => [
        'Accept: application/fhir+json'
    ]
]);

// 🔹 Ejecutar solicitud
$response = curl_exec($ch);
$error = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$info = curl_getinfo($ch);
curl_close($ch);

// 🔹 Manejar errores de cURL
if ($error) {
    error_log("❌ Error cURL: $error - URL: $url");
    http_response_code(500);
    echo json_encode([
        'error' => "Error cURL: $error",
        'url' => $url
    ]);
    exit;
}

// 🔹 Manejar códigos HTTP diferentes a 200
if ($code !== 200) {
    http_response_code($code);
    echo json_encode([
        'error' => "El servidor devolvió HTTP $code",
        'url' => $url
    ]);
    exit;
}

// 🔹 Registrar tiempo total de respuesta (para debug)
$tiempo = round($info['total_time'], 2);
error_log("✅ FHIR Bundle obtenido correctamente en {$tiempo}s desde $url");

// 🔹 Enviar respuesta FHIR al frontend
echo $response;
?>
