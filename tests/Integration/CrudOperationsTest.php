<?php

declare(strict_types=1);

require_once __DIR__ . '/DatabaseTestCase.php';

/** Check that the existing PDO models really save, return, change, and remove rows. */
final class CrudOperationsTest extends DatabaseTestCase
{
    public function testUserCanBeCreatedReadUpdatedAndDeleted(): void
    {
        $username = $this->codePrefix . '_new_teacher';
        $users = new UserModel($this->connection);

        $users->create($username, 'Teacher Before Edit', 'first-password', 'teacher');
        $user = $users->findByUsername($username);
        self::assertNotNull($user);
        self::assertSame('teacher', $user['role']);
        self::assertTrue(password_verify('first-password', $user['password_hash']));

        $userId = (int) $user['id'];
        $this->userIds[] = $userId;
        $users->update($userId, $username, 'Teacher After Edit', 'teacher', 'changed-password');

        $updated = $users->findByUsername($username);
        self::assertNotNull($updated);
        self::assertSame('Teacher After Edit', $updated['full_name']);
        self::assertTrue(password_verify('changed-password', $updated['password_hash']));
        self::assertSame(1, count(array_filter(
            $users->getManagedUsers(),
            static fn (array $row): bool => (int) $row['id'] === $userId,
        )));

        self::assertSame('deleted', $users->deleteIfUnassigned($userId));
        self::assertNull($users->findByUsername($username));
    }

    public function testAdministratorCannotBeRemovedThroughManagedUserDelete(): void
    {
        $administratorId = $this->createUser('administrator', 'administrator');
        $users = new UserModel($this->connection);

        self::assertSame('missing', $users->deleteIfUnassigned($administratorId));
        self::assertSame(1, $this->rowCount('users', 'id', $administratorId));
        self::assertSame([], array_values(array_filter(
            $users->getManagedUsers(),
            static fn (array $row): bool => (int) $row['id'] === $administratorId,
        )));
    }

    public function testProjectCanBeCreatedReadUpdatedAndDeleted(): void
    {
        $code = $this->codePrefix . '_project';
        $projects = new ProjectModel($this->connection);

        $projects->create($code, 'Project Before Edit', 'First description.', null);
        $projectId = $this->findProjectId($code);
        $this->projectIds[] = $projectId;

        self::assertTrue($projects->exists($projectId));
        self::assertTrue($projects->codeExists($code));
        self::assertSame(1, count(array_filter(
            $projects->getAll(),
            static fn (array $row): bool => (int) $row['id'] === $projectId,
        )));

        $projects->update($projectId, $code, 'Project After Edit', 'Updated description.');
        $updated = $this->connection->prepare('SELECT name, description FROM projects WHERE id = ?');
        $updated->execute([$projectId]);
        self::assertSame([
            'name' => 'Project After Edit',
            'description' => 'Updated description.',
        ], $updated->fetch());

        self::assertSame('deleted', $projects->deleteIfUnused($projectId));
        self::assertFalse($projects->exists($projectId));
    }

    public function testEvaluableItemCanBeCreatedReadUpdatedAndDeleted(): void
    {
        $code = $this->codePrefix . '_item';
        $items = new EvaluableItemModel($this->connection);

        $items->create($code, 'Item Before Edit', 'First description.');
        $itemId = $this->findItemId($code);
        $this->itemIds[] = $itemId;

        self::assertTrue($items->exists($itemId));
        self::assertTrue($items->codeExists($code));
        self::assertSame(1, count(array_filter(
            $items->getAll(),
            static fn (array $row): bool => (int) $row['id'] === $itemId,
        )));

        $items->update($itemId, $code, 'Item After Edit', 'Updated description.');
        $updated = $this->connection->prepare('SELECT name, description FROM evaluable_items WHERE id = ?');
        $updated->execute([$itemId]);
        self::assertSame([
            'name' => 'Item After Edit',
            'description' => 'Updated description.',
        ], $updated->fetch());

        self::assertSame('deleted', $items->deleteIfUnused($itemId));
        self::assertFalse($items->exists($itemId));
    }

