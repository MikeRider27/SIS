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
      <p class="text-muted">Búsqueda de documentos tipo <code>DocumentReference</code> asociados a un paciente</p>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <!-- Filtros -->
      <div class="card card-outline card-danger">
        <div class="card-header">
          <h3 class="card-title"><strong>Parámetros de búsqueda</strong></h3>
        </div>
        <div class="card-body">
          <div class="form-group row">
            <label for="id" class="col-md-3 col-form-label text-md-right">Identificador del paciente</label>
            <div class="col-md-6">
              <div class="input-group">
                <input type="text" id="id" name="id" placeholder="Ej: R123456"
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

      <!-- Resultados -->
      <div class="card card-outline card-danger">
        <div class="card-header">
          <h3 class="card-title"><strong>Resultados</strong></h3>
        </div>
        <div class="card-body">
          <table id="listado" class="table table-bordered table-striped">
            <thead>
              <tr class="bg-danger text-white">
                <th>Documento del Paciente</th>
                <th>Nombre del Paciente</th>
                <th>Fecha de Registro</th>
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

<?php
include('/var/www/html/view/includes/footer.php');
?>

<!-- Script -->
<script>
$(function () {
  const table = $('#listado').DataTable({
    responsive: true,
    lengthChange: false,
    autoWidth: false,
    searching: false,
    language: {
      emptyTable: "No hay registros para mostrar",
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 a 0 de 0 registros",
      paginate: {
        first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior"
      }
    },
    columns: [
      { data: "documento" },
      { data: "nombre" },
      { 
        data: "fecha_registro",
        render: function (data) {
          return data && data !== 'No disponible' 
            ? data 
            : '<span class="text-muted">No disponible</span>';
        }
      },
      {
        data: "bundle",
        render: function (data) {
          if (!data || data === 'N/A') {
            return '<span class="text-muted">Sin Bundle</span>';
          }
          return `
            <button class="btn btn-sm btn-primary" onclick="verBundle('${data}')">
              <i class="fa fa-eye"></i> IPS/${data}
            </button>`;
        }
      }
    ]
  });

  // Acción al buscar
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
      data: { 'identifier': patientId },
      dataType: 'json',
      success: function (resp) {
        $('#loading').hide();

        if (resp.status !== 'success' || !resp.data || !resp.data.length) {
          toastr.info('No se encontraron DocumentReference para el identificador especificado.');
          table.clear().draw();
          return;
        }

        table.clear().rows.add(resp.data).draw();
        toastr.success(`${resp.data.length} documentos encontrados.`);
      },
      error: function (xhr, status, error) {
        $('#loading').hide();
        console.error(xhr.responseText);
        toastr.error(`Error al consultar el backend: ${error}`);
      }
    });
  });
});

// Abre el Bundle (IPS) en nueva pestaña
function verBundle(bundleId) {
  if (!bundleId || bundleId === 'N/A') {
    toastr.warning('No hay Bundle asociado para este documento.');
    return;
  }
  const url = `/ips/bundle/${bundleId}`;
  window.location.href = url; // redirige en la misma pestaña
}
</script>
