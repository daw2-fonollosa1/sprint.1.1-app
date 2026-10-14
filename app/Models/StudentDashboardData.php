<?php

declare(strict_types=1);

/**
 * Prepares the project and grade rows shown by the student pages.
 * The input is sample data; this class does not provide real access control.
 */
class StudentDashboardData
{
    public static function build(
        string $studentId,
        array $demoData,
        ?string $requestedProjectId,
    ): array {
        // Key the sample records by ID so templates can find a user or item directly.
        $usersById = array_column($demoData['users'], null, 'id');
        $itemsById = array_column($demoData['items'], null, 'id');
        $student = $usersById[$studentId] ?? null;

        // Only keep projects where this fixed demo student is assigned.
        $studentProjects = array_values(array_filter(
            $demoData['projects'],
            static fn (array $project): bool => in_array($studentId, $project['studentIds'], true),
        ));
        $studentProjectIds = array_column($studentProjects, 'id');

        // Resolve project details only from this student's assigned project list.
        $selectedProject = null;
        if ($requestedProjectId !== null && in_array($requestedProjectId, $studentProjectIds, true)) {
            foreach ($studentProjects as $project) {
                if ($project['id'] === $requestedProjectId) {
                    $selectedProject = $project;
                    break;
                }
            }
        }

        // Filter grades by both student and project before preparing anything for a view.
        $studentGrades = array_values(array_filter(
            $demoData['grades'],
            static fn (array $grade): bool => $grade['studentId'] === $studentId
                && in_array($grade['projectId'], $studentProjectIds, true),
        ));
        $gradesByProjectAndItem = [];
        foreach ($studentGrades as $grade) {
            $gradesByProjectAndItem[$grade['projectId']][$grade['itemId']] = $grade;
        }

        // Create a row for every assigned project item, including items that are still pending.
        $gradeRows = [];
        foreach ($studentProjects as $project) {
            foreach ($project['itemIds'] as $itemId) {
                $item = $itemsById[$itemId] ?? null;
                if ($item === null) {
                    continue;
                }

                $gradeEntry = $gradesByProjectAndItem[$project['id']][$itemId] ?? null;
                $teacherGrades = [];

                // Convert each teacher ID to a name for the visible grade breakdown.
                foreach ($gradeEntry['teacherGrades'] ?? [] as $teacherId => $gradeValue) {
                    $teacher = $usersById[$teacherId] ?? null;
                    if ($teacher !== null) {
                        $teacherGrades[] = ['name' => $teacher['name'], 'value' => $gradeValue];
                    }
                }

                $gradeValues = array_column($teacherGrades, 'value');
                $gradeCount = count($gradeValues);
                $gradeRows[] = [
                    'projectId' => $project['id'],
                    'projectCode' => $project['code'],
                    'projectName' => $project['name'],
                    'itemId' => $item['id'],
                    'itemCode' => $item['code'],
                    'itemName' => $item['name'],
                    'itemDescription' => $item['description'],
                    'teacherGrades' => $teacherGrades,
                    'gradeCount' => $gradeCount,
                    'average' => $gradeCount === 0 ? null : array_sum($gradeValues) / $gradeCount,
                ];
            }
        }

        // The qualifications filter accepts an assigned project or shows all assigned projects.
        $selectedGradeProjectId = $requestedProjectId !== null
            && in_array($requestedProjectId, $studentProjectIds, true)
            ? $requestedProjectId
            : '';
        $visibleGradeRows = $selectedGradeProjectId === ''
            ? $gradeRows
            : array_values(array_filter(
                $gradeRows,
                static fn (array $row): bool => $row['projectId'] === $selectedGradeProjectId,
            ));

        $gradedItemCount = count(array_filter(
            $gradeRows,
            static fn (array $row): bool => $row['average'] !== null,
        ));

        return [
            'student' => $student,
            'projects' => $studentProjects,
            'selectedProject' => $selectedProject,
            'gradeRows' => $gradeRows,
            'visibleGradeRows' => $visibleGradeRows,
            'selectedGradeProjectId' => $selectedGradeProjectId,
            'gradedItemCount' => $gradedItemCount,
            'pendingItemCount' => count($gradeRows) - $gradedItemCount,
        ];
    }
}
