# Read Operations

## Purpose

The role dashboards now read their records from MariaDB through the existing PDO connection. PHP still renders each page and its navigation. JavaScript does not choose database records or run the page navigation.

## Files changed

- `app/Controllers/DashboardController.php` — Chooses the PHP page, checks the session identity, and coordinates the read and create controllers.
- `app/Controllers/ReadOperationsController.php` — Collects page data by calling the existing PDO models. It does not process create forms.
- `app/Models/UserModel.php` — Reads administrator user lists and confirms that a session ID has the expected role in MariaDB.
- `app/Models/ProjectAssignmentModel.php` — Adds a read method for the teachers, students, and evaluable items already linked to each project.
- `app/Models/GradeModel.php` — Counts all individual grades for the administrator summary and provides project, student, and item choices to the existing teacher form.
- `app/Models/TeacherDashboardData.php` — Collects the projects, students, items, grade count, and pending grade count for one teacher.
- `app/Models/StudentDashboardData.php` — Collects one student's assigned projects and items, individual grades, and per-item averages.
- `app/Controllers/CreateOperationsController.php` — Processes create form submissions and returns feedback. It no longer loads the project, item, student, or teacher lists for the views.
- `app/Controllers/AdminUsersController.php` — Keeps the existing user create, edit, and delete behavior. It receives the user list prepared by `ReadOperationsController`.
- `app/Views/layouts/main.php` — Shows safe Catalan access or database errors and includes the selected PHP view when data can be loaded.
- `app/Views/pages/admin/dashboard.php` — Displays current counts and projects from MariaDB.
- `app/Views/pages/admin/projects.php` — Displays each project's current teacher, student, and item assignments.
- `app/Views/pages/teacher/dashboard.php` — Displays counts and projects assigned to the signed-in teacher.
- `app/Views/pages/teacher/projects.php` — Displays the teacher's assigned projects.
- `app/Views/pages/teacher/project_details.php` — Displays students and items for one of the teacher's assigned projects.
- `app/Views/pages/teacher/students.php` — Displays the unique students associated with the teacher's projects.
- `app/Views/pages/teacher/grades.php` — Keeps the existing PHP grade form and selects only choices returned for that teacher.
- `app/Views/pages/student/dashboard.php` — Displays project and grade counts for the signed-in student.
- `app/Views/pages/student/projects.php` — Displays only that student's assigned projects and items.
- `app/Views/pages/student/project_details.php` — Displays information and grade details for one assigned project.
- `app/Views/pages/student/grades.php` — Displays only the signed-in student's individual grades and averages.
- `public/assets/js/admin-common.js`, `admin-items.js`, `admin-projects.js`, `teacher.js`, and `app/Models/DemoData.php` — Removed because they held unused or active hard-coded demonstration records that are replaced by PHP database reads. The shared navigation and form behavior continue to use the remaining JavaScript files.
- `README.md`, `docs/administrator-interface.md`, `docs/administrator-user-management.md`, `docs/create-operations.md`, `docs/login-implementation.md`, `docs/teacher-interface.md`, `docs/student-interface.md`, and `docs/database-connection.md` — Updated so their descriptions match the database-backed reads and authentication limitation.

The existing `ProjectModel.php`, `EvaluableItemModel.php`, `app/Views/pages/admin/users.php`, and `app/Views/pages/admin/evaluable_items.php` already provided the reads needed by their screens, so they are reused without changes. Their existing controllers pass the database rows to the views.

## How a page gets its data

