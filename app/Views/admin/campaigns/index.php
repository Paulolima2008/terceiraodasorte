<?php
declare(strict_types=1);

$campaigns = is_array($campaigns ?? null) ? $campaigns : [];
$createFormData = is_array($createFormData ?? null) ? $createFormData : [];
$createModalOpen = !empty($createModalOpen);
$scheduleFormData = is_array($scheduleFormData ?? null) ? $scheduleFormData : [];
$scheduleCampaign = is_array($scheduleCampaign ?? null) ? $scheduleCampaign : null;
$schedulePrizes = is_array($schedulePrizes ?? null) ? $schedulePrizes : [];
$scheduleRows = is_array($scheduleRows ?? null) ? $scheduleRows : [];
$scheduleModalOpen = !empty($scheduleModalOpen);
?>
<section class="row g-4">
    <div class="col-12">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Campanhas cadastradas</h2>
                    <p>Cada campanha possui seus vouchers, premios e agenda.</p>
                </div>
                <button class="btn btn-danger" type="button" data-bs-toggle="modal" data-bs-target="#createCampaignModal">
                    Nova campanha
                </button>
            </div>
            <div class="table-responsive">
                <table class="table js-datatable align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Modo</th>
                        <th>Vouchers</th>
                        <th>Premios</th>
                        <th>Status</th>
                        <th>Acoes</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($campaigns as $campaign): ?>
                        <tr>
                            <td>#<?= (int) $campaign['id'] ?></td>
                            <td><?= e((string) $campaign['name']) ?></td>
                            <td><?= e((string) $campaign['award_mode']) ?></td>
                            <td><?= (int) $campaign['vouchers_generated'] ?>/<?= (int) $campaign['voucher_quantity'] ?></td>
                            <td><?= (int) $campaign['prizes_generated'] ?>/<?= (int) $campaign['prize_quantity'] ?></td>
                            <td>
                                <span class="badge text-bg-<?= (int) $campaign['is_active'] === 1 ? 'success' : 'secondary' ?>">
                                    <?= (int) $campaign['is_active'] === 1 ? 'Ativa' : 'Inativa' ?>
                                </span>
                            </td>
                            <td class="d-flex gap-2 flex-wrap">
                                <?php if ((string) $campaign['award_mode'] === 'manual'): ?>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/backoffice/campaigns.php?schedule=<?= (int) $campaign['id'] ?>">Agenda manual</a>
                                <?php endif; ?>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/backoffice/vouchers.php?campaign_id=<?= (int) $campaign['id'] ?>">Vouchers</a>
                                <?php if ((int) $campaign['is_active'] !== 1): ?>
                                    <form method="post">
                                        <?= $csrf->input() ?>
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                                        <button class="btn btn-sm btn-outline-success" type="submit">Ativar</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ((string) $campaign['award_mode'] === 'automatic'): ?>
                                    <form method="post">
                                        <?= $csrf->input() ?>
                                        <input type="hidden" name="action" value="auto_schedule">
                                        <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Auto agenda</button>
                                    </form>
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

