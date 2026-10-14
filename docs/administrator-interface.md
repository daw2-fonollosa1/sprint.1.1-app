# Administrator Interface Prototype

## Scope

The administrator area contains a dashboard and demonstration pages for users, projects, and evaluable items. The user page separates teachers from students. The project page lets the visitor edit project details and assign teachers, students, and evaluable items.

PHP renders each page and provides the starting sample records. JavaScript changes an in-memory copy while the page is open. These edits are demonstrations only; they are not saved to a database or browser storage.

Visible interface text is in Catalan. Source code, comments, and this document are in English.

## Files

- `app/Controllers/DashboardController.php` — Defines the administrator navigation, sample users/projects/items, and the view selected for each administrator section.
- `app/Views/layouts/main.php` — Includes the shared navigation, selected page, Bootstrap assets, and safe JSON containing the sample data.
- `app/Views/pages/admin/dashboard.php` — Shows sample totals and a short list of projects.
- `app/Views/pages/admin/users.php` — Separates teachers and students and provides search plus create, edit, and delete controls.
- `app/Views/pages/admin/projects.php` — Provides project create/edit/delete controls and teacher, student, and evaluable-item assignment controls.
- `app/Views/pages/admin/evaluable_items.php` — Provides evaluable-item search and create, edit, and delete controls.
- `public/assets/js/app.js` — Submits the shared role-preview form as a normal PHP page request.
- `public/assets/js/admin-common.js` — Reads the sample data and provides shared table, form, modal, and feedback helpers.
- `public/assets/js/admin-users.js` — Handles the teacher and student lists and their temporary create, edit, and delete actions.
- `public/assets/js/admin-projects.js` — Handles project changes and project assignments.
- `public/assets/js/admin-items.js` — Handles evaluable-item search and temporary create, edit, and delete actions.
- `README.md` — Describes the project status and links to this guide.

## Page and navigation structure

The shared navigation links are configured in `DashboardController`. Administrator links open the dashboard, projects, users, evaluable items, or qualifications. The teacher and student previews keep their existing dashboard, projects, and qualifications placeholders.

Selecting a link sends a normal browser request such as `/?page=users&role=admin`. PHP checks the requested role and page, then includes the matching view inside the shared layout. JavaScript does not take over page navigation.

## Sample data

`DashboardController` defines four example users: two teachers and two students. It also defines two projects and two evaluable items. Each project stores arrays of user and item IDs, which represent its current assignments.

The controller passes the arrays into `main.php`. The layout converts them to JSON inside a script element. `admin-common.js` reads that JSON and shares the resulting objects and helper functions with the script for the selected administrator section.

## Create, edit, delete, and assignments

The create and edit buttons open Bootstrap modals. The same form is reused: an empty hidden ID means a new record, and a populated ID means the record is being edited. The script rejects duplicate usernames or codes and shows an error in the form.

Delete buttons ask for confirmation, remove the selected record from the in-memory array, and show a success message. Removing a user or an evaluable item also removes its ID from project assignments.

On the project page, select a project and mark the teachers, students, or items to assign. Each save button updates only that assignment list and displays feedback. Searching filters the visible user or evaluable-item table.

## Try the interactions

1. Start the app with `php -S 127.0.0.1:8000 -t public` and open `http://127.0.0.1:8000/`.
2. Sign in with `admin` and any non-empty password.
3. Open **Usuaris**, switch between **Professorat** and **Alumnat**, then create or edit a sample account. Try reusing an existing username to see the duplicate warning.
4. Open **Projectes**, edit a project, change its teacher or student checkboxes, and press the corresponding save button.
5. Create a project or delete a sample project after confirming the prompt.
6. Open **Ítems avaluables**, search for `IT-001`, edit an item, or create one. Try reusing an existing item code to see the duplicate warning.
7. Reload or navigate to another page. The changes return to the initial sample data because no permanent storage is used.

## Limitations and next work

The prototype has no real authentication, authorization, database connection, or backend CRUD. The role selector and login are demonstrations only. Data changes exist only in the JavaScript arrays for the current page, so a page navigation resets them. A later backend task can replace these arrays with records loaded and saved through PHP and MariaDB.
