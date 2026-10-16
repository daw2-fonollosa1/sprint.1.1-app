# PHP Login and Sessions

This document explains how a user signs in to the PHP application. PHP checks the account in MariaDB, compares the entered password with its stored hash, and keeps the verified account ID and role in a server-side session. The role in the URL is never used as proof of identity.

## Files changed

- `app/Controllers/LoginController.php` — Validates the login form, verifies the account password, stores the verified ID and role in the session, redirects to the correct dashboard, and signs the user out.
- `app/Models/UserModel.php` — Adds a prepared query that finds an account by username. The login controller uses the returned password hash; views never receive it.
- `public/index.php` — Starts the PHP session, creates a form token, and routes login and logout requests.
- `app/Views/pages/login.php` — Shows the Catalan login form, server feedback, and a hidden form token.
- `app/Views/partials/navigation.php` — Shows a sign-out button that submits a normal PHP form with the form token. The old role preview selector was removed so a signed-in user cannot switch the displayed role from the navigation.
- `app/Controllers/DashboardController.php` — Removes data used only by the old role preview selector. It still checks that the session role matches the requested role page.
- `app/Views/layouts/main.php` — Stops loading the JavaScript file that only controlled the removed role selector. Dashboard pages remain rendered by PHP.
- `public/assets/js/app.js` — Removed because it only submitted the old role preview form; the form no longer exists.
- `app/Views/pages/placeholder.php` — Describes an unfinished section without claiming that login is simulated.
- `database/seed.sql` — Gives development accounts a known test password hash. The SQL still stores only a hash, not the password text.
- `README.md` and other role/database guides — Updated to describe the real database login and PHP session.
- `docs/login-implementation.md` — This guide.

## How a login request works

1. `public/index.php` starts the PHP session and makes a random form token available.
2. The login form sends the username, password, and token to PHP with a normal `POST` request. `public/assets/js/login.js` checks that the required fields are filled in the browser, but it does not decide whether the credentials are correct or navigate to a dashboard.
3. `LoginController` checks the token and validates the fields again on the server.
4. `UserModel::findByUsername()` runs a prepared SQL query against `users`. It returns the account ID, database role, and password hash.
5. PHP's `password_verify()` compares the entered password with that hash. The plain password is not saved.
6. If the credentials are correct, PHP changes the session ID and stores only the verified user ID and database role in `$_SESSION['user']`.
7. PHP redirects to the matching server-rendered dashboard: `administrator` goes to `role=admin`, while `teacher` and `student` keep their matching role names.
8. `DashboardController` checks the session role and confirms that the same account and role still exist in MariaDB before it loads that role's data.

An unknown username and an incorrect password show the same Catalan message. Database details are written to the server log; the browser receives a general message. The sign-out form sends a `POST` request, checks the same type of form token, clears the session, and redirects back to login.

## Development test accounts

The accounts below are created by `database/seed.sql`. They all use the development-only password `12345`:

| Username | Role | Expected destination |
| --- | --- | --- |
| `admin-demo` | Administrator | `/?page=dashboard&role=admin` |
| `teacher-marta` | Teacher | `/?page=dashboard&role=teacher` |
| `teacher-joan` | Teacher | `/?page=dashboard&role=teacher` |
| `student-marc` | Student | `/?page=dashboard&role=student` |
| `student-laura` | Student | `/?page=dashboard&role=student` |

These are sample accounts for a local development database, not production credentials. Re-importing the seed resets the password hash only for rows with these sample usernames. It does not overwrite other account fields or delete rows. The password is intentionally simple for this classroom prototype and must not be used outside a local development database.

## Test examples

| Username | Password | Expected result |
| --- | --- | --- |
| `admin-demo` | `12345` | Login succeeds and PHP opens the administrator dashboard. |
| `teacher-marta` | `12345` | Login succeeds and PHP opens the teacher dashboard. |
| `student-marc` | `12345` | Login succeeds and PHP opens Marc's student dashboard. |
| `student-marc` | `wrong-password` | Login stays on the form and shows “L’usuari o la contrasenya no són correctes.” |
| `unknown-user` | `12345` | The same generic incorrect-credentials message appears. |
| blank | `12345` | The username field shows its required-field message. |
| `admin-demo` | blank | The password field shows its required-field message. |

After signing in, try changing `role=admin` to another value in the address bar. PHP must reject the role mismatch instead of displaying another account's dashboard. Use **Tanca la sessió** to end the session.

## Run the application

1. Install and start MariaDB, and enable PHP's PDO MySQL extension (`pdo_mysql`).
2. Configure `.env` using `.env.example`.
3. Create the database and sample data from the repository root:

   ```bash
   mariadb -u root -p < database/schema.sql
   mariadb -u root -p daw2_project_manager < database/seed.sql
   ```

   The seed resets the password hashes for the five known sample usernames and skips duplicate sample records without overwriting them. It does not delete rows.
4. Check the database connection:

   ```bash
   php tests/check_database_connection.php
   ```

5. Start the PHP server and open the login page:

   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

   Visit `http://127.0.0.1:8000/` and use one of the development accounts above.

If login cannot connect, check that `pdo_mysql` appears in `php -m`, MariaDB is running, and the `.env` values match the local database. At implementation time this environment had neither PDO nor `pdo_mysql`, so a live database login could not be confirmed here.

## Limits

- There is no public registration or password reset. An administrator creates teacher and student accounts.
- The form does not have a “remember me” option. It uses the normal PHP session lifetime rather than creating a long-lived login cookie.
- The application uses PHP sessions and checks roles, but production deployment still needs HTTPS, strong account passwords, backups, and operational security measures such as login rate limiting.
- The seeded password is for local development only. Replace or remove sample accounts before using a production database.
- PHP renders page navigation and dashboard data. JavaScript is limited to the existing client-side required-field feedback; the app is not a JavaScript SPA.
