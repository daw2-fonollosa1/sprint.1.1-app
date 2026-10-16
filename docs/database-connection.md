# MariaDB Connection with PDO

This document describes the reusable PDO connection used by the PHP application. Login, read controllers, and create-operation controllers use it for account checks, page data, inserts, and form choices.

## Files

- `app/Core/Database.php` creates and reuses the PDO connection.
- `config/database.php` reads the database settings from system environment variables or the local `.env` file.
- `.env.example` shows the setting names and safe placeholder values to use for local development.
- `tests/check_database_connection.php` is a command-line check that opens the connection and runs `SELECT 1`.
- `docs/database-connection.md` explains how to configure and check the connection.
- `README.md` lists the new files and describes the current database status.

The existing `.gitignore` already ignores `.env`, so local credentials are not committed. Do not place real passwords in `.env.example` or source code.

## Configure local settings

Copy the example file and edit the copy with the values for your local MariaDB account:

```bash
cp .env.example .env
```

Set these values in `.env`:

- `DB_HOST`: MariaDB host, usually `127.0.0.1` for a local server.
- `DB_PORT`: MariaDB port, usually `3306`.
- `DB_NAME`: database name, currently `daw2_project_manager` from `database/schema.sql`.
- `DB_USER`: a local MariaDB username that can connect to that database.
- `DB_PASSWORD`: that user's local password. It can be blank if the local MariaDB account has no password.

System environment variables with these names take priority over values from `.env`. The `.env` parser uses PHP's built-in `parse_ini_file`; no extra package is required. The database must already exist before the connection check can succeed. The schema and sample data can be created with:

```bash
mariadb -u root -p < database/schema.sql
mariadb -u root -p daw2_project_manager < database/seed.sql
```

## How the connection works

`Database::getConnection()` loads the settings, builds a MySQL-compatible PDO DSN for MariaDB, and returns a PDO object. It keeps that object in a static property so code in the same PHP request can reuse one connection.

PDO uses exception mode so database errors can be handled with `try` and `catch`. It returns rows as associative arrays and disables emulated prepared statements. When a future query includes values, prepare it and pass the values separately with `execute()`; do not join user input into an SQL string.

The class is under `app/Core`, so a model can load and use it without moving database code into `public/`:

```php
require_once dirname(__DIR__) . '/Core/Database.php';

$connection = Database::getConnection();
```

The create-operation controllers and models reuse this connection for user, project, evaluable-item, assignment, and grade inserts. They also use small reads to show saved project/item rows and to populate assignment and grade choices. Every write checks the server-side role session before using PDO.

## Check the connection

From the repository root, run:

```bash
php tests/check_database_connection.php
```

If it works, the command prints:

```text
Database connection successful.
```

If it fails, it prints a short message asking you to check MariaDB, the PDO MySQL driver, and the local settings. The check intentionally does not print the password, connection string, or exception trace. Confirm that PHP has the `pdo_mysql` extension enabled and that MariaDB is running before retrying.

## Outside this issue

- The application does not provide public registration or password reset. The administrator creates teacher and student accounts.
- Docker configuration is unchanged.
- Live database login and dashboard checks still require MariaDB and PHP's `pdo_mysql` extension.
