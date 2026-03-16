<?php
// Mostrar errores durante desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('/var/www/html/core/connection.php');
require_once('/var/www/html/vendor/autoload.php');

use Ramsey\Uuid\Uuid;

// Conexión a la base de datos
$dbconn = getConnection();

if (isset($_POST['accion']) && $_POST['accion'] === "search") {

    // Recolección segura de variables
    $identifier = isset($_POST['documento']) ? trim($_POST['documento']) : '';
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';

    try {
        // PASO 1: Buscar al paciente en la base de datos local
        $sql = "SELECT id, type_code, document, first_name, middle_name, last_name, second_last_name, birth_date, gender, code
                FROM patient 
                WHERE document = :documento AND type_code = :type
                LIMIT 1;";
        $stmt = $dbconn->prepare($sql);
        $stmt->bindValue(':documento', $identifier, PDO::PARAM_STR);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->execute();

        $localData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($localData) {
            // Paciente encontrado en base de datos local
            echo json_encode([
                "status" => "success",
                "source" => "local",
                "message" => "Paciente encontrado en base local.",
                "data" => $localData
            ]);
            exit;
        }

        // PASO 2: No encontrado localmente, buscar en servidor FHIR
        $fhirData = searchPatientInFhirServer($identifier, $type);
        
        if ($fhirData) {
            // PASO 3: Guardar en base de datos local
            $savedData = savePatientToLocal($dbconn, $fhirData, $type);
            
            if ($savedData) {
                echo json_encode([
                    "status" => "success",
                    "source" => "fhir",
                    "message" => "Paciente encontrado en FHIR y guardado localmente.",
                    "data" => $savedData
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "source" => "fhir",
                    "message" => "Paciente encontrado en FHIR pero error al guardar localmente.",
                    "data" => $fhirData
                ]);
            }
        } else {
            // No encontrado en ninguna parte
            echo json_encode([
                "status" => "error",
                "source" => "none",
                "message" => "Paciente no encontrado en base local ni en servidor FHIR.",
                "data" => []
            ]);
        }

    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Error en el proceso: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Formulario no enviado o acción inválida."
    ]);
}

/**
 * Busca un paciente en el servidor FHIR
 */
function searchPatientInFhirServer($documento, $type) {
    try {
        // Construir URL de búsqueda en FHIR
        $url = APP_FHIR_SERVER . '/Patient?identifier=' . urlencode($documento);
        
        // Inicializar cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Ejecutar petición
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $fhirResponse = json_decode($response, true);
            
            // Verificar si hay resultados
            if (isset($fhirResponse['entry']) && count($fhirResponse['entry']) > 0) {
                // Tomar el primer resultado
                $patient = $fhirResponse['entry'][0]['resource'];
                
                // Mapear datos FHIR a formato local
                return mapFhirPatientToLocal($patient, $documento, $type);
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Error buscando paciente en FHIR: " . $e->getMessage());
        return null;
    }
}

/**
 * Mapea datos del paciente desde formato FHIR a formato local
 */
function mapFhirPatientToLocal($fhirData, $documento, $type) {
    // Extraer nombre
    $name = $fhirData['name'][0] ?? [];
    $given = $name['given'] ?? [];
    $family = $name['family'] ?? '';
    $id = $fhirData['id'] ?? null; // ID del recurso FHIR
    
    // Separar apellidos (asumiendo que vienen juntos como "VILLALBA CABAÑAS")
    $apellidos = explode(' ', trim($family), 2);
    $last_name = $apellidos[0] ?? '';        // Primer apellido
    $second_last_name = $apellidos[1] ?? ''; // Segundo apellido (si existe)
    
    // Separar nombres
    $first_name = $given[0] ?? '';      // Primer nombre
    $middle_name = $given[1] ?? '';     // Segundo nombre (si existe)
    
    // Extraer fecha de nacimiento
    $birth_date = $fhirData['birthDate'] ?? null;
    
    // Extraer género (mapear de FHIR a tu formato)
    $gender = $fhirData['gender'] ?? '';
    
    // Cargamo con el ID del recurso FHIR para referencia futura
    $code = $id;
    
    return [
        'document' => $documento,
        'type_code' => $type,
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name,
        'second_last_name' => $second_last_name,
        'birth_date' => $birth_date,
        'gender' => $gender,
        'code' => $code, // UUID generado
        'fhir_id' => $fhirData['id'] ?? null
    ];
}



/**
 * Guarda paciente en base de datos local
 */
function savePatientToLocal($dbconn, $data, $type) {
    try {
        // Verificar si ya existe (por si acaso)
        $checkSql = "SELECT id FROM patient WHERE document = :document AND type_code = :type_code LIMIT 1";
        $checkStmt = $dbconn->prepare($checkSql);
        $checkStmt->bindValue(':document', $data['document'], PDO::PARAM_STR);
        $checkStmt->bindValue(':type_code', $type, PDO::PARAM_STR);
        $checkStmt->execute();
        
        if ($checkStmt->fetch()) {
            // Ya existe, obtener los datos completos
            $sql = "SELECT id, type_code, document, first_name, middle_name, last_name, second_last_name, birth_date, gender, code
                    FROM patient 
                    WHERE document = :document AND type_code = :type_code 
                    LIMIT 1";
            $stmt = $dbconn->prepare($sql);
            $stmt->bindValue(':document', $data['document'], PDO::PARAM_STR);
            $stmt->bindValue(':type_code', $type, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Insertar nuevo registro
        $sql = "INSERT INTO patient (type_code, document, first_name, middle_name, last_name, second_last_name, birth_date, gender, code)
                VALUES (:type_code, :document, :first_name, :middle_name, :last_name, :second_last_name, :birth_date, :gender, :code)
                RETURNING id, type_code, document, first_name, middle_name, last_name, second_last_name, birth_date, gender, code";
        
        $stmt = $dbconn->prepare($sql);
        $stmt->bindValue(':type_code', $data['type_code'], PDO::PARAM_STR);
        $stmt->bindValue(':document', $data['document'], PDO::PARAM_STR);
        $stmt->bindValue(':first_name', $data['first_name'], PDO::PARAM_STR);
        $stmt->bindValue(':middle_name', $data['middle_name'], PDO::PARAM_STR);
        $stmt->bindValue(':last_name', $data['last_name'], PDO::PARAM_STR);
        $stmt->bindValue(':second_last_name', $data['second_last_name'], PDO::PARAM_STR);
        $stmt->bindValue(':birth_date', $data['birth_date'], PDO::PARAM_STR);
        $stmt->bindValue(':gender', $data['gender'], PDO::PARAM_STR);
        $stmt->bindValue(':code', $data['code'], PDO::PARAM_STR);
        
        $stmt->execute();
        
        // Obtener el registro insertado
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error guardando paciente localmente: " . $e->getMessage());
        return null;
    }
}
?>