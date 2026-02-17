<?php include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');
// Obtener la conexión a la base de datos
$dbconn = getConnection();

// Obtener el id
$id =  $_SESSION['idUsuario'];

// Preparar la consulta SQL
$sql = "SELECT u.usuario_id, u.usuario_nick, p.persona_nombre, p.persona_apellido, u.usuario_email
        FROM public.usuarios u  INNER JOIN personas p ON u.persona_id = p.persona_id
        WHERE u.usuario_id=:id";

// Preparar la declaración para seleccionar salones
$stmt = $dbconn->prepare($sql);
$stmt->bindParam(':id', $id);
$stmt->execute();

// Obtener los resultados como un array asociativo
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <?php if ($_SESSION['estado'] == 3) { ?>
            <h1>Modificar Primera Contraseña</h1>
          <?php } else { ?>
            <h1>Modificar Contraseña</h1>
          <?php } ?>
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
              <?php if ($_SESSION['estado'] == 3) { ?>
                <h3 class="card-title">En el primer inicio de sesión es necesario cambiar la contraseña, por favor ingrese la nueva contraseña</h3>
              <?php } else { ?>
                <h3 class="card-title">Por motivos de seguridad, su contraseña ha sido restablecida; por favor, cámbiela inmediatamente</h3>
              <?php } ?>

            </div>
            <!-- /.card-header -->
            <!-- form start -->
            <form id="form" class="user">
              <input type="hidden" name="codigo" id="codigo" value="<?= $usuario['usuario_id']; ?>" readonly>
              <div class="card-body">
                <div class="row">
                  <div class="col-lg-6 col-xs-12">
                    <label for="nombre">Nombre</label>
                    <div class="input-group mb-3">
                      <input type="text" class="form-control rounded-0" name="nombre" id="nombre" placeholder="" value="<?= $usuario['persona_nombre']; ?>" readonly>
                    </div>
                  </div>
                  <div class="col-lg-6 col-xs-12">
                    <label for="apellido">Apellido</label>
                    <div class="input-group mb-3">
                      <input type="text" class="form-control rounded-0" name="apellido" id="apellido" placeholder="" value="<?= $usuario['persona_apellido']; ?>" readonly>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-lg-6 col-xs-12">
                    <label for="nick">Nick</label>
                    <div class="input-group mb-3">
                      <input type="text" class="form-control rounded-0" name="nick" id="nick" placeholder="" value="<?= $usuario['usuario_nick']; ?>" readonly>
                    </div>
                  </div>
                  <div class="col-lg-6 col-xs-12">
                    <label for="email">Email</label>
                    <div class="input-group mb-3">
                      <input type="email" class="form-control rounded-0" name="email" id="email" placeholder="" value="<?= $usuario['usuario_email']; ?>" readonly>
                    </div>
                  </div>

                </div>
                <div class="row">
                  <div class="col-lg-6 col-xs-12">
                    <label for="pass">Contraseña</label>
                    <div class="input-group mb-3">
                      <input type="password" class="form-control rounded-0" name="new-password" id="new-password" placeholder="">
                    </div>
                  </div>
                  <div class="col-lg-6 col-xs-12">
                    <label for="pass">Repita Contraseña</label>
                    <div class="input-group mb-3">
                      <input type="password" class="form-control rounded-0" name="confirm-password" id="confirm-password" placeholder="">
                    </div>
                  </div>
                </div>
                <div id="warning_password" class="p-l-10 mb-2" style="display: none;">
                  <i class="fa fa-times-circle"></i> <span id="password_info"></span>
                </div>
              </div>
              <!-- /.card-body -->

              <div class="card-footer">
                <button type="button" class="btn btn-warning">Cerrar</button>
                <input type="hidden" name="accion" value="modificar-pass">
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

    $('#confirm-password').keyup(function() {
      if ($(this).val() == "") {
        $('#warning_password').css("display", "none");
        $('#guardar').attr("disabled", "disabled");
      } else if ($(this).val() != $('#new-password').val()) {
        $('#warning_password').removeClass("text-green");
        $('#warning_password').addClass("text-red");
        $('#password_info').html('Las contraseñas no coinciden');
        $('#warning_password').css("display", "block");
        $('#guardar').attr("disabled", "disabled");
      } else if ($(this).val() == $('#new-password').val()) {
        $('#warning_password').removeClass("text-red");
        $('#warning_password').addClass("text-green");
        $('#password_info').html('Las contraseñas coinciden');
        $('#warning_password').css("display", "block");
        $('#guardar').removeAttr("disabled");
      }
    });

    $('#new-password').keyup(function() {
      if ($(this).val() == "") {
        $('#warning_password').css("display", "none");
        $('#guardar').attr("disabled", "disabled");
      } else if ($('#confirm-password').val() != "" && $(this).val() != $('#confirm-password').val()) {
        $('#warning_password').removeClass("text-green");
        $('#warning_password').addClass("text-red");
        $('#password_info').html('Las contraseñas no coinciden');
        $('#warning_password').css("display", "block");
        $('#guardar').attr("disabled", "disabled");
      } else if ($('#confirm-password').val() != "" && $(this).val() == $('#confirm-password').val()) {
        $('#warning_password').removeClass("text-red");
        $('#warning_password').addClass("text-green");
        $('#password_info').html('Las contraseñas coinciden');
        $('#warning_password').css("display", "block");
        $('#guardar').removeAttr("disabled");
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
                window.location.href = '/salir';
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
      enviarFormulario();
    });








  });
</script>