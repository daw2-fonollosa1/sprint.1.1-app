<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/EvaluableItemModel.php';
require_once dirname(__DIR__) . '/Models/GradeModel.php';
require_once dirname(__DIR__) . '/Models/ProjectAssignmentModel.php';
require_once dirname(__DIR__) . '/Models/ProjectModel.php';
require_once dirname(__DIR__) . '/Models/StudentDashboardData.php';
require_once dirname(__DIR__) . '/Models/TeacherDashboardData.php';
require_once dirname(__DIR__) . '/Models/UserModel.php';

/** Collects the database rows needed to display application pages. */
class ReadOperationsController
{
    /**
     * Load the data for one already-authorized role and page.
     * DashboardController checks the session and database role before calling this method.
     */
    public function handle(
        PDO $connection,
        string $roleKey,
        string $pageKey,
        int $userId,
        ?string $requestedProjectId,
    ): array {
        $data = [
            'adminDashboardData' => [
                'projects' => [],
                'teachers' => [],
                'students' => [],
                'items' => [],
                'gradeCount' => 0,
            ],
            'managedUsers' => [],
            'formData' => $this->emptyFormData(),
            'teacherData' => [
                'projects' => [],
                'students' => [],
                'summary' => ['gradeCount' => 0, 'pendingGradeCount' => 0],
            ],
            'selectedProject' => null,
            'studentData' => $this->emptyStudentData(),
        ];

        if ($roleKey === 'admin') {
            $this->loadAdministratorData($connection, $pageKey, $data);
        } elseif ($roleKey === 'teacher') {
            $this->loadTeacherData($connection, $pageKey, $userId, $requestedProjectId, $data);
        } elseif ($roleKey === 'student') {
            $data['studentData'] = (new StudentDashboardData($connection))->build(
                $userId,
                $requestedProjectId,
            );
            $data['selectedProject'] = $data['studentData']['selectedProject'];
        }

        return $data;
    }

    /** Load administrator lists, totals, and the choices used by administrator forms. */
    private function loadAdministratorData(PDO $connection, string $pageKey, array &$data): void
    {
        $users = new UserModel($connection);

        if ($pageKey === 'dashboard') {
            $data['adminDashboardData'] = [
                'projects' => (new ProjectModel($connection))->getAll(),
                'teachers' => $users->getUsersByRole('teacher'),
                'students' => $users->getUsersByRole('student'),
                'items' => (new EvaluableItemModel($connection))->getAll(),
                'gradeCount' => (new GradeModel($connection))->countAll(),
            ];
        }

        if ($pageKey === 'users') {
            $data['managedUsers'] = $users->getManagedUsers();
        }

        if ($pageKey === 'projects') {
            $data['formData']['projects'] = (new ProjectModel($connection))->getAll();
            $data['formData']['teachers'] = $users->getUsersByRole('teacher');
            $data['formData']['students'] = $users->getUsersByRole('student');
            $data['formData']['items'] = (new EvaluableItemModel($connection))->getAll();
            $data['formData']['projectAssignments'] = (new ProjectAssignmentModel($connection))
                ->getAllForAdministrator();
            $data['formData']['projectAssignmentIds'] = (new ProjectAssignmentModel($connection))
                ->getIdsForAllProjects();
        }

        if ($pageKey === 'evaluable-items') {
            $data['formData']['items'] = (new EvaluableItemModel($connection))->getAll();
        }

        if ($pageKey === 'grades') {
            // These rows are needed to identify the individual records in the delete form.
            $data['formData']['adminGrades'] = (new GradeModel($connection))->getAllForAdministrator();
        }
    }

    /** Load only this teacher's projects and the choices linked to the selected project. */
    private function loadTeacherData(
        PDO $connection,
        string $pageKey,
        int $teacherId,
        ?string $requestedProjectId,
        array &$data,
    ): void {
        $data['teacherData'] = (new TeacherDashboardData($connection))->build($teacherId);
        $teacherProjects = $data['teacherData']['projects'];
        $data['selectedProject'] = $this->findProject($teacherProjects, $requestedProjectId);

        if ($pageKey === 'projects') {
            $data['formData']['teacherProjects'] = $teacherProjects;
        }

        if ($pageKey !== 'grades') {
            return;
        }

        $gradeModel = new GradeModel($connection);
        $data['formData']['teacherProjects'] = $gradeModel->getTeacherProjects($teacherId);
        $data['formData']['selectedProjectId'] = $this->selectTeacherProjectId(
            $data['formData']['teacherProjects'],
            $requestedProjectId,
        );

        if ($data['formData']['selectedProjectId'] === '') {
            return;
        }

        $projectId = (int) $data['formData']['selectedProjectId'];
        $data['formData']['gradeStudents'] = $gradeModel->getProjectStudents($projectId);
        $data['formData']['gradeItems'] = $gradeModel->getProjectItems($projectId);
        $data['formData']['teacherGrades'] = $gradeModel->getGradesForTeacherProject($teacherId, $projectId);
    }

    /** Keep a teacher's requested project only if it belongs to their assigned project list. */
    private function selectTeacherProjectId(array $projects, ?string $requestedProjectId): string
    {
        foreach ($projects as $project) {
            if ((string) $project['id'] === $requestedProjectId) {
                return $requestedProjectId;
            }
        }

        return isset($projects[0]['id']) ? (string) $projects[0]['id'] : '';
    }

    /** Find a project only inside the teacher's already-filtered project list. */
    private function findProject(array $projects, ?string $requestedProjectId): ?array
    {
        foreach ($projects as $project) {
            if ($requestedProjectId !== null && (string) $project['id'] === $requestedProjectId) {
                return $project;
            }
        }

        return null;
    }

    /** Give forms predictable empty keys before their database rows are loaded. */
    private function emptyFormData(): array
    {
        return [
            'projects' => [],
            'projectAssignments' => [],
            'projectAssignmentIds' => [],
            'items' => [],
            'teachers' => [],
            'students' => [],
            'teacherProjects' => [],
            'gradeStudents' => [],
            'gradeItems' => [],
            'teacherGrades' => [],
            'adminGrades' => [],
            'selectedProjectId' => '',
        ];
    }

    /** Keep student view data predictable until a valid student has been loaded. */
    private function emptyStudentData(): array
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
}
