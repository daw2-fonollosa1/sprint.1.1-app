<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/RoleAuthorization.php';
require_once dirname(__DIR__) . '/Models/EvaluableItemModel.php';
require_once dirname(__DIR__) . '/Models/GradeModel.php';
require_once dirname(__DIR__) . '/Models/ProjectAssignmentModel.php';
require_once dirname(__DIR__) . '/Models/ProjectModel.php';
require_once dirname(__DIR__) . '/Models/UserModel.php';

/** Validates create-only forms and passes approved values to the PDO models. */
class CreateOperationsController
{
    /**
     * Handle one application section and prepare the minimal database data its forms need.
     * The role in the URL selects a prototype page only; permissions come from RoleAuthorization.
     */
    public function handle(string $section, string $previewRole): array
    {
        $state = [
            'feedback' => $this->feedbackFromUrl(),
            'accessError' => '',
            'databaseError' => '',
            'validationErrors' => [],
            'submitted' => [],
            'projects' => [],
            'items' => [],
            'teachers' => [],
            'students' => [],
            'teacherProjects' => [],
            'gradeStudents' => [],
            'gradeItems' => [],
            'selectedProjectId' => '',
        ];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost($section, $previewRole, $state);
        }

        $pageRole = $this->requiredRoleForPage($section, $previewRole);
        if ($pageRole !== '' && !RoleAuthorization::hasRole($pageRole) && $state['accessError'] === '') {
            $state['accessError'] = 'Cal iniciar una sessió vàlida amb el rol corresponent. El login actual és només una demostració.';
        }

        try {
            $connection = Database::getConnection();
            $projects = new ProjectModel($connection);
            $items = new EvaluableItemModel($connection);
            $users = new UserModel($connection);

            if ($section === 'projects' && $previewRole === 'admin') {
                $state['projects'] = $projects->getAll();
                $state['teachers'] = $users->getUsersByRole('teacher');
                $state['students'] = $users->getUsersByRole('student');
                $state['items'] = $items->getAll();
            } elseif ($section === 'evaluable-items' && $previewRole === 'admin') {
                $state['items'] = $items->getAll();
            } elseif ($section === 'projects' && $previewRole === 'teacher') {
                $sessionUser = RoleAuthorization::currentUser();
                if ($sessionUser !== null && $sessionUser['role'] === 'teacher') {
                    $state['teacherProjects'] = $projects->getForTeacher($sessionUser['id']);
                }
            } elseif ($section === 'grades' && $previewRole === 'teacher') {
                $sessionUser = RoleAuthorization::currentUser();
                if ($sessionUser !== null && $sessionUser['role'] === 'teacher') {
                    $gradeModel = new GradeModel($connection);
                    $state['teacherProjects'] = $gradeModel->getTeacherProjects($sessionUser['id']);
                    $state['selectedProjectId'] = $this->selectedProjectId($state['teacherProjects']);

                    if ($state['selectedProjectId'] !== '') {
                        $projectId = (int) $state['selectedProjectId'];
                        $state['gradeStudents'] = $gradeModel->getProjectStudents($projectId);
                        $state['gradeItems'] = $gradeModel->getProjectItems($projectId);
                    }
                }
            }

        } catch (Throwable $error) {
            // Keep connection details and SQL diagnostics out of the Catalan interface.
            error_log('Create operation page data failed: ' . $error->getMessage());
            $state['databaseError'] = 'No s’han pogut carregar les dades. Revisa la connexió amb la base de dades.';
        }

