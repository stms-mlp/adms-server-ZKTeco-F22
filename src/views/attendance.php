<?php if (!defined('ADMS')) { exit('No autorizado'); } ?>
<h2 class="mb-3">Asistencia <small class="text-muted fs-6">(<?= e($total) ?> marcaciones)</small></h2>

<div class="table-responsive">
<table class="table table-bordered table-striped bg-white">
    <thead class="table-dark">
        <tr>
            <th>ID</th><th>Reloj (SN)</th><th>Empleado (PIN)</th><th>Fecha y hora</th>
            <th>St1</th><th>St2</th><th>St3</th><th>St4</th><th>St5</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['id']) ?></td>
            <td><code><?= e($r['sn']) ?></code></td>
            <td><?= e($r['employee_id']) ?></td>
            <td><?= e($r['timestamp']) ?></td>
            <td><?= e($r['status1']) ?></td>
            <td><?= e($r['status2']) ?></td>
            <td><?= e($r['status3']) ?></td>
            <td><?= e($r['status4']) ?></td>
            <td><?= e($r['status5']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
        <tr><td colspan="9" class="text-center text-muted">Sin marcaciones.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<?php if ($pages > 1): ?>
<nav>
  <ul class="pagination">
    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(base_url('attendance?page=' . ($page - 1))) ?>">Anterior</a>
    </li>
    <li class="page-item disabled"><span class="page-link">Página <?= e($page) ?> de <?= e($pages) ?></span></li>
    <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
        <a class="page-link" href="<?= e(base_url('attendance?page=' . ($page + 1))) ?>">Siguiente</a>
    </li>
  </ul>
</nav>
<?php endif; ?>
