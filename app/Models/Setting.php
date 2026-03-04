<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Setting
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function all(): array
    {
        $rows = $this->pdo->query('SELECT setting_key, setting_value FROM admin_settings ORDER BY setting_key ASC')->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
        }

        return $settings;
    }

    public function saveMany(array $settings, int $updatedBy): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_settings (setting_key, setting_value, updated_by, created_at, updated_at)
             VALUES (:setting_key, :setting_value, :updated_by, NOW(), NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()'
        );

        foreach ($settings as $key => $value) {
            $stmt->execute([
                'setting_key' => $key,
                'setting_value' => $value,
                'updated_by' => $updatedBy,
            ]);
        }
    }
}
