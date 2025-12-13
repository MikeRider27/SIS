<?php
include('/var/www/html/view/includes/header.php');
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12 text-center">
                    <h1 class="display-5 fw-bold text-primary">Viewer de Pacientes FHIR</h1>
                    <p class="lead text-muted">Sistema de visualización y gestión de pacientes según estándar FHIR</p>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">

                <!-- Panel izquierdo - Lista de Pacientes -->
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                       <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <!-- TEXTO A LA IZQUIERDA -->
                                <div class="flex-shrink-0">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list me-2"></i><strong>Lista de Pacientes</strong>
                                    </h5>
                                </div>

                                <!-- BOTÓN A LA DERECHA -->
                                <div class="flex-shrink-0">
                                    <a href="/patient" class="btn btn-success btn-sm" id="btn-nuevo-paciente">
                                        <i class="fas fa-plus me-1"></i> Nuevo
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                           
                            
                            <!-- Contador de resultados -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="text-muted" id="contadorResultados">Cargando pacientes...</small>                              
                            </div>

                            <!-- Tabla de pacientes con DataTables -->
                            <div class="table-container" style="height: calc(100% - 110px); overflow: auto;">
                                <table id="tablaPacientes" class="table table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Cédula</th>
                                            <th>Nombre</th>
                                            <th>Actualizado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Los datos se cargan via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel derecho - Detalle JSON -->
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-dark text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-code me-2"></i><strong>Detalle JSON - Recurso FHIR</strong>
                            </h5>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="alert alert-info d-flex align-items-center mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>Seleccione un paciente para visualizar su recurso FHIR completo</small>
                            </div>
                            
                            <div class="json-container flex-grow-1">
                                <textarea id="jsonDisplay" class="d-none"></textarea>
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

.card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    height: 100%;
}

.card-header {
    border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.table-container {
    border: 1px solid #e3e6f0;
    border-radius: var(--border-radius);
}

#tablaPacientes {
    margin-bottom: 0;
}

#tablaPacientes tbody tr {
    cursor: pointer;
    transition: all 0.3s;
}

#tablaPacientes tbody tr:hover {
    background-color: #f8f9fa;
}

#tablaPacientes tbody tr.selected {
    background-color: #e3f2fd;
    border-left: 3px solid var(--primary-color);
}

.table th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fa;
}

.cedula-col {
    font-weight: 600;
    color: var(--primary-color);
}

