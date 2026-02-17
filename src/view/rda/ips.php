<?php 
include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

$dbconn = getConnectionFHIR();
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
        "ajax": {
            url: "/backend/services/itti-672.php",
            method: "GET",
            data: function(d) {
                return { identifier: $('#id').val().trim() };
            },
            dataSrc: function(json) {
                const identifier = $('#id').val().trim();
                if (!identifier) return [];
                
                if (json.status === "error") {
                    toastr.warning(json.message);
                    return [];
                }

                const orgMap = {};
                const rows = [];

                // Procesar organizaciones primero
                if (Array.isArray(json.entry)) {
                    json.entry.forEach(entry => {
                        const res = entry.resource || {};
                        if (res.resourceType === "Organization") {
                            orgMap[res.id] = res.name || "Organización desconocida";
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

                return rows;
            },
            error: function(xhr, textStatus, error) {
                const errorMessages = {
                    'timeout': 'La solicitud ha superado el tiempo de espera',
                    'abort': 'La solicitud ha sido abortada',
                    'parsererror': 'Error al procesar la respuesta del servidor'
                };
                
                const message = errorMessages[textStatus] || `Error: ${error || 'Desconocido'}`;
                toastr.error(message);
            }
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