<?php
session_start();
include('../core/connection.php');
$dbconn = getConnection();

// Verificar si el usuario está logueado
if (isset($_SESSION['idUsuario'])) {

    // Verificar si se envió el formulario y la acción es "distritoList"
    if (isset($_POST['accion']) && $_POST['accion'] === "distritoList") {
        $region = $_POST['region'];

        // Verificar si la variable 'region' no está vacía y es válida
        if (!empty($region) && strlen($region) >= 3) {
            // Extraer las partes de la región
            $codreg = substr($region, 0, 2);
            $subcreg = substr($region, 2);

            // Construir la consulta SQL base
            $sql = "SELECT coddist, nomdist FROM distritos WHERE codreg = :codreg AND  subcreg = :subcreg";


            try {
                // Preparar la consulta SQL
                $stmt = $dbconn->prepare($sql);

                // Asignar parámetros obligatorios
                $stmt->bindParam(':codreg', $codreg);
                $stmt->bindParam(':subcreg', $subcreg);



                // Ejecutar la consulta
                $stmt->execute();

                // Obtener los resultados
                $distritos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Verificar si se encontraron distritos
                if ($distritos) {
                    $status = "success";
                    $message = "Distritos obtenidos correctamente.";
                } else {
                    $status = "error";
                    $message = "No se encontraron distritos.";
                    $distritos = []; // Devolver un array vacío si no hay resultados
                }

                // Devolver la respuesta como JSON
                echo json_encode(array("status" => $status, "message" => $message, "distritos" => $distritos));
            } catch (PDOException $e) {
                // Manejo de errores en la consulta
                echo json_encode(array("status" => "error", "message" => "Error en la consulta: " . $e->getMessage()));
            }
        } else {
            // Si la región no es válida o está vacía, enviar un error
            echo json_encode(array("status" => "error", "message" => "Región no válida o no proporcionada."));
        }
    }  // Verificar si se envió el formulario y la acción es "distritoList"
    else if (isset($_POST['accion']) && $_POST['accion'] === "distritoList2") {
        $departamento = $_POST['departamento'];

        // Verificar si la variable 'region' no está vacía y es válida
        if (!empty($departamento)) {
           

            // Construir la consulta SQL base
            $sql = "SELECT coddist, nomdist FROM distritos WHERE coddpto = :coddpto";


            try {
                // Preparar la consulta SQL
                $stmt = $dbconn->prepare($sql);

                // Asignar parámetros obligatorios
                $stmt->bindParam(':coddpto', $departamento);           



                // Ejecutar la consulta
                $stmt->execute();

                // Obtener los resultados
                $distritos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Verificar si se encontraron distritos
                if ($distritos) {
                    $status = "success";
                    $message = "Distritos obtenidos correctamente.";
                } else {
                    $status = "error";
                    $message = "No se encontraron distritos.";
                    $distritos = []; // Devolver un array vacío si no hay resultados
                }

                // Devolver la respuesta como JSON
                echo json_encode(array("status" => $status, "message" => $message, "distritos" => $distritos));
            } catch (PDOException $e) {
                // Manejo de errores en la consulta
                echo json_encode(array("status" => "error", "message" => "Error en la consulta: " . $e->getMessage()));
            }
        } else {
            // Si la región no es válida o está vacía, enviar un error
            echo json_encode(array("status" => "error", "message" => "Región no válida o no proporcionada."));
        }
    }  // Verificar si se envió el formulario y la acción es "EstablecimientoList"
    else if (isset($_POST['accion']) && $_POST['accion'] === "establecimientoList") {
        $region = $_POST['region'];
        $distrito = $_POST['distrito'];

        // Verificar si la variable 'region' no está vacía y es válida
        if (!empty($region) && strlen($region) >= 3) {
            // Extraer las partes de la región
            $codreg = substr($region, 0, 2);
            $subcreg = substr($region, 2);

            // Construir la consulta SQL base
            $sql = "SELECT codreg, subcreg, coddist, codserv, tiposerv, nomserv FROM establecimientos WHERE codreg = :codreg AND  subcreg = :subcreg AND coddist = :coddist";


            try {
                // Preparar la consulta SQL
                $stmt = $dbconn->prepare($sql);

                // Asignar parámetros obligatorios
                $stmt->bindParam(':codreg', $codreg);
                $stmt->bindParam(':subcreg', $subcreg);
                $stmt->bindParam(':coddist', $distrito);



                // Ejecutar la consulta
                $stmt->execute();

                // Obtener los resultados
                $establecimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Verificar si se encontraron establecimientos
                if ($establecimientos) {
                    $status = "success";
                    $message = "establecimientos obtenidos correctamente.";
                } else {
                    $status = "error";
                    $message = "No se encontraron establecimientos.";
                    $establecimientos = []; // Devolver un array vacío si no hay resultados
                }

                // Devolver la respuesta como JSON
                echo json_encode(array("status" => $status, "message" => $message, "establecimientos" => $establecimientos));
            } catch (PDOException $e) {
                // Manejo de errores en la consulta
                echo json_encode(array("status" => "error", "message" => "Error en la consulta: " . $e->getMessage()));
            }
        } else {
            // Si la región no es válida o está vacía, enviar un error
            echo json_encode(array("status" => "error", "message" => "Región no válida o no proporcionada."));
        }
    }  // Verificar si se envió el formulario y la acción es "EstablecimientoList"
    else if (isset($_POST['accion']) && $_POST['accion'] === "tipo_profesionList") {
        $profesion = explode('-', $_POST['profesion']);        


        // Extraer las partes
        $codprofesion = $profesion[0]; // "8721"
        $codtprof = $profesion[1]; // "01"

        // Construir la consulta SQL base
        $sql = "SELECT codtprof, nomtprof FROM tprofesional WHERE codtprof = :codtprof";


        try {
            // Preparar la consulta SQL
            $stmt = $dbconn->prepare($sql);

            // Asignar parámetros obligatorios
            $stmt->bindParam(':codtprof', $codtprof);      




            // Ejecutar la consulta
            $stmt->execute();

            // Obtener los resultados
            $tipo_profesion = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Verificar si se encontraron tipo_profesion
            if ($tipo_profesion) {
                $status = "success";
                $message = "tipo_profesion obtenidos correctamente.";
            } else {
                $status = "error";
                $message = "No se encontraron tipo_profesion.";
                $tipo_profesion = []; // Devolver un array vacío si no hay resultados
            }

            // Devolver la respuesta como JSON
            echo json_encode(array("status" => $status, "message" => $message, "tipo_profesion" => $tipo_profesion));
        } catch (PDOException $e) {
            // Manejo de errores en la consulta
            echo json_encode(array("status" => "error", "message" => "Error en la consulta: " . $e->getMessage()));
        }
    } else {
        // Si el formulario no fue enviado o la acción no coincide
        error_log("Acción recibida: " . $_POST['accion'] ?? 'Ninguna');
        echo json_encode(array("status" => "error", "message" => "Acción no válida o formulario no enviado."));
    }
} else {
    // Si el usuario no está logueado
    echo json_encode(array("status" => "error", "message" => "No autorizado."));
}
