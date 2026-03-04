<?php
declare(strict_types=1);
?>
<main class="admin-auth-wrap">
    <section class="admin-auth-card">
        <img class="admin-auth-logo" src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= e(APP_NAME) ?>">
        <span class="admin-auth-badge">Painel profissional</span>
        <h1>Acesso administrativo</h1>
        <p>Autenticação com sessão segura, CSRF, rate limit e trilha de auditoria.</p>

        <?php if ($timedOut): ?>
            <div class="alert alert-warning">Sua sessão expirou por inatividade.</div>
        <?php endif; ?>

        <?php if (is_array($flash)): ?>
            <div class="alert alert-<?= e($flash['type'] === 'error' ? 'danger' : 'success') ?>">
                <?= e((string) $flash['message']) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="admin-auth-form">
            <?= $csrf->input() ?>
            <div>
                <label class="form-label" for="email">Email</label>
                <input class="form-control" id="email" name="email" type="email" value="<?= e((string) ADMIN_DEFAULT_EMAIL) ?>" required>
            </div>
            <div>
                <label class="form-label" for="password">Senha</label>
                <input class="form-control" id="password" name="password" type="password" required>
                <small class="text-muted">Senha inicial: use o código atual do admin e troque depois do primeiro acesso.</small>
            </div>
            <button class="btn btn-danger w-100" type="submit">Entrar no painel</button>
        </form>
    </section>
</main>
