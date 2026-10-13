<?php

declare(strict_types=1);

class LoginController
{
    // This lookup connects each demo username with a role key understood by DashboardController.
    // It is only a convenient prototype route: it does not verify a real account or grant secure access.
    private const DEMO_USERS = [
        'admin' => 'admin',
        'teacher' => 'teacher',
        'student' => 'student',
    ];

    public function index(): void
    {
        // These values are passed into the login view so it can show errors or preserve the username.
        $formErrors = [];
        $loginError = '';
        $username = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

            if ($formErrors === []) {
                // Username matching ignores letter case, so ADMIN and admin reach the same demo role.
                $roleKey = self::DEMO_USERS[strtolower($username)] ?? null;

                if ($roleKey === null) {
                    $loginError = 'L’usuari no forma part de les dades de demostració. Prova admin, teacher o student.';
                } else {
                    // Any non-empty password is accepted for the demonstration; it is not saved.
                    // A 303 response tells the browser to follow the redirect with a normal GET request.
                    header('Location: /?page=dashboard&role=' . rawurlencode($roleKey), true, 303);
                    exit;
                }
            }
        }

        // On the first visit or when validation fails, render the login page on the server.
        // The layout includes the login view and makes these variables available to it.
        require dirname(__DIR__) . '/Views/layouts/auth.php';
    }
}
