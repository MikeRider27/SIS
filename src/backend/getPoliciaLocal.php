<?php
session_start();
session_write_close();  // necesario para utilizar ajax de forma asincrona
include('../core/connection.php');
include('./helperIdentificaciones.php');


// Check if user is logged
if($_SESSION['id_usuario']|| (isset($_POST['search']) AND $_POST['search'] == 'SI'))
{
    $cedula = str_replace(array('.', ' '), '' , $_POST['cedula']);
    $cedula = empty($cedula) ? 0 : $cedula;

 

   

  
        // chequeamos en webservices identificaciones
        $persona = checkIdentificaciones($cedula);
        if($persona == FALSE){
            print json_encode(array("status" => "error", "persona" => $persona));
            exit();
        }
    
     
   

    // si el usuario que carga es de vigilancia no se chequea personal de salud 
    // y retornamos los datos de la persona
    if(isset($_POST['user_vigilancia']) && $_POST['user_vigilancia'] == 'SI'){
        print json_encode(array("status" => "success", "persona" => $persona));
        exit();
    }
   

 // si el usuario que carga no pertenece a la direccion de vigilancia
    // entonces chequeamos si la persona es funcionaria de salud

    $result["status"] = "success";
    $result["persona"] = $persona;
    print json_encode($result);
}
else // NOT LOGGED
{
	print json_encode(array("status" => "error", "message" => "No autorizado"));
}
?>
