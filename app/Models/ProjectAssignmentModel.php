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
