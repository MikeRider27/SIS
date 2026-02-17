<?php
/*
*   Se requiere haber importado el archivo 
*   /core/connection.php para el correcto funcionamiento
*/

function checkIdentificaciones($cedula)
{
    try {
        $username = 'personas';
        $password = '@g3137c0120';
        $URL = 'https://ws.mspbs.gov.py/api/getPersonas.php?cedula=' . $cedula;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode("$username:$password")
        ));
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
        //$error = curl_error($ch);
        curl_close($ch);

        $persona = json_decode($res, true);

        if ('200' == $status) {
            if (empty($persona['cedula_identidad'])) { // no se encontro a la persona retornamos FALSE
                return FALSE;
            }

            // Pasar Fecha de Nacimiento a formato 'Año-mes-dia'
            if (isset($persona['fecha_nacimiento'])) {
                $fecnac = date_format(date_create($persona['fecha_nacimiento']), 'Y-m-d');
                $fecnac = "'" . date("Y-m-d", strtotime($fecnac)) . "'";
                $datetime1 = date_create($persona['fecha_nacimiento']);
                $datetime2 = date_create(date('Y-m-d'));
                $interval = date_diff($datetime1, $datetime2);
                $persona['edad'] = $interval->format('%y');
            } else {
                $persona['edad'] = 0;
            }
            //Separamos el primer y segundo nombre
            $nombre = explode(' ', $persona['nombres']);
            $persona['first_name'] = $nombre[0];
            $persona['second_name'] = $nombre[1];
            $apellido = explode(' ', $persona['apellidos']);

            // Verificar si hay más de una palabra en los apellidos
            if (count($apellido) > 1) {
                $persona['last_name'] = $apellido[0]; // Primer apellido
                // Concatenar el resto como segundo apellido
                $persona['slast_name'] = implode(' ', array_slice($apellido, 1));
            } else {
                // Si solo hay un apellido
                $persona['last_name'] = $apellido[0];
                $persona['slast_name'] = '';
            }




            return $persona;
        } else {
            return FALSE;
        }
    } catch (Exception $e) {
        return FALSE;
    }
}
