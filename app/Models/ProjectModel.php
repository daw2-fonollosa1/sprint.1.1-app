<?php

declare(strict_types=1);

/** Reads, creates, updates, and safely deletes projects through PDO. */
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
    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        if ($exceptId === null) {
            $statement = $this->connection->prepare(
                'SELECT id FROM projects WHERE code = :code LIMIT 1',
            );
            $statement->execute(['code' => $code]);
        } else {
            $statement = $this->connection->prepare(
                'SELECT id FROM projects WHERE code = :code AND id <> :except_id LIMIT 1',
            );
            $statement->execute(['code' => $code, 'except_id' => $exceptId]);
        }

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

    /** Check whether the teacher is linked to the selected project. */
    public function isAssignedToTeacher(int $projectId, int $teacherId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT project_id
             FROM teacher_projects
             WHERE project_id = :project_id AND teacher_id = :teacher_id
             LIMIT 1',
        );
        $statement->execute(['project_id' => $projectId, 'teacher_id' => $teacherId]);

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

    /** Update the editable project fields while keeping its ID and assignments unchanged. */
    public function update(int $id, string $code, string $name, string $description): void
    {
        $statement = $this->connection->prepare(
            'UPDATE projects
             SET code = :code, name = :name, description = :description
             WHERE id = :id',
        );
        $statement->execute([
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'description' => $description,
        ]);
    }

    /** Delete a project only when it has no linked assignments or grades. */
    public function deleteIfUnused(int $id): string
    {
        $this->connection->beginTransaction();

        try {
            // Lock the project row while checking its children so a new assignment
            // cannot appear between the safety check and the DELETE statement.
            $project = $this->connection->prepare(
                'SELECT id FROM projects WHERE id = :id FOR UPDATE',
            );
            $project->execute(['id' => $id]);

            if ($project->fetchColumn() === false) {
                $this->connection->rollBack();
                return 'missing';
            }

            $relatedRows = $this->connection->prepare(
                'SELECT
                    EXISTS (SELECT 1 FROM teacher_projects WHERE project_id = :teacher_project_id)
                    OR EXISTS (SELECT 1 FROM student_projects WHERE project_id = :student_project_id)
                    OR EXISTS (SELECT 1 FROM project_evaluable_items WHERE project_id = :item_project_id)
                    OR EXISTS (SELECT 1 FROM grades WHERE project_id = :grade_project_id)',
            );
            $relatedRows->execute([
                'teacher_project_id' => $id,
                'student_project_id' => $id,
                'item_project_id' => $id,
                'grade_project_id' => $id,
            ]);

            if ((bool) $relatedRows->fetchColumn()) {
                $this->connection->rollBack();
                return 'has_related_data';
            }

            $delete = $this->connection->prepare('DELETE FROM projects WHERE id = :id');
            $delete->execute(['id' => $id]);
            $this->connection->commit();

            return $delete->rowCount() > 0 ? 'deleted' : 'missing';
        } catch (Throwable $error) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $error;
        }
    }
}
