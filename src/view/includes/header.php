<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="/public/dist/img/mspbs.ico" />
    <title>SIS | MSPBS</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/public/plugins/fontawesome-free/css/all.min.css">
    <!-- fullCalendar -->
    <link rel="stylesheet" href="/public/plugins/fullcalendar/main.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/public/dist/css/adminlte.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="/public/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="/public/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- daterange picker -->
    <link rel="stylesheet" href="/public/plugins/daterangepicker/daterangepicker.css">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="/public/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Bootstrap Color Picker -->
    <link rel="stylesheet" href="/public/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="/public/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="/public/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="/public/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="/public/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="/public/plugins/toastr/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Sweetalert -->
    <link rel="stylesheet" href="/public/dist/css/sweetalert.css">
    <!-- dropzonejs -->
    <link rel="stylesheet" href="/public/plugins/dropzone/min/dropzone.min.css" />


    <!-- CodeMirror CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/material.min.css">

    <!-- CodeMirror JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
            </ul>
            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">


                <!-- Messages Dropdown Menu -->

                <!-- Notifications Dropdown Menu -->

                <li class="nav-item dropdown">
                <li class="dropdown user user-menu">
                    <a href="" class="dropdown-toggle" data-toggle="dropdown">
                        <img src="/public/dist/img/setting.png" class="user-image" alt="User Image">
                    </a>
                    <ul class="dropdown-menu">
                        <!-- User image -->
                        <li class="user-header">
                            <img src="/public/dist/img/logoPy.png" class="img-circle" alt="User Image">

                            <p>
                                <?php echo $_SESSION['user']['nombre'].' '.$_SESSION['user']['apellido']; ?> <br>
                                <?php echo $_SESSION['user']['rol']; ?> <br>
                                <small>© <?php echo $anio = date('Y'); ?></small>
                            </p>
                        </li>
                        <!-- Menu Footer-->

                        <li class="user-footer">
                            <div class="row">
                                <div class="col-12">
                                    <a href="/logout" class="btn btn-danger btn-flat btn-block"><i class="fa fa-power-off"></i> Salir</a>
                                </div>
                            </div>




                        </li>
                    </ul>
                </li>
                </li>


            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background: linear-gradient(to right, #65050c, #7b161e);">
            <!-- Brand Logo -->
            <a href="/home" class="brand-link">
                <img src="/public/dist/img/logoPy.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light"> <strong> SIS </strong></span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user panel (optional) -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="/public/dist/img/AdminLTELogo.png" class="img-circle elevation-2" alt="User Image">
                    </div>
                    <div class="info">
                        <a href="" class="d-block"> <?php echo $_SESSION['user']['nombre']. ' ' . $_SESSION['user']['apellido']; ?></a>
                    </div>
                </div>


                <!-- Sidebar Menu -->
                <?php include('menu.php'); ?>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>