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

    public function metrics(int $campaignId = 0): array
    {
        $campaignId = $this->normalizeCampaignId($campaignId);

        if ($campaignId > 0) {
            $totalsStmt = $this->pdo->prepare(
                'SELECT
                    (SELECT COUNT(*) FROM campaign_vouchers WHERE campaign_id = :campaign_id_total) AS total_vouchers,
                    (SELECT COUNT(*) FROM campaign_vouchers WHERE campaign_id = :campaign_id_used AND is_used = 1) AS used_vouchers,
                    (SELECT COUNT(*) FROM campaign_spins WHERE campaign_id = :campaign_id_winner AND is_winner = 1) AS winner_spins,
                    (SELECT COUNT(*) FROM campaign_spins WHERE campaign_id = :campaign_id_paid AND is_winner = 1 AND is_paid = 1) AS paid_winners,
                    (SELECT COUNT(*) FROM campaign_spins WHERE campaign_id = :campaign_id_unpaid AND is_winner = 1 AND is_paid = 0) AS unpaid_prizes,
                    (SELECT COUNT(*) FROM campaign_prize_schedule WHERE campaign_id = :campaign_id_schedule) AS scheduled_prizes'
            );
            $totalsStmt->execute([
                'campaign_id_total' => $campaignId,
                'campaign_id_used' => $campaignId,
                'campaign_id_winner' => $campaignId,
                'campaign_id_paid' => $campaignId,
                'campaign_id_unpaid' => $campaignId,
                'campaign_id_schedule' => $campaignId,
            ]);
            $totals = $totalsStmt->fetch();
        } else {
            $totals = $this->pdo->query(
                'SELECT
                    (SELECT COUNT(*) FROM campaign_vouchers) AS total_vouchers,
                    (SELECT COUNT(*) FROM campaign_vouchers WHERE is_used = 1) AS used_vouchers,
                    (SELECT COUNT(*) FROM campaign_spins WHERE is_winner = 1) AS winner_spins,
                    (SELECT COUNT(*) FROM campaign_spins WHERE is_winner = 1 AND is_paid = 1) AS paid_winners,
                    (SELECT COUNT(*) FROM campaign_spins WHERE is_winner = 1 AND is_paid = 0) AS unpaid_prizes,
                    (SELECT COUNT(*) FROM campaign_prize_schedule) AS scheduled_prizes'
            )->fetch();
        }

        if ($campaignId > 0) {
            $financeStmt = $this->pdo->prepare(
                'SELECT
                    COALESCE(SUM(CASE WHEN v.is_used = 1 THEN c.voucher_unit_value ELSE 0 END), 0) AS gross_revenue,
                    (
                        SELECT COALESCE(SUM(CASE WHEN s.is_paid = 1 THEN COALESCE(p.amount, 0) ELSE 0 END), 0)
                        FROM campaign_spins s
                        LEFT JOIN campaign_prizes p ON p.id = s.prize_id
                        WHERE s.campaign_id = :finance_campaign_id_paid
                          AND s.is_winner = 1
                    ) AS paid_prizes_value,
                    (
                        SELECT COALESCE(SUM(p.amount), 0)
                        FROM campaign_prizes p
                        WHERE p.campaign_id = :finance_campaign_id_total_prizes
                    ) AS total_prizes_value
                 FROM campaign_vouchers v
                 INNER JOIN campaigns c ON c.id = v.campaign_id
                 WHERE v.campaign_id = :finance_campaign_id_revenue'
            );
            $financeStmt->execute([
                'finance_campaign_id_paid' => $campaignId,
                'finance_campaign_id_total_prizes' => $campaignId,
                'finance_campaign_id_revenue' => $campaignId,
            ]);
            $finance = $financeStmt->fetch() ?: [];
        } else {
            $finance = $this->pdo->query(
                'SELECT
                    COALESCE(SUM(CASE WHEN v.is_used = 1 THEN c.voucher_unit_value ELSE 0 END), 0) AS gross_revenue,
                    (
                        SELECT COALESCE(SUM(CASE WHEN s.is_paid = 1 THEN COALESCE(p.amount, 0) ELSE 0 END), 0)
                        FROM campaign_spins s
                        LEFT JOIN campaign_prizes p ON p.id = s.prize_id
                        WHERE s.is_winner = 1
                    ) AS paid_prizes_value,
                    (SELECT COALESCE(SUM(amount), 0) FROM campaign_prizes) AS total_prizes_value
                 FROM campaign_vouchers v
                 INNER JOIN campaigns c ON c.id = v.campaign_id'
            )->fetch() ?: [];
        }

        if ($campaignId > 0) {
            $vouchersStmt = $this->pdo->prepare(
                'SELECT
                    c.id,
                    c.name AS campaign_name,
                    SUM(CASE WHEN v.is_used = 1 THEN 1 ELSE 0 END) AS sold_count,
                    SUM(CASE WHEN v.is_used = 0 THEN 1 ELSE 0 END) AS unsold_count
                 FROM campaigns c
                 LEFT JOIN campaign_vouchers v ON v.campaign_id = c.id
                 WHERE c.id = :campaign_id
                 GROUP BY c.id, c.name
                 ORDER BY c.created_at DESC, c.id DESC'
            );
            $vouchersStmt->execute(['campaign_id' => $campaignId]);
            $vouchersByCampaign = $vouchersStmt->fetchAll();
        } else {
            $vouchersByCampaign = $this->pdo->query(
                'SELECT
                    c.id,
                    c.name AS campaign_name,
                    SUM(CASE WHEN v.is_used = 1 THEN 1 ELSE 0 END) AS sold_count,
                    SUM(CASE WHEN v.is_used = 0 THEN 1 ELSE 0 END) AS unsold_count
                 FROM campaigns c
                 LEFT JOIN campaign_vouchers v ON v.campaign_id = c.id
                 GROUP BY c.id, c.name
                 ORDER BY c.created_at DESC, c.id DESC'
            )->fetchAll();
        }

        $pendingWinners = (int) ($totals['unpaid_prizes'] ?? 0);
        $availableVouchers = (int) $totals['total_vouchers'] - (int) $totals['used_vouchers'];
        $grossRevenue = (float) ($finance['gross_revenue'] ?? 0);
        $paidPrizesValue = (float) ($finance['paid_prizes_value'] ?? 0);
        $totalPrizesValue = (float) ($finance['total_prizes_value'] ?? 0);
        $unpaidPrizesValue = max(0.0, $totalPrizesValue - $paidPrizesValue);
        $netCashValue = $grossRevenue - $paidPrizesValue;

        return [
            'cards' => [
                ['label' => 'Vouchers gerados', 'value' => (int) ($totals['total_vouchers'] ?? 0)],
                ['label' => 'Vouchers usados', 'value' => (int) ($totals['used_vouchers'] ?? 0)],
                ['label' => 'Giros premiados', 'value' => (int) ($totals['winner_spins'] ?? 0)],
                ['label' => 'Premios pagos', 'value' => (int) ($totals['paid_winners'] ?? 0)],
                ['label' => 'Premios nao pagos', 'value' => $pendingWinners],
                ['label' => 'Valor total arrecadado', 'value' => $this->formatMoney($grossRevenue)],
                ['label' => 'Valor pago em premios', 'value' => $this->formatMoney($paidPrizesValue)],
                ['label' => 'Valor liquido em caixa', 'value' => $this->formatMoney($netCashValue)],
            ],
            'vouchers_by_campaign' => $vouchersByCampaign,
            'notifications' => [
                ['label' => 'Premios pendentes', 'value' => $pendingWinners],
                ['label' => 'Vouchers disponiveis', 'value' => $availableVouchers],
                ['label' => 'Premios agendados', 'value' => (int) $totals['scheduled_prizes']],
            ],
            'prize_value_chart' => [
                'paid_value' => $paidPrizesValue,
                'unpaid_value' => $unpaidPrizesValue,
                'total_value' => $totalPrizesValue,
            ],
            'selected_campaign_id' => $campaignId,
        ];
    }

    private function normalizeCampaignId(int $campaignId): int
    {
        if ($campaignId <= 0) {
            return 0;
        }

        $stmt = $this->pdo->prepare('SELECT 1 FROM campaigns WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $campaignId]);
        return $stmt->fetchColumn() ? $campaignId : 0;
    }

    private function formatMoney(float $amount): string
    {
        $prefix = $amount < 0 ? '-R$ ' : 'R$ ';
        return $prefix . number_format(abs($amount), 2, ',', '.');
    }
}
