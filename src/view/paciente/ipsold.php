<?php
include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');
$dbconn = getConnectionFHIR();
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid text-center">
      <h1><strong>Consultar IPS (ITI-67)</strong></h1>
      <p class="text-muted">
        Búsqueda de documentos tipo <code>DocumentReference</code> asociados a un paciente
      </p>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <!-- 🔍 Filtros -->
      <div class="card card-outline card-danger">
        <div class="card-header">
          <h3 class="card-title"><strong>Parámetros de búsqueda</strong></h3>
        </div>
        <div class="card-body">
          <div class="form-group row">
            <label for="id" class="col-md-3 col-form-label text-md-right">Identificador del paciente</label>
            <div class="col-md-6">
              <div class="input-group">
                <input type="text" id="id" name="id" placeholder="Ej: PY654321"
                       class="form-control" autocomplete="off" />
                <div class="input-group-append">
                  <button id="filtrar" class="btn btn-danger">
                    <i class="fa fa-search"></i> Buscar
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div id="loading" class="text-center mt-3" style="display:none;">
            <i class="fa fa-spinner fa-spin"></i> Procesando consulta, por favor espere...
          </div>
        </div>
      </div>

      <!-- 📋 Resultados -->
      <div class="card card-outline card-danger">
        <div class="card-header">
          <h3 class="card-title"><strong>Resultados</strong></h3>
        </div>
        <div class="card-body">
          <table id="listado" class="table table-bordered table-striped">
            <thead>
              <tr class="bg-danger text-white text-center">
                <th>Servidor</th>
                <th>Nombre</th>
                <th>Fecha</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

    </div>
  </section>
</div>

<!-- 🪟 MODAL para mostrar el JSON del Bundle -->
<div class="modal fade" id="bundleModal" tabindex="-1" role="dialog" aria-labelledby="bundleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="bundleModalLabel"><i class="fa fa-file-medical"></i> Bundle FHIR (IPS)</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body bg-dark text-light" style="font-family: monospace; white-space: pre-wrap; overflow-x: auto; max-height: 80vh;">
        <pre id="bundleContent" class="text-white"></pre>
      </div>
    </div>
  </div>
</div>

<?php
include('/var/www/html/view/includes/footer.php');
?>

<!-- 📜 Script -->
<script>
$(function () {
  const table = $('#listado').DataTable({
    responsive: true,
    lengthChange: false,
    autoWidth: false,
    searching: false,
    ordering: false,
    language: {
      emptyTable: "No hay registros para mostrar",
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 a 0 de 0 registros",
      paginate: {
        first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior"
      }
    },
    columns: [
      { data: "server", title: "Servidor" },
      { data: "patient_name", title: "Nombre" },
      {
        data: "lastUpdated",
        title: "Fecha",
        render: function (data) {
          if (!data || data === 'No disponible')
            return '<span class="text-muted">No disponible</span>';

          try {
            const d = new Date(data);
            const year = d.getUTCFullYear();
            const month = String(d.getUTCMonth() + 1).padStart(2, '0');
            const day = String(d.getUTCDate()).padStart(2, '0');
            const hours = String(d.getUTCHours()).padStart(2, '0');
            const minutes = String(d.getUTCMinutes()).padStart(2, '0');
            const seconds = String(d.getUTCSeconds()).padStart(2, '0');
            return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
          } catch (e) {
            return data;
          }
        }
      },
      {
        data: "bundle",
        title: "Acción",
        render: function (data, type, row) {
          if (!data || data === 'N/A') {
            return '<span class="text-muted">Sin Bundle</span>';
          }
          return `
            <button class="btn btn-sm btn-primary" onclick="verBundle('${data}', '${row.server}')">
              <i class="fa fa-eye"></i> Ver IPS/${data}
            </button>`;
        }
      }
    ]
  });

  // 🔎 Buscar
  $('#filtrar').on('click', function () {
    const patientId = $('#id').val().trim();
    if (!patientId) {
      toastr.warning('Por favor, ingrese un identificador del paciente.');
      return;
    }

    $('#loading').show();
    table.clear().draw();

    $.ajax({
      url: `/backend/services/track1/ips_viewer.php`,
      method: 'GET',
      data: { identifier: patientId },
      dataType: 'json',
      success: function (resp) {
        $('#loading').hide();
        if (!Array.isArray(resp) || resp.length === 0) {
          toastr.info('No se encontraron DocumentReference para el identificador especificado.');
          table.clear().draw();
          return;
        }
        table.clear().rows.add(resp).draw();
        toastr.success(`${resp.length} documento(s) encontrados.`);
      },
      error: function (xhr, status, error) {
        $('#loading').hide();
        console.error(xhr.responseText);
        toastr.error(`Error al consultar el backend: ${error}`);
      }
    });
  });
});

// 👁️ Mostrar el Bundle en un modal (usando proxy PHP)
function verBundle(bundleId, serverBase) {
  if (!bundleId || bundleId === 'N/A') {
    toastr.warning('No hay Bundle asociado para este documento.');
    return;
  }

  const base = serverBase.endsWith('/') ? serverBase : `${serverBase}/`;
  const bundleUrl = `${base}Bundle/${bundleId}?_format=json`;

  $('#bundleContent').text('Cargando datos del Bundle...');
  $('#bundleModal').modal('show');

  $.ajax({
    url: `/backend/services/track1/get_bundle.php`,
    method: 'GET',
    data: { url: bundleUrl },
    dataType: 'json',
    success: function (data) {
      const prettyJSON = JSON.stringify(data, null, 2);
      $('#bundleContent').html(syntaxHighlight(prettyJSON));
    },
    error: function (xhr, status, error) {
      let msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : error;
      $('#bundleContent').html(`<span class="text-danger">Error al obtener el Bundle: ${msg}</span>`);
    }
  });
}

// 🎨 Colorear el JSON
function syntaxHighlight(json) {
  json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\\s*:)?|\b(true|false|null)\b|\b[0-9.eE+-]+\b)/g, function (match) {
    let cls = 'text-warning';
    if (/^"/.test(match)) {
      if (/:$/.test(match)) cls = 'text-danger';
      else cls = 'text-success';
    } else if (/true|false/.test(match)) cls = 'text-info';
    else if (/null/.test(match)) cls = 'text-muted';
    else cls = 'text-primary';
    return `<span class="${cls}">${match}</span>`;
  });
}
</script>
