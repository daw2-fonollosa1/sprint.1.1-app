<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Models/UserModel.php';

/** Checks database credentials and manages the PHP login session. */
class LoginController
{
    // The database role is used to choose the matching PHP dashboard route after login.
    private const ROLE_PAGES = [
        'administrator' => 'admin',
        'teacher' => 'teacher',
        'student' => 'student',
    ];

    public function index(): void
    {
        // These values let the PHP view show clear errors without losing the username.
        $formErrors = [];
        $loginError = '';
        $loginNotice = ($_GET['logged_out'] ?? '') === '1' ? 'Has tancat la sessió correctament.' : '';
        $username = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            // Reject forged or stale form submissions before looking up an account.
            if (!$this->hasValidCsrfToken()) {
                http_response_code(403);
                $loginError = 'La sol·licitud ha caducat. Torna a carregar la pàgina.';
            }

            // Read the named form controls; missing controls become empty strings.
            $postedUsername = $_POST['username'] ?? '';
            $postedPassword = $_POST['password'] ?? '';

            // Only accept text values from the form. Trim the username for lookup,
            // but keep the password as entered (apart from the empty check below).
            $username = is_string($postedUsername) ? trim($postedUsername) : '';
            $password = is_string($postedPassword) ? $postedPassword : '';

            // Check required fields on the server too, so the form still gives feedback
            // when JavaScript is disabled or a request is sent without the browser UI.
            if ($username === '') {
                $formErrors['username'] = 'Introdueix el teu nom d’usuari.';
            }

            if (trim($password) === '') {
                $formErrors['password'] = 'Introdueix la contrasenya.';
            }

            if ($formErrors === [] && $loginError === '') {
                $this->authenticate($username, $password, $loginError);
            }
        }

        // Render the same server-generated page after a first visit or an invalid login.
        $csrfToken = (string) ($_SESSION['csrf_token'] ?? '');
        require dirname(__DIR__) . '/Views/layouts/auth.php';
    }

    /** End the authenticated session after a normal POST request. */
    public function logout(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Mètode no permès.');
        }

        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            exit('La sol·licitud no és vàlida. Torna a carregar la pàgina.');
        }

        // Remove account data from the server-side session before destroying it.
        $_SESSION = [];

        // Expire the browser's session cookie so it cannot refer to the old session.
        if (ini_get('session.use_cookies')) {
            $cookie = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $cookie['path'],
                'domain' => $cookie['domain'],
                'secure' => $cookie['secure'],
                'httponly' => $cookie['httponly'],
                'samesite' => $cookie['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
        header('Location: /?page=login&logged_out=1', true, 303);
        exit;
    }

    /** Compare the submitted password with the hash stored for the database account. */
    private function authenticate(string $username, string $password, string &$loginError): void
    {
        try {
            $users = new UserModel(Database::getConnection());
            $user = $users->findByUsername($username);

            // Use the same message for an unknown username and a wrong password.
            if ($user === null || !password_verify($password, $user['password_hash'])) {
                $loginError = 'L’usuari o la contrasenya no són correctes.';
                return;
            }

            $databaseRole = (string) $user['role'];
            if (!isset(self::ROLE_PAGES[$databaseRole])) {
                $loginError = 'Aquest compte no té un rol vàlid per accedir a l’aplicació.';
                return;
            }

            // Change the session ID after a valid login to prevent session fixation.
            if (!session_regenerate_id(true)) {
                throw new RuntimeException('Could not regenerate the session ID.');
            }

            // Store only the verified database ID and role; never trust the URL for identity.
            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'role' => $databaseRole,
            ];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // The role in this redirect matches the role returned by the database query.
            $rolePage = self::ROLE_PAGES[$databaseRole];
            header('Location: /?page=dashboard&role=' . rawurlencode($rolePage), true, 303);
            exit;
        } catch (Throwable $error) {
            // Keep database details in the server log, not in the browser response.
            error_log('Login database check failed: ' . $error->getMessage());
            $loginError = 'No s’ha pogut iniciar la sessió. Revisa la connexió amb la base de dades i torna-ho a provar.';
        }
    }

    /** Accept a form only when its token matches the value stored in this session. */
    private function hasValidCsrfToken(): bool
    {
        $submittedToken = $_POST['csrf_token'] ?? null;
        $sessionToken = $_SESSION['csrf_token'] ?? null;

        return is_string($submittedToken)
            && is_string($sessionToken)
            && hash_equals($sessionToken, $submittedToken);
    }
}
