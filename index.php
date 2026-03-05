<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

startSession();

$error = null;
$systemNotice = null;
$submittedCode = '';

try {
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $slotValues = $_POST['voucher_slots'] ?? [];
        $normalizedSlots = '';

        if (is_array($slotValues)) {
            foreach ($slotValues as $slotValue) {
                $char = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $slotValue), 0, 1));
                $normalizedSlots .= $char;
            }
        }

        $submittedCode = normalizeCode((string) ($_POST['code'] ?? ''));
        if ($submittedCode === '') {
            $submittedCode = $normalizedSlots;
        }

        if (!isValidCodeFormat($submittedCode)) {
            $error = 'Formato invalido. Informe um codigo com 5 caracteres, 2 letras e 3 numeros.';
        } else {
            $stmt = $pdo->prepare('SELECT id, campaign_id, is_used FROM campaign_vouchers WHERE code = :code LIMIT 1');
            $stmt->execute(['code' => $submittedCode]);
            $row = $stmt->fetch();

            if (!$row) {
                $error = 'Codigo nao encontrado.';
            } elseif ((int) $row['is_used'] === 1) {
                $error = 'Esse codigo ja foi utilizado.';
            } else {
                $_SESSION['validated_code'] = $submittedCode;
                $_SESSION['validated_campaign_id'] = (int) ($row['campaign_id'] ?? 0);
                header('Location: ' . BASE_URL . '/spin.php');
                exit;
            }
        }
    }
} catch (Throwable $t) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) {
        $error = 'Nao foi possivel validar o voucher neste momento.';
    }

    $systemNotice = 'A validacao sera retomada assim que a base de dados estiver disponivel.';
}

$voucherSlots = array_fill(0, 5, '');
for ($i = 0; $i < 5; $i++) {
    $voucherSlots[$i] = substr($submittedCode, $i, 1);
}

$flash = getFlash();
$voucherFeedbackMessage = null;
$voucherFeedbackClass = 'voucher-feedback';

if ($error !== null) {
    $voucherFeedbackMessage = $error;
    $voucherFeedbackClass .= ' is-error';
} elseif ($flash !== null) {
    $voucherFeedbackMessage = (string) ($flash['message'] ?? '');
} elseif ($systemNotice !== null) {
    $voucherFeedbackMessage = $systemNotice;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= APP_NAME ?> - Plataforma oficial</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/home.css">
    <script>
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }
    </script>
</head>
<body class="home-page">
<header class="site-header">
    <div class="site-header-inner">
        <a class="brand-mark" href="#" aria-label="<?= APP_NAME ?>">
            <img class="brand-logo" src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>">
            <span class="brand-copy">
                <strong><?= APP_NAME ?></strong>
                <small>Campanha oficial</small>
            </span>
        </a>

        <nav class="site-nav" aria-label="Navegacao principal">
            <a href="#projeto">O Projeto</a>
            <a href="#regulamento">Regulamento</a>
            <a href="#parceiros">Parceiros</a>
            <a href="#contato">Contato</a>
        </nav>

        <form method="post" class="voucher-form voucher-form-compact" id="voucher-form">
            <input type="hidden" name="code" id="voucher-code" value="<?= e($submittedCode) ?>">
            <div class="voucher-fieldset">
                <span class="voucher-label">Digite seu voucher</span>
                <div class="voucher-slots">
                    <?php foreach ($voucherSlots as $index => $slot): ?>
                        <input
                            class="voucher-slot"
                            type="text"
                            name="voucher_slots[]"
                            value="<?= e($slot) ?>"
                            maxlength="1"
                            inputmode="text"
                            autocomplete="off"
                            aria-label="Caractere <?= $index + 1 ?> do voucher"
                            data-slot
                        >
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" class="primary-button">Validar</button>
        </form>
    </div>
</header>

