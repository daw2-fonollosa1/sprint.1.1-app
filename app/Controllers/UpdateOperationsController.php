<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/RoleAuthorization.php';
require_once dirname(__DIR__) . '/Models/EvaluableItemModel.php';
require_once dirname(__DIR__) . '/Models/GradeModel.php';
require_once dirname(__DIR__) . '/Models/ProjectAssignmentModel.php';
require_once dirname(__DIR__) . '/Models/ProjectModel.php';
require_once dirname(__DIR__) . '/Models/UserModel.php';

/** Validates edit forms and asks the existing PDO models to save their changes. */
class UpdateOperationsController
{
    /** Process an update request or return feedback for the current page. */
    public function handle(string $section, string $roleKey): array
    {
        $state = [
            'feedback' => $this->feedbackFromUrl(),
            'accessError' => '',
            'databaseError' => '',
            'validationErrors' => [],
            'submitted' => [],
            'editId' => '',
            'editType' => '',
        ];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return $state;
        }

        $action = $this->postString('action');

        // Create and user-management forms have their own controllers. This controller
        // handles only the update actions listed below.
        if (!str_starts_with($action, 'update_')) {
            return $state;
        }

        $requiredRole = $this->requiredRoleForAction($action, $section, $roleKey);
        if ($requiredRole === '') {
            http_response_code(400);
            $state['validationErrors'][] = 'No s’ha reconegut l’operació d’edició sol·licitada.';
            return $state;
        }

        // A hidden role or record ID is not permission. The session must already
        // contain a verified account with the role required for this operation.
        if (!RoleAuthorization::hasRole($requiredRole)) {
            http_response_code(403);
            $state['accessError'] = 'El compte autenticat no té permisos per editar aquestes dades.';
            return $state;
        }

        try {
            $connection = Database::getConnection();

            switch ($action) {
                case 'update_project':
                    $this->updateProject($connection, $roleKey, $state);
                    break;
                case 'update_item':
                    $this->updateItem($connection, $state);
                    break;
                case 'update_assignments':
                    $this->updateAssignments($connection, $state);
                    break;
                case 'update_grade':
                    $this->updateGrade($connection, $state);
                    break;
            }
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                $state['validationErrors'][] = $this->duplicateMessage($action);
                return $state;
            }

