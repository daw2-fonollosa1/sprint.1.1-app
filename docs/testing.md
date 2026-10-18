# Automated Tests

## Test framework

The project uses PHPUnit 13. The application runs on PHP 8.4 in Docker, and PHPUnit 13 supports PHP 8.4 and newer. The tests use the real MariaDB database and the application's `Database::getConnection()` PDO factory.

## Test files

- `composer.json` adds PHPUnit as a development dependency and defines `composer test`.
- `phpunit.xml` points PHPUnit to the integration tests and their bootstrap file.
- `tests/bootstrap.php` validates the test database settings, connects through the application PDO class, and checks that the schema is present.
- `tests/Integration/DatabaseTestCase.php` creates unique test fixtures and removes them after each test.
- `tests/Integration/CrudOperationsTest.php` checks user, project, item, assignment, and prepared-query behavior.
- `tests/Integration/GradesAndDashboardTest.php` checks teacher/student reads, separate grades, and exact arithmetic means.
- `tests/Integration/ControllerValidationTest.php` checks invalid form submissions and session-role restrictions.
- `README.md` links to this guide and shows the test command.

## Create a separate test database

The test suite must not use the development database. It accepts only a database name that ends in `_test`, and it also checks that the name differs from the database in the application's normal settings.

Create a fresh schema with these commands from the repository root. They create `daw2_project_manager_test`; they do not change `daw2_project_manager`:

```bash
mariadb -u root -p -e "CREATE DATABASE IF NOT EXISTS daw2_project_manager_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sed 's/daw2_project_manager/daw2_project_manager_test/g' database/schema.sql | mariadb -u root -p
```

Create a local account that can read and change rows only in the test database. Replace the example password with a local value and do not commit it:

```bash
mariadb -u root -p -e "CREATE USER 'daw2_test'@'127.0.0.1' IDENTIFIED BY 'replace-with-a-local-test-password';"
mariadb -u root -p -e "GRANT SELECT, INSERT, UPDATE, DELETE ON daw2_project_manager_test.* TO 'daw2_test'@'127.0.0.1';"
```

Set the connection values in the same terminal where the tests will run:

```bash
export TEST_DB_HOST=127.0.0.1
export TEST_DB_PORT=3306
export TEST_DB_NAME=daw2_project_manager_test
export TEST_DB_USER=daw2_test
export TEST_DB_PASSWORD='replace-with-a-local-test-password'
```

The test process maps these test-only values to the existing `DB_*` settings before calling `Database::getConnection()`. It fails before running tests if a setting is missing, the database name is not marked `_test`, the test and application database names match, the PDO connection selects another database, or the schema is missing. The test suite does not need Docker.

The schema import creates empty tables. No seed file is needed: each test creates its own records with unique names and removes them after the test. Never point `TEST_DB_*` at a database containing development or real records.

## Install and run

Install Composer, PHP's `pdo_mysql` driver, and the PHP XML and mbstring extensions required by PHPUnit. On Fedora, for example, install `composer`, `php-cli`, `php-mysqlnd`, `php-xml`, and `php-mbstring`. Then run:

```bash
composer install
composer test
```

`composer test` runs `vendor/bin/phpunit` using `phpunit.xml`. A successful run ends with PHPUnit's `OK` summary and the number of tests. If PHPUnit cannot connect or the test schema is missing, fix the test database settings before rerunning it.

## What the tests check

- User, project, and evaluable-item records can be created, read, updated, and deleted through the existing PDO models. User passwords are checked as hashes, and administrator deletion is rejected.
- Teacher, student, and evaluable-item project assignments can be created, read, replaced, and deleted.
- The teacher dashboard returns only that teacher's assigned projects, related students, and related items.
- Grades from two teachers stay as two database rows. The same teacher cannot create a duplicate row for the same project, student, and item; updating or deleting one teacher's row leaves the other row unchanged.
- The student's dashboard calculates an arithmetic mean for the exact student, project, and item. The test uses different values for other students, projects, and items to ensure they do not affect that mean or appear in the selected student's data.
- Controllers reject missing required fields, an invalid grade format, and writes attempted with a student session where an administrator or teacher is required.
- A query value containing SQL-like text is handled as data, and PDO emulated prepares remain disabled.

These are MariaDB integration tests, not tests that only mock PDO. They query the database to confirm that each write happened or was rejected.

## Current test limits

- The suite tests the existing application models and the validation/role checks on invalid form submissions. It does not start a browser or test the full rendered HTML and redirect flow.
- The requirements do not define a numeric grade range or rounding rule, so the tests verify the stored `DECIMAL(10,4)` values and arithmetic means without inventing either rule.
- Create, read, update, and delete coverage applies only to the records and relationships supported by the current models. Any operation not implemented in the application remains an application gap, not a test-only feature.
