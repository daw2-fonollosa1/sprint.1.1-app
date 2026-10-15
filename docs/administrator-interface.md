# Administrator Interface Prototype

## Scope

The administrator area contains a dashboard and pages for users, projects, and evaluable items. The user page separates teachers from students and now reads and changes those accounts in MariaDB. The project and evaluable-item pages remain browser-only demonstrations.

PHP renders each page. User-management forms are submitted to PHP and saved with PDO, so account changes remain after reloading. JavaScript filters the rendered user rows and opens the create/edit modal. Project and evaluable-item changes still affect only in-memory sample data and are not saved.

Visible interface text is in Catalan. Source code, comments, and this document are in English.

## Files

- `app/Controllers/DashboardController.php` — Defines role navigation, selects views, and calls the administrator user controller for the user page.
- `app/Controllers/AdminUsersController.php` — Validates account forms, calls the user model, redirects after successful actions, and returns safe feedback.
- `app/Models/DemoData.php` — Keeps sample users, projects, evaluable items, and grades for the dashboard and demonstration pages.
- `app/Models/UserModel.php` — Loads, creates, updates, and deletes teacher/student records with prepared PDO statements.
- `app/Views/layouts/main.php` — Includes shared navigation and assets. It sends sample JSON only to sections that still use the browser demonstrations.
- `app/Views/pages/admin/dashboard.php` — Shows sample totals and a short list of projects.
- `app/Views/pages/admin/users.php` — Renders users from MariaDB and provides role filters and create, edit, and delete forms.
- `app/Views/pages/admin/projects.php` — Provides demonstration project controls and assignment controls.
- `app/Views/pages/admin/evaluable_items.php` — Provides demonstration evaluable-item controls.
- `public/assets/js/admin-users.js` — Filters the PHP-rendered rows, opens the account modal, and confirms delete submissions. PHP performs all database changes.
- `public/assets/js/admin-common.js` — Supports the project and evaluable-item demonstrations.
- `docs/administrator-user-management.md` — Explains the persistent user-management feature and its tests.
- `README.md` — Describes the current project status and documentation.

## Page and navigation structure

The shared navigation is configured in `DashboardController`. Selecting **Usuaris** sends a normal browser request such as `/?page=users&role=admin`. The controller calls `AdminUsersController`, which loads users through `UserModel` and then includes the user view inside the shared layout. JavaScript does not take over page navigation or save account changes.

## Data sources

The user-management page reads teacher and student rows from the `users` table. It does not list or edit the administrator account. The database schema has `username`, `full_name`, `password_hash`, and `role`; it has no group field, so this form does not collect or store a student group. New passwords are stored as hashes.

`DemoData` still supplies sample data for the administrator dashboard, projects, evaluable items, and teacher/student previews. Those sections are separate from persistent administrator user management.

## User create, edit, and delete

The create and edit buttons open one Bootstrap modal. Its normal POST form sends the action and fields to PHP. `AdminUsersController` checks required fields, schema length limits, allowed teacher/student roles, duplicate usernames, and role changes for users already assigned to projects. `UserModel` runs prepared statements to insert or update the row. A blank password on edit keeps the existing hash. Successful requests redirect back to the page and load the current database rows again.

Deleting a teacher or student removes that user row. The schema's `ON DELETE CASCADE` rules also remove the user's project assignments and any grades linked through those assignments. The page asks for confirmation and explains this effect.

The login and role selector are still demonstrations. The `role=admin` URL is not authentication or access control.

## Try the user-management interactions

1. Configure `.env`, create the database with `database/schema.sql`, and load `database/seed.sql`. Make sure PHP has the `pdo_mysql` extension and that `php tests/check_database_connection.php` succeeds.
2. Start PHP with `php -S 127.0.0.1:8000 -t public` and open `http://127.0.0.1:8000/?page=users&role=admin`.
3. Switch between **Professorat** and **Alumnat** and search for an existing account.
4. Create a test teacher with a unique username and a temporary password. Confirm the success message, reload the page, and verify it remains in the list.
5. Try submitting a blank name, blank username, blank role, blank creation password, or a username already used by a seeded account. The form should reopen and show Catalan validation messages.
6. Edit the test user, save a new name or username, reload, and check that the change remains. Leave the password blank to keep its hash; enter a new temporary password to replace the hash.
7. Try changing the role of a seeded account that has project assignments. PHP should reject the change so its assignments do not become inconsistent.
8. Delete the test user, confirm the prompt, reload, and verify that the account is gone.

Use a disposable development database for these steps. Deleting existing assigned accounts also deletes their linked assignments and grades.

## Limitations

Only administrator teacher/student user management is connected to MariaDB. Projects, evaluable items, assignments, grades, dashboards, and other role pages still use sample data. The application has no real login, authentication, or access control. Database failures show a general Catalan message; technical details are written only to the server log.