.actions-col {
    white-space: nowrap;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 0.8rem;
    margin: 0 2px;
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

.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate {
    padding: 10px;
}

@media (max-width: 992px) {
    .col-lg-6 {
        margin-bottom: 20px;
    }
    
    .CodeMirror {
        height: calc(100% - 120px);
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
    var editor = CodeMirror.fromTextArea(document.getElementById("jsonDisplay"), {
        lineNumbers: true,
        mode: "application/json",
        theme: "material",
        readOnly: true,
        lineWrapping: true
    });

    let pacienteActual = null;
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

    // Función para cargar pacientes en DataTable
    function cargarPacientes() {
        toggleLoading(true);
        $('#contadorResultados').text('Cargando pacientes...');

        // Destruir DataTable existente si existe
        if (dataTable) {
            dataTable.destroy();
        }

        // Inicializar DataTable
        dataTable = $('#tablaPacientes').DataTable({
            "ajax": {
                "url": "/patients/list",
                "dataSrc": function (json) {
                    if (json.status === "success") {
                        // Ordenar por fecha más nueva
                        json.patients.sort((a, b) => new Date(b.lastUpdated) - new Date(a.lastUpdated));
                        
                        // Procesar datos para DataTable
                        let data = json.patients.map(p => {
                            return {
                                id: p.id,
                                cedula: p.cedula ? p.cedula : "SIN CÉDULA",
                                nombre: p.nombre || "Nombre no disponible",
                                lastUpdated: new Date(p.lastUpdated).toLocaleDateString(),
                                raw: p.raw,
                                dtLastUpdated: new Date(p.lastUpdated)
                            };
                        });
                        
                        $('#contadorResultados').text(`${data.length} paciente(s) encontrado(s)`);
                        toastr.success(`Se cargaron ${data.length} pacientes`);
                        toggleLoading(false);
                        
                        return data;
                    } else {
                        $('#contadorResultados').text('Error al cargar pacientes');
                        toastr.error("Error al cargar la lista de pacientes");
                        toggleLoading(false);
                        return [];
                    }
                },
                "error": function (xhr, error, thrown) {
                    $('#contadorResultados').text('Error al cargar pacientes');
                    toastr.error("Error de conexión: " + error);
                    toggleLoading(false);
                }
            },
            "columns": [
                { 
                    "data": "cedula",
                    "className": "cedula-col"
                },
                { "data": "nombre" },
                { 
                    "data": "lastUpdated",
                    "width": "120px"
                },
                {
                    "data": null,
                    "className": "actions-col",
                    "width": "100px",
                    "render": function (data, type, row) {
                        return `
                            <button class="btn btn-sm btn-outline-primary btnEditar" title="Editar" data-id="${row.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btnEliminar" title="Eliminar" data-id="${row.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                    },
                    "orderable": false
                }
            ],
            "order": [[2, 'desc']], // Ordenar por fecha descendente
            "language": {
                "search": "Buscar:",
                "lengthMenu": "Mostrar _MENU_ registros",
                "zeroRecords": "No se encontraron resultados",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            "responsive": true,
            "autoWidth": false,
            "drawCallback": function(settings) {
                // Actualizar contador después de cada dibujo
                let api = this.api();
                let total = api.rows({ search: 'applied' }).count();
                $('#contadorResultados').text(`${total} paciente(s) encontrado(s)`);
            }
        });

        // Evento para seleccionar fila
        $('#tablaPacientes tbody').on('click', 'tr', function () {
            if ($(this).hasClass('selected')) {
                $(this).removeClass('selected');
                editor.setValue("");
                pacienteActual = null;
            } else {
                dataTable.$('tr.selected').removeClass('selected');
                $(this).addClass('selected');
                
                let data = dataTable.row(this).data();
                if (data) {
                    pacienteActual = data.id;
                    let jsonData = data.raw;
                    jsonOriginal = JSON.stringify(jsonData, null, 2);
                    
                    editor.setValue(jsonOriginal);
                    editor.setOption("readOnly", true);
                    $("#btnGuardarEdicion").hide();
                    $("#btnCancelarEdicion").hide();
                    
                    toastr.info(`Visualizando paciente: ${data.nombre}`);
                }
            }
        });

        // Evento para botón editar
        $('#tablaPacientes tbody').on('click', '.btnEditar', function (e) {
            e.stopPropagation();
            let id = $(this).data('id');
            let row = dataTable.row($(this).closest('tr'));
            let data = row.data();
            
            if (data) {
                pacienteActual = data.id;
                let jsonData = data.raw;
                jsonOriginal = JSON.stringify(jsonData, null, 2);
                
                editor.setValue(jsonOriginal);
                editor.setOption("readOnly", false);
                $("#btnGuardarEdicion").show();
                $("#btnCancelarEdicion").show();
                
                // Seleccionar la fila
                dataTable.$('tr.selected').removeClass('selected');
                $(this).closest('tr').addClass('selected');
                
                toastr.warning(`Modo edición: ${data.nombre}`);
            }
        });

        // Evento para botón eliminar
        $('#tablaPacientes tbody').on('click', '.btnEliminar', function (e) {
            e.stopPropagation();
            let id = $(this).data('id');
            let row = dataTable.row($(this).closest('tr'));
            let data = row.data();
            
            if (data) {
                $('#confirmModalBody').html(`
                    <p>¿Está seguro que desea eliminar al paciente?</p>
                    <div class="alert alert-warning">
                        <strong>${data.cedula}</strong><br>
                        ${data.nombre}
                    </div>
                    <p class="text-danger"><small>Esta acción no se puede deshacer.</small></p>
                `);
                
                $('#confirmAction').off('click').on('click', function(){
                    $('#confirmModal').modal('hide');
                    
                    toggleLoading(true);
                    
                    $.ajax({
                        url: "/backend/services/pacientes/deletePaciente.php?id=" + data.id,
                        method: "DELETE",
                        success: function(resp){
                            toastr.success("Paciente eliminado correctamente");
                            dataTable.row(row).remove().draw();
                            editor.setValue("");
                            pacienteActual = null;
                        },
                        error: function(xhr, status, error){
                            toastr.error("Error al eliminar paciente: " + error);
                        },
                        complete: function(){
                            toggleLoading(false);
                        }
                    });
                });
                
                $('#confirmModal').modal('show');
            }
        });
    }

    // Inicializar carga de pacientes
    cargarPacientes();

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
        if(!pacienteActual) {
            toastr.error("No hay paciente seleccionado");
            return;
        }

        let updatedJson;
        try {
            updatedJson = JSON.parse(editor.getValue());
            
            // Validación básica del JSON
            if (!updatedJson.resourceType || updatedJson.resourceType !== "Patient") {
                toastr.error("El JSON debe ser un recurso FHIR Patient válido");
                return;
            }
            
        } catch(e) {
            toastr.error("Error: El JSON no es válido - " + e.message);
            return;
        }

        toggleLoading(true);

        $.ajax({
            url: "/backend/services/pacientes/updatePaciente.php?id=" + pacienteActual,
            method: "PUT",
            data: JSON.stringify(updatedJson),
            contentType: "application/fhir+json",
            success: function(resp){
                toastr.success("Paciente actualizado correctamente");
                editor.setOption("readOnly", true);
                $("#btnGuardarEdicion").hide();
                $("#btnCancelarEdicion").hide();
                
                // Recargar la tabla para obtener datos actualizados
                dataTable.ajax.reload();
            },
            error: function(xhr, status, error){
                toastr.error("Error al actualizar paciente: " + error);
            },
            complete: function(){
                toggleLoading(false);
            }
        });
    });

    // Filtro búsqueda personalizado
    $("#filtro").on("keyup", function(){
        dataTable.search($(this).val()).draw();
    });

    // Limpiar filtro
    $("#btnLimpiarFiltro").on("click", function(){
        $("#filtro").val("");
        dataTable.search("").draw();
    });

    // Recargar lista
    $("#btnRecargar").on("click", function(){
        dataTable.ajax.reload();
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