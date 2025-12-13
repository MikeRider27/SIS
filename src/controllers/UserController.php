<?php
// Inclusión de archivos necesarios
require_once __DIR__ . '/../models/User.php';       // Modelo de usuario
require_once __DIR__ . '/../middlewares/auth.php'; // Middleware de autenticación

class UserController
{
    private $userModel;

    // Constructor: inicializa el modelo de usuario
    public function __construct()
    {
        $this->userModel = new User(); // Crea una instancia del modelo User
    }

    // Muestra el formulario de login, verificando primero si hay sesión activa
    public function show()
    {
        $this->checkActiveSession(); // Verifica sesión activa
        require __DIR__ . '/../view/login/index.php'; // Carga la vista de login
    }

    // Procesa el login del usuario
    public function login()
    {
        // Obtiene credenciales del POST
        $user = $_POST['user'] ?? '';
        $password = $_POST['password'] ?? '';

        // Intenta hacer login con el modelo
        $user = $this->userModel->login($user, $password);  

        if ($user) {
     
            if ($user['estado'] == TRUE) {
                $this->startUserSession($user); // Inicia sesión
                echo json_encode(['status' => 'success']);
            } else {
                // Requiere cambio de contraseña
                echo json_encode(['status' => 'change', 'id' => $user['id']]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Credenciales inválidas']);
        }
        exit;
    }


   
    

    // Muestra la página de inicio (requiere autenticación)
    public function home()
    {
        auth(); // Middleware que verifica autenticación
        require __DIR__ . '/../view/home/index.php'; // Carga la vista de home
    }

    // Cierra la sesión del usuario
    public function logout()
    {
        session_start();    // Inicia la sesión (si no está iniciada)
        session_destroy(); // Destruye todos los datos de la sesión
        header("Location: /"); // Redirige a la raíz
        exit; // Termina la ejecución
    }

    // Método privado: verifica si hay una sesión activa para redirigir al home
    private function checkActiveSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start(); // Inicia sesión si no está iniciada
        }

        // Si hay usuario en sesión, redirige al home
        if (isset($_SESSION['user'])) {
            header("Location: /home");
            exit;
        }
    }

    // Método privado: inicia la sesión del usuario
    private function startUserSession($user)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start(); // Inicia sesión si no está iniciada
        }

        $_SESSION['user'] = $user; // Almacena el usuario en la sesión
    }

    public function listar()
    {
        header('Content-Type: application/json');

        $start = $_GET['start'] ?? 0;
        $length = $_GET['length'] ?? 10;
        $search = $_GET['search']['value'] ?? '';

        try {
            $result = $this->userModel->getPaginated($start, $length, $search);

            echo json_encode([
                'status' => 'success',
                'draw' => intval($_GET['draw'] ?? 0),
                'recordsTotal' => $result['recordsTotal'],
                'recordsFiltered' => $result['recordsFiltered'],
                'data' => $result['data']
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener los datos: ' . $e->getMessage()
            ]);
        }
    }
}
