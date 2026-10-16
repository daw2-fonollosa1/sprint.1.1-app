<?php

declare(strict_types=1);

/** Loads one authenticated student's projects, items, and grades from MariaDB. */
class StudentDashboardData
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        // Keep using the PDO connection supplied by the application.
        $this->connection = $connection;
    }

    /** Prepare only the selected student's assigned records for the student views. */
    public function build(int $studentId, ?string $requestedProjectId): array
    {
        $studentStatement = $this->connection->prepare(
            'SELECT id, full_name
             FROM users
             WHERE id = :student_id AND role = :role
             LIMIT 1',
        );
        $studentStatement->execute([
            'student_id' => $studentId,
            'role' => 'student',
        ]);
        $student = $studentStatement->fetch();

        if ($student === false) {
            return $this->emptyData();
        }

        // The first query starts from this student's assignments. It cannot return
        // projects or items that belong only to another student.
        $projectStatement = $this->connection->prepare(
            'SELECT projects.id AS project_id,
                    projects.code AS project_code,
                    projects.name AS project_name,
                    projects.description AS project_description,
                    evaluable_items.id AS item_id,
                    evaluable_items.code AS item_code,
                    evaluable_items.name AS item_name,
                    evaluable_items.description AS item_description
             FROM student_projects
             INNER JOIN projects ON projects.id = student_projects.project_id
             LEFT JOIN project_evaluable_items
                 ON project_evaluable_items.project_id = projects.id
             LEFT JOIN evaluable_items
                 ON evaluable_items.id = project_evaluable_items.evaluable_item_id
             WHERE student_projects.student_id = :student_id
             ORDER BY projects.name, evaluable_items.name',
        );
        $projectStatement->execute(['student_id' => $studentId]);

        $projectsById = [];
        $itemPositions = [];

        foreach ($projectStatement->fetchAll() as $row) {
            $projectId = (int) $row['project_id'];

            if (!isset($projectsById[$projectId])) {
                $projectsById[$projectId] = [
                    'id' => $projectId,
                    'code' => $row['project_code'],
                    'name' => $row['project_name'],
                    'description' => $row['project_description'],
                    'items' => [],
                ];
            }

            // A project may have no item yet. Keep the project and skip the empty item row.
            if ($row['item_id'] === null) {
                continue;
            }

            $itemId = (int) $row['item_id'];
            $projectsById[$projectId]['items'][$itemId] = [
                'id' => $itemId,
                'code' => $row['item_code'],
                'name' => $row['item_name'],
                'description' => $row['item_description'],
                'teacherGrades' => [],
                'gradeCount' => 0,
                'average' => null,
            ];
            $itemPositions[$this->gradeKey($projectId, $itemId)] = [$projectId, $itemId];
        }

        $this->addIndividualGrades($studentId, $projectsById, $itemPositions);
        $this->addGradeAverages($studentId, $projectsById);

        $projects = array_values($projectsById);
        $gradeRows = $this->makeGradeRows($projects);
        $selectedProject = $this->findAssignedProject($projects, $requestedProjectId);
        $filter = $this->filterGradeRows($gradeRows, $projects, $requestedProjectId);
        $gradedItemCount = 0;

        foreach ($gradeRows as $gradeRow) {
            if ($gradeRow['average'] !== null) {
                $gradedItemCount++;
            }
        }

        return [
            'student' => [
                'id' => (int) $student['id'],
                'full_name' => $student['full_name'],
            ],
            'projects' => $projects,
            'gradeRows' => $gradeRows,
            'visibleGradeRows' => $filter['rows'],
            'filterError' => $filter['error'],
            'selectedProject' => $selectedProject,
            'selectedGradeProjectId' => $filter['projectId'],
            'assignedItemCount' => count($gradeRows),
            'gradedItemCount' => $gradedItemCount,
            'pendingItemCount' => count($gradeRows) - $gradedItemCount,
        ];
    }

    /** Add each teacher's grade, filtered by the authenticated student ID. */
    private function addIndividualGrades(int $studentId, array &$projects, array $itemPositions): void
    {
        $statement = $this->connection->prepare(
            'SELECT grades.project_id,
                    grades.evaluable_item_id,
                    users.full_name AS teacher_name,
                    grades.grade_value
             FROM grades
             INNER JOIN users ON users.id = grades.teacher_id
             WHERE grades.student_id = :student_id
             ORDER BY grades.project_id, grades.evaluable_item_id, users.full_name',
        );
        $statement->execute(['student_id' => $studentId]);

        foreach ($statement->fetchAll() as $row) {
            $key = $this->gradeKey((int) $row['project_id'], (int) $row['evaluable_item_id']);

            // Only add grades for an item already found in this student's project list.
            if (!isset($itemPositions[$key])) {
                continue;
            }

            [$projectId, $itemId] = $itemPositions[$key];
            $projects[$projectId]['items'][$itemId]['teacherGrades'][] = [
                'name' => $row['teacher_name'],
                'value' => $row['grade_value'],
            ];
        }
    }

    /** Ask MariaDB for one mean per student, project, and evaluable item. */
    private function addGradeAverages(int $studentId, array &$projects): void
    {
        $statement = $this->connection->prepare(
            'SELECT project_id,
                    evaluable_item_id,
                    COUNT(*) AS grade_count,
                    AVG(grade_value) AS average
             FROM grades
             WHERE student_id = :student_id
             GROUP BY project_id, evaluable_item_id',
        );
        $statement->execute(['student_id' => $studentId]);

        foreach ($statement->fetchAll() as $row) {
            $projectId = (int) $row['project_id'];
            $itemId = (int) $row['evaluable_item_id'];

            if (isset($projects[$projectId]['items'][$itemId])) {
                $projects[$projectId]['items'][$itemId]['gradeCount'] = (int) $row['grade_count'];
                // Keep MariaDB's decimal result as text; do not add a PHP rounding rule.
                $projects[$projectId]['items'][$itemId]['average'] = (string) $row['average'];
            }
        }
    }

    /** Flatten project items into rows used by the dashboard and grade list. */
    private function makeGradeRows(array $projects): array
    {
        $gradeRows = [];

        foreach ($projects as $project) {
            foreach ($project['items'] as $item) {
                $gradeRows[] = [
                    'projectId' => $project['id'],
                    'projectName' => $project['name'],
                    'projectCode' => $project['code'],
                    'itemId' => $item['id'],
                    'itemName' => $item['name'],
                    'itemCode' => $item['code'],
                    'itemDescription' => $item['description'],
                    'teacherGrades' => $item['teacherGrades'],
                    'gradeCount' => $item['gradeCount'],
                    'average' => $item['average'],
                ];
            }
        }

        return $gradeRows;
    }

    /** Find a project only inside the student's own assigned project list. */
    private function findAssignedProject(array $projects, ?string $requestedProjectId): ?array
    {
        foreach ($projects as $project) {
            if ((string) $project['id'] === $requestedProjectId) {
                return $project;
            }
        }

        return null;
    }

    /** Apply a project filter only when it matches one of this student's projects. */
    private function filterGradeRows(array $gradeRows, array $projects, ?string $requestedProjectId): array
    {
        if ($requestedProjectId === null || $requestedProjectId === '') {
            return ['rows' => $gradeRows, 'projectId' => '', 'error' => ''];
        }

        foreach ($projects as $project) {
            if ((string) $project['id'] === $requestedProjectId) {
                $filteredRows = array_values(array_filter(
                    $gradeRows,
                    static fn (array $row): bool => (string) $row['projectId'] === $requestedProjectId,
                ));

                return ['rows' => $filteredRows, 'projectId' => $requestedProjectId, 'error' => ''];
            }
        }

        return [
            'rows' => [],
            'projectId' => '',
            'error' => 'El projecte seleccionat no està assignat al teu compte.',
        ];
    }

    /** Build a consistent empty result if the session ID is not a student account. */
    private function emptyData(): array
    {
        return [
            'student' => null,
            'projects' => [],
            'gradeRows' => [],
            'visibleGradeRows' => [],
            'filterError' => '',
            'selectedProject' => null,
            'selectedGradeProjectId' => '',
            'assignedItemCount' => 0,
            'gradedItemCount' => 0,
            'pendingItemCount' => 0,
        ];
    }

    /** Make the same simple key for a project's item in the small result arrays. */
    private function gradeKey(int $projectId, int $itemId): string
    {
        return $projectId . ':' . $itemId;
    }
}
