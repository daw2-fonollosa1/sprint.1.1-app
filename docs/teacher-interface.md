# Teacher Interface Prototype

## Scope

The teacher preview contains a dashboard, a list of assigned projects, project details, a list of associated students, and a grade entry page. PHP renders these pages. JavaScript enhances the role selector and handles the grade-entry demonstration; normal links ask PHP to render a new page.

## Files created or changed

- `app/Controllers/DashboardController.php` — Adds teacher pages and their navigation and selects the sample projects assigned to the demo teacher.
- `app/Models/DemoData.php` — Holds the sample projects, users, evaluable items, and grades used by the teacher preview.
- `app/Views/layouts/main.php` — Marks teacher pages, passes teacher sample data as JSON, and loads the teacher interaction script for the teacher role.
- `public/assets/js/app.js` — Submits the shared role-preview form as a regular PHP request.
- `app/Views/pages/teacher/dashboard.php` — Shows project, student, and pending-grade totals, recent assigned projects, and a shortcut to the grade page.
- `app/Views/pages/teacher/projects.php` — Lists multiple assigned projects and links to their details or grade entry.
- `app/Views/pages/teacher/project_details.php` — Shows a project's code, name, description, assigned students, and evaluable items.
- `app/Views/pages/teacher/students.php` — Lists students associated with the teacher's projects and links to their project details or grading form.
- `app/Views/pages/teacher/grades.php` — Provides project, student, item, and grade controls, plus individual teacher grades and their arithmetic mean.
- `public/assets/js/teacher.js` — Updates the grade options and calculates and displays temporary grades in the browser.
- `README.md` — Adds the teacher prototype to the project status and documentation links.
- `docs/teacher-interface.md` — Explains the teacher prototype and how to demonstrate it.

## Projects, details, students, and items

The sample teacher is `teacher-1`. The controller filters the project sample data by that teacher ID, so the project list can contain more than one project. Each project stores the IDs of its assigned students and evaluable items.

The dashboard and projects page link to project details. The detail page shows the project information, its students, and its evaluable items. The students page combines students across the teacher's assigned projects and shows which project each student belongs to.

The shared navigation includes the dashboard, projects, students, and qualifications. Project details are opened from a project link, so they do not appear as a separate top-level menu item. All links use regular PHP page requests.

## Grade demonstration

The approved grading wireframe shows a scale from 0 to 10 with decimal grades. The grade page asks the teacher to select a project, student, and evaluable item before entering a grade. It displays these three selected records together so it is clear where the grade applies.

The sample data stores each teacher's grade for one project/student/item combination. When multiple teachers have entered a grade for the same combination, `teacher.js` calculates the arithmetic mean: it adds those grades and divides by their count. Updating the current teacher's grade recalculates the mean while preserving the other teacher's grade.

The JavaScript changes exist only in memory on the current page. Navigating to another PHP page or reloading restores the original sample grades.

## Try the demonstration

1. From the repository root, run `php -S 127.0.0.1:8000 -t public` and open `http://127.0.0.1:8000/`.
2. Sign in with username `teacher` and any non-empty password.
3. Open **Els meus projectes** and choose **Obri els detalls** on either project. The details page shows its assigned students and evaluable items.
4. Open **Alumnat dels meus projectes** and choose **Qualifica** for a student.
5. On **Qualificacions**, verify that the project, student, and item are identified. For the first project, the first student and first item show two sample teacher grades, `8.5` and `9.0`, with an arithmetic mean of `8.75`.
6. Change the grade to `7.0` and choose **Desa la qualificació**. The current teacher's grade changes to `7.0` and the mean becomes `8.00`.
7. Try `11` or `-1`. The page shows a Catalan validation message and does not update the sample data.
8. Reload or navigate away and return. The grade returns to the original sample value because the prototype does not save it permanently.

## Temporary behavior and backend work

There is no MariaDB connection, backend grade submission, authentication, or access control in this prototype. The role selector is only a preview. Sample project, student, item, and grade data are PHP arrays passed to the page as JSON. JavaScript edits only its in-memory copy. A future backend implementation must load and save grades through PHP and MariaDB and enforce the teacher's project permissions on the server.
