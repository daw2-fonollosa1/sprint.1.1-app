# Database Schema

This document explains the first MariaDB schema for the PHP application. The SQL files create the tables and add small development examples. PHP does not use this database yet.

## Files

- `database/schema.sql` creates the database and its tables.
- `database/seed.sql` inserts sample accounts, projects, evaluable items, assignments, and grades.

The seed data is for development only. Its password hashes are random placeholders and are not usable login credentials. The SQL file contains no plaintext passwords.

## Tables

### `users`

Stores application accounts. `role` can be `administrator`, `teacher`, or `student`. `username` is unique so two accounts cannot use the same login name. `password_hash` is where a future authentication system would store a password hash.

The seed has one administrator, two teachers, and two students. The requirement says there must be one administrator. This simple schema stores the role, but the application must enforce the one-administrator rule and must check that teacher and student assignments use users with the matching role.

### `projects`

Stores each project, with its unique `code`, `name`, and `description`.

### `evaluable_items`

Stores each reusable evaluable item, with its unique `code`, `name`, and `description`.

### `teacher_projects`

Connects teachers to projects. Its two-column primary key means the same teacher cannot be assigned to the same project twice. A teacher can still be assigned to many different projects, and each project can have many teachers.

### `student_projects`

Connects students to projects in the same way. A student can take part in many projects, and each project can have many students.

### `project_evaluable_items`

Connects evaluable items to projects. An item can be reused in several projects, and a project can contain several items.

### `grades`

Stores one teacher's grade for one student, one project, and one evaluable item. Its foreign keys point to the assignment tables, so a grade can only be inserted when:

- The teacher is assigned to that project.
- The student is assigned to that project.
- The evaluable item is linked to that project.

The unique constraint across project, student, item, and teacher prevents one teacher from entering the same grade combination twice. A different teacher can still grade the same student, item, and project. Each teacher's value is stored separately; a final arithmetic mean can be calculated from those rows when the application needs to display it. There is no average column.

`grade_value` uses `DECIMAL(10, 4)` so values can be stored consistently with up to four digits after the decimal point. The project requirements do not yet define the permitted grade scale or how the final mean should be rounded. This storage precision does not define either rule; the team must decide them before adding scale or rounding constraints.

## Relationships

```text
users ──< teacher_projects >── projects
users ──< student_projects >── projects
projects ──< project_evaluable_items >── evaluable_items

grades links one assigned teacher, one assigned student, and one item
within the same project.
```

The arrows represent one-to-many links to a junction table. The junction tables are what allow many-to-many relationships. Foreign keys prevent rows from referring to accounts, projects, or items that do not exist. `ON DELETE CASCADE` removes dependent assignments when a referenced account, project, or item is deleted, and removes related grades when an assignment is deleted.

Foreign keys check that the referenced user exists, but they do not check that the user's `role` matches the assignment table. The application must check that rule. The database also does not enforce the total count of administrators; the application must preserve the one-administrator requirement.

## Run the scripts

Run these from the repository root on a machine with MariaDB client access and permission to create a database:

```bash
mariadb -u root -p < database/schema.sql
mariadb -u root -p daw2_project_manager < database/seed.sql
```

The seed is intended for a newly created, empty schema. To inspect the tables and sample records:

```bash
mariadb -u root -p daw2_project_manager -e "SHOW TABLES; SELECT role, COUNT(*) AS account_count FROM users GROUP BY role; SELECT project_id, student_id, evaluable_item_id, COUNT(*) AS teacher_count FROM grades GROUP BY project_id, student_id, evaluable_item_id;"
```

For project 1, student 4, and item 1, the final query should show two grade rows because two teachers graded that combination. A second grade by the same teacher for that exact combination should be rejected by the unique constraint.

The schema has not been connected to the PHP application. A later persistence task will need to add database configuration and PDO queries.
