<?php

declare(strict_types=1);

/**
 * Reads the trusted account identity from PHP's server-side session.
 * URL values, form fields, and JavaScript data are never treated as a logged-in role.
 */
class RoleAuthorization
{
    /**
     * Return a valid session user, or null when no verified login is active.
     * LoginController stores ['id' => int, 'role' => string] from the users table.
     */
    public static function currentUser(): ?array
    {
        $sessionUser = $_SESSION['user'] ?? null;

        if (!is_array($sessionUser)) {
            return null;
        }

        $userId = filter_var(
            $sessionUser['id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );
        $role = $sessionUser['role'] ?? null;

        if (!is_int($userId) || !is_string($role)) {
            return null;
        }

        if (!in_array($role, ['administrator', 'teacher', 'student'], true)) {
            return null;
        }

        return ['id' => $userId, 'role' => $role];
    }

    /** Check whether the authenticated server-side session has the required role. */
    public static function hasRole(string $requiredRole): bool
    {
        $user = self::currentUser();

        return $user !== null && $user['role'] === $requiredRole;
    }
}
