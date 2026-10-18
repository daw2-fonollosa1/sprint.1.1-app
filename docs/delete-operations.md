# Delete Operations

This guide explains how the PHP application deletes records from MariaDB. Delete buttons show a confirmation first. If confirmed, the browser sends a normal `POST` request; PHP checks the signed-in account and the record's relationships before a model runs a prepared `DELETE` statement.

## Files created or changed

- `app/Controllers/DeleteOperationsController.php` — Checks the delete action, role, and record ID, then calls the model and returns Catalan feedback.
- `app/Controllers/DashboardController.php` — Routes delete requests separately from the existing create and update controllers. It also maps the administrator's existing **Qualificacions** page to its PHP view.
- `app/Controllers/ReadOperationsController.php` — Loads the individual grades needed to identify a grade in the administrator's delete list. The existing teacher grade list is reused.
- `app/Controllers/AdminUsersController.php` — Keeps the existing user delete form and reports when a user still has project assignments.
- `app/Models/UserModel.php` — Deletes only a teacher or student who has no project assignment. Administrator accounts are excluded.
- `app/Models/ProjectModel.php` — Deletes a project only if it has no teacher, student, item, or grade relationship.
- `app/Models/EvaluableItemModel.php` — Deletes an item only if it is not assigned to a project and has no grades.
- `app/Models/ProjectAssignmentModel.php` — Deletes one selected teacher, student, or item link only if no grade depends on it.
- `app/Models/GradeModel.php` — Loads grade details for the administrator list and deletes one grade row by ID. A teacher delete also checks the grade's teacher ID.
- `app/Views/pages/admin/users.php` — Explains that assigned accounts are protected and the administrator account is not in this list.
- `app/Views/pages/admin/projects.php` — Adds confirmed forms to delete unused projects and individual assignment links.
- `app/Views/pages/admin/evaluable_items.php` — Adds a confirmed form to delete an unassigned evaluable item.
- `app/Views/pages/admin/grades.php` — New administrator list for deleting any individual grade.
- `app/Views/pages/teacher/grades.php` — Adds a confirmed form for deleting the signed-in teacher's own grade.
- `public/assets/js/admin-users.js` — Confirms a user delete and explains that PHP blocks it when assignments still exist.
- `README.md` — Lists the new controller and guide and describes delete operations in the project status.
- `docs/administrator-user-management.md` — Explains that deleting an assigned account is blocked instead of cascading to its grades.
- `docs/administrator-interface.md` — Lists the administrator grade-management page.
- `docs/read-operations.md` — Explains the small grade-list read used to identify a delete target.
- `docs/update-operations.md` — Points to the separate delete controller and guide.

## How a delete request reaches PHP

1. A view displays a record already loaded from MariaDB. Its delete form includes an action and the record ID, and uses `method="post"`.
2. The browser asks the user to confirm. Cancelling the prompt prevents the form from being sent.
3. `public/index.php` starts the PHP session and sends the request to `DashboardController`.
4. The dashboard checks the session account against MariaDB. It then sends delete actions to `DeleteOperationsController`; user deletes continue through `AdminUsersController`.
5. The controller checks the action, required role, and positive numeric ID. The model uses PDO prepared statements for values from the form.
6. On success, PHP sends a `303` redirect. The existing read flow reloads the list and the page shows a Catalan success message.

Database errors are written to the server log. The browser receives a general Catalan error, not a SQL message or stack trace.

## Permissions

- Administrators can delete teacher and student accounts, projects, evaluable items, assignments, and any grade. Administrator project/item deletion follows the requirement that the administrator has full control.
- Teachers can delete only grades they entered for projects still assigned to them.
- Students cannot submit delete actions.
- The only administrator account cannot be deleted through the user form or its model method. It is excluded from the list of manageable users.

Permissions use the server-side PHP session. A role, teacher ID, or other permission claim sent in a form is never trusted.

## Related records and database rules

The schema uses `ON DELETE CASCADE` between users, projects, assignments, and grades. The delete forms do not use those cascades to remove related information:

- A user with any project assignment cannot be deleted. This protects the assignment and any grades that refer to it.
- A project with any teacher, student, or item assignment cannot be deleted. A project with grades is also blocked.
- An evaluable item assigned to a project or used by a grade cannot be deleted.
- An assignment with one or more related grades cannot be deleted. An assignment with no grades can be removed individually.
- A grade delete uses only its `grades.id` (and the authenticated teacher ID for a teacher request). Another teacher's grade for the same student, project, and item is left unchanged.

The checks and deletes for users, projects, items, and assignments run inside a transaction and lock the selected row while checking relationships. Grade deletion affects only one row and does not remove an assignment.

## Manual test checklist

The automated tests for delete behavior use the dedicated database described in [Automated tests](testing.md) and remove their own fixture rows. The browser checklist below performs permanent changes, so use a disposable development database for those steps. Configure `.env`, start MariaDB, import `database/schema.sql` and `database/seed.sql`, and run `php tests/check_database_connection.php`. From the repository root, run `php -S 127.0.0.1:8000 -t public` and open `http://127.0.0.1:8000/`.

1. Sign in as `admin-demo` using the development password in `docs/login-implementation.md`.
2. On **Gestió d’usuaris**, try deleting `teacher-marta`. PHP should block the request because she has project assignments. Create a new teacher with no assignments, delete that account, reload, and confirm it remains absent. Try changing a delete form's hidden ID to the administrator's ID (`1` in the seed data); PHP must reject it.
3. On **Projectes**, try deleting `PRJ-001`. PHP should block it because it has assignments. Create a project with no assignments, confirm its delete, reload, and confirm only that project disappeared.
4. On **Ítems avaluables**, try deleting `IT-001`; PHP should block it while it is assigned. Create an unused item, confirm its delete, reload, and confirm that item disappeared.
5. In a project assignment list, try deleting the teacher, student, or item link used by a grade. PHP must block the removal. Delete a link with no grades, such as teacher Joan from `PRJ-002` in the seed data, reload, and confirm only that link disappeared.
6. On the administrator **Qualificacions** page, delete Marta's grade for Marc, `PRJ-001`, and `IT-001` (seed grade ID `1`). Reload and confirm grade ID `1` is gone while Joan's matching grade (ID `2`) remains unchanged.
7. Sign in as `teacher-marta` and delete one of her own grades from **Qualificacions**. Reload and confirm it is absent. Try submitting a grade ID belonging to Joan; PHP must reject it and preserve Joan's row.
8. To test confirmation cancellation, start deleting an unused project or item and choose **Cancel·la** in the browser prompt. Confirm no request was sent and the record remains.
9. To test a missing record, change a delete form's hidden ID to a nonexistent positive value such as `999999` and submit it. The page should show a Catalan error and the other rows should remain unchanged.

Deleting or unassigning seeded records changes the development data. Reload the seed in a disposable database to restore the examples before repeating these steps.

## Limits

This Issue implements deletion only. The administrator grade table reads only the fields needed to choose and identify an individual grade for deletion; it does not add a grade-edit or grade-create form. `docs/decisions.md` leaves assignment management and teacher access to other teachers' grades open. This implementation keeps assignment deletion administrator-only, allows teachers to delete only their own grades, and follows the requirement that administrators have full control.
