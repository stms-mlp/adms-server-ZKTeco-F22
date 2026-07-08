<?php if (!defined('ADMS')) { exit('No autorizado'); }
/** Pinta el <select> de relojes (reutilizado en cada formulario). */
$snSelect = function () use ($devices) {
    $h = '<select name="sn" class="form-select form-select-sm mb-2" required>';
    $h .= '<option value="">— Elegí un reloj —</option>';
    foreach ($devices as $d) {
        $label = $d['no_sn'] . ($d['nama'] ? ' (' . $d['nama'] . ')' : '');
        $h .= '<option value="' . e($d['no_sn']) . '">' . e($label) . '</option>';
    }
    $h .= '</select>';
    return $h;
};
$csrf = '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
$dedoSelect = function ($name = 'fid') {
    $h = '<select name="' . $name . '" class="form-select form-select-sm">';
    foreach (dedos() as $n => $nombre) {
        $h .= '<option value="' . $n . '"' . ($n === 5 ? ' selected' : '') . '>' . e($n . ' · ' . $nombre) . '</option>';
    }
    $h .= '</select>';
    return $h;
};
?>
<h2 class="mb-1">Panel de pruebas</h2>
<p class="text-muted">
    Estos comandos se <strong>encolan</strong> y el reloj los ejecuta la próxima vez que sondea
    al servidor (cada pocos segundos). El resultado aparece abajo, en el historial.
</p>

<?php if (!empty($msg)): ?>
    <div class="alert alert-info"><?= e($msg) ?></div>
<?php endif; ?>

<?php if (!$devices): ?>
    <div class="alert alert-warning">No hay relojes registrados todavía. Conectá un reloj primero.</div>
<?php endif; ?>

