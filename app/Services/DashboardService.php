<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class DashboardService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function metrics(): array
    {
        $totals = $this->pdo->query(
            'SELECT
                (SELECT COUNT(*) FROM codes) AS total_codes,
                (SELECT COUNT(*) FROM codes WHERE is_used = 1) AS used_codes,
                (SELECT COUNT(*) FROM spins WHERE is_winner = 1) AS winner_spins,
                (SELECT COUNT(*) FROM spins WHERE is_winner = 1 AND is_paid = 1) AS paid_winners,
                (SELECT COUNT(*) FROM admin_users WHERE is_active = 1) AS active_admins'
        )->fetch();

        $monthly = $this->pdo->query(
            'SELECT DATE_FORMAT(created_at, "%Y-%m") AS month_key, COUNT(*) AS total
             FROM spins
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
             GROUP BY DATE_FORMAT(created_at, "%Y-%m")
             ORDER BY month_key ASC'
        )->fetchAll();

        $activities = $this->pdo->query(
            'SELECT l.created_at, l.module_name, l.action_name, u.name AS admin_name, l.ip_address
             FROM admin_audit_logs l
             LEFT JOIN admin_users u ON u.id = l.admin_user_id
             ORDER BY l.id DESC
             LIMIT 8'
        )->fetchAll();

        $notifications = [
            [
                'label' => 'Prêmios pendentes',
                'value' => (int) $this->pdo->query('SELECT COUNT(*) FROM spins WHERE is_winner = 1 AND is_paid = 0')->fetchColumn(),
            ],
            [
                'label' => 'Códigos ainda disponíveis',
                'value' => (int) $totals['total_codes'] - (int) $totals['used_codes'],
            ],
        ];

        return [
            'cards' => [
                ['label' => 'Códigos cadastrados', 'value' => (int) $totals['total_codes']],
                ['label' => 'Códigos utilizados', 'value' => (int) $totals['used_codes']],
                ['label' => 'Giros premiados', 'value' => (int) $totals['winner_spins']],
                ['label' => 'Admins ativos', 'value' => (int) $totals['active_admins']],
            ],
            'monthly' => $monthly,
            'activities' => $activities,
            'notifications' => $notifications,
        ];
    }
}
