<?php
declare(strict_types=1);

$rows = is_array($rows ?? null) ? $rows : [];
$campaignId = (int) ($campaignId ?? 0);
$campaign = is_array($campaign ?? null) ? $campaign : null;
?>
<section class="row g-4">
    <div class="col-12">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Gestao de pagamentos de premios</h2>
                    <p>Controle de status pago/nao pago<?= $campaign !== null ? ' da campanha ' . e((string) $campaign['name']) : '' ?>.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table js-datatable align-middle">
                    <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Posicao</th>
                        <th>Premio</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Pago em</th>
                        <th>Acao</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= e((string) $row['code']) ?></td>
                            <td>#<?= (int) $row['spin_position'] ?></td>
                            <td><?= e((string) $row['result_label']) ?></td>
                            <td>R$ <?= number_format((float) ($row['amount'] ?? 0), 2, ',', '.') ?></td>
                            <td><?= (int) $row['is_paid'] === 1 ? 'Pago' : 'Pendente' ?></td>
                            <td><?= e((string) ($row['paid_at'] ?? '-')) ?></td>
                            <td>
                                <?php if ((int) $row['is_paid'] === 0): ?>
                                    <form method="post">
                                        <?= $csrf->input() ?>
                                        <input type="hidden" name="campaign_id" value="<?= $campaignId ?>">
                                        <input type="hidden" name="spin_id" value="<?= (int) $row['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Marcar pago</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Concluido</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </div>
</section>
