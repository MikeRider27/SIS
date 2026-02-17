<?php
include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

$dbconn = getConnectionFHIR();

// Cargar lista de países (si se necesita en otra parte)
$sql = "SELECT name, alpha_2, alpha_3 FROM public.country;";
$stmt = $dbconn->prepare($sql);
$stmt->execute();
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header text-center">
    <div class="container-fluid">
      <h1><strong>Terminology Lookup ($lookup)</strong></h1>
      <p class="text-muted">Consulta de términos en el servidor FHIR Terminology</p>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <!-- Formulario -->
      <div class="card card-danger">
        <div class="card-header">
          <h3 class="card-title"><strong>Parámetros de búsqueda</strong></h3>
        </div>
        <div class="card-body">
          <div class="row justify-content-center">
            <div class="col-sm-5">
              <label for="id" class="font-size-14">Código:</label>
              <input type="text" id="id" name="id" class="form-control font-size-14" placeholder="Ej: 123456" autocomplete="off" />
            </div>
            <div class="col-sm-5">
              <label for="local" class="font-size-14">Sistema (System):</label>
              <input type="text" id="local" name="local" class="form-control font-size-14"
                     value="http://node-acme.org/terminology"
                     placeholder="Ej: http://snomed.info/sct" autocomplete="off" />
            </div>
          </div>

          <div class="row justify-content-center mt-4">
            <div class="col-sm-6 text-center">
              <button id="filtrar" class="btn btn-outline-danger">
                <i class="fa fa-search"></i> <strong>Consultar</strong>
              </button>
              <div id="loading" class="mt-3 text-center" style="display:none;">
                <i class="fa fa-spinner fa-spin"></i> Procesando solicitud...
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Resultados -->
      <div class="card card-danger" id="DATOSFHIR" style="display:none;">
        <div class="card-header">
          <h3 class="card-title"><strong>Resultado</strong></h3>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-sm-4">
              <label for="system" class="font-size-14">System:</label>
              <input type="text" id="system" class="form-control font-size-14" readonly />
            </div>
            <div class="col-sm-4">
              <label for="display" class="font-size-14">Display:</label>
              <input type="text" id="display" class="form-control font-size-14" readonly />
            </div>
            <div class="col-sm-4">
              <label for="version" class="font-size-14">Version:</label>
              <input type="text" id="version" class="form-control font-size-14" readonly />
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</div>

<?php
include('/var/www/html/view/includes/footer.php');
?>

<script>
$(function() {
  // Función loader
  function mostrarLoader(mostrar) {
    $('#loading').toggle(mostrar);
  }

  // Click en “Consultar”
  $('#filtrar').on('click', function(e) {
    e.preventDefault();

    const code = $('#id').val().trim();
    const system = $('#local').val().trim();

    if (!code || !system) {
      toastr.warning('Debe ingresar el código y el sistema.');
      return;
    }

    mostrarLoader(true);
    $('#DATOSFHIR').hide();

    $.ajax({
      url: '/backend/services/lookup.php',
      method: 'POST',
      data: JSON.stringify({ code, system }),
      contentType: 'application/json',
      success: function(response) {
        mostrarLoader(false);

        try {
          // Si el servidor devuelve string JSON
          if (typeof response === 'string') {
            response = JSON.parse(response);
          }

          if (response.error) {
            toastr.error(response.error);
            return;
          }

          $('#DATOSFHIR').show();

          $('#system').val(response.system || '');
          $('#display').val(response.display || '');
          $('#version').val(response.version || '');

          if (response.display) {
            toastr.success(`Término encontrado: ${response.display}`);
          } else {
            toastr.info('No se encontró un display asociado a este código.');
          }

          console.log('Respuesta FHIR Lookup:', response);
        } catch (e) {
          toastr.error('Error procesando la respuesta del servidor.');
          console.error(e);
        }
      },
      error: function(xhr, status, error) {
        mostrarLoader(false);
        toastr.error('Error al comunicarse con el servidor de terminología.');
        console.error('Error Lookup:', error);
      }
    });
  });
});
</script>