1. `public/index.php` starts the PHP session and sends non-login page requests to `DashboardController`.
2. The controller reads the account ID and role from `RoleAuthorization::currentUser()`. A `role` URL value only selects the navigation preview; it is not used as proof of identity.
3. `UserModel::findByIdAndRole()` checks that the session account still exists in the `users` table with the same role.
4. On a create-form page, `CreateOperationsController` handles a submitted form first. It returns feedback and submitted values, but it does not load the page lists.
5. `ReadOperationsController` passes the existing PDO connection and authenticated account ID to the relevant model.
6. The model runs SQL and returns rows as PHP arrays. Views loop through those arrays and render ordinary HTML.
7. Views use `htmlspecialchars()` for database text. IDs used in links are converted to integers. Database exceptions go to the server log, while the page displays a general message in Catalan.

The administrator summary counts projects, teachers, students, evaluable items, and individual grade rows. The administrator project table reads names from `teacher_projects`, `student_projects`, and `project_evaluable_items`.

For a teacher, `TeacherDashboardData` begins with `teacher_projects`. Its student query joins those assigned projects to `student_projects`; its item query joins them to `project_evaluable_items`. The teacher's project ID is a prepared SQL value. Project details are found only within the resulting assigned project list.

For a student, the project query begins with `student_projects` for the authenticated student's ID. The grade queries also use that ID in a prepared `WHERE` condition. A project filter is accepted only if its ID exists in this student's already-loaded project list. No student ID from a URL, form, or JavaScript is used to choose whose grades are read.

## Grade averages

Each teacher grade remains an individual row in `grades`. MariaDB calculates one average for each exact `student_id`, `project_id`, and `evaluable_item_id` group. The student ID is filtered before grouping, so grades from another student cannot affect the displayed value. The view displays the separate teacher grades beside the average.

The seed data gives this example:

```text
Student Marc, PRJ-001, Documentació: (8.5000 + 9.0000) / 2 = 8.75
Student Marc, PRJ-003, Proves: (8.0000 + 9.0000) / 2 = 8.5
```

The requirements do not define a grade scale or a rounding rule. The application displays MariaDB's decimal average after removing trailing zeroes; PHP does not apply a new rounding rule.

## Authentication limitation

The current demonstration login does not verify a password against MariaDB and does not create `$_SESSION['user']`. The read controller therefore shows a Catalan access message instead of loading any role's data. This is intentional: a URL role selector is not safe authorization.

To use the database-backed pages, the application first needs a real login that verifies a password hash and stores the verified user ID and database role in the PHP session. Until that exists, the role-filtered reads are implemented but cannot be exercised through the current login screen. This prototype does not claim to provide real authentication, role security, or student-data privacy.

## Checks and manual test plan

There are no automated dashboard read tests in the repository. Run syntax and connection checks from the repository root:

```bash
find app public tests -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/check_database_connection.php
```

The first command should report no syntax errors. The second should print `Database connection successful.` when MariaDB, `pdo_mysql`, and local database settings are available.

After configuring the database with `database/schema.sql` and `database/seed.sql`, and after a verified login is available, test these expected results:

1. Administrator dashboard: 3 projects, 2 teachers, 2 students, 2 items, and 6 individual grades. The project page shows each assignment from the relationship tables.
2. Either seeded teacher: only the three projects assigned to that teacher, with each project's associated students and items.
3. Seeded student Marc: projects `PRJ-001` and `PRJ-003`; `PRJ-001 / Documentació` has teacher grades `8.5` and `9.0` and average `8.75`; `PRJ-003 / Proves` has grades `8.0` and `9.0` and average `8.5`.
4. Seeded student Laura: only `PRJ-002 / Proves` and her grade `8.0`. While signed in as Marc, adding `student_id=5` to a URL must not reveal Laura's grade.
5. While signed in as a student, request a project not assigned to that student. The grade page should show an invalid-project message and no other student's records.
6. Without a verified session or with a mismatched role, the shared layout should show the Catalan access message and should not include the requested role page.

In the current development environment, PHP has neither the PDO extension nor its MySQL driver, and no MariaDB/MySQL command-line client is installed. The connection check and live dashboard queries cannot succeed here; this document does not claim they were run against MariaDB. Use the commands above once the driver, server, and local settings are ready.
