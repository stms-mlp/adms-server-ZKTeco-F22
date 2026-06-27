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

    // Registrar/actualizar el reloj
    $up = db()->prepare(
        'INSERT INTO devices (no_sn, online) VALUES (?, NOW())
         ON DUPLICATE KEY UPDATE online = NOW()'
    );
    $up->execute([$sn]);

    // Línea de zona horaria (solo si está configurada). UTC-3 => "-3".
    $tzLine = '';
    if (isset($config['device_timezone']) && $config['device_timezone'] !== null && $config['device_timezone'] !== '') {
        $tzLine = 'TimeZone=' . $config['device_timezone'] . "\r\n";
    }

    $r = "GET OPTION FROM: {$sn}\r\n"
       . "Stamp=9999\r\n"
       . 'OpStamp=' . time() . "\r\n"
       . "ErrorDelay=60\r\n"
       . "Delay=30\r\n"
       . "ResLogDay=18250\r\n"
       . "ResLogDelCount=10000\r\n"
       . "ResLogCount=50000\r\n"
       . "TransTimes=00:00;14:05\r\n"
       . "TransInterval=1\r\n"
       . "TransFlag=1111000000\r\n"
       . $tzLine
       . "Realtime=1\r\n"
       . 'Encrypt=0';

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

        // Registros de operación (incluye altas de usuario/huella): solo contamos.
        if ($table === 'OPERLOG') {
            foreach ($arr as $rey) {
                if ($rey !== '' && $rey !== null) {
                    $tot++;
                }
            }
            text_response('OK: ' . $tot);
        }

        // Marcaciones de asistencia.
        $ins = db()->prepare(
            'INSERT INTO attendances
                (sn, `table`, stamp, employee_id, `timestamp`, status1, status2, status3, status4, status5, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

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
