<?php

// Función para manejar las rutas
function routeHandler($method, $route)
{
    $staticRoutes = [
        'GET' => [
            '/' => '/view/login/index.php',
            '/home' => '/view/home/index.php',
            '/salir' => '/view/login/salir.php',
            '/paciente/view' => '/view/rda/viewer3.php',
            '/paciente/ips' => '/view/rda/ips.php',
            '/consultas/create' => '/view/track1/create_paciente.php',         
            
            

            '/paciente/search' => '/view/paciente/index.php',       
            '/itti67/search' => '/view/paciente/ips2.php',
            '/ips/view' => '/view/paciente/viewer2.php',
            //'/icvp/vaccine' => '/view/ICVP/create.php',
         
      
      
            //pacientes
            '/pacientes/list' => '/view/pacientes/list.php',
            '/pacientes/create' => '/view/pacientes/create.php',
            '/pacientes/edit' => '/view/pacientes/edit.php',
            //profesional
            '/profesional/list' => '/view/profesional/list.php',
            '/profesional/create' => '/view/profesional/create.php',
            '/profesional/edit' => '/view/profesional/edit.php',
            //organizacion
            '/organizacion/list' => '/view/organizacion/list.php',
            '/organizacion/create' => '/view/organizacion/create.php',
            '/organizacion/edit' => '/view/organizacion/edit.php',
           
            //Datos
            '/datos/personal' => '/view/datos/personal.php',
            '/datos/paciente' => '/view/datos/paciente.php',
            '/datos/servicio' => '/view/datos/servicio.php',
            //usuarios
            '/usuarios/list' => '/view/usuario/index.php',
            '/usuarios/create' => '/view/usuario/create.php',
        ],
        'POST' => [
            // Define tus rutas POST aquí
        ],
        'PUT' => [
            // Define tus rutas PUT aquí
        ],
        'DELETE' => [
            // Define tus rutas DELETE aquí
        ]
    ];

    $dynamicRoutes = [
        'GET' => [          
            '/ips/view/([A-Za-z0-9-]+)' => '/view/rda/viewer3.php',
            '/ips/bundle/([A-Za-z0-9-]+)' => '/view/rda/viewer3.php'           

        ],
        'POST' => [
            // Define tus rutas POST dinámicas aquí
        ],
        'PUT' => [
            // Define tus rutas PUT dinámicas aquí
        ],
        'DELETE' => [
            // Define tus rutas DELETE dinámicas aquí
        ]
    ];

    // Verificar si la ruta es una ruta estática válida
    if (isset($staticRoutes[$method]) && array_key_exists($route, $staticRoutes[$method])) {
        require getDocumentRoot() . $staticRoutes[$method][$route];
        return;
    }

    // Verificar si la ruta es una ruta dinámica válida
    if (isset($dynamicRoutes[$method])) {
        foreach ($dynamicRoutes[$method] as $pattern => $file) {
            // Convertir la ruta a un patrón de expresión regular
            $pattern = str_replace('/', '\/', $pattern);
            if (preg_match('/^' . $pattern . '$/', $route, $matches)) {
                array_shift($matches); // Eliminar el primer elemento que es la cadena completa
                // Definir variables basadas en los grupos de captura
                foreach ($matches as $index => $value) {
                    $_GET['param' . ($index + 1)] = $value;
                }
                require getDocumentRoot() . $file;
                return;
            }
        }
    }

    // Mostrar un error 404 si la ruta no es válida
    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found", true, 404);
    echo '404 - Page not found';
}

// Función para obtener el DOCUMENT_ROOT
function getDocumentRoot()
{
    return rtrim($_SERVER['DOCUMENT_ROOT'], '/');
}

// Obtener la ruta actual de la solicitud (usando la variable $_SERVER['REQUEST_URI'])
$current_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Obtener el método de la solicitud
$request_method = $_SERVER['REQUEST_METHOD'];

// Manejar la ruta y el método
routeHandler($request_method, $current_uri);
