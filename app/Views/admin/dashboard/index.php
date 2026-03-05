<?php
declare(strict_types=1);
$chartApiUrl = BASE_URL . '/backoffice/api/metrics.php';
?>
<section class="row g-4 mb-4">
    <?php foreach ($metrics['cards'] as $card): ?>
        <div class="col-12 col-md-6 col-xl-3">
            <article class="metric-card">
                <span><?= e($card['label']) ?></span>
                <strong><?= e((string) $card['value']) ?></strong>
            </article>
        </div>
    <?php endforeach; ?>
</section>

<section class="row g-4">
    <div class="col-12 col-xl-6">
        <article class="admin-panel-card dashboard-chart-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Vouchers por campanha</h2>
                    <p>Comparativo de vouchers vendidos e nao vendidos em cada campanha.</p>
                </div>
            </div>
            <div class="dashboard-chart-wrap">
                <canvas id="dashboardChart" data-metrics-url="<?= e($chartApiUrl) ?>"></canvas>
            </div>
        </article>
    </div>
    <div class="col-12 col-xl-6">
        <article class="admin-panel-card dashboard-chart-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Valor dos premios</h2>
                    <p>Distribuicao entre valor pago e saldo de premios.</p>
                </div>
            </div>
            <div class="dashboard-pie-wrap">
                <canvas id="dashboardPrizeChart" data-metrics-url="<?= e($chartApiUrl) ?>"></canvas>
            </div>
        </article>
    </div>
</section>
