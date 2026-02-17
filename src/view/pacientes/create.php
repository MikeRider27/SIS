<?php
include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

$dbconn = getConnection();
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid text-center">
      <h1><strong>Consulta Ambulatoria - Registro Diario de Consultas</strong></h1>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        
        <!-- Formulario -->
        <div class="col-md-6">
          <form id="form" class="user">
            <div class="card card-danger">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user me-2"></i>DATOS DEL/LA PACIENTE</h3>
              </div>
              <div class="card-body">
                <div class="form-section mb-4">
                  <h6 class="section-title text-primary mb-3">Identificación</h6>
                  <div class="row">
                    <div class="col-lg-6 mb-3">
                      <label class="form-label required">Tipo de Documento de Identidad:</label>
                      <select class="form-control" id="tipo_documento" name="tipo_documento" required>
                        <option value="">Seleccione</option>
                        <option value="1">Cédula de Identidad</option>
                        <option value="2">Cédula Extranjera</option>
                        <option value="3">Pasaporte</option>
                      </select>
                    </div>
                    <div class="col-lg-6 mb-3">
                      <label class="form-label required">Número de Documento:</label>
                      <div class="input-group">
                        <input type="text" class="form-control" name="cedula" id="cedula" disabled required>
                        <span class="input-group-append" id="search">
                          <button id="getDatosCedula" type="button" class="btn btn-outline-danger">
                            <i class="fas fa-search"></i>
                          </button>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-section mb-4">
                  <h6 class="section-title text-primary mb-3">Nombres y Apellidos</h6>
                  <div class="row">
                    <div class="col-lg-6 mb-3">
                      <label class="form-label required">Primer Nombre:</label>
                      <input type="text" class="form-control" name="pnombre" id="pnombre" required>
                    </div>
                    <div class="col-lg-6 mb-3">
                      <label class="form-label">Segundo Nombre:</label>
                      <input type="text" class="form-control" name="snombre" id="snombre">
                    </div>
                    <div class="col-lg-6 mb-3">
                      <label class="form-label required">Primer Apellido:</label>
                      <input type="text" class="form-control" name="papellido" id="papellido" required>
                    </div>
                    <div class="col-lg-6 mb-3">
                      <label class="form-label">Segundo Apellido:</label>
                      <input type="text" class="form-control" name="sapellido" id="sapellido">
                    </div>
                  </div>
                </div>

                <div class="form-section">
                  <h6 class="section-title text-primary mb-3">Información Personal</h6>
                  <div class="row">
                    <div class="col-lg-6 mb-3">
                      <label class="form-label required">Fecha Nacimiento:</label>
                      <input type="date" class="form-control" name="fecha_nacimiento" id="fecha_nacimiento" required>
                    </div>
                    <div class="col-lg-6 mb-3">
                      <label class="form-label required">Sexo:</label>
                      <select class="form-control" id="sexo" name="sexo" required>
                        <option value="">Seleccione</option>
                        <option value="1">Femenino</option>
                        <option value="2">Masculino</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-footer text-right">
                <button id="guardar" type="submit" class="btn btn-outline-danger">
                  <i class="fas fa-check"></i> <strong>Guardar</strong>
                </button>
                <button type="button" id="limpiar" class="btn btn-outline-secondary ml-2">
                  <i class="fas fa-broom"></i> <strong>Limpiar</strong>
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- JSON Viewer -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header bg-dark">
              <h5 class="card-title mb-0 text-white">
                <i class="fas fa-code me-2"></i><strong>Visor JSON (FHIR Patient)</strong>
              </h5>
            </div>
            <div class="card-body p-0">
              <textarea id="jsonDisplay" class="d-none"></textarea>
              <div class="json-info-alert p-3 border-bottom">
                <small class="text-muted">
                  <i class="fas fa-info-circle me-1"></i>
                  El JSON se genera automáticamente al completar el formulario
                </small>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
  <div class="spinner-container">
    <div class="spinner-border text-danger" role="status">
      <span class="sr-only">Cargando...</span>
    </div>
    <p class="mt-2 mb-0">Procesando...</p>
  </div>
</div>

<?php
include('/var/www/html/view/includes/footer.php');
?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Toastr -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- CodeMirror -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/material.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>

