-- =====================================================================
--  Servidor ADMS ZKTeco - Esquema para SQLite
--  (se crea automáticamente la primera vez si usás el driver 'sqlite')
-- =====================================================================

CREATE TABLE IF NOT EXISTS devices (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    nama       TEXT,
    no_sn      TEXT NOT NULL UNIQUE,
    lokasi     TEXT,
    online     TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime')),
    updated_at TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS secretarias (
    id     INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS reparticiones (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    secretaria_id INTEGER NOT NULL,
    nombre        TEXT NOT NULL,
    es_secretaria INTEGER NOT NULL DEFAULT 0,
    tipo          TEXT DEFAULT 'Otro',   -- Secretaría/Subsecretaría/Dirección/Departamento/División/Otro
    parent_id     INTEGER,               -- dependencia padre (opcional)
    UNIQUE(secretaria_id, nombre)
);

CREATE TABLE IF NOT EXISTS system_users (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario    TEXT NOT NULL UNIQUE,
    pass_hash  TEXT NOT NULL,
    rol        TEXT NOT NULL DEFAULT 'consulta',  -- admin | enrolador | consulta
    nombre     TEXT,
    activo     INTEGER NOT NULL DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now','localtime')),
    updated_at TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS employees (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    pin            TEXT NOT NULL UNIQUE,   -- PIN/ID del empleado en el reloj (clave de vínculo)
    nombre         TEXT,
    apellido       TEXT,
    dni            TEXT,
    sector         TEXT,
    cargo          TEXT,
    foto           TEXT,                   -- ruta de la foto (carpeta uploads/)
    reparticion_id INTEGER,                -- dependencia (organigrama)
    activo         INTEGER NOT NULL DEFAULT 1,
    created_at     TEXT DEFAULT (datetime('now','localtime')),
    updated_at     TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS attendances (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    sn          TEXT NOT NULL,
    "table"     TEXT,
    stamp       TEXT,
    employee_id INTEGER NOT NULL,
    "timestamp" TEXT NOT NULL,
    status1     INTEGER,
    status2     INTEGER,
    status3     INTEGER,
    status4     INTEGER,
    status5     INTEGER,
    created_at  TEXT,
    updated_at  TEXT
);
CREATE INDEX IF NOT EXISTS attendances_employee_idx  ON attendances (employee_id);
CREATE INDEX IF NOT EXISTS attendances_timestamp_idx ON attendances ("timestamp");
CREATE INDEX IF NOT EXISTS attendances_sn_idx        ON attendances (sn);

CREATE TABLE IF NOT EXISTS device_commands (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    sn           TEXT NOT NULL,
    command      TEXT NOT NULL,
    label        TEXT,
    status       TEXT NOT NULL DEFAULT 'pending',
    return_code  TEXT,
    response     TEXT,
    created_at   TEXT DEFAULT (datetime('now','localtime')),
    sent_at      TEXT,
    completed_at TEXT
);
CREATE INDEX IF NOT EXISTS device_commands_sn_status_idx ON device_commands (sn, status);

CREATE TABLE IF NOT EXISTS device_log (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    data       TEXT,
    tgl        TEXT,
    sn         TEXT,
    "option"   TEXT,
    url        TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime')),
    updated_at TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS finger_log (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    data       TEXT,
    url        TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime')),
    updated_at TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS error_log (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    error      TEXT,
    data       TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);
