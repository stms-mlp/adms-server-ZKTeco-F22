<?php
/**
 * Front controller / router del servidor ADMS ZKTeco (Lago Puelo).
 *
 * Todas las peticiones entran por acá (ver .htaccess).
 * - /iclock/*  -> protocolo de los relojes (sin login)
 * - resto      -> panel de administración (con login)
 */

require __DIR__ . '/src/bootstrap.php';
require __DIR__ . '/src/iclock.php';

// Ruta solicitada, relativa a la carpeta de instalación.
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($scriptDir !== '' && strpos($uri, $scriptDir) === 0) {
    $uri = substr($uri, strlen($scriptDir));
}
$route  = '/' . trim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];

// ----------------------------------------------------------------------
//  Protocolo de los relojes (texto plano, sin autenticación)
// ----------------------------------------------------------------------
switch (true) {
    case $route === '/iclock/cdata' && $method === 'GET':
        iclock_handshake();

    case $route === '/iclock/cdata' && $method === 'POST':
        iclock_receive();

    case $route === '/iclock/getrequest':
        iclock_getrequest();

    case $route === '/iclock/devicecmd':
        iclock_devicecmd();
}

// ----------------------------------------------------------------------
//  Panel de administración (requiere login)
// ----------------------------------------------------------------------

// Login / logout
if ($route === '/login') {
    if ($method === 'POST') {
        start_session();
        $u = $_POST['user'] ?? '';
        $p = $_POST['pass'] ?? '';
        if ($u === ($config['admin']['user'] ?? '') && password_verify($p, $config['admin']['pass_hash'] ?? '')) {
            $_SESSION['admin'] = $u;
            redirect('devices');
        }
        view('login', ['error' => 'Usuario o clave incorrectos'], 'Ingreso');
        exit;
    }
    if (is_logged_in()) { redirect('devices'); }
    view('login', ['error' => null], 'Ingreso');
    exit;
}

if ($route === '/logout') {
    start_session();
    $_SESSION = [];
    session_destroy();
    redirect('login');
}

// A partir de acá, todo exige login.
require_login();

