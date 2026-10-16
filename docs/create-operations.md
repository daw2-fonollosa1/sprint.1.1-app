# Application Create Operations

This change adds PHP/PDO insert operations for users, projects, evaluable items, project assignments, and teacher grades. The forms use ordinary HTML requests. JavaScript arrays and browser storage do not save these records.

## Files changed

- `public/index.php` starts a PHP session before dispatching the request.
- `app/Core/RoleAuthorization.php` reads a verified user ID and role from `$_SESSION['user']`. It never trusts `role` from the URL or a hidden form input.
- `app/Controllers/CreateOperationsController.php` validates project, item, assignment, and grade forms, checks permissions, and handles feedback and redirects.
- `app/Controllers/ReadOperationsController.php` loads the projects, users, items, and grade form choices shown beside those forms.
- `app/Controllers/AdminUsersController.php` now requires a server-side administrator role before accepting any user-management POST action.
- `app/Controllers/DashboardController.php` sends the relevant admin and teacher forms through the create controller.
- `app/Models/UserModel.php` provides the user operations used by the controllers. Its create method hashes account passwords.
- `app/Models/ProjectModel.php` reads project lists and inserts projects.
- `app/Models/EvaluableItemModel.php` reads item lists and inserts items.
- `app/Models/ProjectAssignmentModel.php` creates teacher, student, and item links.
- `app/Models/GradeModel.php` provides the teacher's grade form choices and inserts one individual grade.
- `app/Views/pages/admin/projects.php` contains the project creation form, database project list, and three assignment forms.
- `app/Views/pages/admin/evaluable_items.php` contains the item creation form and database list.
- `app/Views/pages/admin/users.php` shows the access message when the server cannot verify an administrator.
- `app/Views/pages/teacher/projects.php` contains the teacher project creation form and a list of projects assigned to the session teacher.
- `app/Views/pages/teacher/grades.php` contains the project, student, item, and grade forms. It no longer stores grade changes in JavaScript.
- `app/Views/layouts/main.php` stops loading the sample project/item JavaScript on the new PHP forms.
- `README.md` describes the create handlers and authenticated role checks.
- `docs/administrator-user-management.md` now reflects the server-side role check.
- `docs/create-operations.md` explains these operations and their current limits.

## How a request reaches PHP and PDO

1. The browser submits a normal `POST` form to the public front controller, `public/index.php`.
2. `DashboardController` calls `CreateOperationsController` to process a create form submission and `ReadOperationsController` to load the rows shown by the PHP page. The view receives both results.
3. The controller reads form values, checks required fields, formats, IDs, and the role from the PHP session.
4. A model uses the existing `Database::getConnection()` PDO connection. Values are passed to `prepare()` and `execute()` instead of being joined into SQL strings.
5. On success, PHP responds with a `303` redirect. The next page request reads the database again, so a refresh does not repeat the insert.

Database exceptions are written to the server log. The browser receives a general Catalan message without SQL text, credentials, or a stack trace.

## Each create operation

### Users

The existing administrator form sends a teacher or student to `AdminUsersController`. The controller validates the name, username, role, and new password. `UserModel` inserts one `users` row with a PHP `password_hash()` value; the plain password is never put into SQL. The unique username index rejects duplicate accounts. User fields in the project requirements are not fully specified, so the form follows the current database columns.

### Projects

An administrator or teacher can submit code, name, and description. Code must be unique and must fit the schema. If a teacher creates a project, the model also inserts the teacher's row into `teacher_projects` in the same transaction. The schema has no `created_by` column; this uses the existing assignment relationship so the creating teacher can access the project. Administrator-created projects are not automatically assigned to a teacher.

### Evaluable items

The administrator form submits code, name, and description. PHP checks the required values and schema lengths, and MariaDB's unique code constraint prevents duplicate items.

### Project assignments

The administrator page has separate forms for teachers, students, and evaluable items. Each form chooses a project and one or more records. The controller checks that IDs exist and have the expected type. `ProjectAssignmentModel` inserts the selected links in a transaction. If one link is invalid or already exists, the complete group of inserts is rolled back.

The requirements say that the administrator has full control, but they do not explicitly state who manages assignments. This version uses the administrator role for assignment creation and records that assumption here; the team should confirm it.

### Grades

The teacher form only offers projects assigned to the authenticated teacher, students assigned to the selected project, and evaluable items linked to that project. PHP checks those relationships again before inserting. Each insert includes the teacher ID from the session. A teacher's duplicate grade for the same project, student, and item is rejected by the schema's unique key; a different teacher gets a separate grade row.

`grade_value` is `DECIMAL(10,4)`, so the server accepts up to six integer digits and four decimal digits. The requirements and decision log do not define the grading scale or rounding rule, so this code does not impose a range or calculate an average.

## Authentication and permissions

The login verifies a user's password hash from MariaDB and stores the ID and role returned by that row in the server-side session:

```php
$_SESSION['user'] = [
    'id' => $verifiedUserId,
    'role' => $verifiedDatabaseRole,
];
```

The session role comes from the verified `users` row. Do not set it from `$_GET`, `$_POST`, JavaScript, or a hidden input. Each create operation checks that session role before making database changes.

## Testing

PHP syntax checks passed for all changed PHP controllers, models, and views. A database integration check could not run in this environment: PHP does not have the PDO class or `pdo_mysql` module, the `mariadb` client is unavailable, and Docker Compose reports that its configuration is empty. No database insert is claimed as tested here.

Earlier controller checks confirmed that requests without a verified session were rejected before writing. The login now creates that session after checking MariaDB. Repeat the valid and invalid form checks below using an authenticated account; this environment has no PDO MySQL driver, so live database submissions cannot be run here.

After MariaDB and `pdo_mysql` are available, import the seed and sign in with an account in `docs/login-implementation.md`. Use a development database for these checks:

| Operation | Valid example | Invalid example and expected result |
| --- | --- | --- |
| Create user | New unique username, non-empty full name, role `teacher` or `student`, and a temporary test password | Leave a required field empty or use seeded username `teacher-marta`; PHP shows validation and does not insert a row |
| Create project | Code `PRJ-TEST-01`, name `Projecte de prova`, and a description | Leave code/name/description empty or use existing code `PRJ-001`; no duplicate project is inserted |
| Create evaluable item | Code `IT-TEST-01`, name `Proves de creació`, and a description | Leave a required field empty or use existing code `IT-001`; no duplicate item is inserted |
| Assign teachers, students, or items | Choose existing project 1 and a matching record such as teacher 2, student 4, or item 1 | Submit no record, choose an ID of the wrong type, or repeat an existing link; invalid groups are not partially inserted |
| Create grade | With teacher ID 2, choose project 1, student 4, item 2, and grade `7.125` | Use text such as `abc`, more than six integer digits, more than four decimal digits, or repeat teacher 2/project 1/student 4/item 1; PHP rejects it and does not overwrite another grade |

Check persistence by reloading the relevant page or querying the corresponding table. For assignments, query the matching junction table. For grades, query `grades` and verify that `teacher_id` identifies the author.

There are no automated create-operation tests in the repository yet. Real valid and invalid database form submissions require MariaDB and PHP's `pdo_mysql` extension. Use the test accounts described in `docs/login-implementation.md` after loading the seed data.

## Scope limits

This Issue adds create operations and only the small database reads needed for form choices and visible lists. It does not add project/item/assignment/grade update or delete operations or change the role permissions defined by the project requirements.
