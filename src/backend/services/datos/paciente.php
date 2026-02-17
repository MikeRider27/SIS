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
    $identifier         = trim($_POST['identificacion']) ?? '';
    $name               = mb_strtoupper(trim($_POST['nombre']), 'UTF-8');
    $last_name          = mb_strtoupper(trim($_POST['apellido']), 'UTF-8');
    $gender             = $_POST['sexo'] ?? '';
    $sexoFHIR = "unknown";
    if ($gender == "1") $sexoFHIR = "female";
    if ($gender == "2") $sexoFHIR = "male";
    $birth_date         = $_POST['fecha_nacimiento'] ?? '';
    $country            = $_POST['country'] ?? '';
    $code               = Uuid::uuid4()->toString();

    try {
        // Iniciar transacción
        $dbconn->beginTransaction();

        // Inserción del nuevo profesional
        $sql = "INSERT INTO paciente2026 (documento, nombre, apellido, fechanac, sexo, pais, code)
                VALUES (:documento, :nombre, :apellido, :fechanac, :sexo, :pais, :code)";

        $stmt = $dbconn->prepare($sql);
        $stmt->bindValue(':documento', $identifier, PDO::PARAM_STR);
        $stmt->bindValue(':nombre', $name, PDO::PARAM_STR);
        $stmt->bindValue(':apellido', $last_name, PDO::PARAM_STR);
        $stmt->bindValue(':fechanac', $birth_date, PDO::PARAM_STR);
        $stmt->bindValue(':sexo', $sexoFHIR, PDO::PARAM_STR);
        $stmt->bindValue(':pais', $country, PDO::PARAM_STR);
        $stmt->bindValue(':code', $code, PDO::PARAM_STR);       
        $stmt->execute();

        // Obtener ID del registro insertado
        $paciente = $dbconn->lastInsertId();

        // Confirmar transacción
        $dbconn->commit();

        if ($paciente) {
            $status = "success";
            $message = "Paciente registrado correctamente.";
        } else {
            $status = "error";
            $message = "No se pudo registrar el paciente.";
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
    $identifier        = isset($_POST['documento']) ? trim($_POST['documento']) : '';
    $type    = isset($_POST['type']) ? trim($_POST['type']) : '';


    try {
    // Buscar al profesional en la base de datos
    $sql = "SELECT id, tipo, codetipo, documento, pnombre, snombre, papellido, sapellido, fechanac, sexo, code
            FROM public.paciente2026 
            WHERE documento = :documento AND tipo = :type
            LIMIT 1;";
    $stmt = $dbconn->prepare($sql);
    $stmt->bindValue(':documento', $identifier, PDO::PARAM_STR);
    $stmt->bindValue(':type', $type, PDO::PARAM_STR);
    $stmt->execute();

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $status = "success";
        $message = "Paciente encontrado.";
    } else {
        $status = "error";
        $message = "No se pudo encontrar el paciente.";
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
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Formulario no enviado o acción inválida."
    ]);
}
?>
