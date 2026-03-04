<?php
declare(strict_types=1);
?>
<article class="admin-panel-card">
    <div class="admin-panel-head">
        <div>
            <h2>Configurações do painel</h2>
            <p>Parâmetros centrais do backoffice e canais institucionais.</p>
        </div>
    </div>
    <form method="post" class="row g-3">
        <?= $csrf->input() ?>
        <div class="col-12 col-lg-6">
            <label class="form-label">Nome do site</label>
            <input class="form-control" name="site_name" value="<?= e((string) ($settings['site_name'] ?? APP_NAME)) ?>">
        </div>
        <div class="col-12 col-lg-6">
            <label class="form-label">Instagram</label>
            <input class="form-control" name="instagram_url" value="<?= e((string) ($settings['instagram_url'] ?? '')) ?>" placeholder="https://instagram.com/...">
        </div>
        <div class="col-12 col-lg-6">
            <label class="form-label">WhatsApp</label>
            <input class="form-control" name="whatsapp_number" value="<?= e((string) ($settings['whatsapp_number'] ?? '')) ?>" placeholder="+55 00 00000-0000">
        </div>
        <div class="col-12">
            <label class="form-label">Mensagem de suporte</label>
            <textarea class="form-control" name="support_message" rows="4"><?= e((string) ($settings['support_message'] ?? '')) ?></textarea>
        </div>
        <div class="col-12">
            <button class="btn btn-danger" type="submit">Salvar configurações</button>
        </div>
    </form>
</article>
