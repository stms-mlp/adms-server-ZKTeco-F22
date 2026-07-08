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

/** ¿Hay un usuario logueado? */
function is_logged_in(): bool
{
    start_session();
    return !empty($_SESSION['admin']);
}

/** Rol del usuario actual: 'admin' | 'enrolador' | 'consulta'. */
function current_rol(): string
{
    start_session();
    return $_SESSION['rol'] ?? 'admin';
}

/** ¿El usuario actual tiene alguno de estos roles? */
function has_rol($roles): bool
{
    return in_array(current_rol(), (array) $roles, true);
}

/** Exige login y uno de los roles indicados; si no, 403. */
function require_rol($roles): void
{
    require_login();
    if (!has_rol($roles)) {
        http_response_code(403);
        view('logs', ['rows' => [], 'titulo' => 'Acceso denegado (403)'], '403');
        exit;
    }
}

/** Etiqueta legible de un rol. */
function rol_label(string $rol): string
{
    return [
        'admin'     => 'Administrador del sistema',
        'enrolador' => 'Enrolador',
        'consulta'  => 'Consulta (solo lectura)',
    ][$rol] ?? $rol;
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

/**
 * Mapa de dedos según la numeración FID de ZKTeco (0-9).
 * Convención estándar: del meñique izquierdo (0) al meñique derecho (9).
 */
function dedos(): array
{
    return [
        0 => 'Meñique izquierdo',
        1 => 'Anular izquierdo',
        2 => 'Mayor (medio) izquierdo',
        3 => 'Índice izquierdo',
        4 => 'Pulgar izquierdo',
        5 => 'Pulgar derecho',
        6 => 'Índice derecho',
        7 => 'Mayor (medio) derecho',
        8 => 'Anular derecho',
        9 => 'Meñique derecho',
    ];
}

/** Nombre del dedo a partir del FID. */
function dedo_nombre($fid): string
{
    $d = dedos();
    return $d[(int) $fid] ?? ('Dedo ' . $fid);
}

/**
 * Normaliza un nombre para enviarlo al reloj: transilera acentos y ñ,
 * quita caracteres que el firmware no maneja bien y colapsa espacios.
 */
function normalizar_nombre_dispositivo(string $s): string
{
    $s = trim($s);
    $map = [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u',
        'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U',
        'ñ'=>'n','Ñ'=>'N','à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u',
        'ç'=>'c','Ç'=>'C','º'=>'','ª'=>'','´'=>'','`'=>'',
    ];
    $s = strtr($s, $map);
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) { $s = $t; }
    }
    $s = preg_replace('/[^A-Za-z0-9 .\-]/', '', $s); // solo caracteres seguros
    $s = preg_replace('/\s+/', ' ', trim($s));
    return $s;
}

/** Reparticiones agrupadas por secretaría (para <optgroup>). */
function reparticiones_agrupadas(): array
{
    $out = [];
    $rows = db()->query(
        'SELECT r.id, r.nombre AS rep, s.nombre AS sec
         FROM reparticiones r JOIN secretarias s ON s.id = r.secretaria_id
         ORDER BY s.nombre, r.es_secretaria DESC, r.nombre'
    )->fetchAll();
    foreach ($rows as $r) {
        $out[$r['sec']][] = ['id' => $r['id'], 'nombre' => $r['rep']];
    }
    return $out;
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
