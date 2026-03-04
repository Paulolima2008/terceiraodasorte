<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class AdminInstaller
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function ensure(): void
    {
        $this->createTables();
        $this->seedPermissions();
        $this->seedAdminUser();
    }

    public function isInstalled(): bool
    {
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'admin_users'");
        return (bool) $stmt->fetchColumn();
    }

    private function createTables(): void
    {
        $queries = [
            'CREATE TABLE IF NOT EXISTS admin_users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(190) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                last_login_at DATETIME NULL,
                last_login_ip VARCHAR(45) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS admin_permissions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                module_name VARCHAR(80) NOT NULL,
                permission_key VARCHAR(120) NOT NULL UNIQUE,
                permission_label VARCHAR(140) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS admin_user_permissions (
                admin_user_id INT UNSIGNED NOT NULL,
                permission_id INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (admin_user_id, permission_id),
                CONSTRAINT fk_admin_user_permissions_user FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
                CONSTRAINT fk_admin_user_permissions_permission FOREIGN KEY (permission_id) REFERENCES admin_permissions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS admin_audit_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                admin_user_id INT UNSIGNED NULL,
                module_name VARCHAR(80) NOT NULL,
                action_name VARCHAR(120) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent VARCHAR(255) NOT NULL,
                context_json JSON NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_audit_logs_created_at (created_at),
                CONSTRAINT fk_admin_audit_logs_user FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS admin_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(120) NOT NULL UNIQUE,
                setting_value TEXT NOT NULL,
                updated_by INT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_admin_settings_user FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB',
            'CREATE TABLE IF NOT EXISTS admin_login_attempts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                identifier_key VARCHAR(190) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                attempts INT UNSIGNED NOT NULL DEFAULT 0,
                blocked_until DATETIME NULL,
                last_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_admin_login_attempt (identifier_key, ip_address)
            ) ENGINE=InnoDB',
        ];

        foreach ($queries as $query) {
            $this->pdo->exec($query);
        }
    }

    private function seedPermissions(): void
    {
        $permissions = [
            ['dashboard', 'dashboard.view', 'Visualizar dashboard'],
            ['users', 'users.view', 'Visualizar usuários'],
            ['users', 'users.manage', 'Gerenciar usuários'],
            ['permissions', 'permissions.view', 'Visualizar permissões'],
            ['permissions', 'permissions.manage', 'Gerenciar permissões'],
            ['logs', 'logs.view', 'Visualizar logs'],
            ['settings', 'settings.manage', 'Gerenciar configurações'],
            ['profile', 'profile.manage', 'Gerenciar perfil'],
        ];

        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_permissions (module_name, permission_key, permission_label)
             VALUES (:module_name, :permission_key, :permission_label)
             ON DUPLICATE KEY UPDATE permission_label = VALUES(permission_label), module_name = VALUES(module_name)'
        );

        foreach ($permissions as [$moduleName, $permissionKey, $permissionLabel]) {
            $stmt->execute([
                'module_name' => $moduleName,
                'permission_key' => $permissionKey,
                'permission_label' => $permissionLabel,
            ]);
        }
    }

    private function seedAdminUser(): void
    {
        $exists = (int) $this->pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
        if ($exists > 0) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_users (name, email, password_hash, is_active)
             VALUES (:name, :email, :password_hash, 1)'
        );
        $stmt->execute([
            'name' => 'Administrador Principal',
            'email' => ADMIN_DEFAULT_EMAIL,
            'password_hash' => password_hash(ADMIN_ACCESS_CODE, PASSWORD_DEFAULT),
        ]);

        $adminUserId = (int) $this->pdo->lastInsertId();
        $permissionIds = $this->pdo->query('SELECT id FROM admin_permissions')->fetchAll(PDO::FETCH_COLUMN);
        $insert = $this->pdo->prepare(
            'INSERT INTO admin_user_permissions (admin_user_id, permission_id, created_at)
             VALUES (:admin_user_id, :permission_id, NOW())'
        );

        foreach ($permissionIds as $permissionId) {
            $insert->execute([
                'admin_user_id' => $adminUserId,
                'permission_id' => (int) $permissionId,
            ]);
        }
    }
}
