<?php include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

// Obtener la conexión a la base de datos
$dbconn = getConnectionFHIR();

// Consulta para obtener países
$sql = "SELECT alpha_3, name FROM public.country;";
$stmt = $dbconn->prepare($sql);
$stmt->execute();
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12 text-center">
                    <h1><strong>Crear Servicios</strong></h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title">Formulario de Servicios</h3>
                        </div>
                        <div class="card-body">
                            <form id="form" class="user">
                                <div class="row">
                                    <!-- ID -->
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="id_servicio">ID:</label>
                                            <input type="text" id="id_servicio" name="id_servicio" class="form-control"  />
                                        </div>
                                    </div>
                                    <!-- Nombre servicio -->
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="nombre_servicio">Nombre servicio:</label>
                                            <input type="text" id="nombre_servicio" name="nombre_servicio" class="form-control"  />
                                        </div>
                                    </div>                                    
                                   
                                       <!-- Ciudad -->
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="tipo">TIPO:</label>
                                            <input type="text" id="tipo" name="tipo" class="form-control"  />
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones -->
                                <div class="card-footer">

                                    <div class="float-left">
                                        <a href="/evento/<?= $id; ?>" class="btn btn-outline-danger">
                                            <i class="fas fa-times"></i> <strong>CERRAR</strong>
                                        </a>
                                    </div>

                                    <div class="float-right">
                                        <input type="hidden" name="accion" value="agregar">
                                        <button id="guardar" type="submit" class="btn btn-outline-danger">
                                            <i class="fas fa-check"></i> <strong>Guardar</strong>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>



<?php include('/var/www/html/view/includes/footer.php'); ?>
<script>
    $(function() {
        // Initialize Select2 Elements
        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });


        function handleAjaxRequest(requestConfig, timeout = 10000) {
            const deferred = $.Deferred();
            let t;

            const ajaxRequest = $.ajax(requestConfig)
                .done((data) => {
                    clearTimeout(t);
                    deferred.resolve(data);
                })
                .fail((xhr, ajaxOptions, thrownError) => {
                    if (xhr.readyState === 0) {
                        toastr.warning('Advertencia, Ocurrió un error al intentar comunicarse con el servidor. Por favor, contacte con el administrador de la red');
                    } else {
                        toastr.warning('Advertencia, Ocurrió un error al procesar la solicitud. Por favor, contacte con el administrador del sistema');
                    }
                    console.log(thrownError);
                    deferred.reject(xhr, ajaxOptions, thrownError);
                });

            t = setTimeout(() => {
                if (ajaxRequest.readyState !== 4) {
                    ajaxRequest.abort();
                    deferred.reject();
                }
            }, timeout);

            return deferred.promise();
        }

        function enviarFormulario() {
            // Habilitar el campo 'sexo' antes de enviar el formulario
            $('#sexo').attr('disabled', false);

            $('#guardar').attr("disabled", "disabled");

            const requestConfig = {
                url: '/backend/services/datos/servicio.php',
                method: 'POST',
                data: $('#form').serialize()
            };

            handleAjaxRequest(requestConfig)
                .done((data) => {
                    try {
                        const response = JSON.parse(data);
                        if (response.status === "success") {
                            toastr.success(response.message);
                            setTimeout(() => {
                                location.reload();
                            }, 3000);
                        } else {
                            toastr.error(response.message);
                        }
                    } catch (error) {
                        toastr.error('Ocurrió un error intentando resolver la solicitud. Por favor contacte con el administrador del sistema');
                        console.log(error);
                    }
                })
                .fail(() => {
                    toastr.error('Ocurrió un error intentando resolver la solicitud. Por favor contacte con el administrador de la red');
                })
                .always(() => {
                    $('#guardar').removeAttr('disabled');
                });
        }


        $('#form').submit(function(e) {
            e.preventDefault();
           

            enviarFormulario();
        });


    });
</script>