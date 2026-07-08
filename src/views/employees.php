<?php if (!defined('ADMS')) { exit('No autorizado'); }
$ed = $edit ?? null;
$repSelect = function ($sel) use ($reparticiones) {
    $h = '<select name="reparticion_id" class="form-select form-select-sm">';
    $h .= '<option value="">— Sin dependencia —</option>';
    foreach ($reparticiones as $sec => $reps) {
        $h .= '<optgroup label="' . e($sec) . '">';
        foreach ($reps as $r) {
            $s = ((string) $sel === (string) $r['id']) ? ' selected' : '';
            $h .= '<option value="' . e($r['id']) . '"' . $s . '>' . e($r['nombre']) . '</option>';
        }
        $h .= '</optgroup>';
    }
    $h .= '</select>';
    return $h;
};
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
          </div>
          <div class="row g-2">
            <div class="col"><input name="nombre" class="form-control form-control-sm" placeholder="Nombre" value="<?= e($ed['nombre'] ?? '') ?>"></div>
            <div class="col"><input name="apellido" class="form-control form-control-sm" placeholder="Apellido" value="<?= e($ed['apellido'] ?? '') ?>"></div>
          </div>
          <input name="dni" class="form-control form-control-sm mt-2" placeholder="DNI" value="<?= e($ed['dni'] ?? '') ?>">
          <label class="form-label mb-0 mt-2">Dependencia</label>
          <?= $repSelect($ed['reparticion_id'] ?? '') ?>
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
        <hr>
        <form method="post" onsubmit="return confirm('Importar/actualizar empleados con los datos que el reloj ya envió?');">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="import_reloj" value="1">
          <button class="btn btn-outline-primary btn-sm w-100">Importar empleados del reloj</button>
          <small class="text-muted d-block mt-1">Crea los empleados que faltan (PIN + nombre) a partir de lo enviado por el reloj.</small>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <h2 class="mb-2">Empleados <small class="text-muted fs-6">(<?= count($employees) ?>)</small></h2>
    <form class="row g-2 mb-3" method="get">
      <div class="col-auto">
        <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Todos</option>
          <option value="activos" <?= ($fEstado ?? '') === 'activos' ? 'selected' : '' ?>>Activos</option>
          <option value="bajas" <?= ($fEstado ?? '') === 'bajas' ? 'selected' : '' ?>>Bajas</option>
        </select>
      </div>
      <div class="col-auto">
        <select name="rep" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Toda dependencia</option>
          <?php foreach ($reparticiones as $sec => $reps): ?>
            <optgroup label="<?= e($sec) ?>">
              <?php foreach ($reps as $r): ?>
                <option value="<?= e($r['id']) ?>" <?= ((string)($fRep ?? '') === (string)$r['id']) ? 'selected' : '' ?>><?= e($r['nombre']) ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
    <div class="table-responsive">
      <table class="table table-bordered table-striped bg-white align-middle">
        <thead class="table-dark">
          <tr><th>Foto</th><th>PIN</th><th>Apellido y nombre</th><th>Dependencia</th><th>Marcas</th><th>Estado</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($employees as $emp): ?>
          <tr class="<?= $emp['activo'] ? '' : 'table-secondary' ?>">
            <td>
              <?php if (!empty($emp['foto'])): ?>
                <img src="<?= e(base_url($emp['foto'])) ?>" class="rounded" style="height:40px;width:40px;object-fit:cover">
              <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </td>
            <td><code><?= e($emp['pin']) ?></code></td>
            <td><?= e(trim($emp['apellido'] . ' ' . $emp['nombre'])) ?><?= $emp['cargo'] ? '<br><small class="text-muted">' . e($emp['cargo']) . '</small>' : '' ?></td>
            <td>
              <?php if ($emp['reparticion']): ?>
                <?= e($emp['reparticion']) ?><br><small class="text-muted"><?= e($emp['secretaria']) ?></small>
              <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </td>
            <td><a href="<?= e(base_url('attendance?emp=' . urlencode($emp['pin']))) ?>"><?= e($emp['marcas']) ?></a></td>
            <td>
              <?php if ($emp['activo']): ?><span class="badge bg-success">Activo</span>
              <?php else: ?><span class="badge bg-secondary">Baja</span><?php endif; ?>
            </td>
            <td class="text-nowrap">
              <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('employees?edit=' . $emp['id'])) ?>">Editar</a>
              <a class="btn btn-sm btn-outline-<?= $emp['activo'] ? 'warning' : 'success' ?>"
                 href="<?= e(base_url('employees?toggle=' . $emp['id'] . '&csrf=' . csrf_token())) ?>">
                 <?= $emp['activo'] ? 'Dar de baja' : 'Reactivar' ?></a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$employees): ?>
          <tr><td colspan="7" class="text-center text-muted">Sin empleados con ese filtro.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
