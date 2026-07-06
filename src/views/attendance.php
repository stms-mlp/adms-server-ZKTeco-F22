<?php if (!defined('ADMS')) { exit('No autorizado'); } ?>
<h2 class="mb-1">Asistencia <small class="text-muted fs-6">(<?= e($total) ?> marcaciones)</small></h2>
<?php if (!empty($emp)): ?>
    <p><span class="badge bg-primary">Filtrando por empleado PIN <?= e($emp) ?></span>
       <a href="<?= e(base_url('attendance')) ?>" class="ms-2">ver todas</a></p>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-bordered table-striped bg-white align-middle">
    <thead class="table-dark">
        <tr>
            <th></th><th>Empleado</th><th>Reloj (SN)</th><th>Fecha y hora</th>
            <th>Marca</th><th>Verificación</th><th>ID</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <?php $nom = trim(($r['apellido'] ?? '') . ' ' . ($r['nombre'] ?? '')); ?>
        <tr>
            <td>
                <?php if (!empty($r['foto'])): ?>
                    <img src="<?= e(base_url($r['foto'])) ?>" class="rounded" style="height:36px;width:36px;object-fit:cover">
                <?php endif; ?>
            </td>
            <td>
                <?php if ($nom !== ''): ?>
                    <?= e($nom) ?><br>
                    <small class="text-muted">PIN <?= e($r['employee_id']) ?><?= $r['sector'] ? ' · ' . e($r['sector']) : '' ?></small>
                <?php else: ?>
                    <span class="text-muted">PIN <?= e($r['employee_id']) ?></span>
                    <br><small class="text-warning">sin empleado asignado</small>
                <?php endif; ?>
            </td>
            <td><code><?= e($r['sn']) ?></code></td>
            <td><?= e($r['timestamp']) ?></td>
            <td><?= e(estado_marca($r['status1'])) ?></td>
            <td><?= e(modo_verificacion($r['status2'])) ?></td>
            <td><?= e($r['id']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
        <tr><td colspan="7" class="text-center text-muted">Sin marcaciones.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<?php if ($pages > 1): ?>
<?php $q = !empty($emp) ? '&emp=' . urlencode($emp) : ''; ?>
<nav>
  <ul class="pagination">
    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(base_url('attendance?page=' . ($page - 1) . $q)) ?>">Anterior</a>
    </li>
    <li class="page-item disabled"><span class="page-link">Página <?= e($page) ?> de <?= e($pages) ?></span></li>
    <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(base_url('attendance?page=' . ($page + 1) . $q)) ?>">Siguiente</a>
    </li>
  </ul>
</nav>
<?php endif; ?>
