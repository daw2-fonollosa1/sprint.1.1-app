# Application Create Operations

This change adds PHP/PDO insert operations for users, projects, evaluable items, project assignments, and teacher grades. The forms use ordinary HTML requests. JavaScript arrays and browser storage do not save these records.

## Files changed

- `public/index.php` starts a PHP session before dispatching the request.
- `app/Core/RoleAuthorization.php` reads a verified user ID and role from `$_SESSION['user']`. It never trusts `role` from the URL or a hidden form input.
- `app/Controllers/CreateOperationsController.php` validates project, item, assignment, and grade forms, checks permissions, and handles feedback and redirects.
- `app/Controllers/AdminUsersController.php` now requires a server-side administrator role before accepting any user-management POST action.
- `app/Controllers/DashboardController.php` sends the relevant admin and teacher forms through the create controller.
- `app/Models/UserModel.php` adds role-specific reads for assignment choices. Its existing create method still hashes account passwords.
- `app/Models/ProjectModel.php` reads project lists and inserts projects.
- `app/Models/EvaluableItemModel.php` reads item lists and inserts items.
- `app/Models/ProjectAssignmentModel.php` creates teacher, student, and item links.
- `app/Models/GradeModel.php` reads only the dropdown choices needed by a teacher and inserts one individual grade.
- `app/Views/pages/admin/projects.php` contains the project creation form, database project list, and three assignment forms.
- `app/Views/pages/admin/evaluable_items.php` contains the item creation form and database list.
- `app/Views/pages/admin/users.php` shows the access message when the server cannot verify an administrator.
- `app/Views/pages/teacher/projects.php` contains the teacher project creation form and a list of projects assigned to the session teacher.
- `app/Views/pages/teacher/grades.php` contains the project, student, item, and grade forms. It no longer stores grade changes in JavaScript.
- `app/Views/layouts/main.php` stops loading the sample project/item JavaScript on the new PHP forms.
- `README.md` describes the new handlers and the authentication blocker.
- `docs/administrator-user-management.md` now reflects the server-side role check.
- `docs/create-operations.md` explains these operations and their current limits.

## How a request reaches PHP and PDO

1. The browser submits a normal `POST` form to the public front controller, `public/index.php`.
2. `DashboardController` selects the existing PHP page and calls the relevant controller.
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

## Authentication blocker

The current login accepts demo usernames and only changes the role preview in the URL. It does not verify a password, read a user row, or create a server-side identity. Every create form therefore receives HTTP 403 until a real login has verified the user and set a session such as:

```php
$_SESSION['user'] = [
    'id' => $verifiedUserId,
    'role' => $verifiedDatabaseRole,
];
```

The session role must come from the verified `users` row. Do not set it from `$_GET`, `$_POST`, JavaScript, or a hidden input. Creating authentication and access control is outside this Issue, so the handlers are implemented but cannot currently complete a write from the demo login.

## Testing

PHP syntax checks passed for all changed PHP controllers, models, and views. A database integration check could not run in this environment: PHP does not have the PDO class or `pdo_mysql` module, the `mariadb` client is unavailable, and Docker Compose reports that its configuration is empty. No database insert is claimed as tested here.

I ran controller-level POST checks with an empty PHP session and client-supplied role values. Both a project create request and a user create request returned HTTP 403 before any write. I also rendered the changed forms with small sample fixtures and exercised the project required-field and grade precision validators with valid and invalid values; those checks passed without writing to a database. A browser submission with the demo login should show the same Catalan access message.

After MariaDB, `pdo_mysql`, and verified login sessions are available, use a disposable database for these checks:

| Operation | Valid example | Invalid example and expected result |
| --- | --- | --- |
| Create user | New unique username, non-empty full name, role `teacher` or `student`, and a temporary test password | Leave a required field empty or use seeded username `teacher-marta`; PHP shows validation and does not insert a row |
| Create project | Code `PRJ-TEST-01`, name `Projecte de prova`, and a description | Leave code/name/description empty or use existing code `PRJ-001`; no duplicate project is inserted |
| Create evaluable item | Code `IT-TEST-01`, name `Proves de creació`, and a description | Leave a required field empty or use existing code `IT-001`; no duplicate item is inserted |
| Assign teachers, students, or items | Choose existing project 1 and a matching record such as teacher 2, student 4, or item 1 | Submit no record, choose an ID of the wrong type, or repeat an existing link; invalid groups are not partially inserted |
| Create grade | With teacher ID 2, choose project 1, student 4, item 2, and grade `7.125` | Use text such as `abc`, more than six integer digits, more than four decimal digits, or repeat teacher 2/project 1/student 4/item 1; PHP rejects it and does not overwrite another grade |

Check persistence by reloading the relevant page or querying the corresponding table. For assignments, query the matching junction table. For grades, query `grades` and verify that `teacher_id` identifies the author.

There are no automated create-operation tests in the repository yet. Real valid and invalid database form submissions remain untested because this environment does not have PDO MySQL/MariaDB or an authenticated login.

## Scope limits

This Issue adds create operations and only the small database reads needed for form choices and visible lists. It does not add project/item/assignment/grade update or delete operations, does not connect the database-backed grade rows to the student grade view, and does not implement authentication, session login, or general access control. Existing administrator user edit/delete handlers remain from their earlier issue, but their POST requests are also blocked by the new server-side role check until authentication exists.
