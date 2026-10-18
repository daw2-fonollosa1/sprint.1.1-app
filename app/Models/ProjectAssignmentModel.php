<?php

declare(strict_types=1);

/** Reads, creates, updates, and safely deletes project assignment links. */
class ProjectAssignmentModel
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        // Save the existing connection for the SQL methods below.
        $this->connection = $connection;
    }

    /**
     * Return the teacher, student, and item names already linked to each project.
     * These rows are read-only and are used by the administrator's project table.
     */
    public function getAllForAdministrator(): array
    {
        $queries = [
            'teachers' => "SELECT teacher_projects.project_id, users.full_name AS label
                           FROM teacher_projects
                           INNER JOIN users ON users.id = teacher_projects.teacher_id
                           WHERE users.role = 'teacher'
                           ORDER BY users.full_name",
            'students' => "SELECT student_projects.project_id, users.full_name AS label
                           FROM student_projects
                           INNER JOIN users ON users.id = student_projects.student_id
                           WHERE users.role = 'student'
                           ORDER BY users.full_name",
            'items' => "SELECT project_evaluable_items.project_id,
                               CONCAT(evaluable_items.code, ' · ', evaluable_items.name) AS label
                        FROM project_evaluable_items
                        INNER JOIN evaluable_items
                            ON evaluable_items.id = project_evaluable_items.evaluable_item_id
                        ORDER BY evaluable_items.name",
        ];
        $assignments = [];

        foreach ($queries as $type => $sql) {
            $rows = $this->connection->query($sql)->fetchAll();

            foreach ($rows as $row) {
                $projectId = (int) $row['project_id'];

                if (!isset($assignments[$projectId])) {
                    $assignments[$projectId] = [
                        'teachers' => [],
                        'students' => [],
                        'items' => [],
                    ];
                }

                $assignments[$projectId][$type][] = $row['label'];
            }
        }

        return $assignments;
    }

    /** Return assigned database IDs so edit forms can mark the current checkboxes. */
    public function getIdsForAllProjects(): array
    {
        $queries = [
            'teachers' => 'SELECT project_id, teacher_id AS record_id FROM teacher_projects',
            'students' => 'SELECT project_id, student_id AS record_id FROM student_projects',
            'items' => 'SELECT project_id, evaluable_item_id AS record_id FROM project_evaluable_items',
        ];
        $assignments = [];

        foreach ($queries as $type => $sql) {
            foreach ($this->connection->query($sql)->fetchAll() as $row) {
                $projectId = (int) $row['project_id'];
                $assignments[$projectId][$type][] = (int) $row['record_id'];
            }
        }

        return $assignments;
    }

    /**
     * Insert a selected group of links as one all-or-nothing operation.
     * The form action selects one of these fixed SQL statements; user input never selects a table.
     */
    public function createMany(string $type, int $projectId, array $recordIds): void
    {
        $sqlByType = [
            'teachers' => 'INSERT INTO teacher_projects (teacher_id, project_id)
                           VALUES (:record_id, :project_id)',
            'students' => 'INSERT INTO student_projects (student_id, project_id)
                           VALUES (:record_id, :project_id)',
            'items' => 'INSERT INTO project_evaluable_items (project_id, evaluable_item_id)
                        VALUES (:project_id, :record_id)',
        ];

        if (!isset($sqlByType[$type])) {
            throw new InvalidArgumentException('Unknown project assignment type.');
        }

        $statement = $this->connection->prepare($sqlByType[$type]);
        $this->connection->beginTransaction();

        try {
            foreach ($recordIds as $recordId) {
                $statement->execute([
                    'project_id' => $projectId,
                    'record_id' => $recordId,
                ]);
            }

            $this->connection->commit();
        } catch (Throwable $error) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $error;
        }
    }

    /**
     * Replace all assignment choices for one project as one transaction.
     * Return the first assignment type that cannot be removed because it has grades.
     */
    public function replaceForProject(int $projectId, array $selectedIds): ?string
    {
        $types = $this->assignmentTypes();
        $currentIds = [];
        $removedIds = [];

        $this->connection->beginTransaction();

        try {
            // Lock current links while the submitted selection is being applied.
            foreach ($types as $type => $settings) {
                $statement = $this->connection->prepare(
                    'SELECT ' . $settings['recordColumn'] . ' FROM ' . $settings['table']
                    . ' WHERE project_id = :project_id FOR UPDATE',
                );
                $statement->execute(['project_id' => $projectId]);
                $currentIds[$type] = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
                $removedIds[$type] = array_values(array_diff($currentIds[$type], $selectedIds[$type]));
            }

            // The schema cascades assignment removals to grades, so stop before any
            // change when one of the removed links is still used by a saved grade.
            foreach ($removedIds as $type => $ids) {
                if ($ids !== [] && $this->hasGradesForAssignment($type, $projectId, $ids)) {
                    $this->connection->rollBack();
                    return $type;
                }
            }

            foreach ($types as $type => $settings) {
                $this->saveAssignmentChanges(
                    $type,
                    $projectId,
                    $settings,
                    $currentIds[$type],
                    $selectedIds[$type],
                );
            }

            $this->connection->commit();

            return null;
        } catch (Throwable $error) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $error;
        }
    }

    /** Delete one teacher, student, or item link if no grade uses that relationship. */
    public function deleteOne(string $type, int $projectId, int $recordId): string
    {
        $settingsByType = $this->assignmentTypes();

        if (!isset($settingsByType[$type])) {
            return 'invalid_type';
        }

        $settings = $settingsByType[$type];
        $this->connection->beginTransaction();

        try {
            // Table and column names come only from the fixed map above, never from the form.
            $link = $this->connection->prepare(
                'SELECT ' . $settings['recordColumn'] . ' FROM ' . $settings['table']
                . ' WHERE project_id = :project_id AND ' . $settings['recordColumn'] . ' = :record_id'
                . ' FOR UPDATE',
            );
            $link->execute(['project_id' => $projectId, 'record_id' => $recordId]);

            if ($link->fetchColumn() === false) {
                $this->connection->rollBack();
                return 'missing';
            }

            $grade = $this->connection->prepare(
                'SELECT id FROM grades
                 WHERE project_id = :project_id AND ' . $settings['gradeColumn'] . ' = :record_id
                 LIMIT 1',
            );
            $grade->execute(['project_id' => $projectId, 'record_id' => $recordId]);

            if ($grade->fetchColumn() !== false) {
                $this->connection->rollBack();
                return 'has_grades';
            }

            $delete = $this->connection->prepare($settings['deleteSql']);
            $delete->execute(['project_id' => $projectId, 'record_id' => $recordId]);
            $this->connection->commit();

            return $delete->rowCount() > 0 ? 'deleted' : 'missing';
        } catch (Throwable $error) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $error;
        }
    }

    /** Keep SQL table and column names fixed in code instead of accepting form values. */
    private function assignmentTypes(): array
    {
        return [
            'teachers' => [
                'table' => 'teacher_projects',
                'recordColumn' => 'teacher_id',
                'gradeColumn' => 'teacher_id',
                'insertSql' => 'INSERT INTO teacher_projects (teacher_id, project_id) VALUES (:record_id, :project_id)',
                'deleteSql' => 'DELETE FROM teacher_projects WHERE teacher_id = :record_id AND project_id = :project_id',
            ],
            'students' => [
                'table' => 'student_projects',
                'recordColumn' => 'student_id',
                'gradeColumn' => 'student_id',
                'insertSql' => 'INSERT INTO student_projects (student_id, project_id) VALUES (:record_id, :project_id)',
                'deleteSql' => 'DELETE FROM student_projects WHERE student_id = :record_id AND project_id = :project_id',
            ],
            'items' => [
                'table' => 'project_evaluable_items',
                'recordColumn' => 'evaluable_item_id',
                'gradeColumn' => 'evaluable_item_id',
                'insertSql' => 'INSERT INTO project_evaluable_items (project_id, evaluable_item_id) VALUES (:project_id, :record_id)',
                'deleteSql' => 'DELETE FROM project_evaluable_items WHERE project_id = :project_id AND evaluable_item_id = :record_id',
            ],
        ];
    }

    /** Save only the added and removed rows so unchanged relations stay untouched. */
    private function saveAssignmentChanges(
        string $type,
        int $projectId,
        array $settings,
        array $currentIds,
        array $selectedIds,
    ): void {
        $delete = $this->connection->prepare($settings['deleteSql']);
        foreach (array_diff($currentIds, $selectedIds) as $recordId) {
            $delete->execute(['project_id' => $projectId, 'record_id' => $recordId]);
        }

        $insert = $this->connection->prepare($settings['insertSql']);
        foreach (array_diff($selectedIds, $currentIds) as $recordId) {
            $insert->execute(['project_id' => $projectId, 'record_id' => $recordId]);
        }
    }

    /** Check the grades table before removing a relationship that it references. */
    private function hasGradesForAssignment(string $type, int $projectId, array $recordIds): bool
    {
        $gradeColumn = $this->assignmentTypes()[$type]['gradeColumn'];
        $placeholders = [];
        $values = ['project_id' => $projectId];

        foreach ($recordIds as $index => $recordId) {
            $placeholder = 'record_' . $index;
            $placeholders[] = ':' . $placeholder;
            $values[$placeholder] = $recordId;
        }

        $sql = 'SELECT id FROM grades WHERE project_id = :project_id AND ' . $gradeColumn
            . ' IN (' . implode(', ', $placeholders) . ') LIMIT 1';
        $statement = $this->connection->prepare($sql);
        $statement->execute($values);

        return $statement->fetchColumn() !== false;
    }
}
