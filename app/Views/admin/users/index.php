<?php
declare(strict_types=1);

$isEditing = is_array($editUser);
$createFormData = is_array($createFormData ?? null) ? $createFormData : [];
$createModalOpen = !empty($createModalOpen);
$canManagePermissions = !empty($canManagePermissions);
$permissions = is_array($permissions ?? null) ? $permissions : [];
$permissionUser = is_array($permissionUser ?? null) ? $permissionUser : null;
$permissionModalOpen = $canManagePermissions && !empty($permissionModalOpen) && $permissionUser !== null;
$permissionUserIds = array_map('intval', is_array($permissionUserIds ?? null) ? $permissionUserIds : []);

$groupedPermissions = [];
foreach ($permissions as $permission) {
    $module = (string) ($permission['module_name'] ?? 'general');
    $groupedPermissions[$module][] = $permission;
}

$moduleNamesPt = [
    'dashboard' => 'Dashboard',
    'operations' => 'Operacoes da roleta',
    'users' => 'Usuarios',
    'permissions' => 'Permissoes',
    'logs' => 'Logs',
    'settings' => 'Configuracoes',
    'general' => 'Geral',
];

$permissionNamesPt = [
    'dashboard.view' => 'Visualizar dashboard',
    'operations.view' => 'Visualizar operacoes da roleta',
    'operations.manage' => 'Gerenciar operacoes da roleta',
    'users.view' => 'Visualizar usuarios',
    'users.manage' => 'Gerenciar usuarios',
    'permissions.view' => 'Visualizar permissoes',
    'permissions.manage' => 'Gerenciar permissoes',
    'logs.view' => 'Visualizar logs',
    'settings.manage' => 'Gerenciar configuracoes',
];
?>
<section class="row g-4">
    <div class="<?= $isEditing ? 'col-12 col-xxl-8' : 'col-12' ?>">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Usuarios administrativos</h2>
                    <p>Gerencie acessos com email unico, hash seguro e status ativo.</p>
                </div>
                <button class="btn btn-danger" type="button" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    Novo usuario
                </button>
            </div>
            <div class="table-responsive">
                <table class="table js-datatable align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Ultimo login</th>
                        <th>Acoes</th>
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
                            <td class="d-flex gap-2 flex-wrap">
                                <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/backoffice/users.php?edit=<?= (int) $user['id'] ?>">Editar</a>
                                <?php if ($canManagePermissions): ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/backoffice/users.php?permissions=<?= (int) $user['id'] ?>">Permissoes</a>
                                <?php endif; ?>
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
    <?php if ($isEditing): ?>
        <div class="col-12 col-xxl-4">
            <article class="admin-panel-card">
                <div class="admin-panel-head">
                    <div>
                        <h2>Editar usuario</h2>
                        <p>Atualize dados de acesso e status.</p>
                    </div>
                </div>
                <form method="post" class="row g-3">
                    <?= $csrf->input() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
                    <div class="col-12">
                        <label class="form-label" for="edit_user_name">Nome</label>
                        <input class="form-control" id="edit_user_name" name="name" value="<?= e((string) ($editUser['name'] ?? '')) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="edit_user_email">Email</label>
                        <input class="form-control" id="edit_user_email" type="email" name="email" value="<?= e((string) ($editUser['email'] ?? '')) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="edit_user_password">Senha (opcional)</label>
                        <input class="form-control" id="edit_user_password" type="password" name="password">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" id="edit_is_active" type="checkbox" name="is_active" <?= !isset($editUser['is_active']) || (int) $editUser['is_active'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="edit_is_active">Usuario ativo</label>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-danger" type="submit">Salvar alteracoes</button>
                        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/backoffice/users.php">Cancelar</a>
                    </div>
                </form>
            </article>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade admin-modal" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true" data-open-on-load="<?= $createModalOpen ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header admin-modal-header">
                <div class="admin-modal-copy">
                    <span class="admin-auth-badge">Cadastro</span>
                    <h2 id="createUserModalLabel">Novo usuario</h2>
                    <p>Cadastre um novo usuario administrativo sem sair da listagem.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form method="post" class="row g-3">
                    <?= $csrf->input() ?>
                    <input type="hidden" name="action" value="create">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="create_user_name">Nome</label>
                        <input class="form-control" id="create_user_name" name="name" value="<?= e((string) ($createFormData['name'] ?? '')) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="create_user_email">Email</label>
                        <input class="form-control" id="create_user_email" type="email" name="email" value="<?= e((string) ($createFormData['email'] ?? '')) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="create_user_password">Senha</label>
                        <input class="form-control" id="create_user_password" type="password" name="password" required>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" id="create_is_active" type="checkbox" name="is_active" <?= !isset($createFormData['is_active']) || (int) $createFormData['is_active'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="create_is_active">Usuario ativo</label>
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-danger" type="submit">Criar usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($canManagePermissions): ?>
<div class="modal fade admin-modal" id="permissionsModal" tabindex="-1" aria-labelledby="permissionsModalLabel" aria-hidden="true" data-open-on-load="<?= $permissionModalOpen ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header admin-modal-header">
                <div class="admin-modal-copy">
                    <span class="admin-auth-badge">Atribuicao</span>
                    <h2 id="permissionsModalLabel">Permissoes do usuario</h2>
                    <p>
                        <?= $permissionUser !== null
                            ? 'Defina as permissoes para ' . e((string) $permissionUser['name']) . '.'
                            : 'Selecione um usuario para configurar permissoes.' ?>
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <?php if ($permissionUser !== null): ?>
                    <form method="post" class="row g-3">
                        <?= $csrf->input() ?>
                        <input type="hidden" name="action" value="permissions_update">
                        <input type="hidden" name="user_id" value="<?= (int) $permissionUser['id'] ?>">

                        <div class="col-12">
                            <div class="alert alert-light border mb-0">
                                <strong>Usuario:</strong> <?= e((string) $permissionUser['name']) ?><br>
                                <strong>Email:</strong> <?= e((string) $permissionUser['email']) ?>
                            </div>
                        </div>

                        <?php foreach ($groupedPermissions as $module => $items): ?>
                            <div class="col-12">
                                <div class="permission-group">
                                    <strong><?= e((string) ($moduleNamesPt[(string) $module] ?? ucfirst((string) $module))) ?></strong>
                                    <div class="permission-checks">
                                        <?php foreach ($items as $permission): ?>
                                            <?php $permissionKey = (string) ($permission['permission_key'] ?? ''); ?>
                                            <label class="form-check permission-check">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    name="permission_ids[]"
                                                    value="<?= (int) $permission['id'] ?>"
                                                    <?= in_array((int) $permission['id'], $permissionUserIds, true) ? 'checked' : '' ?>
                                                >
                                                <span class="form-check-label"><?= e((string) ($permissionNamesPt[$permissionKey] ?? $permission['permission_label'])) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="col-12 d-flex justify-content-end gap-2">
                            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/backoffice/users.php">Cancelar</a>
                            <button class="btn btn-danger" type="submit">Salvar permissoes</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">Usuario nao encontrado para atribuicao de permissoes.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
