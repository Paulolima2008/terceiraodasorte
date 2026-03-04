<?php
declare(strict_types=1);
$groupedPermissions = [];
foreach ($permissions as $permission) {
    $groupedPermissions[$permission['module_name']][] = $permission;
}
?>
<section class="row g-4">
    <div class="col-12 col-xl-4">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Usuário alvo</h2>
                    <p>Selecione o usuário que terá as permissões atualizadas.</p>
                </div>
            </div>
            <form method="get">
                <label class="form-label">Usuário</label>
                <select class="form-select" name="user_id" onchange="this.form.submit()">
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int) $user['id'] ?>" <?= (int) $selectedUserId === (int) $user['id'] ? 'selected' : '' ?>>
                            <?= e((string) $user['name']) ?> (<?= e((string) $user['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </article>
    </div>
    <div class="col-12 col-xl-8">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Matriz de permissões</h2>
                    <p>Controle fino por módulo com persistência no banco.</p>
                </div>
            </div>
            <form method="post" class="row g-3">
                <?= $csrf->input() ?>
                <input type="hidden" name="user_id" value="<?= (int) $selectedUserId ?>">
                <?php foreach ($groupedPermissions as $module => $items): ?>
                    <div class="col-12">
                        <div class="permission-group">
                            <strong><?= e(ucfirst((string) $module)) ?></strong>
                            <div class="permission-checks">
                                <?php foreach ($items as $permission): ?>
                                    <label class="form-check permission-check">
                                        <input class="form-check-input" type="checkbox" name="permission_ids[]" value="<?= (int) $permission['id'] ?>" <?= in_array((int) $permission['id'], $selectedPermissionIds, true) ? 'checked' : '' ?>>
                                        <span class="form-check-label"><?= e((string) $permission['permission_label']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="col-12">
                    <button class="btn btn-danger" type="submit">Salvar permissões</button>
                </div>
            </form>
        </article>
    </div>
</section>
