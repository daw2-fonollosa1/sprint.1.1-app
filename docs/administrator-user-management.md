# Administrator User Management

The administrator page reads teacher and student accounts from MariaDB and contains create, edit, and delete forms. Every POST change checks the authenticated server-side session role. Sign in as `admin-demo` with the local development password `12345` after importing the seed data.

## Files

- `app/Controllers/AdminUsersController.php` reads form values, validates account data, and calls the user model.
- `app/Controllers/ReadOperationsController.php` loads the teacher and student list before the page is rendered.
- `app/Models/UserModel.php` reads and changes the `users` table with PDO prepared statements. It hashes passwords with `password_hash()`, never returns password hashes to the view, and blocks deletion of users who still have project assignments.
- `app/Views/pages/admin/users.php` renders the database results and the create, edit, and delete forms.
- `public/assets/js/admin-users.js` filters rows, opens the shared modal, and asks for delete confirmation. JavaScript does not save account data.
- `app/Core/RoleAuthorization.php` checks the current session identity before PHP accepts a POST change.
- `public/index.php` starts the PHP session before routing requests.

## Request and validation flow

The browser submits a normal POST request to `/?page=users&role=admin`. `DashboardController` verifies the session and database role, then `ReadOperationsController` loads the user list. `AdminUsersController` checks the session role before processing any submitted action. The URL's `role=admin` value is only a page preview; it is not used to authorize the request. When an authenticated administrator session exists, PHP validates the fields and calls `UserModel`, which uses PDO prepared statements. Successful writes redirect back to the page.

The schema provides `username`, `full_name`, `password_hash`, and `role`. The page only creates teachers and students. It requires a password on account creation and stores only its hash. A blank password while editing means keep the existing hash. The student group is not stored because the schema has no group column.

The controller checks required values, database length limits, allowed roles, duplicate usernames, and role changes for users with project assignments. The database's unique username key is also checked by MariaDB.

## Login and role check

`LoginController` verifies the submitted password with the hash returned by `UserModel::findByUsername()`. After a successful check, PHP stores the database user ID and role in `$_SESSION['user']`. `DashboardController` and the user-management controller check that session role before they display or change administrator data. Changing `role=admin` in the address bar does not grant administrator access.

## Manual check

1. Configure `.env`, create the schema and seed data, and run `php tests/check_database_connection.php`.
2. Start PHP with `php -S 127.0.0.1:8000 -t public` and open `http://127.0.0.1:8000/?page=users&role=admin`.
3. Confirm the teacher/student rows are displayed when PDO can connect.
4. Create and edit a test teacher or student. Delete a user with no project assignments and confirm it remains absent after reloading. Try deleting an assigned user and confirm PHP blocks the action without removing that user's assignments or grades.
5. Sign out and sign in as a teacher or student. Opening the administrator user-management page must show the role access message, and POST changes must be rejected.

Use a disposable development database. User accounts with project assignments cannot be deleted: their assignments may be referenced by grades, and the database schema would otherwise cascade-delete that data. The administrator account is not listed as a manageable teacher or student and cannot be deleted through this form.
