<?php

declare(strict_types=1);

/** Reads and creates projects using the application's existing PDO connection. */
class ProjectModel
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        // Save the existing connection for the SQL methods below.
        $this->connection = $connection;
    }

    /** Return projects for the small lists and assignment form choices used by this issue. */
    public function getAll(): array
    {
        $statement = $this->connection->query(
            'SELECT id, code, name, description FROM projects ORDER BY name',
        );

        return $statement->fetchAll();
    }

    /** Return only projects already assigned to the selected teacher. */
    public function getForTeacher(int $teacherId): array
    {
        $statement = $this->connection->prepare(
            'SELECT projects.id, projects.code, projects.name, projects.description
             FROM projects
             INNER JOIN teacher_projects ON teacher_projects.project_id = projects.id
             WHERE teacher_projects.teacher_id = :teacher_id
             ORDER BY projects.name',
        );
        $statement->execute(['teacher_id' => $teacherId]);

        return $statement->fetchAll();
    }

    /** Check the unique project code before inserting, for a friendly validation message. */
    public function codeExists(string $code): bool
    {
        $statement = $this->connection->prepare(
            'SELECT id FROM projects WHERE code = :code LIMIT 1',
        );
        $statement->execute(['code' => $code]);

        return $statement->fetchColumn() !== false;
    }

    /** Check that an assignment form refers to a project that exists. */
    public function exists(int $projectId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT id FROM projects WHERE id = :id LIMIT 1',
        );
        $statement->execute(['id' => $projectId]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * Insert a project. When a teacher creates it, also assign that teacher to it.
     * Both inserts use one transaction so a teacher never gets a project without its assignment.
     */
    public function create(string $code, string $name, string $description, ?int $creatorTeacherId): void
    {
        $this->connection->beginTransaction();

        try {
            $statement = $this->connection->prepare(
                'INSERT INTO projects (code, name, description)
                 VALUES (:code, :name, :description)',
            );
            $statement->execute([
                'code' => $code,
                'name' => $name,
                'description' => $description,
            ]);

            // The schema has no project creator column. This existing relationship makes
            // a teacher-created project available to the teacher who created it.
            if ($creatorTeacherId !== null) {
                $assignment = $this->connection->prepare(
                    'INSERT INTO teacher_projects (teacher_id, project_id)
                     VALUES (:teacher_id, :project_id)',
                );
                $assignment->execute([
                    'teacher_id' => $creatorTeacherId,
                    'project_id' => (int) $this->connection->lastInsertId(),
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
