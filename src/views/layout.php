<?php if (!defined('ADMS')) { exit('No autorizado'); } ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'ADMS') ?> · ADMS Lago Puelo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php if (is_logged_in()): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= e(base_url('devices')) ?>">ADMS · Lago Puelo</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= e(base_url('devices')) ?>">Relojes</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(base_url('attendance')) ?>">Asistencia</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(base_url('panel')) ?>">Panel de pruebas</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(base_url('device-log')) ?>">Log dispositivos</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(base_url('finger-log')) ?>">Finger log</a></li>
            </ul>
            <a class="btn btn-outline-light btn-sm" href="<?= e(base_url('logout')) ?>">Salir</a>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="container my-4">
    <?= $content ?>
</main>

<footer class="text-center text-muted small py-4">
    ADMS ZKTeco · Municipalidad de Lago Puelo
</footer>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
