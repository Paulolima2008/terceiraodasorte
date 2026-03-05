<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

final class Campaign
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function allWithMetrics(): array
    {
        return $this->pdo->query(
            'SELECT
                c.id,
                c.name,
                c.voucher_quantity,
                c.voucher_unit_value,
                c.prize_quantity,
                c.prize_total_value,
                c.award_mode,
                c.is_active,
                c.created_at,
                (SELECT COUNT(*) FROM campaign_vouchers v WHERE v.campaign_id = c.id) AS vouchers_generated,
                (SELECT COUNT(*) FROM campaign_vouchers v WHERE v.campaign_id = c.id AND v.is_used = 1) AS vouchers_used,
                (SELECT COUNT(*) FROM campaign_prizes p WHERE p.campaign_id = c.id) AS prizes_generated,
                (SELECT COUNT(*) FROM campaign_prize_schedule ps WHERE ps.campaign_id = c.id) AS prizes_scheduled
             FROM campaigns c
             ORDER BY c.id DESC'
        )->fetchAll();
    }

    public function allBasic(): array
    {
        return $this->pdo->query(
            'SELECT id, name, award_mode, is_active
             FROM campaigns
             ORDER BY is_active DESC, id DESC'
        )->fetchAll();
    }

    public function find(int $campaignId): ?array
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

    public function firstCampaignId(): int
    {
        return (int) $this->pdo->query('SELECT id FROM campaigns ORDER BY is_active DESC, id ASC LIMIT 1')->fetchColumn();
    }

    public function createAndBootstrap(array $payload): int
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $voucherQuantity = (int) ($payload['voucher_quantity'] ?? 0);
        $voucherUnitValue = (float) ($payload['voucher_unit_value'] ?? 0);
        $prizeQuantity = (int) ($payload['prize_quantity'] ?? 0);
        $prizeTotalValue = (float) ($payload['prize_total_value'] ?? 0);
        $awardMode = (string) ($payload['award_mode'] ?? 'manual');
        $setActive = !empty($payload['is_active']);

        if ($name === '') {
            throw new RuntimeException('Informe o nome da campanha.');
        }
        if ($voucherQuantity <= 0) {
            throw new RuntimeException('A quantidade de vouchers deve ser maior que zero.');
        }
        if ($prizeQuantity <= 0) {
            throw new RuntimeException('A quantidade de premios deve ser maior que zero.');
        }
        if ($prizeQuantity > $voucherQuantity) {
            throw new RuntimeException('A quantidade de premios nao pode ser maior que a de vouchers.');
        }
        if ($prizeTotalValue <= 0) {
            throw new RuntimeException('Informe um valor total de premios maior que zero.');
        }
        if ($voucherUnitValue < 0) {
            throw new RuntimeException('O valor unitario do voucher nao pode ser negativo.');
        }
        if (!in_array($awardMode, ['manual', 'automatic'], true)) {
            throw new RuntimeException('Forma de premiacao invalida.');
        }

        $this->pdo->beginTransaction();

        try {
            if ($setActive) {
                $this->pdo->exec('UPDATE campaigns SET is_active = 0');
            }

            $insertCampaign = $this->pdo->prepare(
                'INSERT INTO campaigns
                    (name, voucher_quantity, voucher_unit_value, prize_quantity, prize_total_value, award_mode, is_active, created_at, updated_at)
                 VALUES
                    (:name, :voucher_quantity, :voucher_unit_value, :prize_quantity, :prize_total_value, :award_mode, :is_active, NOW(), NOW())'
            );
            $insertCampaign->execute([
                'name' => $name,
                'voucher_quantity' => $voucherQuantity,
                'voucher_unit_value' => $voucherUnitValue,
                'prize_quantity' => $prizeQuantity,
                'prize_total_value' => $prizeTotalValue,
                'award_mode' => $awardMode,
                'is_active' => $setActive ? 1 : 0,
            ]);

            $campaignId = (int) $this->pdo->lastInsertId();

            $insertState = $this->pdo->prepare(
                'INSERT INTO campaign_states (campaign_id, next_spin_position, updated_at)
                 VALUES (:campaign_id, 1, NOW())'
            );
            $insertState->execute(['campaign_id' => $campaignId]);

            $this->generateVouchers($campaignId, $voucherQuantity);
            $this->generatePrizes($campaignId, $prizeQuantity, $prizeTotalValue);

            if ($awardMode === 'automatic') {
                $this->generateAutomaticSchedule($campaignId);
            }

            $this->pdo->commit();

            return $campaignId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function activate(int $campaignId): void
    {
        $campaign = $this->find($campaignId);
        if (!$campaign) {
            throw new RuntimeException('Campanha nao encontrada.');
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec('UPDATE campaigns SET is_active = 0');
            $stmt = $this->pdo->prepare('UPDATE campaigns SET is_active = 1, updated_at = NOW() WHERE id = :id');
            $stmt->execute(['id' => $campaignId]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function generateAutomaticSchedule(int $campaignId): void
    {
        $campaign = $this->find($campaignId);
        if (!$campaign) {
            throw new RuntimeException('Campanha nao encontrada para gerar agenda automatica.');
        }

        $voucherQuantity = (int) $campaign['voucher_quantity'];
        $prizes = $this->pdo->prepare('SELECT id FROM campaign_prizes WHERE campaign_id = :campaign_id ORDER BY id ASC');
        $prizes->execute(['campaign_id' => $campaignId]);
        $prizeIds = array_map('intval', $prizes->fetchAll(PDO::FETCH_COLUMN));

        if ($prizeIds === []) {
            throw new RuntimeException('Nenhum premio encontrado para gerar a agenda automatica.');
        }
        if (count($prizeIds) > $voucherQuantity) {
            throw new RuntimeException('A campanha possui mais premios do que vouchers.');
        }

        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $delete = $this->pdo->prepare('DELETE FROM campaign_prize_schedule WHERE campaign_id = :campaign_id');
            $delete->execute(['campaign_id' => $campaignId]);

            $positions = range(1, $voucherQuantity);
            shuffle($positions);
            $selectedPositions = array_slice($positions, 0, count($prizeIds));
            sort($selectedPositions);

            $insert = $this->pdo->prepare(
                'INSERT INTO campaign_prize_schedule (campaign_id, spin_position, prize_id, created_at)
                 VALUES (:campaign_id, :spin_position, :prize_id, NOW())'
            );

            foreach ($prizeIds as $index => $prizeId) {
                $insert->execute([
                    'campaign_id' => $campaignId,
                    'spin_position' => $selectedPositions[$index],
                    'prize_id' => $prizeId,
                ]);
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function generateVouchers(int $campaignId, int $quantity): void
    {
        $existingCodes = [];
        foreach ($this->pdo->query('SELECT code FROM campaign_vouchers')->fetchAll(PDO::FETCH_COLUMN) as $code) {
            $existingCodes[(string) $code] = true;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO campaign_vouchers (campaign_id, code, is_used, used_at, created_at)
             VALUES (:campaign_id, :code, 0, NULL, NOW())'
        );

        $remaining = $quantity;
        while ($remaining > 0) {
            $code = $this->generateCode();
            if (isset($existingCodes[$code])) {
                continue;
            }

            $insert->execute([
                'campaign_id' => $campaignId,
                'code' => $code,
            ]);
            $existingCodes[$code] = true;
            $remaining--;
        }
    }

    private function generatePrizes(int $campaignId, int $quantity, float $totalValue): void
    {
        $amounts = $this->splitAmount($quantity, $totalValue);
        $insert = $this->pdo->prepare(
            'INSERT INTO campaign_prizes (campaign_id, name, amount, created_at)
             VALUES (:campaign_id, :name, :amount, NOW())'
        );

        foreach ($amounts as $index => $amount) {
            $insert->execute([
                'campaign_id' => $campaignId,
                'name' => 'Premio #' . ($index + 1) . ' - R$ ' . number_format($amount, 2, ',', '.'),
                'amount' => $amount,
            ]);
        }
    }

    private function splitAmount(int $quantity, float $totalValue): array
    {
        $totalCents = (int) round($totalValue * 100);
        if ($quantity <= 0 || $totalCents <= 0) {
            return [];
        }

        $base = intdiv($totalCents, $quantity);
        $remainder = $totalCents % $quantity;

        $values = array_fill(0, $quantity, $base);
        for ($i = 0; $i < $remainder; $i++) {
            $values[$i]++;
        }

        shuffle($values);

        return array_map(static fn (int $cents): float => $cents / 100, $values);
    }

    private function generateCode(): string
    {
        $positions = [0, 1, 2, 3, 4];
        shuffle($positions);
        $letterPositions = array_flip(array_slice($positions, 0, 2));

        $result = [];
        for ($i = 0; $i < 5; $i++) {
            if (isset($letterPositions[$i])) {
                $result[] = chr(random_int(65, 90));
            } else {
                $result[] = (string) random_int(0, 9);
            }
        }

        return implode('', $result);
    }
}
