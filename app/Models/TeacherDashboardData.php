<?php

declare(strict_types=1);

require_once __DIR__ . '/ProjectModel.php';

/** Loads only the projects and related records assigned to one teacher. */
class TeacherDashboardData
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        // Reuse the connection created by the application's Database class.
        $this->connection = $connection;
    }

    /** Build the project list, student list, and summary shown by teacher pages. */
    public function build(int $teacherId): array
    {
        $projects = (new ProjectModel($this->connection))->getForTeacher($teacherId);
        $projectsById = [];

        foreach ($projects as $project) {
            $projectId = (int) $project['id'];
            $project['id'] = $projectId;
            $project['students'] = [];
            $project['items'] = [];
            $projectsById[$projectId] = $project;
        }

        // Load students only through projects assigned to this teacher.
        $studentStatement = $this->connection->prepare(
            'SELECT projects.id AS project_id, users.id AS student_id, users.full_name
             FROM teacher_projects
             INNER JOIN projects ON projects.id = teacher_projects.project_id
             INNER JOIN student_projects ON student_projects.project_id = projects.id
             INNER JOIN users ON users.id = student_projects.student_id
             WHERE teacher_projects.teacher_id = :teacher_id
               AND users.role = :student_role
             ORDER BY users.full_name, projects.name',
        );
        $studentStatement->execute([
            'teacher_id' => $teacherId,
            'student_role' => 'student',
        ]);

        $studentsById = [];
        foreach ($studentStatement->fetchAll() as $row) {
            $projectId = (int) $row['project_id'];
            $studentId = (int) $row['student_id'];
            $student = [
                'id' => $studentId,
                'full_name' => $row['full_name'],
            ];

            $projectsById[$projectId]['students'][] = $student;

            // Keep one row per student on the teacher's student screen, even when
            // that student is assigned to more than one of the teacher's projects.
            if (!isset($studentsById[$studentId])) {
                $studentsById[$studentId] = [
                    'id' => $studentId,
                    'full_name' => $row['full_name'],
                    'projects' => [],
                ];
            }

            $studentsById[$studentId]['projects'][] = [
                'id' => $projectId,
                'code' => $projectsById[$projectId]['code'],
                'name' => $projectsById[$projectId]['name'],
            ];
        }

        // Load evaluable items only through the teacher's assigned projects.
        $itemStatement = $this->connection->prepare(
            'SELECT projects.id AS project_id,
                    evaluable_items.id AS item_id,
                    evaluable_items.code,
                    evaluable_items.name,
                    evaluable_items.description
             FROM teacher_projects
             INNER JOIN projects ON projects.id = teacher_projects.project_id
             INNER JOIN project_evaluable_items
                 ON project_evaluable_items.project_id = projects.id
             INNER JOIN evaluable_items
                 ON evaluable_items.id = project_evaluable_items.evaluable_item_id
             WHERE teacher_projects.teacher_id = :teacher_id
             ORDER BY projects.name, evaluable_items.name',
        );
        $itemStatement->execute(['teacher_id' => $teacherId]);

        foreach ($itemStatement->fetchAll() as $row) {
            $projectId = (int) $row['project_id'];
            $projectsById[$projectId]['items'][] = [
                'id' => (int) $row['item_id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'description' => $row['description'],
            ];
        }

        return [
            'projects' => array_values($projectsById),
            'students' => array_values($studentsById),
            'summary' => $this->getSummary($teacherId),
        ];
    }

    /** Count this teacher's saved grades and ungraded assigned combinations. */
    private function getSummary(int $teacherId): array
    {
        $gradeStatement = $this->connection->prepare(
            'SELECT COUNT(*) FROM grades WHERE teacher_id = :teacher_id',
        );
        $gradeStatement->execute(['teacher_id' => $teacherId]);

        $pendingStatement = $this->connection->prepare(
            'SELECT COUNT(*)
             FROM teacher_projects
             INNER JOIN student_projects
                 ON student_projects.project_id = teacher_projects.project_id
             INNER JOIN project_evaluable_items
                 ON project_evaluable_items.project_id = teacher_projects.project_id
             LEFT JOIN grades
                 ON grades.teacher_id = teacher_projects.teacher_id
                AND grades.project_id = teacher_projects.project_id
                AND grades.student_id = student_projects.student_id
                AND grades.evaluable_item_id = project_evaluable_items.evaluable_item_id
             WHERE teacher_projects.teacher_id = :teacher_id
               AND grades.id IS NULL',
        );
        $pendingStatement->execute(['teacher_id' => $teacherId]);

        return [
            'gradeCount' => (int) $gradeStatement->fetchColumn(),
            'pendingGradeCount' => (int) $pendingStatement->fetchColumn(),
        ];
    }
}
