<?php
/**
 * Protocolo Push de ZKTeco (endpoints /iclock/*).
 *
 * Estos endpoints NO requieren login: los usan los relojes.
 * Siempre responden en texto plano, como espera el firmware.
 */

if (!defined('ADMS')) { exit('No autorizado'); }

function text_response(string $body): void
{
    header('Content-Type: text/plain; charset=utf-8');
    echo $body;
    exit;
}

/**
 * Handshake inicial:  GET /iclock/cdata?SN=...&options=all...
 * Registra el reloj y devuelve la configuración (incluida la zona horaria).
 */
function iclock_handshake(): void
{
    global $config;
    $sn = $_GET['SN'] ?? '';

    // Log crudo
    $stmt = db()->prepare('INSERT INTO device_log (url, data, sn, `option`) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        json_encode($_GET),
        file_get_contents('php://input'),
        $sn,
        $_GET['options'] ?? null,
    ]);

    // Registrar/actualizar el reloj (UPSERT compatible con sqlite y mysql)
    $now = now_sql();
    if (db_driver() === 'sqlite') {
        $up = db()->prepare(
            'INSERT INTO devices (no_sn, online, updated_at) VALUES (?, ?, ?)
             ON CONFLICT(no_sn) DO UPDATE SET online = excluded.online, updated_at = excluded.updated_at'
        );
        $up->execute([$sn, $now, $now]);
    } else {
        $up = db()->prepare(
            'INSERT INTO devices (no_sn, online) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE online = VALUES(online)'
        );
        $up->execute([$sn, $now]);
    }

    // Línea de zona horaria (solo si está configurada). UTC-3 => "-3".
    $tzLine = '';
    if (isset($config['device_timezone']) && $config['device_timezone'] !== null && $config['device_timezone'] !== '') {
        $tzLine = 'TimeZone=' . $config['device_timezone'] . "\r\n";
    }

    // Stamps por tabla. "None" le indica al reloj que envíe TODOS los
    // registros pendientes (asistencia y operaciones); desde ahí sigue en
    // tiempo real. Con un número alto (p. ej. 9999) el firmware nuevo cree
    // que ya está sincronizado y no envía nada.
    // ATTLOGStamp=None -> el reloj envía todas las marcaciones (protegidas
    // contra duplicados por índice único). OPERLOG/ATTPHOTO en 9999 para que
    // NO re-vuelque usuarios y huellas en cada reconexión (tráfico pesado).
    $r = "GET OPTION FROM: {$sn}\r\n"
       . "ATTLOGStamp=None\r\n"
       . "OPERLOGStamp=9999\r\n"
       . "ATTPHOTOStamp=9999\r\n"
       . "ErrorDelay=30\r\n"
       . "Delay=10\r\n"
       . "TransTimes=00:00;14:05\r\n"
       . "TransInterval=1\r\n"
       . "TransFlag=1111000000\r\n"
       . $tzLine
       . "Realtime=1\r\n"
       . 'Encrypt=None';

    text_response($r);
}

/**
 * Recepción de registros:  POST /iclock/cdata?SN=...&table=ATTLOG|OPERLOG
 * Guarda las marcaciones de asistencia.
 */
function iclock_receive(): void
{
    $sn      = $_GET['SN'] ?? '';
    $table   = $_GET['table'] ?? '';
    $stamp   = $_GET['Stamp'] ?? '';
    $content = file_get_contents('php://input');

    // Log crudo
    $log = db()->prepare('INSERT INTO finger_log (url, data) VALUES (?, ?)');
    $log->execute([json_encode($_GET), $content]);

    try {
        $arr = preg_split('/\r\n|\r|\n/', $content);
        $tot = 0;

        // Solo la tabla ATTLOG contiene marcaciones de asistencia.
        // Todo lo demás (options, OPERLOG, USERINFO, FINGERTMP, etc.) NO se
        // parsea como asistencia: se registra en finger_log y se confirma con
        // OK, para que el reloj no reintente en bucle.
        if ($table !== 'ATTLOG') {
            foreach ($arr as $rey) {
                if ($rey !== '' && $rey !== null) {
                    $tot++;
                }
            }
            text_response('OK: ' . $tot);
        }

        // Marcaciones de asistencia (idempotente: ignora duplicados).
        $ignore = db_driver() === 'sqlite' ? 'INSERT OR IGNORE' : 'INSERT IGNORE';
        $ins = db()->prepare(
            "$ignore INTO attendances
                (sn, `table`, stamp, employee_id, `timestamp`, status1, status2, status3, status4, status5, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $now = now_sql();

        foreach ($arr as $rey) {
            if ($rey === '' || $rey === null) {
                continue;
            }
            $data = explode("\t", $rey);
            $ins->execute([
                $sn,
                $table,
                $stamp,
                $data[0] ?? 0,
                $data[1] ?? null,
                fmt_int($data[2] ?? null),
                fmt_int($data[3] ?? null),
                fmt_int($data[4] ?? null),
                fmt_int($data[5] ?? null),
                fmt_int($data[6] ?? null),
                $now,
                $now,
            ]);
            $tot++;
        }
        text_response('OK: ' . $tot);
    } catch (Throwable $e) {
        $err = db()->prepare('INSERT INTO error_log (error, data) VALUES (?, ?)');
        $err->execute([$e->getMessage(), $content]);
        text_response('ERROR');
    }
}

/**
 * Sondeo de comandos:  GET /iclock/getrequest?SN=...
 * Entrega los comandos pendientes encolados desde el panel.
 */
function iclock_getrequest(): void
{
    $sn = $_GET['SN'] ?? '';
    if ($sn === '') {
        text_response('OK');
    }
    $cmds = pop_pending_commands($sn);
    text_response($cmds !== '' ? $cmds : 'OK');
}

/**
 * Resultado de un comando:  POST /iclock/devicecmd?SN=...
 * Cuerpo típico:  ID=12&Return=0&CMD=DATA
 */
function iclock_devicecmd(): void
{
    $raw = file_get_contents('php://input');
    parse_str(str_replace("\n", '&', trim($raw)), $fields);

    $id     = isset($fields['ID']) ? (int) $fields['ID'] : 0;
    $return = $fields['Return'] ?? null;

    if ($id > 0) {
        complete_command($id, $return, $raw);
    }
    text_response('OK');
}

function fmt_int($value)
{
    return (isset($value) && $value !== '') ? (int) $value : null;
}
