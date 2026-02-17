<?php include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');
// Obtener la conexión a la base de datos
$dbconn = getConnection();

// Obtener el id
$id =  $_SESSION['idUsuario'];

// Preparar la consulta SQL
$sql = "SELECT u.usuario_id, p.persona_id, u.usuario_nick, p.persona_cedula, p.persona_nombre, p.persona_apellido, p.persona_fechanacimiento,  p.persona_genero, u.usuario_email, u.rol_id, 
        r.rol_descripcion, u.estado_id, u.dependencia_id, d.dependencia_descripcion
            FROM public.usuarios u 
            INNER JOIN personas p ON u.persona_id = p.persona_id 
            INNER JOIN roles r ON u.rol_id = r.rol_id 
            INNER JOIN dependencia d ON u.dependencia_id = d.dependencia_id
        WHERE u.usuario_id=:id";

// Preparar la declaración para seleccionar salones
$stmt = $dbconn->prepare($sql);
$stmt->bindParam(':id', $id);
$stmt->execute();

// Obtener los resultados como un array asociativo
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$nombre = explode(" ", $usuario['persona_nombre']);
$apellido = explode(" ", $usuario['persona_apellido']);
//dar formato a dd/mm/yyyyy
$fecha = date("d/m/Y", strtotime($usuario['persona_fechanacimiento']));
?>
<style>
  /* Estilos para el contenedor principal del perfil */
  .profile-container {
    position: relative;
    display: inline-block;
    text-align: center;
    /* Centra horizontalmente */
    width: 100%;
    /* Ajusta el ancho según tu diseño */
  }

  /* Estilos para el contenedor de la imagen de perfil */
  .profile-image {
    position: relative;
    display: inline-block;
    /* Permite que el contenedor se ajuste al tamaño de la imagen */
    text-align: center;
    /* Centra horizontalmente el contenido */
  }

  /* Estilos para la imagen de perfil */
  .profile-user-img {
    width: 160px;
    /* Ajusta el tamaño según tu diseño */
    height: 160px;
    /* Ajusta el tamaño según tu diseño */
    border-radius: 50%;
    /* Forma circular */
    display: block;
    /* Asegura que la imagen se muestre como bloque */
    margin: 0 auto;
    /* Centra la imagen horizontalmente */
  }

  /* Estilos para el ícono de cámara */
  .change-profile-icon {
    position: absolute;
    top: 10px;
    /* Ajusta la posición vertical */
    right: 10px;
    /* Ajusta la posición horizontal */
    background-color: rgba(255, 255, 255, 0.8);
    /* Fondo semi-transparente */
    border-radius: 50%;
    /* Forma circular */
    padding: 8px;
  }

  .change-profile-icon i {
    color: #333;
    /* Color del ícono */
    font-size: 20px;
    /* Tamaño del ícono */
  }

  /* Estilos para el botón de cambiar foto de perfil */
  .btn-change-profile {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    /* Invisible pero clickeable */
  }
</style>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Perfil</h1>
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
        <div class="col-md-3">

          <!-- Profile Image -->
          <div class="card card-primary card-outline">
            <div class="card-body box-profile">
              <!-- Contenedor principal del perfil -->
              <!-- Contenedor principal del perfil -->
              <div class="profile-container">
                <!-- Contenedor para la imagen de perfil -->
                <div class="profile-image">
                  <img class="profile-user-img img-fluid img-circle" src="../../public/dist/img/<?= $_SESSION['avatar']; ?>" alt="User profile picture">

                  <!-- Ícono de cámara -->
                  <div class="change-profile-icon">
                    <i class="fas fa-camera"></i>
                  </div>
                </div>

                <!-- Botón para cambiar la foto de perfil -->
                <button type="button" class="btn btn-primary btn-change-profile" data-toggle="modal" data-target="#modal-agregar">
                  Cambiar foto
                </button>
              </div>


              <h3 class="profile-username text-center"><?= $nombre[0] . " " . $apellido[0]; ?></h3>

              <p class="text-muted text-center"><?= $usuario['dependencia_descripcion']; ?></p>

              <ul class="list-group list-group-unbordered mb-3">
                <li class="list-group-item">
                  <b>Documento</b> <a class="float-right"><?= $usuario['persona_cedula']; ?></a>
                </li>
                <li class="list-group-item">
                  <b>Fecha de Nacimiento</b> <a class="float-right"><?= $fecha; ?> </a>
                </li>
              </ul>

            </div>
            <!-- /.card-body -->
          </div>
          <!-- /.card -->

          <!-- About Me Box -->

          <!-- /.card -->
        </div>
        <!-- /.col -->
        <div class="col-md-9">
          <!-- general form elements -->
          <div class="card card-primary card-outline">

            <!-- /.card-header -->
            <!-- form start -->
            <form id="form-editar" class="user">
              <input type="hidden" name="codigo" id="codigo" value="<?= $usuario['usuario_id']; ?>" readonly>
              <div class="card-body">
                <div class="row">
                  <div class="col-lg-12 col-xs-12">
                    <label for="nombre">Nombre Completo</label>
                    <div class="input-group mb-3">
                      <input type="text" class="form-control rounded-0" name="nombre" id="nombre" placeholder="" value="<?= $usuario['persona_nombre'] . " " . $usuario['persona_apellido']; ?>" readonly>
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
                  <div class="col-lg-12 col-xs-12">
                    <label for="pass">Contraseña</label>
                    <div class="input-group mb-3">
                      <input type="password" class="form-control rounded-0" name="new-password" id="new-password" placeholder="">
                    </div>
                  </div>
                  <div class="col-lg-12 col-xs-12">
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
                <div class="float-left">               
                  <a href="/home"  class="btn btn-warning">Cerrar</a>
                </div>
                <div class="float-right">              
                    <input type="hidden" name="accion" value="modificar">
                    <button id="editar" type="submit" class="btn btn-success">Guardar</button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <!-- /.col -->
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
    // Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    });

    // Restringir archivos que sobrepasen los 8M
    $('#avatar').bind('change', function() {
      max_upload_size = 8 * 1024 * 1024;
      if (this.files[0].size > max_upload_size) {
        file_size = Math.ceil((this.files[0].size / 1024) / 1024);
        max_allowed = Math.ceil((max_upload_size / 1024) / 1024);
        Swal.fire("Error!", "El tamaño del archivo seleccionado (" + file_size + " Mb) supera el tamaño maximo de carga permitido (" + max_allowed + " Mb).", "error");
      }
    });

    function enviarFormulario() {
      $("#guardar").attr("disabled", "disabled");
      // CREAMOS UN OBJETO FORM DATA (para enviar tambien los archivos)
      var fd = new FormData();

      // Agregamos los campos del formulario con sus respectivos valores al objeto     
      fd.append('code', $("[name='code']").val());
      fd.append('accion', $("[name='accio']").val());


      // En caso de haberse cargado los archivos los agregamos al objeto
      // Documento de internacion
      var files1 = $('#avatar')[0].files;
      if (files1[0]) {
        fd.append('avatar', files1[0]);
      }


      $.ajax({
        url: "../../backend/perfil.php",
        type: 'post',
        data: fd,
        contentType: false,
        processData: false,
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
              $('#guardar').removeAttr("disabled");
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





    function editarFormulario() {
      $('#editar').attr("disabled", "disabled");
      $.ajax({
        url: '../../backend/usuarios.php',
        method: 'POST',
        data: $('#form-editar').serialize(),
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
              $('#editar').removeAttr("disabled");
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

    $('#form-editar').submit(function(e) {
      e.preventDefault();
    //  editarFormulario();
    });




  });
</script>