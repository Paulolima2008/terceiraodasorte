<?php
declare(strict_types=1);

$rows = is_array($rows ?? null) ? $rows : [];
$campaignId = (int) ($campaignId ?? 0);
$campaign = is_array($campaign ?? null) ? $campaign : null;
$report = is_array($report ?? null) ? $report : [];
$status = (string) ($status ?? 'all');
$soldCount = (int) ($report['sold_count'] ?? 0);
$unsoldCount = (int) ($report['unsold_count'] ?? 0);
$totalCount = $soldCount + $unsoldCount;
$buildFilterUrl = static function (string $filterStatus) use ($campaignId): string {
    $url = BASE_URL . '/backoffice/vouchers.php?status=' . rawurlencode($filterStatus);
    if ($campaignId > 0) {
        $url .= '&campaign_id=' . $campaignId;
    }
    return $url;
};
?>
<section class="row g-4">
    <div class="col-12">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Vouchers</h2>
                    <p>Lista de codigos gerados e status de uso<?= $campaign !== null ? ' da campanha ' . e((string) $campaign['name']) : '' ?>.</p>
                </div>
            </div>
            <div class="voucher-kpis">
                <article class="voucher-kpi">
                    <span>Total de vouchers</span>
                    <strong><?= $totalCount ?></strong>
                </article>
                <article class="voucher-kpi">
                    <span>Vendidos</span>
                    <strong><?= $soldCount ?></strong>
                </article>
                <article class="voucher-kpi">
                    <span>Nao vendidos</span>
                    <strong><?= $unsoldCount ?></strong>
                </article>
            </div>

            <div class="voucher-filter-toolbar">
                <div class="voucher-filter-copy">
                    <span>Filtro por status</span>
                    <strong><?= $status === 'used' ? 'Usados' : ($status === 'unused' ? 'Nao usados' : 'Todos') ?></strong>
                </div>
                <div class="voucher-status-pills" role="tablist" aria-label="Filtro de status dos vouchers">
                    <a class="voucher-status-pill <?= $status === 'all' ? 'is-active' : '' ?>" href="<?= $buildFilterUrl('all') ?>">
                        Todos
                        <span class="badge rounded-pill"><?= $totalCount ?></span>
                    </a>
                    <a class="voucher-status-pill <?= $status === 'used' ? 'is-active' : '' ?>" href="<?= $buildFilterUrl('used') ?>">
                        Usados
                        <span class="badge rounded-pill"><?= $soldCount ?></span>
                    </a>
                    <a class="voucher-status-pill <?= $status === 'unused' ? 'is-active' : '' ?>" href="<?= $buildFilterUrl('unused') ?>">
                        Nao usados
                        <span class="badge rounded-pill"><?= $unsoldCount ?></span>
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table js-datatable align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Codigo</th>
                        <th>Usado</th>
                        <th>Resultado</th>
                        <th>Posicao</th>
                        <th>Pago</th>
                        <th>Data de uso</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($rows === []): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nenhum voucher encontrado para o filtro selecionado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>#<?= (int) $row['id'] ?></td>
                                <td><?= e((string) $row['code']) ?></td>
                                <td><?= (int) $row['is_used'] === 1 ? 'Sim' : 'Nao' ?></td>
                                <td><?= e((string) ($row['result_label'] ?? '-')) ?></td>
                                <td><?= e((string) ($row['spin_position'] ?? '-')) ?></td>
                                <td><?= (int) ($row['is_paid'] ?? 0) === 1 ? 'Sim' : 'Nao' ?></td>
                                <td><?= e((string) ($row['used_at'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </div>
</section>
