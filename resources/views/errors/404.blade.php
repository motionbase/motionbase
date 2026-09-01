<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Ende des Internets</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #ff0055;
            --ink: #18181b;
            --ink-soft: #3f3f46;
            --muted: #71717a;
            --line: #e4e4e7;
            --line-soft: #f4f4f5;
            --surface: #ffffff;
            --surface-muted: #fafafa;
            --font: 'Inter', 'Inter var', ui-sans-serif, system-ui, -apple-system,
                    BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            font-family: var(--font);
            font-feature-settings: 'ss01', 'ss02', 'cv01', 'cv02';
            -webkit-font-smoothing: antialiased;
            background: var(--surface-muted);
            color: var(--ink);
            overflow-x: hidden;
        }
        ::selection { background: var(--brand); color: #fff; }

        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem 0;
            text-align: center;
        }

        .code {
            font-size: clamp(4.5rem, 18vw, 9rem);
            font-weight: 700;
            letter-spacing: -0.05em;
            line-height: 0.85;
            margin: 0;
        }
        .code span { color: var(--brand); }

        h1 {
            margin: 1.25rem 0 0;
            font-size: clamp(1.25rem, 4vw, 1.75rem);
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .joke {
            margin: 0.875rem auto 0;
            min-height: 3.25em;
            max-width: 34rem;
            font-size: 1rem;
            line-height: 1.6;
            color: var(--muted);
            transition: opacity 0.35s ease;
        }
        .joke code {
            font-family: var(--mono);
            font-size: 0.8125em;
            color: var(--ink-soft);
            background: var(--line-soft);
            padding: 0.1em 0.35em;
            border-radius: 0.25rem;
        }

        .actions { margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.625rem; justify-content: center; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.125rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            font-family: inherit;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, transform 0.15s ease;
        }
        .btn--primary { background: var(--ink); color: #fff; }
        .btn--primary:hover { background: #000; transform: translateY(-1px); }
        .btn--ghost { background: var(--surface); border-color: var(--line); color: var(--ink-soft); }
        .btn--ghost:hover { background: var(--line-soft); border-color: #d4d4d8; }
        .btn:focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }

        /* ---- Die Bühne: Strecke, Abbruchkante, Ball ---- */
        .stage {
            position: relative;
            width: 100%;
            max-width: 46rem;
            height: 200px;
            margin: 2.5rem auto 0;
            flex: none;
        }

        .track {
            position: absolute;
            left: 0;
            top: 96px;
            height: 4px;
            width: 62%;
            background: var(--line);
            border-radius: 999px;
        }
        /* Die Kante franst aus - hier endet das Internet */
        .track::after {
            content: '';
            position: absolute;
            right: -2px; top: -3px;
            width: 10px; height: 10px;
            background: var(--line);
            clip-path: polygon(0 30%, 60% 0, 100% 55%, 45% 100%);
        }

        .sign {
            position: absolute;
            left: 62%;
            top: 44px;
            transform: translateX(-50%);
            font-size: 0.625rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            color: var(--muted);
            white-space: nowrap;
        }
        .sign::after {
            content: '';
            display: block;
            width: 1px; height: 26px;
            margin: 4px auto 0;
            background: var(--line);
        }

        .ball {
            position: absolute;
            top: 98px;
            left: 0;
            width: 44px; height: 44px;
            margin: -22px 0 0 -22px;
            border-radius: 50%;
            background: var(--brand);
            box-shadow: 0 8px 20px rgba(255, 0, 85, 0.28);
            will-change: transform;
        }
        .ball::after {
            content: '';
            position: absolute;
            inset: 34% 30% auto auto;
            width: 7px; height: 7px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.85);
        }

        .counter {
            position: absolute;
            right: 0;
            top: 138px;
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--muted);
            text-align: right;
            line-height: 1.7;
        }
        .counter b { color: var(--ink); font-weight: 600; }

        footer {
            margin-top: auto;
            padding: 2rem 1rem 1.5rem;
            font-size: 0.8125rem;
            color: var(--muted);
        }

        @media (prefers-reduced-motion: reduce) {
            .ball { transition: none !important; }
            .joke { transition: none; }
        }
    </style>
</head>
<body>
<div class="page">

    <p class="code">4<span>0</span>4</p>
    <h1>Du hast das Ende des Internets erreicht.</h1>
    <p class="joke" id="joke">Einen Moment, wir holen noch jemanden.</p>

    <div class="actions">
        <a class="btn btn--primary" href="{{ url('/') }}">Zurück auf festen Boden</a>
        <button class="btn btn--ghost" type="button" id="again">Nochmal fallen lassen</button>
    </div>

    <div class="stage" id="stage">
        <div class="track"></div>
        <div class="sign">ENDE</div>
        <div class="ball" id="ball"></div>
        <div class="counter">
            <div>Abstürze: <b id="falls">0</b></div>
            <div>Gefundene Seiten: <b>0</b></div>
        </div>
    </div>

    <footer>
        MotionBase — hier wird sonst über Easing geredet, nicht darunter gelitten.
    </footer>
</div>

<script>
    (() => {
        const ball = document.getElementById('ball');
        const stage = document.getElementById('stage');
        const jokeEl = document.getElementById('joke');
        const fallsEl = document.getElementById('falls');
        const againBtn = document.getElementById('again');

        const JOKES = [
            'Wir haben überall gesucht. Sogar in <code>node_modules</code>.',
            'Diese Seite hat einen Overshoot gemacht und ist über das Ziel hinausgeschossen.',
            'Die URL läuft mit <code>cubic-bezier(0, 0, 0, 0)</code> — sie kommt nie an.',
            'Ab hier ist nur noch Weißraum. Und zwar der echte, nicht der aus dem Styleguide.',
            'Letzte Ausfahrt vor <code>undefined</code>.',
            'Der Ball unten sucht mit. Er ist nur nicht besonders gut darin.',
            '<code>animation-fill-mode: none</code> — deshalb ist am Ende nichts mehr da.',
            'Serverseitig alles in Ordnung. Diese Seite ist nur menschlich verschwunden.',
            'Das Timing war perfekt. Das Ziel leider nicht.',
            'Wir hätten das mit <code>ease-in-out</code> sanfter gestalten können. Haben wir aber nicht.',
        ];

        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let falls = 0;
        let jokeIndex = -1;

        function nextJoke() {
            jokeIndex = (jokeIndex + 1) % JOKES.length;
            if (reduced) {
                jokeEl.innerHTML = JOKES[jokeIndex];
                return;
            }
            jokeEl.style.opacity = '0';
            setTimeout(() => {
                jokeEl.innerHTML = JOKES[jokeIndex];
                jokeEl.style.opacity = '1';
            }, 350);
        }

        // Reduced motion: der Ball sitzt an der Kante und denkt darüber nach.
        if (reduced) {
            const edge = stage.getBoundingClientRect().width * 0.62;
            ball.style.transform = `translate3d(${edge}px, 0, 0)`;
            nextJoke();
            setInterval(nextJoke, 6000);
            againBtn.addEventListener('click', nextJoke);
            return;
        }

        const easeOut = (t) => 1 - Math.pow(1 - t, 3);
        const easeIn = (t) => t * t * t;

        const ROLL = 2200;   // heranrollen
        const TEETER = 900;  // kippeln
        const FALL = 1100;   // fallen und aus dem Bild
        const PAUSE = 550;

        let phaseStart = performance.now();
        let phase = 'roll';

        function edgeX() {
            return stage.getBoundingClientRect().width * 0.62;
        }

        function frame(now) {
            const t = now - phaseStart;
            const edge = edgeX();

            if (phase === 'roll') {
                const p = Math.min(1, t / ROLL);
                const x = easeOut(p) * edge;
                ball.style.transform = `translate3d(${x}px, 0, 0) rotate(${(x / 44) * 180}deg)`;
                if (p === 1) { phase = 'teeter'; phaseStart = now; }

            } else if (phase === 'teeter') {
                // Kurz überlegen, ob das wirklich eine gute Idee ist
                const p = Math.min(1, t / TEETER);
                const wobble = Math.sin(p * Math.PI * 5) * (1 - p) * 7;
                ball.style.transform =
                    `translate3d(${edge + wobble}px, ${Math.abs(wobble) * 0.18}px, 0) rotate(${(edge / 44) * 180 + wobble}deg)`;
                if (p === 1) { phase = 'fall'; phaseStart = now; }

            } else if (phase === 'fall') {
                const p = Math.min(1, t / FALL);
                const drop = easeIn(p) * (window.innerHeight * 0.9);
                ball.style.transform =
                    `translate3d(${edge + p * 26}px, ${drop}px, 0) rotate(${(edge / 44) * 180 + p * 900}deg)`;
                if (p === 1) {
                    falls += 1;
                    fallsEl.textContent = falls;
                    nextJoke();
                    phase = 'pause';
                    phaseStart = now;
                }

            } else if (phase === 'pause') {
                ball.style.transform = 'translate3d(-60px, 0, 0)';
                if (t > PAUSE) { phase = 'roll'; phaseStart = now; }
            }

            requestAnimationFrame(frame);
        }

        nextJoke();
        requestAnimationFrame(frame);

        // Ungeduldig? Dann eben sofort.
        againBtn.addEventListener('click', () => {
            phase = 'fall';
            phaseStart = performance.now();
        });
    })();
</script>
</body>
</html>
