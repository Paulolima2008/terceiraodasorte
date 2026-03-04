<?php
declare(strict_types=1);
?>
<article class="admin-panel-card">
    <div class="admin-panel-head">
        <div>
            <h2>Logs do sistema</h2>
            <p>Auditoria de segurança com data, usuário, ação e IP.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table js-datatable align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Usuário</th>
                <th>Módulo</th>
                <th>Ação</th>
                <th>IP</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td>#<?= (int) $log['id'] ?></td>
                    <td><?= e((string) $log['created_at']) ?></td>
                    <td><?= e((string) ($log['admin_name'] ?? 'Sistema')) ?></td>
                    <td><?= e((string) $log['module_name']) ?></td>
                    <td><?= e((string) $log['action_name']) ?></td>
                    <td><?= e((string) $log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</article>
