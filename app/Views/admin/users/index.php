<?php
declare(strict_types=1);
$isEditing = is_array($editUser);
?>
<section class="row g-4">
    <div class="col-12 col-xl-7">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Usuários administrativos</h2>
                    <p>Gerencie acessos com email único, hash seguro e status ativo.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table js-datatable align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Último login</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?= (int) $user['id'] ?></td>
                            <td><?= e((string) $user['name']) ?></td>
                            <td><?= e((string) $user['email']) ?></td>
                            <td><span class="badge text-bg-<?= (int) $user['is_active'] === 1 ? 'success' : 'secondary' ?>"><?= (int) $user['is_active'] === 1 ? 'Ativo' : 'Inativo' ?></span></td>
                            <td><?= e((string) ($user['last_login_at'] ?? '-')) ?></td>
                            <td class="d-flex gap-2">
                                <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/backoffice/users.php?edit=<?= (int) $user['id'] ?>">Editar</a>
                                <?php if ((int) $user['id'] !== (int) ($currentUser['id'] ?? 0)): ?>
                                    <form method="post">
                                        <?= $csrf->input() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
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
    <div class="col-12 col-xl-5">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2><?= $isEditing ? 'Editar usuário' : 'Novo usuário' ?></h2>
                    <p><?= $isEditing ? 'Atualize dados de acesso e status.' : 'Cadastre um novo usuário administrativo.' ?></p>
                </div>
            </div>
            <form method="post" class="row g-3">
                <?= $csrf->input() ?>
                <input type="hidden" name="action" value="<?= $isEditing ? 'update' : 'create' ?>">
                <?php if ($isEditing): ?>
                    <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
                <?php endif; ?>
                <div class="col-12">
                    <label class="form-label">Nome</label>
                    <input class="form-control" name="name" value="<?= e((string) ($editUser['name'] ?? '')) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Email</label>
                    <input class="form-control" type="email" name="email" value="<?= e((string) ($editUser['email'] ?? '')) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Senha <?= $isEditing ? '(opcional)' : '' ?></label>
                    <input class="form-control" type="password" name="password" <?= $isEditing ? '' : 'required' ?>>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" id="is_active" type="checkbox" name="is_active" <?= !isset($editUser['is_active']) || (int) $editUser['is_active'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Usuário ativo</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-danger" type="submit"><?= $isEditing ? 'Salvar alterações' : 'Criar usuário' ?></button>
                    <?php if ($isEditing): ?>
                        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/backoffice/users.php">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </article>
    </div>
</section>
