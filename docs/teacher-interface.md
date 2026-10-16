# Teacher Interface Prototype

## Scope

The teacher preview contains a dashboard, a list of assigned projects, project details, a list of associated students, and a grade entry page. PHP renders these pages. The project and grade forms now use create-only PDO handlers, while the remaining dashboard and detail views still use sample data. All writes require a verified teacher session; the current demo login does not create one.

## Files created or changed

- `app/Controllers/DashboardController.php` — Adds teacher pages and their navigation and selects the sample projects assigned to the demo teacher.
- `app/Models/DemoData.php` — Holds the sample projects, users, evaluable items, and grades used by the teacher preview.
- `app/Views/layouts/main.php` — Marks teacher pages, passes teacher sample data as JSON, and loads the teacher interaction script for the teacher role.
- `public/assets/js/app.js` — Submits the shared role-preview form as a regular PHP request.
- `app/Views/pages/teacher/dashboard.php` — Shows project, student, and pending-grade totals, recent assigned projects, and a shortcut to the grade page.
- `app/Views/pages/teacher/projects.php` — Lists multiple assigned projects and links to their details or grade entry.
- `app/Views/pages/teacher/project_details.php` — Shows a project's code, name, description, assigned students, and evaluable items.
- `app/Views/pages/teacher/students.php` — Lists students associated with the teacher's projects and links to their project details or grading form.
- `app/Views/pages/teacher/grades.php` — Provides server-rendered project, student, item, and individual grade creation forms.
- `app/Controllers/CreateOperationsController.php`, `app/Models/GradeModel.php`, and `app/Models/ProjectModel.php` — Validate and save teacher project and grade creates through PDO.
- `public/assets/js/teacher.js` — Remains in the project for the earlier prototype, but no longer saves the grade form.
- `README.md` — Adds the teacher prototype to the project status and documentation links.
- `docs/teacher-interface.md` — Explains the teacher prototype and how to demonstrate it.

## Projects, details, students, and items

The sample teacher is `teacher-1`. The controller filters the project sample data by that teacher ID, so the project list can contain more than one project. Each project stores the IDs of its assigned students and evaluable items.

The dashboard and projects page link to project details. The detail page shows the project information, its students, and its evaluable items. The students page combines students across the teacher's assigned projects and shows which project each student belongs to.

The shared navigation includes the dashboard, projects, students, and qualifications. Project details are opened from a project link, so they do not appear as a separate top-level menu item. All links use regular PHP page requests.

## Grade creation

The grade page asks the teacher to select an assigned project, an assigned student, an evaluable item linked to that project, and a grade value. The requirements do not define a grade scale or rounding rule, so the form checks only the precision supported by `DECIMAL(10,4)` in the database.

The grade form submits to PHP and inserts one database row for the authenticated teacher and selected assigned project/student/item. The unique schema key prevents the same teacher from inserting a duplicate grade while allowing another teacher to create a separate row. This create-only issue does not read grades or calculate averages.

## Try the demonstration

1. From the repository root, run `php -S 127.0.0.1:8000 -t public` and open `http://127.0.0.1:8000/`.
2. Sign in with username `teacher` and any non-empty password. This still opens only the demo role preview.
3. Open **Els meus projectes**. Sample project cards remain for navigation, and the new PDO form explains that server authentication is required.
4. Open **Qualificacions**. Without a verified server session, project/student/item options are unavailable and the POST is blocked.
5. After a future login issue creates a verified teacher session, select an assigned project and test the grade inputs listed in `docs/create-operations.md`.

## Temporary behavior and backend work

The project and grade inserts use MariaDB through PDO, but a real login/session system is still missing. The current role selector is only a preview, so the server rejects create submissions until login stores a verified teacher identity in PHP's session. Sample project, student, and item data still powers the prototype detail pages. Grade scale and rounding rules also remain undefined in the requirements.
