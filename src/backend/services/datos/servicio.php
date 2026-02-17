<?php
// Mostrar errores durante desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('/var/www/html/core/connection.php');
require_once('/var/www/html/vendor/autoload.php');

use Ramsey\Uuid\Uuid;

// Conexión a la base de datos
$dbconn = getConnectionFHIR();

if (isset($_POST['accion']) && $_POST['accion'] === "agregar") {

    // Recolección segura de variables
    $identifier         = empty(trim($_POST['id_servicio'])) ? '' : trim($_POST['id_servicio']);
    $name               = mb_strtoupper(trim($_POST['nombre_servicio']), 'UTF-8');
   $address = mb_strtoupper(trim($_POST['direccion'] ?? ''), 'UTF-8');
$department = mb_strtoupper(trim($_POST['ciudad'] ?? 'ASUNCIÓN'), 'UTF-8');

    $country            = $_POST['country'] ?? 'PRY';
  
    $type               = empty(trim($_POST['tipo'])) ? '' : mb_strtoupper(trim($_POST['tipo']), 'UTF-8');
    $code               = empty(trim($_POST['id_servicio'])) ? '' : trim($_POST['id_servicio']);
 
    try {
        // Iniciar transacción
        $dbconn->beginTransaction();

        // Inserción del nuevo profesional
        $sql = "INSERT INTO establecimiento2025 (id_establecimiento, nombre, direccion, departamento, pais, code, type)
                VALUES (:id_establecimiento, :nombre, :direccion, :departamento, :pais, :code, :type);";

        $stmt = $dbconn->prepare($sql);
        $stmt->bindParam(':id_establecimiento', $identifier);
        $stmt->bindParam(':nombre', $name);
        $stmt->bindParam(':direccion', $address);
        $stmt->bindParam(':departamento', $department);
        $stmt->bindParam(':pais', $country);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':type', $type);
        $stmt->execute();

        // Obtener ID del registro insertado
        $servicio = $dbconn->lastInsertId();

        // Confirmar transacción
        $dbconn->commit();

        if ($servicio) {
            $status = "success";
            $message = "Servicio registrado correctamente.";
        } else {
            $status = "error";
            $message = "No se pudo registrar el servicio.";
        }

        // Respuesta JSON
        echo json_encode([
            "status" => $status,
            "message" => $message
        ]);

    } catch (Exception $e) {
        // Revertir transacción ante error
        if ($dbconn->inTransaction()) {
            $dbconn->rollBack();
        }

        echo json_encode([
            "status" => "error",
            "message" => "Error al registrar: " . $e->getMessage()
        ]);
    }

} else if (isset($_POST['accion']) && $_POST['accion'] === "search") {

   // Recolección segura de variables
    $identifier        = isset($_POST['id_establecimiento']) ? trim($_POST['id_establecimiento']) : '';

    try {
    // Buscar al profesional en la base de datos
    $sql = "SELECT id, id_establecimiento, nombre, direccion, departamento, pais, code, type
            FROM establecimiento2025
            WHERE id_establecimiento = :id_establecimiento AND type IS NOT NULL
            LIMIT 1;";
    $stmt = $dbconn->prepare($sql);
    $stmt->bindValue(':id_establecimiento', $identifier, PDO::PARAM_STR);
    $stmt->execute();

    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    switch ($data['type']) {
        case 'HG':
            $data['tipo'] = 'Hospital General';
            break;
        case 'HR':
            $data['tipo'] = 'Hospital Regional';
            break;
        case 'HD':
            $data['tipo'] = 'Hospital Distrital';
            break;
        case 'HP':
            $data['tipo'] = 'Hospital Privado';
            break;
        case 'HE':
            $data['tipo'] = 'Hospital Especializado';
            break;
        case 'HESC':
            $data['tipo'] = 'Hospital Escuela';
            break;
        case 'IP':
            $data['tipo'] = 'Institución Privada';
            break;
        case 'IPS':
            $data['tipo'] = 'Instituto de Previsión Social';
            break;
        default:
            $data['tipo'] = 'Otro';
            break;
    }

    if ($data) {
        $status = "success";
        $message = "Establecimiento encontrado.";
    } else {
        $status = "error";
        $message = "No se pudo encontrar el establecimiento.";
    }

    // Respuesta JSON
    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data ?: []
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error al registrar: " . $e->getMessage()
    ]);
}
}else {
    echo json_encode([
        "status" => "error",
        "message" => "Formulario no enviado o acción inválida."
    ]);
}
?>
