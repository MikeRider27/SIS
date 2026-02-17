<?php include('/var/www/html/view/includes/header.php'); ?>
<?php include('/var/www/html/core/connection.php'); ?>

<?php
// Obtener conexión y países (opcional)
$dbconn = getConnectionFHIR();
$sql = "SELECT name, alpha_2, alpha_3 FROM public.country;";
$stmt = $dbconn->prepare($sql);
$stmt->execute();
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Encabezado -->
    <section class="content-header">
        <div class="container-fluid text-center">
            <div class="row justify-content-center">
                <div class="col-sm-6">
                    <a href="/paciente/ips/icvp" class="btn btn-outline-danger col-sm-3 mb-3">
                        <strong>Generar ICVP</strong>
                    </a>
                    <a href="/paciente/dvc_ver" class="btn btn-outline-danger col-sm-3 mb-3 active">
                        <strong>Ver ICVP</strong>
                    </a>
                    <h1><strong>Verificar ICVP (Decodificar QR)</strong></h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Contenido principal -->
    <section class="content">
        <div class="container-fluid">
            <div class="card card-danger">
                <div class="card-header text-center">
                    <h3 class="card-title"><strong>Seleccionar QR</strong></h3>
                </div>
                <div class="card-body text-center">
                    <div class="image-container" style="margin: 10px 0;">
                        <img id="qr-logo" src="../../public/dist/img/qrLogoMSPBS.png" 
                             alt="Subir QR" 
                             style="max-width: 20%; height: auto; opacity: 0.5; cursor: pointer;">
                    </div>
                    <input type="file" id="file-logo" accept="image/*" style="display:none;">
                    <button id="verificar" class="btn btn-outline-danger mt-3">
                        <strong>Verificar</strong> &nbsp;<i class="fa fa-check"></i>
                    </button>

                    <div class="mt-4" id="valueTable" style="display:none;">
                        <div id="resultado"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include('/var/www/html/view/includes/footer.php'); ?>

<!-- Librería jsQR -->
<script src="https://cdn.jsdelivr.net/npm/jsqr/dist/jsQR.js"></script>
<script>
$(function() {
    $('#qr-logo').on('click', () => $('#file-logo').click());

    $('#file-logo').on('change', e => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = ev => {
                $('#qr-logo').attr('src', ev.target.result).css({
                    opacity: '1',
                    cursor: 'default'
                });
            };
            reader.readAsDataURL(file);
        }
    });

    $('#verificar').on('click', function() {
        const file = $('#file-logo')[0].files[0];
        if (!file) return alert("Selecciona una imagen QR primero.");

        const reader = new FileReader();
        reader.onload = function(ev) {
            const img = new Image();
            img.src = ev.target.result;
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
                const qr = jsQR(ctx.getImageData(0, 0, img.width, img.height).data, img.width, img.height);

                if (!qr) return alert("No se pudo leer el QR.");

                $.ajax({
                    url: '../../backend/services/ver_dvc.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ qr: qr.data }),
                    success: function(resp) {
                        let data;
                        try {
                            data = typeof resp === 'string' ? JSON.parse(resp) : resp;
                        } catch (e) {
                            console.error('Respuesta inválida del backend', resp);
                            alert('Error al procesar respuesta del servidor.');
                            return;
                        }

                        console.log('✅ Respuesta backend:', data);

                        if (!data.valid) {
                            $('#resultado').html('<div class="alert alert-danger">El QR no es válido.</div>');
                            $('#valueTable').show();
                            return;
                        }

                        // 🔹 Mostrar información del certificado
                        const infoHTML = `
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h4 class="mb-0"><strong>Datos del Certificado</strong></h4>
                                </div>
                                <div class="card-body text-left">
                                    <p><strong>Nombre:</strong> ${data.name}</p>
                                    <p><strong>Documento:</strong> ${data.documentType || ''} ${data.documentNumber || ''}</p>
                                    <p><strong>Fecha de Nacimiento:</strong> ${data.birthDate}</p>
                                    <p><strong>Género:</strong> ${data.gender}</p>
                                    <hr>
                                    <p><strong>Vacuna:</strong> ${data.vaccine.productCode}</p>
                                    <p><strong>Lote:</strong> ${data.vaccine.batchNumber}</p>
                                    <p><strong>Fecha de aplicación:</strong> ${data.vaccine.vaccinationDate}</p>
                                    <p><strong>Válido hasta:</strong> ${data.vaccine.validUntil}</p>
                                    <hr>
                                    <button class="btn btn-outline-primary btn-sm" id="btnDescargarJSON">
                                        <i class="fa fa-download"></i> Descargar JSON
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" id="btnImprimir">
                                        <i class="fa fa-print"></i> Imprimir
                                    </button>
                                </div>
                            </div>`;

                        // 🔹 Escapar caracteres del JSON para no romper el template HTML
                        const jsonFormatted = JSON.stringify(data.decoded, null, 2)
                            .replace(/</g, "&lt;")
                            .replace(/>/g, "&gt;");

                        const jsonHTML = `
                            <div class="card border-dark">
                                <div class="card-header bg-dark text-white">
                                    <h4 class="mb-0"><strong>JSON completo decodificado</strong></h4>
                                </div>
                                <div class="card-body">
                                    <pre id="jsonContent" style="
                                        text-align:left;
                                        font-size:13px;
                                        white-space:pre-wrap;
                                        overflow:auto;
                                        height:500px;
                                    ">${jsonFormatted}</pre>
                                </div>
                            </div>`;

                        const html = `
                            <div class="row mt-4">
                                <div class="col-md-6">${infoHTML}</div>
                                <div class="col-md-6">${jsonHTML}</div>
                            </div>`;

                        $('#resultado').html(html);
                        $('#valueTable').show();

                        // 🔹 Descargar JSON
                        $('#btnDescargarJSON').on('click', function() {
                            const blob = new Blob([JSON.stringify(data.decoded, null, 2)], { type: "application/json" });
                            const link = document.createElement("a");
                            link.href = URL.createObjectURL(blob);
                            link.download = "dvc_decodificado.json";
                            link.click();
                        });

                        // 🔹 Imprimir certificado
                        $('#btnImprimir').on('click', function() {
                            const win = window.open('', '_blank');
                            win.document.write(`
                                <html><head><title>Certificado DVC</title></head>
                                <body style="font-family: Arial; margin: 20px;">
                                <h2 style="color:#c00;">Datos del Certificado</h2>
                                <p><strong>Nombre:</strong> ${data.name}</p>
                                <p><strong>Documento:</strong> ${data.documentType || ''} ${data.documentNumber || ''}</p>
                                <p><strong>Fecha de Nacimiento:</strong> ${data.birthDate}</p>
                                <p><strong>Género:</strong> ${data.gender}</p>
                                <hr>
                                <p><strong>Vacuna:</strong> ${data.vaccine.productCode}</p>
                                <p><strong>Lote:</strong> ${data.vaccine.batchNumber}</p>
                                <p><strong>Fecha de aplicación:</strong> ${data.vaccine.vaccinationDate}</p>
                                <p><strong>Válido hasta:</strong> ${data.vaccine.validUntil}</p>
                                </body></html>`);
                            win.document.close();
                            win.print();
                        });
                    },
                    error: function(xhr) {
                        console.error('❌ Error AJAX', xhr);
                        alert('Error al procesar el QR.');
                    }
                });
            };
        };
        reader.readAsDataURL(file);
    });
});
</script>
