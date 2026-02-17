<?php
include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');
$dbconn = getConnection();
?>

<style>
  .split { 
    display: flex; 
    gap: 20px; 
    flex-wrap: wrap; 
  }
  .left, .right { 
    flex: 1; 
    min-width: 400px; 
  }
  .CodeMirror { 
    height: auto !important; 
    min-height: 500px;
    border-radius: 4px; 
    font-size: 14px; 
    border: 1px solid #ddd;
  }
  .card-header {
    background-color: #dc3545 !important;
    color: white;
  }
  .required-field::after {
    content: " *";
    color: #dc3545;
  }
  .form-group {
    margin-bottom: 1rem;
  }
  .dynamic-item {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
    background-color: #f8f9fa;
    position: relative;
  }
  .dynamic-item-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 10px;
  }
  .item-number {
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 10px;
  }
  .btn-remove-item {
    position: absolute;
    top: 10px;
    right: 10px;
  }
  .btn-add-item {
    margin-top: 10px;
  }
  .empty-state {
    text-align: center;
    padding: 20px;
    color: #6c757d;
    font-style: italic;
  }
  .patient-search-section {
    border-bottom: 2px solid #dc3545;
    padding-bottom: 15px;
    margin-bottom: 15px;
  }
  .table-patient:hover {
    background-color: #f8f9fa;
    cursor: pointer;
  }
  .table-patient.selected {
    background-color: #d4edda;
  }
  .loading-spinner {
    display: none;
    text-align: center;
    padding: 20px;
  }
</style>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid text-center">
      <h1><strong>Constructor FHIR Bundle — Paraguay (Documento)</strong></h1>
      <p class="lead">Generador de documentos FHIR para el sistema de salud de Paraguay</p>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <!-- Alert Container -->
      <div class="alert-container"></div>
      
      <div class="split">
        <!-- FORMULARIO -->
        <div class="left">
          <!-- PACIENTE -->
          <div class="card card-danger">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-user-injured mr-2"></i>DATOS DEL/LA PACIENTE</h3>
            </div>
            <div class="card-body">
              <!-- Sección de Búsqueda -->
              <div class="patient-search-section">
                <div class="row">
                  <div class="col-12">
                    <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#patientSearchModal">
                      <i class="fas fa-search mr-2"></i> Buscar Paciente Existente
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Formulario de Datos del Paciente -->
              <div class="row">
                <div class="col-lg-6 form-group">
                  <label class="required-field">Tipo de Documento:</label>
                  <select class="form-control" id="tipo_documento" required>
                    <option value="">Seleccione</option>
                    <option value="1">Cédula de Identidad</option>
                    <option value="2">Cédula Extranjera</option>
                    <option value="3">Pasaporte</option>
                  </select>
                </div>
                <div class="col-lg-6 form-group">
                  <label class="required-field">Número de Documento:</label>
                  <div class="input-group">
                    <input type="text" class="form-control" id="cedula" required 
                           pattern="[0-9A-Za-z]+" title="Solo números y letras permitidos">
                    <div class="input-group-append">
                      <button class="btn btn-outline-secondary" type="button" id="searchByDocument">
                        <i class="fas fa-search"></i>
                      </button>
                    </div>
                  </div>
                </div>
                <div class="col-lg-3 form-group">
                  <label class="required-field">Primer Nombre:</label>
                  <input type="text" class="form-control" id="pnombre" required>
                </div>
                <div class="col-lg-3 form-group">
                  <label>Segundo Nombre:</label>
                  <input type="text" class="form-control" id="snombre">
                </div>
                <div class="col-lg-3 form-group">
                  <label class="required-field">Primer Apellido:</label>
                  <input type="text" class="form-control" id="papellido" required>
                </div>
                <div class="col-lg-3 form-group">
                  <label>Segundo Apellido:</label>
                  <input type="text" class="form-control" id="sapellido">
                </div>
                <div class="col-lg-4 form-group">
                  <label class="required-field">Fecha Nacimiento:</label>
                  <input type="date" class="form-control" id="fecha_nacimiento" required 
                         max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-lg-4 form-group">
                  <label class="required-field">Sexo:</label>
                  <select class="form-control" id="sexo" required>
                    <option value="">Seleccione</option>
                    <option value="male">Masculino</option>
                    <option value="female">Femenino</option>
                    <option value="other">Otro</option>
                    <option value="unknown">Desconocido</option>
                  </select>
                </div>
                <div class="col-lg-4 form-group">
                  <label>Nacionalidad:</label>
                  <input type="text" class="form-control" id="nacionalidad" value="Paraguaya">
                </div>
              </div>
            </div>
          </div>

          <!-- PROFESIONAL -->
          <div class="card card-danger mt-3">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-user-md mr-2"></i>PROFESIONAL / PRACTITIONER</h3>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-lg-4 form-group">
                  <label class="required-field">Cédula Profesional:</label>
                  <input type="text" class="form-control" id="prac_identifier" required>
                </div>
                <div class="col-lg-4 form-group">
                  <label class="required-field">Nombre:</label>
                  <input type="text" class="form-control" id="prac_name" required>
                </div>
                <div class="col-lg-4 form-group">
                  <label class="required-field">Apellido:</label>
                  <input type="text" class="form-control" id="prac_family" required>
                </div>
              </div>
            </div>
          </div>

          <!-- DIAGNÓSTICO -->
          <div class="card card-danger mt-3">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-stethoscope mr-2"></i>DIAGNÓSTICOS (Conditions)</h3>
            </div>
            <div class="card-body">
              <div id="conditions-container">
                <div class="empty-state" id="conditions-empty">
                  No hay diagnósticos agregados. Haga clic en "Agregar Diagnóstico" para comenzar.
                </div>
              </div>
              <button type="button" class="btn btn-success btn-sm btn-add-item" id="addConditionBtn">
                <i class="fas fa-plus"></i> Agregar Diagnóstico
              </button>
            </div>
          </div>

          <!-- ALERGIAS -->
          <div class="card card-danger mt-3">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-allergies mr-2"></i>ALERGIAS / INTOLERANCIAS</h3>
            </div>
            <div class="card-body">
              <div id="allergies-container">
                <div class="empty-state" id="allergies-empty">
                  No hay alergias agregadas. Haga clic en "Agregar Alergia" para comenzar.
                </div>
              </div>
              <button type="button" class="btn btn-success btn-sm btn-add-item" id="addAllergyBtn">
                <i class="fas fa-plus"></i> Agregar Alergia
              </button>
            </div>
          </div>

          <!-- MEDICACIÓN -->
          <div class="card card-danger mt-3">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-pills mr-2"></i>MEDICACIÓN (MedicationStatements)</h3>
            </div>
            <div class="card-body">
              <div id="medications-container">
                <div class="empty-state" id="medications-empty">
                  No hay medicamentos agregados. Haga clic en "Agregar Medicamento" para comenzar.
                </div>
              </div>
              <button type="button" class="btn btn-success btn-sm btn-add-item" id="addMedicationBtn">
                <i class="fas fa-plus"></i> Agregar Medicamento
              </button>
            </div>
          </div>

          <!-- DOCUMENTO -->
          <div class="card card-danger mt-3">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-file-medical mr-2"></i>DOCUMENTO (Composition)</h3>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-lg-6 form-group">
                  <label class="required-field">Título:</label>
                  <input type="text" class="form-control" id="comp_title" required
                         value="Ejemplo de Documento Clinico Paraguay">
                </div>
                <div class="col-lg-6 form-group">
                  <label class="required-field">Fecha documento:</label>
                  <input type="date" class="form-control" id="comp_date" required
                         value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-12 form-group">
                  <label class="required-field">Custodio:</label>
                  <input type="text" class="form-control" id="comp_custodian" required
                         value="HOSPITAL GENERAL DE CORONEL OVIEDO">
                </div>
              </div>
            </div>
            <div class="card-footer text-right">
              <button id="validateJson" class="btn btn-outline-warning mr-2">
                <i class="fas fa-check-circle"></i> Validar
              </button>
              <button id="downloadJson" class="btn btn-outline-success mr-2">
                <i class="fas fa-download"></i> Descargar JSON
              </button>
              <button id="clearForm" class="btn btn-outline-secondary">
                <i class="fas fa-broom"></i> Limpiar Todo
              </button>
            </div>
          </div>

        </div>

        <!-- VISOR JSON -->
        <div class="right">
          <div class="card">
            <div class="card-header bg-success text-white">
              <h5 class="card-title mb-0">
                <i class="fas fa-code mr-2"></i><strong>Visor JSON (FHIR Bundle)</strong>
              </h5>
            </div>
            <div class="card-body p-0">
              <textarea id="jsonDisplay" class="d-none"></textarea>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</div>

