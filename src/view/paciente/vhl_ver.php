<?php include('/var/www/html/view/includes/header.php'); ?>
<?php include('/var/www/html/core/connection.php'); ?>

<?php
$dbconn = getConnectionFHIR();
$sql = "SELECT name, alpha_2, alpha_3 FROM public.country;";
$stmt = $dbconn->prepare($sql);
$stmt->execute();
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row justify-content-center ">
        <div class="col-sm-6 text-center">
          <a href="/paciente/vhl_generar" class="btn btn-outline-danger col-sm-3 mb-3">
            <strong>Generar VHL</strong>
          </a>
          <a href="/paciente/vhl_ver" class="btn btn-outline-danger mr-2 col-sm-3 mb-3 active">
            <strong>Ver VHL</strong>
          </a>
          <h1><strong>Ver VHL</strong></h1>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card card-danger">
            <div class="card-header">
              <div class="float-left">
                <h3 class="card-title"><strong>Código</strong></h3>
              </div>
              <div class="float-right">
                <div id="countdown2" style="font-size: 2em;"></div>
              </div>
            </div>

            <div class="card-body">
              <div class="row justify-content-center">
                <div class="col-md-12 text-center">
                  <div class="image-container my-3">
                    <img id="qr-logo" src="../../public/dist/img/qrLogoMSPBS.png"
                      alt="QR" style="max-width: 15%; opacity: 0.5;">
                  </div>
                  <input type="file" id="file-logo" style="display:none;" />

                  <div class="mt-5">
                    <div class="input-group">
                      <input type="text" id="codigo" name="codigo" placeholder="Código de acceso"
                        class="form-control font-size-14" autocomplete="off" />
                      <div id="boton-excel" class="input-group-append">
                        <button id="verificar" class="btn btn-outline-danger">
                          <strong>Verificar</strong>&nbsp;<i class="fa fa-check"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-12" id="valueTable" style="display:none;">
                  <div id="resultado"></div>
                </div>
              </div>
            </div>

            <div class="col-md-12">
              <div id="validationMessage" style="display:none;" class="alert alert-success text-center">
                <strong>Validación exitosa:</strong> Todos los pasos se completaron correctamente.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include('/var/www/html/view/includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/jsqr/dist/jsQR.js"></script>
<script>
$(function() {
  $('#qr-logo').on('click', () => $('#file-logo').click());

  $('#file-logo').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = ev => {
        $('#qr-logo').attr('src', ev.target.result).css({'opacity': ''});
      };
      reader.readAsDataURL(file);
    }
  });

  $('#verificar').on('click', function() {
    const file = $('#file-logo')[0].files[0];
    const codigo = $('#codigo').val();

    if (!file) {
      alert("Por favor selecciona una imagen QR primero.");
      return;
    }

    const reader = new FileReader();
    reader.onload = function(event) {
      const img = new Image();
      img.src = event.target.result;
      img.onload = function() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);

        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const qrCode = jsQR(imageData.data, canvas.width, canvas.height);

        if (!qrCode) {
          alert("No se pudo leer el QR.");
          return;
        }

        $.ajax({
          url: '/backend/services/ver_vhl.php',
          method: 'POST',
          contentType: 'application/json',
          data: JSON.stringify({ qr: qrCode.data, codigo: codigo }),
          success: function(response) {
            let data;
            try { data = JSON.parse(response); } catch { data = response; }

            console.log("Respuesta completa:", data);

            if (!data || data.valid === false) {
              $('#resultado').html('<div class="alert alert-danger">No se pudo validar el QR. ' + (data.message || '') + '</div>');
              $('#valueTable').show();
              return;
            }

            // ---- Tabla de pasos ----
            const steps = data.steps || {};
            let tableHtml = '<h4 class="text-center mb-3"><strong>Proceso de Validación</strong></h4>';
            tableHtml += '<table class="table table-bordered"><thead><tr><th>Paso</th><th>Descripción</th><th>Estado</th></tr></thead><tbody>';

            Object.keys(steps).forEach(key => {
              const step = steps[key];
              const icon = step.status === 'SUCCESS'
                ? '<span style="color:green;">✔️</span>'
                : step.status === 'FAILED'
                ? '<span style="color:red;">❌</span>'
                : '<span style="color:orange;">⏳</span>';
              tableHtml += `<tr>
                              <td>${step.step}</td>
                              <td>${step.description}</td>
                              <td class="text-center">${icon}</td>
                            </tr>`;
            });
            tableHtml += '</tbody></table>';

            // ---- Datos de SHL (si existen) ----
            let tableHtml2 = '';
            if (data.rawValidation && data.rawValidation.shLinkContent) {
              const sh = data.rawValidation.shLinkContent;
              tableHtml2 += '<h4 class="mt-4"><strong>Smart Health Link (SHL)</strong></h4>';
              tableHtml2 += '<table class="table table-bordered"><tbody>';
              if (sh.exp) {
                const date = new Date(sh.exp);
                const formatted = date.toLocaleString('es-PY');
                tableHtml2 += `<tr><td><strong>Exp:</strong> ${formatted}</td></tr>`;
              }
              if (sh.key) tableHtml2 += `<tr><td><strong>Key:</strong> ${sh.key}</td></tr>`;
              if (sh.label) tableHtml2 += `<tr><td><strong>Label:</strong> ${sh.label}</td></tr>`;
              if (sh.url) tableHtml2 += `<tr><td><strong>URL:</strong> <a href="${sh.url}" target="_blank">${sh.url}</a></td></tr>`;
              tableHtml2 += '</tbody></table>';
            } else {
              tableHtml2 += '<div class="alert alert-warning text-center mt-4">No se recibió información de SHL en la respuesta (probablemente falló en el paso 6).</div>';
            }

            // ---- Manifest / Bundle ----
            let tableHtml3 = '';
            if (data.fileLocation) {
              tableHtml3 += '<h4 class="mt-4"><strong>Enlace al Bundle</strong></h4>';
              tableHtml3 += `<div class="text-center"><a target="_blank" href="${data.fileLocation}" class="btn btn-light">Abrir application/fhir+json</a></div>`;
            }

            // ---- Paciente ----
            const patient = data.patientName ? `<h3 class="text-center mb-4"><strong>${data.patientName}</strong></h3>` : '';

            $('#resultado').html(patient + tableHtml + tableHtml2 + tableHtml3);
            $('#valueTable').show();
          },
          error: function(xhr) {
            console.error(xhr.responseText);
            $('#resultado').html('<div class="alert alert-danger">Error al procesar la validación.</div>');
            $('#valueTable').show();
          }
        });
      };
    };
    reader.readAsDataURL(file);
  });
});
</script>
