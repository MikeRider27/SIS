<?php
include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

$dbconn = getConnection();
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid text-center">
      <h1><strong>Registro de Organización de Salud</strong></h1>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        
        <!-- Formulario -->
        <div class="col-md-6">
          <form id="formOrg" class="user">
            <div class="card card-danger">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-hospital me-2"></i>DATOS DE LA ORGANIZACIÓN</h3>
              </div>
              <div class="card-body">
                <div class="form-section mb-4">
                  <h6 class="section-title text-primary mb-3">Identificación</h6>
                  <div class="row">
                    <div class="col-lg-12 mb-3">
                      <label class="form-label required">Código / Identificador:</label>
                      <input type="text" class="form-control" name="identifier" id="identifier" placeholder="Ej: 0005000.00010102" required>
                    </div>
                  </div>
                </div>

                <div class="form-section mb-4">
                  <h6 class="section-title text-primary mb-3">Información de la Organización</h6>
                  <div class="row">
                    <div class="col-lg-12 mb-3">
                      <label class="form-label required">Tipo de Organización:</label>
                      <select class="form-control" name="type" id="type" required>
                        <option value="">Seleccione</option>
                        <option value="HG">HOSPITAL GENERAL</option>
                        <option value="HR">HOSPITAL REGIONAL</option>
                        <option value="HD">HOSPITAL DISTRITAL</option>
                        <option value="HP">HOSPITAL PRIVADO</option>
                        <option value="HE">HOSPITAL ESPECIALIZADO</option>
                        <option value="HESC">HOSPITAL ESCUELA</option>
                        <option value="IP">INSTITUCIÓN PRIVADA</option>
                        <option value="IPS">INSTITUTO DE PREVISION SOCIAL</option>
                      </select>
                    </div>
                    <div class="col-lg-12 mb-3">
                      <label class="form-label required">Nombre de la Organización:</label>
                      <input type="text" class="form-control" name="name" id="name" placeholder="Ej: HOSPITAL GENERAL DE CORONEL OVIEDO" required>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-footer text-right">
                <button id="guardarOrg" type="submit" class="btn btn-outline-danger">
                  <i class="fas fa-check"></i> <strong>Guardar</strong>
                </button>
                <button type="button" id="limpiarForm" class="btn btn-outline-secondary ml-2">
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
                <i class="fas fa-code me-2"></i><strong>Visor JSON (FHIR Organization)</strong>
              </h5>
            </div>
            <div class="card-body p-0">
              <textarea id="jsonDisplayOrg" class="d-none"></textarea>
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
  var editor = CodeMirror.fromTextArea(document.getElementById("jsonDisplayOrg"), {
    lineNumbers: true,
    mode: "application/json",
    theme: "material",
    readOnly: true
  });

  // Función para mostrar/ocultar loading
  function toggleLoading(show) {
    if (show) {
      $('#loadingOverlay').show();
    } else {
      $('#loadingOverlay').hide();
    }
  }

  // Construir JSON dinámico para Organization (estructura corregida)
  function buildOrganizationJSON(){
    let identifier = $("#identifier").val();
    let type       = $("#type").val();
    let name       = $("#name").val();

    let organization = {
      resourceType: "Organization",
      id: identifier || "",
      meta: {
        profile: ["https://mspbs.gov.py/fhir/StructureDefinition/OrganizacionPy"]
      },
      identifier: identifier ? [{ value: identifier }] : [],
      type: type ? [{ text: type }] : [],
      name: name || ""
    };

    editor.setValue(JSON.stringify(organization, null, 2));
  }

  // Escuchar cambios en el formulario
  $("#formOrg input, #formOrg select").on("input change", function(){
    buildOrganizationJSON();
  });

  // Limpiar formulario
  $('#limpiarForm').click(function() {
    if(confirm('¿Está seguro de que desea limpiar el formulario?')) {
      $('#formOrg')[0].reset();
      buildOrganizationJSON();
      toastr.info("Formulario limpiado correctamente");
    }
  });

  // Validación básica del formulario
  function validarFormulario() {
    let valido = true;
    const camposRequeridos = ['#identifier', '#type', '#name'];
    
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

  // Guardar Organización
  $("#formOrg").submit(function(e){
    e.preventDefault();

    if (!validarFormulario()) {
      toastr.warning("Por favor complete todos los campos obligatorios");
      return;
    }

    $('#guardarOrg').attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
    toggleLoading(true);

    $.ajax({
      url: '/backend/services/organization/createOrganization.php',
      method: 'POST',
      data: $("#formOrg").serialize(),
      dataType: 'json',
      success: function(response){
        if(response.status === "success"){
          toastr.success("Organización guardada correctamente");
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
        $('#guardarOrg').removeAttr('disabled').html('<i class="fas fa-check"></i> <strong>Guardar</strong>');
        toggleLoading(false);
      }
    });
  });

  // Remover clases de error al interactuar con los campos
  $("#formOrg input, #formOrg select").on('input change', function() {
    $(this).removeClass('is-invalid');
  });

  // Inicializar JSON vacío
  buildOrganizationJSON();
});
</script>