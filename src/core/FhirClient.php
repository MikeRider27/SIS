<?php
// Cliente FHIR centralizado: evita duplicar la lógica de cURL en cada servicio
// y aplica timeouts, verificación SSL configurable y logging consistentes.

if (!function_exists('sendFHIRRequest')) {
    function sendFHIRRequest($url, $resource = null, $method = 'GET') {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/fhir+json',
                'Accept: application/fhir+json'
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => defined('APP_FHIR_SSL_VERIFY') ? APP_FHIR_SSL_VERIFY : true,
            CURLOPT_SSL_VERIFYHOST => (defined('APP_FHIR_SSL_VERIFY') ? APP_FHIR_SSL_VERIFY : true) ? 2 : 0,
        ]);

        if ($resource !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($resource));
        }

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = 'Error de cURL: ' . curl_error($ch);
            error_log("[FHIR] {$method} {$url} -> {$error}");
            curl_close($ch);
            return ['status' => 500, 'body' => json_encode(['error' => $error])];
        }
        curl_close($ch);

        if ($httpcode >= 400) {
            error_log("[FHIR] {$method} {$url} -> HTTP {$httpcode}");
        }

        return ['status' => $httpcode, 'body' => $response];
    }
}

// Revisa la respuesta de $validate y determina si hay issues de severidad error/fatal
if (!function_exists('fhirHasFatalIssues')) {
    function fhirHasFatalIssues($validationResponse) {
        if (!isset($validationResponse['issue']) || !is_array($validationResponse['issue'])) {
            return false;
        }
        foreach ($validationResponse['issue'] as $issue) {
            if (in_array($issue['severity'] ?? '', ['error', 'fatal'], true)) {
                return true;
            }
        }
        return false;
    }
}

// Escapa texto que se interpola en narrativas HTML (text.div) de recursos FHIR
if (!function_exists('fhirEscape')) {
    function fhirEscape($value) {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// Corta la ejecución con una respuesta JSON de error si no hay conexión a la BD local
if (!function_exists('requireDbConnection')) {
    function requireDbConnection($dbconn) {
        if (!$dbconn) {
            http_response_code(503);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se pudo conectar a la base de datos local'
            ]);
            exit;
        }
    }
}
