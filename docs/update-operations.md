# Update Operations

This document explains how the PHP application edits existing records in MariaDB. Edit forms are normal HTML `POST` forms. PHP checks the submitted values and the logged-in account before a model runs a prepared `UPDATE` statement.

## Files used for updates

- `app/Controllers/UpdateOperationsController.php` — Handles project, evaluable-item, project-assignment, and grade edit requests. It checks the session role, validates form fields, and returns Catalan feedback.
- `app/Controllers/DashboardController.php` — Calls the update controller for pages that contain edit forms. It keeps update requests separate from the existing create controller.
- `app/Controllers/ReadOperationsController.php` — Loads current assignment IDs and the signed-in teacher's grades so PHP can prefill edit forms with saved database values.
- `app/Models/ProjectModel.php` — Checks project codes, checks that a teacher is assigned to a project, and updates project fields with PDO.
- `app/Models/EvaluableItemModel.php` — Checks evaluable-item codes and updates item fields with PDO.
- `app/Models/ProjectAssignmentModel.php` — Reads the current relationship IDs and replaces the selected project assignments in one transaction.
- `app/Models/GradeModel.php` — Reads a teacher's own grades, finds a grade only for its teacher, and updates only `grade_value`.
- `app/Views/pages/admin/projects.php` — Shows edit forms for project details and the project's teacher, student, and item assignments.
- `app/Views/pages/admin/evaluable_items.php` — Shows a prefilled edit form for every evaluable item.
- `app/Views/pages/teacher/projects.php` — Shows edit forms for projects assigned to the signed-in teacher.
- `app/Views/pages/teacher/grades.php` — Shows the signed-in teacher's existing grades and their edit forms.
- `README.md` — Lists the update controller and this guide.

Administrator user editing already exists in `AdminUsersController.php` and `UserModel.php` from the earlier user-management work. It remains administrator-only, validates the account fields, checks duplicate usernames, and hashes a replacement password. Leaving the password blank keeps its current hash. Those files did not need changes for this Issue.

## How a form reaches PHP and PDO

1. The PHP view gets current records from `ReadOperationsController` and fills the form fields from those records.
2. The browser sends the form with `method="post"`. The form includes an action name and the database record ID; JavaScript is not responsible for saving it.
3. `public/index.php` sends the request to `DashboardController`. That controller has already checked the server-side session and confirmed its role against the `users` table.
4. `UpdateOperationsController` checks that the action belongs to this page and role. It validates the ID and submitted fields before calling a model.
5. The model uses the existing PDO connection and prepared statements. On success, PHP sends a `303` redirect so the browser loads the updated database values with a fresh `GET` request.

If validation fails, PHP keeps the submitted non-secret values in the response, opens the matching edit section, and shows Catalan messages. Database details are written to the server log; the page only shows a general Catalan error.

## What each edit changes

### Users

Only an administrator can edit teacher and student accounts. The existing user form updates name, username, and role. A blank password means “keep the current password”; a new password is hashed with PHP `password_hash()` before it is stored. The form never reads or displays a password hash.

### Projects and evaluable items

Administrators can edit any project. A teacher can edit only a project already assigned to that teacher. Only administrators can edit evaluable items. Project and item forms use the fields in the schema: code, name, and description. Codes must remain unique, and editing a record can keep its existing code.

Changing project or item details keeps the record ID and its assignment relationships. Required fields and the schema's text lengths are checked in PHP before the update query.

### Project assignments

The administrator's assignment form shows the current teachers, students, and evaluable items as checked boxes. Saving the form replaces the selected relationship sets. The model calculates what was added or removed and changes all three sets inside one transaction, so a database error cannot leave only part of the form saved. It inserts only new links, which avoids duplicate relationships.

The schema cascades assignment deletion to related grades. To avoid losing grades during an assignment edit, PHP blocks removal of a teacher, student, or item that is used by a saved grade for that project. The message identifies which type of assignment cannot be removed. Existing links with grades stay intact.

### Grades

The teacher's grade list shows only grade rows whose `teacher_id` matches the signed-in teacher. The update query changes only `grade_value` and includes both the grade ID and authenticated teacher ID in its `WHERE` clause. It never accepts a new teacher, project, student, or item from the form, so those associations cannot be changed by editing the grade.

If another teacher has graded the same student, project, and item, that teacher's row remains separate and unchanged. The student screen calculates the arithmetic mean from the individual grade rows; no average is stored by this update operation. The requirements do not define a grade scale or rounding rule, so validation follows the existing `DECIMAL(10,4)` format without inventing a range.

## Permissions and open decisions

The project requirements do not fully specify update permissions for projects, items, and assignments; `docs/decisions.md` records that question as pending. This implementation follows the closest existing role rules and interface:

- Administrators edit users, projects, items, and assignments.
- Teachers edit only their assigned projects and their own grade rows.
- Students cannot submit update operations.

These checks use the server-side PHP session. A role or record owner supplied in a form is never treated as proof of permission. Project assignment edits are administrator-only because the existing assignment controls are in the administrator interface and the requirements do not grant assignment management to teachers.

## Manual test checklist

There are no automated update-operation tests in the repository. Use a disposable development database or make a backup first because successful edits remain in MariaDB.

Configure `.env` and start MariaDB. Import the schema and seed data in step 1 below. From the project root, start the PHP development server with `php -S 127.0.0.1:8000 -t public`, then open `http://127.0.0.1:8000/` in a browser.

1. Load `database/schema.sql` and `database/seed.sql`, then sign in as `admin-demo` using the development password documented in `docs/login-implementation.md`.
2. On **Projectes**, edit the name or description of `PRJ-001`, save, reload, and confirm the new value remains. Try using the code of another project or leaving a required field blank; PHP should show a Catalan error and save nothing.
3. On **Ítems avaluables**, edit an item's name, save, and reload. Try another item's code and confirm the duplicate is rejected.
4. On **Projectes**, change an assignment set and save it. Reload and confirm the checked boxes match MariaDB. Save the same selection again and confirm the relationship remains a single assignment. Try removing a teacher, student, or item that has a grade and confirm PHP blocks the change without removing the grade.
5. Sign in as `teacher-marta`. In **Els meus projectes**, edit a project assigned to Marta and confirm it persists after reload. A manually altered ID for a project not assigned to Marta must be rejected.
6. On **Qualificacions**, edit Marta's grade for Marc, project `PRJ-001`, item `IT-001` (seed value `8.5000`). Reload and confirm the new value remains. Try an invalid number such as `1234567.12345`; PHP should reject it.
7. Sign in as `teacher-joan` and edit Joan's grade for the same student, project, and item (seed value `9.0000`). Confirm Joan's change does not overwrite Marta's row. For example, values `8.75` and `9.25` produce the arithmetic mean `(8.75 + 9.25) / 2 = 9`.
8. Attempt to submit a grade ID belonging to another teacher while signed in as Marta. PHP must reject it and leave the other teacher's grade unchanged.
9. On **Gestió d’usuaris**, use the existing administrator edit form. Try a valid name change and a duplicate username; confirm the valid change persists and the duplicate is rejected. Leave the password empty and verify that the old password still works.

## Limitations

- These handlers update existing records only. Creation remains in the existing create forms, and deletion is handled separately by `DeleteOperationsController.php` as described in `docs/delete-operations.md`.
- The database schema's `ON DELETE CASCADE` behavior is unchanged. This controller protects grade records when assignment forms are edited.
- This is a classroom application. Production deployment still needs appropriate operational protections such as HTTPS, backups, and request rate limits.
