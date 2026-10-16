<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/RoleAuthorization.php';
require_once dirname(__DIR__) . '/Models/UserModel.php';

/**
 * Handles create, edit, and delete requests for teacher and student accounts.
 *
 * This controller is the link between the browser forms, the UserModel SQL methods,
 * and the PHP view. The browser submits ordinary POST requests; PHP validates them
 * and then reloads the page with fresh rows from MariaDB.
 */
class AdminUsersController
{
    public function handle(): array
    {
        // Collect everything the view needs in one array so it can render the page
        // without querying the database or interpreting raw request parameters.
        $state = [
            'users' => [],
            'feedback' => $this->feedbackFromUrl(),
            'databaseError' => '',
            'accessError' => '',
            'validationErrors' => [],
            'formValues' => [
                'id' => '',
                'full_name' => '',
                'username' => '',
                'role' => $this->requestedFilter(),
            ],
            'formAction' => 'create',
            'reopenModal' => false,
            'activeFilter' => $this->requestedFilter(),
        ];

        if (!RoleAuthorization::hasRole('administrator')) {
            $state['accessError'] = 'Cal una sessió d’administrador autenticada al servidor. El login actual només permet previsualitzar les pantalles.';
        }

        // Reject an unauthenticated write before opening PDO. This makes the server-side
        // role check the first decision for POST requests, even when MariaDB is unavailable.
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !RoleAuthorization::hasRole('administrator')) {
            http_response_code(403);
            $state['accessError'] = 'No s’ha pogut verificar el teu rol al servidor. Cal implementar l’autenticació abans de modificar usuaris.';

            // The write is rejected, but keep the existing read-only user list visible when
            // MariaDB is available so the denied form response is not mistaken for no users.
            try {
                $state['users'] = (new UserModel(Database::getConnection()))->getManagedUsers();
            } catch (Throwable $error) {
                error_log('Administrator user list read failed: ' . $error->getMessage());
                $state['databaseError'] = 'No s’ha pogut carregar la llista d’usuaris.';
            }

            return $state;
        }

        try {
            // Reuse the application's shared PDO factory and model for every request.
            $users = new UserModel(Database::getConnection());

            // GET displays the page. POST is used by the create, edit, and delete forms.
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                // At this point the server-side administrator session has already passed its check.
                $this->handlePost($users, $state);
            }

