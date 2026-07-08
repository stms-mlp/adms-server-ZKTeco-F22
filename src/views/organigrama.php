<?php if (!defined('ADMS')) { exit('No autorizado'); } ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="mb-0">Organigrama</h2>
  <form method="post" onsubmit="return confirm('Importar/actualizar el organigrama desde el archivo CSV?');">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="importar" value="1">
    <button class="btn btn-primary btn-sm">Importar / actualizar organigrama</button>
  </form>
</div>

<?php if (!empty($msg)): ?>
  <div class="alert alert-info"><?= e($msg) ?></div>
<?php endif; ?>

<?php if (!$orga): ?>
  <div class="alert alert-warning">Todavía no hay organigrama cargado. Usá el botón «Importar / actualizar organigrama».</div>
<?php else: ?>
  <?php
  $porSec = [];
  foreach ($orga as $o) { $porSec[$o['secretaria']][] = $o; }
  ?>
  <div class="row">
    <?php foreach ($porSec as $sec => $reps): ?>
      <div class="col-md-6 mb-3">
        <div class="card h-100">
          <div class="card-header fw-bold"><?= e($sec) ?></div>
          <ul class="list-group list-group-flush">
            <?php foreach ($reps as $r): ?>
              <li class="list-group-item d-flex justify-content-between">
                <span><?= e($r['reparticion']) ?>
                  <?php if ($r['es_secretaria']): ?><span class="badge bg-info text-dark ms-1">sede</span><?php endif; ?>
                </span>
                <span class="badge bg-secondary"><?= e($r['empleados']) ?> emp.</span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
