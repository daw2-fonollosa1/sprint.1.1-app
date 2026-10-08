# Sprint 1.1 PHP Application

This repository contains the PHP application for the individual Sprint 1.1 project. The separate HTML, CSS, and JavaScript application is maintained in another repository.

## Project status

The current implementation provides the server-rendered interface scaffold: a shared layout, role-specific navigation previews, Bootstrap 5, and responsive custom styles. The role selector is only for previewing the interface. Authentication, authorization, MariaDB persistence, and CRUD features are not implemented yet.

## Technology requirements

- PHP without an application framework.
- Object-oriented programming and the MVC pattern.
- MariaDB access through PDO when persistence is implemented.
- Bootstrap 5 for the responsive interface.
- Catalan text in the user interface.
- English source code, code comments, and project documentation.
- Docker Compose for application services.

## Project structure

```text
app/
├── Controllers/
├── Core/
└── Views/
    ├── layouts/
    ├── pages/
    └── partials/
config/
docs/
├── wireframes/
├── decisions.md
├── learning-objectives.md
└── requirements.md
public/
├── assets/
│   ├── css/
│   ├── img/
│   └── js/
└── index.php
routes/
storage/
tests/
Dockerfile
docker-compose.yml
composer.json
.env.example
README.md
```

`public/` is the web document root. PHP views live under `app/Views/`; only the front controller and public assets should be directly served to the browser. Composer's `vendor/` directory is generated when dependencies are installed and is not committed.

## Documentation

- [Learning objectives](docs/learning-objectives.md)
- [Requirements](docs/requirements.md)
- [Project decisions](docs/decisions.md)

## Run the interface scaffold

From the repository root, run PHP's built-in development server:

```bash
php -S 127.0.0.1:8000 -t public
```

Then open `http://127.0.0.1:8000/`. Bootstrap is currently loaded from its CDN, so the browser needs an internet connection to download it.