<!-- Modal para Búsqueda de Pacientes -->
<div class="modal fade" id="patientSearchModal" tabindex="-1" role="dialog" aria-labelledby="patientSearchModalLabel">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="patientSearchModalLabel">
          <i class="fas fa-search mr-2"></i>Buscar Paciente
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true" class="text-white">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Filtros de Búsqueda -->
        <div class="row mb-3">
          <div class="col-md-6">
            <label for="searchDocument">Buscar por Documento:</label>
            <div class="input-group">
              <input type="text" class="form-control" id="searchDocument" placeholder="Ingrese número de documento">
              <div class="input-group-append">
                <button class="btn btn-outline-primary" type="button" id="btnSearchDocument">
                  <i class="fas fa-search"></i> Buscar
                </button>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <label for="searchName">Buscar por Nombre/Apellido:</label>
            <div class="input-group">
              <input type="text" class="form-control" id="searchName" placeholder="Ingrese nombre o apellido">
              <div class="input-group-append">
                <button class="btn btn-outline-primary" type="button" id="btnSearchName">
                  <i class="fas fa-search"></i> Buscar
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Spinner de carga -->
        <div class="loading-spinner" id="loadingSpinner">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Cargando...</span>
          </div>
          <p class="mt-2">Buscando pacientes...</p>
        </div>

        <!-- Tabla de Resultados -->
        <div class="table-responsive">
          <table class="table table-bordered table-hover">
            <thead class="thead-dark">
              <tr>
                <th>Seleccionar</th>
                <th>Tipo Doc.</th>
                <th>N° Documento</th>
                <th>Primer Nombre</th>
                <th>Segundo Nombre</th>
                <th>Primer Apellido</th>
                <th>Segundo Apellido</th>
                <th>Fecha Nac.</th>
                <th>Sexo</th>
              </tr>
            </thead>
            <tbody id="patientResults">
              <!-- Los resultados se cargarán aquí dinámicamente -->
            </tbody>
          </table>
        </div>

        <!-- Mensaje cuando no hay resultados -->
        <div id="noResults" class="text-center mt-3" style="display: none;">
          <p class="text-muted">No se encontraron pacientes con los criterios de búsqueda.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="selectPatientBtn" disabled>
          <i class="fas fa-check mr-2"></i> Seleccionar Paciente
        </button>
      </div>
    </div>
  </div>
</div>

<?php include('/var/www/html/view/includes/footer.php'); ?>

<!-- Dependencias -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/material.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// === FUNCIONES GLOBALES ===
function uuidv4() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
    var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
    return v.toString(16);
  });
}

// Variables globales
let conditionCounter = 0;
let allergyCounter = 0;
let medicationCounter = 0;
let selectedPatientId = null;
let currentPatients = [];

// Plantillas HTML para elementos dinámicos
const conditionTemplate = (index) => `
  <div class="dynamic-item" id="condition-${index}">
    <div class="dynamic-item-header">
      <div class="item-number">${index + 1}</div>
      <h6 class="mb-0">Diagnóstico #${index + 1}</h6>
      <button type="button" class="btn btn-danger btn-sm btn-remove-item" data-remove-condition="${index}">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="row">
      <div class="col-lg-6 form-group">
        <label>Código (ICD-10):</label>
        <input type="text" class="form-control condition-code" data-index="${index}" 
               placeholder="E10.4">
      </div>
      <div class="col-lg-6 form-group">
        <label>Texto diagnóstico:</label>
        <input type="text" class="form-control condition-text" data-index="${index}" 
               placeholder="Diabetes Tipo 1...">
      </div>
      <div class="col-lg-6 form-group">
        <label>Fecha de inicio:</label>
        <input type="date" class="form-control condition-onset" data-index="${index}">
      </div>
      <div class="col-lg-6 form-group">
        <label>Estado verificación:</label>
        <select class="form-control condition-verif" data-index="${index}">
          <option value="confirmed">Confirmado</option>
          <option value="provisional">Provisional</option>
          <option value="refuted">Refutado</option>
        </select>
      </div>
      <div class="col-12 form-group">
        <label>Nota:</label>
        <textarea class="form-control condition-note" data-index="${index}" 
                  placeholder="Antecedentes de diabetes de tipo 1..." rows="2"></textarea>
      </div>
    </div>
  </div>
`;

