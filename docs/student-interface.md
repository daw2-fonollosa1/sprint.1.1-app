# Student Interface Prototype

## Scope

The student preview has a dashboard, a list of assigned projects, project details, and a grades page. PHP renders each page. Links and the project filter make regular requests to PHP; the student pages do not use JavaScript to control navigation. The pages use the existing Bootstrap 5 layout and navigation.

## Files created or changed

- `app/Controllers/DashboardController.php` — Adds the student routes and passes the fixed demo student and project filter to the student data model.
- `app/Models/DemoData.php` — Stores the sample records shared by all role previews, including Marc's second project and example grades.
- `app/Models/StudentDashboardData.php` — Filters Marc's assigned projects and grades, creates the item rows, and calculates the arithmetic means used by his views.
- `app/Views/partials/navigation.php` — Keeps the Projects link active while a student views a project's details.
- `app/Views/pages/student/dashboard.php` — Shows Marc's assigned project count, graded and pending item counts, project links, and a per-item grade summary.
- `app/Views/pages/student/projects.php` — Lists Marc's assigned projects and links to their details.
- `app/Views/pages/student/project_details.php` — Shows one assigned project's information, evaluable items, grades, and teacher-grade calculations.
- `app/Views/pages/student/grades.php` — Lists the selected student's grades and lets the student filter them by one of their projects.
- `README.md` — Adds the student interface to the project status and documentation links.
- `docs/administrator-interface.md` — Updates the shared sample-project count and the description of the student pages.
- `docs/teacher-interface.md` — Explains that shared sample data is now provided by `DemoData.php`.
- `docs/student-interface.md` — Explains the prototype and its demonstration steps.

## Projects, details, items, and grades

The prototype uses the fixed sample student Marc Gómez Ribas (`student-1`). His sample projects are `PRJ-001` and `PRJ-003`. The projects page lists both. Opening a project shows its code, name, group, description, and only the evaluable items attached to that project.

The dashboard summarizes assigned projects and counts graded and pending evaluable items. The grades page lists each item result. When two teachers have graded the same item, the page shows their individual grades and the arithmetic-mean calculation. Items without grades are labelled as pending.

The wireframe includes a global average card. The project requirements define an arithmetic mean only when teachers grade the same student, project, and item; they do not define how grades from different items should be combined. To avoid inventing that rule, this prototype shows per-item averages and item counts instead of a global average.

## Arithmetic mean examples

The calculation follows the requirement:

```text
Arithmetic mean = Sum of grades / Number of grades
```

For `PRJ-001` and the `Documentació` item, the sample teachers' grades are `8.5` and `9.0`:

```text
(8.5 + 9.0) / 2 = 17.5 / 2 = 8.75
```

For the `Proves` item in `PRJ-001`, one teacher has entered `7.5`, so the displayed result is `7.5`. For `PRJ-003` and the `Proves` item, the sample grades are `8.0` and `9.0`:

```text
(8.0 + 9.0) / 2 = 17.0 / 2 = 8.5
```

The sample inputs use one decimal place. Their means need at most two decimal places, so the displayed values are exact for these examples. In the Catalan interface, a comma is used as the decimal separator.

## Navigate and test

1. From the repository root, start PHP's development server:

   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

2. Open `http://127.0.0.1:8000/`, sign in with username `student`, and enter any non-empty password. This is a prototype shortcut, not real authentication.
3. The student dashboard should show two assigned projects and three graded items out of four. It should list the per-item results `8.75`, `7.5`, and `8.5`; one item is pending.
4. Open **Els meus projectes**. Both `PRJ-001` and `PRJ-003` should appear. Open either project to see its description and evaluable items.
5. In `PRJ-001`, verify that `Documentació` lists grades `8.5` and `9.0` and shows `(8.5 + 9.0) / 2 = 8.75`. `Proves` shows `7.5`.
6. In `PRJ-003`, verify that `Proves` lists `8.0` and `9.0` and shows an average of `8.5`. `Documentació` is pending.
7. Open **Qualificacions** and try the project filter. It should filter Marc's rows and must not offer a student selector.
8. To check that another student's project is not rendered, open `http://127.0.0.1:8000/?page=project-details&role=student&project_id=project-2`. The response should be a not-found page message that says the project is unavailable. Adding `student_id=student-2` to the URL does not change the displayed student or reveal Laura's grade.

## Temporary data and future backend work

The sample student ID is fixed in the PHP controller. The controller selects only projects assigned to that ID and filters grades by both the same student ID and those project IDs before building the student views. The pages contain no student switcher and no other sample student's grade data is added to the rendered student content.

This filtering is for the interface demonstration only. The application does not provide real authentication, access control, or data privacy. Sample data is held in PHP arrays and is rebuilt on every request. A future backend must load the signed-in student and their assignments from MariaDB, enforce access on the server, and persist data through the PHP application.
