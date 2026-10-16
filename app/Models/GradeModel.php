<?php

declare(strict_types=1);

/** Reads grade-form choices and inserts one teacher's individual grade. */
class GradeModel
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        // Keep the shared PDO connection so each method can use the same database link.
        $this->connection = $connection;
    }

    /** Return projects assigned to this teacher for the grade form. */
    public function getTeacherProjects(int $teacherId): array
    {
        $statement = $this->connection->prepare(
            'SELECT projects.id, projects.code, projects.name
             FROM projects
             INNER JOIN teacher_projects ON teacher_projects.project_id = projects.id
             WHERE teacher_projects.teacher_id = :teacher_id
             ORDER BY projects.name',
        );
        $statement->execute(['teacher_id' => $teacherId]);

        return $statement->fetchAll();
    }

    /** Return students already assigned to one project. */
    public function getProjectStudents(int $projectId): array
    {
        $statement = $this->connection->prepare(
            'SELECT users.id, users.full_name
             FROM users
             INNER JOIN student_projects ON student_projects.student_id = users.id
             WHERE student_projects.project_id = :project_id AND users.role = :role
             ORDER BY users.full_name',
        );
        $statement->execute(['project_id' => $projectId, 'role' => 'student']);

        return $statement->fetchAll();
    }

    /** Return evaluable items already linked to one project. */
    public function getProjectItems(int $projectId): array
    {
        $statement = $this->connection->prepare(
            'SELECT evaluable_items.id, evaluable_items.code, evaluable_items.name
             FROM evaluable_items
             INNER JOIN project_evaluable_items
                 ON project_evaluable_items.evaluable_item_id = evaluable_items.id
             WHERE project_evaluable_items.project_id = :project_id
             ORDER BY evaluable_items.name',
        );
        $statement->execute(['project_id' => $projectId]);

        return $statement->fetchAll();
    }

    /** Check that the selected teacher is assigned to the project being graded. */
    public function teacherHasProject(int $teacherId, int $projectId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT teacher_projects.project_id
             FROM teacher_projects
             INNER JOIN users ON users.id = teacher_projects.teacher_id
             WHERE teacher_projects.teacher_id = :teacher_id
               AND teacher_projects.project_id = :project_id
               AND users.role = :role
             LIMIT 1',
        );
        $statement->execute([
            'teacher_id' => $teacherId,
            'project_id' => $projectId,
            'role' => 'teacher',
        ]);

        return $statement->fetchColumn() !== false;
    }

    /** Check that the selected student is part of the project being graded. */
    public function studentHasProject(int $studentId, int $projectId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT student_projects.student_id
             FROM student_projects
             INNER JOIN users ON users.id = student_projects.student_id
             WHERE student_projects.student_id = :student_id
               AND student_projects.project_id = :project_id
               AND users.role = :role
             LIMIT 1',
        );
        $statement->execute([
            'student_id' => $studentId,
            'project_id' => $projectId,
            'role' => 'student',
        ]);

        return $statement->fetchColumn() !== false;
    }

    /** Check that the selected evaluable item is linked to the project. */
    public function itemHasProject(int $itemId, int $projectId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT evaluable_item_id FROM project_evaluable_items
             WHERE evaluable_item_id = :item_id AND project_id = :project_id LIMIT 1',
        );
        $statement->execute([
            'item_id' => $itemId,
            'project_id' => $projectId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    /** Insert a separate row for this teacher; a duplicate by this teacher is rejected. */
    public function create(
        int $projectId,
        int $studentId,
        int $itemId,
        int $teacherId,
        string $gradeValue,
    ): void {
        $statement = $this->connection->prepare(
            'INSERT INTO grades (project_id, student_id, evaluable_item_id, teacher_id, grade_value)
             VALUES (:project_id, :student_id, :item_id, :teacher_id, :grade_value)',
        );
        $statement->execute([
            'project_id' => $projectId,
            'student_id' => $studentId,
            'item_id' => $itemId,
            'teacher_id' => $teacherId,
            'grade_value' => $gradeValue,
        ]);
    }
}