const allergyTemplate = (index) => `
  <div class="dynamic-item" id="allergy-${index}">
    <div class="dynamic-item-header">
      <div class="item-number">${index + 1}</div>
      <h6 class="mb-0">Alergia #${index + 1}</h6>
      <button type="button" class="btn btn-danger btn-sm btn-remove-item" data-remove-allergy="${index}">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="row">
      <div class="col-lg-6 form-group">
        <label>Tipo:</label>
        <select class="form-control allergy-category" data-index="${index}">
          <option value="medication">Medicamento</option>
          <option value="food">Alimento</option>
          <option value="environment">Ambiente</option>
        </select>
      </div>
      <div class="col-lg-6 form-group">
        <label>Código (ICD-10):</label>
        <input type="text" class="form-control allergy-code" data-index="${index}" 
               placeholder="T36.0X5">
      </div>
      <div class="col-12 form-group">
        <label>Descripción:</label>
        <input type="text" class="form-control allergy-text" data-index="${index}" 
               placeholder="alergia a penicilina">
      </div>
    </div>
  </div>
`;

const medicationTemplate = (index) => `
  <div class="dynamic-item" id="medication-${index}">
    <div class="dynamic-item-header">
      <div class="item-number">${index + 1}</div>
      <h6 class="mb-0">Medicamento #${index + 1}</h6>
      <button type="button" class="btn btn-danger btn-sm btn-remove-item" data-remove-medication="${index}">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="row">
      <div class="col-lg-6 form-group">
        <label>Medicamento:</label>
        <input type="text" class="form-control medication-text" data-index="${index}" 
               placeholder="Paracetamol">
      </div>
      <div class="col-lg-6 form-group">
        <label>Fecha (efectiva):</label>
        <input type="date" class="form-control medication-date" data-index="${index}">
      </div>
      <div class="col-12 form-group">
        <label>Dosis:</label>
        <input type="text" class="form-control medication-dosage" data-index="${index}" 
               placeholder="1 tableta de 500 mg cada 8 horas">
      </div>
    </div>
  </div>
`;