<style>
:root {
  --primary-color: #dc3545;
  --secondary-color: #6c757d;
  --success-color: #28a745;
  --border-radius: 6px;
}

.card {
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  border: 1px solid #e3e6f0;
}

.card-header {
  border-bottom: 1px solid #e3e6f0;
  background: linear-gradient(135deg, var(--primary-color) 0%, #c82333 100%);
}

.form-control, .form-select {
  border-radius: var(--border-radius);
  border: 1px solid #d1d3e2;
  transition: all 0.3s;
}

.form-control:focus, .form-select:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.btn {
  border-radius: var(--border-radius);
  transition: all 0.3s;
}

.btn-outline-danger:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.CodeMirror {
  height: calc(100vh - 250px);
  border-radius: 0 0 var(--border-radius) var(--border-radius);
  font-size: 13px;
}

.form-section {
  padding: 15px;
  border-radius: var(--border-radius);
  background-color: #f8f9fa;
  border-left: 4px solid var(--primary-color);
}

.section-title {
  font-weight: 600;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.required::after {
  content: " *";
  color: var(--primary-color);
}

.form-label {
  font-weight: 500;
  margin-bottom: 5px;
  font-size: 0.9rem;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 9999;
  display: flex;
  justify-content: center;
  align-items: center;
}

.spinner-container {
  background: white;
  padding: 30px;
  border-radius: var(--border-radius);
  text-align: center;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.json-info-alert {
  background-color: #e9ecef;
  border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.input-group-append .btn {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}

@media (max-width: 768px) {
  .CodeMirror {
    height: 400px;
  }
  
  .form-section {
    padding: 10px;
  }
}
</style>

<script>
$(function(){
  // Configuración de Toastr
  toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "timeOut": "5000"
  };

  // Inicializar CodeMirror
  var editor = CodeMirror.fromTextArea(document.getElementById("jsonDisplay"), {
    lineNumbers: true,
    mode: "application/json",
    theme: "material",
    readOnly: true
  });

  // Inicialmente ocultamos el botón buscar
  $("#search").hide();

  // Función para mostrar/ocultar loading
  function toggleLoading(show) {
    if (show) {
      $('#loadingOverlay').show();
    } else {
      $('#loadingOverlay').hide();
    }
  }

  // Función para mapear sexo a estándar FHIR
  function mapSexo(val) {
    if(val == "1") return "female";
    if(val == "2") return "male";
    return "unknown";
  }

  // Construir JSON dinámico
  function buildPatientJSON(){
    let cedula   = $("#cedula").val();
    let pnombre  = $("#pnombre").val();
    let snombre  = $("#snombre").val();
    let papellido= $("#papellido").val();
    let sapellido= $("#sapellido").val();
    let fecha    = $("#fecha_nacimiento").val();
    let sexo     = mapSexo($("#sexo").val());

    // Tipo de documento dinámico
    let tipo_documento_val = $("#tipo_documento").val();
    let tipo_documento = null;

    if (tipo_documento_val == "1") {
      tipo_documento = {
        system: "https://mspbs.gov.py/fhir/CodeSystem/IdentificadoresPersonaCS",
        code: "01",
        display: "Cédula de Identidad"
      };
    } else if (tipo_documento_val == "2") {
      tipo_documento = {
        system: "https://mspbs.gov.py/fhir/CodeSystem/IdentificadoresPersonaCS",
        code: "02",
        display: "Cédula Extranjera"
      };
    } else if (tipo_documento_val == "3") {
      tipo_documento = {
        system: "https://mspbs.gov.py/fhir/CodeSystem/IdentificadoresPersonaCS",
        code: "03",
        display: "Pasaporte"
      };
    }

    let patient = {
      resourceType: "Patient",
      meta: {
        profile: ["https://mspbs.gov.py/fhir/StructureDefinition/PacientePy"]
      },
      identifier: tipo_documento ? [{
        type: { coding: [ tipo_documento ] },
        value: cedula
      }] : [],
      name: [{
        family: (papellido + " " + sapellido).trim(),
        given: [pnombre, snombre].filter(Boolean)
      }],
      gender: sexo,
      birthDate: fecha
    };

    editor.setValue(JSON.stringify(patient, null, 2));
  }

  // Escuchar cambios en el formulario
  $("#form input, #form select").on("input change", function(){
    buildPatientJSON();
  });

  // Mostrar/ocultar botón buscar según tipo de documento
  $("#tipo_documento").on("change", function(){
    if($(this).val() === "1"){ 
      $("#search").show();
      $("#cedula").prop("disabled", false);
    } else {
      $("#search").hide();
      $("#cedula").prop("disabled", false);
      buildPatientJSON();
    }
  });

  // Buscar por cédula
  function obtenerDatosCedula() {
    const cedula = $('#cedula').val();
    if (!cedula) {
      toastr.warning("Por favor ingrese un número de cédula");
      return;
    }

    $('#getDatosCedula').attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    toggleLoading(true);

    $.ajax({
      url: '/backend/getPoliciaLocal.php',
      method: 'POST',
      data: { cedula, search: 'SI' },
      success: function(data){
        try {
          const response = JSON.parse(data);
          if (response.status === "success") {
            const {
              first_name,
              second_name,
              last_name,
              slast_name,
              fecha_nacimiento,
              codigo_genero
            } = response.persona;

            $('#pnombre').val(first_name);
            $('#snombre').val(second_name);
            $('#papellido').val(last_name);
            $('#sapellido').val(slast_name);
            $('#fecha_nacimiento').val(fecha_nacimiento);
            $('#sexo').val(codigo_genero).trigger('change');

            buildPatientJSON();
            toastr.success("Datos del paciente cargados correctamente");
          } else {
            toastr.error("No se encontraron datos para la cédula proporcionada");
          }
        } catch (e) {
          toastr.error("Error al procesar la respuesta del servidor");
          console.error(e);
        }
      },
      error: function(xhr, status, error) {
        toastr.error("Error de conexión: " + error);
      },
      complete: function(){
        $('#getDatosCedula').removeAttr('disabled').html('<i class="fas fa-search"></i>');
        toggleLoading(false);
      }
    });
  }

  // Evento buscar
  $('#getDatosCedula').click(function(e) {
    e.preventDefault(); 
    obtenerDatosCedula();
  });

  // Limpiar formulario
  $('#limpiar').click(function() {
    if(confirm('¿Está seguro de que desea limpiar el formulario?')) {
      $('#form')[0].reset();
      $("#search").hide();
      $("#cedula").prop("disabled", true);
      buildPatientJSON();
      toastr.info("Formulario limpiado correctamente");
    }
  });

  // Validación básica del formulario
  function validarFormulario() {
    let valido = true;
    const camposRequeridos = ['#tipo_documento', '#cedula', '#pnombre', '#papellido', '#fecha_nacimiento', '#sexo'];
    
    camposRequeridos.forEach(function(campo) {
      if (!$(campo).val()) {
        valido = false;
        $(campo).addClass('is-invalid');
      } else {
        $(campo).removeClass('is-invalid');
      }
    });
    
    return valido;
  }

  // Guardar paciente
  $("#form").submit(function(e){
    e.preventDefault();

    if (!validarFormulario()) {
      toastr.warning("Por favor complete todos los campos obligatorios");
      return;
    }

    $('#guardar').attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
    toggleLoading(true);

    $.ajax({
      url: '/backend/services/pacientes/createPaciente.php',
      method: 'POST',
      data: $("#form").serialize(),
      dataType: 'json',
      success: function(response){
        if(response.status === "success"){
          toastr.success("Paciente guardado correctamente");
          if(response.fhir_response){
            editor.setValue(JSON.stringify(response.fhir_response, null, 2));
          }
        } else {
          toastr.error("Error: " + response.message);
        }
      },
      error: function(xhr, status, error){
        toastr.error("Error en el servidor: " + error);
      },
      complete: function(){
        $('#guardar').removeAttr('disabled').html('<i class="fas fa-check"></i> <strong>Guardar</strong>');
        toggleLoading(false);
      }
    });
  });

  // Remover clases de error al interactuar con los campos
  $("#form input, #form select").on('input change', function() {
    $(this).removeClass('is-invalid');
  });

  // Inicializar JSON vacío
  buildPatientJSON();
});
</script>