<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

startSession();

if (empty($_SESSION['validated_code'])) {
    setFlash('error', 'Valide um codigo antes de acessar a roleta.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$code = $_SESSION['validated_code'];
$segments = prizeSegments();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= APP_NAME ?> - Roleta</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body class="roulette-page">
<main class="page">
    <section class="card card-roulette">
        <div class="roulette-layout">
            <div class="roulette-wrap">
                <div class="roulette-glow"></div>
                <div class="pointer" id="pointer"></div>
                <div class="roulette-ring">
                    <div class="wheel-dots" id="wheelDots"></div>
                    <div class="roulette" id="roulette"></div>
                    <div class="center-cap">
                        <img class="center-logo" src="<?= BASE_URL ?>/assets/img/logo.png" alt="<?= APP_NAME ?>">
                    </div>
                </div>
            </div>

            <div class="roulette-side">
                <h1><?= APP_NAME ?></h1>
                <p class="subtitle">Codigo validado: <strong><?= e($code) ?></strong></p>
                <button id="spinBtn" class="spin-btn">Girar Roleta</button>
                <p id="statusText" class="status-text">Pronto para girar.</p>
                <a class="link" href="<?= BASE_URL ?>/index.php">Voltar</a>
            </div>
        </div>
    </section>
</main>

<section id="winOverlay" class="win-overlay" aria-hidden="true">
    <div class="win-panel">
        <div class="win-badge">Premio confirmado</div>
        <h2 id="winTitle">Parabens!</h2>
        <p>Seu giro foi registrado com sucesso.</p>
        <button id="closeWinOverlay" type="button">Continuar</button>
    </div>
    <div class="confetti-layer" id="confettiLayer" aria-hidden="true"></div>
</section>

<script>
const segments = <?= json_encode($segments, JSON_UNESCAPED_UNICODE) ?>;
const roulette = document.getElementById('roulette');
const spinBtn = document.getElementById('spinBtn');
const pointer = document.getElementById('pointer');
const wheelDots = document.getElementById('wheelDots');
const statusText = document.getElementById('statusText');
const winOverlay = document.getElementById('winOverlay');
const winTitle = document.getElementById('winTitle');
const closeWinOverlay = document.getElementById('closeWinOverlay');
const confettiLayer = document.getElementById('confettiLayer');

const step = 360 / segments.length;
const confettiColors = ['#2fa739', '#d4cb4b', '#ffffff', '#f2a900', '#1f7d2e'];

const prizePalette = {
    'R$ 2,00': ['#2f9e44', '#40c057'],
    'R$ 5,00': ['#1c7ed6', '#339af0'],
    'R$ 10,00': ['#f08c00', '#f59f00'],
    'R$ 20,00': ['#5f3dc4', '#7950f2'],
    'R$ 50,00': ['#c92a2a', '#e03131'],
};
const retryPalette = ['#8f8f8f', '#6c757d', '#495057'];

function textColorForBackground(hexColor) {
    const hex = hexColor.replace('#', '');
    const r = parseInt(hex.slice(0, 2), 16) / 255;
    const g = parseInt(hex.slice(2, 4), 16) / 255;
    const b = parseInt(hex.slice(4, 6), 16) / 255;
    const luminance = (0.2126 * r) + (0.7152 * g) + (0.0722 * b);
    return luminance > 0.58 ? '#1e2a12' : '#f8fff2';
}

const prizeCount = {};
let retryCount = 0;
const segmentMeta = segments.map((label) => {
    if (label === 'Tente novamente') {
        const retryIndex = retryCount % retryPalette.length;
        const color = retryPalette[retryIndex];
        retryCount += 1;
        return {
            label,
            color,
            textColor: textColorForBackground(color),
            className: `segment-label is-retry retry-${retryIndex + 1}`,
        };
    }

    const count = prizeCount[label] || 0;
    prizeCount[label] = count + 1;
    const palette = prizePalette[label] || ['#2f9e44', '#40c057'];
    const color = palette[count % palette.length];

    return {
        label,
        color,
        textColor: textColorForBackground(color),
        className: 'segment-label is-prize',
    };
});

roulette.style.background = `conic-gradient(${segmentMeta.map((meta, i) => {
    const start = i * step;
    const end = (i + 1) * step;
    return `${meta.color} ${start}deg ${end}deg`;
}).join(',')})`;

function renderWheelLabels() {
    roulette.querySelectorAll('.segment-label').forEach((node) => node.remove());

    const wheelRadius = roulette.clientWidth / 2;
    const labelRadius = wheelRadius * 0.58;
    const arcLength = ((Math.PI * 2 * labelRadius) * step) / 360;
    const labelWidth = Math.max(74, Math.min(Math.round(arcLength * 0.9), Math.round(wheelRadius * 0.64)));
    const labelHeight = Math.max(28, Math.min(Math.round(arcLength * 0.42), 40));

    segmentMeta.forEach((meta, i) => {
        const el = document.createElement('span');
        const angle = (i * step) + (step / 2);

        el.className = meta.className;
        el.textContent = meta.label;
        el.style.color = meta.textColor;
        el.style.width = `${labelWidth}px`;
        el.style.height = `${labelHeight}px`;
        el.style.marginLeft = `-${Math.round(labelWidth / 2)}px`;
        el.style.marginTop = `-${Math.round(labelHeight / 2)}px`;
        el.style.transform = `rotate(${angle}deg) translateY(-${labelRadius}px) rotate(${-angle}deg)`;

        roulette.appendChild(el);
    });
}

function renderWheelDots() {
    wheelDots.innerHTML = '';
    const dotCount = 28;
    const dotRadius = (roulette.clientWidth / 2) + 10;

    for (let i = 0; i < dotCount; i++) {
        const dot = document.createElement('i');
        dot.className = 'wheel-dot';
        const angle = (360 / dotCount) * i;
        dot.style.transform = `rotate(${angle}deg) translateY(-${dotRadius}px)`;
        wheelDots.appendChild(dot);
    }
}

renderWheelLabels();
renderWheelDots();

window.addEventListener('resize', () => {
    renderWheelLabels();
    renderWheelDots();
});

let currentRotation = 0;
let spinning = false;
let audioCtx = null;
let tickTimer = null;

function ensureAudioContext() {
    if (!window.AudioContext && !window.webkitAudioContext) {
        return null;
    }

    if (!audioCtx) {
        const AudioEngine = window.AudioContext || window.webkitAudioContext;
        audioCtx = new AudioEngine();
    }

    if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }

    return audioCtx;
}

function playTone(freq, duration, type = 'sine', volume = 0.04, startDelay = 0) {
    const ctx = ensureAudioContext();
    if (!ctx) {
        return;
    }

    const oscillator = ctx.createOscillator();
    const gain = ctx.createGain();
    const startAt = ctx.currentTime + startDelay;
    const endAt = startAt + duration;

    oscillator.type = type;
    oscillator.frequency.setValueAtTime(freq, startAt);
    gain.gain.setValueAtTime(0.0001, startAt);
    gain.gain.exponentialRampToValueAtTime(volume, startAt + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.0001, endAt);

    oscillator.connect(gain);
    gain.connect(ctx.destination);
    oscillator.start(startAt);
    oscillator.stop(endAt + 0.02);
}

function stopSpinTicks() {
    if (tickTimer !== null) {
        window.clearTimeout(tickTimer);
        tickTimer = null;
    }
}

function startSpinTicks() {
    stopSpinTicks();
    let ticks = 0;

    const tickLoop = () => {
        playTone(850, 0.02, 'square', 0.02);
        ticks += 1;
        const nextDelay = Math.min(190, 64 + (ticks * 2.5));
        tickTimer = window.setTimeout(tickLoop, nextDelay);
    };

    tickLoop();
}

function playWinSound() {
    playTone(523.25, 0.11, 'triangle', 0.06, 0);
    playTone(659.25, 0.12, 'triangle', 0.06, 0.12);
    playTone(783.99, 0.14, 'triangle', 0.065, 0.24);
    playTone(1046.5, 0.24, 'triangle', 0.07, 0.4);
}

function playLoseSound() {
    playTone(320, 0.14, 'sawtooth', 0.03, 0);
    playTone(220, 0.2, 'sawtooth', 0.03, 0.12);
}

function launchConfetti() {
    confettiLayer.innerHTML = '';

    for (let i = 0; i < 120; i++) {
        const piece = document.createElement('i');
        piece.className = 'confetti-piece';
        piece.style.left = `${Math.random() * 100}%`;
        piece.style.width = `${6 + Math.random() * 8}px`;
        piece.style.height = `${10 + Math.random() * 10}px`;
        piece.style.setProperty('--drift', `${(Math.random() * 2 - 1) * 180}px`);
        piece.style.animationDuration = `${1.8 + Math.random() * 1.8}s`;
        piece.style.animationDelay = `${Math.random() * 0.45}s`;
        piece.style.background = confettiColors[Math.floor(Math.random() * confettiColors.length)];
        piece.style.transform = `rotate(${Math.random() * 360}deg)`;
        confettiLayer.appendChild(piece);
    }
}

function showWinOverlay(label) {
    winTitle.textContent = `Voce ganhou ${label}`;
    launchConfetti();
    winOverlay.classList.add('show');
    winOverlay.setAttribute('aria-hidden', 'false');
}

closeWinOverlay.addEventListener('click', () => {
    winOverlay.classList.remove('show');
    winOverlay.setAttribute('aria-hidden', 'true');
});

spinBtn.addEventListener('click', async () => {
    if (spinning) return;

    ensureAudioContext();
    spinning = true;
    spinBtn.disabled = true;
    statusText.textContent = 'Girando... aguarde o resultado.';
    statusText.classList.remove('status-win', 'status-lose');
    pointer.classList.add('is-active');
    startSpinTicks();

    try {
        const response = await fetch('<?= BASE_URL ?>/api/spin.php', { method: 'POST' });
        const data = await response.json();

        if (!response.ok || !data.ok) {
            statusText.textContent = data.message || 'Nao foi possivel girar.';
            spinBtn.disabled = false;
            spinning = false;
            pointer.classList.remove('is-active');
            stopSpinTicks();
            return;
        }

        const options = Array.isArray(data.segment_indexes) && data.segment_indexes.length > 0
            ? data.segment_indexes.map((value) => Number(value))
            : [Number(data.segment_index)];
        const index = options[Math.floor(Math.random() * options.length)];

        const base = index * step + (step / 2) + ((Math.random() - 0.5) * (step * 0.28));
        const rotationToPointer = ((90 - base) % 360 + 360) % 360;
        const extraTurns = 18 + Math.floor(Math.random() * 5);
        const spinDurationSec = 12 + Math.random() * 3;

        roulette.style.transitionDuration = `${spinDurationSec}s`;
        currentRotation += (360 * extraTurns) + rotationToPointer;
        roulette.style.transform = `rotate(${currentRotation}deg)`;

        setTimeout(() => {
            pointer.classList.remove('is-active');
            stopSpinTicks();
            statusText.textContent = data.is_winner
                ? `Parabens! Voce ganhou ${data.result_label}.`
                : 'Tente novamente.';

            if (data.is_winner) {
                statusText.classList.add('status-win');
                showWinOverlay(data.result_label);
                playWinSound();
            } else {
                statusText.classList.add('status-lose');
                playLoseSound();
            }
        }, Math.round((spinDurationSec * 1000) + 140));
    } catch (error) {
        statusText.textContent = 'Erro de comunicacao com o servidor.';
        spinBtn.disabled = false;
        spinning = false;
        pointer.classList.remove('is-active');
        stopSpinTicks();
    }
});
</script>
</body>
</html>
