# Servidor ADMS ZKTeco — Municipalidad de Lago Puelo

Servidor que recibe las marcaciones de los relojes biométricos **ZKTeco** (probado con **F22**)
mediante el protocolo *Push* (`/iclock/*`), y un panel web para ver dispositivos, asistencia
y enviar comandos a los relojes.

Reescrito en **PHP puro** (sin framework ni Composer) para correr fácil en **hosting compartido**
(por ejemplo donweb). Funciona con **SQLite** (por defecto, sin servidor de base) o con
**MySQL/MariaDB**.

## Requisitos

- PHP >= 7.4 (probado en 8.x) con **PDO** (`pdo_sqlite` y/o `pdo_mysql`)
- Apache con `mod_rewrite` (el `.htaccess` ya viene incluido)
- (Opcional) MySQL/MariaDB si preferís ese motor

## Base de datos: SQLite o MySQL

El motor se elige en `config.php` con `db.driver`:

- **`'sqlite'` (recomendado, por defecto):** no necesita servidor de base. El archivo se crea
  solo la primera vez en `data/adms.sqlite`. Esa carpeta está protegida por `.htaccess`
  (`data/.htaccess` + reglas en el `.htaccess` raíz) para que la base **no pueda descargarse**
  por la web. Backup = copiar el archivo. Asegurate de que la carpeta `data/` tenga permisos
  de escritura para PHP.
- **`'mysql'`:** completá host/usuario/clave/base e importá el esquema:
  ```bash
  mysql -u USUARIO -p NOMBRE_BASE < database/schema.sql
  ```
  (en donweb se importa `database/schema.sql` desde **phpMyAdmin**).

> Con SQLite no hace falta importar nada: las tablas se crean automáticamente.

## Instalación

1. **Subir los archivos** al hosting (a `public_html` o la carpeta pública del dominio).

2. **Configurar** copiando el ejemplo:
   ```bash
   cp config.example.php config.php
   ```
   Editar `config.php` con el motor de base (`db.driver`), la zona horaria y el usuario admin.

3. **Generar la clave del panel** y pegar el hash en `config.php` (`admin.pass_hash`):
   ```bash
   php -r "echo password_hash('TU_CLAVE', PASSWORD_DEFAULT), PHP_EOL;"
   ```

4. Entrar al panel: `https://tudominio/` (te pide login).

## Zona horaria (UTC-3)

- `timezone` en `config.php` fija la zona del servidor (`America/Argentina/Buenos_Aires`).
- `device_timezone` es el valor que se le envía al reloj en el *handshake*.
  Para Lago Puelo (UTC-3) está en `'-3'`. Si tu firmware interpreta la zona en **minutos**,
  usá `'-180'`. Poné `null` para no modificar la hora del reloj.

## Apuntar el reloj al servidor

En el reloj: **Menú → Comunicación → Servidor en la nube (ADMS)** y configurar la IP/dominio
y el puerto del servidor. El reloj usará las rutas `/iclock/cdata` automáticamente.

## Endpoints del protocolo (los usan los relojes)

| Método | Ruta                  | Función                                   |
|--------|-----------------------|-------------------------------------------|
| GET    | `/iclock/cdata`       | Handshake + envío de configuración/zona   |
| POST   | `/iclock/cdata`       | Recepción de marcaciones (ATTLOG/OPERLOG) |
| GET    | `/iclock/getrequest`  | El reloj retira los comandos pendientes   |
| POST   | `/iclock/devicecmd`   | El reloj reporta el resultado del comando |

## Panel de pruebas

En **Panel de pruebas** podés encolar comandos hacia los relojes: consultar info, alta/baja de
usuarios, enrolar huella, replicar un *template* de huella a otro reloj, abrir cerradura,
reiniciar, borrar datos y un campo de comando manual. Los comandos se ejecutan cuando el reloj
vuelve a sondear; el resultado queda en el historial.

> Nota: el alcance exacto de cada comando depende del firmware del reloj.

## Estructura

```
index.php              Front controller / router
.htaccess              Reescritura de URLs (Apache)
config.example.php     Plantilla de configuración (copiar a config.php)
database/schema.sql        Esquema para MySQL
database/schema.sqlite.sql Esquema para SQLite (se aplica solo automáticamente)
data/                  Base SQLite (protegida, no se sube al repo)
src/                   Código (protocolo, comandos, helpers, vistas)
```

## Licencia

MIT. Basado en el proyecto original [adms-server-ZKTeco](https://github.com/saifulcoder/adms-server-ZKTeco).
