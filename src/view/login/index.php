<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="../../public/dist/img/mspbs.ico" />
    <title>LACPASS | MSPBS</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../public/plugins/fontawesome-free/css/all.min.css">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="../../public/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="../../public/dist/css/adminlte.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="../../public/plugins/toastr/toastr.min.css">
    <!-- Custom CSS -->
    <style>
        .logo-sicam {
            font-size: 2.5rem;
            font-weight: bold;
            color: #7b161e;
            margin-top: 10px;
            font-style: oblique; /* Texto en cursiva */

        }
        .leyenda {
            font-size: 1rem;
            color: #65050c;
            margin-top: 5px;
            font-style: italic; /* Texto en cursiva */

        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 15px 0;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        .divider span {
            padding: 0 10px;
            color: #777;
            font-size: 14px;
        }
    </style>
</head>

<body class="hold-transition login-page" style="background-image: linear-gradient(346deg, rgba(55, 55, 55,0.04) 0%, rgba(55, 55, 55,0.04) 22%,rgba(140, 140, 140,0.04) 22%, rgba(140, 140, 140,0.04) 69%,rgba(225, 225, 225,0.04) 69%, rgba(225, 225, 225,0.04) 100%),linear-gradient(31deg, rgba(55, 55, 55,0.04) 0%, rgba(55, 55, 55,0.04) 42%,rgba(140, 140, 140,0.04) 42%, rgba(140, 140, 140,0.04) 85%,rgba(225, 225, 225,0.04) 85%, rgba(225, 225, 225,0.04) 100%),linear-gradient(55deg, rgba(55, 55, 55,0.04) 0%, rgba(55, 55, 55,0.04) 13%,rgba(140, 140, 140,0.04) 13%, rgba(140, 140, 140,0.04) 72%,rgba(225, 225, 225,0.04) 72%, rgba(225, 225, 225,0.04) 100%),linear-gradient(90deg, rgb(101,5,12),rgb(101,5,12));" >
    <div class="login-box">
        <!-- /.login-logo -->
        <div class="card card-outline card-danger">
            <div class="card-header text-center">
                <img src="../../public/dist/img/logo_mspbs.png" alt="MSPBS Logo" width="100%" height="100%">
                <div class="logo-sicam"><strong>SIS</strong></div>
                <div class="leyenda">Donde la Interoperabilidad y el Cuidado se Encuentran</div>
            </div>
            <div class="card-body">
                <form id="login">
                    <div class="input-group mb-3">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user text-danger"></span>
                            </div>
                        </div>
                        <input type="text" id="user" name="user" class="form-control" placeholder="Usuario">
                    </div>
                    <div class="input-group mb-3">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock text-danger"></span>
                            </div>
                        </div>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Contraseña">
                    </div>
                    <div class="row">
                        <!-- /.col -->
                        <div class="col-12">
                            <input type="hidden" name="accion" value="ingresar">
                            <button id="ingresar" type="submit" class="btn btn-danger btn-block">Iniciar</button>
                        </div>
                        <!-- /.col -->
                    </div>
                </form>
                
                <!-- Separador visual -->
                <div class="divider">
                    <span>O</span>
                </div>
                
                <!-- Botón de acceso como invitado -->
                <div class="row">
                    <div class="col-12">
                        <button id="accesoInvitado" class="btn btn-outline-secondary btn-block">
                            <i class="fas fa-user-clock mr-2"></i>Acceder como Invitado
                        </button>
                    </div>
                </div>
                
                <div class="social-auth-links text-center mt-2 mb-3">
                    <span>
                        © <?php echo date("Y"); ?> MSPBS -DGTIC
                    </span>
                </div>
                <!-- /.social-auth-links -->
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
    <!-- /.login-box -->

    <!-- jQuery -->
    <script src="../../public/plugins/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="../../public/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="../../public/dist/js/adminlte.min.js"></script>
    <!-- Toastr -->
    <script src="../../public/plugins/toastr/toastr.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            // Manejo del formulario de login normal
            $('#login').submit(function(e) {
                e.preventDefault(); // Evitar el envío del formulario por defecto
                var $submitButton = $('#ingresar'); // Cacheamos el botón de enviar para mejor rendimiento

                // Desactivar el botón de enviar durante la solicitud
                $submitButton.prop("disabled", true);

                // Realizar la solicitud AJAX
                $.ajax({
                    url: '/backend/ingresar.php',
                    method: 'POST',
                    data: $(this).serialize(), // Serializar los datos del formulario
                    success: function(data) {
                        try {
                            var response = JSON.parse(data);
                            if (response.status == "success") {
                                // Redirigir al usuario a la página de inicio si la autenticación es exitosa
                                window.location.href = '/home';
                            } else if (response.status == "cambiarpass") {
                                location.href = '/usuarios/password';
                            } else if (response.status == "reseteo") {
                                location.href = '/usuarios/reseteo';
                            } else if (response.status == "desactivado") {
                                // Mostrar un mensaje de error si la autenticación falla
                                toastr.error("ERROR: en el Usuario esta desactivado");
                            } else {
                                // Mostrar un mensaje de error si la autenticación falla
                                toastr.error("ERROR: en el Usuario o la Contraseña");
                            }
                        } catch (error) {
                            // Manejar errores de análisis JSON
                            toastr.error("Advertencia: Ocurrió un error al procesar la respuesta del servidor. Por favor, contacte con el administrador del sistema.");
                        }
                    },
                    error: function(xhr, status, error) {
                        // Manejar errores de la solicitud AJAX
                        toastr.error("Advertencia: Ocurrió un error al comunicarse con el servidor. Por favor, contacte con el administrador de la red.");
                    },
                    complete: function() {
                        // Reactivar el botón de enviar después de completar la solicitud
                        $submitButton.prop("disabled", false);
                    }
                });
            });
            
            // Manejo del acceso como invitado
            $('#accesoInvitado').click(function(e) {
                e.preventDefault();
                var $button = $(this);
                
                // Desactivar el botón durante la solicitud
                $button.prop("disabled", true);
                
                // Realizar solicitud para acceso como invitado
                $.ajax({
                    url: '/backend/ingresar.php',
                    method: 'POST',
                    data: {
                        accion: 'ingresar_invitado'
                    },
                    success: function(data) {
                        try {
                            var response = JSON.parse(data);
                            if (response.status == "success") {
                                // Redirigir al usuario a la página de inicio
                                window.location.href = '/home';
                            } else {
                                // Mostrar un mensaje de error
                                toastr.error("ERROR: No se pudo acceder como invitado");
                            }
                        } catch (error) {
                            // Manejar errores de análisis JSON
                            toastr.error("Advertencia: Ocurrió un error al procesar la respuesta del servidor. Por favor, contacte con el administrador del sistema.");
                        }
                    },
                    error: function(xhr, status, error) {
                        // Manejar errores de la solicitud AJAX
                        toastr.error("Advertencia: Ocurrió un error al comunicarse con el servidor. Por favor, contacte con el administrador de la red.");
                    },
                    complete: function() {
                        // Reactivar el botón después de completar la solicitud
                        $button.prop("disabled", false);
                    }
                });
            });
        });
    </script>
</body>

</html>