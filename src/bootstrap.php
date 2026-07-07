<?php
/**
 * Arranque de la aplicación: carga config, zona horaria, base de datos y helpers.
 */

if (!defined('ADMS')) {
    define('ADMS', true);
}

define('BASE_PATH', dirname(__DIR__));

// Versión de la aplicación (se actualiza en cada cambio).
define('APP_VERSION', '2026.07.07-attlog2');

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

// --- Driver de base de datos en uso ('sqlite' o 'mysql') ---
function db_driver(): string
{
    global $config;
    return $config['db']['driver'] ?? 'sqlite';
}

/** Fecha/hora actual en formato SQL (independiente del motor). */
function now_sql(): string
{
    return date('Y-m-d H:i:s');
}

// --- Conexión a la base de datos (PDO) ---
function db(): PDO
{
    static $pdo = null;
    global $config;
    if ($pdo === null) {
        $c = $config['db'];
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            if (db_driver() === 'sqlite') {
                $path = $c['sqlite_path'];
                $dir  = dirname($path);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0750, true);
                }
                $isNew = !file_exists($path);
                $pdo = new PDO('sqlite:' . $path, null, null, $opts);
                $pdo->exec('PRAGMA foreign_keys = ON');
                $pdo->exec('PRAGMA journal_mode = WAL');
                if ($isNew) {
                    init_sqlite_schema($pdo);
                }
            } else {
                $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";
                $pdo = new PDO($dsn, $c['user'], $c['pass'], $opts);
                $pdo->exec("SET time_zone = '-03:00'");
            }
            ensure_extra_tables($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }
    return $pdo;
}

/** Crea las tablas en una base SQLite nueva. */
function init_sqlite_schema(PDO $pdo): void
{
    $sql = file_get_contents(BASE_PATH . '/database/schema.sqlite.sql');
    if ($sql !== false) {
        $pdo->exec($sql);
    }
}

/**
 * Migración liviana e idempotente: asegura tablas nuevas (empleados) en
 * bases ya existentes. Se ejecuta en cada conexión (CREATE TABLE IF NOT EXISTS).
 */
function ensure_extra_tables(PDO $pdo): void
{
    // Índice único para no duplicar marcaciones si el reloj reenvía.
    try {
        if (db_driver() === 'sqlite') {
            $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS attendances_unique
                        ON attendances (sn, employee_id, `timestamp`)');
        } else {
            $pdo->exec('ALTER TABLE attendances
                        ADD UNIQUE INDEX attendances_unique (sn, employee_id, `timestamp`)');
        }
    } catch (PDOException $e) {
        // Ya existe (MySQL no soporta IF NOT EXISTS en índices): ignorar.
    }

    if (db_driver() === 'sqlite') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS employees (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                pin        TEXT NOT NULL UNIQUE,
                nombre     TEXT,
                apellido   TEXT,
                dni        TEXT,
                sector     TEXT,
                cargo      TEXT,
                foto       TEXT,
                activo     INTEGER NOT NULL DEFAULT 1,
                created_at TEXT DEFAULT (datetime(\'now\',\'localtime\')),
                updated_at TEXT DEFAULT (datetime(\'now\',\'localtime\'))
            )'
        );
    } else {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS employees (
                id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                pin        VARCHAR(190) NOT NULL,
                nombre     VARCHAR(190) NULL,
                apellido   VARCHAR(190) NULL,
                dni        VARCHAR(60) NULL,
                sector     VARCHAR(190) NULL,
                cargo      VARCHAR(190) NULL,
                foto       VARCHAR(255) NULL,
                activo     TINYINT NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY employees_pin_unique (pin)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }
}

require __DIR__ . '/helpers.php';
require __DIR__ . '/commands.php';