            // Read the latest database rows after handling a request, so the next page
            // response shows the change that was just made.
            $state['users'] = $users->getManagedUsers();
        } catch (Throwable $error) {
            // Keep technical details in the server log for the developer. The page only
            // receives a general Catalan message, never the SQL, password, or stack trace.
            error_log('Administrator user management failed: ' . $error->getMessage());
            $state['databaseError'] = 'No s’ha pogut completar l’acció. Revisa la connexió i torna-ho a provar.';
        }

        return $state;
    }

    private function handlePost(UserModel $users, array &$state): void
    {
        // The hidden form field says which operation the user requested.
        // Reject unknown values instead of guessing what the request should do.
        $action = $this->postString('action');

        if ($action === 'delete') {
            $this->deleteUser($users, $state);
            return;
        }

        if ($action !== 'create' && $action !== 'update') {
            $state['databaseError'] = 'No s’ha reconegut l’acció sol·licitada.';
            return;
        }

        $this->saveUser($users, $action, $state);
    }

    private function saveUser(UserModel $users, string $action, array &$state): void
    {
        // Read form values as strings and trim ordinary text fields before validation.
        // Passwords are read without trimming so their spaces remain part of the password.
        $idValue = $this->postString('id');
        $fullName = $this->postString('full_name');
        $username = $this->postString('username');
        $role = $this->postString('role');
        $password = $this->postString('password', false);
        $userId = $action === 'update' ? $this->positiveId($idValue) : null;
        $existingUser = $userId === null ? null : $users->findManagedUser($userId);

        // Keep submitted non-secret values and the form mode so PHP can reopen the modal
        // and show errors beside the values the user already entered.
        $state['formAction'] = $action;
        $state['reopenModal'] = true;
        $state['activeFilter'] = in_array($role, ['teacher', 'student'], true)
            ? $role
            : $state['activeFilter'];
        $state['formValues'] = [
            'id' => $idValue,
            'full_name' => $fullName,
            'username' => $username,
            'role' => $role,
        ];

        // These checks mirror the required fields and maximum lengths in the database schema.
        if ($fullName === '') {
            $state['validationErrors'][] = 'Introdueix el nom complet.';
        } elseif ($this->characterCount($fullName) > 150) {
            $state['validationErrors'][] = 'El nom complet no pot superar els 150 caràcters.';
        }

        if ($username === '') {
            $state['validationErrors'][] = 'Introdueix un nom d’usuari.';
        } elseif ($this->characterCount($username) > 80) {
            $state['validationErrors'][] = 'El nom d’usuari no pot superar els 80 caràcters.';
        }

        if (!in_array($role, ['teacher', 'student'], true)) {
            $state['validationErrors'][] = 'Selecciona professorat o alumnat.';
        }

        // A new account needs a password. During editing it is optional, meaning "keep current".
        if ($action === 'create' && trim($password) === '') {
            $state['validationErrors'][] = 'Introdueix una contrasenya per al compte nou.';
        }

        if ($action === 'update' && $userId === null) {
            $state['validationErrors'][] = 'No s’ha pogut identificar l’usuari que vols editar.';
        } elseif ($action === 'update' && $existingUser === null) {
            $state['validationErrors'][] = 'L’usuari seleccionat no existeix o no es pot editar.';
        }

        // Stop before any SQL change when one or more submitted fields are invalid.
        if ($state['validationErrors'] !== []) {
            return;
        }

        // Check duplicate usernames before saving to give a readable form error.
        // The database unique key also protects against two simultaneous requests.
        if ($users->usernameExists($username, $userId)) {
            $state['validationErrors'][] = 'Ja existeix un compte amb aquest nom d’usuari.';
            return;
        }

        // Keep assignment foreign keys valid: an assigned teacher/student cannot change role.
        if (
            $existingUser !== null
            && $existingUser['role'] !== $role
            && $users->hasProjectAssignments($userId)
        ) {
            $state['validationErrors'][] = 'No es pot canviar el rol d’un usuari assignat a projectes.';
            return;
        }

        try {
            if ($action === 'create') {
                // Create and then redirect. The redirect prevents a browser refresh
                // from submitting the same form a second time (POST/Redirect/GET).
                $users->create($username, $fullName, $password, $role);
                $this->redirectAfterSave('created', $role);
            }

            // A blank edit password becomes null, which tells the model to keep the old hash.
            $users->update(
                $userId,
                $username,
                $fullName,
                $role,
                $password === '' ? null : $password,
            );
            $this->redirectAfterSave('updated', $role);
        } catch (PDOException $error) {
            // The username may be taken by another request after our earlier duplicate check.
            // Convert the database's unique-key error into the same understandable form message.
            if ((string) $error->getCode() === '23000') {
                $state['validationErrors'][] = 'Ja existeix un compte amb aquest nom d’usuari.';
                return;
            }

            throw $error;
        }
    }

    private function deleteUser(UserModel $users, array &$state): void
    {
        // Never trust a hidden input alone. Parse a positive integer and confirm
        // the account is a teacher/student before issuing the DELETE query.
        $userId = $this->positiveId($this->postString('id'));

        if ($userId === null) {
            $state['databaseError'] = 'No s’ha pogut identificar l’usuari que vols eliminar.';
            return;
        }

        $user = $users->findManagedUser($userId);

        if ($user === null || !$users->delete($userId)) {
            $state['databaseError'] = 'L’usuari seleccionat no existeix o ja s’ha eliminat.';
            return;
        }

        // A successful delete also uses the redirect so reloading does not repeat it.
        $this->redirectAfterSave('deleted', $user['role']);
    }

    private function redirectAfterSave(string $status, string $role): void
    {
        // A 303 tells the browser to load the result as GET after the POST has completed.
        // The URL carries only a short status code and selected role, never account secrets.
        $location = '/?page=users&role=admin&status=' . rawurlencode($status)
            . '&filter=' . rawurlencode($role);
        header('Location: ' . $location, true, 303);
        exit;
    }

    private function postString(string $name, bool $trim = true): string
    {
        // Forms normally send strings, but a crafted request could send an array.
        // Return an empty string for unexpected input so validation can reject it safely.
        $value = $_POST[$name] ?? '';

        if (!is_string($value)) {
            return '';
        }

        return $trim ? trim($value) : $value;
    }

    private function positiveId(string $value): ?int
    {
        // IDs from hidden form fields are still user input; accept only positive integers.
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return is_int($id) ? $id : null;
    }

    private function characterCount(string $value): int
    {
        // Count Unicode characters rather than bytes, so names with accents are measured correctly.
        $count = preg_match_all('/./us', $value);

        return $count === false ? PHP_INT_MAX : $count;
    }

    private function requestedFilter(): string
    {
        // The URL filter controls which role tab is active; allow only known values.
        $filter = $_GET['filter'] ?? 'teacher';

        return is_string($filter) && in_array($filter, ['teacher', 'student'], true)
            ? $filter
            : 'teacher';
    }

    private function feedbackFromUrl(): string
    {
        // After a successful POST, translate its short status code into Catalan page feedback.
        $status = $_GET['status'] ?? '';

        if (!is_string($status)) {
            return '';
        }

        if ($status === 'created') {
            return 'S’ha creat l’usuari correctament.';
        }

        if ($status === 'updated') {
            return 'S’han desat els canvis de l’usuari.';
        }

        if ($status === 'deleted') {
            return 'S’ha eliminat l’usuari i les assignacions i qualificacions vinculades.';
        }

        return '';
    }
}
