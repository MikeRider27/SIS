<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../core/connection.php');
$dbconn = getConnection();
$dbconnFHIR = getConnectionFHIR();
include('./services/generateIPS.php');
require_once('/var/www/html/vendor/autoload.php');

// Asegúrate de cargar el autoload de Composer
use Ramsey\Uuid\Uuid;


// Verificar si el usuario está logueado
if (isset($_SESSION['idUsuario'])) {
  if (isset($_POST['accion']) && $_POST['accion'] === "agregar") {
    // Inicializar variables
    $codigo_usuario = $_SESSION['idUsuario'];
    $passmed = $_POST['passmed'];
    $nombremed = $_POST['nombremed'];
    $apellidomed = $_POST['apellidomed'];
    $sexomed = $_POST['sexomed'];
    $fecha_nacimientomed = $_POST['fecha_nacimientomed'];
    $nacionalidadmed = $_POST['nacionalidadmed'];
    $id_colegio_medico = $_POST['id_colegio_medico'];
    $fecha_acto_medico = $_POST['fecha_acto_medico'];
    $establecimiento = $_POST['establecimiento'];
    $id_servicio = $_POST['id_servicio'];
    $direccion = $_POST['direccion'];
    $departamentomed = $_POST['departamentomed'];
    $paismed = $_POST['paismed'];


    $cedula = $_POST['cedula'];
    $pasaporte = $_POST['pasaporte'];
    $pnombre = $_POST['pnombre'];
    $snombre = $_POST['snombre'];
    $papellido = $_POST['papellido'];
    $sapellido = $_POST['sapellido'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $sexo = $_POST['sexo'];
    $pais = $_POST['pasi'];

    // Inicializar variables enfermero  
    $pass_vac = $_POST['pass_vac'];
    $nombre_vac = $_POST['nombre_vac'];
    $apellido_vac = $_POST['apellido_vac'];
    $sexo_vac = $_POST['sexo_vac'];
    $fecha_nacimiento_vac = $_POST['fecha_nacimiento_vac'];
    $nacionalidad_vac = $_POST['nacionalidad_vac'];
    $id_funcionario = $_POST['id_funcionario'];
    $fecha_acto_vac = $_POST['fecha_acto_vac'];

    // Inicializar variables vacuna




    // Verificar si existe el header del usuario
    $sql4 = "SELECT codigo_region, subregion, codigo_distrito, codigo_establecimiento, tipo_consulta, codigo_profesional, codtprof, especialidad, codigo_usuario, estado 
                FROM header 
                WHERE estado = TRUE AND codigo_usuario = :codigo_usuario";
    $stmt4 = $dbconn->prepare($sql4);
    $stmt4->bindParam(':codigo_usuario', $codigo_usuario, PDO::PARAM_INT);
    $stmt4->execute();
    $datos = $stmt4->fetch(PDO::FETCH_ASSOC);

    // GENERAMOS LA CONSULTA
    $sql5 = "SELECT MAX(unrocons) + 1 AS unrocons, MAX(unroficha) + 1 AS unroficha 
                FROM establecimientos 
                WHERE codreg = :codreg AND subcreg = :subcreg AND coddist = :coddist AND codserv = :codserv";
    $stmt5 = $dbconn->prepare($sql5);
    $stmt5->bindParam(':codreg', $datos['codigo_region']);
    $stmt5->bindParam(':subcreg', $datos['subregion']);
    $stmt5->bindParam(':coddist', $datos['codigo_distrito']);
    $stmt5->bindParam(':codserv', $datos['codigo_establecimiento']);
    $stmt5->execute();
    $max = $stmt5->fetch(PDO::FETCH_ASSOC);

    // Fecha y hora actual
    $fecha_actual = date('Y-m-d');
    $hora_actual = date('H:i:s');

    // Obtener y limpiar datos de diagnósticos CIE10
    $codigosCIE = isset($_POST['labItemCIE']) ? array_filter($_POST['labItemCIE'], 'trim') : [];
    $motivosCIE = isset($_POST['labitemMotivo']) ? array_map('trim', $_POST['labitemMotivo']) : [];

    // Obtener datos de alergias
    $codigosAlergia = isset($_POST['labItemNo']) ? array_filter($_POST['labItemNo'], 'trim') : [];
 

    // Obtener datos de procedimientos
    $codigosProcedimiento = isset($_POST['labItemNop']) ? array_filter($_POST['labItemNop'], 'trim') : [];
    $detalleProcedimiento = isset($_POST['labItemNota']) ? array_map('trim', $_POST['labItemNota']) : [];

    // Obtener datos de medicamentos
    $codigosMedicamento = isset($_POST['codigo_medicamento']) ? array_filter($_POST['codigo_medicamento'], 'trim') : [];
    $cantidadMedicamento = isset($_POST['cantidad_dispensada']) ? array_map('trim', $_POST['cantidad_dispensada']) : [];
    $viaMedicamento = isset($_POST['indicacion_via']) ? array_map('trim', $_POST['indicacion_via']) : [];
    $dosisMedicamento = isset($_POST['unidad_medida']) ? array_map('trim', $_POST['unidad_medida']) : [];
    $frecuenciaMedicamento = isset($_POST['indicacion_frecuencia']) ? array_map('trim', $_POST['indicacion_frecuencia']) : [];
    $periodoMedicamento = isset($_POST['indicacion_pediodo']) ? array_map('trim', $_POST['indicacion_pediodo']) : [];

    //obtener vacunaciones 
    // Obtener y limpiar datos de vacunaciones
    $cod_vac = isset($_POST['cod_vac']) ? array_filter($_POST['cod_vac'], 'trim') : [];
    $lote_vac = isset($_POST['lote_vac']) ? array_map('trim', $_POST['lote_vac']) : [];

    try {
      // Iniciar transacción
      $dbconnFHIR->beginTransaction();

      $sqlpaciente = "INSERT INTO paciente2024 (cedula, pass, pnombre, snombre, papellido, sapellido, fechanac, sexo, pais)
      VALUES(:cedula, :pass, :pnombre, :snombre, :papellido, :sapellido, :fechanac, :sexo, :pais)";
      $stmtpaciente = $dbconnFHIR->prepare($sqlpaciente);
      $stmtpaciente->bindParam(':cedula', $cedula);
      $stmtpaciente->bindParam(':pass', $pasaporte);
      $stmtpaciente->bindParam(':pnombre', $pnombre);
      $stmtpaciente->bindParam(':snombre', $snombre);
      $stmtpaciente->bindParam(':papellido', $papellido);
      $stmtpaciente->bindParam(':sapellido', $sapellido);
      $stmtpaciente->bindParam(':fechanac', $fecha_nacimiento);
      $stmtpaciente->bindParam(':sexo', $sexo);
      $stmtpaciente->bindParam(':pais', $pais);
      $stmtpaciente->execute();
      $pacienteID = $dbconnFHIR->lastInsertId();

     $sqlservicio = "INSERT INTO establecimiento2024 (nombre, code, direccion, departamento, pais, id_paciente) VALUES(:nombre, :code, :direccion, :departamento, :pais, :id_paciente);";
      $stmtservicio = $dbconnFHIR->prepare($sqlservicio);
      $stmtservicio->bindParam(':nombre', $establecimiento);
      $stmtservicio->bindParam(':code', $id_servicio);
      $stmtservicio->bindParam(':direccion', $direccion);
      $stmtservicio->bindParam(':departamento', $departamentomed);
      $stmtservicio->bindParam(':pais', $paismed);
      $stmtservicio->bindParam(':id_paciente', $pacienteID);
      $stmtservicio->execute();
 

      $sqlmedico = "INSERT INTO medico2024 (id_medico, nombre, apellido, fechanac, sexo, pais, id_colegio, fecha, id_paciente) VALUES (:id_medico, :nombre, :apellido, :fechanac, :sexo, :pais, :id_colegio, :fecha, :id_paciente)";
      $stmtmedico = $dbconnFHIR->prepare($sqlmedico);
      $stmtmedico->bindParam(':id_medico', $passmed);
      $stmtmedico->bindParam(':nombre', $nombremed);
      $stmtmedico->bindParam(':apellido', $apellidomed);
      $stmtmedico->bindParam(':fechanac', $fecha_nacimientomed);
      $stmtmedico->bindParam(':sexo', $sexomed);
      $stmtmedico->bindParam(':pais', $nacionalidadmed);
      $stmtmedico->bindParam(':id_colegio', $id_colegio_medico);
      $stmtmedico->bindParam(':fecha', $fecha_acto_medico);
      $stmtmedico->bindParam(':id_paciente', $pacienteID);
      $stmtmedico->execute();
 



      // Insertar diagnósticos CIE10
      foreach ($codigosCIE as $index => $codigo10) {
        if (!empty($codigo10)) {
          // Generar un nuevo UUID para cada DIAGNOSTICOS
          $diagnosticos = Uuid::uuid4()->toString();
          $motivo = isset($motivosCIE[$index]) ? trim($motivosCIE[$index]) : null;
          $sql6 = 'INSERT INTO consulta_diagnosticos (id_paciente, codcie10, motivo, code)
                            VALUES (:id_paciente, :codcie10, :motivo, :code)';
          $stmt6 = $dbconnFHIR->prepare($sql6);   
          $stmt6->bindParam(':id_paciente', $pacienteID);
          $stmt6->bindParam(':codcie10', $codigo10);
          $stmt6->bindParam(':motivo', $motivo);
          $stmt6->bindParam(':code', $diagnosticos);
          $stmt6->execute();
          var_dump("sss");
        }
      }
      var_dump("4fds");
      // Insertar alergias
      foreach ($codigosAlergia as $alergia) {
        if (!empty($alergia)) {
          // Generar un nuevo UUID para cada Alergias
          $alergiascode = Uuid::uuid4()->toString();
          $sql7 = 'INSERT INTO consulta_alergias (id_paciente, codalergia, code)
VALUES(:id_paciente, :codalergia, :code)';
          $stmt7 = $dbconnFHIR->prepare($sql7);   
          $stmt7->bindParam(':id_paciente', $pacienteID);
          $stmt7->bindParam(':codalergia', $alergia);
          $stmt7->bindParam(':code', $alergiascode);
          $stmt7->execute();
          var_dump("sssdfsds");
        }
      }


      // Commit de la transacción
      $dbconnFHIR->commit();
      $status = "success";
      $message = "Se registró correctamente."; // ID: " . $processedData['id'] . ", Tipo: " . $processedData['type'];
    } catch (Exception $e) {
      // rollback transaction
      $dbconnFHIR->rollBack();
      $status = "error";
      $message = "Ocurrió un error: " . $e->getMessage();
    }

    print json_encode(array("status" => $status, "message" => $message));
  } else {
    echo json_encode(array("status" => "error", "message" => "Acción no permitida."));
  }
} else {
  echo json_encode(array("status" => "error", "message" => "No autorizado."));
}