$(function(){
  // === FUNCIONES PARA BÚSQUEDA DE PACIENTES DESDE API FHIR ===
  
  // Función para buscar pacientes desde la API FHIR
  async function searchPatientsFromAPI(filters = {}) {
    const loadingSpinner = $('#loadingSpinner');
    const noResults = $('#noResults');
    
    loadingSpinner.show();
    noResults.hide();
    
    try {
      let url = 'https://fhir-conectaton.mspbs.gov.py/fhir/Patient?_count=50&_sort=-_lastUpdated';
      
      // Agregar filtros si existen
      if (filters.document) {
        url += `&identifier=${encodeURIComponent(filters.document)}`;
      }
      
      if (filters.name) {
        url += `&name=${encodeURIComponent(filters.name)}`;
      }
      
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      });
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      
      const data = await response.json();
      
      if (data.resourceType === 'Bundle' && data.entry) {
        return data.entry.map(entry => parseFHIRPatient(entry.resource));
      } else {
        return [];
      }
      
    } catch (error) {
      console.error('Error al buscar pacientes:', error);
      showAlert('Error al conectar con el servidor FHIR', 'danger');
      return [];
    } finally {
      loadingSpinner.hide();
    }
  }
  
  // Función para parsear paciente FHIR a formato interno
  function parseFHIRPatient(fhirPatient) {
    const patient = {
      id: fhirPatient.id,
      tipo_documento: "1", // Por defecto cédula de identidad
      cedula: "",
      pnombre: "",
      snombre: "",
      papellido: "",
      sapellido: "",
      fecha_nacimiento: fhirPatient.birthDate || "",
      sexo: fhirPatient.gender || "unknown",
      nacionalidad: "Paraguaya"
    };
    
    // Extraer identificadores
    if (fhirPatient.identifier && fhirPatient.identifier.length > 0) {
      const identifier = fhirPatient.identifier[0];
      if (identifier.value) {
        patient.cedula = identifier.value;
      }
      // Determinar tipo de documento
      if (identifier.type && identifier.type.coding && identifier.type.coding.length > 0) {
        const coding = identifier.type.coding[0];
        if (coding.code === '01') patient.tipo_documento = "1";
        else if (coding.code === '02') patient.tipo_documento = "2";
        else if (coding.code === '03') patient.tipo_documento = "3";
      }
    }
    
    // Extraer nombres
    if (fhirPatient.name && fhirPatient.name.length > 0) {
      const name = fhirPatient.name[0];
      if (name.family) {
        const familyParts = name.family.split(' ');
        patient.papellido = familyParts[0] || "";
        patient.sapellido = familyParts.slice(1).join(' ') || "";
      }
      if (name.given && name.given.length > 0) {
        patient.pnombre = name.given[0] || "";
        patient.snombre = name.given.slice(1).join(' ') || "";
      }
    }
    
    return patient;
  }
  
  // Función para mostrar resultados en la tabla
  function displayPatientResults(patients) {
    const tbody = $('#patientResults');
    const noResults = $('#noResults');
    
    tbody.empty();
    currentPatients = patients;
    
    if (patients.length === 0) {
      noResults.show();
      $('#selectPatientBtn').prop('disabled', true);
      return;
    }
    
    noResults.hide();
    
    patients.forEach(patient => {
      const tipoDocMap = {
        "1": "Cédula",
        "2": "Extranjera", 
        "3": "Pasaporte"
      };
      
      const sexoMap = {
        "male": "Masculino",
        "female": "Femenino",
        "other": "Otro",
        "unknown": "Desconocido"
      };
      
      const row = `
        <tr class="table-patient" data-patient-id="${patient.id}">
          <td>
            <div class="form-check">
              <input class="form-check-input patient-radio" type="radio" name="patientSelect" value="${patient.id}">
            </div>
          </td>
          <td>${tipoDocMap[patient.tipo_documento] || patient.tipo_documento}</td>
          <td>${patient.cedula}</td>
          <td>${patient.pnombre}</td>
          <td>${patient.snombre || ''}</td>
          <td>${patient.papellido}</td>
          <td>${patient.sapellido || ''}</td>
          <td>${patient.fecha_nacimiento}</td>
          <td>${sexoMap[patient.sexo] || patient.sexo}</td>
        </tr>
      `;
      tbody.append(row);
    });
    
    // Agregar event listeners para selección
    $('.patient-radio').change(function() {
      selectedPatientId = $(this).val();
      $('#selectPatientBtn').prop('disabled', false);
      
      // Resaltar fila seleccionada
      $('.table-patient').removeClass('selected');
      $(this).closest('tr').addClass('selected');
    });
    
    // Doble click para selección rápida
    $('.table-patient').dblclick(function() {
      const patientId = $(this).data('patient-id');
      selectPatient(patientId);
    });
  }
  
  // Función para seleccionar paciente y llenar el formulario
  function selectPatient(patientId) {
    const patient = currentPatients.find(p => p.id == patientId);
    
    if (patient) {
      // Llenar formulario con datos del paciente
      $('#tipo_documento').val(patient.tipo_documento);
      $('#cedula').val(patient.cedula);
      $('#pnombre').val(patient.pnombre);
      $('#snombre').val(patient.snombre || '');
      $('#papellido').val(patient.papellido);
      $('#sapellido').val(patient.sapellido || '');
      $('#fecha_nacimiento').val(patient.fecha_nacimiento);
      $('#sexo').val(patient.sexo);
      $('#nacionalidad').val(patient.nacionalidad || 'Paraguaya');
      
      // Cerrar modal
      $('#patientSearchModal').modal('hide');
      
      // Mostrar mensaje de éxito
      showAlert(`Paciente ${patient.pnombre} ${patient.papellido} seleccionado correctamente`, 'success');
      
      // Actualizar JSON
      refreshEditor();
    }
  }
  
  // Función para buscar por documento desde el formulario principal
  async function searchByDocument() {
    const documentNumber = $('#cedula').val().trim();
    
    if (!documentNumber) {
      showAlert('Por favor ingrese un número de documento para buscar', 'warning');
      return;
    }
    
    const results = await searchPatientsFromAPI({ document: documentNumber });
    
    if (results.length === 0) {
      showAlert('No se encontró ningún paciente con ese documento', 'warning');
      return;
    }
    
    if (results.length === 1) {
      // Si hay solo un resultado, seleccionarlo automáticamente
      selectPatient(results[0].id);
    } else {
      // Si hay múltiples resultados, mostrar el modal
      displayPatientResults(results);
      $('#patientSearchModal').modal('show');
    }
  }

  // === FUNCIONES PARA MANEJAR ELEMENTOS DINÁMICOS ===
  function addCondition() {
    const container = $('#conditions-container');
    const emptyState = $('#conditions-empty');
    
    if (emptyState.length) emptyState.remove();
    
    container.append(conditionTemplate(conditionCounter));
    conditionCounter++;
    refreshEditor();
  }

  function removeCondition(index) {
    $(`#condition-${index}`).remove();
    renumberConditions();
    refreshEditor();
  }

  function renumberConditions() {
    const conditions = $('.dynamic-item[id^="condition-"]');
    if (conditions.length === 0) {
      $('#conditions-container').append('<div class="empty-state" id="conditions-empty">No hay diagnósticos agregados. Haga clic en "Agregar Diagnóstico" para comenzar.</div>');
      conditionCounter = 0;
    } else {
      conditions.each(function(index) {
        $(this).find('.item-number').text(index + 1);
        $(this).find('h6').text(`Diagnóstico #${index + 1}`);
      });
    }
  }

  function addAllergy() {
    const container = $('#allergies-container');
    const emptyState = $('#allergies-empty');
    
    if (emptyState.length) emptyState.remove();
    
    container.append(allergyTemplate(allergyCounter));
    allergyCounter++;
    refreshEditor();
  }

  function removeAllergy(index) {
    $(`#allergy-${index}`).remove();
    renumberAllergies();
    refreshEditor();
  }

  function renumberAllergies() {
    const allergies = $('.dynamic-item[id^="allergy-"]');
    if (allergies.length === 0) {
      $('#allergies-container').append('<div class="empty-state" id="allergies-empty">No hay alergias agregadas. Haga clic en "Agregar Alergia" para comenzar.</div>');
      allergyCounter = 0;
    } else {
      allergies.each(function(index) {
        $(this).find('.item-number').text(index + 1);
        $(this).find('h6').text(`Alergia #${index + 1}`);
      });
    }
  }

  function addMedication() {
    const container = $('#medications-container');
    const emptyState = $('#medications-empty');
    
    if (emptyState.length) emptyState.remove();
    
    container.append(medicationTemplate(medicationCounter));
    medicationCounter++;
    refreshEditor();
  }

  function removeMedication(index) {
    $(`#medication-${index}`).remove();
    renumberMedications();
    refreshEditor();
  }

  function renumberMedications() {
    const medications = $('.dynamic-item[id^="medication-"]');
    if (medications.length === 0) {
      $('#medications-container').append('<div class="empty-state" id="medications-empty">No hay medicamentos agregados. Haga clic en "Agregar Medicamento" para comenzar.</div>');
      medicationCounter = 0;
    } else {
      medications.each(function(index) {
        $(this).find('.item-number').text(index + 1);
        $(this).find('h6').text(`Medicamento #${index + 1}`);
      });
    }
  }

  // Obtener datos de elementos dinámicos
  function getConditionsData() {
    const conditions = [];
    $('.dynamic-item[id^="condition-"]').each(function() {
      const index = $(this).attr('id').split('-')[1];
      const code = $(`.condition-code[data-index="${index}"]`).val();
      const text = $(`.condition-text[data-index="${index}"]`).val();
      const onset = $(`.condition-onset[data-index="${index}"]`).val();
      const verif = $(`.condition-verif[data-index="${index}"]`).val();
      const note = $(`.condition-note[data-index="${index}"]`).val();
      
      if (code || text) {
        conditions.push({
          code: code,
          text: text,
          onset: onset,
          verif: verif,
          note: note
        });
      }
    });
    return conditions;
  }

  function getAllergiesData() {
    const allergies = [];
    $('.dynamic-item[id^="allergy-"]').each(function() {
      const index = $(this).attr('id').split('-')[1];
      const category = $(`.allergy-category[data-index="${index}"]`).val();
      const code = $(`.allergy-code[data-index="${index}"]`).val();
      const text = $(`.allergy-text[data-index="${index}"]`).val();
      
      if (text || code) {
        allergies.push({
          category: category,
          code: code,
          text: text
        });
      }
    });
    return allergies;
  }

  function getMedicationsData() {
    const medications = [];
    $('.dynamic-item[id^="medication-"]').each(function() {
      const index = $(this).attr('id').split('-')[1];
      const text = $(`.medication-text[data-index="${index}"]`).val();
      const date = $(`.medication-date[data-index="${index}"]`).val();
      const dosage = $(`.medication-dosage[data-index="${index}"]`).val();
      
      if (text) {
        medications.push({
          text: text,
          date: date,
          dosage: dosage
        });
      }
    });
    return medications;
  }

  // Inicializar CodeMirror
  var editor = CodeMirror.fromTextArea(document.getElementById("jsonDisplay"), {
    lineNumbers: true,
    mode: "application/json",
    theme: "material",
    readOnly: true,
    lineWrapping: true
  });

  function showAlert(message, type = 'info') {
    const alertHtml = `
      <div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="close" data-dismiss="alert">
          <span>&times;</span>
        </button>
      </div>
    `;
    $('.alert-container').append(alertHtml);
    setTimeout(() => $('.alert').alert('close'), 5000);
  }

  // Mapeos
  function mapTipoDocumento(val) {
    const map = {
      "1": {code: "01", display: "Cédula de Identidad"},
      "2": {code: "02", display: "Cédula Extranjera"}, 
      "3": {code: "03", display: "Pasaporte"}
    };
    return map[val] || null;
  }

  // Construcción del Bundle FHIR manteniendo la estructura exacta
  function buildBundle() {
    const now = new Date(), isoNow = now.toISOString();
    
    // Generar UUIDs
    const bundleUUID = uuidv4();
    const compositionUUID = uuidv4();
    const patientUUID = uuidv4();
    const practitionerUUID = uuidv4();

    // Patient
    const tipoDoc = mapTipoDocumento($("#tipo_documento").val());
    const cedula = $("#cedula").val().trim();
    let identifier = [];
    
    if (tipoDoc && cedula) {
      identifier.push({
        "type" : {
          "coding" : [
            {
              "system" : "https://mspbs.gov.py/fhir/CodeSystem/IdentificadoresPersonaCS",
              "code" : tipoDoc.code,
              "display" : tipoDoc.display
            }
          ]
        },
        "value" : cedula
      });
    }

    const patient = {
      "resourceType" : "Patient",
      "id" : patientUUID,
      "meta" : {
        "profile" : [
          "https://mspbs.gov.py/fhir/StructureDefinition/PacientePy"
        ]
      },
      "text" : {
        "status" : "generated",
        "div" : "<div xmlns=\"http://www.w3.org/1999/xhtml\"><a name=\"Patient_PacienteEjemploPy\"> </a><p class=\"res-header-id\"><b>Generated Narrative: Patient PacienteEjemploPy</b></p><a name=\"PacienteEjemploPy\"> </a><a name=\"hcPacienteEjemploPy\"> </a><div style=\"display: inline-block; background-color: #d9e0e7; padding: 6px; margin: 4px; border: 1px solid #8da1b4; border-radius: 5px; line-height: 60%\"><p style=\"margin-bottom: 0px\"/><p style=\"margin-bottom: 0px\">Profile: <a href=\"StructureDefinition-PacientePy.html\">Paciente Paraguay</a></p></div><p style=\"border: 1px #661aff solid; background-color: #e6e6ff; padding: 10px;\">" + 
                ($("#papellido").val() || "Apellido") + " " + ($("#pnombre").val() || "Nombre") + " " + 
                ($("#sexo").val() || "gender") + ", DoB: " + ($("#fecha_nacimiento").val() || "birthDate") + 
                " ( " + (tipoDoc ? tipoDoc.display : "Documento") + ": " + cedula + ")</p><hr/></div>"
      },
      "identifier" : identifier,
      "name" : [
        {
          "family" : $("#papellido").val(),
          "given" : [$("#pnombre").val()].filter(Boolean)
        }
      ],
      "gender" : $("#sexo").val(),
      "birthDate" : $("#fecha_nacimiento").val()
    };

    // Practitioner
    const practitioner = {
      "resourceType" : "Practitioner",
      "id" : practitionerUUID,
      "meta" : {
        "profile" : [
          "https://mspbs.gov.py/fhir/StructureDefinition/PractitionerPy"
        ]
      },
      "text" : {
        "status" : "generated",
        "div" : "<div xmlns=\"http://www.w3.org/1999/xhtml\"><a name=\"Practitioner_PractitionerEjemploPy\"> </a><p class=\"res-header-id\"><b>Generated Narrative: Practitioner PractitionerEjemploPy</b></p><a name=\"PractitionerEjemploPy\"> </a><a name=\"hcPractitionerEjemploPy\"> </a><div style=\"display: inline-block; background-color: #d9e0e7; padding: 6px; margin: 4px; border: 1px solid #8da1b4; border-radius: 5px; line-height: 60%\"><p style=\"margin-bottom: 0px\"/><p style=\"margin-bottom: 0px\">Profile: <a href=\"StructureDefinition-PractitionerPy.html\">Profesional Paraguay</a></p></div><p><b>identifier</b>: Cédula de Identidad/" + $("#prac_identifier").val() + "</p><p><b>name</b>: " + $("#prac_name").val() + " " + $("#prac_family").val() + " </p></div>"
      },
      "identifier" : [
        {
          "type" : {
            "coding" : [
              {
                "system" : "https://mspbs.gov.py/fhir/CodeSystem/IdentificadoresProfesionalCS",
                "code" : "01",
                "display" : "Cédula de Identidad"
              }
            ]
          },
          "value" : $("#prac_identifier").val()
        }
      ],
      "name" : [
        {
          "family" : $("#prac_family").val(),
          "given" : [$("#prac_name").val()].filter(Boolean)
        }
      ]
    };

    // Conditions (múltiples)
    const conditionsData = getConditionsData();
    const conditionEntries = [];
    const conditionUUIDs = [];
    
    conditionsData.forEach((cond, index) => {
      const conditionUUID = uuidv4();
      conditionUUIDs.push(conditionUUID);
      
      conditionEntries.push({
        "fullUrl" : "urn:uuid:" + conditionUUID,
        "resource" : {
          "resourceType" : "Condition",
          "id" : conditionUUID,
          "meta" : {
            "profile" : [
              "https://mspbs.gov.py/fhir/StructureDefinition/ConditionPy"
            ]
          },
          "text" : {
            "status" : "generated",
            "div" : "<div xmlns=\"http://www.w3.org/1999/xhtml\"><a name=\"Condition_ConditionEjemploParaguay\"> </a><p class=\"res-header-id\"><b>Generated Narrative: Condition ConditionEjemploParaguay</b></p><a name=\"ConditionEjemploParaguay\"> </a><a name=\"hcConditionEjemploParaguay\"> </a><div style=\"display: inline-block; background-color: #d9e0e7; padding: 6px; margin: 4px; border: 1px solid #8da1b4; border-radius: 5px; line-height: 60%\"><p style=\"margin-bottom: 0px\"/><p style=\"margin-bottom: 0px\">Profile: <a href=\"StructureDefinition-ConditionPy.html\">Condition_Paraguay</a></p></div><p><b>verificationStatus</b>: <span title=\"Codes:{http://terminology.hl7.org/CodeSystem/condition-ver-status " + (cond.verif || "confirmed") + "}\">" + (cond.verif || "confirmed") + "</span></p><p><b>code</b>: <span title=\"Codes:{http://hl7.org/fhir/sid/icd-10 " + (cond.code || "") + "}\">" + (cond.text || "") + "</span></p><p><b>subject</b>: <a href=\"Patient-PacienteEjemploPy.html\">" + ($("#papellido").val() || "Apellido") + " " + ($("#pnombre").val() || "Nombre") + "</a></p>" + 
                    (cond.onset ? "<p><b>onset</b>: " + cond.onset + " --&gt; (ongoing)</p>" : "") +
                    (cond.note ? "<p><b>note</b>: </p><blockquote><div><p>" + cond.note + "</p>\n</div></blockquote>" : "") + "</div>"
          },
          "verificationStatus" : {
            "coding" : [
              {
                "system" : "http://terminology.hl7.org/CodeSystem/condition-ver-status",
                "code" : cond.verif || "confirmed"
              }
            ]
          },
          "code" : {
            "coding" : [
              {
                "system" : "http://hl7.org/fhir/sid/icd-10",
                "code" : cond.code || "",
                "display" : cond.text || ""
              }
            ],
            "text" : cond.text || ""
          },
          "subject" : {
            "reference" : "urn:uuid:" + patientUUID
          },
          "onsetPeriod" : cond.onset ? {
            "start" : cond.onset
          } : undefined,
          "note" : cond.note ? [
            {
              "text" : cond.note
            }
          ] : undefined
        }
      });
    });

    // Allergies (múltiples)
    const allergiesData = getAllergiesData();
    const allergyEntries = [];
    const allergyUUIDs = [];
    
    allergiesData.forEach((allergy, index) => {
      const allergyUUID = uuidv4();
      allergyUUIDs.push(allergyUUID);
      
      allergyEntries.push({
        "fullUrl" : "urn:uuid:" + allergyUUID,
        "resource" : {
          "resourceType" : "AllergyIntolerance",
          "id" : allergyUUID,
          "meta" : {
            "profile" : [
              "https://mspbs.gov.py/fhir/StructureDefinition/AlergiaPy"
            ]
          },
          "text" : {
            "status" : "generated",
            "div" : "<div xmlns=\"http://www.w3.org/1999/xhtml\"><a name=\"AllergyIntolerance_AlergiaPeruMedicamento\"> </a><p class=\"res-header-id\"><b>Generated Narrative: AllergyIntolerance AlergiaPeruMedicamento</b></p><a name=\"AlergiaPeruMedicamento\"> </a><a name=\"hcAlergiaPeruMedicamento\"> </a><div style=\"display: inline-block; background-color: #d9e0e7; padding: 6px; margin: 4px; border: 1px solid #8da1b4; border-radius: 5px; line-height: 60%\"><p style=\"margin-bottom: 0px\"/><p style=\"margin-bottom: 0px\">Profile: <a href=\"StructureDefinition-AlergiaPy.html\">Alergias/Intolerancia Paraguay</a></p></div><p><b>clinicalStatus</b>: <span title=\"Codes:\">active</span></p><p><b>verificationStatus</b>: <span title=\"Codes:\">confirmed</span></p><p><b>category</b>: " + (allergy.category || "medication") + "</p><p><b>code</b>: <span title=\"Codes:{http://hl7.org/fhir/sid/icd-10 " + (allergy.code || "T36.0X5") + "}\">" + (allergy.text || "") + "</span></p><p><b>patient</b>: <a href=\"Patient/PacienteEjemploParaguay\">Paciente Ejemplo</a></p></div>"
          },
          "clinicalStatus" : {
            "coding" : [
              {
                "code" : "active"
              }
            ]
          },
          "verificationStatus" : {
            "coding" : [
              {
                "code" : "confirmed"
              }
            ]
          },
          "category" : [
            allergy.category || "medication"
          ],
          "code" : {
            "coding" : [
              {
                "system" : "http://hl7.org/fhir/sid/icd-10",
                "code" : allergy.code || "T36.0X5",
                "display" : allergy.text || ""
              }
            ],
            "text" : allergy.text || ""
          },
          "patient" : {
            "reference" : "urn:uuid:" + patientUUID,
            "display" : "Paciente Ejemplo"
          }
        }
      });
    });

    // Medications (múltiples)
    const medicationsData = getMedicationsData();
    const medicationEntries = [];
    const medicationUUIDs = [];
    
    medicationsData.forEach((med, index) => {
      const medicationUUID = uuidv4();
      medicationUUIDs.push(medicationUUID);
      
      medicationEntries.push({
        "fullUrl" : "urn:uuid:" + medicationUUID,
        "resource" : {
          "resourceType" : "MedicationStatement",
          "id" : medicationUUID,
          "meta" : {
            "profile" : [
              "https://mspbs.gov.py/fhir/StructureDefinition/MedicationStatementPy"
            ]
          },
          "text" : {
            "status" : "generated",
            "div" : "<div xmlns=\"http://www.w3.org/1999/xhtml\"><a name=\"MedicationStatement_MedicationStatementEjemploParaguay\"> </a><p class=\"res-header-id\"><b>Generated Narrative: MedicationStatement MedicationStatementEjemploParaguay</b></p><a name=\"MedicationStatementEjemploParaguay\"> </a><a name=\"hcMedicationStatementEjemploParaguay\"> </a><div style=\"display: inline-block; background-color: #d9e0e7; padding: 6px; margin: 4px; border: 1px solid #8da1b4; border-radius: 5px; line-height: 60%\"><p style=\"margin-bottom: 0px\"/><p style=\"margin-bottom: 0px\">Profile: <a href=\"StructureDefinition-MedicationStatementPy.html\">Medication Paraguay</a></p></div><p><b>status</b>: Active</p><p><b>medication</b>: <span title=\"Codes:\">" + (med.text || "") + "</span></p><p><b>subject</b>: <a href=\"Patient/PacienteEjemploParaguay\">Patient/PacienteEjemploParaguay</a></p>" + 
                    (med.date ? "<p><b>effective</b>: " + med.date + "</p>" : "") +
                    "<h3>Dosages</h3><table class=\"grid\"><tr><td style=\"display: none\">-</td><td><b>Text</b></td><td><b>Route</b></td></tr><tr><td style=\"display: none\">*</td><td>" + (med.dosage || "") + "</td><td><span title=\"Codes:\">Oral</span></td></tr></table></div>"
          },
          "status" : "active",
          "medicationCodeableConcept" : {
            "text" : med.text || ""
          },
          "subject" : {
            "reference" : "urn:uuid:" + patientUUID
          },
          "effectiveDateTime" : med.date || undefined,
          "dosage" : [
            {
              "text" : med.dosage || "",
              "route" : {
                "text" : "Oral"
              }
            }
          ]
        }
      });
    });

    // Composition
    const composition = {
      "resourceType" : "Composition",
      "id" : compositionUUID,
      "meta" : {
        "profile" : [
          "https://mspbs.gov.py/fhir/StructureDefinition/CompositionPy"
        ]
      },
      "text" : {
        "status" : "generated",
        "div" : "<div xmlns=\"http://www.w3.org/1999/xhtml\"><a name=\"Composition_CompositionEjemploPy\"> </a><p class=\"res-header-id\"><b>Generated Narrative: Composition CompositionEjemploPy</b></p><a name=\"CompositionEjemploPy\"> </a><a name=\"hcCompositionEjemploPy\"> </a><div style=\"display: inline-block; background-color: #d9e0e7; padding: 6px; margin: 4px; border: 1px solid #8da1b4; border-radius: 5px; line-height: 60%\"><p style=\"margin-bottom: 0px\"/><p style=\"margin-bottom: 0px\">Profile: <a href=\"StructureDefinition-CompositionPy.html\">Documento Clinico Paraguay</a></p></div><p><b>status</b>: Final</p><p><b>type</b>: <span title=\"Codes:{http://loinc.org 11450-4}\">Problem list - Reported</span></p><p><b>date</b>: " + ($("#comp_date").val() || "2025-09-01") + "</p><p><b>author</b>: <a href=\"Practitioner-PractitionerEjemploPy.html\">Practitioner " + ($("#prac_name").val() || "Nombre") + " " + ($("#prac_family").val() || "Apellido") + " </a></p><p><b>title</b>: " + ($("#comp_title").val() || "Ejemplo de Documento Clinico Paraguay") + "</p><p><b>custodian</b>: <a href=\"Organization-OrganizacionEjemploPy.html\">Organization " + ($("#comp_custodian").val() || "HOSPITAL GENERAL DE CORONEL OVIEDO") + "</a></p></div>"
      },
      "status" : "final",
      "type" : {
        "coding" : [
          {
            "system" : "http://loinc.org",
            "code" : "11450-4"
          }
        ]
      },
      "subject" : {
        "reference" : "urn:uuid:" + patientUUID
      },
      "date" : $("#comp_date").val() || "2025-09-01",
      "author" : [
        {
          "reference" : "urn:uuid:" + practitionerUUID
        }
      ],
      "title" : $("#comp_title").val() || "Ejemplo de Documento Clinico Paraguay",
      "custodian" : {
        "reference" : "Organization/OrganizacionEjemploPy"
      },
      "section" : []
    };

    // Agregar secciones según datos disponibles
    if (conditionsData.length > 0) {
      composition.section.push({
        "title" : "Diagnósticos",
        "code" : {
          "coding" : [
            {
              "system" : "http://loinc.org",
              "code" : "11450-4",
              "display" : "Problem list Reported"
            }
          ]
        },
        "text" : {
          "status" : "generated",
          "div" : "<div xmlns=\"http://www.w3.org/1999/xhtml\">Diagnósticos</div>"
        },
        "entry" : conditionUUIDs.map(uuid => ({
          "reference" : "urn:uuid:" + uuid
        }))
      });
    }

    if (allergiesData.length > 0) {
  composition.section.push({
    "title": "Alergias e Intolerancias",
    "code": {
      "coding": [
        {
          "system": "http://loinc.org",
          "code": "48765-2",
          "display": "Allergies and intolerances"
        }
      ]
    },
    "text": {
      "status": "generated",
      "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\">Alergias</div>"
    },
    "entry": allergyUUIDs.map(uuid => ({
      "reference": "urn:uuid:" + uuid
    }))
  });
}

if (medicationsData.length > 0) {
  composition.section.push({
    "title": "Medicación",
    "code": {
      "coding": [
        {
          "system": "http://loinc.org",
          "code": "10160-0",
          "display": "History of Medication use"
        }
      ]
    },
    "text": {
      "status": "generated",
      "div": "<div xmlns=\"http://www.w3.org/1999/xhtml\">Medicación</div>"
    },
    "entry": medicationUUIDs.map(uuid => ({
      "reference": "urn:uuid:" + uuid
    }))
  });
}

    // Bundle final - MANTENIENDO EL ORDEN EXACTO
    const bundle = {
      "resourceType" : "Bundle",
      "id" : "BundleDocumentEjemploParaguay",
      "meta" : {
        "profile" : [
          "https://mspbs.gov.py/fhir/StructureDefinition/BundleDocPy",
          "https://mspbs.gov.py/fhir/StructureDefinition/BundlePy"
        ]
      },
      "identifier" : {
        "system" : "urn:oid",
        "value" : bundleUUID
      },
      "type" : "document",
      "timestamp" : isoNow,
      "entry" : [
        {
          "fullUrl" : "urn:uuid:" + compositionUUID,
          "resource" : composition
        }
      ]
    };

    // Agregar Conditions
    conditionEntries.forEach(entry => {
      bundle.entry.push(entry);
    });

    // Agregar Allergies
    allergyEntries.forEach(entry => {
      bundle.entry.push(entry);
    });

    // Agregar Medications
    medicationEntries.forEach(entry => {
      bundle.entry.push(entry);
    });

    // Agregar Patient y Practitioner (siempre al final)
    bundle.entry.push({
      "fullUrl" : "urn:uuid:" + patientUUID,
      "resource" : patient
    });

    bundle.entry.push({
      "fullUrl" : "urn:uuid:" + practitionerUUID,
      "resource" : practitioner
    });

    return bundle;
  }

  // Actualizar editor
  function refreshEditor() {
    try {
      const bundle = buildBundle();
      editor.setValue(JSON.stringify(bundle, null, 2));
    } catch (error) {
      console.error('Error al generar JSON:', error);
      showAlert('Error al generar el documento FHIR', 'danger');
    }
  }

  // Validar JSON
  function validateJSON() {
    try {
      const jsonString = editor.getValue();
      JSON.parse(jsonString);
      showAlert('JSON válido ✓', 'success');
      return true;
    } catch (error) {
      showAlert('JSON inválido: ' + error.message, 'danger');
      return false;
    }
  }

  // Limpiar formulario
  function clearForm() {
    if (confirm('¿Está seguro de que desea limpiar todos los campos?')) {
      // Limpiar campos estáticos
      $('input[type="text"], input[type="date"], select, textarea').not('.dynamic-item input, .dynamic-item select, .dynamic-item textarea').val('');
      $('#comp_title').val('Ejemplo de Documento Clinico Paraguay');
      $('#comp_date').val('<?php echo date('Y-m-d'); ?>');
      $('#comp_custodian').val('HOSPITAL GENERAL DE CORONEL OVIEDO');
      $('#nacionalidad').val('Paraguaya');
      
      // Limpiar elementos dinámicos
      $('.dynamic-item').remove();
      conditionCounter = 0;
      allergyCounter = 0;
      medicationCounter = 0;
      
      // Restaurar estados vacíos
      $('#conditions-container').append('<div class="empty-state" id="conditions-empty">No hay diagnósticos agregados. Haga clic en "Agregar Diagnóstico" para comenzar.</div>');
      $('#allergies-container').append('<div class="empty-state" id="allergies-empty">No hay alergias agregadas. Haga clic en "Agregar Alergia" para comenzar.</div>');
      $('#medications-container').append('<div class="empty-state" id="medications-empty">No hay medicamentos agregados. Haga clic en "Agregar Medicamento" para comenzar.</div>');
      
      refreshEditor();
      showAlert('Formulario limpiado correctamente', 'info');
    }
  }

  // === EVENT LISTENERS ===
  $(document).on('input change', 'input, select, textarea', refreshEditor);

  // Event listeners para botones de agregar
  $('#addConditionBtn').click(function(e) {
    e.preventDefault();
    addCondition();
  });

  $('#addAllergyBtn').click(function(e) {
    e.preventDefault();
    addAllergy();
  });

  $('#addMedicationBtn').click(function(e) {
    e.preventDefault();
    addMedication();
  });

  // Event listeners para botones de eliminar (delegación)
  $(document).on('click', '[data-remove-condition]', function(e) {
    e.preventDefault();
    const index = $(this).data('remove-condition');
    removeCondition(index);
  });

  $(document).on('click', '[data-remove-allergy]', function(e) {
    e.preventDefault();
    const index = $(this).data('remove-allergy');
    removeAllergy(index);
  });

  $(document).on('click', '[data-remove-medication]', function(e) {
    e.preventDefault();
    const index = $(this).data('remove-medication');
    removeMedication(index);
  });

  // Event listeners para búsqueda de pacientes
  $('#searchByDocument').click(function(e) {
    e.preventDefault();
    searchByDocument();
  });

  $('#btnSearchDocument').click(async function(e) {
    e.preventDefault();
    const documentNumber = $('#searchDocument').val().trim();
    if (documentNumber) {
      const results = await searchPatientsFromAPI({ document: documentNumber });
      displayPatientResults(results);
    }
  });

  $('#btnSearchName').click(async function(e) {
    e.preventDefault();
    const name = $('#searchName').val().trim();
    if (name) {
      const results = await searchPatientsFromAPI({ name: name });
      displayPatientResults(results);
    }
  });

  $('#selectPatientBtn').click(function(e) {
    e.preventDefault();
    if (selectedPatientId) {
      selectPatient(selectedPatientId);
    }
  });

   // Cargar todos los pacientes al abrir el modal
  $('#patientSearchModal').on('show.bs.modal', async function() {
    const allPatients = await searchPatientsFromAPI();
    displayPatientResults(allPatients);
    selectedPatientId = null;
    $('#selectPatientBtn').prop('disabled', true);
  });
  
  // SOLUCIÓN: Manejar correctamente el foco al cerrar el modal
  $('#patientSearchModal').on('hidden.bs.modal', function() {
    // Restablecer el foco al botón que abrió el modal
    $('[data-target="#patientSearchModal"]').focus();
    
    // Limpiar cualquier selección
    $('.table-patient').removeClass('selected');
    $('.patient-radio').prop('checked', false);
    
    // Limpiar campos de búsqueda
    $('#searchDocument').val('');
    $('#searchName').val('');
  });

  $('#validateJson').click(function(e) {
    e.preventDefault();
    validateJSON();
  });

  $('#downloadJson').click(function(e) {
    e.preventDefault();
    if (validateJSON()) {
      const bundle = buildBundle();
      const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(bundle, null, 2));
      const dlAnchor = document.createElement('a');
      dlAnchor.setAttribute('href', dataStr);
      dlAnchor.setAttribute('download', 'bundle_fhir_paraguay_' + new Date().toISOString().slice(0, 10) + '.json');
      document.body.appendChild(dlAnchor);
      dlAnchor.click();
      document.body.removeChild(dlAnchor);
      showAlert('Documento descargado correctamente', 'success');
    }
  });

  $('#clearForm').click(function(e) {
    e.preventDefault();
    clearForm();
  });

  // Inicialización
  refreshEditor();
  showAlert('Formulario listo. Complete los datos requeridos.', 'info');
});
</script>