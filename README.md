# Sprint 1.1 PHP Application

This repository contains the PHP application for the individual Sprint 1.1 project. The separate HTML, CSS, and JavaScript application is maintained in another repository.

## Project status

The current implementation provides server-rendered PHP pages, role-specific navigation, and Bootstrap 5. Login checks usernames and password hashes in MariaDB through PDO, then stores the verified account ID and role in a PHP session. Read operations load administrator, teacher, and student data through PDO; create, update, and delete handlers save supported changes through the same connection. There is no public registration, and pages check the session role before loading role-specific records or accepting writes.

## Technology requirements

- PHP without an application framework.
- Object-oriented programming and the MVC pattern.
- MariaDB access through PDO, with prepared statements for queries that use request values.
- Bootstrap 5 for the responsive interface.
- Catalan text in the user interface.
- English source code, code comments, and project documentation.
- Docker Compose for application services.

## Project structure

```text
app/
├── Controllers/
│   ├── AdminUsersController.php
│   ├── CreateOperationsController.php
│   ├── DeleteOperationsController.php
│   ├── DashboardController.php
│   ├── LoginController.php
│   ├── ReadOperationsController.php
│   └── UpdateOperationsController.php
├── Core/
│   ├── Database.php
│   └── RoleAuthorization.php
├── Models/
│   ├── EvaluableItemModel.php
│   ├── GradeModel.php
│   ├── ProjectAssignmentModel.php
│   ├── ProjectModel.php
│   ├── StudentDashboardData.php
│   ├── TeacherDashboardData.php
│   └── UserModel.php
└── Views/
    ├── layouts/
    ├── pages/
    │   ├── admin/
    │   ├── student/
    │   ├── teacher/
    │   ├── login.php
    │   └── placeholder.php
    └── partials/
database/
├── schema.sql
└── seed.sql
config/
└── database.php
docs/
├── administrator-interface.md
├── administrator-user-management.md
├── delete-operations.md
├── decisions.md
├── database-schema.md
├── database-connection.md
├── learning-objectives.md
├── login-implementation.md
├── requirements.md
├── testing.md
├── student-interface.md
├── update-operations.md
├── teacher-interface.md
└── wireframes/
public/
├── assets/
│   ├── css/
│   ├── img/
│   └── js/
└── index.php
routes/
storage/
tests/
├── Integration/
├── bootstrap.php
└── check_database_connection.php
Dockerfile
docker-compose.yml
.dockerignore
composer.json
phpunit.xml
.env.example
.gitignore
README.md
```

`public/` is the web document root. PHP views live under `app/Views/`; only the front controller and public assets should be directly served to the browser. Composer's `vendor/` directory is generated when dependencies are installed and is not committed.

`LoginController.php` checks credentials through `UserModel.php`, creates the authenticated PHP session, and handles sign-out. `DashboardController.php` selects the requested page and checks the PHP session against the `users` table. `ReadOperationsController.php` gathers the database rows needed by that page, while the create, update, and delete controllers handle their respective form submissions. `AdminUsersController.php` continues to manage teacher and student accounts. `TeacherDashboardData.php` and `StudentDashboardData.php` prepare the related records for their role-specific views.

## Documentation

- [Learning objectives](docs/learning-objectives.md)
- [Requirements](docs/requirements.md)
- [Project decisions](docs/decisions.md)
- [Database schema](docs/database-schema.md)
- [Database connection](docs/database-connection.md)
- [Login and sessions](docs/login-implementation.md)
- [Create operations](docs/create-operations.md)
- [Read operations](docs/read-operations.md)
- [Update operations](docs/update-operations.md)
- [Delete operations](docs/delete-operations.md)
- [Automated tests](docs/testing.md)
- [Administrator interface prototype](docs/administrator-interface.md)
- [Administrator user management](docs/administrator-user-management.md)
- [Teacher interface prototype](docs/teacher-interface.md)
- [Student interface prototype](docs/student-interface.md)

## Run with Docker Compose

Docker Compose starts Apache/PHP and MariaDB in separate containers. From the repository root, create your local environment file if you do not already have one, then edit both development passwords:

```bash
cp -n .env.example .env
```

Set `DB_PASSWORD` and `DB_ROOT_PASSWORD` in `.env` to local development values. The `.env` file is ignored by Git. The PHP container receives the database settings from Compose, and its database host is set to the MariaDB service name `db`.

Build the PHP image and start both containers:

```bash
docker compose up --build -d
```

Open the application at [http://127.0.0.1:8080/](http://127.0.0.1:8080/). If you changed `APP_PORT` in `.env`, use that port instead. On the first start with a new database volume, MariaDB runs `database/schema.sql` followed by `database/seed.sql`. These initialization scripts do not rerun over an existing database volume.

Useful commands:

```bash
docker compose ps
docker compose logs -f
docker compose exec app php tests/check_database_connection.php
docker compose down
```

`docker compose down` stops and removes the containers but preserves the named `mariadb_data` volume. The database records are still there the next time you run `docker compose up -d`. **Do not run `docker compose down -v` unless you intend to permanently delete the stored database volume and all its data.**

To check that database data survives a restart, run this command before and after `docker compose down` followed by `docker compose up -d`. Enter the `DB_ROOT_PASSWORD` from `.env` when prompted; the user count should stay the same:

```bash
docker compose exec db mariadb -uroot -p -N -e "SELECT COUNT(*) FROM daw2_project_manager.users;"
```

To rebuild the PHP image after changing application files, run `docker compose up --build -d` again. This class-project setup is for local development and is not production-ready.

## Run the PHP application

From the repository root, run PHP's built-in development server:

```bash
php -S 127.0.0.1:8000 -t public
```

Configure MariaDB and PHP's `pdo_mysql` extension, then load `database/schema.sql` and `database/seed.sql` as described in [Login and sessions](docs/login-implementation.md). Start the server and open `http://127.0.0.1:8000/`. Bootstrap is loaded from its CDN, so the browser needs an internet connection to download it.

## Run the automated tests

Automated tests use PHPUnit and a dedicated MariaDB database ending in `_test`. Do not point the tests at the development database. Follow [docs/testing.md](docs/testing.md) to create the test database and set `TEST_DB_*` variables, then run:

```bash
composer install
composer test
```

The tests create temporary records and remove them after each test. They do not use the development seed data.
