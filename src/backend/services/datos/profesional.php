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
    $professional_type = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';

    try {
        // PASO 1: Buscar al profesional en la base de datos local
        $sql = "SELECT id, document, first_name, middle_name, last_name, second_last_name, code
                FROM professional
                WHERE TRIM(document) = :documento 
                LIMIT 1;";
        $stmt = $dbconn->prepare($sql);
        $stmt->bindValue(':documento', $identifier, PDO::PARAM_STR);
        $stmt->execute();

        $localData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($localData) {
            // Profesional encontrado en base de datos local
            echo json_encode([
                "status" => "success",
                "source" => "local",
                "message" => "Profesional encontrado en base local.",
                "data" => $localData
            ]);
            exit;
        }

        // PASO 2: No encontrado localmente, buscar en servidor FHIR
        $fhirData = searchInFhirServer($identifier, $professional_type);
        
        if ($fhirData) {
            // PASO 3: Guardar en base de datos local
            $savedData = saveProfessionalToLocal($dbconn, $fhirData, $professional_type);
            
            if ($savedData) {
                echo json_encode([
                    "status" => "success",
                    "source" => "fhir",
                    "message" => "Profesional encontrado en FHIR y guardado localmente.",
                    "data" => $savedData
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "source" => "fhir",
                    "message" => "Profesional encontrado en FHIR pero error al guardar localmente.",
                    "data" => $fhirData
                ]);
            }
        } else {
            // No encontrado en ninguna parte
            echo json_encode([
                "status" => "error",
                "source" => "none",
                "message" => "Profesional no encontrado en base local ni en servidor FHIR.",
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
 * Busca un profesional en el servidor FHIR
 */
function searchInFhirServer($documento, $tipo) {
    try {
        // Construir URL de búsqueda en FHIR
        $url = APP_FHIR_SERVER . '/Practitioner?identifier=' . urlencode($documento);
        
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
                $practitioner = $fhirResponse['entry'][0]['resource'];
                
                // Mapear datos FHIR a formato local
                return mapFhirToLocal($practitioner, $documento, $tipo);
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Error buscando en FHIR: " . $e->getMessage());
        return null;
    }
}

/**
 * Mapea datos del formato FHIR a formato local
 */
function mapFhirToLocal($fhirData, $documento, $tipo) {
    // Extraer nombre
    $name = $fhirData['name'][0] ?? [];
    $given = $name['given'] ?? [];
    $family = $name['family'] ?? '';
    
    // Separar apellidos (asumiendo que vienen juntos como "AGUIRRE GARCIA")
    $apellidos = explode(' ', trim($family), 2);
    $last_name = $apellidos[0] ?? '';        // Primer apellido
    $second_last_name = $apellidos[1] ?? ''; // Segundo apellido (si existe)
    
    // Separar nombres
    $first_name = $given[0] ?? '';      // Primer nombre
    $middle_name = $given[1] ?? '';     // Segundo nombre (si existe)
    
    // Extraer el valor del documento
    $document = $documento;
    
    // Generar UUID para el campo code
    $code = Uuid::uuid4()->toString();
    
    return [
        'document' => $document,
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name,
        'second_last_name' => $second_last_name,
        'code' => $code, // UUID generado
        'fhir_id' => $fhirData['id'] ?? null
    ];
}

/**
 * Guarda profesional en base de datos local
 */
function saveProfessionalToLocal($dbconn, $data, $tipo) {
    try {
        // Verificar si ya existe (por si acaso)
        $checkSql = "SELECT id FROM professional WHERE document = :document LIMIT 1";
        $checkStmt = $dbconn->prepare($checkSql);
        $checkStmt->bindValue(':document', $data['document'], PDO::PARAM_STR);
        $checkStmt->execute();
        
        if ($checkStmt->fetch()) {
            // Ya existe, obtener los datos completos
            $sql = "SELECT id, document, first_name, middle_name, last_name, second_last_name, code
                    FROM professional WHERE document = :document LIMIT 1";
            $stmt = $dbconn->prepare($sql);
            $stmt->bindValue(':document', $data['document'], PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Insertar nuevo registro (el id es auto-incrementable, no lo incluimos)
        $sql = "INSERT INTO professional (document, first_name, middle_name, last_name, second_last_name, code)
                VALUES (:document, :first_name, :middle_name, :last_name, :second_last_name, :code)
                RETURNING id, document, first_name, middle_name, last_name, second_last_name, code";
        
        $stmt = $dbconn->prepare($sql);
        $stmt->bindValue(':document', $data['document'], PDO::PARAM_STR);
        $stmt->bindValue(':first_name', $data['first_name'], PDO::PARAM_STR);
        $stmt->bindValue(':middle_name', $data['middle_name'], PDO::PARAM_STR);
        $stmt->bindValue(':last_name', $data['last_name'], PDO::PARAM_STR);
        $stmt->bindValue(':second_last_name', $data['second_last_name'], PDO::PARAM_STR);
        $stmt->bindValue(':code', $data['code'], PDO::PARAM_STR); // Aquí va el UUID
        
        $stmt->execute();
        
        // Obtener el registro insertado
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error guardando profesional localmente: " . $e->getMessage());
        return null;
    }
}