    public function testAllProjectAssignmentTypesCanBeCreatedReadUpdatedAndDeleted(): void
    {
        $projectId = $this->createProject('assignment_project');
        $firstTeacher = $this->createUser('teacher', 'first_teacher');
        $secondTeacher = $this->createUser('teacher', 'second_teacher');
        $firstStudent = $this->createUser('student', 'first_student');
        $secondStudent = $this->createUser('student', 'second_student');
        $firstItem = $this->createItem('first_item');
        $secondItem = $this->createItem('second_item');
        $assignments = new ProjectAssignmentModel($this->connection);

        $assignments->createMany('teachers', $projectId, [$firstTeacher]);
        $assignments->createMany('students', $projectId, [$firstStudent]);
        $assignments->createMany('items', $projectId, [$firstItem]);

        $ids = $assignments->getIdsForAllProjects();
        self::assertSame([$firstTeacher], $ids[$projectId]['teachers']);
        self::assertSame([$firstStudent], $ids[$projectId]['students']);
        self::assertSame([$firstItem], $ids[$projectId]['items']);
        self::assertSame('Test first_teacher', $assignments->getAllForAdministrator()[$projectId]['teachers'][0]);

        self::assertNull($assignments->replaceForProject($projectId, [
            'teachers' => [$secondTeacher],
            'students' => [$secondStudent],
            'items' => [$secondItem],
        ]));

        $updatedIds = $assignments->getIdsForAllProjects()[$projectId];
        self::assertSame([$secondTeacher], $updatedIds['teachers']);
        self::assertSame([$secondStudent], $updatedIds['students']);
        self::assertSame([$secondItem], $updatedIds['items']);

        self::assertSame('deleted', $assignments->deleteOne('teachers', $projectId, $secondTeacher));
        self::assertSame('deleted', $assignments->deleteOne('students', $projectId, $secondStudent));
        self::assertSame('deleted', $assignments->deleteOne('items', $projectId, $secondItem));
        self::assertArrayNotHasKey($projectId, $assignments->getIdsForAllProjects());
    }

    public function testDeletesAreBlockedWhenRelatedGradesWouldBeAffected(): void
    {
        $projectId = $this->createProject('protected_project');
        $teacherId = $this->createUser('teacher', 'protected_teacher');
        $studentId = $this->createUser('student', 'protected_student');
        $itemId = $this->createItem('protected_item');
        $this->assignTeacher($teacherId, $projectId);
        $this->assignStudent($studentId, $projectId);
        $this->assignItem($itemId, $projectId);
        $this->createGrade($projectId, $studentId, $itemId, $teacherId, '8.0000');

        self::assertSame('has_assignments', (new UserModel($this->connection))->deleteIfUnassigned($teacherId));
        self::assertSame('has_assignments', (new UserModel($this->connection))->deleteIfUnassigned($studentId));
        self::assertSame('has_related_data', (new ProjectModel($this->connection))->deleteIfUnused($projectId));
        self::assertSame('has_related_data', (new EvaluableItemModel($this->connection))->deleteIfUnused($itemId));
        self::assertSame('has_grades', (new ProjectAssignmentModel($this->connection))
            ->deleteOne('teachers', $projectId, $teacherId));
        self::assertSame('has_grades', (new ProjectAssignmentModel($this->connection))
            ->deleteOne('students', $projectId, $studentId));
        self::assertSame('has_grades', (new ProjectAssignmentModel($this->connection))
            ->deleteOne('items', $projectId, $itemId));

        self::assertSame(1, $this->rowCount('projects', 'id', $projectId));
        self::assertSame(1, $this->rowCount('evaluable_items', 'id', $itemId));
        self::assertSame(1, $this->rowCount('grades', 'project_id', $projectId));
    }

    public function testUserValuesStayDataInPreparedQueries(): void
    {
        $maliciousValue = "' OR 1=1 --";
        $users = new UserModel($this->connection);
        $projects = new ProjectModel($this->connection);

        self::assertNull($users->findByUsername($maliciousValue));
        self::assertFalse($projects->codeExists($maliciousValue));
        self::assertFalse((bool) $this->connection->getAttribute(PDO::ATTR_EMULATE_PREPARES));
    }

    private function findProjectId(string $code): int
    {
        $statement = $this->connection->prepare('SELECT id FROM projects WHERE code = ?');
        $statement->execute([$code]);

        return (int) $statement->fetchColumn();
    }

    private function findItemId(string $code): int
    {
        $statement = $this->connection->prepare('SELECT id FROM evaluable_items WHERE code = ?');
        $statement->execute([$code]);

        return (int) $statement->fetchColumn();
    }

}
