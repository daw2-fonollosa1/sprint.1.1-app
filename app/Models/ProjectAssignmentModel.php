<?php

declare(strict_types=1);

/** Creates teacher, student, and evaluable-item links for a project. */
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
}
