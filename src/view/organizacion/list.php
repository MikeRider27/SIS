<?php
include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

// Conexión FHIR
$dbconn = getConnectionFHIR();
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid text-center">
            <h1><strong>Viewer de Organizaciones FHIR</strong></h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">

                <!-- Panel izquierdo - Lista de Organizaciones -->
                <div class="col-lg-6 left-panel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-building me-2"></i><strong>Lista de Organizaciones</strong>
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Filtro de búsqueda -->
                           
                            
                            <!-- Contador de resultados -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="text-muted" id="contadorResultados">Cargando organizaciones...</small>
                                
                            </div>

                            <!-- DataTable de organizaciones -->
                            <div class="table-container">
                                <table id="tablaOrg" class="table table-striped table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Nombre</th>
                                            <th>Actualizado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel derecho - Detalle JSON -->
                <div class="col-lg-6 right-panel">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-dark text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-code me-2"></i><strong>Detalle JSON - Recurso FHIR</strong>
                            </h5>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="alert alert-info d-flex align-items-center mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>Seleccione una organización para visualizar su recurso FHIR completo</small>
                            </div>
                            
                            <div class="json-container flex-grow-1">
                                <textarea id="jsonOrg" class="d-none"></textarea>
                            </div>
                            
                            <div class="mt-3">
                                <button id="btnGuardarEdicion" class="btn btn-success" style="display:none;">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                                <button id="btnCancelarEdicion" class="btn btn-secondary ml-2" style="display:none;">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </button>
                                <button id="btnCopiarJSON" class="btn btn-outline-primary ml-2">
                                    <i class="fas fa-copy me-2"></i>Copiar JSON
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="spinner-container">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Cargando...</span>
        </div>
        <p class="mt-2 mb-0">Procesando...</p>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar acción</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="confirmModalBody">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmAction">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<?php
include('/var/www/html/view/includes/footer.php');
?>

<!-- jQuery y Bootstrap -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<!-- Toastr -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- CodeMirror CSS & JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/material.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>

<style>
:root {
    --primary-color: #0d6efd;
    --secondary-color: #6c757d;
    --success-color: #198754;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --border-radius: 8px;
}

.content-wrapper {
    background-color: #f8f9fa;
    min-height: calc(100vh - 56px);
}

.content-header {
    padding: 15px 0;
}

.left-panel, .right-panel {
    height: calc(100vh - 150px);
    overflow-y: auto;
}

.card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    height: 100%;
}

.card-header {
    border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding: 12px 15px;
}

.table-container {
    height: calc(100% - 110px);
    overflow-y: auto;
    border: 1px solid #e3e6f0;
    border-radius: var(--border-radius);
}

#tablaOrg {
    margin-bottom: 0;
}

#tablaOrg tbody tr {
    cursor: pointer;
    transition: all 0.3s;
}

#tablaOrg tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateX(2px);
}

#tablaOrg tbody tr.selected {
    background-color: #e3f2fd;
    border-left: 3px solid var(--primary-color);
}

.organization-codigo {
    font-weight: 600;
    color: var(--primary-color);
}

.organization-name {
    color: var(--dark);
}

.organization-actions {
    white-space: nowrap;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 0.8rem;
}

.CodeMirror {
    height: calc(100% - 120px);
    border: 1px solid #e3e6f0;
    border-radius: var(--border-radius);
    font-size: 13px;
}

.json-container {
    position: relative;
    height: 100%;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
}

