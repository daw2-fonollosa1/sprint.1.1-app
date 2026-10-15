# Administrator User Management

This feature saves teacher and student accounts in MariaDB. The existing login and role selector remain demonstrations; the user-management page does not provide authentication or access control.

## Changed files

- `app/Controllers/DashboardController.php` calls the user-management controller when the administrator opens `?page=users&role=admin`.
- `app/Controllers/AdminUsersController.php` reads submitted form values, validates them, chooses create/edit/delete, and returns feedback for the view.
- `app/Models/UserModel.php` reads and changes `users` rows with PDO prepared statements.
- `app/Views/pages/admin/users.php` renders the database results and the create/edit/delete forms in the existing Bootstrap design.
- `public/assets/js/admin-users.js` filters server-rendered rows, fills the shared modal, and asks for delete confirmation. It does not store or change user records.
- `app/Views/layouts/main.php` no longer sends demo user JSON or loads the demo helper on the user-management page.
- `docs/administrator-interface.md` describes which administrator areas are persistent and which still use sample data.
- `README.md` lists the new model, controller, and documentation and updates the project status.

The existing PDO class in `app/Core/Database.php` and settings in `config/database.php` are reused. The existing `users` table in `database/schema.sql` is also reused; neither was changed for this feature.

## How a request reaches MariaDB

The browser opens the normal PHP URL `/?page=users&role=admin`. `DashboardController` selects the user page and calls `AdminUsersController`. That controller opens the existing PDO connection, checks any POST action, and asks `UserModel` to read or change the database. The controller then renders `users.php` inside the shared PHP layout.

Forms use ordinary POST requests and a redirect after successful changes. The browser is not a single-page app, and JavaScript does not save account data. The redirect makes PHP load the latest rows from MariaDB after create, edit, or delete.

## Fields and validation

The current table provides `username`, `full_name`, `password_hash`, and `role`. The page manages only `teacher` and `student` accounts; it cannot edit or delete the administrator. A password is required when creating an account. On edit, a blank password keeps the stored hash, while a new password is hashed with PHP's `password_hash()` before it is saved.

The project requirements say that user fields have not yet been fully specified. The existing database schema is the source for this feature's fields. The old browser prototype had a student group input, but the schema has no group column, so the persistent form does not collect or invent one.

The controller checks that name and username are present, that their lengths fit the schema, that the role is teacher or student, and that a username is not already used. It also prevents changing the role of an account that has project assignments. The schema does not enforce user-role matching inside the assignment tables, so this check avoids making existing assignments inconsistent.

## Create, edit, and delete

- **Create:** PHP validates the submitted fields, checks username uniqueness, hashes the new password, and inserts the row. The database generates the ID.
- **Edit:** PHP checks that the selected row is a teacher or student, validates the new values, and updates the row. A password hash is changed only when a new password is entered.
- **Delete:** PHP checks that the selected row is manageable and deletes it. The schema uses `ON DELETE CASCADE`, so linked project assignments and grades are deleted too. The page warns about this before submitting.

Values from the form are passed to SQL with `prepare()` and `execute()`. User text is escaped when PHP places it in HTML. If a database operation fails, the page shows a general Catalan message; technical details are kept out of the interface and sent to the server log.

## Local setup and manual checks

1. Copy `.env.example` to `.env` and enter local MariaDB settings. `.env` is ignored by Git.
2. Create the schema and development sample rows:

   ```bash
   mariadb -u root -p < database/schema.sql
   mariadb -u root -p daw2_project_manager < database/seed.sql
   ```

3. Confirm PDO can connect, then start the PHP development server:

   ```bash
   php tests/check_database_connection.php
   php -S 127.0.0.1:8000 -t public
   ```

4. Open `http://127.0.0.1:8000/?page=users&role=admin`.
5. Create a test teacher with a unique username and a temporary password. The page should show a Catalan success message; reloading should keep the row.
6. Try an empty name, username, or password on create, then try a username already used by a seeded account. The modal should show a Catalan validation message and no extra row should be created.
7. Edit the test user's name, username, or role, save, and reload. The changes should remain. Leave the password blank and the existing password hash should be preserved.
8. Try changing the role of a seeded user assigned to a project. PHP should reject that change. Delete the test user and reload; it should no longer appear.

Use a disposable development database. Deleting an assigned account also deletes its linked assignments and grades.

There is no automated test suite for administrator user CRUD yet. In this environment, the PHP `pdo_mysql` extension and MariaDB client are unavailable, so the database interactions must be exercised with the checklist on a machine where MariaDB and `pdo_mysql` are available.

## Still outside this feature

This issue only persists administrator-managed teacher and student accounts. Project and evaluable-item CRUD, assignments, grades, and the other dashboards still use sample data. Login still accepts prototype inputs and does not check the `users` table. The `role=admin` URL is not real authentication or authorization.
