<?php
/**
 * Cola de comandos hacia los relojes (protocolo Push de ZKTeco).
 *
 * Flujo:
 *   1. El panel encola un comando con enqueue_command().
 *   2. El reloj hace GET /iclock/getrequest?SN=... y se le entregan los
 *      comandos pendientes con el formato  "C:<id>:<comando>".
 *   3. El reloj ejecuta y reporta en POST /iclock/devicecmd?SN=...
 *      (ID=<id>&Return=<codigo>&CMD=<...>), que actualiza el estado.
 *
 * Referencia del protocolo: ZKTeco PUSH SDK (cdata/getrequest/devicecmd).
 */

if (!defined('ADMS')) { exit('No autorizado'); }

/** Encolar un comando crudo para un reloj. Devuelve el id. */
function enqueue_command(string $sn, string $command, ?string $label = null): int
{
    $stmt = db()->prepare(
        'INSERT INTO device_commands (sn, command, label, status) VALUES (?, ?, ?, "pending")'
    );
    $stmt->execute([$sn, $command, $label]);
    return (int) db()->lastInsertId();
}

/** Retirar los comandos pendientes de un reloj y marcarlos como enviados. */
function pop_pending_commands(string $sn): string
{
    $stmt = db()->prepare(
        'SELECT id, command FROM device_commands WHERE sn = ? AND status = "pending" ORDER BY id ASC'
    );
    $stmt->execute([$sn]);
    $rows = $stmt->fetchAll();

    if (!$rows) {
        return '';
    }

    $lines = [];
    $ids = [];
    foreach ($rows as $row) {
        $lines[] = 'C:' . $row['id'] . ':' . $row['command'];
        $ids[] = (int) $row['id'];
    }

    $in = implode(',', array_fill(0, count($ids), '?'));
    $upd = db()->prepare(
        "UPDATE device_commands SET status = 'sent', sent_at = NOW() WHERE id IN ($in)"
    );
    $upd->execute($ids);

    return implode("\n", $lines) . "\n";
}

/** Registrar el resultado que devuelve el reloj para un comando. */
function complete_command(int $id, ?string $returnCode, string $rawResponse): void
{
    $status = ($returnCode === null || $returnCode === '' || (int) $returnCode === 0) ? 'done' : 'error';
    $stmt = db()->prepare(
        'UPDATE device_commands SET status = ?, return_code = ?, response = ?, completed_at = NOW() WHERE id = ?'
    );
    $stmt->execute([$status, $returnCode, $rawResponse, $id]);
}

/**
 * Catálogo de comandos disponibles desde el panel de pruebas.
 * Cada uno arma el cuerpo del comando según los datos del formulario.
 * Los campos de USERINFO/FINGERTMP van separados por TAB ("\t").
 */
function build_command(string $action, array $p): array
{
    $sep = "\t";
    switch ($action) {
        case 'info':
            // Pide al reloj que reporte su información/estado.
            return ['INFO', 'Consultar información del reloj'];

        case 'reboot':
            return ['REBOOT', 'Reiniciar el reloj'];

        case 'create_user':
            $pin  = trim($p['pin'] ?? '');
            $name = trim($p['name'] ?? '');
            $card = trim($p['card'] ?? '');
            $pri  = trim($p['privilege'] ?? '0'); // 0=usuario, 14=admin
            $body = 'DATA UPDATE USERINFO PIN=' . $pin . $sep
                  . 'Name=' . $name . $sep
                  . 'Pri=' . $pri . $sep
                  . 'Passwd=' . $sep
                  . 'Card=' . $card . $sep
                  . 'Grp=1' . $sep . 'TZ=0000000000000000';
            return [$body, "Alta/edición de usuario PIN=$pin ($name)"];

        case 'delete_user':
            $pin = trim($p['pin'] ?? '');
            return ['DATA DELETE USERINFO PIN=' . $pin, "Borrar usuario PIN=$pin"];

        case 'query_users':
            return ['DATA QUERY USERINFO', 'Pedir todos los usuarios al reloj'];

        case 'query_attlog':
            $from = trim($p['from'] ?? '2024-01-01');
            $to   = trim($p['to'] ?? date('Y-m-d'));
            return ['DATA QUERY ATTLOG StartTime=' . $from . ' 00:00:00' . $sep . 'EndTime=' . $to . ' 23:59:59',
                    "Pedir marcaciones del $from al $to"];

        case 'enroll_fp':
            // Enrolar huella remotamente en el reloj (el usuario apoya el dedo en el reloj).
            $pin = trim($p['pin'] ?? '');
            $fid = trim($p['fid'] ?? '0'); // dedo 0-9
            $body = 'ENROLL_FP PIN=' . $pin . $sep . 'FID=' . $fid . $sep . 'RETRY=3' . $sep . 'OVERWRITE=1';
            return [$body, "Enrolar huella PIN=$pin dedo=$fid"];

        case 'push_fp':
            // Reenviar a este reloj una huella ya capturada (template) de otro reloj.
            $pin  = trim($p['pin'] ?? '');
            $fid  = trim($p['fid'] ?? '0');
            $size = trim($p['size'] ?? '');
            $tmp  = trim($p['tmp'] ?? '');
            $body = 'DATA UPDATE FINGERTMP PIN=' . $pin . $sep
                  . 'FID=' . $fid . $sep
                  . 'Size=' . $size . $sep
                  . 'Valid=1' . $sep
                  . 'TMP=' . $tmp;
            return [$body, "Cargar huella (template) PIN=$pin dedo=$fid"];

        case 'clear_attlog':
            return ['CLEAR LOG', 'Borrar marcaciones del reloj'];

        case 'clear_data':
            return ['CLEAR DATA', 'Borrar TODOS los datos del reloj'];

        case 'unlock':
            $sec = trim($p['seconds'] ?? '5');
            return ['AC_UNLOCK ' . $sec, "Abrir cerradura $sec s"];

        case 'raw':
            $body = trim($p['raw'] ?? '');
            return [$body, 'Comando manual'];
    }
    return ['', ''];
}
