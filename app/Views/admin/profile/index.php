<?php
declare(strict_types=1);
?>
<section class="row g-4">
    <div class="col-12 col-xl-8">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Meu perfil</h2>
                    <p>Atualize seus dados de acesso com segurança.</p>
                </div>
            </div>
            <form method="post" class="row g-3">
                <?= $csrf->input() ?>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Nome</label>
                    <input class="form-control" name="name" value="<?= e((string) ($currentUser['name'] ?? '')) ?>" required>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Email</label>
                    <input class="form-control" type="email" name="email" value="<?= e((string) ($currentUser['email'] ?? '')) ?>" required>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Nova senha</label>
                    <input class="form-control" type="password" name="password" placeholder="Deixe em branco para manter">
                </div>
                <div class="col-12">
                    <button class="btn btn-danger" type="submit">Salvar perfil</button>
                </div>
            </form>
        </article>
    </div>
    <div class="col-12 col-xl-4">
        <article class="admin-panel-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Segurança</h2>
                    <p>Boas práticas para produção.</p>
                </div>
            </div>
            <ul class="security-list">
                <li>Troque a senha inicial após o primeiro login.</li>
                <li>Use um email exclusivo para o backoffice.</li>
                <li>Monitore o módulo de logs periodicamente.</li>
                <li>Mantenha permissões mínimas por usuário.</li>
            </ul>
        </article>
    </div>
</section>