            error_log('Update operation database request failed: ' . $error->getMessage());
            $state['databaseError'] = 'No s’han pogut desar els canvis. Revisa les dades i torna-ho a provar.';
        } catch (Throwable $error) {
            error_log('Update operation failed: ' . $error->getMessage());
            $state['databaseError'] = 'No s’han pogut desar els canvis. Torna-ho a provar.';
        }

        return $state;
    }

    /** Validate and update project details without changing assignment relationships. */
    private function updateProject(PDO $connection, string $roleKey, array &$state): void
    {
        $id = $this->positiveId($this->postString('id'));
        $code = $this->postString('code');
        $name = $this->postString('name');
        $description = $this->postString('description');
        $state['editId'] = $id === null ? '' : (string) $id;
        $state['editType'] = 'project';
        $state['submitted'] = [
            'id' => $this->postString('id'),
            'code' => $code,
            'name' => $name,
            'description' => $description,
        ];

        $this->validateCodeNameDescription($code, $name, $description, $state);
        $projects = new ProjectModel($connection);

        if ($id === null || !$projects->exists($id)) {
            $state['validationErrors'][] = 'El projecte que vols editar no existeix.';
        }

        if ($state['validationErrors'] !== []) {
            return;
        }

        $sessionUser = RoleAuthorization::currentUser();
        if ($roleKey === 'teacher' && !$projects->isAssignedToTeacher($id, $sessionUser['id'])) {
            http_response_code(403);
            $state['accessError'] = 'Només pots editar projectes assignats al teu compte de professorat.';
            return;
        }

        if ($projects->codeExists($code, $id)) {
            $state['validationErrors'][] = 'Ja existeix un projecte amb aquest codi.';
            return;
        }

        $projects->update($id, $code, $name, $description);
        $this->redirectAfterUpdate('projects', $roleKey, 'project-updated');
    }

    /** Validate and update an evaluable item's code, name, and description. */
    private function updateItem(PDO $connection, array &$state): void
    {
        $id = $this->positiveId($this->postString('id'));
        $code = $this->postString('code');
        $name = $this->postString('name');
        $description = $this->postString('description');
        $state['editId'] = $id === null ? '' : (string) $id;
        $state['editType'] = 'item';
        $state['submitted'] = [
            'id' => $this->postString('id'),
            'code' => $code,
            'name' => $name,
            'description' => $description,
        ];

        $this->validateCodeNameDescription($code, $name, $description, $state);
        $items = new EvaluableItemModel($connection);

        if ($id === null || !$items->exists($id)) {
            $state['validationErrors'][] = 'L’ítem avaluable que vols editar no existeix.';
        }

        if ($state['validationErrors'] !== []) {
            return;
        }

        if ($items->codeExists($code, $id)) {
            $state['validationErrors'][] = 'Ja existeix un ítem avaluable amb aquest codi.';
            return;
        }

        $items->update($id, $code, $name, $description);
        $this->redirectAfterUpdate('evaluable-items', 'admin', 'item-updated');
    }

    /** Validate every selected relationship before the model changes assignments. */
    private function updateAssignments(PDO $connection, array &$state): void
    {
        $projectId = $this->positiveId($this->postString('project_id'));
        $state['editId'] = $projectId === null ? '' : (string) $projectId;
        $state['editType'] = 'assignments';
        $state['submitted'] = [
            'project_id' => $this->postString('project_id'),
            'assignments' => [],
        ];

        if ($projectId === null || !(new ProjectModel($connection))->exists($projectId)) {
            $state['validationErrors'][] = 'Selecciona un projecte existent per editar-ne les assignacions.';
        }

        $selectedIds = [
            'teachers' => $this->readIdList('teacher_ids', $state),
            'students' => $this->readIdList('student_ids', $state),
            'items' => $this->readIdList('item_ids', $state),
        ];
        $state['submitted']['assignments'] = $selectedIds;

        if ($state['validationErrors'] !== []) {
            return;
        }

        $users = new UserModel($connection);
        $items = new EvaluableItemModel($connection);

        foreach ($selectedIds['teachers'] as $teacherId) {
            if (!$users->hasRoleForId($teacherId, 'teacher')) {
                $state['validationErrors'][] = 'Una de les persones seleccionades no és professorat vàlid.';
                return;
            }
        }

        foreach ($selectedIds['students'] as $studentId) {
            if (!$users->hasRoleForId($studentId, 'student')) {
                $state['validationErrors'][] = 'Una de les persones seleccionades no és alumnat vàlid.';
                return;
            }
        }

        foreach ($selectedIds['items'] as $itemId) {
            if (!$items->exists($itemId)) {
                $state['validationErrors'][] = 'Un dels ítems seleccionats ja no existeix.';
                return;
            }
        }

        $blockedType = (new ProjectAssignmentModel($connection))
            ->replaceForProject($projectId, $selectedIds);

        if ($blockedType !== null) {
            $state['validationErrors'][] = $this->assignmentGradeMessage($blockedType);
            return;
        }

        $this->redirectAfterUpdate('projects', 'admin', 'assignments-updated');
    }

    /** Update only the authenticated teacher's grade_value column. */
    private function updateGrade(PDO $connection, array &$state): void
    {
        $gradeId = $this->positiveId($this->postString('id'));
        $gradeValue = str_replace(',', '.', $this->postString('grade_value'));
        $state['editId'] = $gradeId === null ? '' : (string) $gradeId;
        $state['editType'] = 'grade';
        $state['submitted'] = [
            'id' => $this->postString('id'),
            'grade_value' => $this->postString('grade_value'),
        ];

        // Match the existing create validation and DECIMAL(10,4) column. The requirements
        // do not define a grade scale, so this edit does not invent one.
        if (preg_match('/^-?\d{1,6}(?:\.\d{1,4})?$/', $gradeValue) !== 1) {
            $state['validationErrors'][] = 'Introdueix una nota amb un màxim de sis xifres enteres i quatre decimals.';
        }

        if ($gradeId === null) {
            $state['validationErrors'][] = 'No s’ha pogut identificar la qualificació que vols editar.';
        }

        if ($state['validationErrors'] !== []) {
            return;
        }

        $sessionUser = RoleAuthorization::currentUser();
        $grades = new GradeModel($connection);
        $grade = $grades->findForTeacher($gradeId, $sessionUser['id']);

        if ($grade === null || !$grades->teacherHasProject($sessionUser['id'], (int) $grade['project_id'])) {
            http_response_code(403);
            $state['accessError'] = 'Només pots editar qualificacions introduïdes pel teu compte de professorat.';
            return;
        }

        $grades->updateOwnGrade($gradeId, $sessionUser['id'], $gradeValue);
        $this->redirectAfterUpdate(
            'grades',
            'teacher',
            'grade-updated',
            (int) $grade['project_id'],
        );
    }

    /** Check required values and their database column limits for projects and items. */
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

    /** Convert checkbox input into unique positive IDs and report malformed form data. */
    private function readIdList(string $fieldName, array &$state): array
    {
        $rawIds = $_POST[$fieldName] ?? [];
        if (!is_array($rawIds)) {
            $state['validationErrors'][] = 'Les assignacions seleccionades no tenen un format vàlid.';
            return [];
        }

        $ids = [];
        foreach ($rawIds as $rawId) {
            $id = is_string($rawId) ? $this->positiveId($rawId) : null;
            if ($id === null) {
                $state['validationErrors'][] = 'Una de les assignacions seleccionades no és vàlida.';
                continue;
            }

            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }

    /** Accept positive integer IDs only; IDs in hidden form fields remain untrusted input. */
    private function positiveId(string $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return is_int($id) ? $id : null;
    }

    /** Count Unicode characters so accented text matches MariaDB's character limits. */
    private function characterCount(string $value): int
    {
        $count = preg_match_all('/./us', $value);

        return $count === false ? PHP_INT_MAX : $count;
    }

    /** Return the server-side database role that may submit the selected edit form. */
    private function requiredRoleForAction(string $action, string $section, string $roleKey): string
    {
        if ($action === 'update_project' && $section === 'projects') {
            if ($roleKey === 'admin') {
                return 'administrator';
            }

            if ($roleKey === 'teacher') {
                return 'teacher';
            }
        }

        if ($action === 'update_item' && $section === 'evaluable-items' && $roleKey === 'admin') {
            return 'administrator';
        }

        if ($action === 'update_assignments' && $section === 'projects' && $roleKey === 'admin') {
            return 'administrator';
        }

        if ($action === 'update_grade' && $section === 'grades' && $roleKey === 'teacher') {
            return 'teacher';
        }

        return '';
    }

    /** Prevent a relationship edit from silently cascading into existing grade rows. */
    private function assignmentGradeMessage(string $type): string
    {
        if ($type === 'teachers') {
            return 'No es pot retirar professorat amb qualificacions guardades en aquest projecte.';
        }

        if ($type === 'students') {
            return 'No es pot retirar alumnat amb qualificacions guardades en aquest projecte.';
        }

        return 'No es pot retirar un ítem avaluable amb qualificacions guardades en aquest projecte.';
    }

    /** Use POST/Redirect/GET so reloading after a successful edit does not repeat it. */
    private function redirectAfterUpdate(string $section, string $role, string $status, ?int $projectId = null): void
    {
        $url = '/?page=' . rawurlencode($section)
            . '&role=' . rawurlencode($role)
            . '&status=' . rawurlencode($status);

        if ($projectId !== null) {
            $url .= '&project_id=' . $projectId;
        }

        header('Location: ' . $url, true, 303);
        exit;
    }

    /** Translate safe status codes into Catalan messages on the next GET request. */
    private function feedbackFromUrl(): string
    {
        $status = $_GET['status'] ?? '';
        if (!is_string($status)) {
            return '';
        }

        $messages = [
            'project-updated' => 'S’han desat els canvis del projecte.',
            'item-updated' => 'S’han desat els canvis de l’ítem avaluable.',
            'assignments-updated' => 'S’han desat les assignacions del projecte.',
            'grade-updated' => 'S’ha actualitzat la qualificació.',
        ];

        return $messages[$status] ?? '';
    }

    /** Convert duplicate-value errors into a useful message without showing SQL details. */
    private function duplicateMessage(string $action): string
    {
        if ($action === 'update_project') {
            return 'Ja existeix un projecte amb aquest codi.';
        }

        if ($action === 'update_item') {
            return 'Ja existeix un ítem avaluable amb aquest codi.';
        }

        return 'No s’han pogut desar aquestes dades perquè entren en conflicte amb un registre existent.';
    }

    /** Read one string field only; array-shaped POST data is rejected as an empty value. */
    private function postString(string $name): string
    {
        $value = $_POST[$name] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
