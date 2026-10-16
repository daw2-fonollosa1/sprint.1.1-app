# Student Interface

## Scope

The student dashboard, project list, project details, and grade list read from MariaDB. The data is selected from the user ID and role in the server-side PHP session. A student ID from the URL or a form is never used to choose whose grades are displayed.

## Files

- `app/Controllers/DashboardController.php` — Verifies the session account against the `users` table before showing a student page.
- `app/Controllers/ReadOperationsController.php` — Passes the verified student ID to the student data model and prepares the selected page's data.
- `app/Models/StudentDashboardData.php` — Reads assigned projects, project items, the selected student's individual grades, and averages.
- `app/Views/pages/student/dashboard.php` — Shows the student's assigned project, graded-item, and pending-item counts.
- `app/Views/pages/student/projects.php` — Lists the student's assigned projects and item counts.
- `app/Views/pages/student/project_details.php` — Shows one assigned project's information, its items, individual teacher grades, and per-item average.
- `app/Views/pages/student/grades.php` — Shows the same student's grades and filters by one of that student's projects.
- `app/Views/layouts/main.php` — Displays an access message instead of including a role view when no valid session is available.
- `docs/read-operations.md` — Explains the read flow, privacy filter, average calculation, and manual checks.

## Privacy filter

The controller gets the user ID from `RoleAuthorization::currentUser()` and confirms that the same ID has the `student` role in MariaDB. `StudentDashboardData` starts its project query from `student_projects` and uses a prepared `student_id` value. Its grade queries also filter by that same ID.

The project filter only accepts a project already in the student's assigned project list. The model does not read a student ID from `$_GET`, `$_POST`, or JavaScript. The login verifies the account in MariaDB and creates the server-side session from the returned database ID and role.

## How averages are calculated

The model asks MariaDB to calculate `AVG(grade_value)` grouped by `project_id` and `evaluable_item_id`, with the authenticated `student_id` as a filter. This means a grade from a different project or item is never included. Individual teacher grades are displayed beside the average.

The seed includes these checks:

```text
Marc, PRJ-001, Documentació: (8.5000 + 9.0000) / 2 = 8.75
Marc, PRJ-001, Proves: 7.5000 / 1 = 7.5
Marc, PRJ-003, Proves: (8.0000 + 9.0000) / 2 = 8.5
Laura, PRJ-002, Proves: 8.0000 / 1 = 8.0
```

The requirements do not set a grading scale or a rounding rule. The view trims trailing zeroes from MariaDB's decimal result but does not round it again in PHP.

## Manual test checklist

There are no automated read-operation tests in the repository. The seed data can be used for these checks after MariaDB and `pdo_mysql` are available. Sign in as `student-marc` or `student-laura` with the development password `12345`:

1. Sign in as administrator and open `/?page=dashboard&role=admin`. Check that the user, project, item, and grade totals match the database.
2. Sign in as `teacher-marta` or `teacher-joan`. Confirm the dashboard and project pages show only the three projects assigned to that account, with their associated students and items.
3. Sign in as `student-marc`. Confirm the student sees `PRJ-001` and `PRJ-003`, four assigned items, three graded items, and one pending item.
4. On Marc's grade page, check that `PRJ-001 / Documentació` shows `8.5`, `9.0`, and the mean `8.75`. Confirm `PRJ-003 / Proves` is averaged separately as `8.5`.
5. Sign in as `student-laura`. Confirm only `PRJ-002 / Proves` and its `8.0` grade are shown.
6. While signed in as Marc, request `/?page=grades&role=student&project_id=2` or add `student_id=5` to the URL. The first request should show an invalid-project filter message; the student ID parameter must not change the selected account or reveal Laura's grade.
7. Remove or change the PHP session role and confirm the controller shows an access message without rendering another role's records.

Student filtering uses the ID and role in the authenticated PHP session. The user ID submitted in a URL or form cannot select another student's records. Production deployment still needs HTTPS and the usual operational security measures; the seeded development accounts must not be used in production.
