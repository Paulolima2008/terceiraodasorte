<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

final class RouletteOperation
{
    private const CAMPAIGN_SESSION_KEY = 'admin_campaign_id';

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function campaigns(): array
    {
        return $this->pdo->query(
            'SELECT id, name, award_mode, is_active, voucher_quantity, prize_quantity
             FROM campaigns
             ORDER BY is_active DESC, id DESC'
        )->fetchAll();
    }

    public function resolveCampaignId(int $campaignId): int
    {
        if ($campaignId > 0 && $this->campaignExists($campaignId)) {
            $_SESSION[self::CAMPAIGN_SESSION_KEY] = $campaignId;
            return $campaignId;
        }

        $sessionCampaignId = (int) ($_SESSION[self::CAMPAIGN_SESSION_KEY] ?? 0);
        if ($sessionCampaignId > 0 && $this->campaignExists($sessionCampaignId)) {
            return $sessionCampaignId;
        }

        $fallbackCampaignId = (int) $this->pdo->query('SELECT id FROM campaigns ORDER BY is_active DESC, id ASC LIMIT 1')->fetchColumn();
        if ($fallbackCampaignId > 0) {
            $_SESSION[self::CAMPAIGN_SESSION_KEY] = $fallbackCampaignId;
        }

        return $fallbackCampaignId;
    }

