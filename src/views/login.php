<?php if (!defined('ADMS')) { exit('No autorizado'); } ?>
<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card shadow-sm mt-5">
            <div class="card-body">
                <h4 class="card-title mb-3 text-center">ADMS · Lago Puelo</h4>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" action="<?= e(base_url('login')) ?>">
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="user" class="form-control" autofocus required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Clave</label>
                        <input type="password" name="pass" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100">Ingresar</button>
                </form>
            </div>
        </div>
    </div>
</div>
