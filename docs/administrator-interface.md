# Administrator Interface

## Scope

The administrator pages read users, projects, evaluable items, assignments, and dashboard totals from MariaDB. The existing PHP forms for creating and managing data remain separate from this read work. All page navigation and HTML rendering are handled by PHP.

The login checks the password against the `users` table and stores the verified account ID and role in the PHP session. The administrator pages confirm that account and role in the database before loading administrator data.

## Files

- `app/Controllers/DashboardController.php` — Selects the requested page, checks the session role against MariaDB, and prepares dashboard data.
- `app/Controllers/AdminUsersController.php` — Loads teacher and student rows for the administrator's user table.
- `app/Controllers/ReadOperationsController.php` — Loads the existing project rows and current project assignments for the administrator page.
- `app/Controllers/CreateOperationsController.php` — Validates and processes project, item, and assignment creation forms.
- `app/Models/UserModel.php` — Reads user lists and verifies that a session user has the expected database role.
- `app/Models/ProjectModel.php` — Reads project rows.
- `app/Models/EvaluableItemModel.php` — Reads evaluable-item rows.
- `app/Models/ProjectAssignmentModel.php` — Reads the teachers, students, and items assigned to each project.
- `app/Models/GradeModel.php` — Counts individual grades for the administrator dashboard.
- `app/Views/layouts/main.php` — Shows safe access or database messages and includes the selected PHP view.
- `app/Views/partials/navigation.php` — Shows only the authenticated role's links and a sign-out form.
- `app/Views/pages/admin/dashboard.php` — Displays current project, user, item, and grade counts and a project list.
- `app/Views/pages/admin/users.php` — Displays database users with the existing role filters.
- `app/Views/pages/admin/projects.php` — Displays projects and the names assigned to each one.
- `app/Views/pages/admin/evaluable_items.php` — Displays evaluable items from MariaDB.
- `docs/read-operations.md` — Explains the complete read flow and testing steps.

## How administrator data is loaded

`public/index.php` starts a PHP session and sends the request to `DashboardController`. The controller checks the session role and then checks that the same user ID and role exist in MariaDB. It does not authorize a page from the `role` URL value.

For the dashboard, the controller calls the existing PDO models. The project, teacher, student, and item lists provide the displayed totals. `GradeModel` counts saved individual grade rows. On the project page, `ProjectAssignmentModel` loads assignment names from the three relationship tables.

Database values are escaped before PHP places them in HTML. Database errors are written to the server log; the page shows a general Catalan message.

## Test data and limitations

`database/seed.sql` contains development records for one administrator, two teachers, two students, projects, items, assignments, and grades. Sign in as `admin-demo` with the development password `12345` to open the administrator pages. A role in the URL cannot change the role stored in the session.

Configure MariaDB and `pdo_mysql`, then load `database/schema.sql` and `database/seed.sql` before expecting the administrator screens to display records.
