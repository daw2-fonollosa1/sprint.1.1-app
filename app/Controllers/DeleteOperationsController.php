<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/RoleAuthorization.php';
require_once dirname(__DIR__) . '/Models/EvaluableItemModel.php';
require_once dirname(__DIR__) . '/Models/GradeModel.php';
require_once dirname(__DIR__) . '/Models/ProjectAssignmentModel.php';
require_once dirname(__DIR__) . '/Models/ProjectModel.php';

/** Checks delete requests and asks the existing PDO models to remove one record. */
class DeleteOperationsController
{
    /** Handle one POST request from an existing administrator or teacher page. */
    public function handle(string $section, string $roleKey, int $sessionUserId): array
    {
        $state = [
            'feedback' => $this->feedbackFromUrl(),
            'accessError' => '',
            'databaseError' => '',
            'validationErrors' => [],
        ];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return $state;
        }

        $action = $this->postString('action');
        if (!str_starts_with($action, 'delete_')) {
            return $state;
        }

        $requiredRole = $this->requiredRoleForAction($action, $section, $roleKey);
        if ($requiredRole === '') {
            http_response_code(400);
            $state['validationErrors'][] = 'No s’ha reconegut l’operació d’eliminació sol·licitada.';
            return $state;
        }

        // The session role is checked on the server. Form fields never grant access.
        if (!RoleAuthorization::hasRole($requiredRole)) {
            http_response_code(403);
            $state['accessError'] = 'El compte autenticat no té permisos per eliminar aquestes dades.';
            return $state;
        }

        try {
            $connection = Database::getConnection();

            switch ($action) {
                case 'delete_project':
                    $this->deleteProject($connection, $state);
                    break;
                case 'delete_item':
                    $this->deleteItem($connection, $state);
                    break;
                case 'delete_assignment':
                    $this->deleteAssignment($connection, $state);
                    break;
                case 'delete_grade':
                    $this->deleteGrade($connection, $roleKey, $sessionUserId, $state);
                    break;
            }
        } catch (PDOException $error) {
            // A foreign-key conflict can happen if related data changes at the same time.
            if ((string) $error->getCode() === '23000') {
                $state['validationErrors'][] = 'No es pot eliminar el registre perquè encara té dades vinculades.';
                return $state;
            }

            error_log('Delete operation database request failed: ' . $error->getMessage());
            $state['databaseError'] = 'No s’ha pogut completar l’eliminació. Revisa la connexió i torna-ho a provar.';
        } catch (Throwable $error) {
            error_log('Delete operation failed: ' . $error->getMessage());
            $state['databaseError'] = 'No s’ha pogut completar l’eliminació. Torna-ho a provar.';
        }

