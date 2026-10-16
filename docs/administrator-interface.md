# Administrator Interface Prototype

## Scope

The administrator area contains a dashboard and pages for users, projects, and evaluable items. The user page reads teachers and students from MariaDB. The project and evaluable-item pages now create database records and provide the assignment forms. Their update and delete operations are not part of the current create-only work.

PHP renders each page. Forms submit to PHP and use PDO prepared statements. The current demo login does not create an authenticated server-side session, so every data-changing POST is rejected until authentication is implemented. JavaScript does not save records.

Visible interface text is in Catalan. Source code, comments, and this document are in English.

## Files

- `app/Controllers/DashboardController.php` — Defines role navigation, selects views, and calls the administrator user controller for the user page.
- `app/Controllers/AdminUsersController.php` — Validates account forms, calls the user model, redirects after successful actions, and returns safe feedback.
- `app/Models/DemoData.php` — Keeps sample users, projects, evaluable items, and grades for the dashboard and demonstration pages.
- `app/Models/UserModel.php` — Loads, creates, updates, and deletes teacher/student records with prepared PDO statements.
- `app/Controllers/CreateOperationsController.php` — Validates create forms, checks session roles, and prepares form choices.
- `app/Models/ProjectModel.php`, `EvaluableItemModel.php`, `ProjectAssignmentModel.php`, and `GradeModel.php` — Add the insert methods and minimal choice/list reads described in `docs/create-operations.md`.
- `app/Views/layouts/main.php` — Includes shared navigation and assets. It sends sample JSON only to sections that still use the browser demonstrations.
- `app/Views/pages/admin/dashboard.php` — Shows sample totals and a short list of projects.
- `app/Views/pages/admin/users.php` — Renders users from MariaDB and provides role filters and create, edit, and delete forms.
- `app/Views/pages/admin/projects.php` — Provides the PDO project form, persistent list, and create-only assignment forms.
- `app/Views/pages/admin/evaluable_items.php` — Provides the PDO item form and persistent list.
- `public/assets/js/admin-users.js` — Filters the PHP-rendered rows, opens the account modal, and confirms delete submissions. PHP performs all database changes.
- `public/assets/js/admin-common.js` — Remains from the earlier browser demonstration and is no longer loaded on the database-backed project/item pages.
- `docs/administrator-user-management.md` — Explains the persistent user-management feature and its tests.
- `README.md` — Describes the current project status and documentation.

## Page and navigation structure

The shared navigation is configured in `DashboardController`. Selecting **Usuaris** sends a normal browser request such as `/?page=users&role=admin`. The controller calls `AdminUsersController`, which loads users through `UserModel` and then includes the user view inside the shared layout. JavaScript does not take over page navigation or save account changes.

## Data sources

The user-management page reads teacher and student rows from the `users` table. It does not list or edit the administrator account. The database schema has `username`, `full_name`, `password_hash`, and `role`; it has no group field, so this form does not collect or store a student group. New passwords are stored as hashes.

`DemoData` still supplies sample data for dashboards and teacher/student previews. The admin project and item tables now read database rows, but the surrounding dashboards have not been converted from their sample data.

## User create, edit, and delete

The create and edit buttons open one Bootstrap modal. Its normal POST form sends the action and fields to PHP. `AdminUsersController` checks required fields, schema length limits, allowed teacher/student roles, duplicate usernames, and role changes for users already assigned to projects. `UserModel` runs prepared statements to insert or update the row. A blank password on edit keeps the existing hash. The server now requires an authenticated administrator session before processing a POST; the demo login does not create one, so the forms currently show an access error.

Deleting a teacher or student removes that user row. The schema's `ON DELETE CASCADE` rules also remove the user's project assignments and any grades linked through those assignments. The page asks for confirmation and explains this effect.

The login and role selector are still demonstrations. The `role=admin` URL is not authentication or access control. Project, item, assignment, and grade creation details are in `docs/create-operations.md`.

## Try the user-management interactions

1. Configure `.env`, create the database with `database/schema.sql`, and load `database/seed.sql`. Make sure PHP has the `pdo_mysql` extension and that `php tests/check_database_connection.php` succeeds.
2. Start PHP with `php -S 127.0.0.1:8000 -t public` and open `http://127.0.0.1:8000/?page=users&role=admin`.
3. Switch between **Professorat** and **Alumnat** and search for an existing account.
4. Confirm the user list is rendered from MariaDB when PDO can connect.
5. Submit any create/edit/delete form using the demo login. PHP should return HTTP 403 and explain that a server-side authenticated administrator session is required.
6. After a future login feature populates a verified session, run the create, invalid-field, edit, and delete checks from `docs/administrator-user-management.md`.

Use a disposable development database for these steps. Deleting existing assigned accounts also deletes their linked assignments and grades.

## Limitations

PDO create handlers now exist for users, projects, items, assignments, and grades. The demonstration login still does not authenticate or populate the server session, so writes are blocked. Dashboards and other read views still use sample data. Database failures show a general Catalan message; technical details are written only to the server log. See `docs/create-operations.md` for the operation details and current blockers.