.spinner-container {
    background: white;
    padding: 30px;
    border-radius: var(--border-radius);
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.input-group-text {
    background-color: #f8f9fa;
    border: 1px solid #ced4da;
}

.form-control {
    border-radius: var(--border-radius);
}

.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate {
    padding: 10px;
}

.dataTables_wrapper .dataTables_filter input {
    border-radius: var(--border-radius);
}

@media (max-width: 992px) {
    .col-lg-6 {
        margin-bottom: 20px;
    }
    
    .left-panel, .right-panel {
        height: 50vh;
    }
    
    .CodeMirror {
        height: calc(100% - 120px);
    }
    
    .table-container {
        height: calc(100% - 110px);
    }
}
</style>

<script>
$(document).ready(function(){
    // Configuración de Toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "5000"
    };

    // Inicializar CodeMirror
    var editor = CodeMirror.fromTextArea(document.getElementById("jsonOrg"), {
        lineNumbers: true,
        mode: "application/json",
        theme: "material",
        readOnly: true,
        lineWrapping: true
    });

    let orgActual = null;
    let jsonOriginal = null;
    let dataTable = null;

    // Función para mostrar/ocultar loading
    function toggleLoading(show) {
        if (show) {
            $('#loadingOverlay').show();
        } else {
            $('#loadingOverlay').hide();
        }
    }

    // Función para inicializar DataTable
    function inicializarDataTable() {
        if (dataTable) {
            dataTable.destroy();
        }

        dataTable = $('#tablaOrg').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            },
            "paging": true,
            "pageLength": 10,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[2, 'desc']], // Ordenar por fecha de actualización descendente
            "columns": [
                { 
                    "data": "codigo",
                    "className": "organization-codigo"
                },
                { 
                    "data": "nombre",
                    "className": "organization-name"
                },
                { 
                    "data": "fechaActualizacion",
                    "className": "text-muted",
                    "width": "120px"
                },
                { 
                    "data": "acciones",
                    "className": "organization-actions text-center",
                    "orderable": false,
                    "width": "100px"
                }
            ],
            "data": []
        });

        // Actualizar contador de resultados
        dataTable.on('draw', function () {
            const count = dataTable.rows({ filter: 'applied' }).count();
            $('#contadorResultados').text(`${count} organización(es) encontrada(s)`);
        });
    }

    // Función para cargar organizaciones
    function cargarOrganizaciones() {
        toggleLoading(true);
        $('#contadorResultados').text('Cargando organizaciones...');

        $.getJSON("/backend/services/organization/listOrganization.php")
            .done(function(data){
                if(data.status === "success"){
                    // Ordenar por fecha más nueva (si existe)
                    if (data.organizations && data.organizations[0] && data.organizations[0].lastUpdated) {
                        data.organizations.sort((a, b) => new Date(b.lastUpdated) - new Date(a.lastUpdated));
                    }

                    const tableData = data.organizations.map(o => {
                        let codigo = o.codigo ? o.codigo : "SIN CÓDIGO";
                        let nombre = o.nombre || "Nombre no disponible";
                        let fechaActualizacion = o.lastUpdated ? 
                            new Date(o.lastUpdated).toLocaleDateString() : "Fecha no disponible";
                        
                        return {
                            codigo: codigo,
                            nombre: nombre,
                            fechaActualizacion: fechaActualizacion,
                            acciones: `
                                <button class="btn btn-sm btn-outline-primary btnEditar mr-1" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btnEliminar" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            `,
                            raw: o.raw,
                            id: o.id
                        };
                    });

                    if (!dataTable) {
                        inicializarDataTable();
                    }

                    dataTable.clear();
                    dataTable.rows.add(tableData);
                    dataTable.draw();

                    toastr.success(`Se cargaron ${tableData.length} organizaciones`);

                } else {
                    $('#contadorResultados').text('Error al cargar organizaciones');
                    toastr.error("Error al cargar la lista de organizaciones");
                }
            })
            .fail(function(xhr, status, error){
                $('#contadorResultados').text('Error al cargar organizaciones');
                toastr.error("Error de conexión: " + error);
            })
            .always(function(){
                toggleLoading(false);
            });
    }

    // Inicializar DataTable y cargar organizaciones
    inicializarDataTable();
    cargarOrganizaciones();

    // Ver JSON al hacer clic en la fila
    $('#tablaOrg tbody').on('click', 'tr', function (e) {
        if (!$(e.target).closest('.btnEditar, .btnEliminar').length) {
            const data = dataTable.row(this).data();
            if (data) {
                orgActual = data.id;
                let jsonData = data.raw;
                jsonOriginal = JSON.stringify(jsonData, null, 2);
                
                editor.setValue(jsonOriginal);
                editor.setOption("readOnly", true);
                $("#btnGuardarEdicion").hide();
                $("#btnCancelarEdicion").hide();
                
                // Resaltar fila seleccionada
                $('#tablaOrg tbody tr').removeClass('selected');
                $(this).addClass('selected');
                
                toastr.info(`Visualizando organización: ${data.nombre}`);
            }
        }
    });

    // Editar organización
    $('#tablaOrg tbody').on('click', '.btnEditar', function (e) {
        e.stopPropagation();
        const tr = $(this).closest('tr');
        const data = dataTable.row(tr).data();
        
        if (data) {
            orgActual = data.id;
            let jsonData = data.raw;
            jsonOriginal = JSON.stringify(jsonData, null, 2);
            
            editor.setValue(jsonOriginal);
            editor.setOption("readOnly", false);
            $("#btnGuardarEdicion").show();
            $("#btnCancelarEdicion").show();
            
            // Resaltar fila seleccionada
            $('#tablaOrg tbody tr').removeClass('selected');
            tr.addClass('selected');
            
            toastr.warning(`Modo edición: ${data.nombre}`);
        }
    });

    // Cancelar edición
    $("#btnCancelarEdicion").on("click", function(){
        editor.setValue(jsonOriginal);
        editor.setOption("readOnly", true);
        $("#btnGuardarEdicion").hide();
        $("#btnCancelarEdicion").hide();
        toastr.info("Edición cancelada");
    });

    // Guardar edición
    $("#btnGuardarEdicion").on("click", function(){
        if(!orgActual) {
            toastr.error("No hay organización seleccionada");
            return;
        }

        let updatedJson;
        try {
            updatedJson = JSON.parse(editor.getValue());
            
            // Validación básica del JSON
            if (!updatedJson.resourceType || updatedJson.resourceType !== "Organization") {
                toastr.error("El JSON debe ser un recurso FHIR Organization válido");
                return;
            }
            
        } catch(e) {
            toastr.error("Error: El JSON no es válido - " + e.message);
            return;
        }

        toggleLoading(true);

        $.ajax({
            url: "/backend/services/organization/updateOrganization.php?id=" + orgActual,
            method: "PUT",
            data: JSON.stringify(updatedJson),
            contentType: "application/fhir+json",
            success: function(resp){
                toastr.success("Organización actualizada correctamente");
                editor.setOption("readOnly", true);
                $("#btnGuardarEdicion").hide();
                $("#btnCancelarEdicion").hide();
                
                // Recargar la lista para obtener datos actualizados
                setTimeout(() => cargarOrganizaciones(), 1000);
            },
            error: function(xhr, status, error){
                toastr.error("Error al actualizar organización: " + error);
            },
            complete: function(){
                toggleLoading(false);
            }
        });
    });

    // Eliminar organización
    $('#tablaOrg tbody').on('click', '.btnEliminar', function (e) {
        e.stopPropagation();
        const tr = $(this).closest('tr');
        const data = dataTable.row(tr).data();
        
        if (!data) return;

        let id = data.id;
        let nombre = data.nombre;
        let codigo = data.codigo;

        $('#confirmModalBody').html(`
            <p>¿Está seguro que desea eliminar la organización?</p>
            <div class="alert alert-warning">
                <strong>${codigo}</strong><br>
                ${nombre}
            </div>
            <p class="text-danger"><small>Esta acción no se puede deshacer.</small></p>
        `);
        
        $('#confirmAction').off('click').on('click', function(){
            $('#confirmModal').modal('hide');
            
            toggleLoading(true);
            
            $.ajax({
                url: "/backend/services/organization/deleteOrganization.php?id=" + id,
                method: "DELETE",
                success: function(resp){
                    toastr.success("Organización eliminada correctamente");
                    dataTable.row(tr).remove().draw();
                    editor.setValue("");
                },
                error: function(xhr, status, error){
                    toastr.error("Error al eliminar organización: " + error);
                },
                complete: function(){
                    toggleLoading(false);
                }
            });
        });
        
        $('#confirmModal').modal('show');
    });

    // Filtro búsqueda personalizado (si se desea mantener además del filtro de DataTables)
    $("#filtroOrg").on("keyup", function(){
        dataTable.search($(this).val()).draw();
    });

    // Limpiar filtro
    $("#btnLimpiarFiltro").on("click", function(){
        $("#filtroOrg").val("");
        dataTable.search("").draw();
    });

    // Recargar lista
    $("#btnRecargar").on("click", function(){
        cargarOrganizaciones();
    });

    // Copiar JSON al portapapeles
    $("#btnCopiarJSON").on("click", function(){
        let jsonText = editor.getValue();
        if (!jsonText) {
            toastr.warning("No hay JSON para copiar");
            return;
        }
        
        navigator.clipboard.writeText(jsonText).then(function() {
            toastr.success("JSON copiado al portapapeles");
        }, function(err) {
            // Fallback para navegadores más antiguos
            let textArea = document.createElement("textarea");
            textArea.value = jsonText;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            toastr.success("JSON copiado al portapapeles");
        });
    });
});
</script>