<?php
// MVC-style router with support for static and dynamic routes, and custom error views

require_once __DIR__ . '/core/connection.php';

// ==============================
// 1. ROUTE DEFINITIONS
// ==============================

$routes = [
    'GET' => [
        // User system routes
        '/'                     => ['controller' => 'UserController', 'method' => 'show'],
        '/logout'               => ['controller' => 'UserController', 'method' => 'logout'],
        '/home'                 => ['controller' => 'UserController', 'method' => 'home'],
        // Patient routes
        '/patients'             => ['controller' => 'PatientController', 'method' => 'show'],
        '/patients/list'        => ['controller' => 'PatientController', 'method' => 'getFHIRPatients'],
        '/patient'              => ['controller' => 'PatientController', 'method' => 'showPatient'],
        '/paciente/create'      => ['controller' => 'PatientController', 'method' => 'savePatient'],

    ],
    'POST' => [
        // User system actions
        '/login'                => ['controller' => 'UserController', 'method' => 'login'],
        '/logout'               => ['controller' => 'UserController', 'method' => 'logout'],
        '/change'               => ['controller' => 'UserController', 'method' => 'change'],
    ],
    'PUT' => [
        // Questionnaire section updates
        // Add PUT routes here when needed
    ],
];

// ==============================
// 2. ERROR VIEW DEFINITIONS
// ==============================

$errorViews = [
    '404' => __DIR__ . '/view/errors/404.php',
    '500' => __DIR__ . '/view/errors/500.php',
];

// ==============================
// 3. GET CURRENT ROUTE AND METHOD
// ==============================

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestUri = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

// ==============================
// 4. FUNCTION TO SHOW ERROR VIEW
// ==============================

function showErrorView($errorCode, $errorViews)
{
    http_response_code($errorCode);
    $errorFile = $errorViews[$errorCode] ?? null;

    if ($errorFile && file_exists($errorFile)) {
        require $errorFile;
    } else {
        $errorMessage = $errorCode === 404 ? 'Page not found' : 'Server error';
        echo "<!DOCTYPE html>
        <html><head><title>Error $errorCode</title>
        <style>
            body { font-family: Arial; text-align: center; padding: 50px; }
            h1 { font-size: 50px; } 
            p { font-size: 20px; }
        </style></head><body>
        <h1>Error $errorCode</h1>
        <p>$errorMessage</p>
        </body></html>";
    }

    exit;
}

// ==============================
// 5. FUNCTION TO LOAD CONTROLLER AND EXECUTE METHOD
// ==============================

function dispatch($controllerName, $methodName, $params = [], $errorViews)
{
    $controllerFile = __DIR__ . "/controllers/{$controllerName}.php";

    if (!file_exists($controllerFile)) {
        showErrorView(500, $errorViews);
    }

    require_once $controllerFile;

    if (!class_exists($controllerName)) {
        showErrorView(500, $errorViews);
    }

    $controller = new $controllerName();

    if (!method_exists($controller, $methodName)) {
        showErrorView(500, $errorViews);
    }

    try {
        call_user_func_array([$controller, $methodName], $params);
    } catch (Exception $e) {
        error_log("Controller error: " . $e->getMessage());
        showErrorView(500, $errorViews);
    }
}

// ==============================
// 6. ROUTE RESOLUTION
// ==============================

// Check for exact match first
if (isset($routes[$method][$requestUri])) {
    $route = $routes[$method][$requestUri];
    dispatch($route['controller'], $route['method'], [], $errorViews);
    exit;
}

// Check for dynamic route matches
if (isset($routes[$method])) {
    foreach ($routes[$method] as $routePattern => $routeData) {
        // Replace {param} with regex for dynamic segment
        $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $routePattern);
        $pattern = "#^" . rtrim($pattern, '/') . "$#";
        
        if (preg_match($pattern, $requestUri, $matches)) {
            array_shift($matches); // Remove full match
            dispatch($routeData['controller'], $routeData['method'], $matches, $errorViews);
            exit;
        }
    }
}

// If no match found, show 404
showErrorView(404, $errorViews);