<?php
// Inclusión de archivos necesarios
require_once __DIR__ . '/../models/Patient.php';       // Modelo de paciente
require_once __DIR__ . '/../middlewares/auth.php'; // Middleware de autenticación

class PatientController
{
    private $patientModel;

    // Constructor: inicializa el modelo de usuario
    public function __construct()
    {
        $this->patientModel = new Patient(); // Crea una instancia del modelo Patient
    }

    // Muestra el formulario de login, verificando primero si hay sesión activa
    public function show()
    {
         auth(); // Verifica sesión activa
        require __DIR__ . '/../view/patient/index.php'; // Carga la vista de login
    }

     // Muestra el formulario de login, verificando primero si hay sesión activa
    public function showPatient()
    {
         auth(); // Verifica sesión activa
        require __DIR__ . '/../view/patient/create.php'; // Carga la vista de login
    }

    /**
     * Obtiene pacientes desde FHIR (para API)
     */
    public function getFHIRPatients()
    {
        auth(); // Verifica sesión activa si necesitas autenticación
        
        // Puedes recibir parámetros opcionales
        $count = $_GET['count'] ?? 50;
        $sort = $_GET['sort'] ?? '-_lastUpdated';
        
        $result = $this->patientModel->getFHIRPatients($count, $sort);
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    /**
     * Obtiene un paciente específico desde FHIR
     */
    public function getFHIRPatient()
    {
        auth();
        
        if (!isset($_GET['id'])) {
            echo json_encode(['status' => 'error', 'message' => 'ID no proporcionado']);
            return;
        }
        
        $id = $_GET['id'];
        $result = $this->patientModel->getFHIRPatientById($id);
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }

}