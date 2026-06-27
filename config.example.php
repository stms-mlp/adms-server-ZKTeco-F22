<?php
/**
 * Configuración del servidor ADMS (Lago Puelo).
 *
 * Copiá este archivo como "config.php" y completá tus datos.
 * En hosting compartido (ej. donweb) cargá acá los datos de la base MySQL.
 *
 *   cp config.example.php config.php
 */

return [

    // --- Base de datos ---
    // driver: 'sqlite' (recomendado, sin servidor) o 'mysql'.
    'db' => [
        'driver' => 'sqlite',

        // --- Solo para SQLite ---
        // Archivo de la base. Está dentro de /data, protegido por .htaccess
        // para que NO pueda descargarse por la web. Se crea solo la 1ª vez.
        'sqlite_path' => __DIR__ . '/data/adms.sqlite',

        // --- Solo para MySQL/MariaDB ---
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'adms',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // --- Zona horaria ---
    // Zona horaria del servidor (PHP). Lago Puelo / Argentina = UTC-3.
    'timezone' => 'America/Argentina/Buenos_Aires',

    // Valor de "TimeZone" que se envía al reloj en el handshake.
    // El firmware de muchos ZKTeco lo interpreta en HORAS: para UTC-3 usá "-3".
    // Si tu firmware lo interpreta en MINUTOS, usá "-180".
    // Se envía tal cual está acá; dejalo en null para NO tocar el reloj.
    'device_timezone' => '-3',

    // --- Acceso al panel de administración ---
    // El protocolo de los relojes (/iclock/*) NO pide login; solo el panel web.
    // Generá el hash con:  php -r "echo password_hash('TU_CLAVE', PASSWORD_DEFAULT), PHP_EOL;"
    'admin' => [
        'user'      => 'admin',
        // hash de la clave "admin" (CAMBIALA por la tuya, ver README).
        'pass_hash' => '$2y$12$4187fcsDmnJMVQ2U8vTwuuKHW4zftw5rHXU03pF6.mWhvktjJ74Ai',
    ],

    // Mostrar errores en pantalla (poné false en producción).
    'debug' => false,
];
