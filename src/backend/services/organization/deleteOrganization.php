<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, GET');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

include('/var/www/html/core/connection.php');
require_once('/var/www/html/vendor/autoload.php');

use Ramsey\Uuid\Uuid;

function sendFHIRRequest($url, $resource = null, $method) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/fhir+json',
        'Accept: application/fhir+json'
    ]);
    
    if ($resource !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($resource));
    }

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = 'Error de cURL: ' . curl_error($ch);
        curl_close($ch);
        return ['status' => 500, 'body' => json_encode(['error' => $error])];
    }
    curl_close($ch);

    return ['status' => $httpcode, 'body' => $response];
}

// ===============================
// 1. Obtener el ID a eliminar
// ===============================
// Puede venir por GET, POST o DELETE
$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Falta ID de Organization (puede ser id, identifier o fhir_id)'
    ]);
    exit;
}

// Conexión a la base de datos local
$dbconn = getConnection();

try {
    // ===============================
    // 2. Buscar la organización a eliminar
    // ===============================
    $sql_find = "SELECT id, identifier, name, type FROM organization WHERE identifier = :identifier";
    $stmt_find = $dbconn->prepare($sql_find);
    $stmt_find->bindValue(':identifier', $id, PDO::PARAM_STR);
    $stmt_find->execute();
    
    $organization = $stmt_find->fetch(PDO::FETCH_ASSOC);
    
    if (!$organization) {
        echo json_encode([
            'status' => 'error',
            'message' => 'NO_EXISTE_LOCAL',
            'details' => 'La organización no existe en la base de datos local'
        ]);
        exit;
    }
    
    // ===============================
    // 3. Verificar si existe en FHIR
    // ===============================
    $fhir_id_to_delete = $organization['fhir_id'] ?? $organization['identifier'];
    $searchUrl = APP_FHIR_SERVER . "/Organization?identifier=" . urlencode($organization['identifier']);
    $searchResult = sendFHIRRequest($searchUrl, null, 'GET');
    
    $exists_in_fhir = false;
    $fhir_resource_id = null;
    
    if ($searchResult['status'] === 200) {
        $searchData = json_decode($searchResult['body'], true);
        
        if (isset($searchData['total']) && $searchData['total'] > 0) {
            $exists_in_fhir = true;
            $fhir_resource_id = $searchData['entry'][0]['resource']['id'] ?? null;
        }
    }
    
    // ===============================
    // 4. Iniciar transacción
    // ===============================
    $dbconn->beginTransaction();
    
    // ===============================
    // 5. Eliminar de FHIR (si existe)
    // ===============================
    if ($exists_in_fhir) {
        $deleteUrl = APP_FHIR_SERVER . "/Organization/" . urlencode($fhir_resource_id ?: $fhir_id_to_delete);
        $deletion = sendFHIRRequest($deleteUrl, null, 'DELETE');
        
        if (!in_array($deletion['status'], [200, 204])) {
            throw new Exception('Error al eliminar en FHIR: ' . $deletion['body']);
        }
    }
    
    // ===============================
    // 6. Eliminar de base de datos local
    // ===============================
    $sql_delete = "DELETE FROM organization WHERE id = :id";
    $stmt_delete = $dbconn->prepare($sql_delete);
    $stmt_delete->bindValue(':id', $organization['id'], PDO::PARAM_INT);
    $delete_success = $stmt_delete->execute();
    
    if (!$delete_success || $stmt_delete->rowCount() === 0) {
        throw new Exception("Error al eliminar de base de datos local");
    }
    
    // ===============================
    // 7. Confirmar transacción
    // ===============================
    $dbconn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Organización eliminada correctamente',
        'details' => [
            'local_id' => $organization['id'],
            'identifier' => $organization['identifier'],
            'name' => $organization['name'],
            'fhir_deleted' => $exists_in_fhir,
            'fhir_id' => $fhir_resource_id ?: $fhir_id_to_delete
        ]
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($dbconn) && $dbconn->inTransaction()) {
        $dbconn->rollBack();
    }
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en el proceso de eliminación: ' . $e->getMessage()
    ]);
}
?>