<main id="top">
    <section class="hero-section">
        <div class="section-shell">
            <div class="hero-slider-card" aria-label="Apresentacao da campanha">
                <div class="hero-slide is-active" data-slide>
                    <div class="hero-slide-visual">
                        <img
                            class="hero-slide-image"
                            src="<?= BASE_URL ?>/assets/img/slide1.jpg"
                            alt="Banner da campanha Terceirão da Sorte"
                        >
                    </div>
                </div>

                <div class="hero-slide" data-slide>
                    <div class="hero-slide-visual">
                        <img
                            class="hero-slide-image"
                            src="<?= BASE_URL ?>/assets/img/slide2.jpg"
                            alt="Banner promocional da campanha Terceirão da Sorte"
                        >
                    </div>
                </div>

                <div class="hero-slider-controls">
                    <button type="button" class="hero-dot is-active" aria-label="Slide 1" data-slide-to="0"></button>
                    <button type="button" class="hero-dot" aria-label="Slide 2" data-slide-to="1"></button>
                </div>
            </div>

        </div>
    </section>

    <section class="content-section" id="projeto">
        <div class="section-shell">
            <div class="project-showcase">
                <article class="project-intro-card">
                    <span class="eyebrow">O Projeto</span>
                    <h2>Terceirão da Sorte</h2>
                    <p>O Terceirão da Sorte é um projeto criado pelos alunos do terceiro ano com o objetivo de arrecadar recursos para ajudar a custear a formatura da turma.</p>
                    <p>Cada participação fortalece a campanha e contribui para transformar o encerramento dessa etapa escolar em uma conquista coletiva.</p>
                    <p class="project-cta">Compre seu voucher, gire a roleta e participe.</p>
                    <a class="project-inline-link" href="#top">Participar agora</a>
                </article>

                <div class="project-feature-stack">
                    <article class="project-feature-card">
                        <span class="project-feature-index">01</span>
                        <strong>Projeto dos alunos</strong>
                        <p>Uma campanha organizada pelo terceirão para arrecadar recursos e aproximar a turma da realização da formatura.</p>
                    </article>
                    <article class="project-feature-card">
                        <span class="project-feature-index">02</span>
                        <strong>Conquista coletiva</strong>
                        <p>Cada voucher vendido fortalece a ação e ajuda a transformar esse encerramento em um momento especial para todos.</p>
                    </article>
                </div>
            </div>

            <div class="section-heading how-it-works-heading">
                <span class="eyebrow">Como funciona</span>
            </div>

            <div class="steps-grid">
                <article class="step-card">
                    <div class="step-icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48" class="step-icon-svg">
                            <rect x="8" y="14" width="32" height="22" rx="6"></rect>
                            <path d="M14 20h20M14 26h12"></path>
                        </svg>
                    </div>
                    <strong>1. Compre o voucher</strong>
                    <span>Adquira seu Voucher da Sorte com um dos alunos participantes.</span>
                </article>
                <article class="step-card">
                    <div class="step-icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48" class="step-icon-svg">
                            <rect x="10" y="10" width="28" height="28" rx="6"></rect>
                            <path d="M17 18h14M17 24h14M17 30h8"></path>
                        </svg>
                    </div>
                    <strong>2. Receba seu codigo</strong>
                    <span>Cada voucher possui um codigo exclusivo.</span>
                </article>
                <article class="step-card">
                    <div class="step-icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48" class="step-icon-svg">
                            <rect x="12" y="8" width="24" height="32" rx="6"></rect>
                            <path d="M19 16h10M17 23h14M17 30h9"></path>
                        </svg>
                    </div>
                    <strong>3. Acesse o site</strong>
                    <span>Digite o codigo na pagina da roleta.</span>
                </article>
                <article class="step-card">
                    <div class="step-icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48" class="step-icon-svg">
                            <circle cx="24" cy="24" r="14"></circle>
                            <circle cx="24" cy="24" r="3"></circle>
                            <path d="M24 10v28M10 24h28M14 14l20 20M34 14 14 34"></path>
                        </svg>
                    </div>
                    <strong>4. Gire a roleta</strong>
                    <span>Descubra seu premio na hora.</span>
                </article>
            </div>
        </div>
    </section>

    <section class="content-section" id="regulamento">
        <div class="section-shell">
            <div class="section-heading">
                <span class="eyebrow">Regulamento</span>
                <h2>Regulamento da campanha</h2>
                <p>Consulte o documento completo com as regras, orientacoes e condicoes de participacao.</p>
            </div>

            <div class="regulation-download">
                <p>Baixe o regulamento completo para conferir todos os detalhes da campanha.</p>
                <a class="download-button is-disabled" href="#" aria-disabled="true">Baixar regulamento completo</a>
            </div>
        </div>
    </section>

    <section class="content-section" id="parceiros">
        <div class="section-shell">
            <div class="section-heading">
                <span class="eyebrow">Parceiros</span>
                <h2>Quem Apoia o Projeto</h2>
                <p>Instituicoes e apoiadores que fortalecem o Terceirao da Sorte e ajudam a impulsionar a campanha.</p>
            </div>

            <div class="partners-carousel" aria-label="Carrossel de logos dos parceiros">
                <div class="partners-track">
                    <article class="partner-logo-card">
                        <img class="partner-logo-image" src="<?= BASE_URL ?>/assets/img/parceiro-franciscano.jpg" alt="Colégio Franciscano Santa Maria dos Anjos">
                    </article>
                    <article class="partner-logo-card">
                        <img class="partner-logo-image" src="<?= BASE_URL ?>/assets/img/parceiro-franciscano.jpg" alt="Colégio Franciscano Santa Maria dos Anjos">
                    </article>
                    <article class="partner-logo-card" aria-hidden="true">
                        <img class="partner-logo-image" src="<?= BASE_URL ?>/assets/img/parceiro-franciscano.jpg" alt="">
                    </article>
                    <article class="partner-logo-card" aria-hidden="true">
                        <img class="partner-logo-image" src="<?= BASE_URL ?>/assets/img/parceiro-franciscano.jpg" alt="">
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="content-section" id="contato">
        <div class="section-shell">
            <div class="section-heading">
                <span class="eyebrow">Contato</span>
                <h2>Canais oficiais da campanha.</h2>
                <p>Em caso de dúvida, utilize os canais oficiais do projeto para acompanhar novidades e obter atendimento.</p>
            </div>

            <div class="contact-showcase">
                <article class="contact-main-card">
                    <span class="contact-label">Atendimento da campanha</span>
                    <h3>Fale com a equipe oficial do projeto</h3>
                    <p>Procure a equipe oficial no ponto de atendimento divulgado pela organização do evento.</p>
                </article>

                <div class="contact-social-stack">
                    <article class="contact-social-card">
                        <div class="contact-social-icon is-instagram" aria-hidden="true">
                            <img class="contact-social-image" src="<?= BASE_URL ?>/assets/img/instagram-original.svg" alt="">
                        </div>
                        <div class="contact-social-body">
                            <strong>Instagram</strong>
                            <p>Espaço reservado para adicionar o perfil oficial da campanha.</p>
                        </div>
                    </article>

                    <article class="contact-social-card">
                        <div class="contact-social-icon is-whatsapp" aria-hidden="true">
                            <img class="contact-social-image" src="<?= BASE_URL ?>/assets/img/whatsapp-original.svg" alt="">
                        </div>
                        <div class="contact-social-body">
                            <strong>WhatsApp</strong>
                            <p>Espaço reservado para adicionar o número oficial de atendimento.</p>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
