<?php 
include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

$dbconn = getConnection();
?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12 text-center">
                    <h1><strong>Consultar RDA (ITI-67)</strong></h1>
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
                            <h3 class="card-title m-0"><strong>Paciente</strong></h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="id" class="font-size-14">Identificador del paciente</label>
                                        <div class="input-group">
                                            <input type="text" id="id" name="id" 
                                                   placeholder="Ingrese identificador del paciente" 
                                                   class="form-control font-size-14" autocomplete="off" 
                                                   onkeypress="handleEnterKey(event)" />
                                            <div class="input-group-append">
                                                <button id="filtrar" class="btn btn-outline-danger">
                                                    <strong>Buscar</strong> &nbsp;<i class="fa fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="loading" class="col-sm-12 text-center d-none">
                                        <i class="fa fa-spinner fa-spin"></i> Procesando consulta
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title m-0"><strong>Resultados</strong></h3>
                        </div>
                        <div class="card-body p-0">
                            <div id="pacienteInfo" class="alert alert-info m-3 d-none"></div>
                            <table id="listado" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Organización</th>
                                        <th>Última actualización</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
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
    const table = $('#listado').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
        "searching": false,
        "paging": false,
        "info": false,
        "ordering": false,
        "ajax": function(data, callback, settings) {
            const identifier = $('#id').val().trim();

            // No disparar ninguna consulta (ni al backend ni al servidor FHIR)
            // hasta que el usuario haya ingresado un identificador y buscado.
            if (!identifier) {
                $('#pacienteInfo').addClass('d-none');
                callback({ data: [] });
                return;
            }

            $.ajax({
                url: "/backend/services/itti-672.php",
                method: "GET",
                data: { identifier: identifier },
                dataType: "json",
                success: function(json) {
                    if (json.status === "error") {
                        toastr.warning(json.message);
                        $('#pacienteInfo').addClass('d-none');
                        callback({ data: [] });
                        return;
                    }

                    const orgMap = {};
                    const patientMap = {};
                    const rows = [];

                    function nombrePaciente(res) {
                        const name = res?.name?.[0];
                        if (!name) return "Paciente desconocido";
                        const given = Array.isArray(name.given) ? name.given.join(' ') : '';
                        const family = name.family || '';
                        return (given + ' ' + family).trim() || "Paciente desconocido";
                    }

                    // Procesar organizaciones y pacientes primero
                    if (Array.isArray(json.entry)) {
                        json.entry.forEach(entry => {
                            const res = entry.resource || {};
                            if (res.resourceType === "Organization") {
                                orgMap[res.id] = res.name || "Organización desconocida";
                            } else if (res.resourceType === "Patient") {
                                patientMap[res.id] = nombrePaciente(res);
                            }
                        });

                        // Procesar DocumentReference
                        json.entry.forEach(entry => {
                            const res = entry.resource || {};
                            if (res.resourceType === "DocumentReference") {
                                const orgRef = res.custodian?.reference || "";
                                const orgId = orgRef.replace("Organization/", "");
                                const orgName = orgMap[orgId] || "MINISTERIO DE SALUD";

                                rows.push({
                                    organization: orgName,
                                    lastUpdated: res.meta?.lastUpdated || "N/A",
                                    documentUrl: res.content?.[0]?.attachment?.url || ""
                                });
                            }
                        });
                    }

                    // Mostrar el nombre del paciente una sola vez arriba de la tabla
                    // (todos los resultados corresponden al mismo paciente buscado)
                    const nombres = Object.values(patientMap);
                    if (nombres.length && rows.length) {
                        $('#pacienteInfo').text('Paciente: ' + nombres[0]).removeClass('d-none');
                    } else {
                        $('#pacienteInfo').addClass('d-none');
                    }

                    callback({ data: rows });
                },
                error: function(xhr, textStatus, error) {
                    const errorMessages = {
                        'timeout': 'La solicitud ha superado el tiempo de espera',
                        'abort': 'La solicitud ha sido abortada',
                        'parsererror': 'Error al procesar la respuesta del servidor'
                    };

                    let message = errorMessages[textStatus] || `Error: ${error || 'Desconocido'}`;
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    toastr.error(message);
                    callback({ data: [] });
                }
            });
        },
        // En la configuración de las columnas del DataTable:
        "columns": [
            {
                "data": "organization",
                "className": "align-middle"
            },
            { 
                "data": "lastUpdated",
                "className": "align-middle"
            },
            { 
                "data": null,
                "className": "align-middle text-center",
                "render": function(data, type, row, meta) {
                    if (!row.documentUrl) return '-';
                    
                    // Extraer el ID del documento (bundle) de la URL
                    const documentId = row.documentUrl.split('/').pop();
                    
                    // Formato Bundle/ID
                    const buttonText = documentId ? `Bundle/${documentId}` : 'Sin ID';
                    
                    return `<button class="btn btn-primary btn-sm" onclick="viewDocument('${row.documentUrl}')">
                                ${buttonText}
                            </button>`;
                }
            }
        ],
        "language": {
            "emptyTable": "No hay registros para mostrar",
            "loadingRecords": "Cargando...",
            "processing": "Procesando..."
        }
    });

    $('#filtrar').on('click', function() {
        performSearch();
    });

    function performSearch() {
        const identifier = $('#id').val().trim();
        if (identifier) {
            $('#loading').removeClass('d-none');
            table.ajax.reload(function() {
                $('#loading').addClass('d-none');
            });
        } else {
            toastr.warning('Por favor, ingrese un identificador para la búsqueda.');
        }
    }

    window.handleEnterKey = function(event) {
        if (event.key === 'Enter') {
            performSearch();
        }
    }
});

function viewDocument(url) {
    if (!url) {
        toastr.warning('No hay documento disponible');
        return;
    }
    
    const documentId = url.split('/').pop();
    if (documentId) {
        window.location.href = "/ips/bundle/" + encodeURIComponent(documentId);
    } else {
        toastr.error('ID de documento no válido');
    }
}
</script>