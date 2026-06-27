<?php
/**
 * Arranque de la aplicación: carga config, zona horaria, base de datos y helpers.
 */

if (!defined('ADMS')) {
    define('ADMS', true);
}

define('BASE_PATH', dirname(__DIR__));

// --- Cargar configuración ---
$configFile = BASE_PATH . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    exit('Falta config.php. Copiá config.example.php a config.php y completá tus datos.');
}
$config = require $configFile;

// --- Errores / debug ---
if (!empty($config['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
}

// --- Zona horaria (UTC-3 para Lago Puelo) ---
date_default_timezone_set($config['timezone'] ?? 'America/Argentina/Buenos_Aires');

// --- Conexión a la base de datos (PDO) ---
function db(): PDO
{
    static $pdo = null;
    global $config;
    if ($pdo === null) {
        $c = $config['db'];
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";
        try {
            $pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("SET time_zone = '-03:00'");
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }
    return $pdo;
}

require __DIR__ . '/helpers.php';
require __DIR__ . '/commands.php';