<div class="row g-3">

    <!-- Información del reloj -->
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Información del reloj</h6>
            <form method="post"><?= $csrf ?><?= $snSelect() ?>
                <input type="hidden" name="action" value="info">
                <button class="btn btn-sm btn-outline-primary w-100">Consultar info</button>
            </form>
        </div></div>
    </div>

    <!-- Pedir usuarios -->
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Pedir usuarios</h6>
            <form method="post"><?= $csrf ?><?= $snSelect() ?>
                <input type="hidden" name="action" value="query_users">
                <button class="btn btn-sm btn-outline-primary w-100">Solicitar usuarios al reloj</button>
            </form>
        </div></div>
    </div>

    <!-- Reiniciar -->
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Reiniciar reloj</h6>
            <form method="post"><?= $csrf ?><?= $snSelect() ?>
                <input type="hidden" name="action" value="reboot">
                <button class="btn btn-sm btn-outline-warning w-100">Reiniciar</button>
            </form>
        </div></div>
    </div>

    <!-- Alta / edición de usuario -->
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Alta / edición de usuario</h6>
            <form method="post"><?= $csrf ?><?= $snSelect() ?>
                <input type="hidden" name="action" value="create_user">
                <div class="row g-2">
                    <div class="col"><input name="pin" class="form-control form-control-sm" placeholder="PIN / ID" required></div>
                    <div class="col"><input name="name" class="form-control form-control-sm" placeholder="Nombre"></div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="col"><input name="card" class="form-control form-control-sm" placeholder="N° tarjeta (opcional)"></div>
                    <div class="col">
                        <select name="privilege" class="form-select form-select-sm">
                            <option value="0">Usuario</option>
                            <option value="14">Administrador</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-sm btn-success w-100 mt-2">Crear / actualizar usuario</button>
            </form>
        </div></div>
    </div>

    <!-- Borrar usuario -->
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Borrar usuario</h6>
            <form method="post"><?= $csrf ?><?= $snSelect() ?>
                <input type="hidden" name="action" value="delete_user">
                <input name="pin" class="form-control form-control-sm mb-2" placeholder="PIN / ID a borrar" required>
                <button class="btn btn-sm btn-danger w-100">Borrar usuario</button>
            </form>
        </div></div>
    </div>

    <!-- Enrolar huella -->
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Enrolar huella (en el reloj)</h6>
            <p class="small text-muted mb-2">El usuario debe apoyar el dedo en el reloj cuando reciba el comando.</p>
            <form method="post"><?= $csrf ?><?= $snSelect() ?>
                <input type="hidden" name="action" value="enroll_fp">
                <input name="pin" class="form-control form-control-sm" placeholder="PIN / ID" required>
                <label class="form-label mb-0 mt-2 small">Dedo</label>
                <?= $dedoSelect() ?>
                <button class="btn btn-sm btn-primary w-100 mt-2">Iniciar enrolamiento</button>
            </form>
        </div></div>
    </div>

    <!-- Cargar template de huella -->
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Cargar huella (template) a otro reloj</h6>
            <p class="small text-muted mb-2">Para replicar una huella ya capturada en otro reloj.</p>
            <form method="post"><?= $csrf ?><?= $snSelect() ?>
                <input type="hidden" name="action" value="push_fp">
                <div class="row g-2">
                    <div class="col-5"><input name="pin" class="form-control form-control-sm" placeholder="PIN / ID" required></div>
                    <div class="col-4"><?= $dedoSelect() ?></div>
                    <div class="col-3"><input name="size" class="form-control form-control-sm" placeholder="Size"></div>
                </div>
                <textarea name="tmp" class="form-control form-control-sm mt-2" rows="2" placeholder="TMP (template base64)"></textarea>
                <button class="btn btn-sm btn-primary w-100 mt-2">Cargar template</button>
            </form>
        </div></div>
    </div>

    <!-- Abrir cerradura -->
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Abrir cerradura</h6>
            <form method="post"><?= $csrf ?><?= $snSelect() ?>
                <input type="hidden" name="action" value="unlock">
                <input name="seconds" class="form-control form-control-sm mb-2" value="5" placeholder="Segundos">
                <button class="btn btn-sm btn-outline-secondary w-100">Abrir</button>
            </form>
        </div></div>
    </div>

    <!-- Borrar marcaciones -->
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Borrar marcaciones del reloj</h6>
            <form method="post" onsubmit="return confirm('¿Borrar las marcaciones guardadas en el reloj?');">
                <?= $csrf ?><?= $snSelect() ?>
                <input type="hidden" name="action" value="clear_attlog">
                <button class="btn btn-sm btn-outline-danger w-100">Borrar marcaciones</button>
            </form>
        </div></div>
    </div>

    <!-- Borrar todo -->
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <h6 class="card-title">Borrar TODOS los datos</h6>
            <form method="post" onsubmit="return confirm('¡CUIDADO! Borra usuarios, huellas y marcaciones del reloj. ¿Continuar?');">
                <?= $csrf ?><?= $snSelect() ?>
                <input type="hidden" name="action" value="clear_data">
                <button class="btn btn-sm btn-danger w-100">Borrar todo</button>
            </form>
        </div></div>
    </div>

    <!-- Comando manual -->
    <div class="col-12">
        <div class="card"><div class="card-body">
            <h6 class="card-title">Comando manual (avanzado)</h6>
            <p class="small text-muted mb-2">Se envía tal cual al reloj (sin el prefijo <code>C:id:</code>). Usá TAB real si el comando lo requiere.</p>
            <form method="post">
                <?= $csrf ?>
                <div class="row g-2">
                    <div class="col-md-3"><?= $snSelect() ?></div>
                    <div class="col-md-7"><input name="raw" class="form-control form-control-sm" placeholder="Ej: REBOOT"></div>
                    <div class="col-md-2">
                        <input type="hidden" name="action" value="raw">
                        <button class="btn btn-sm btn-dark w-100">Enviar</button>
                    </div>
                </div>
            </form>
        </div></div>
    </div>
</div>

<h4 class="mt-4">Historial de comandos</h4>
<div class="table-responsive">
<table class="table table-sm table-bordered bg-white">
    <thead class="table-dark">
        <tr><th>#</th><th>Reloj</th><th>Descripción</th><th>Estado</th><th>Cód.</th><th>Encolado</th><th>Completado</th></tr>
    </thead>
    <tbody>
    <?php foreach ($cmds as $c): ?>
        <?php
        $badge = ['pending' => 'secondary', 'sent' => 'info', 'done' => 'success', 'error' => 'danger'][$c['status']] ?? 'secondary';
        ?>
        <tr>
            <td><?= e($c['id']) ?></td>
            <td><code><?= e($c['sn']) ?></code></td>
            <td><?= e($c['label']) ?><br><small class="text-muted"><?= e(mb_strimwidth($c['command'], 0, 80, '…')) ?></small></td>
            <td><span class="badge bg-<?= $badge ?>"><?= e($c['status']) ?></span></td>
            <td><?= e($c['return_code']) ?></td>
            <td><small><?= e($c['created_at']) ?></small></td>
            <td><small><?= e($c['completed_at']) ?></small></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$cmds): ?>
        <tr><td colspan="7" class="text-center text-muted">Sin comandos todavía.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
