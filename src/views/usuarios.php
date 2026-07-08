<?php if (!defined('ADMS')) { exit('No autorizado'); }
$csrf = '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
$rolSelect = function ($sel, $roles) {
    $h = '<select name="rol" class="form-select form-select-sm">';
    foreach ($roles as $r) {
        $h .= '<option value="' . e($r) . '"' . ($sel === $r ? ' selected' : '') . '>' . e(rol_label($r)) . '</option>';
    }
    return $h . '</select>';
};
?>
<h2 class="mb-3">Usuarios del sistema</h2>
<?php if (!empty($msg)): ?><div class="alert alert-info"><?= e($msg) ?></div><?php endif; ?>

<div class="row">
  <div class="col-lg-4 mb-4">
    <div class="card"><div class="card-body">
      <h5 class="card-title">Nuevo usuario</h5>
      <form method="post">
        <?= $csrf ?><input type="hidden" name="acc" value="crear">
        <input name="usuario" class="form-control form-control-sm mb-2" placeholder="Usuario (para ingresar)" required>
        <input name="nombre" class="form-control form-control-sm mb-2" placeholder="Nombre y apellido">
        <input name="clave" type="text" class="form-control form-control-sm mb-2" placeholder="Clave" required>
        <label class="form-label mb-0 small">Rol</label>
        <?= $rolSelect('consulta', $roles) ?>
        <button class="btn btn-success btn-sm w-100 mt-2">Crear usuario</button>
      </form>
      <hr>
      <p class="small text-muted mb-0">
        <strong>Roles:</strong><br>
        · <strong>Administrador:</strong> acceso total, puede crear otros usuarios y administradores del reloj.<br>
        · <strong>Enrolador:</strong> gestiona empleados y huellas; <em>no</em> puede crear administradores del reloj.<br>
        · <strong>Consulta:</strong> solo ve asistencia y genera reportes.
      </p>
    </div></div>
  </div>

  <div class="col-lg-8">
    <div class="table-responsive">
      <table class="table table-bordered table-striped bg-white align-middle">
        <thead class="table-dark"><tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Estado</th><th>Cambiar clave</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <form method="post">
              <?= $csrf ?><input type="hidden" name="acc" value="editar"><input type="hidden" name="id" value="<?= e($u['id']) ?>">
              <td><code><?= e($u['usuario']) ?></code></td>
              <td><input name="nombre" class="form-control form-control-sm" value="<?= e($u['nombre']) ?>"></td>
              <td><?= $rolSelect($u['rol'], $roles) ?></td>
              <td><div class="form-check"><input class="form-check-input" type="checkbox" name="activo" <?= $u['activo'] ? 'checked' : '' ?>><label class="form-check-label small">Activo</label></div></td>
              <td><input name="clave" type="text" class="form-control form-control-sm" placeholder="(dejar vacío)"></td>
              <td class="text-nowrap">
                <button class="btn btn-sm btn-primary">Guardar</button>
            </form>
                <form method="post" class="d-inline" onsubmit="return confirm('Borrar el usuario <?= e($u['usuario']) ?>?');">
                  <?= $csrf ?><input type="hidden" name="acc" value="borrar"><input type="hidden" name="id" value="<?= e($u['id']) ?>">
                  <button class="btn btn-sm btn-outline-danger">Borrar</button>
                </form>
              </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$usuarios): ?><tr><td colspan="6" class="text-center text-muted">Sin usuarios. Ingresás con el admin de config.php (<code><?= e($confAdmin) ?></code>).</td></tr><?php endif; ?>
        </tbody>
      </table>
      <p class="small text-muted">El administrador definido en <code>config.php</code> (<code><?= e($confAdmin) ?></code>) siempre puede ingresar como respaldo, aunque no aparezca en esta lista.</p>
    </div>
  </div>
</div>
