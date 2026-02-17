<?php
include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

$dbconn = getConnectionFHIR();
$sql = "SELECT name, alpha_2, alpha_3 FROM public.country;";
$stmt = $dbconn->prepare($sql);
$stmt->execute();
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header text-center">
    <div class="container-fluid">
      <h1><strong>Terminology Translate ($translate)</strong></h1>
      <p class="text-muted">Traducción de códigos entre sistemas terminológicos (FHIR Terminology Service)</p>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card card-danger">
        <div class="card-header">
          <h3 class="card-title"><strong>Parámetros de búsqueda</strong></h3>
        </div>
        <div class="card-body">
          <div class="row justify-content-center">
            <div class="col-sm-4">
              <label for="id" class="font-size-14">Código:</label>
              <input type="text" id="id" name="id" class="form-control font-size-14" placeholder="Ej: 123456" autocomplete="off" />
            </div>

            <div class="col-sm-4">
              <label for="local" class="font-size-14">Sistema de origen:</label>
              <select class="form-control select2bs4" id="local" name="local">
                <option value="">Seleccione</option>
                <option value="http://node-acme.org/terminology">Local</option>
                <option value="http://id.who.int/icd/release/11/mms">CIE-11</option>
                <option value="http://racsel.org/connectathon">RACSEL</option>
                <option value="http://hl7.org/fhir/sid/icd-10">CIE-10</option>
                <option value="http://snomed.info/sct">SNOMED</option>
                <option value="http://smart.who.int/pcmt-vaxprequal/CodeSystem/PreQualProductIDs">PreQual</option>
              </select>
            </div>

            <div class="col-sm-4">
              <label for="target" class="font-size-14">Sistema de destino:</label>
              <select class="form-control select2bs4" id="target" name="target">
                <option value="">Seleccione</option>
                <option value="http://node-acme.org/terminology">Local</option>
                <option value="http://id.who.int/icd/release/11/mms">CIE-11</option>
                <option value="http://racsel.org/connectathon">RACSEL</option>
                <option value="http://hl7.org/fhir/sid/icd-10">CIE-10</option>
                <option value="http://snomed.info/sct">SNOMED</option>
                <option value="http://smart.who.int/pcmt-vaxprequal/CodeSystem/PreQualProductIDs">PreQual</option>
              </select>
            </div>
          </div>

          <div class="row justify-content-center mt-4">
            <div class="col-sm-6 text-center">
              <button id="filtrar" class="btn btn-outline-danger">
                <i class="fa fa-language"></i> <strong>Traducir</strong>
              </button>
              <div id="loading" class="mt-3 text-center" style="display:none;">
                <i class="fa fa-spinner fa-spin"></i> Procesando consulta...
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Resultados -->
      <div class="card card-danger" id="DATOSFHIR" style="display:none;">
        <div class="card-header">
          <h3 class="card-title"><strong>Resultado de la traducción</strong></h3>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tablaResultados" class="table table-bordered table-striped text-center">
              <thead class="bg-danger text-white">
                <tr>
                  <th>Sistema</th>
                  <th>Código</th>
                  <th>Display</th>
                  <th>Equivalencia</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
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
  $('.select2bs4').select2({ theme: 'bootstrap4' });

  function mostrarLoader(mostrar) {
    $('#loading').toggle(mostrar);
  }

  $('#filtrar').on('click', function(e) {
    e.preventDefault();

    const code = $('#id').val().trim();
    const system = $('#local').val().trim();
    const targetSystem = $('#target').val().trim();

    if (!code || !system || !targetSystem) {
      toastr.warning('Debe completar todos los campos: código, sistema de origen y destino.');
      return;
    }

    mostrarLoader(true);
    $('#DATOSFHIR').hide();
    $('#tablaResultados tbody').empty();

    $.ajax({
      url: '/backend/services/translate.php',
      method: 'POST',
      data: JSON.stringify({ code, system, targetSystem }),
      contentType: 'application/json',
      success: function(response) {
        mostrarLoader(false);
        $('#DATOSFHIR').show();

        const $tbody = $('#tablaResultados tbody').empty();

        try {
          if (typeof response === 'string') response = JSON.parse(response);

          // 🔹 Si viene un array (múltiples matches)
          if (Array.isArray(response) && response.length > 0) {
            response.forEach(item => {
              const row = `
                <tr>
                  <td>${item.system || '-'}</td>
                  <td>${item.code || '-'}</td>
                  <td>${item.display || '-'}</td>
                  <td>${item.equivalence || '-'}</td>
                </tr>`;
              $tbody.append(row);
            });
            toastr.success(`Se encontraron ${response.length} traducción(es).`);
          }
          // 🔹 Si viene un solo resultado
          else if (response.system && response.code) {
            const row = `
              <tr>
                <td>${response.system}</td>
                <td>${response.code}</td>
                <td>${response.display}</td>
                <td>${response.equivalence || '-'}</td>
              </tr>`;
            $tbody.append(row);
            toastr.success('Traducción obtenida correctamente.');
          }
          // 🔹 Si hay error
          else if (response.error) {
            toastr.error(response.error);
          } else {
            toastr.info('No se encontraron traducciones para los parámetros indicados.');
          }

          console.log('Respuesta del servidor (translate):', response);
        } catch (err) {
          toastr.error('Error procesando la respuesta del servidor.');
          console.error(err);
        }
      },
      error: function(xhr, status, error) {
        mostrarLoader(false);
        toastr.error('Error al comunicarse con el servidor de terminología.');
        console.error('Error Translate:', error);
      }
    });
  });
});
</script>
