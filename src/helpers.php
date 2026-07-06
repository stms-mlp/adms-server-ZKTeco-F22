<?php
/**
 * Funciones de apoyo: escape HTML, sesión, autenticación del panel y vistas.
 */

if (!defined('ADMS')) { exit('No autorizado'); }

/** Escapar para HTML. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Iniciar sesión una sola vez. */
function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/** ¿Hay un administrador logueado? */
function is_logged_in(): bool
{
    start_session();
    return !empty($_SESSION['admin']);
}

/** Exigir login; si no, redirige a /login. */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login');
    }
}

/** Redirección relativa a la base de la app. */
function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

/** URL absoluta respetando el subdirectorio donde está instalada la app. */
function base_url(string $path = ''): string
{
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if ($base === '' || $base === '.') {
        $base = '';
    }
    return $base . '/' . ltrim($path, '/');
}

/** Renderizar una vista dentro del layout. */
function view(string $name, array $data = [], string $title = 'ADMS'): void
{
    extract($data);
    ob_start();
    require BASE_PATH . '/src/views/' . $name . '.php';
    $content = ob_get_clean();
    require BASE_PATH . '/src/views/layout.php';
}

/** Token CSRF simple para los formularios del panel. */
function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): bool
{
    start_session();
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
}

function csrf_check_get(): bool
{
    start_session();
    return isset($_GET['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_GET['csrf']);
}

/** Etiqueta legible del estado de marca (st1). Depende del firmware. */
function estado_marca($v): string
{
    $map = [
        0 => 'Entrada', 1 => 'Salida',
        2 => 'Descanso (salida)', 3 => 'Descanso (entrada)',
        4 => 'Hora extra (entrada)', 5 => 'Hora extra (salida)',
    ];
    return $map[(int) $v] ?? (string) $v;
}

/** Etiqueta legible del modo de verificación (st2). Depende del firmware. */
function modo_verificacion($v): string
{
    if ($v === null || $v === '') { return ''; }
    $map = [1 => 'Huella', 3 => 'Contraseña', 4 => 'Tarjeta', 15 => 'Rostro'];
    return $map[(int) $v] ?? (string) $v;
}

/**
 * Guarda la foto subida de un empleado y devuelve la ruta relativa, o null.
 * Valida que sea una imagen real y limita el tamaño.
 */
function guardar_foto_empleado(string $pin): ?string
{
    if (empty($_FILES['foto']) || ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $tmp  = $_FILES['foto']['tmp_name'];
    $info = @getimagesize($tmp);
    if ($info === false) {
        return null; // no es una imagen
    }
    if (($_FILES['foto']['size'] ?? 0) > 3 * 1024 * 1024) {
        return null; // máx 3 MB
    }
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$info['mime']] ?? null;
    if ($ext === null) {
        return null;
    }
    $dir = BASE_PATH . '/uploads';
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
    $safePin = preg_replace('/[^A-Za-z0-9_-]/', '', $pin);
    $name = 'emp_' . $safePin . '_' . time() . '.' . $ext;
    if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
        return null;
    }
    return 'uploads/' . $name;
}
