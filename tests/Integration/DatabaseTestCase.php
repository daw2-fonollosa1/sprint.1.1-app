<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Models/EvaluableItemModel.php';
require_once dirname(__DIR__, 2) . '/app/Models/GradeModel.php';
require_once dirname(__DIR__, 2) . '/app/Models/ProjectAssignmentModel.php';
require_once dirname(__DIR__, 2) . '/app/Models/ProjectModel.php';
require_once dirname(__DIR__, 2) . '/app/Models/StudentDashboardData.php';
require_once dirname(__DIR__, 2) . '/app/Models/TeacherDashboardData.php';
require_once dirname(__DIR__, 2) . '/app/Models/UserModel.php';

/** Shared MariaDB fixtures for tests that use the application's PDO models. */
abstract class DatabaseTestCase extends TestCase
{
    protected PDO $connection;

    protected string $codePrefix;

    /** @var int[] IDs created by this test and removed in tearDown(). */
    protected array $userIds = [];

    /** @var int[] IDs created by this test and removed in tearDown(). */
    protected array $projectIds = [];

    /** @var int[] IDs created by this test and removed in tearDown(). */
    protected array $itemIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = Database::getConnection();
        $this->codePrefix = 'test_' . bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        // Projects cascade only into their own assignment and grade rows.
        // Every project here was created by this test, never by the app seed data.
        $this->deleteFixtureRows('projects', $this->projectIds);
        $this->deleteFixtureRows('evaluable_items', $this->itemIds);
        $this->deleteFixtureRows('users', $this->userIds);

        parent::tearDown();
    }

    protected function createUser(string $role, string $label): int
    {
        $username = $this->codePrefix . '_' . $label;
        $statement = $this->connection->prepare(
            'INSERT INTO users (username, full_name, password_hash, role)
             VALUES (:username, :full_name, :password_hash, :role)',
        );
        $statement->execute([
            'username' => $username,
            'full_name' => 'Test ' . $label,
            'password_hash' => password_hash('not-a-real-login', PASSWORD_DEFAULT),
            'role' => $role,
        ]);

        $id = (int) $this->connection->lastInsertId();
        $this->userIds[] = $id;

        return $id;
    }

    protected function createProject(string $label): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO projects (code, name, description)
             VALUES (:code, :name, :description)',
        );
        $statement->execute([
            'code' => $this->codePrefix . '_' . $label,
            'name' => 'Test ' . $label,
            'description' => 'Created only for an automated test.',
        ]);

        $id = (int) $this->connection->lastInsertId();
        $this->projectIds[] = $id;

        return $id;
    }

    protected function createItem(string $label): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO evaluable_items (code, name, description)
             VALUES (:code, :name, :description)',
        );
        $statement->execute([
            'code' => $this->codePrefix . '_' . $label,
            'name' => 'Test ' . $label,
            'description' => 'Created only for an automated test.',
        ]);

        $id = (int) $this->connection->lastInsertId();
        $this->itemIds[] = $id;

        return $id;
    }

    protected function assignTeacher(int $teacherId, int $projectId): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO teacher_projects (teacher_id, project_id) VALUES (?, ?)',
        );
        $statement->execute([$teacherId, $projectId]);
    }

    protected function assignStudent(int $studentId, int $projectId): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO student_projects (student_id, project_id) VALUES (?, ?)',
        );
        $statement->execute([$studentId, $projectId]);
    }

    protected function assignItem(int $itemId, int $projectId): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO project_evaluable_items (project_id, evaluable_item_id) VALUES (?, ?)',
        );
        $statement->execute([$projectId, $itemId]);
    }

    protected function createGrade(
        int $projectId,
        int $studentId,
        int $itemId,
        int $teacherId,
        string $value,
    ): int {
        (new GradeModel($this->connection))->create(
            $projectId,
            $studentId,
            $itemId,
            $teacherId,
            $value,
        );

        return (int) $this->connection->lastInsertId();
    }

    protected function rowCount(string $table, string $column, int $id): int
    {
        // Table and column names passed by tests are fixed in test code, not request data.
        $statement = $this->connection->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
        $statement->execute([$id]);

        return (int) $statement->fetchColumn();
    }

    private function deleteFixtureRows(string $table, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $statement = $this->connection->prepare("DELETE FROM {$table} WHERE id IN ({$placeholders})");
        $statement->execute($ids);
    }
}
