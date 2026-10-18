<?php

declare(strict_types=1);

/** Reads, creates, updates, and safely deletes evaluable items through PDO. */
class EvaluableItemModel
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        // Save the existing connection for the SQL methods below.
        $this->connection = $connection;
    }

    /** Return evaluable items for the administrator list and project-assignment choices. */
    public function getAll(): array
    {
        $statement = $this->connection->query(
            'SELECT id, code, name, description FROM evaluable_items ORDER BY name',
        );

        return $statement->fetchAll();
    }

    /** Check a unique code before insert so users see a useful message. */
    public function codeExists(string $code, ?int $exceptId = null): bool
    {
        if ($exceptId === null) {
            $statement = $this->connection->prepare(
                'SELECT id FROM evaluable_items WHERE code = :code LIMIT 1',
            );
            $statement->execute(['code' => $code]);
        } else {
            $statement = $this->connection->prepare(
                'SELECT id FROM evaluable_items WHERE code = :code AND id <> :except_id LIMIT 1',
            );
            $statement->execute(['code' => $code, 'except_id' => $exceptId]);
        }

        return $statement->fetchColumn() !== false;
    }

    /** Check whether an item ID exists before creating a project assignment. */
    public function exists(int $itemId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT id FROM evaluable_items WHERE id = :id LIMIT 1',
        );
        $statement->execute(['id' => $itemId]);

        return $statement->fetchColumn() !== false;
    }

    /** Insert one item. The schema's unique code constraint protects against race conditions. */
    public function create(string $code, string $name, string $description): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO evaluable_items (code, name, description)
             VALUES (:code, :name, :description)',
        );
        $statement->execute([
            'code' => $code,
            'name' => $name,
            'description' => $description,
        ]);
    }

    /** Update the item fields while leaving every project assignment intact. */
    public function update(int $id, string $code, string $name, string $description): void
    {
        $statement = $this->connection->prepare(
            'UPDATE evaluable_items
             SET code = :code, name = :name, description = :description
             WHERE id = :id',
        );
        $statement->execute([
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'description' => $description,
        ]);
    }

    /** Delete an evaluable item only when no project or grade depends on it. */
    public function deleteIfUnused(int $id): string
    {
        $this->connection->beginTransaction();

        try {
            // Lock the item while checking its project links to prevent an unexpected cascade.
            $item = $this->connection->prepare(
                'SELECT id FROM evaluable_items WHERE id = :id FOR UPDATE',
            );
            $item->execute(['id' => $id]);

            if ($item->fetchColumn() === false) {
                $this->connection->rollBack();
                return 'missing';
            }

            $relatedRows = $this->connection->prepare(
                'SELECT
                    EXISTS (
                        SELECT 1 FROM project_evaluable_items
                        WHERE evaluable_item_id = :assignment_item_id
                    )
                    OR EXISTS (SELECT 1 FROM grades WHERE evaluable_item_id = :grade_item_id)',
            );
            $relatedRows->execute([
                'assignment_item_id' => $id,
                'grade_item_id' => $id,
            ]);

            if ((bool) $relatedRows->fetchColumn()) {
                $this->connection->rollBack();
                return 'has_related_data';
            }

            $delete = $this->connection->prepare('DELETE FROM evaluable_items WHERE id = :id');
            $delete->execute(['id' => $id]);
            $this->connection->commit();

            return $delete->rowCount() > 0 ? 'deleted' : 'missing';
        } catch (Throwable $error) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $error;
        }
    }
}