        return $state;
    }

    /** Check the role before reading form fields or opening the database for a write. */
    private function handlePost(string $section, string $previewRole, array &$state): void
    {
        $action = $this->postString('action');
        $requiredRole = $this->requiredRoleForAction($action, $section, $previewRole);

        if ($requiredRole === '') {
            http_response_code(400);
            $state['validationErrors'][] = 'No s’ha reconegut l’operació sol·licitada.';
            return;
        }

        if (!RoleAuthorization::hasRole($requiredRole)) {
            http_response_code(403);
            $state['accessError'] = 'No s’ha pogut verificar el teu rol al servidor. Cal implementar l’autenticació abans de crear dades.';
            return;
        }

        try {
            $connection = Database::getConnection();

            switch ($action) {
                case 'create_project':
                    $this->createProject($connection, $state);
                    break;
                case 'create_item':
                    $this->createItem($connection, $state);
                    break;
                case 'assign_teachers':
                    $this->createAssignments($connection, 'teachers', $state);
                    break;
                case 'assign_students':
                    $this->createAssignments($connection, 'students', $state);
                    break;
                case 'assign_items':
                    $this->createAssignments($connection, 'items', $state);
                    break;
                case 'create_grade':
                    $this->createGrade($connection, $state);
                    break;
            }
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                $state['validationErrors'][] = $this->duplicateMessage($action);
                return;
            }

            error_log('Create operation database request failed: ' . $error->getMessage());
            $state['databaseError'] = 'No s’ha pogut desar la informació. Revisa la connexió i les dades relacionades.';
        } catch (Throwable $error) {
            error_log('Create operation failed: ' . $error->getMessage());
            $state['databaseError'] = 'No s’ha pogut desar la informació. Torna-ho a provar.';
        }
    }

    /** Validate and insert a project, assigning its creator when that creator is a teacher. */
    private function createProject(PDO $connection, array &$state): void
    {
        $code = $this->postString('code');
        $name = $this->postString('name');
        $description = $this->postString('description');
        $state['submitted'] = [
            'code' => $code,
            'name' => $name,
            'description' => $description,
        ];

        $this->validateCodeNameDescription($code, $name, $description, $state);
        if ($state['validationErrors'] !== []) {
            return;
        }

        $projects = new ProjectModel($connection);
        if ($projects->codeExists($code)) {
            $state['validationErrors'][] = 'Ja existeix un projecte amb aquest codi.';
            return;
        }

        $sessionUser = RoleAuthorization::currentUser();
        $creatorTeacherId = $sessionUser['role'] === 'teacher' ? $sessionUser['id'] : null;
        $projects->create($code, $name, $description, $creatorTeacherId);
        $this->redirectAfterCreate('projects', $this->currentPreviewRole(), 'project-created');
    }

    /** Validate and insert an evaluable item. */
    private function createItem(PDO $connection, array &$state): void
    {
        $code = $this->postString('code');
        $name = $this->postString('name');
        $description = $this->postString('description');
        $state['submitted'] = [
            'code' => $code,
            'name' => $name,
            'description' => $description,
        ];

        $this->validateCodeNameDescription($code, $name, $description, $state);
        if ($state['validationErrors'] !== []) {
            return;
        }

        $items = new EvaluableItemModel($connection);
        if ($items->codeExists($code)) {
            $state['validationErrors'][] = 'Ja existeix un ítem avaluable amb aquest codi.';
            return;
        }

        $items->create($code, $name, $description);
        $this->redirectAfterCreate('evaluable-items', 'admin', 'item-created');
    }

    /** Validate every selected ID and insert all relationship rows in one transaction. */
    private function createAssignments(PDO $connection, string $type, array &$state): void
    {
        $projectId = $this->positiveId($this->postString('project_id'));
        $rawIds = $_POST['record_ids'] ?? [];
        $recordIds = [];

        if (!is_array($rawIds)) {
            $rawIds = [];
        }

        foreach ($rawIds as $rawId) {
            $recordIds[] = is_string($rawId) ? $this->positiveId($rawId) : null;
        }

        $state['submitted'] = ['project_id' => $this->postString('project_id')];

        if ($projectId === null || !(new ProjectModel($connection))->exists($projectId)) {
            $state['validationErrors'][] = 'Selecciona un projecte existent.';
        }

        if ($recordIds === [] || in_array(null, $recordIds, true)) {
            $state['validationErrors'][] = 'Selecciona almenys un element vàlid per assignar.';
            return;
        }

        $recordIds = array_values(array_unique($recordIds));
        $users = new UserModel($connection);
        $items = new EvaluableItemModel($connection);

        foreach ($recordIds as $recordId) {
            // Confirm that each selected ID exists and has the type chosen by the form.
            if ($type === 'teachers') {
                $isValid = $users->hasRoleForId($recordId, 'teacher');
            } elseif ($type === 'students') {
                $isValid = $users->hasRoleForId($recordId, 'student');
            } elseif ($type === 'items') {
                $isValid = $items->exists($recordId);
            } else {
                $isValid = false;
            }

            if (!$isValid) {
                $state['validationErrors'][] = 'Una de les opcions seleccionades no existeix o no correspon al tipus indicat.';
                return;
            }
        }

        if ($state['validationErrors'] !== []) {
            return;
        }

        (new ProjectAssignmentModel($connection))->createMany($type, $projectId, $recordIds);
        $this->redirectAfterCreate('projects', 'admin', 'assignments-created');
    }

    /** Insert a grade for the authenticated teacher without overwriting another row. */
    private function createGrade(PDO $connection, array &$state): void
    {
        $projectId = $this->positiveId($this->postString('project_id'));
        $studentId = $this->positiveId($this->postString('student_id'));
        $itemId = $this->positiveId($this->postString('item_id'));
        $gradeValue = str_replace(',', '.', $this->postString('grade_value'));
        $state['submitted'] = [
            'project_id' => $this->postString('project_id'),
            'student_id' => $this->postString('student_id'),
            'item_id' => $this->postString('item_id'),
            'grade_value' => $this->postString('grade_value'),
        ];

        if ($projectId === null || $studentId === null || $itemId === null) {
            $state['validationErrors'][] = 'Selecciona un projecte, un alumne i un ítem avaluable.';
        }

        // DECIMAL(10,4) allows up to six integer digits and four decimal digits.
        // The requirements do not yet define a grading range or rounding rule.
        if (!$this->isValidGradeFormat($gradeValue)) {
            $state['validationErrors'][] = 'Introdueix una nota numèrica amb un màxim de sis xifres enteres i quatre decimals.';
        }

        if ($state['validationErrors'] !== []) {
            return;
        }

        $sessionUser = RoleAuthorization::currentUser();
        $gradeModel = new GradeModel($connection);
        if (!$gradeModel->teacherHasProject($sessionUser['id'], $projectId)) {
            $state['validationErrors'][] = 'El projecte no està assignat al teu compte de professorat.';
            return;
        }

        if (!$gradeModel->studentHasProject($studentId, $projectId)
            || !$gradeModel->itemHasProject($itemId, $projectId)) {
            $state['validationErrors'][] = 'L’alumne i l’ítem han d’estar assignats al projecte seleccionat.';
            return;
        }

        $gradeModel->create($projectId, $studentId, $itemId, $sessionUser['id'], $gradeValue);
        $this->redirectAfterCreate('grades', 'teacher', 'grade-created');
    }

    /** Check the shared required fields and column limits used by projects and items. */
    private function validateCodeNameDescription(string $code, string $name, string $description, array &$state): void
    {
        if ($code === '') {
            $state['validationErrors'][] = 'Introdueix el codi.';
        } elseif ($this->characterCount($code) > 50) {
            $state['validationErrors'][] = 'El codi no pot superar els 50 caràcters.';
        }

        if ($name === '') {
            $state['validationErrors'][] = 'Introdueix el nom.';
        } elseif ($this->characterCount($name) > 150) {
            $state['validationErrors'][] = 'El nom no pot superar els 150 caràcters.';
        }

        if ($description === '') {
            $state['validationErrors'][] = 'Introdueix la descripció.';
        } elseif (strlen($description) > 65535) {
            $state['validationErrors'][] = 'La descripció supera la mida permesa per la base de dades.';
        }
    }

    /** Return the server-side role allowed to submit this action, or an empty string. */
    private function requiredRoleForAction(string $action, string $section, string $previewRole): string
    {
        if ($action === 'create_project' && $section === 'projects') {
            if ($previewRole === 'admin') {
                return 'administrator';
            }

            if ($previewRole === 'teacher') {
                return 'teacher';
            }
        }

        if ($action === 'create_item' && $section === 'evaluable-items' && $previewRole === 'admin') {
            return 'administrator';
        }

        if (in_array($action, ['assign_teachers', 'assign_students', 'assign_items'], true)
            && $section === 'projects' && $previewRole === 'admin') {
            return 'administrator';
        }

        if ($action === 'create_grade' && $section === 'grades' && $previewRole === 'teacher') {
            return 'teacher';
        }

        // Unknown actions and page combinations are not allowed to make a database change.
        return '';
    }

    /** Return the role needed to view a protected form page, or empty for a public page. */
    private function requiredRoleForPage(string $section, string $previewRole): string
    {
        if (($section === 'projects' || $section === 'evaluable-items') && $previewRole === 'admin') {
            return 'administrator';
        }

        if (($section === 'projects' || $section === 'grades') && $previewRole === 'teacher') {
            return 'teacher';
        }

        return '';
    }

    /** Read one safe string value from POST; crafted arrays are rejected as empty strings. */
    private function postString(string $name): string
    {
        $value = $_POST[$name] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    /** Accept positive integer form IDs only. */
    private function positiveId(string $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return is_int($id) ? $id : null;
    }

    /** Count Unicode characters so Catalan accents count as one character. */
    private function characterCount(string $value): int
    {
        $count = preg_match_all('/./us', $value);

        return $count === false ? PHP_INT_MAX : $count;
    }

    /** Match the DECIMAL(10,4) storage shape without inventing a grade scale. */
    private function isValidGradeFormat(string $value): bool
    {
        return preg_match('/^-?\d{1,6}(?:\.\d{1,4})?$/', $value) === 1;
    }

    /** Read a requested project only when it is one of the authenticated teacher's projects. */
    private function selectedProjectId(array $projects): string
    {
        $postedProjectId = $this->postString('project_id');
        $requestedProjectId = $_GET['project_id'] ?? $postedProjectId;
        $requestedProjectId = is_string($requestedProjectId) ? $requestedProjectId : '';

        foreach ($projects as $project) {
            if ((string) $project['id'] === $requestedProjectId) {
                return $requestedProjectId;
            }
        }

        return isset($projects[0]['id']) ? (string) $projects[0]['id'] : '';
    }

    /** Redirect successful creates so reloading the result cannot repeat the POST. */
    private function redirectAfterCreate(string $section, string $role, string $status): void
    {
        $url = '/?page=' . rawurlencode($section)
            . '&role=' . rawurlencode($role)
            . '&status=' . rawurlencode($status);
        header('Location: ' . $url, true, 303);
        exit;
    }

    /** Only read the preview role to build a return link; never use it as authorization. */
    private function currentPreviewRole(): string
    {
        $role = $_GET['role'] ?? 'admin';

        return is_string($role) && in_array($role, ['admin', 'teacher'], true) ? $role : 'admin';
    }

    /** Convert safe status codes into visible Catalan success messages. */
    private function feedbackFromUrl(): string
    {
        $status = $_GET['status'] ?? '';

        if (!is_string($status)) {
            return '';
        }

        if ($status === 'project-created') {
            return 'S’ha creat el projecte correctament.';
        }

        if ($status === 'item-created') {
            return 'S’ha creat l’ítem avaluable correctament.';
        }

        if ($status === 'assignments-created') {
            return 'S’han creat les assignacions correctament.';
        }

        if ($status === 'grade-created') {
            return 'S’ha creat la qualificació correctament.';
        }

        return '';
    }

    /** Give common unique-key failures a clear message without exposing database details. */
    private function duplicateMessage(string $action): string
    {
        if ($action === 'create_project') {
            return 'Ja existeix un projecte amb aquest codi.';
        }

        if ($action === 'create_item') {
            return 'Ja existeix un ítem avaluable amb aquest codi.';
        }

        if (in_array($action, ['assign_teachers', 'assign_students', 'assign_items'], true)) {
            return 'Una o més assignacions seleccionades ja existeixen. No s’ha afegit cap assignació d’aquest enviament.';
        }

        if ($action === 'create_grade') {
            return 'Ja has introduït una nota per a aquest projecte, alumne i ítem.';
        }

        return 'Una de les dades ja existeix o no compleix les relacions de la base de dades.';
    }
}