        return $state;
    }

    /** Remove a project only when no assignments or grades depend on it. */
    private function deleteProject(PDO $connection, array &$state): void
    {
        $id = $this->positiveId($this->postString('id'));
        if ($id === null) {
            $state['validationErrors'][] = 'No s’ha pogut identificar el projecte que vols eliminar.';
            return;
        }

        $result = (new ProjectModel($connection))->deleteIfUnused($id);
        if ($result === 'missing') {
            $state['validationErrors'][] = 'El projecte seleccionat no existeix o ja s’ha eliminat.';
            return;
        }
        if ($result === 'has_related_data') {
            $state['validationErrors'][] = 'No es pot eliminar un projecte que encara té professorat, alumnat, ítems o qualificacions associats.';
            return;
        }

        $this->redirectAfterDelete('projects', 'admin', 'project-deleted');
    }

    /** Remove an evaluable item only when it is not assigned to a project. */
    private function deleteItem(PDO $connection, array &$state): void
    {
        $id = $this->positiveId($this->postString('id'));
        if ($id === null) {
            $state['validationErrors'][] = 'No s’ha pogut identificar l’ítem avaluable que vols eliminar.';
            return;
        }

        $result = (new EvaluableItemModel($connection))->deleteIfUnused($id);
        if ($result === 'missing') {
            $state['validationErrors'][] = 'L’ítem avaluable seleccionat no existeix o ja s’ha eliminat.';
            return;
        }
        if ($result === 'has_related_data') {
            $state['validationErrors'][] = 'No es pot eliminar un ítem avaluable assignat a un projecte o utilitzat en una qualificació.';
            return;
        }

        $this->redirectAfterDelete('evaluable-items', 'admin', 'item-deleted');
    }

    /** Remove one assignment link, but keep it when a grade depends on that link. */
    private function deleteAssignment(PDO $connection, array &$state): void
    {
        $projectId = $this->positiveId($this->postString('project_id'));
        $recordId = $this->positiveId($this->postString('record_id'));
        $type = $this->postString('assignment_type');

        if ($projectId === null || $recordId === null || !in_array($type, ['teachers', 'students', 'items'], true)) {
            $state['validationErrors'][] = 'Les dades de l’assignació que vols eliminar no són vàlides.';
            return;
        }

        $result = (new ProjectAssignmentModel($connection))->deleteOne($type, $projectId, $recordId);
        if ($result === 'missing') {
            $state['validationErrors'][] = 'L’assignació seleccionada no existeix o ja s’ha eliminat.';
            return;
        }
        if ($result === 'has_grades') {
            $state['validationErrors'][] = 'No es pot eliminar aquesta assignació perquè hi ha qualificacions vinculades.';
            return;
        }
        if ($result !== 'deleted') {
            $state['validationErrors'][] = 'El tipus d’assignació seleccionat no és vàlid.';
            return;
        }

        $this->redirectAfterDelete('projects', 'admin', 'assignment-deleted');
    }

    /** Delete one grade row; teachers can delete only their own rows. */
    private function deleteGrade(PDO $connection, string $roleKey, int $sessionUserId, array &$state): void
    {
        $gradeId = $this->positiveId($this->postString('id'));
        if ($gradeId === null) {
            $state['validationErrors'][] = 'No s’ha pogut identificar la qualificació que vols eliminar.';
            return;
        }

        $grades = new GradeModel($connection);
        $grade = $roleKey === 'admin'
            ? $grades->findById($gradeId)
            : $grades->findForTeacher($gradeId, $sessionUserId);

        if ($grade === null) {
            $state['validationErrors'][] = 'La qualificació no existeix o no tens permís per eliminar-la.';
            return;
        }

        if ($roleKey === 'teacher' && !$grades->teacherHasProject($sessionUserId, (int) $grade['project_id'])) {
            http_response_code(403);
            $state['accessError'] = 'Només pots eliminar qualificacions dels teus projectes assignats.';
            return;
        }

        $teacherId = $roleKey === 'teacher' ? $sessionUserId : null;
        if (!$grades->deleteOne($gradeId, $teacherId)) {
            $state['validationErrors'][] = 'La qualificació ja no existeix o no s’ha pogut eliminar.';
            return;
        }

        $projectId = $roleKey === 'teacher' ? (int) $grade['project_id'] : null;
        $this->redirectAfterDelete('grades', $roleKey, 'grade-deleted', $projectId);
    }

    /** State which authenticated role may send each delete action from the matching page. */
    private function requiredRoleForAction(string $action, string $section, string $roleKey): string
    {
        if ($action === 'delete_project' && $section === 'projects' && $roleKey === 'admin') {
            return 'administrator';
        }

        if ($action === 'delete_item' && $section === 'evaluable-items' && $roleKey === 'admin') {
            return 'administrator';
        }

        if ($action === 'delete_assignment' && $section === 'projects' && $roleKey === 'admin') {
            return 'administrator';
        }

        if ($action === 'delete_grade' && $section === 'grades') {
            if ($roleKey === 'admin') {
                return 'administrator';
            }
            if ($roleKey === 'teacher') {
                return 'teacher';
            }
        }

        return '';
    }

    /** Accept only positive numeric identifiers from POST fields. */
    private function positiveId(string $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return is_int($id) ? $id : null;
    }

    /** Read one scalar form value; malformed array input becomes an empty string. */
    private function postString(string $name): string
    {
        $value = $_POST[$name] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    /** Send the browser to the fresh PHP list after a successful POST. */
    private function redirectAfterDelete(string $section, string $role, string $status, ?int $projectId = null): void
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

    /** Show only fixed Catalan success messages; status values never become page text. */
    private function feedbackFromUrl(): string
    {
        $status = $_GET['status'] ?? '';
        if (!is_string($status)) {
            return '';
        }

        $messages = [
            'project-deleted' => 'S’ha eliminat el projecte.',
            'item-deleted' => 'S’ha eliminat l’ítem avaluable.',
            'assignment-deleted' => 'S’ha eliminat l’assignació.',
            'grade-deleted' => 'S’ha eliminat la qualificació seleccionada.',
        ];

        return $messages[$status] ?? '';
    }
}
