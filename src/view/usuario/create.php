<?php
include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

if (!in_array('usuarios', $_SESSION['permisos'] ?? [], true)) {
    echo '<div class="content-wrapper"><div class="alert alert-danger m-4">No tiene permisos para acceder a esta sección.</div></div>';
    include('/var/www/html/view/includes/footer.php');
    exit;
}

$dbconn = getConnection();
$roles = $dbconn->query("SELECT id, name, description FROM roles WHERE is_active = TRUE ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$permisosDisponibles = $dbconn->query("SELECT code, label FROM permissions ORDER BY label")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Creación de Usuario</h1>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-8">
          <form id="formUsuario">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Datos del Usuario</h3>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-lg-6 mb-3">
                    <label class="required">Nombre de Usuario</label>
                    <input type="text" class="form-control" name="username" id="username" required>
                  </div>
                  <div class="col-lg-6 mb-3">
                    <label class="required">Correo Electrónico</label>
                    <input type="email" class="form-control" name="email" id="email" required>
                  </div>
                </div>
                <div class="row">
                  <div class="col-lg-6 mb-3">
                    <label class="required">Nombre</label>
                    <input type="text" class="form-control" name="first_name" id="first_name" required>
                  </div>
                  <div class="col-lg-6 mb-3">
                    <label class="required">Apellido</label>
                    <input type="text" class="form-control" name="last_name" id="last_name" required>
                  </div>
                </div>
                <div class="row">
                  <div class="col-lg-6 mb-3">
                    <label class="required">Contraseña</label>
                    <input type="password" class="form-control" name="password" id="password" minlength="8" required>
                    <small class="text-muted">Mínimo 8 caracteres.</small>
                  </div>
                  <div class="col-lg-6 mb-3">
                    <label class="required">Rol</label>
                    <select class="form-control" name="role_id" id="role_id" required>
                      <option value="">Seleccione</option>
                      <?php foreach ($roles as $rol): ?>
                        <option value="<?= (int)$rol['id'] ?>"><?= htmlspecialchars($rol['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="row">
                  <div class="col-12">
                    <label>Menús a los que tendrá acceso</label>
                    <div class="border rounded p-3">
                      <?php foreach ($permisosDisponibles as $permiso): ?>
                        <div class="form-check">
                          <input type="checkbox" class="form-check-input" name="permisos[]"
                                 id="permiso_<?= htmlspecialchars($permiso['code']) ?>"
                                 value="<?= htmlspecialchars($permiso['code']) ?>">
                          <label class="form-check-label" for="permiso_<?= htmlspecialchars($permiso['code']) ?>">
                            <?= htmlspecialchars($permiso['label']) ?>
                          </label>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <a href="/usuarios/list" class="btn btn-warning">Cerrar</a>
                <button id="guardar" type="submit" class="btn btn-success">Guardar</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<style>
.required::after { content: " *"; color: #dc3545; }
</style>

<?php include('/var/www/html/view/includes/footer.php'); ?>

<script>
$(function () {
  toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 5000 };

  $('#formUsuario').submit(function (e) {
    e.preventDefault();
    $('#guardar').attr('disabled', true);

    $.ajax({
      url: '/backend/services/usuarios/createUsuario.php',
      method: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      success: function (response) {
        if (response.status === 'success') {
          toastr.success(response.message);
          setTimeout(function () { window.location.href = '/usuarios/list'; }, 1500);
        } else {
          toastr.error(response.message);
          $('#guardar').removeAttr('disabled');
        }
      },
      error: function () {
        toastr.error('Ocurrió un error al comunicarse con el servidor.');
        $('#guardar').removeAttr('disabled');
      }
    });
  });
});
</script>
