<?php include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

// Obtener la conexión a la base de datos
$dbconn = getConnectionFHIR();

// Buscamos si tenemos el header
$sql = "SELECT name, alpha_2, alpha_3 FROM public.country;";
$stmt = $dbconn->prepare($sql);
$stmt->execute();
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row justify-content-center ">
                <div class="col-sm-6 text-center">
                    <button type="button" class="btn btn-outline-danger mr-2 col-sm-3 mb-3 active"><strong>Generar ICVP</strong></button>
                    <a href="/paciente/dvc_ver" class="btn btn-outline-danger col-sm-3 mb-3">
                        <strong>Ver ICVP</strong>
                    </a>
                    <h1><strong>Generar ICVP</strong></h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Filtros -->
                    <div class="card card-danger">
                        <div class="card-header">
                            <div class="float-left">
                                <h3 class="card-title"><strong>Paciente</strong></h3>
                            </div>
                            <div class="float-right">
                                <div id="countdown2" style="font-size: 2em;"></div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="row justify-content-center">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <div class="row">
                                                        <div class="col-sm-4">
                                                            <div class="form-group">
                                                                <label for="id" class="font-size-14">ID del Bundle</label><br>
                                                                <input type="text" id="id" name="id" class="form-control font-size-14" autocomplete="off" />
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-4">
                                                            <div class="form-group">
                                                                <label for="identifier" class="font-size-14">Servidor FHIR</label><br>
                                                                <select class="form-control select2bs4" id="fhirUrl" name="fhirUrl">
                                                                    <option value="">Seleccione</option>
                                                                    <option selected value="1">https://fhir.mspbs.gov.py</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-4">
                                                            <div class="form-group">
                                                                <label for="identifier" class="font-size-14">Mediador FHIR</label><br>
                                                                <select class="form-control select2bs4" id="mediatorUrl" name="mediatorUrl">
                                                                    <option value="">Seleccione</option>
                                                                    <option selected value="2">https://fhir.mspbs.gov.py/mediator</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row justify-content-center mt-3">
                                                        <div class="container-fluid">
                                                            <div class="row justify-content-center">
                                                                <div class="col-sm-6 text-center">
                                                                    <button id="filtrar" class="btn btn-outline-danger">
                                                                        <strong>Buscar</strong>&nbsp;<i class="fa fa-search"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resultados -->
                    <div class="card card-danger">
                        <div class="card-header mb-3">
                            <h3 class="card-title"><strong>Resultados</strong></h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="row justify-content-center">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <div class="row">
                                                        <div class="col-sm-12">
                                                            <div class="form-group">
                                                                <table id="listado" class="table table-bordered table-striped">
                                                                    <tbody></tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card -->
                </div>
            </div>
        </div>
    </section>
</div>

<?php
include('/var/www/html/view/includes/footer.php');
?>

<!-- Page specific script -->
<script>
    $(function() {
        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });

        function handleAjaxError(xhr, textStatus, error) {
            if (textStatus === "timeout") {
                toastr.warning('Ocurrió un error al intentar comunicarse con el servidor. Por favor contacte con el administrador de la red.');
            } else {
                toastr.warning('Ocurrió un error al intentar comunicarse con el servidor. Por favor contacte con el administrador del sistema.');
            }
            document.getElementById("listado_processing").style.display = "none";
        }

        var table = $('#listado').DataTable({
            "responsive": true,
            "lengthChange": false,
            "autoWidth": false,
            "processing": true,
            "serverSide": true,
            "deferLoading": 0,
            "paging": false, // Deshabilita la paginación
            "info": false, // Deshabilita la información de los registros
            "searching": false, // Opcional: deshabilita la barra de búsqueda si no la necesitas
            "ajax": {
                url: "/backend/services/dvc_generate.php",
                method: "GET",
                data: function(d) {
                    d.id = $('#id').val();
                    d.serve = $('#fhirUrl').val();
                    d.intermediary = $('#mediatorUrl').val();
                },
                dataSrc: function(json) {
                    if (json.error) {
                        toastr.warning(json.error || "Error desconocido.");
                        return []; // Detener el procesamiento
                    }

                    if (!Array.isArray(json)) {
                        toastr.warning('Formato de datos inesperado.');
                        return [];
                    }

                    return json.map(function(entry) {
                        // Generar la imagen QR
                        var qrImage = entry.base64Image ?
                            `<img src="data:image/png;base64,${entry.base64Image}" style="display: block; margin: 0 auto; width: 200px; height: 200px;" alt="QR Code">` :
                            'Sin QR';

                        // Agregar el botón de descarga si existe la imagen QR
                        var downloadButton = entry.base64Image ?
                            `<div style="text-align: center; margin-top: 10px;">
                                <a href="data:image/png;base64,${entry.base64Image}" download="QR_Code_${entry.patientName || 'unknown'}.png" class="btn btn-outline-danger">Descargar</a>
                             </div>` :
                            '';

                        return [
                            entry.patientName || '', // Columna: Nombre del Paciente
                            entry.vaccineCode || '', // Columna: Código de Vacuna
                            entry.vaccineName || '', // Columna: Nombre de la Vacuna
                            qrImage + downloadButton // Columna: Imagen QR + Botón de descarga
                        ];
                    });
                },
                error: function(xhr, textStatus, error) {
                    handleAjaxError(xhr, textStatus, error);
                }
            },
            "columns": [{
                    "title": "Nombre del Paciente"
                },
                {
                    "title": "Código de Vacuna"
                },
                {
                    "title": "Nombre de la Vacuna"
                },
                {
                    "title": "QR Code"
                }
            ],
            "language": {
                "emptyTable": "No hay registros en la tabla",
                "info": "Se muestran _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Se muestran 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "zeroRecords": "No se encontraron registros que coincidan",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            }
        });

        $('#filtrar').on('click', function() {
            table.ajax.reload();
        });
    });
</script>