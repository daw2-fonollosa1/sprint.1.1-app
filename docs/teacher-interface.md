# Teacher Interface

## Scope

Teacher pages read assigned projects, associated students, and evaluable items from MariaDB. The dashboard also shows the number of individual grades entered by the teacher and the number of assigned grading combinations still pending. Project and grade forms remain PHP forms.

The pages are filtered by the ID and role in the server-side session. The current demonstration login does not create an authenticated session, so it cannot display teacher data yet.

## Files

- `app/Controllers/DashboardController.php` — Checks the teacher session before showing a role page.
- `app/Controllers/ReadOperationsController.php` — Loads the selected teacher page data and resolves project choices only inside that teacher's assigned projects.
- `app/Models/TeacherDashboardData.php` — Reads a teacher's assigned projects, students, and evaluable items and counts grades and pending combinations.
- `app/Models/ProjectModel.php` — Reads projects assigned through `teacher_projects`.
- `app/Models/GradeModel.php` — Reads the project, student, and item choices required by the existing grade form.
- `app/Views/pages/teacher/dashboard.php` — Shows database totals and assigned projects.
- `app/Views/pages/teacher/projects.php` — Lists assigned projects and retains the existing PHP project form.
- `app/Views/pages/teacher/project_details.php` — Shows one assigned project, its students, and its evaluable items.
- `app/Views/pages/teacher/students.php` — Lists unique students across the teacher's projects and shows each student's project links.
- `app/Views/pages/teacher/grades.php` — Retains the PHP grade form and accepts only project choices returned for the signed-in teacher.
- `docs/read-operations.md` — Explains how all database reads and privacy filters work.

## How teacher data is filtered

`TeacherDashboardData` starts from the `teacher_projects` table. It then joins `student_projects` and `project_evaluable_items` to find only students and items attached to those projects. The teacher ID is passed as a prepared SQL value.

The project detail page accepts a project ID from the URL only after the controller compares it with the teacher's already-filtered project list. The project, student, and item names are escaped before display. The schema does not contain project status or student group columns, so those values are not shown.

## Dashboard counts

- Assigned projects: number of rows in the teacher's filtered project list.
- Students: number of unique students associated with those projects.
- Grades entered: individual `grades` rows whose `teacher_id` is the signed-in teacher.
- Pending: assigned project/student/item combinations for which this teacher has not entered a grade.

## Try the screens

After MariaDB and `pdo_mysql` are available, load the schema and seed data. Once a verified teacher login stores the matching user ID and role in the PHP session:

1. Open `/?page=dashboard&role=teacher`. The seed has two teachers, each assigned to three projects and two students across those projects.
2. Open **Els meus projectes**. The page should show only projects assigned to the signed-in teacher and their assigned students/items.
3. Open one project's details. Confirm its student and item lists match the assignment tables.
4. Open **Alumnat**. A student assigned to multiple of the teacher's projects should appear once with each project listed.
5. Open **Qualificacions** and change the project. PHP reloads the page and offers students and items linked to that project.

## Authentication dependency

The role in a URL is only a navigation preview. Until a real login verifies a password, loads the user from MariaDB, and creates `$_SESSION['user']`, the application intentionally displays an access message instead of teacher records. This avoids treating a URL parameter as a privacy boundary.