(() => {
    const voucherForm = document.getElementById('voucher-form');
    const hiddenCode = document.getElementById('voucher-code');
    const slotInputs = voucherForm ? Array.from(voucherForm.querySelectorAll('[data-slot]')) : [];
    const brandMark = document.querySelector('.brand-mark');
    const voucherFeedbackMessage = <?= json_encode($voucherFeedbackMessage, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const voucherFeedbackClass = <?= json_encode($voucherFeedbackClass, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    const forceTop = () => {
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;
        window.scrollTo(0, 0);
    };

    forceTop();
    window.requestAnimationFrame(forceTop);
    window.setTimeout(forceTop, 0);

    if (brandMark) {
        brandMark.addEventListener('click', (event) => {
            event.preventDefault();
            forceTop();
            window.requestAnimationFrame(forceTop);
        });
    }

    const syncVoucherCode = () => {
        if (!hiddenCode) {
            return;
        }

        hiddenCode.value = slotInputs.map((input) => input.value.toUpperCase()).join('');
    };

    slotInputs.forEach((input, index) => {
        input.addEventListener('input', (event) => {
            const sanitized = event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 1);
            event.target.value = sanitized;
            syncVoucherCode();

            if (sanitized !== '' && index < slotInputs.length - 1) {
                slotInputs[index + 1].focus();
                slotInputs[index + 1].select();
            }
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && event.currentTarget.value === '' && index > 0) {
                slotInputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', (event) => {
            event.preventDefault();
            const pasted = (event.clipboardData || window.clipboardData).getData('text');
            const sanitized = pasted.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, slotInputs.length);

            sanitized.split('').forEach((char, pasteIndex) => {
                if (slotInputs[pasteIndex]) {
                    slotInputs[pasteIndex].value = char;
                }
            });

            for (let clearIndex = sanitized.length; clearIndex < slotInputs.length; clearIndex++) {
                slotInputs[clearIndex].value = '';
            }

            syncVoucherCode();

            const focusIndex = Math.min(sanitized.length, slotInputs.length - 1);
            if (slotInputs[focusIndex]) {
                slotInputs[focusIndex].focus();
            }
        });
    });

    if (voucherForm) {
        voucherForm.addEventListener('submit', syncVoucherCode);
        syncVoucherCode();
    }

    const slides = Array.from(document.querySelectorAll('[data-slide]'));
    const dots = Array.from(document.querySelectorAll('[data-slide-to]'));
    let activeSlide = 0;
    let sliderTimer = null;

    const renderSlide = (index) => {
        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle('is-active', slideIndex === index);
        });

        dots.forEach((dot, dotIndex) => {
            dot.classList.toggle('is-active', dotIndex === index);
        });

        activeSlide = index;
    };

    const startSlider = () => {
        if (slides.length < 2) {
            return;
        }

        sliderTimer = window.setInterval(() => {
            renderSlide((activeSlide + 1) % slides.length);
        }, 4800);
    };

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            const index = Number(dot.getAttribute('data-slide-to') || 0);
            renderSlide(index);
            if (sliderTimer) {
                window.clearInterval(sliderTimer);
            }
            startSlider();
        });
    });

    renderSlide(activeSlide);
    startSlider();

    if (voucherFeedbackMessage) {
        const voucherToastWrap = document.createElement('div');
        voucherToastWrap.className = 'voucher-toast-wrap';

        const voucherToast = document.createElement('div');
        voucherToast.className = `${voucherFeedbackClass} voucher-toast`;
        voucherToast.setAttribute('role', 'status');
        voucherToast.setAttribute('aria-live', 'polite');
        voucherToast.textContent = voucherFeedbackMessage;

        voucherToastWrap.appendChild(voucherToast);
        document.body.appendChild(voucherToastWrap);

        window.setTimeout(() => {
            voucherToast.classList.add('is-hidden');
            window.setTimeout(() => {
                voucherToastWrap.remove();
            }, 400);
        }, 10000);
    }

    window.addEventListener('pageshow', forceTop);
})();
</script>
</body>
</html>
