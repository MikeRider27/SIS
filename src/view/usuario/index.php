<?php
include('/var/www/html/view/includes/header.php');

if (!in_array('usuarios', $_SESSION['permisos'] ?? [], true)) {
    echo '<div class="content-wrapper"><div class="alert alert-danger m-4">No tiene permisos para acceder a esta sección.</div></div>';
    include('/var/www/html/view/includes/footer.php');
    exit;
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-12">
          <h1>Listado de Usuarios</h1>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header py-3">
              <div class="float-left">
                <h3 class="card-title" style="display:inline-block;">Usuarios</h3>
              </div>
              <div class="float-right">
                <a href="/usuarios/create" class="btn btn-success"><i class="fas fa-plus"></i> Nuevo Usuario</a>
              </div>
            </div>
            <div class="card-body">
              <table id="listado" class="table table-bordered table-striped" style="width:100%">
                <thead>
                  <tr>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acción</th>
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

<!-- Modal de Permisos -->
<div class="modal fade" id="permisosModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Menús con acceso</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="permisosModalBody">
        <p class="text-muted">Cargando...</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarPermisos">Guardar</button>
      </div>
    </div>
  </div>
</div>

<?php include('/var/www/html/view/includes/footer.php'); ?>

<script>
$(function () {
  toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 5000 };

  var table = $('#listado').DataTable({
    responsive: true,
    lengthChange: false,
    autoWidth: false,
    ajax: {
      url: '/backend/services/usuarios/listUsuario.php',
      dataSrc: function (json) {
        if (json.status !== 'success') {
          toastr.error(json.message || 'No se pudo cargar la lista de usuarios');
          return [];
        }
        return json.usuarios;
      },
      error: function () {
        toastr.error('Error al comunicarse con el servidor.');
      }
    },
    columns: [
      { data: 'username' },
      { data: 'nombre' },
      { data: 'email' },
      { data: 'rol' },
      { data: 'estado' },
      {
        data: null,
        orderable: false,
        render: function (data, type, row) {
          var buttons = '<button class="btn btn-sm btn-primary btnPermisos" data-id="' + row.id + '" data-permisos=\'' + JSON.stringify(row.permisos || []) + '\' title="Menús con acceso"><i class="fas fa-list-check"></i> Permisos</button> ';
          buttons += '<button class="btn btn-sm btn-warning btnResetear" data-id="' + row.id + '" title="Resetear contraseña"><i class="fas fa-unlock"></i></button> ';
          if (row.is_active) {
            buttons += '<button class="btn btn-sm btn-danger btnDesactivar" data-id="' + row.id + '" title="Desactivar"><i class="fas fa-lock"></i></button>';
          } else {
            buttons += '<button class="btn btn-sm btn-success btnActivar" data-id="' + row.id + '" title="Activar"><i class="fas fa-unlock-alt"></i></button>';
          }
          return buttons;
        }
      }
    ],
    language: {
      emptyTable: 'No hay usuarios registrados',
      zeroRecords: 'No se encontraron resultados',
      search: 'Filtrar:',
      lengthMenu: 'Mostrar _MENU_ registros',
      info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
      paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
    }
  });

  function accionUsuario(id, accion, confirmMsg, successReload) {
    if (!confirm(confirmMsg)) return;
    $.ajax({
      url: '/backend/services/usuarios/updateUsuario.php',
      method: 'POST',
      data: { id: id, accion: accion },
      dataType: 'json',
      success: function (response) {
        if (response.status === 'success') {
          if (response.temp_password) {
            toastr.success(response.message + ' Contraseña temporal: ' + response.temp_password, '', { timeOut: 0, extendedTimeOut: 0 });
          } else {
            toastr.success(response.message);
          }
          if (successReload) table.ajax.reload(null, false);
        } else {
          toastr.error(response.message);
        }
      },
      error: function () {
        toastr.error('Error al comunicarse con el servidor.');
      }
    });
  }

  $('#listado tbody').on('click', '.btnDesactivar', function () {
    accionUsuario($(this).data('id'), 'desactivar', '¿Desactivar este usuario?', true);
  });

  $('#listado tbody').on('click', '.btnActivar', function () {
    accionUsuario($(this).data('id'), 'activar', '¿Activar este usuario?', true);
  });

  $('#listado tbody').on('click', '.btnResetear', function () {
    accionUsuario($(this).data('id'), 'reseteo', '¿Resetear la contraseña de este usuario?', false);
  });

  // --- Modal de permisos ---
  var permisosDisponibles = null;
  var usuarioIdActual = null;

  $('#listado tbody').on('click', '.btnPermisos', function () {
    usuarioIdActual = $(this).data('id');
    var permisosUsuario = $(this).data('permisos') || [];

    $('#permisosModalBody').html('<p class="text-muted">Cargando...</p>');
    $('#permisosModal').modal('show');

    function pintarCheckboxes() {
      var html = permisosDisponibles.map(function (p) {
        var checked = permisosUsuario.indexOf(p.code) !== -1 ? 'checked' : '';
        return '<div class="form-check">' +
          '<input type="checkbox" class="form-check-input permiso-check" id="modal_permiso_' + p.code + '" value="' + p.code + '" ' + checked + '>' +
          '<label class="form-check-label" for="modal_permiso_' + p.code + '">' + p.label + '</label>' +
          '</div>';
      }).join('');
      $('#permisosModalBody').html(html || '<p class="text-muted">No hay permisos configurados.</p>');
    }

    if (permisosDisponibles) {
      pintarCheckboxes();
    } else {
      $.getJSON('/backend/services/usuarios/listPermisos.php', function (json) {
        if (json.status === 'success') {
          permisosDisponibles = json.permisos;
          pintarCheckboxes();
        } else {
          $('#permisosModalBody').html('<p class="text-danger">' + (json.message || 'Error al cargar los permisos') + '</p>');
        }
      }).fail(function () {
        $('#permisosModalBody').html('<p class="text-danger">Error al comunicarse con el servidor.</p>');
      });
    }
  });

  $('#btnGuardarPermisos').on('click', function () {
    var seleccionados = $('.permiso-check:checked').map(function () { return this.value; }).get();

    $.ajax({
      url: '/backend/services/usuarios/updateUsuario.php',
      method: 'POST',
      data: { id: usuarioIdActual, accion: 'actualizar_permisos', permisos: seleccionados },
      dataType: 'json',
      success: function (response) {
        if (response.status === 'success') {
          toastr.success(response.message);
          $('#permisosModal').modal('hide');
          table.ajax.reload(null, false);
        } else {
          toastr.error(response.message);
        }
      },
      error: function () {
        toastr.error('Error al comunicarse con el servidor.');
      }
    });
  });
});
</script>
