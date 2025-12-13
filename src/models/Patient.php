<?php
class Patient
{
    private $db; // Conexión a la base de datos

    // Constructor: obtiene la conexión a la base de datos
    public function __construct()
    {
        $this->db = getConnection(); // Obtiene conexión PDO
    } 
    
    // Obtenemos la lista de pacientes
    public function getFHIRPatients($count = 100, $sort = '-_lastUpdated')
    {
        $url = APP_FHIR_SERVER . "/Patient?_count={$count}&_sort={$sort}";
        $result = $this->fetchFHIR($url);

        if ($result['status'] !== 200) {
            return ['status' => 'error', 'message' => 'Error fetching patients from FHIR server'];
        }

        $data = json_decode($result['body'], true);
        $patients = [];

        if (isset($data['entry'])) {
            foreach ($data['entry'] as $entry) {
                $r = $entry['resource'];
                $patients[] = [
                    'id' => $r['id'] ?? null,
                    'cedula' => $r['identifier'][0]['value'] ?? null,
                    'nombre' => ($r['name'][0]['given'][0] ?? '') . ' ' . ($r['name'][0]['family'] ?? ''),
                    'lastUpdated' => $r['meta']['lastUpdated'] ?? null,
                    'raw' => $r
                ];
            }
        }

        return ['status' => 'success', 'patients' => $patients];
    }

    // Función para realizar solicitudes al servidor FHIR
    private function fetchFHIR($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/fhir+json']);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $httpCode, 'body' => $response];
    }

    // Puedes añadir más métodos para buscar paciente por ID, etc.
    public function getPatientById($id)
    {
        $url = APP_FHIR_SERVER . "/Patient/{$id}";
        $result = $this->fetchFHIR($url);

        if ($result['status'] !== 200) {
            return ['status' => 'error', 'message' => 'Error fetching patient from FHIR server'];
        }

        $data = json_decode($result['body'], true);
        return ['status' => 'success', 'patient' => $data];
    }

}
