<?php if (!defined('ADMS')) { exit('No autorizado'); }
$ed = $edit ?? null;
?>
<div class="row">
  <div class="col-lg-4 mb-4">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title"><?= $ed ? 'Editar empleado' : 'Nuevo empleado' ?></h5>
        <?php if (!empty($msg)): ?>
            <div class="alert alert-info py-2"><?= e($msg) ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" action="<?= e(base_url('employees')) ?>">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= e($ed['id'] ?? '') ?>">
          <div class="mb-2">
            <label class="form-label mb-0">PIN / ID en el reloj *</label>
            <input name="pin" class="form-control form-control-sm" required value="<?= e($ed['pin'] ?? '') ?>">
            <small class="text-muted">Debe coincidir con el ID enrolado en los relojes.</small>
          </div>
          <div class="row g-2">
            <div class="col"><input name="nombre" class="form-control form-control-sm" placeholder="Nombre" value="<?= e($ed['nombre'] ?? '') ?>"></div>
            <div class="col"><input name="apellido" class="form-control form-control-sm" placeholder="Apellido" value="<?= e($ed['apellido'] ?? '') ?>"></div>
          </div>
          <div class="row g-2 mt-1">
            <div class="col"><input name="dni" class="form-control form-control-sm" placeholder="DNI" value="<?= e($ed['dni'] ?? '') ?>"></div>
            <div class="col"><input name="sector" class="form-control form-control-sm" placeholder="Sector / Área" value="<?= e($ed['sector'] ?? '') ?>"></div>
          </div>
          <input name="cargo" class="form-control form-control-sm mt-2" placeholder="Cargo" value="<?= e($ed['cargo'] ?? '') ?>">
          <div class="mb-2 mt-2">
            <label class="form-label mb-0">Foto (JPG/PNG/WebP, máx 3 MB)</label>
            <input type="file" name="foto" accept="image/*" class="form-control form-control-sm">
            <?php if (!empty($ed['foto'])): ?>
              <img src="<?= e(base_url($ed['foto'])) ?>" alt="foto" class="rounded mt-2" style="height:64px">
            <?php endif; ?>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="activo" id="activo" <?= (!$ed || $ed['activo']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="activo">Activo</label>
          </div>
          <button class="btn btn-success btn-sm w-100"><?= $ed ? 'Guardar cambios' : 'Crear empleado' ?></button>
          <?php if ($ed): ?>
            <a href="<?= e(base_url('employees')) ?>" class="btn btn-outline-secondary btn-sm w-100 mt-2">Cancelar</a>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <h2 class="mb-3">Empleados <small class="text-muted fs-6">(<?= count($employees) ?>)</small></h2>
    <div class="table-responsive">
      <table class="table table-bordered table-striped bg-white align-middle">
        <thead class="table-dark">
          <tr><th>Foto</th><th>PIN</th><th>Apellido y nombre</th><th>DNI</th><th>Sector</th><th>Marcas</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($employees as $emp): ?>
          <tr class="<?= $emp['activo'] ? '' : 'table-secondary' ?>">
            <td>
              <?php if (!empty($emp['foto'])): ?>
                <img src="<?= e(base_url($emp['foto'])) ?>" class="rounded" style="height:40px;width:40px;object-fit:cover">
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td><code><?= e($emp['pin']) ?></code></td>
            <td><?= e(trim($emp['apellido'] . ' ' . $emp['nombre'])) ?><?= $emp['cargo'] ? '<br><small class="text-muted">' . e($emp['cargo']) . '</small>' : '' ?></td>
            <td><?= e($emp['dni']) ?></td>
            <td><?= e($emp['sector']) ?></td>
            <td><a href="<?= e(base_url('attendance?emp=' . urlencode($emp['pin']))) ?>"><?= e($emp['marcas']) ?></a></td>
            <td class="text-nowrap">
              <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('employees?edit=' . $emp['id'])) ?>">Editar</a>
              <a class="btn btn-sm btn-outline-danger"
                 href="<?= e(base_url('employees?delete=' . $emp['id'] . '&csrf=' . csrf_token())) ?>"
                 onclick="return confirm('¿Borrar este empleado? (no borra sus marcaciones)');">Borrar</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$employees): ?>
          <tr><td colspan="7" class="text-center text-muted">Todavía no cargaste empleados.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
