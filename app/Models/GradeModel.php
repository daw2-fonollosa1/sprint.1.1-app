<?php

declare(strict_types=1);

/** Reads grade-form choices and manages individual teacher grade rows. */
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

    /** Return the signed-in teacher's grades for one selected project. */
    public function getGradesForTeacherProject(int $teacherId, int $projectId): array
    {
        $statement = $this->connection->prepare(
            'SELECT grades.id, grades.grade_value, projects.id AS project_id,
                    projects.code AS project_code, projects.name AS project_name,
                    students.full_name AS student_name,
                    evaluable_items.code AS item_code, evaluable_items.name AS item_name
             FROM grades
             INNER JOIN projects ON projects.id = grades.project_id
             INNER JOIN users AS students ON students.id = grades.student_id
             INNER JOIN evaluable_items ON evaluable_items.id = grades.evaluable_item_id
             WHERE grades.teacher_id = :teacher_id AND grades.project_id = :project_id
             ORDER BY students.full_name, evaluable_items.name',
        );
        $statement->execute(['teacher_id' => $teacherId, 'project_id' => $projectId]);

        return $statement->fetchAll();
    }

    /** Return the grade details needed by the administrator's grade-management page. */
    public function getAllForAdministrator(): array
    {
        $statement = $this->connection->query(
            'SELECT grades.id, grades.grade_value, projects.code AS project_code,
                    projects.name AS project_name, students.full_name AS student_name,
                    teachers.full_name AS teacher_name, evaluable_items.code AS item_code,
                    evaluable_items.name AS item_name
             FROM grades
             INNER JOIN projects ON projects.id = grades.project_id
             INNER JOIN users AS students ON students.id = grades.student_id
             INNER JOIN users AS teachers ON teachers.id = grades.teacher_id
             INNER JOIN evaluable_items ON evaluable_items.id = grades.evaluable_item_id
             ORDER BY projects.name, students.full_name, evaluable_items.name, teachers.full_name',
        );

        return $statement->fetchAll();
    }

    /** Find one grade only when it belongs to the teacher making the request. */
    public function findForTeacher(int $gradeId, int $teacherId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, project_id, student_id, evaluable_item_id, teacher_id, grade_value
             FROM grades
             WHERE id = :id AND teacher_id = :teacher_id
             LIMIT 1',
        );
        $statement->execute(['id' => $gradeId, 'teacher_id' => $teacherId]);
        $grade = $statement->fetch();

        return $grade === false ? null : $grade;
    }

    /** Find one grade row for an administrator's delete request. */
    public function findById(int $gradeId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, project_id, teacher_id FROM grades WHERE id = :id LIMIT 1',
        );
        $statement->execute(['id' => $gradeId]);
        $grade = $statement->fetch();

        return $grade === false ? null : $grade;
    }

    /** Return the total number of individual grades for the administrator dashboard. */
    public function countAll(): int
    {
        $statement = $this->connection->query('SELECT COUNT(*) FROM grades');

        return (int) $statement->fetchColumn();
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

    /** Update only the grade value; the teacher and all related IDs remain unchanged. */
    public function updateOwnGrade(int $gradeId, int $teacherId, string $gradeValue): void
    {
        $statement = $this->connection->prepare(
            'UPDATE grades
             SET grade_value = :grade_value
             WHERE id = :id AND teacher_id = :teacher_id',
        );
        $statement->execute([
            'grade_value' => $gradeValue,
            'id' => $gradeId,
            'teacher_id' => $teacherId,
        ]);
    }

    /** Delete exactly one grade row, optionally requiring the teacher who entered it. */
    public function deleteOne(int $gradeId, ?int $teacherId = null): bool
    {
        if ($teacherId === null) {
            $statement = $this->connection->prepare('DELETE FROM grades WHERE id = :id');
            $statement->execute(['id' => $gradeId]);
        } else {
            $statement = $this->connection->prepare(
                'DELETE FROM grades WHERE id = :id AND teacher_id = :teacher_id',
            );
            $statement->execute(['id' => $gradeId, 'teacher_id' => $teacherId]);
        }

        return $statement->rowCount() > 0;
    }
}
