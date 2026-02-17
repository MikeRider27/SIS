<?php include('/var/www/html/view/includes/header.php'); ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-12">
          <h1>Listado de Usuarios</h1>

        </div>

      </div>
    </div><!-- /.container-fluid -->
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header py-3">
              <div class="float-left">
                <h3 class="card-title" style="display: inline-block;">Usuarios</h3>
              </div>
              <div class="float-right">
                <a href="/usuarios/create" class="btn btn-success"><i class="fas fa-plus"></i> Nuevo Usuario</a>
              </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="listado" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Correo Electronico</th>
                    <th>Dependencia</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acción</th>
                  </tr>
                </thead>
                <tfoot>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Correo Electronico</th>
                    <th>Dependencia</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acción</th>
                  </tr>
                </tfoot>
              </table>
            </div>
            <!-- /.card-body -->
          </div>
          <!-- /.card -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php
include('/var/www/html/view/includes/footer.php');
?>
<!-- Page specific script -->


<script>
  $(function() {

    Reseteo = function(id) {
      swal({
        title: "Confirmar",
        text: "Está seguro de resetear este Usuario?",
        type: "warning",
        confirmButtonText: "SI",
        confirmButtonColor: "#5cb85c",
        showCancelButton: true,
        cancelButtonText: "NO",
      }, function(isConfirm) {
        if (isConfirm) {
          $.ajax({
            url: '../../backend/usuarios.php',
            method: 'POST',
            data: 'accion=reseteo&id=' + id,
            success: function(data) {
              try {
                response = JSON.parse(data);
                if (response.status == "success") {
                  setTimeout(function() {
                    swal({
                        title: "Éxito!",
                        text: response.message,
                        type: "success",
                        confirmButtonText: "Ok",
                        closeOnConfirm: false
                      },
                      function() {
                        location.reload();
                      });
                  }, 2000);
                } else {
                  swal("Advertencia", "Ocurrio un error intentado resolver la solicitud. Por favor contacte con el administrador del sistema", "warning");
                }
              } catch (error) {
                swal("Advertencia", "Ocurrio un error intentado resolver la solicitud. Por favor contacte con el administrador del sistema", "warning");
              }
            },
            error: function(data) {
              swal("Advertencia", "Ocurrio un error intentado comunicarse con el servidor. Por favor contacte con el administrador de la red", "warning");
            }
          });
        }
      })
    };

    Desactivar = function(id) {
      swal({
        title: "Confirmar",
        text: "Está seguro de Inactivar este Usuario?",
        type: "warning",
        confirmButtonText: "SI",
        confirmButtonColor: "#5cb85c",
        showCancelButton: true,
        cancelButtonText: "NO",
      }, function(isConfirm) {
        if (isConfirm) {
          $.ajax({
            url: '../../backend/usuarios.php',
            method: 'POST',
            data: 'accion=desactivar&id=' + id,
            success: function(data) {
              try {
                response = JSON.parse(data);
                if (response.status == "success") {
                  setTimeout(function() {
                    swal({
                        title: "Éxito!",
                        text: response.message,
                        type: "success",
                        confirmButtonText: "Ok",
                        closeOnConfirm: false
                      },
                      function() {
                        location.reload();
                      });
                  }, 2000);
                } else {
                  swal("Advertencia", "Ocurrio un error intentado resolver la solicitud. Por favor contacte con el administrador del sistema", "warning");
                }
              } catch (error) {
                swal("Advertencia", "Ocurrio un error intentado resolver la solicitud. Por favor contacte con el administrador del sistema", "warning");
              }
            },
            error: function(data) {
              swal("Advertencia", "Ocurrio un error intentado comunicarse con el servidor. Por favor contacte con el administrador de la red", "warning");
            }
          });
        }
      })
    };

    Activar = function(id) {
      swal({
        title: "Confirmar",
        text: "Está seguro de Activar este Usuario?",
        type: "warning",
        confirmButtonText: "SI",
        confirmButtonColor: "#5cb85c",
        showCancelButton: true,
        cancelButtonText: "NO",
      }, function(isConfirm) {
        if (isConfirm) {
          $.ajax({
            url: '../../backend/usuarios.php',
            method: 'POST',
            data: 'accion=activar&id=' + id,
            success: function(data) {
              try {
                response = JSON.parse(data);
                if (response.status == "success") {
                  setTimeout(function() {
                    swal({
                        title: "Éxito!",
                        text: response.message,
                        type: "success",
                        confirmButtonText: "Ok",
                        closeOnConfirm: false
                      },
                      function() {
                        location.reload();
                      });
                  }, 2000);
                } else {
                  swal("Advertencia", "Ocurrio un error intentado resolver la solicitud. Por favor contacte con el administrador del sistema", "warning");
                }
              } catch (error) {
                swal("Advertencia", "Ocurrio un error intentado resolver la solicitud. Por favor contacte con el administrador del sistema", "warning");
              }
            },
            error: function(data) {
              swal("Advertencia", "Ocurrio un error intentado comunicarse con el servidor. Por favor contacte con el administrador de la red", "warning");
            }
          });
        }
      })
    };

    function handleAjaxError(xhr, textStatus, error) {
      if (textStatus === "timeout") {
        toastr.warning('Ocurrió un error al intentar comunicarse con el servidor. Por favor contacte con el administrador de la red.');
        document.getElementById("listado_processing").style.display = "none";
      } else {
        toastr.warning('Ocurrió un error al intentar comunicarse con el servidor. Por favor contacte con el administrador del sistema.');
        document.getElementById("listado_processing").style.display = "none";
      }
    }

    var table = $('#listado').DataTable({
      "responsive": true,
      "lengthChange": false,
      "autoWidth": false,
      "ajax": {
        url: "../../backend/listado/listaUsuarios.php",
        timeout: 15000,
        dataSrc: function(json) {
          if (json.status === "error") {
            alert(json.message);
            return [];
          }
          return json.data;
        },
        error: function(xhr, textStatus, error) {
          let errorMessage;
          switch (textStatus) {
            case 'timeout':
              errorMessage = 'La solicitud ha superado el tiempo de espera. Inténtalo de nuevo.';
              break;
            case 'abort':
              errorMessage = 'La solicitud ha sido abortada.';
              break;
            default:
              errorMessage = 'Ocurrió un error: ' + error;
          }
          handleAjaxError(xhr, textStatus, error);
          alert(errorMessage);
        }
      },
      "columns": [{
          "data": "id"
        },
        {
          "data": "nombre"
        },
        {
          "data": "nick"
        },
        {
          "data": "email"
        },
        {
          "data": "dependencia"
        },
        {
          "data": "rol"
        },
        {
          "data": "estado"
        },
        {
          "data": "id"
        }
      ],
      "columnDefs": [{
        "render": function(number_row, type, row) {
          var buttons = '<div class="row">' +
            '<button class="btn btn-warning" onclick="Reseteo(' + row.id + ');"><i class="fas fa-unlock"></i></button>';
          if (row.code === 1) {
            buttons += '<button class="btn btn-danger" onclick="Desactivar(' + row.id + ');"> <i class="fas fa-lock"></i></button>';
          } 
          if (row.code === 2){
            buttons += '<button class="btn btn-success" onclick="Activar(' + row.id + ');"> <i class="fas fa-unlock"></i></button></div>';
          }
          buttons += '</div>';
          return buttons;
        },
        "orderable": false,
        "targets": 7 // columna modificar usuario
      }],
      "language": {
        "decimal": "",
        "emptyTable": "No hay registros en la tabla",
        "info": "Se muestran _START_ a _END_ de _TOTAL_ registros",
        "infoEmpty": "Se muestran 0 a 0 de 0 registros",
        "infoFiltered": "(filtrado de _MAX_ registros totales)",
        "infoPostFix": "",
        "thousands": ",",
        "lengthMenu": "Mostrar _MENU_ registros",
        "loadingRecords": "Cargando...",
        "processing": "Procesando...",
        "search": "Filtrar por (Nombre):",
        "zeroRecords": "No se encontraron registros que coincidan",
        "paginate": {
          "first": "Primero",
          "last": "Último",
          "next": "Siguiente",
          "previous": "Anterior"
        },
        "aria": {
          "sortAscending": ": activar para ordenar la columna ascendente",
          "sortDescending": ": activar para ordenar la columna descendente"
        }
      }
    });
  });
</script>