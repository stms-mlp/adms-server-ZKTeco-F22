<?php if (!defined('ADMS')) { exit('No autorizado'); } ?>
<h2 class="mb-3">Reportes de asistencia</h2>

<form class="card card-body mb-4" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-2">
      <label class="form-label mb-0">Desde</label>
      <input type="date" name="desde" class="form-control form-control-sm" value="<?= e($desde) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label mb-0">Hasta</label>
      <input type="date" name="hasta" class="form-control form-control-sm" value="<?= e($hasta) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label mb-0">Filtrar por</label>
      <select name="tipo" class="form-select form-select-sm" id="tipo" onchange="document.querySelectorAll('[data-val]').forEach(x=>x.style.display='none');var t=document.getElementById('val-'+this.value);if(t)t.style.display='';">
        <option value="" <?= $tipo === '' ? 'selected' : '' ?>>Todos</option>
        <option value="emp" <?= $tipo === 'emp' ? 'selected' : '' ?>>Empleado</option>
        <option value="rep" <?= $tipo === 'rep' ? 'selected' : '' ?>>Repartición</option>
        <option value="sec" <?= $tipo === 'sec' ? 'selected' : '' ?>>Secretaría</option>
      </select>
    </div>
    <div class="col-md-4">
      <div data-val id="val-emp" style="display:<?= $tipo === 'emp' ? '' : 'none' ?>">
        <label class="form-label mb-0">Empleado</label>
        <select name="val" class="form-select form-select-sm">
          <option value="">—</option>
          <?php foreach ($empleados as $em): ?>
            <option value="<?= e($em['pin']) ?>" <?= ($tipo==='emp' && (string)$val===(string)$em['pin'])?'selected':'' ?>>
              <?= e(trim($em['apellido'].' '.$em['nombre'])) ?> (<?= e($em['pin']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div data-val id="val-rep" style="display:<?= $tipo === 'rep' ? '' : 'none' ?>">
        <label class="form-label mb-0">Repartición</label>
        <select name="val" class="form-select form-select-sm">
          <option value="">—</option>
          <?php foreach ($reparticiones as $sec => $reps): ?>
            <optgroup label="<?= e($sec) ?>">
              <?php foreach ($reps as $r): ?>
                <option value="<?= e($r['id']) ?>" <?= ($tipo==='rep' && (string)$val===(string)$r['id'])?'selected':'' ?>><?= e($r['nombre']) ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </div>
      <div data-val id="val-sec" style="display:<?= $tipo === 'sec' ? '' : 'none' ?>">
        <label class="form-label mb-0">Secretaría</label>
        <select name="val" class="form-select form-select-sm">
          <option value="">—</option>
          <?php foreach ($secretarias as $s): ?>
            <option value="<?= e($s['id']) ?>" <?= ($tipo==='sec' && (string)$val===(string)$s['id'])?'selected':'' ?>><?= e($s['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary btn-sm w-100">Ver reporte</button>
    </div>
  </div>
</form>

<?php if ($rows): ?>
  <div class="d-flex justify-content-between align-items-center mb-2">
    <span class="text-muted"><?= count($rows) ?> filas (empleado × día)</span>
    <?php
    $qs = http_build_query(['desde'=>$desde,'hasta'=>$hasta,'tipo'=>$tipo,'val'=>$val,'export'=>'csv']);
    ?>
    <a class="btn btn-outline-success btn-sm" href="<?= e(base_url('reportes?' . $qs)) ?>">Exportar a Excel (CSV)</a>
  </div>
  <div class="table-responsive">
    <table class="table table-bordered table-striped bg-white">
      <thead class="table-dark">
        <tr><th>Fecha</th><th>Empleado</th><th>Secretaría / Repartición</th><th>Entrada</th><th>Salida</th><th>Marcas</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <?php $nom = trim(($r['apellido'] ?? '') . ' ' . ($r['nombre'] ?? '')); ?>
        <tr>
          <td><?= e($r['dia']) ?></td>
          <td><?= $nom !== '' ? e($nom) : '<span class="text-muted">PIN '.e($r['pin']).'</span>' ?></td>
          <td><?= e($r['secretaria']) ?><?= $r['reparticion'] ? '<br><small class="text-muted">'.e($r['reparticion']).'</small>' : '' ?></td>
          <td><?= e(substr($r['entrada'], 11, 5)) ?></td>
          <td><?= e($r['marcas'] > 1 ? substr($r['salida'], 11, 5) : '—') ?></td>
          <td><?= e($r['marcas']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div class="alert alert-secondary">Sin marcaciones para ese filtro y rango de fechas.</div>
<?php endif; ?>