<div class="modal fade admin-modal" id="createCampaignModal" tabindex="-1" aria-labelledby="createCampaignModalLabel" aria-hidden="true" data-open-on-load="<?= $createModalOpen ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header admin-modal-header">
                <div class="admin-modal-copy">
                    <span class="admin-auth-badge">Cadastro</span>
                    <h2 id="createCampaignModalLabel">Nova campanha</h2>
                    <p>Defina vouchers e premiacao manual ou automatica sem sair da listagem.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form method="post" class="row g-3">
                    <?= $csrf->input() ?>
                    <input type="hidden" name="action" value="create">

                    <div class="col-12">
                        <label class="form-label" for="create_campaign_name">Nome da campanha</label>
                        <input class="form-control" id="create_campaign_name" name="name" value="<?= e((string) ($createFormData['name'] ?? '')) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="create_campaign_voucher_quantity">Qtd. vouchers</label>
                        <input class="form-control" id="create_campaign_voucher_quantity" type="number" min="1" name="voucher_quantity" value="<?= e((string) ($createFormData['voucher_quantity'] ?? '')) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="create_campaign_voucher_unit_value">Valor unitario voucher (R$)</label>
                        <input class="form-control" id="create_campaign_voucher_unit_value" type="number" min="0" step="0.01" name="voucher_unit_value" value="<?= e((string) ($createFormData['voucher_unit_value'] ?? '0.00')) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="create_campaign_prize_quantity">Qtd. premios</label>
                        <input class="form-control" id="create_campaign_prize_quantity" type="number" min="1" name="prize_quantity" value="<?= e((string) ($createFormData['prize_quantity'] ?? '')) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="create_campaign_prize_total_value">Valor total premios (R$)</label>
                        <input class="form-control" id="create_campaign_prize_total_value" type="number" min="0.01" step="0.01" name="prize_total_value" value="<?= e((string) ($createFormData['prize_total_value'] ?? '')) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="create_campaign_award_mode">Forma de premiacao</label>
                        <select class="form-select" id="create_campaign_award_mode" name="award_mode" required>
                            <option value="manual" <?= ((string) ($createFormData['award_mode'] ?? 'manual')) === 'manual' ? 'selected' : '' ?>>Manual</option>
                            <option value="automatic" <?= ((string) ($createFormData['award_mode'] ?? 'manual')) === 'automatic' ? 'selected' : '' ?>>Automatica</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" id="create_campaign_is_active" type="checkbox" name="is_active" <?= !isset($createFormData['is_active']) || (int) $createFormData['is_active'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="create_campaign_is_active">Ativar apos criar</label>
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-danger" type="submit">Criar campanha</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade admin-modal" id="scheduleCampaignModal" tabindex="-1" aria-labelledby="scheduleCampaignModalLabel" aria-hidden="true" data-open-on-load="<?= $scheduleModalOpen ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header admin-modal-header">
                <div class="admin-modal-copy">
                    <span class="admin-auth-badge">Agenda manual</span>
                    <h2 id="scheduleCampaignModalLabel">Agendamento de premiacao</h2>
                    <p>
                        <?php if ($scheduleCampaign !== null): ?>
                            Campanha: <?= e((string) $scheduleCampaign['name']) ?>
                        <?php else: ?>
                            Selecione uma campanha manual para agendar.
                        <?php endif; ?>
                    </p>
                </div>
                <a class="btn-close" href="<?= BASE_URL ?>/backoffice/campaigns.php" aria-label="Fechar"></a>
            </div>
            <div class="modal-body">
                <?php if ($scheduleCampaign !== null && (string) ($scheduleCampaign['award_mode'] ?? '') === 'manual'): ?>
                    <form method="post" class="row g-2 mb-4">
                        <?= $csrf->input() ?>
                        <input type="hidden" name="action" value="schedule_add">
                        <input type="hidden" name="campaign_id" value="<?= (int) $scheduleCampaign['id'] ?>">
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="schedule_spin_position">Posicao</label>
                            <input id="schedule_spin_position" class="form-control" type="number" min="1" max="<?= (int) ($scheduleCampaign['voucher_quantity'] ?? 1) ?>" name="spin_position" value="<?= e((string) ($scheduleFormData['spin_position'] ?? '')) ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="schedule_prize_id">Premio</label>
                            <select id="schedule_prize_id" class="form-select" name="prize_id" required>
                                <option value="">Selecione</option>
                                <?php foreach ($schedulePrizes as $prize): ?>
                                    <option value="<?= (int) $prize['id'] ?>" <?= (int) ($scheduleFormData['prize_id'] ?? 0) === (int) $prize['id'] ? 'selected' : '' ?>>
                                        <?= e((string) $prize['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-3 d-flex align-items-end">
                            <button class="btn btn-danger w-100" type="submit">Adicionar</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
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
                                        <form method="post">
                                            <?= $csrf->input() ?>
                                            <input type="hidden" name="action" value="schedule_remove">
                                            <input type="hidden" name="campaign_id" value="<?= (int) $scheduleCampaign['id'] ?>">
                                            <input type="hidden" name="schedule_id" value="<?= (int) $row['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">Campanha invalida para agenda manual.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
