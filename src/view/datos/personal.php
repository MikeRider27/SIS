<?php include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

// Obtener la conexión a la base de datos
$dbconn = getConnectionFHIR();

// Consulta para obtener países
$sql = "SELECT name FROM public.country;";
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
                    <h1><strong>Crear Personal</strong></h1>
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
                            <h3 class="card-title">Formulario de Personal</h3>
                        </div>
                        <div class="card-body">
                            <form id="form" class="user">
                                <div class="row">
                                    <!-- Identificacion -->
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="identificacion">Identificacion:</label>
                                            <input type="text" id="identificacion" name="identificacion" class="form-control" required />
                                        </div>
                                    </div>
                                    <!-- Nombre -->
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="nombre">Nombre:</label>
                                            <input type="text" id="nombre" name="nombre" class="form-control" required />
                                        </div>
                                    </div>
                                    <!-- Apellido -->
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="apellido">Apellido:</label>
                                            <input type="text" id="apellido" name="apellido" class="form-control" required />
                                        </div>
                                    </div>
                                    <!-- Sexo -->
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="sexo">Sexo:</label>
                                            <select id="sexo" name="sexo" class="form-control" required>
                                                <option value="">Seleccione</option>
                                                <option value="1">Femenino</option>
                                                <option value="2">Masculino</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- fecha de nacimiento -->
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="fechanacimiento">fecha de nacimiento:</label>
                                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control" required />
                                        </div>
                                    </div>
                                    <!-- Nacionalidad -->
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="country">Nacionalidad:</label>
                                            <select id="country" name="country" class="form-control select2bs4" required>
                                                <option value="">Seleccione</option>
                                                <?php foreach ($countries as $country) {
                                                    echo '<option value="' . $country['name'] . '">' . $country['name'] . '</option>';
                                                } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Tipo de Profesional -->
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="tipo_profesional">Tipo de Profesional:</label>
                                            <select id="tipo_profesional" name="tipo_profesional" class="form-control" required>
                                                <option value="">Seleccione</option>
                                                <option value="medico">Médico</option>
                                                <option value="enfermero">Enfermero</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- registro -->
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="registro">Registro:</label>
                                            <input type="text" id="registro" name="registro" class="form-control" required />
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
                url: '/backend/services/datos/profesional.php',
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