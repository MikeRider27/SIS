<?php include('/var/www/html/view/includes/header.php'); ?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1><i class="nav-icon fas fa-server text-secondary"></i> Configuración FHIR Server Endpoint</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="/inicio">Inicio</a></li>
            <li class="breadcrumb-item active">FHIR Server Endpoint</li>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">Registrar nuevo Endpoint</h3>
            </div>

            <form id="formEndpoint">
              <div class="card-body">
                <div class="row">
                  <div class="col-lg-6 col-xs-12">
                    <label for="nombre">Nombre del Endpoint</label>
                    <input type="text" class="form-control rounded-0" name="nombre" id="nombre" placeholder="Ej: Servidor Nacional FHIR">
                  </div>
                  <div class="col-lg-6 col-xs-12">
                    <label for="url">URL del Servidor FHIR</label>
                    <input type="url" class="form-control rounded-0" name="url" id="url" placeholder="https://fhir.mspbs.gov.py/fhir">
                  </div>
                </div>

                <div class="row mt-3">
                  <div class="col-lg-3">
                    <label for="version">Versión FHIR</label>
                    <select class="form-control select2bs4" id="version" name="version" style="width: 100%;">
                      <option value="">Seleccione versión</option>
                      <option value="R4">R4 FHIR</option>
                      <option value="R5">R5 MEDIATOR</option>
                      <option value="R6">R6 PDQm</option>
                      <option value="R7">R7 PIXm</option>
                      <option value="R8">R8 Terminology</option>
                      <option value="R9">R9 Snomed</option>
                      <option value="R10">R10 ICVP</option>
                      <option value="R11">R11 Broadcast Server</option>


                    </select>
                  </div>

                  <div class="col-lg-3">
                    <label for="autenticacion">Autenticación</label>
                    <select class="form-control select2bs4" id="autenticacion" name="autenticacion" style="width: 100%;">
                      <option value="">Seleccione método</option>
                      <option value="none">Sin autenticación</option>
                      <option value="basic">Basic Auth</option>
                      <option value="bearer">Bearer Token</option>
                      <option value="apikey">API Key</option>
                    </select>
                  </div>

                  <div class="col-lg-6">
                    <label for="token">TOKEN</label>
                    <textarea class="form-control rounded-0" name="token" id="token" rows="2" placeholder="Ingrese el token o clave si aplica"></textarea>
                  </div>
                </div>

                <div class="row mt-3">
                  <div class="col-lg-3">
                    <div class="form-check form-switch mt-4">
                      <input class="form-check-input" type="checkbox" name="activo" id="activo" checked>
                      <label class="form-check-label" for="activo">Activo</label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="card-footer">
                <a href="/fhir/list" class="btn btn-warning">Cerrar</a>
                <input type="hidden" name="accion" value="agregar">
                <button id="guardar" type="submit" class="btn btn-success">Guardar</button>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include('/var/www/html/view/includes/footer.php'); ?>

<script>
$(function() {
  $('.select2bs4').select2({ theme: 'bootstrap4' });

  $('#formEndpoint').submit(function(e) {
    e.preventDefault();

    if (!isValid()) return;

    $('#guardar').attr("disabled", true);

    $.ajax({
      url: '/backend/services/config/server_config.php',
      method: 'POST',
      data: $('#formEndpoint').serialize(),
      success: function(data) {
        try {
          const response = JSON.parse(data);
          if (response.status === "success") {
            toastr.success(response.message);
            setTimeout(() => location.href = "/fhir/list", 1500);
          } else {
            toastr.error(response.message);
          }
        } catch (err) {
          toastr.error('Error procesando respuesta del servidor.');
          console.error(err);
        } finally {
          $('#guardar').removeAttr("disabled");
        }
      },
      error: function() {
        toastr.error('No se pudo conectar con el servidor.');
        $('#guardar').removeAttr("disabled");
      }
    });
  });

  function isValid() {
    if ($('#nombre').val().trim() === "") { toastr.warning('Debe ingresar el nombre del endpoint.'); return false; }
    if ($('#url').val().trim() === "") { toastr.warning('Debe ingresar la URL del servidor FHIR.'); return false; }
    if ($('#version').val() === "") { toastr.warning('Debe seleccionar la versión FHIR.'); return false; }
    if ($('#autenticacion').val() === "") { toastr.warning('Debe seleccionar un método de autenticación.'); return false; }
    return true;
  }
});
</script>
