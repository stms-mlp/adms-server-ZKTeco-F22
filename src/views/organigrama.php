<?php if (!defined('ADMS')) { exit('No autorizado'); }
$csrf = '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
$porSec = [];
foreach ($orga as $o) { $porSec[$o['secretaria']][] = $o; }
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="mb-0">Organigrama</h2>
  <?php if ($puedeEditar): ?>
  <form method="post" onsubmit="return confirm('Importar/actualizar el organigrama desde el archivo CSV?');">
    <?= $csrf ?><input type="hidden" name="acc" value="importar">
    <button class="btn btn-outline-primary btn-sm">Importar del CSV</button>
  </form>
  <?php endif; ?>
</div>

<?php if (!empty($msg)): ?>
  <div class="alert alert-info"><?= e($msg) ?></div>
<?php endif; ?>

<?php if ($puedeEditar): ?>
<div class="row g-2 mb-4">
  <div class="col-md-6">
    <form method="post" class="card card-body">
      <?= $csrf ?><input type="hidden" name="acc" value="add_sec">
      <label class="form-label mb-1 fw-bold">Nueva secretaría</label>
      <div class="input-group input-group-sm">
        <input name="nombre" class="form-control" placeholder="Nombre de la secretaría" required>
        <button class="btn btn-success">Agregar</button>
      </div>
    </form>
  </div>
  <div class="col-md-6">
    <form method="post" class="card card-body">
      <?= $csrf ?><input type="hidden" name="acc" value="add_rep">
      <label class="form-label mb-1 fw-bold">Nueva repartición</label>
      <div class="input-group input-group-sm">
        <select name="secretaria_id" class="form-select" required>
          <option value="">Secretaría…</option>
          <?php foreach ($secretarias as $s): ?><option value="<?= e($s['id']) ?>"><?= e($s['nombre']) ?></option><?php endforeach; ?>
        </select>
        <input name="nombre" class="form-control" placeholder="Repartición" required>
        <button class="btn btn-success">Agregar</button>
      </div>
      <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="es_secretaria" id="esrep"><label class="form-check-label small" for="esrep">Es la sede de la secretaría</label></div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if (!$orga): ?>
  <div class="alert alert-warning">Todavía no hay organigrama cargado.</div>
<?php else: ?>
  <div class="row">
    <?php foreach ($porSec as $sec => $reps): $secId = $reps[0]['sec_id']; ?>
      <div class="col-md-6 mb-3">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <?php if ($puedeEditar): ?>
              <form method="post" class="d-flex flex-grow-1 me-2">
                <?= $csrf ?><input type="hidden" name="acc" value="ren_sec"><input type="hidden" name="id" value="<?= e($secId) ?>">
                <input name="nombre" class="form-control form-control-sm fw-bold" value="<?= e($sec) ?>">
                <button class="btn btn-sm btn-outline-secondary ms-1" title="Renombrar">✓</button>
              </form>
              <form method="post" onsubmit="return confirm('Borrar la secretaría? (debe estar sin reparticiones)');">
                <?= $csrf ?><input type="hidden" name="acc" value="del_sec"><input type="hidden" name="id" value="<?= e($secId) ?>">
                <button class="btn btn-sm btn-outline-danger">🗑</button>
              </form>
            <?php else: ?>
              <span class="fw-bold"><?= e($sec) ?></span>
            <?php endif; ?>
          </div>
          <ul class="list-group list-group-flush">
            <?php foreach ($reps as $r): ?>
              <li class="list-group-item">
                <?php if ($puedeEditar): ?>
                  <form method="post" class="d-flex align-items-center gap-1">
                    <?= $csrf ?><input type="hidden" name="acc" value="ren_rep"><input type="hidden" name="id" value="<?= e($r['rep_id']) ?>">
                    <input name="nombre" class="form-control form-control-sm" value="<?= e($r['reparticion']) ?>">
                    <div class="form-check" title="Sede de la secretaría"><input class="form-check-input" type="checkbox" name="es_secretaria" <?= $r['es_secretaria'] ? 'checked' : '' ?>></div>
                    <button class="btn btn-sm btn-outline-secondary" title="Guardar">✓</button>
                    <span class="badge bg-secondary"><?= e($r['empleados']) ?></span>
                  </form>
                  <?php if (!$r['empleados']): ?>
                  <form method="post" class="mt-1" onsubmit="return confirm('Borrar esta repartición?');">
                    <?= $csrf ?><input type="hidden" name="acc" value="del_rep"><input type="hidden" name="id" value="<?= e($r['rep_id']) ?>">
                    <button class="btn btn-sm btn-link text-danger p-0">borrar</button>
                  </form>
                  <?php endif; ?>
                <?php else: ?>
                  <div class="d-flex justify-content-between">
                    <span><?= e($r['reparticion']) ?> <?php if ($r['es_secretaria']): ?><span class="badge bg-info text-dark">sede</span><?php endif; ?></span>
                    <span class="badge bg-secondary"><?= e($r['empleados']) ?> emp.</span>
                  </div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
