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
