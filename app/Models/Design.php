<?php
/**
 * Design Model
 * Manages garment print, pattern, and design attributes.
 */

class Design {

    /**
     * Find design by ID.
     */
    public static function find(int $id): ?array {
        return Database::fetchOne("SELECT * FROM `designs` WHERE id = ?", [$id]);
    }

    /**
     * Get all active designs.
     */
    public static function allActive(): array {
        return Database::fetchAll("SELECT * FROM `designs` WHERE is_active = 1 ORDER BY name ASC");
    }

    /**
     * Get all designs (including inactive) with optional search.
     */
    public static function all(?string $search = null): array {
        if ($search) {
            $s = '%' . trim($search) . '%';
            return Database::fetchAll(
                "SELECT * FROM `designs` WHERE name LIKE ? OR code LIKE ? ORDER BY name ASC",
                [$s, $s]
            );
        }
        return Database::fetchAll("SELECT * FROM `designs` ORDER BY name ASC");
    }

    /**
     * Create a new design.
     */
    public static function create(array $data): int {
        $name = mb_strtoupper(trim($data['name'] ?? ''));
        $code = !empty($data['code']) ? mb_strtoupper(trim($data['code'])) : null;
        $description = trim($data['description'] ?? '');
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        if (empty($code) && !empty($name)) {
            $code = substr(preg_replace('/[^A-Z0-9]/', '', $name), 0, 4);
        }

        Database::execute(
            "INSERT INTO `designs` (`name`, `code`, `description`, `is_active`) VALUES (?, ?, ?, ?)",
            [$name, $code, $description, $isActive]
        );

        return (int)Database::lastInsertId();
    }

    /**
     * Update an existing design.
     */
    public static function update(int $id, array $data): bool {
        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $fields[] = "`name` = ?";
            $params[] = mb_strtoupper(trim($data['name']));
        }
        if (isset($data['code'])) {
            $fields[] = "`code` = ?";
            $params[] = mb_strtoupper(trim($data['code']));
        }
        if (isset($data['description'])) {
            $fields[] = "`description` = ?";
            $params[] = trim($data['description']);
        }
        if (isset($data['is_active'])) {
            $fields[] = "`is_active` = ?";
            $params[] = (int)$data['is_active'];
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        return Database::execute(
            "UPDATE `designs` SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );
    }
}
