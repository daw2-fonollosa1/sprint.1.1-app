# Sprint 1.1 PHP Application

This repository contains the PHP application for the individual Sprint 1.1 project. The separate HTML, CSS, and JavaScript application is maintained in another repository.

## Project status

The current implementation provides server-rendered PHP pages, role-specific navigation previews, and Bootstrap 5. PDO create handlers are implemented for users, projects, evaluable items, project assignments, and individual grades. However, every database write requires an authenticated server-side session, and the demonstration login does not create one, so writes are blocked until authentication is implemented. The login and role selector do not provide real authentication or access control.

## Technology requirements

- PHP without an application framework.
- Object-oriented programming and the MVC pattern.
- MariaDB access through PDO with prepared statements for the application's create operations.
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
│   ├── DashboardController.php
│   └── LoginController.php
├── Core/
│   ├── Database.php
│   └── RoleAuthorization.php
├── Models/
│   ├── DemoData.php
│   ├── EvaluableItemModel.php
│   ├── GradeModel.php
│   ├── ProjectAssignmentModel.php
│   ├── ProjectModel.php
│   ├── StudentDashboardData.php
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
├── decisions.md
├── database-schema.md
├── database-connection.md
├── learning-objectives.md
├── login-implementation.md
├── requirements.md
├── student-interface.md
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
└── check_database_connection.php
Dockerfile
docker-compose.yml
composer.json
.env.example
.gitignore
README.md
```

`public/` is the web document root. PHP views live under `app/Views/`; only the front controller and public assets should be directly served to the browser. Composer's `vendor/` directory is generated when dependencies are installed and is not committed.

`DashboardController.php` selects the requested role and page. `DemoData.php` keeps the shared sample records in one place, and `StudentDashboardData.php` prepares the filtered project and grade information for the student views.

## Documentation

- [Learning objectives](docs/learning-objectives.md)
- [Requirements](docs/requirements.md)
- [Project decisions](docs/decisions.md)
- [Database schema](docs/database-schema.md)
- [Database connection](docs/database-connection.md)
- [Create operations](docs/create-operations.md)
- [Administrator interface prototype](docs/administrator-interface.md)
- [Administrator user management](docs/administrator-user-management.md)
- [Teacher interface prototype](docs/teacher-interface.md)
- [Student interface prototype](docs/student-interface.md)

## Run the interface scaffold

From the repository root, run PHP's built-in development server:

```bash
php -S 127.0.0.1:8000 -t public
```

Then open `http://127.0.0.1:8000/`. Bootstrap is currently loaded from its CDN, so the browser needs an internet connection to download it.
