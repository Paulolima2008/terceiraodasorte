<?php
declare(strict_types=1);

$campaigns = is_array($campaigns ?? null) ? $campaigns : [];
$campaignId = (int) ($campaignId ?? 0);
$campaign = is_array($campaign ?? null) ? $campaign : null;
$summary = is_array($summary ?? null) ? $summary : [];
$prizes = is_array($prizes ?? null) ? $prizes : [];
$scheduleRows = is_array($scheduleRows ?? null) ? $scheduleRows : [];
$searchCode = (string) ($searchCode ?? '');
$codeDetails = is_array($codeDetails ?? null) ? $codeDetails : null;
?>
<section class="row g-4 mb-2">
    <div class="col-12">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Campanha da operacao</h2>
                    <p>Selecione a campanha para consultar codigo, agenda e premio.</p>
                </div>
            </div>
            <form method="get" class="row g-2">
                <div class="col-12 col-lg-8">
                    <label class="form-label" for="campaign_id">Campanha</label>
                    <select class="form-select" id="campaign_id" name="campaign_id">
                        <?php foreach ($campaigns as $item): ?>
                            <option value="<?= (int) $item['id'] ?>" <?= (int) $item['id'] === $campaignId ? 'selected' : '' ?>>
                                <?= e((string) $item['name']) ?> (<?= e((string) $item['award_mode']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-4 d-flex align-items-end">
                    <button class="btn btn-danger w-100" type="submit">Aplicar campanha</button>
                </div>
            </form>
        </article>
    </div>
</section>

<?php if ($campaign !== null): ?>
<section class="row g-4 mb-2">
    <div class="col-12 col-md-6 col-xl-3">
        <article class="metric-card">
            <span>Vouchers gerados</span>
            <strong><?= (int) ($summary['total_codes'] ?? 0) ?></strong>
        </article>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <article class="metric-card">
            <span>Vouchers usados</span>
            <strong><?= (int) ($summary['used_codes'] ?? 0) ?></strong>
        </article>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <article class="metric-card">
            <span>Premios previstos</span>
            <strong><?= (int) ($campaign['prize_quantity'] ?? 0) ?></strong>
        </article>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <article class="metric-card">
            <span>Premios agendados</span>
            <strong><?= (int) ($summary['scheduled_count'] ?? 0) ?></strong>
        </article>
    </div>
</section>

<section class="row g-4">
    <div class="col-12 col-xl-5">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Consultar codigo e premio</h2>
                    <p>Modo de premiacao: <strong><?= e((string) ($campaign['award_mode'] ?? 'manual')) ?></strong></p>
                </div>
            </div>
            <form method="get" class="row g-2 mb-3">
                <input type="hidden" name="campaign_id" value="<?= $campaignId ?>">
                <div class="col-8">
                    <label class="form-label" for="search_code">Codigo</label>
                    <input id="search_code" class="form-control text-uppercase" type="text" name="code" maxlength="5" value="<?= e($searchCode) ?>" placeholder="Ex: A1B23">
                </div>
                <div class="col-4 d-flex align-items-end">
                    <button class="btn btn-danger w-100" type="submit">Consultar</button>
                </div>
            </form>

            <?php if ($searchCode !== ''): ?>
                <?php if ($codeDetails === null): ?>
                    <div class="alert alert-warning mb-0">Codigo nao encontrado nesta campanha.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <tbody>
                            <tr><th style="width: 40%">Codigo</th><td><?= e((string) $codeDetails['code']) ?></td></tr>
                            <tr><th>Usado</th><td><?= (int) $codeDetails['is_used'] === 1 ? 'Sim' : 'Nao' ?></td></tr>
                            <tr><th>Resultado</th><td><?= e((string) ($codeDetails['result_label'] ?? '-')) ?></td></tr>
                            <tr><th>Posicao do giro</th><td><?= e((string) ($codeDetails['spin_position'] ?? '-')) ?></td></tr>
                            <tr><th>Pago</th><td><?= (int) ($codeDetails['is_paid'] ?? 0) === 1 ? 'Sim' : 'Nao' ?></td></tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if ((int) ($codeDetails['is_winner'] ?? 0) === 1 && (int) ($codeDetails['is_paid'] ?? 0) === 0): ?>
                        <form method="post" class="mt-3">
                            <?= $csrf->input() ?>
                            <input type="hidden" name="campaign_id" value="<?= $campaignId ?>">
                            <input type="hidden" name="action" value="mark_paid">
                            <input type="hidden" name="spin_id" value="<?= (int) $codeDetails['spin_id'] ?>">
                            <input type="hidden" name="return_code" value="<?= e((string) $codeDetails['code']) ?>">
                            <button class="btn btn-outline-danger" type="submit">Marcar premio pago</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </article>
    </div>

    <div class="col-12 col-xl-7">
        <article class="admin-panel-card" id="agenda-premios">
            <div class="admin-panel-head">
                <div>
                    <h2>Agenda de premiacao</h2>
                    <p>
                        <?php if ((string) ($campaign['award_mode'] ?? '') === 'automatic'): ?>
                            Campanha automatica: gere a agenda automaticamente.
                        <?php else: ?>
                            Campanha manual: informe a posicao e o premio.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if ((string) ($campaign['award_mode'] ?? '') === 'automatic'): ?>
                <form method="post" class="mb-3">
                    <?= $csrf->input() ?>
                    <input type="hidden" name="campaign_id" value="<?= $campaignId ?>">
                    <input type="hidden" name="action" value="auto_schedule">
                    <button class="btn btn-danger" type="submit">Gerar agenda automatica</button>
                </form>
            <?php else: ?>
                <form method="post" class="row g-2 mb-4">
                    <?= $csrf->input() ?>
                    <input type="hidden" name="campaign_id" value="<?= $campaignId ?>">
                    <input type="hidden" name="action" value="schedule_add">
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="spin_position">Posicao</label>
                        <input id="spin_position" class="form-control" type="number" min="1" max="<?= (int) ($campaign['voucher_quantity'] ?? 1) ?>" name="spin_position" required>
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label" for="prize_id">Premio</label>
                        <select id="prize_id" class="form-select" name="prize_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($prizes as $prize): ?>
                                <option value="<?= (int) $prize['id'] ?>"><?= e((string) $prize['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex align-items-end">
                        <button class="btn btn-danger w-100" type="submit">Adicionar</button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table js-datatable align-middle">
                    <thead>
                    <tr>
                        <th>Posicao</th>
                        <th>Premio</th>
                        <th>Valor</th>
                        <th>Acao</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($scheduleRows as $row): ?>
                        <tr>
                            <td>#<?= (int) $row['spin_position'] ?></td>
                            <td><?= e((string) $row['name']) ?></td>
                            <td>R$ <?= number_format((float) ($row['amount'] ?? 0), 2, ',', '.') ?></td>
                            <td>
                                <?php if ((string) ($campaign['award_mode'] ?? '') === 'manual'): ?>
                                    <form method="post">
                                        <?= $csrf->input() ?>
                                        <input type="hidden" name="campaign_id" value="<?= $campaignId ?>">
                                        <input type="hidden" name="action" value="schedule_remove">
                                        <input type="hidden" name="schedule_id" value="<?= (int) $row['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Remover</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Auto</span>
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
<?php endif; ?>
