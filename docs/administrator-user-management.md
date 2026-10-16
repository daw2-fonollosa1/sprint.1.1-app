# Administrator User Management

The administrator page reads teacher and student accounts from MariaDB and contains create, edit, and delete forms. Every POST change now checks a server-side session role. The current demo login does not create an authenticated session, so the page is read-only until real authentication is implemented.

## Files

- `app/Controllers/AdminUsersController.php` reads form values, validates account data, and calls the user model.
- `app/Models/UserModel.php` reads and changes the `users` table with PDO prepared statements. It hashes passwords with `password_hash()` and never returns password hashes to the view.
- `app/Views/pages/admin/users.php` renders the database results and the create, edit, and delete forms.
- `public/assets/js/admin-users.js` filters rows, opens the shared modal, and asks for delete confirmation. JavaScript does not save account data.
- `app/Core/RoleAuthorization.php` checks the current session identity before PHP accepts a POST change.
- `public/index.php` starts the PHP session before routing requests.

## Request and validation flow

The browser submits a normal POST request to `/?page=users&role=admin`. `DashboardController` calls `AdminUsersController`, which checks the session role before processing any action. The URL's `role=admin` value is only a page preview; it is not used to authorize the request. When an authenticated administrator session exists, PHP validates the fields and calls `UserModel`, which uses PDO prepared statements. Successful writes redirect back to the page.

The schema provides `username`, `full_name`, `password_hash`, and `role`. The page only creates teachers and students. It requires a password on account creation and stores only its hash. A blank password while editing means keep the existing hash. The student group is not stored because the schema has no group column.

The controller checks required values, database length limits, allowed roles, duplicate usernames, and role changes for users with project assignments. The database's unique username key is also checked by MariaDB.

## Current authentication blocker

The demonstration login accepts sample names and does not read the `users` table or create a server-side identity. Therefore, the create, edit, and delete POST forms receive HTTP 403. The page can still be viewed when the database is available. A future login must verify a password hash and set a session identity such as `$_SESSION['user'] = ['id' => 1, 'role' => 'administrator']`; the role must come from the verified database row.

## Manual check

1. Configure `.env`, create the schema and seed data, and run `php tests/check_database_connection.php`.
2. Start PHP with `php -S 127.0.0.1:8000 -t public` and open `http://127.0.0.1:8000/?page=users&role=admin`.
3. Confirm the teacher/student rows are displayed when PDO can connect.
4. Try a create, edit, or delete POST using the demo login. PHP must reject the request with HTTP 403 and a Catalan access message.
5. After real login/session support is added, test valid and invalid creation, edit, and delete operations using a disposable database. Confirm saved rows remain after reload.

Use a disposable development database. Deleting a user also removes linked assignments and grades because the schema uses cascading foreign keys.
