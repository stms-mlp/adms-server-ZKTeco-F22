<?php if (!defined('ADMS')) { exit('No autorizado'); }
$csrf = '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
$porSec = [];
foreach ($orga as $o) { $porSec[$o['secretaria']][] = $o; }
$tipoSelect = function ($sel) use ($tipos) {
    $h = '<select name="tipo" class="form-select form-select-sm">';
    foreach ($tipos as $t) { $h .= '<option' . ($sel === $t ? ' selected' : '') . '>' . e($t) . '</option>'; }
    return $h . '</select>';
};
// JSON de dependencias por secretaría para el selector de padre (cascada).
$depsJson = json_encode($depsPorSec, JSON_UNESCAPED_UNICODE);
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

<?php if (!empty($msg)): ?><div class="alert alert-info"><?= e($msg) ?></div><?php endif; ?>

<?php if ($puedeEditar): ?>
<div class="row g-2 mb-4">
  <div class="col-md-4">
    <form method="post" class="card card-body h-100">
      <?= $csrf ?><input type="hidden" name="acc" value="add_sec">
      <label class="form-label mb-1 fw-bold">Nueva secretaría</label>
      <div class="input-group input-group-sm">
        <input name="nombre" class="form-control" placeholder="Nombre" required>
        <button class="btn btn-success">Agregar</button>
      </div>
    </form>
  </div>
  <div class="col-md-8">
    <form method="post" class="card card-body h-100" id="formRep">
      <?= $csrf ?><input type="hidden" name="acc" value="add_rep">
      <label class="form-label mb-1 fw-bold">Nueva dependencia</label>
      <div class="row g-2">
        <div class="col-md-4">
          <select name="secretaria_id" id="secRep" class="form-select form-select-sm" required onchange="cargarPadres()">
            <option value="">Secretaría…</option>
            <?php foreach ($secretarias as $s): ?><option value="<?= e($s['id']) ?>"><?= e($s['nombre']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3"><?= $tipoSelect('Dirección') ?></div>
        <div class="col-md-5"><input name="nombre" class="form-control form-control-sm" placeholder="Nombre de la dependencia" required></div>
      </div>
      <div class="row g-2 mt-1">
        <div class="col-md-7">
          <select name="parent_id" id="parentRep" class="form-select form-select-sm">
            <option value="">— Sin dependencia padre (cuelga de la secretaría) —</option>
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-center">
          <div class="form-check"><input class="form-check-input" type="checkbox" name="es_secretaria" id="esrep"><label class="form-check-label small" for="esrep">Es sede</label></div>
        </div>
        <div class="col-md-2"><button class="btn btn-success btn-sm w-100">Agregar</button></div>
      </div>
    </form>
  </div>
</div>
<script>
var DEPS = <?= $depsJson ?: '{}' ?>;
function cargarPadres(sel, target) {
  var secId = (sel || document.getElementById('secRep').value);
  var t = target || document.getElementById('parentRep');
  if (!t) return;
  var actual = t.getAttribute('data-sel') || '';
  t.innerHTML = '<option value="">— Sin dependencia padre —</option>';
  (DEPS[secId] || []).forEach(function(d){
    var o = document.createElement('option'); o.value = d.id;
    o.textContent = d.nombre + ' (' + d.tipo + ')';
    if (String(actual) === String(d.id)) o.selected = true;
    t.appendChild(o);
  });
}
</script>
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
              <form method="post" onsubmit="return confirm('Borrar la secretaría? (sin dependencias)');">
                <?= $csrf ?><input type="hidden" name="acc" value="del_sec"><input type="hidden" name="id" value="<?= e($secId) ?>">
                <button class="btn btn-sm btn-outline-danger">🗑</button>
              </form>
            <?php else: ?><span class="fw-bold"><?= e($sec) ?></span><?php endif; ?>
          </div>
          <ul class="list-group list-group-flush">
            <?php foreach ($reps as $r): ?>
              <li class="list-group-item">
                <?php if ($puedeEditar): ?>
                  <form method="post" onsubmit="var p=this.querySelector('[name=parent_id]');">
                    <?= $csrf ?><input type="hidden" name="acc" value="ren_rep"><input type="hidden" name="id" value="<?= e($r['rep_id']) ?>">
                    <div class="d-flex align-items-center gap-1">
                      <input name="nombre" class="form-control form-control-sm" value="<?= e($r['reparticion']) ?>">
                      <span class="badge bg-secondary"><?= e($r['empleados']) ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-1 mt-1">
                      <?= $tipoSelect($r['tipo']) ?>
                      <select name="parent_id" class="form-select form-select-sm padreSel"
                              data-sel="<?= e($r['parent_id']) ?>" data-sec="<?= e($r['sec_id']) ?>"></select>
                      <button class="btn btn-sm btn-outline-secondary" title="Guardar">✓</button>
                    </div>
                    <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="es_secretaria" <?= $r['es_secretaria'] ? 'checked' : '' ?>><label class="form-check-label small">Es sede de la secretaría</label></div>
                  </form>
                  <?php if (!$r['empleados']): ?>
                  <form method="post" onsubmit="return confirm('Borrar esta dependencia?');">
                    <?= $csrf ?><input type="hidden" name="acc" value="del_rep"><input type="hidden" name="id" value="<?= e($r['rep_id']) ?>">
                    <button class="btn btn-sm btn-link text-danger p-0">borrar</button>
                  </form>
                  <?php endif; ?>
                <?php else: ?>
                  <div class="d-flex justify-content-between">
                    <span><?= e($r['reparticion']) ?> <span class="badge bg-light text-dark border"><?= e($r['tipo']) ?></span>
                      <?php if ($r['es_secretaria']): ?><span class="badge bg-info text-dark">sede</span><?php endif; ?></span>
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
  <?php if ($puedeEditar): ?>
  <script>
  document.querySelectorAll('select.padreSel').forEach(function(s){ cargarPadres(s.dataset.sec, s); });
  </script>
  <?php endif; ?>
<?php endif; ?>
