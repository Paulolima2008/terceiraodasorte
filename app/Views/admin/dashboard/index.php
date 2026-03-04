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
    <div class="col-12 col-xl-8">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Desempenho mensal</h2>
                    <p>Resumo visual dos giros registrados ao longo dos últimos meses.</p>
                </div>
            </div>
            <canvas id="dashboardChart" data-metrics-url="<?= e($chartApiUrl) ?>" height="120"></canvas>
        </article>
    </div>
    <div class="col-12 col-xl-4">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Notificações</h2>
                    <p>Sinais rápidos do que merece atenção.</p>
                </div>
            </div>
            <div class="notification-stack">
                <?php foreach ($metrics['notifications'] as $notification): ?>
                    <div class="notification-item">
                        <span><?= e($notification['label']) ?></span>
                        <strong><?= e((string) $notification['value']) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </div>
</section>

<section class="row g-4 mt-1">
    <div class="col-12 col-xl-7">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Últimas atividades</h2>
                    <p>Eventos recentes auditados pelo sistema.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Usuário</th>
                        <th>Módulo</th>
                        <th>Ação</th>
                        <th>IP</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($metrics['activities'] as $activity): ?>
                        <tr>
                            <td><?= e((string) $activity['created_at']) ?></td>
                            <td><?= e((string) ($activity['admin_name'] ?? 'Sistema')) ?></td>
                            <td><?= e((string) $activity['module_name']) ?></td>
                            <td><?= e((string) $activity['action_name']) ?></td>
                            <td><?= e((string) $activity['ip_address']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </div>
    <div class="col-12 col-xl-5">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Atalhos</h2>
                    <p>Acesso rápido para os módulos estratégicos.</p>
                </div>
            </div>
            <div class="shortcut-grid">
                <a href="<?= BASE_URL ?>/backoffice/users.php" class="shortcut-item">
                    <strong>Usuários</strong>
                    <span>CRUD administrativo com senha hash e status ativo.</span>
                </a>
                <a href="<?= BASE_URL ?>/backoffice/permissions.php" class="shortcut-item">
                    <strong>Permissões</strong>
                    <span>Controle por módulo para governança do painel.</span>
                </a>
                <a href="<?= BASE_URL ?>/backoffice/logs.php" class="shortcut-item">
                    <strong>Logs</strong>
                    <span>Auditoria com usuário, ação, data e IP.</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/management.php" class="shortcut-item">
                    <strong>Operação legado</strong>
                    <span>Fluxo atual de vouchers e agenda de premiações.</span>
                </a>
            </div>
        </article>
    </div>
</section>
