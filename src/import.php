<?php
/**
 * Importaciones: organigrama (CSV) y empleados desde los datos que el reloj
 * ya envió (registros USER guardados en finger_log).
 */

if (!defined('ADMS')) { exit('No autorizado'); }

/**
 * Importa/actualiza el organigrama desde database/organigrama.csv
 * (separado por ';', con encabezado). Devuelve conteos.
 */
function importar_organigrama(): array
{
    $file = BASE_PATH . '/database/organigrama.csv';
    if (!is_readable($file)) {
        return ['error' => 'No se encuentra database/organigrama.csv'];
    }
    $fh = fopen($file, 'r');
    $secCreadas = 0; $repCreadas = 0; $repActualizadas = 0;
    $primera = true;
    $cacheSec = [];

    while (($line = fgetcsv($fh, 0, ';', '"', '\\')) !== false) {
        if ($primera) { $primera = false; continue; } // encabezado
        if (count($line) < 3 || trim($line[0]) === '') { continue; }
        $secNombre = trim($line[0]);
        $repNombre = trim($line[1]);
        $esSec     = (int) trim($line[2]);

        // Secretaría
        if (!isset($cacheSec[$secNombre])) {
            $st = db()->prepare('SELECT id FROM secretarias WHERE nombre = ?');
            $st->execute([$secNombre]);
            $id = $st->fetchColumn();
            if ($id === false) {
                db()->prepare('INSERT INTO secretarias (nombre) VALUES (?)')->execute([$secNombre]);
                $id = db()->lastInsertId();
                $secCreadas++;
            }
            $cacheSec[$secNombre] = $id;
        }
        $secId = $cacheSec[$secNombre];

        // Repartición
        $st = db()->prepare('SELECT id FROM reparticiones WHERE secretaria_id = ? AND nombre = ?');
        $st->execute([$secId, $repNombre]);
        $repId = $st->fetchColumn();
        if ($repId === false) {
            db()->prepare('INSERT INTO reparticiones (secretaria_id, nombre, es_secretaria) VALUES (?, ?, ?)')
                ->execute([$secId, $repNombre, $esSec]);
            $repCreadas++;
        } else {
            db()->prepare('UPDATE reparticiones SET es_secretaria = ? WHERE id = ?')->execute([$esSec, $repId]);
            $repActualizadas++;
        }
    }
    fclose($fh);
    return ['secretarias' => $secCreadas, 'reparticiones' => $repCreadas, 'actualizadas' => $repActualizadas];
}

/**
 * Importa empleados a partir de los registros USER que el reloj ya envió
 * (guardados en finger_log). Crea los que falten por PIN; no pisa datos
 * existentes salvo el nombre si estaba vacío.
 */
function importar_empleados_reloj(): array
{
    $rows = db()->query("SELECT data FROM finger_log WHERE data LIKE '%USER PIN=%'")->fetchAll();
    $creados = 0; $actualizados = 0; $vistos = [];

    // Empleados ya existentes (por PIN)
    $exist = [];
    foreach (db()->query('SELECT pin, nombre FROM employees')->fetchAll() as $e) {
        $exist[$e['pin']] = $e['nombre'];
    }

    $insert = db()->prepare(
        'INSERT INTO employees (pin, nombre, activo, created_at, updated_at) VALUES (?, ?, 1, ?, ?)'
    );
    $updateName = db()->prepare('UPDATE employees SET nombre = ?, updated_at = ? WHERE pin = ?');

    foreach ($rows as $r) {
        // Cada USER: "USER PIN=123<TAB>Name=Juan Perez<TAB>Pri=0 ..."
        if (!preg_match_all('/PIN=(\d+)[\t ]+Name=(.*?)[\t ]+Pri=/s', $r['data'], $m, PREG_SET_ORDER)) {
            continue;
        }
        foreach ($m as $u) {
            $pin    = $u[1];
            $nombre = trim($u[2]);
            if (isset($vistos[$pin])) { continue; }
            $vistos[$pin] = true;

            if (!array_key_exists($pin, $exist)) {
                $insert->execute([$pin, $nombre, now_sql(), now_sql()]);
                $exist[$pin] = $nombre;
                $creados++;
            } elseif (($exist[$pin] === null || $exist[$pin] === '') && $nombre !== '') {
                $updateName->execute([$nombre, now_sql(), $pin]);
                $actualizados++;
            }
        }
    }
    return ['creados' => $creados, 'actualizados' => $actualizados];
}