    private function campaignExists(int $campaignId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM campaigns WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $campaignId]);
        return (bool) $stmt->fetchColumn();
    }

    public function campaign(int $campaignId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, voucher_quantity, voucher_unit_value, prize_quantity, prize_total_value, award_mode, is_active
             FROM campaigns
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $campaignId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function summary(int $campaignId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM campaign_vouchers WHERE campaign_id = :campaign_id) AS total_codes,
                (SELECT COUNT(*) FROM campaign_vouchers WHERE campaign_id = :campaign_id AND is_used = 1) AS used_codes,
                (SELECT COUNT(*) FROM campaign_spins WHERE campaign_id = :campaign_id AND is_winner = 1) AS winner_spins,
                (SELECT COUNT(*) FROM campaign_spins WHERE campaign_id = :campaign_id AND is_winner = 1 AND is_paid = 1) AS paid_winners,
                (SELECT COUNT(*) FROM campaign_prize_schedule WHERE campaign_id = :campaign_id) AS scheduled_count'
        );
        $stmt->execute(['campaign_id' => $campaignId]);
        $row = $stmt->fetch();

        return $row ?: [
            'total_codes' => 0,
            'used_codes' => 0,
            'winner_spins' => 0,
            'paid_winners' => 0,
            'scheduled_count' => 0,
        ];
    }

    public function findCodeDetails(string $code, int $campaignId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                v.code,
                v.is_used,
                v.used_at,
                s.id AS spin_id,
                s.spin_position,
                s.result_label,
                s.is_winner,
                s.is_paid,
                s.paid_at
             FROM campaign_vouchers v
             LEFT JOIN campaign_spins s ON s.voucher_id = v.id
             WHERE v.code = :code AND v.campaign_id = :campaign_id
             LIMIT 1'
        );
        $stmt->execute([
            'code' => $code,
            'campaign_id' => $campaignId,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function prizes(int $campaignId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, amount
             FROM campaign_prizes
             WHERE campaign_id = :campaign_id
             ORDER BY id ASC'
        );
        $stmt->execute(['campaign_id' => $campaignId]);
        return $stmt->fetchAll();
    }

    public function scheduleRows(int $campaignId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ps.id, ps.spin_position, p.name, p.amount
             FROM campaign_prize_schedule ps
             INNER JOIN campaign_prizes p ON p.id = ps.prize_id
             WHERE ps.campaign_id = :campaign_id
             ORDER BY ps.spin_position ASC'
        );
        $stmt->execute(['campaign_id' => $campaignId]);
        return $stmt->fetchAll();
    }

    public function markWinnerAsPaid(int $campaignId, int $spinId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE campaign_spins
             SET is_paid = 1, paid_at = NOW()
             WHERE campaign_id = :campaign_id
               AND id = :id
               AND is_winner = 1
               AND is_paid = 0'
        );
        $stmt->execute([
            'campaign_id' => $campaignId,
            'id' => $spinId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function addSchedule(int $campaignId, int $position, int $prizeId): void
    {
        $campaign = $this->campaign($campaignId);
        if (!$campaign) {
            throw new RuntimeException('Campanha nao encontrada.');
        }

        if ((string) $campaign['award_mode'] !== 'manual') {
            throw new RuntimeException('A campanha automatica nao permite cadastro manual de agenda.');
        }

        $maxPosition = (int) $campaign['voucher_quantity'];
        if ($position < 1 || $position > $maxPosition) {
            throw new RuntimeException('A posicao deve estar entre 1 e ' . $maxPosition . '.');
        }

        $prizeStmt = $this->pdo->prepare(
            'SELECT id FROM campaign_prizes WHERE id = :id AND campaign_id = :campaign_id LIMIT 1'
        );
        $prizeStmt->execute([
            'id' => $prizeId,
            'campaign_id' => $campaignId,
        ]);
        if (!$prizeStmt->fetch()) {
            throw new RuntimeException('Premio invalido para a campanha selecionada.');
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO campaign_prize_schedule (campaign_id, spin_position, prize_id, created_at)
             VALUES (:campaign_id, :spin_position, :prize_id, NOW())'
        );

        try {
            $insert->execute([
                'campaign_id' => $campaignId,
                'spin_position' => $position,
                'prize_id' => $prizeId,
            ]);
        } catch (Throwable $exception) {
            if ((string) $exception->getCode() === '23000') {
                throw new RuntimeException('Posicao ou premio ja utilizado na agenda desta campanha.');
            }

            throw $exception;
        }
    }

    public function removeSchedule(int $campaignId, int $scheduleId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM campaign_prize_schedule
             WHERE id = :id AND campaign_id = :campaign_id'
        );
        $stmt->execute([
            'id' => $scheduleId,
            'campaign_id' => $campaignId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function regenerateAutomaticSchedule(int $campaignId): void
    {
        $campaign = new Campaign();
        $campaign->generateAutomaticSchedule($campaignId);
    }

    public function voucherRows(int $campaignId, string $statusFilter): array
    {
        $where = 'v.campaign_id = :campaign_id';
        if ($statusFilter === 'used') {
            $where .= ' AND v.is_used = 1';
        } elseif ($statusFilter === 'unused') {
            $where .= ' AND v.is_used = 0';
        }

        $stmt = $this->pdo->prepare(
            'SELECT
                v.id,
                v.code,
                v.is_used,
                v.used_at,
                v.created_at,
                s.spin_position,
                s.result_label,
                s.is_paid
             FROM campaign_vouchers v
             LEFT JOIN campaign_spins s ON s.voucher_id = v.id
             WHERE ' . $where . '
             ORDER BY v.id DESC'
        );
        $stmt->execute(['campaign_id' => $campaignId]);

        return $stmt->fetchAll();
    }

    public function paymentRows(int $campaignId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                s.id,
                s.spin_position,
                s.result_label,
                s.is_paid,
                s.paid_at,
                s.created_at,
                v.code,
                p.amount
             FROM campaign_spins s
             INNER JOIN campaign_vouchers v ON v.id = s.voucher_id
             LEFT JOIN campaign_prizes p ON p.id = s.prize_id
             WHERE s.campaign_id = :campaign_id
               AND s.is_winner = 1
             ORDER BY s.id DESC'
        );
        $stmt->execute(['campaign_id' => $campaignId]);

        return $stmt->fetchAll();
    }

    public function voucherReport(int $campaignId): array
    {
        $campaign = $this->campaign($campaignId);
        if (!$campaign) {
            return [
                'sold_count' => 0,
                'unsold_count' => 0,
                'sold_value' => 0.0,
                'unsold_value' => 0.0,
                'unit_value' => 0.0,
            ];
        }

        $stmt = $this->pdo->prepare(
            'SELECT
                SUM(CASE WHEN is_used = 1 THEN 1 ELSE 0 END) AS sold_count,
                SUM(CASE WHEN is_used = 0 THEN 1 ELSE 0 END) AS unsold_count
             FROM campaign_vouchers
             WHERE campaign_id = :campaign_id'
        );
        $stmt->execute(['campaign_id' => $campaignId]);
        $row = $stmt->fetch() ?: ['sold_count' => 0, 'unsold_count' => 0];

        $unitValue = (float) $campaign['voucher_unit_value'];
        $soldCount = (int) ($row['sold_count'] ?? 0);
        $unsoldCount = (int) ($row['unsold_count'] ?? 0);

        return [
            'sold_count' => $soldCount,
            'unsold_count' => $unsoldCount,
            'unit_value' => $unitValue,
            'sold_value' => $soldCount * $unitValue,
            'unsold_value' => $unsoldCount * $unitValue,
        ];
    }

    public function prizePaymentReport(int $campaignId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                SUM(CASE WHEN s.is_paid = 1 THEN 1 ELSE 0 END) AS paid_count,
                SUM(CASE WHEN s.is_paid = 0 THEN 1 ELSE 0 END) AS unpaid_count,
                SUM(CASE WHEN s.is_paid = 1 THEN COALESCE(p.amount, 0) ELSE 0 END) AS paid_value,
                SUM(CASE WHEN s.is_paid = 0 THEN COALESCE(p.amount, 0) ELSE 0 END) AS unpaid_value
             FROM campaign_spins s
             LEFT JOIN campaign_prizes p ON p.id = s.prize_id
             WHERE s.campaign_id = :campaign_id
               AND s.is_winner = 1'
        );
        $stmt->execute(['campaign_id' => $campaignId]);
        $row = $stmt->fetch() ?: [];

        return [
            'paid_count' => (int) ($row['paid_count'] ?? 0),
            'unpaid_count' => (int) ($row['unpaid_count'] ?? 0),
            'paid_value' => (float) ($row['paid_value'] ?? 0),
            'unpaid_value' => (float) ($row['unpaid_value'] ?? 0),
        ];
    }
}
