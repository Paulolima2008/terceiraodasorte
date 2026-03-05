<?php
declare(strict_types=1);

$campaign = is_array($campaign ?? null) ? $campaign : null;
$voucherReport = is_array($voucherReport ?? null) ? $voucherReport : [];
$prizeReport = is_array($prizeReport ?? null) ? $prizeReport : [];

$soldCount = (int) ($voucherReport['sold_count'] ?? 0);
$unsoldCount = (int) ($voucherReport['unsold_count'] ?? 0);
$totalVouchers = $soldCount + $unsoldCount;
$unitValue = (float) ($voucherReport['unit_value'] ?? 0);
$soldValue = (float) ($voucherReport['sold_value'] ?? 0);
$unsoldValue = (float) ($voucherReport['unsold_value'] ?? 0);

$paidCount = (int) ($prizeReport['paid_count'] ?? 0);
$unpaidCount = (int) ($prizeReport['unpaid_count'] ?? 0);
$totalPrizes = $paidCount + $unpaidCount;
$paidValue = (float) ($prizeReport['paid_value'] ?? 0);
$unpaidValue = (float) ($prizeReport['unpaid_value'] ?? 0);

$grossRevenue = $soldValue;
$netCash = $grossRevenue - $paidValue;

$calcPercent = static function (int $value, int $total): float {
    if ($total <= 0) {
        return 0.0;
    }
    return round(($value / $total) * 100, 1);
};

$soldPercent = $calcPercent($soldCount, $totalVouchers);
$unsoldPercent = $calcPercent($unsoldCount, $totalVouchers);
$paidPercent = $calcPercent($paidCount, $totalPrizes);
$unpaidPercent = $calcPercent($unpaidCount, $totalPrizes);
?>
<section class="row g-4 report-summary-section">
    <div class="col-12">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Relatorios</h2>
                    <p>
                        Visao consolidada de vendas e pagamentos
                        <?= $campaign !== null ? ' da campanha ' . e((string) $campaign['name']) : '' ?>.
                    </p>
                </div>
            </div>
            <div class="report-kpis">
                <article class="report-kpi-card">
                    <span>Vouchers vendidos</span>
                    <strong><?= $soldCount ?></strong>
                    <small><?= number_format($soldPercent, 1, ',', '.') ?>% do total</small>
                </article>
                <article class="report-kpi-card">
                    <span>Receita bruta</span>
                    <strong>R$ <?= number_format($grossRevenue, 2, ',', '.') ?></strong>
                    <small><?= $totalVouchers ?> vouchers | R$ <?= number_format($unitValue, 2, ',', '.') ?> un.</small>
                </article>
                <article class="report-kpi-card">
                    <span>Premios pagos</span>
                    <strong>R$ <?= number_format($paidValue, 2, ',', '.') ?></strong>
                    <small><?= $paidCount ?> pagos (<?= number_format($paidPercent, 1, ',', '.') ?>%)</small>
                </article>
                <article class="report-kpi-card">
                    <span>Caixa liquido</span>
                    <strong>R$ <?= number_format($netCash, 2, ',', '.') ?></strong>
                    <small>Receita - premios pagos</small>
                </article>
            </div>
        </article>
    </div>
</section>

<section class="row g-4">
    <div class="col-12 col-xl-6">
        <article class="admin-panel-card report-detail-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Desempenho de vouchers</h2>
                    <p>Comparativo de vendidos e nao vendidos.</p>
                </div>
            </div>
            <div class="report-progress-wrap">
                <div class="report-progress-row">
                    <div class="report-progress-copy">
                        <strong>Vendidos</strong>
                        <span><?= $soldCount ?> vouchers</span>
                    </div>
                    <div class="report-progress-bar">
                        <span style="width: <?= $soldPercent ?>%"></span>
                    </div>
                    <strong class="report-progress-value"><?= number_format($soldPercent, 1, ',', '.') ?>%</strong>
                </div>
                <div class="report-progress-row">
                    <div class="report-progress-copy">
                        <strong>Nao vendidos</strong>
                        <span><?= $unsoldCount ?> vouchers</span>
                    </div>
                    <div class="report-progress-bar is-warning">
                        <span style="width: <?= $unsoldPercent ?>%"></span>
                    </div>
                    <strong class="report-progress-value"><?= number_format($unsoldPercent, 1, ',', '.') ?>%</strong>
                </div>
            </div>

            <div class="table-responsive mt-3">
                <table class="table align-middle mb-0 report-table">
                    <tbody>
                    <tr><th>Valor unitario</th><td>R$ <?= number_format($unitValue, 2, ',', '.') ?></td></tr>
                    <tr><th>Valor vendido</th><td>R$ <?= number_format($soldValue, 2, ',', '.') ?></td></tr>
                    <tr><th>Valor nao vendido</th><td>R$ <?= number_format($unsoldValue, 2, ',', '.') ?></td></tr>
                    </tbody>
                </table>
            </div>
        </article>
    </div>

    <div class="col-12 col-xl-6">
        <article class="admin-panel-card report-detail-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Status de premios</h2>
                    <p>Controle financeiro de premios pagos e pendentes.</p>
                </div>
            </div>
            <div class="report-progress-wrap">
                <div class="report-progress-row">
                    <div class="report-progress-copy">
                        <strong>Pagos</strong>
                        <span><?= $paidCount ?> premios</span>
                    </div>
                    <div class="report-progress-bar">
                        <span style="width: <?= $paidPercent ?>%"></span>
                    </div>
                    <strong class="report-progress-value"><?= number_format($paidPercent, 1, ',', '.') ?>%</strong>
                </div>
                <div class="report-progress-row">
                    <div class="report-progress-copy">
                        <strong>Pendentes</strong>
                        <span><?= $unpaidCount ?> premios</span>
                    </div>
                    <div class="report-progress-bar is-warning">
                        <span style="width: <?= $unpaidPercent ?>%"></span>
                    </div>
                    <strong class="report-progress-value"><?= number_format($unpaidPercent, 1, ',', '.') ?>%</strong>
                </div>
            </div>

            <div class="table-responsive mt-3">
                <table class="table align-middle mb-0 report-table">
                    <tbody>
                    <tr><th>Valor pago</th><td>R$ <?= number_format($paidValue, 2, ',', '.') ?></td></tr>
                    <tr><th>Valor pendente</th><td>R$ <?= number_format($unpaidValue, 2, ',', '.') ?></td></tr>
                    <tr><th>Total em premios</th><td>R$ <?= number_format($paidValue + $unpaidValue, 2, ',', '.') ?></td></tr>
                    </tbody>
                </table>
            </div>
        </article>
    </div>
</section>
