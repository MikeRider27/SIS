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

 if (isset($_POST['accion']) && $_POST['accion'] === "search") {

   // Recolección segura de variables
    $identifier        = isset($_POST['documento']) ? trim($_POST['documento']) : '';
    $professional_type = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';

    try {
    // Buscar al profesional en la base de datos
    $sql = "SELECT id, documento, pnombre, papellido, snombre, sapellido, fechanac, sexo, pais, id_colegio, tipo, code
            FROM public.profesional2025 
            WHERE TRIM(documento) = :documento AND TRIM(tipo) = :tipo 
            LIMIT 1;";
    $stmt = $dbconn->prepare($sql);
    $stmt->bindValue(':documento', $identifier, PDO::PARAM_STR);
    $stmt->bindValue(':tipo', $professional_type, PDO::PARAM_STR);
    $stmt->execute();

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $status = "success";
        $message = "Profesional encontrado.";
    } else {
        $status = "error";
        $message = "No se pudo encontrar el profesional.";
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
