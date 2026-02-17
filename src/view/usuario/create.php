<?php include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');
// Obtener la conexión a la base de datos
$dbconn = getConnection();

// Preparar la consulta SQL
$sql = "SELECT rol_id, rol_descripcion FROM roles;";

// Preparar la declaración para seleccionar Roles
$stmt = $dbconn->prepare($sql);
$stmt->execute();

// Obtener los resultados como un array asociativo
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preparar la consulta SQL
$sql1 = "SELECT dependencia_id, dependencia_descripcion FROM dependencia;";

// Preparar la declaración para seleccionar dependencias
$stmt1 = $dbconn->prepare($sql1);
$stmt1->execute();

// Obtener los resultados como un array asociativo
$dependencias = $stmt1->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Creacion de Usuario</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">

          </ol>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <!-- left column -->
        <div class="col-md-12">
          <!-- general form elements -->
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">Usuario</h3>
            </div>
            <!-- /.card-header -->
            <!-- form start -->
            <form id="form" class="user">
              <div class="card-body">
                <div class="row">
                  <div class="col-lg-4 col-xs-12">
                    <label for="cedula">Cédula de Identidad</label>
                    <div class="input-group mb-3">
                      <input type="text" class="form-control rounded-0" name="cedula" id="cedula" placeholder="">
                      <span class="input-group-append" id="search">
                        <button id="getDatosCedula" type="submit" class="btn btn-primary btn-flat"> <i class="fas fa-search" aria-hidden="true"></i></button>
                      </span>
                    </div>
                  </div>
                  <div class="col-lg-4 col-xs-12">
                    <label for="nombre">Nombre</label>
                    <div class="input-group mb-3">
                      <input type="text" class="form-control rounded-0" name="nombre" id="nombre" placeholder="" readonly>
                      <input type="hidden" class="form-control rounded-0" name="fecha_nacimiento" id="fecha_nacimiento" placeholder="">
                    </div>
                  </div>
                  <div class="col-lg-4 col-xs-12">
                    <label for="apellido">Apellido</label>
                    <div class="input-group mb-3">
                      <input type="text" class="form-control rounded-0" name="apellido" id="apellido" placeholder="" readonly>
                      <input type="hidden" class="form-control rounded-0" name="sexo" id="sexo" placeholder="">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-lg-4 col-xs-12">
                    <label for="nick">Nombre de Usuario</label>
                    <div class="input-group mb-3">
                      <input type="text" class="form-control rounded-0" name="nick" id="nick" placeholder="">
                    </div>
                  </div>
                  <div class="col-lg-4 col-xs-12">
                    <label for="email">Correo Electronico </label>
                    <div class="input-group mb-3">
                      <input type="email" class="form-control rounded-0" name="email" id="email" placeholder="">
                    </div>
                  </div>
                  <div class="col-lg-4 col-xs-12">
                    <label for="rol">Rol</label>
                    <div class="input-group mb-3">
                      <select class="form-control select2bs4" style="width: 100%; height: 40px;" id="rol" name="rol">
                        <option value="">Seleccione</option>
                        <?php
                        if ($roles !== null) {
                          foreach ($roles as $rol) {
                            echo '<option value="' . $rol['rol_id'] . '">' . $rol['rol_descripcion'] . '</option>';
                          }
                        } else {
                          echo '<option value="">No hay roles disponibles</option>';
                        }
                        ?>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="row">

                  <div class="col-lg-4 col-xs-12">
                    <label for="dependencia">Dependencia</label>
                    <div class="input-group mb-3">
                      <select class="form-control select2bs4" style="width: 100%; height: 40px;" id="dependencia" name="dependencia">
                        <option value="">Seleccione</option>
                        <?php
                        if ($dependencias !== null) {
                          foreach ($dependencias as $dependencia) {
                            echo '<option value="' . $dependencia['dependencia_id'] . '">' . $dependencia['dependencia_descripcion'] . '</option>';
                          }
                        } else {
                          echo '<option value="">No hay depe$dependencias disponibles</option>';
                        }
                        ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-lg-4 col-xs-12">
                    <div id="salones-container">
                      <!-- Aquí se cargarán los checkboxes de los salones -->
                    </div>
                  </div>
                </div>
              </div>
              <!-- /.card-body -->

              <div class="card-footer">                
                <a href="/usuarios"  class="btn btn-warning">Cerrar</a>
                <input type="hidden" name="accion" value="agregar">
                <button id="guardar" type="submit" class="btn btn-success">Guardar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <!-- /.row -->
    </div><!-- /.container-fluid -->
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

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    });

    $('#dependencia').change(function() {
      var dependencia = $(this).val();

      $.ajax({
        url: '../../backend/listas.php',
        type: 'POST',
        data: {
          dependencia: dependencia,
          accion: 'asignarSalon'
        },
        dataType: 'json',
        success: function(response) {
          var salonesContainer = $('#salones-container');
          salonesContainer.empty();

          if (response.status === "success") {
            if (response.salon.length > 0) {
              response.salon.forEach(function(salon) {
                var colorClass = salon.tipo === 2 ? 'green' : 'blue';
                var checkbox = '<div class="checkbox"> ' +
                  '<label style="color: ' + colorClass + ';"> ' +
                  '<input type="checkbox" name="salones[]" value="' + salon.id + '"> ' +
                  salon.descripcion +
                  '</label>' +
                  '</div>';
                salonesContainer.append(checkbox);
              });
            } else {
              salonesContainer.append('<div>No hay salones disponibles</div>');
            }
          } else {
            salonesContainer.append('<div>Error en la solicitud</div>');
          }
        },
        error: function() {
          var salonesContainer = $('#salones-container');
          salonesContainer.empty();
          salonesContainer.append('<div>Error al cargar los salones.</div>');
        }
      });
    });

    // Buscamos la cedula
    $('#getDatosCedula').click(function() {
      const cedula = $('#cedula').val();
      if (cedula) {
        let requestTimeout;

        $('#nombre').attr('readonly', true).val('');
        $('#apellido').attr('readonly', true).val('');
        $('#getDatosCedula').attr('disabled', true);

        try {
          const getDatosCedulaRequest = $.ajax({
            url: '../../backend/getPoliciaLocal.php',
            method: 'POST',
            data: {
              cedula: cedula,
              search: 'SI'
            },
            success: function(data) {
              clearTimeout(requestTimeout);

              const response = JSON.parse(data);
              if (response.status === "success") {
                $('#nombre').val(response.persona.nombres);
                $('#apellido').val(response.persona.apellidos);
                $('#fecha_nacimiento').val(response.persona.fecha_nacimiento);
                $('#sexo').val(response.persona.codigo_genero);
              } else {
                toastr.warning('No se encuentran registros de la cédula ingresada.');
              }

              $('#getDatosCedula').removeAttr('disabled');
            },
            error: function(xhr, ajaxOptions, thrownError) {
              clearTimeout(requestTimeout);

              if (xhr.readyState === 0) {
                toastr.warning('Problemas en la red. No se puede establecer conexión con el servidor de Personas.');
              } else {
                toastr.warning('Ocurrió un error procesando la solicitud. Por favor contacte con el administrador del sistema.');
                console.error(thrownError);
              }

              $('#getDatosCedula').removeAttr('disabled');
            }
          });

          requestTimeout = setTimeout(function() {
            if (getDatosCedulaRequest.readyState !== 4) {
              getDatosCedulaRequest.abort();
              toastr.error('Se está experimentando problemas en contactar con el servicio que provee los datos de las cédulas.');
              $('#getDatosCedula').removeAttr('disabled');
            }
          }, 10000);

        } catch (error) {
          clearTimeout(requestTimeout);
          toastr.warning('Ocurrió un error intentando resolver la solicitud. Por favor contacte con el administrador del sistema.');
          console.error(error);

          $('#getDatosCedula').removeAttr('disabled');
        }
      }
    });

    function enviarFormulario() {
      $('#guardar').attr("disabled", "disabled");
      $.ajax({
        url: '../../backend/usuarios.php',
        method: 'POST',
        data: $('#form').serialize(),
        success: function(data) {
          try {
            response = JSON.parse(data);
            if (response.status == "success") {
              toastr.success(response.message);
              setTimeout(function() {
                location.reload();
              }, 3000);
            } else {
              toastr.error(response.message);
            }
          } catch (error) {
            toastr.error('Ocurrió un error intentando resolver la solicitud. Por favor contacte con el administrador del sistema');
            console.log(error);
          }
        },
        error: function(error) {
          toastr.error('Ocurrió un error intentando resolver la solicitud. Por favor contacte con el administrador de la red');
          console.log(error);
        }
      });
    }

    $('#form').submit(function(e) {
      e.preventDefault();

      // Validación para campos
      if (!isValid()) {
        return false;
      }

      enviarFormulario();
    });

    // Función para validar los campos
    function isValid() {
      if ($('#cedula').val() == "") {
        toastr.warning('Favor cargar la cedula.');
        return false;
      }
      if ($('#nick').val() == "") {
        toastr.warning('Favor cargar un nick.');
        return false;
      }
      if ($('#email').val() == "") {
        toastr.warning('Favor cargar un correo.');
        return false;
      }
      if ($('#rol').val() == "") {
        toastr.warning('Favor seleccionar un rol.');
        return false;
      }
      if ($('#dependencia').val() == "") {
        toastr.warning('Favor seleccionar una dependencia.');
        return false;
      }
      return true;
    }






  });
</script>
<div class="row"></div>