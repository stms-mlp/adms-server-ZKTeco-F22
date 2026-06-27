<?php if (!defined('ADMS')) { exit('No autorizado'); } ?>
<h2 class="mb-3">Relojes conectados</h2>
<p class="text-muted">Los relojes se registran solos al conectarse. Podés completar el nombre y la ubicación.</p>

<div class="table-responsive">
<table class="table table-bordered table-striped bg-white align-middle">
    <thead class="table-dark">
        <tr>
            <th>ID</th><th>N° de serie (SN)</th><th>Nombre</th><th>Ubicación</th>
            <th>Último contacto</th><th>Estado</th><th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($devices as $d): ?>
        <?php $online = $d['online'] && (strtotime($d['online']) > time() - 300); ?>
        <form method="post" action="<?= e(base_url('devices')) ?>">
        <tr>
            <td><?= e($d['id']) ?></td>
            <td><code><?= e($d['no_sn']) ?></code></td>
            <td><input class="form-control form-control-sm" name="nama" value="<?= e($d['nama']) ?>"></td>
            <td><input class="form-control form-control-sm" name="lokasi" value="<?= e($d['lokasi']) ?>"></td>
            <td><?= e($d['online']) ?></td>
            <td>
                <?php if ($online): ?>
                    <span class="badge bg-success">En línea</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Sin contacto</span>
                <?php endif; ?>
            </td>
            <td>
                <input type="hidden" name="id" value="<?= e($d['id']) ?>">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button class="btn btn-sm btn-primary">Guardar</button>
            </td>
        </tr>
        </form>
    <?php endforeach; ?>
    <?php if (!$devices): ?>
        <tr><td colspan="7" class="text-center text-muted">Todavía no se conectó ningún reloj.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