switch ($route) {
    case '/':
    case '/devices':
        if ($method === 'POST' && csrf_check()) {
            // Guardar nombre/ubicación del reloj
            $stmt = db()->prepare('UPDATE devices SET nama = ?, lokasi = ? WHERE id = ?');
            $stmt->execute([$_POST['nama'] ?? null, $_POST['lokasi'] ?? null, (int) ($_POST['id'] ?? 0)]);
            redirect('devices');
        }
        $devices = db()->query('SELECT id, no_sn, nama, lokasi, online FROM devices ORDER BY online DESC')->fetchAll();
        view('devices', ['devices' => $devices], 'Relojes');
        break;

    case '/attendance':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per  = 25;
        $off  = ($page - 1) * $per;
        // Filtro opcional por empleado (PIN)
        $emp  = trim($_GET['emp'] ?? '');
        $where = $emp !== '' ? 'WHERE a.employee_id = ' . (int) $emp : '';
        $total = (int) db()->query("SELECT COUNT(*) c FROM attendances a $where")->fetch()['c'];
        $stmt = db()->prepare(
            "SELECT a.id, a.sn, a.employee_id, a.`timestamp`,
                    a.status1, a.status2, a.status3, a.status4, a.status5,
                    e.nombre, e.apellido, e.foto, r.nombre AS reparticion, s.nombre AS secretaria
             FROM attendances a
             LEFT JOIN employees e ON e.pin = a.employee_id
             LEFT JOIN reparticiones r ON r.id = e.reparticion_id
             LEFT JOIN secretarias s ON s.id = r.secretaria_id
             $where
             ORDER BY a.id DESC LIMIT ? OFFSET ?"
        );
        $stmt->bindValue(1, $per, PDO::PARAM_INT);
        $stmt->bindValue(2, $off, PDO::PARAM_INT);
        $stmt->execute();
        view('attendance', [
            'rows'  => $stmt->fetchAll(),
            'page'  => $page,
            'pages' => max(1, (int) ceil($total / $per)),
            'total' => $total,
            'emp'   => $emp,
        ], 'Asistencia');
        break;

    case '/employees':
        $msg = null;
        if ($method === 'POST' && csrf_check()) {
            $id   = (int) ($_POST['id'] ?? 0);
            $pin  = trim($_POST['pin'] ?? '');
            $rep  = ($_POST['reparticion_id'] ?? '') !== '' ? (int) $_POST['reparticion_id'] : null;
            $data = [
                'pin'      => $pin,
                'nombre'   => trim($_POST['nombre'] ?? ''),
                'apellido' => trim($_POST['apellido'] ?? ''),
                'dni'      => trim($_POST['dni'] ?? ''),
                'cargo'    => trim($_POST['cargo'] ?? ''),
                'activo'   => isset($_POST['activo']) ? 1 : 0,
            ];
            if ($pin === '') {
                $msg = 'El PIN/ID es obligatorio.';
            } else {
                $foto = guardar_foto_empleado($pin);
                try {
                    if ($id > 0) {
                        $sql = 'UPDATE employees SET pin=?, nombre=?, apellido=?, dni=?, cargo=?, reparticion_id=?, activo=?'
                             . ($foto ? ', foto=?' : '') . ', updated_at=? WHERE id=?';
                        $args = [$data['pin'], $data['nombre'], $data['apellido'], $data['dni'],
                                 $data['cargo'], $rep, $data['activo']];
                        if ($foto) { $args[] = $foto; }
                        $args[] = now_sql();
                        $args[] = $id;
                        db()->prepare($sql)->execute($args);
                        $msg = 'Empleado actualizado.';
                    } else {
                        db()->prepare(
                            'INSERT INTO employees (pin, nombre, apellido, dni, cargo, reparticion_id, foto, activo, created_at, updated_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                        )->execute([$data['pin'], $data['nombre'], $data['apellido'], $data['dni'],
                                    $data['cargo'], $rep, $foto, $data['activo'], now_sql(), now_sql()]);
                        $msg = 'Empleado creado.';
                    }
                } catch (PDOException $e) {
                    $msg = 'Error: ¿el PIN ya existe? (' . $e->getCode() . ')';
                }
            }
        }
        // Importar empleados desde los datos del reloj
        if ($method === 'POST' && csrf_check() && ($_POST['import_reloj'] ?? '') !== '') {
            $r = importar_empleados_reloj();
            $msg = "Importación del reloj: {$r['creados']} creados, {$r['actualizados']} actualizados.";
        }
        // Baja / alta rápida (mantiene el empleado cargado)
        if (($_GET['toggle'] ?? '') !== '' && csrf_check_get()) {
            db()->prepare('UPDATE employees SET activo = 1 - activo, updated_at = ? WHERE id = ?')
                ->execute([now_sql(), (int) $_GET['toggle']]);
            redirect('employees');
        }
        if (($_GET['delete'] ?? '') !== '' && csrf_check_get()) {
            db()->prepare('DELETE FROM employees WHERE id = ?')->execute([(int) $_GET['delete']]);
            redirect('employees');
        }
        $edit = null;
        if (($_GET['edit'] ?? '') !== '') {
            $st = db()->prepare('SELECT * FROM employees WHERE id = ?');
            $st->execute([(int) $_GET['edit']]);
            $edit = $st->fetch() ?: null;
        }
        // Filtros del listado
        $fEstado = $_GET['estado'] ?? '';   // '', 'activos', 'bajas'
        $fRep    = ($_GET['rep'] ?? '') !== '' ? (int) $_GET['rep'] : null;
        $cond = [];
        if ($fEstado === 'activos') { $cond[] = 'e.activo = 1'; }
        if ($fEstado === 'bajas')   { $cond[] = 'e.activo = 0'; }
        if ($fRep !== null)         { $cond[] = 'e.reparticion_id = ' . $fRep; }
        $w = $cond ? ('WHERE ' . implode(' AND ', $cond)) : '';
        $employees = db()->query(
            "SELECT e.*, r.nombre AS reparticion, s.nombre AS secretaria,
                    (SELECT COUNT(*) FROM attendances a WHERE a.employee_id = e.pin) marcas
             FROM employees e
             LEFT JOIN reparticiones r ON r.id = e.reparticion_id
             LEFT JOIN secretarias s ON s.id = r.secretaria_id
             $w
             ORDER BY e.activo DESC, e.nombre, e.apellido"
        )->fetchAll();
        view('employees', [
            'employees'    => $employees,
            'edit'         => $edit,
            'msg'          => $msg,
            'reparticiones'=> reparticiones_agrupadas(),
            'fEstado'      => $fEstado,
            'fRep'         => $fRep,
        ], 'Empleados');
        break;

    case '/organigrama':
        $msg = null;
        if ($method === 'POST' && csrf_check() && ($_POST['importar'] ?? '') !== '') {
            $r = importar_organigrama();
            $msg = isset($r['error']) ? $r['error']
                 : "Organigrama importado: {$r['secretarias']} secretarías nuevas, "
                 . "{$r['reparticiones']} reparticiones nuevas, {$r['actualizadas']} actualizadas.";
        }
        $orga = db()->query(
            'SELECT s.nombre AS secretaria, r.nombre AS reparticion, r.es_secretaria,
                    (SELECT COUNT(*) FROM employees e WHERE e.reparticion_id = r.id) empleados
             FROM reparticiones r
             JOIN secretarias s ON s.id = r.secretaria_id
             ORDER BY s.nombre, r.es_secretaria DESC, r.nombre'
        )->fetchAll();
        view('organigrama', ['orga' => $orga, 'msg' => $msg], 'Organigrama');
        break;

    case '/reportes':
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $tipo  = $_GET['tipo'] ?? '';           // 'emp' | 'rep' | 'sec' | ''
        $val   = $_GET['val'] ?? '';
        $rows  = [];
        $cond  = ['a.`timestamp` >= ?', 'a.`timestamp` <= ?'];
        $args  = [$desde . ' 00:00:00', $hasta . ' 23:59:59'];
        if ($tipo === 'emp' && $val !== '') { $cond[] = 'e.pin = ?';          $args[] = $val; }
        if ($tipo === 'rep' && $val !== '') { $cond[] = 'e.reparticion_id = ?'; $args[] = (int) $val; }
        if ($tipo === 'sec' && $val !== '') { $cond[] = 's.id = ?';           $args[] = (int) $val; }
        $w = 'WHERE ' . implode(' AND ', $cond);
        $sql =
            "SELECT e.pin, e.nombre, e.apellido, r.nombre AS reparticion, s.nombre AS secretaria,
                    substr(a.`timestamp`,1,10) AS dia,
                    MIN(a.`timestamp`) AS entrada, MAX(a.`timestamp`) AS salida, COUNT(*) AS marcas
             FROM attendances a
             LEFT JOIN employees e ON e.pin = a.employee_id
             LEFT JOIN reparticiones r ON r.id = e.reparticion_id
             LEFT JOIN secretarias s ON s.id = r.secretaria_id
             $w
             GROUP BY e.pin, e.nombre, e.apellido, r.nombre, s.nombre, substr(a.`timestamp`,1,10)
             ORDER BY dia DESC, s.nombre, e.nombre";
        $st = db()->prepare($sql);
        $st->execute($args);
        $rows = $st->fetchAll();

        // Exportar a CSV
        if (($_GET['export'] ?? '') === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="reporte_asistencia_' . $desde . '_a_' . $hasta . '.csv"');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel
            fputcsv($out, ['Fecha', 'PIN', 'Empleado', 'Secretaría', 'Repartición', 'Entrada', 'Salida', 'Marcas'], ';', '"', '\\');
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['dia'], $r['pin'], trim(($r['apellido'] ?? '') . ' ' . ($r['nombre'] ?? '')),
                    $r['secretaria'], $r['reparticion'],
                    substr($r['entrada'], 11, 5), substr($r['salida'], 11, 5), $r['marcas'],
                ], ';', '"', '\\');
            }
            fclose($out);
            exit;
        }

        view('reportes', [
            'rows'          => $rows,
            'desde'         => $desde,
            'hasta'         => $hasta,
            'tipo'          => $tipo,
            'val'           => $val,
            'empleados'     => db()->query('SELECT pin, nombre, apellido FROM employees ORDER BY nombre, apellido')->fetchAll(),
            'reparticiones' => reparticiones_agrupadas(),
            'secretarias'   => db()->query('SELECT id, nombre FROM secretarias ORDER BY nombre')->fetchAll(),
        ], 'Reportes');
        break;

    case '/device-log':
        $rows = db()->query('SELECT id, data, url FROM device_log ORDER BY id DESC LIMIT 200')->fetchAll();
        view('logs', ['rows' => $rows, 'titulo' => 'Log de dispositivos'], 'Log de dispositivos');
        break;

    case '/finger-log':
        $rows = db()->query('SELECT id, data, url FROM finger_log ORDER BY id DESC LIMIT 200')->fetchAll();
        view('logs', ['rows' => $rows, 'titulo' => 'Log de datos (finger)'], 'Finger log');
        break;

    case '/panel':
        $msg = null;
        if ($method === 'POST' && csrf_check()) {
            $sn     = $_POST['sn'] ?? '';
            $action = $_POST['action'] ?? '';
            [$body, $label] = build_command($action, $_POST);
            if ($sn !== '' && $body !== '') {
                $id = enqueue_command($sn, $body, $label);
                $msg = "Comando encolado (#$id): $label — se ejecutará cuando el reloj $sn vuelva a sondear.";
            } else {
                $msg = 'Faltan datos: elegí un reloj y un comando válido.';
            }
        }
        $devices = db()->query('SELECT no_sn, nama FROM devices ORDER BY no_sn')->fetchAll();
        $cmds = db()->query(
            'SELECT id, sn, label, command, status, return_code, created_at, completed_at
             FROM device_commands ORDER BY id DESC LIMIT 50'
        )->fetchAll();
        view('panel', ['devices' => $devices, 'cmds' => $cmds, 'msg' => $msg], 'Panel de pruebas');
        break;

    default:
        http_response_code(404);
        view('logs', ['rows' => [], 'titulo' => 'Página no encontrada (404)'], '404');
}
