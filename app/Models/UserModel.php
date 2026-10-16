<?php

declare(strict_types=1);

/**
 * Reads and changes teacher and student accounts in MariaDB.
 *
 * Keeping SQL here gives the controller one clear place to ask for account
 * data, while the controller stays focused on HTTP requests and validation.
 */
class UserModel
{
    // Administrators are intentionally excluded: this screen only manages staff and students.
    private const MANAGED_ROLES = ['teacher', 'student'];

    /** PDO connection shared by this model's SQL methods. */
    private PDO $connection;

    /** Receive the configured connection rather than opening a second one. */
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Return the accounts shown in the administrator's table.
     * The password hash is deliberately omitted because the page never needs to display it.
     */
    public function getManagedUsers(): array
    {
        // Placeholders keep the role values separate from the SQL command.
        $statement = $this->connection->prepare(
            'SELECT id, username, full_name, role
             FROM users
             WHERE role IN (?, ?)
             ORDER BY role, full_name',
        );
        $statement->execute(self::MANAGED_ROLES);

        return $statement->fetchAll();
    }

    /** Return only IDs and names for a role-specific dropdown; passwords are never selected. */
    public function getUsersByRole(string $role): array
    {
        if (!in_array($role, self::MANAGED_ROLES, true)) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT id, username, full_name, role
             FROM users
             WHERE role = :role
             ORDER BY full_name',
        );
        $statement->execute(['role' => $role]);

        return $statement->fetchAll();
    }

    /** Confirm that a submitted ID belongs to the expected account role. */
    public function hasRoleForId(int $id, string $role): bool
    {
        if (!in_array($role, self::MANAGED_ROLES, true)) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT id FROM users WHERE id = :id AND role = :role LIMIT 1',
        );
        $statement->execute(['id' => $id, 'role' => $role]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * Find one account before editing or deleting it.
     * Restricting by role also prevents a crafted request from changing an administrator.
     */
    public function findManagedUser(int $id): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, username, full_name, role
             FROM users
             WHERE id = :id AND role IN (:teacher_role, :student_role)',
        );
        $statement->execute([
            'id' => $id,
            'teacher_role' => 'teacher',
            'student_role' => 'student',
        ]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    /**
     * Check whether a username is already used by any account.
     * During editing, ignore the selected account itself because it keeps its own username.
     * The database unique constraint remains the final protection against duplicate names.
     */
    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        if ($exceptId === null) {
            $statement = $this->connection->prepare(
                'SELECT id FROM users WHERE username = :username LIMIT 1',
            );
            $statement->execute(['username' => $username]);
        } else {
            $statement = $this->connection->prepare(
                'SELECT id FROM users WHERE username = :username AND id <> :id LIMIT 1',
            );
            $statement->execute(['username' => $username, 'id' => $exceptId]);
        }

        return $statement->fetchColumn() !== false;
    }

    /**
     * Check both assignment tables before changing a user's role.
     * A teacher assignment and a student assignment have different foreign-key columns,
     * so an assigned account cannot safely switch between those two kinds of user here.
     */
    public function hasProjectAssignments(int $id): bool
    {
        $statement = $this->connection->prepare(
            'SELECT
                EXISTS (SELECT 1 FROM teacher_projects WHERE teacher_id = :teacher_id)
                OR EXISTS (SELECT 1 FROM student_projects WHERE student_id = :student_id)',
        );
        $statement->execute(['teacher_id' => $id, 'student_id' => $id]);

        return (bool) $statement->fetchColumn();
    }

    /**
     * Insert a new account and save only a password hash.
     * password_hash() creates a one-way value; the plain password is never placed in SQL.
     */
    public function create(string $username, string $fullName, string $password, string $role): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO users (username, full_name, password_hash, role)
             VALUES (:username, :full_name, :password_hash, :role)',
        );
        $statement->execute([
            'username' => $username,
            'full_name' => $fullName,
            'password_hash' => $this->makePasswordHash($password),
            'role' => $role,
        ]);
    }

    /**
     * Update the account fields, optionally replacing its password hash.
     * A null password means the edit form was left blank, so the current hash stays unchanged.
     */
    public function update(int $id, string $username, string $fullName, string $role, ?string $password): void
    {
        $values = [
            'id' => $id,
            'username' => $username,
            'full_name' => $fullName,
            'role' => $role,
        ];

        // Use a separate SQL statement when the password stays the same. This avoids
        // accidentally replacing the stored hash with an empty value.
        if ($password === null) {
            $sql = 'UPDATE users
                    SET username = :username, full_name = :full_name, role = :role
                    WHERE id = :id AND role IN (:teacher_role, :student_role)';
        } else {
            $sql = 'UPDATE users
                    SET username = :username, full_name = :full_name,
                        password_hash = :password_hash, role = :role
                    WHERE id = :id AND role IN (:teacher_role, :student_role)';
            $values['password_hash'] = $this->makePasswordHash($password);
        }

        $values['teacher_role'] = 'teacher';
        $values['student_role'] = 'student';

        $statement = $this->connection->prepare($sql);
        $statement->execute($values);
    }

    /**
     * Delete one teacher or student account.
     * The database foreign keys remove linked assignments and grades with ON DELETE CASCADE.
     * Return false when the ID did not match a managed user.
     */
    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare(
            'DELETE FROM users
             WHERE id = :id AND role IN (:teacher_role, :student_role)',
        );
        $statement->execute([
            'id' => $id,
            'teacher_role' => 'teacher',
            'student_role' => 'student',
        ]);

        return $statement->rowCount() > 0;
    }

    private function makePasswordHash(string $password): string
    {
        // PASSWORD_DEFAULT lets PHP choose a supported password hashing algorithm.
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // password_hash normally returns a string; fail safely if PHP reports a problem.
        if (!is_string($hash)) {
            throw new RuntimeException('The password could not be hashed.');
        }

        return $hash;
    }
}
