<?php if (!defined('ADMS')) { exit('No autorizado'); } ?>
<h2 class="mb-3"><?= e($titulo) ?></h2>

<div class="table-responsive">
<table class="table table-bordered table-sm bg-white">
    <thead class="table-dark"><tr><th>ID</th><th>URL</th><th>Data</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['id']) ?></td>
            <td style="max-width:300px;word-break:break-all;"><small><?= e($r['url']) ?></small></td>
            <td style="max-width:600px;word-break:break-all;"><small><?= e($r['data']) ?></small></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
        <tr><td colspan="3" class="text-center text-muted">Sin registros.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
