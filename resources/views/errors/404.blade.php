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
            pointer-events: none;
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

        .pad {
            position: absolute;
            bottom: 8px;
            left: 0;
            width: 92px;
            height: 10px;
            margin-left: -46px;
            border-radius: 999px;
            background: var(--ink);
            will-change: transform;
        }

        .score {
            position: absolute;
            left: 0;
            top: 0;
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--muted);
            line-height: 1.7;
            text-align: left;
        }
        .score b { display: block; font-size: 1.75rem; color: var(--ink); line-height: 1.1; }

        .hint {
            position: absolute;
            left: 50%;
            bottom: -1.75rem;
            transform: translateX(-50%);
            margin: 0;
            font-size: 0.8125rem;
            color: var(--muted);
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        .hint.is-visible { opacity: 1; }
        .hint kbd {
            font-family: var(--mono);
            font-size: 0.9em;
            border: 1px solid var(--line);
            border-bottom-width: 2px;
            border-radius: 0.3rem;
            padding: 0.1em 0.4em;
            background: var(--surface);
            color: var(--ink-soft);
        }

        .stage {
            transition: height 0.35s cubic-bezier(0, 0, 0.58, 1),
                        background-color 0.35s ease, border-color 0.35s ease;
            border: 1px solid transparent;
            border-radius: 0.75rem;
        }
        /* Im Spiel wird aus der Strecke ein Feld - die leere Fläche darunter
           war ohnehin nur da, damit der Ball hinausfallen kann. */
        .stage.is-playing {
            height: 380px;
            cursor: none;
            background: var(--surface);
            border-color: var(--line);
        }
        .stage.is-playing .track,
        .stage.is-playing .sign,
        .stage.is-playing .counter { opacity: 0; }

        .track, .sign, .counter { transition: opacity 0.3s ease; }

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
    <p class="joke" id="joke">Einen Moment, wir schauen noch kurz nach.</p>

    <div class="actions">
        <a class="btn btn--primary" href="{{ url('/') }}">Zurück auf festen Boden</a>
        <button class="btn btn--ghost" type="button" id="again">Nochmal fallen lassen</button>
    </div>

    <div class="stage" id="stage">
        <div class="track"></div>
        <div class="sign">ENDE</div>
        <div class="ball" id="ball"></div>
        <div class="pad" id="pad" hidden></div>

        <div class="counter">
            <div>Abstürze: <b id="falls">0</b></div>
            <div>Gefundene Seiten: <b>0</b></div>
        </div>

        <div class="score" id="score" hidden>
            <b id="scoreValue">0</b>
            <span id="scoreBest"></span>
        </div>

        <p class="hint" id="hint" hidden></p>
    </div>

    <footer>
        MotionBase — normalerweise finden wir Dinge.
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
            'Wir haben überall gesucht. Sogar hinter dem Sofa.',
            'Diese Seite ist umgezogen und hat keine Nachsendeadresse hinterlassen.',
            'Hier ist Schluss. Weiter hinten kommt nur noch Serverraum.',
            'Wenn du das liest, bist du weiter gekommen als vorgesehen.',
            'Vielleicht ein Tippfehler. Vielleicht Schicksal. Wir wollen uns nicht festlegen.',
            'Bitte weitergehen, hier gibt es wirklich nichts zu sehen.',
            'Der Ball da unten sucht auch. Seit Jahren. Ohne Erfolg.',
            'Das Internet endet an dieser Stelle. Wir sind selbst überrascht.',
            'Die Seite hat sich krankgemeldet. Auf unbestimmte Zeit.',
            'Hinter der Kante beginnt das Nichts. Gepflegt, aber leer.',
            'Du bist der Erste hier unten. Vermutlich. Es führt niemand Buch.',
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
            if (playing) {
                gameFrame(now);
                requestAnimationFrame(frame);
                return;
            }

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
            if (playing) return;
            phase = 'fall';
            phaseStart = performance.now();
        });

        /* ================================================================
           Versteckt: nach ein paar Abstürzen taucht ein Hinweis auf, und
           wer ihn befolgt, darf den Ball selbst auffangen.
           ================================================================ */
        const pad = document.getElementById('pad');
        const scoreEl = document.getElementById('score');
        const scoreValue = document.getElementById('scoreValue');
        const scoreBest = document.getElementById('scoreBest');
        const hint = document.getElementById('hint');

        const BEST_KEY = 'motionbase.404.best';
        let best = 0;
        try { best = parseInt(localStorage.getItem(BEST_KEY) || '0', 10) || 0; } catch { best = 0; }

        let playing = false;
        let padX = 0;
        let ballPos = { x: 0, y: 0 };
        let ballVel = { x: 0, y: 0 };
        let score = 0;
        let lastTick = 0;

        const RADIUS = 22;
        const PAD_HALF = 46;
        const PAD_TOP = 18;     // Abstand der Pad-Oberkante zum Boden der Bühne
        const GRAVITY = 1500;   // px/s²

        function stageBox() { return stage.getBoundingClientRect(); }

        function showHint(html) {
            hint.innerHTML = html;
            hint.hidden = false;
            requestAnimationFrame(() => hint.classList.add('is-visible'));
        }

        function hideHint() {
            hint.classList.remove('is-visible');
            setTimeout(() => { if (!hint.classList.contains('is-visible')) hint.hidden = true; }, 400);
        }

        function startGame() {
            if (playing) return;
            playing = true;
            score = 0;
            const box = stageBox();
            padX = box.width / 2;
            ballPos = { x: box.width * 0.5, y: 20 };
            ballVel = { x: 90, y: 0 };
            lastTick = performance.now();

            stage.classList.add('is-playing');
            pad.hidden = false;
            scoreEl.hidden = false;
            scoreValue.textContent = '0';
            scoreBest.textContent = best ? 'Beste: ' + best : '';
            hideHint();
            jokeEl.innerHTML = 'Nicht runterfallen lassen.';
            againBtn.textContent = 'Aufhören';
        }

        function endGame() {
            playing = false;
            stage.classList.remove('is-playing');
            pad.hidden = true;
            againBtn.textContent = 'Nochmal fallen lassen';

            if (score > best) {
                best = score;
                try { localStorage.setItem(BEST_KEY, String(best)); } catch { /* Privatmodus */ }
                jokeEl.innerHTML = 'Bestleistung: ' + score + '. Niemand sonst weiß davon.';
            } else {
                jokeEl.innerHTML = score === 0
                    ? 'Null. Das war schnell.'
                    : score + ' gefangen. Die Bestleistung liegt bei ' + best + '.';
            }

            scoreBest.textContent = 'Beste: ' + best;
            showHint('<kbd>Leertaste</kbd> für nochmal');
            phase = 'pause';
            phaseStart = performance.now();
        }

        function gameFrame(now) {
            const dt = Math.min(0.032, (now - lastTick) / 1000);
            lastTick = now;

            const box = stageBox();
            const floor = box.height - PAD_TOP;

            ballVel.y += GRAVITY * dt;
            ballPos.x += ballVel.x * dt;
            ballPos.y += ballVel.y * dt;

            // Wände
            if (ballPos.x < RADIUS) { ballPos.x = RADIUS; ballVel.x = Math.abs(ballVel.x); }
            if (ballPos.x > box.width - RADIUS) { ballPos.x = box.width - RADIUS; ballVel.x = -Math.abs(ballVel.x); }
            if (ballPos.y < RADIUS) { ballPos.y = RADIUS; ballVel.y = Math.abs(ballVel.y); }

            // Pad
            if (ballVel.y > 0 && ballPos.y + RADIUS >= floor && ballPos.y + RADIUS <= floor + 26) {
                const offset = (ballPos.x - padX) / PAD_HALF;

                if (Math.abs(offset) <= 1.15) {
                    ballPos.y = floor - RADIUS;
                    // Auftreffpunkt bestimmt den Winkel - daher kommt das Können
                    ballVel.y = -Math.min(1000, 560 + score * 11);
                    ballVel.x += offset * 240;
                    ballVel.x = Math.max(-520, Math.min(520, ballVel.x));

                    score += 1;
                    scoreValue.textContent = score;
                }
            }

            if (ballPos.y - RADIUS > box.height) {
                falls += 1;
                fallsEl.textContent = falls;
                endGame();
                return;
            }

            pad.style.transform = `translate3d(${padX}px, 0, 0)`;
            ball.style.transform =
                `translate3d(${ballPos.x}px, ${ballPos.y - 98}px, 0) rotate(${ballPos.x * 1.6}deg)`;
        }

        function movePad(clientX) {
            const box = stageBox();
            padX = Math.max(PAD_HALF, Math.min(box.width - PAD_HALF, clientX - box.left));
        }

        stage.addEventListener('pointermove', (e) => { if (playing) movePad(e.clientX); });
        stage.addEventListener('touchmove', (e) => {
            if (!playing) return;
            e.preventDefault();
            movePad(e.touches[0].clientX);
        }, { passive: false });

        window.addEventListener('keydown', (e) => {
            if (e.code === 'Space') {
                e.preventDefault();
                playing ? null : startGame();
                return;
            }
            if (playing && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
                e.preventDefault();
                const box = stageBox();
                padX = Math.max(PAD_HALF, Math.min(box.width - PAD_HALF,
                    padX + (e.key === 'ArrowLeft' ? -46 : 46)));
            }
            if (playing && e.key === 'Escape') endGame();
        });

        ball.addEventListener('click', startGame);

        againBtn.addEventListener('click', () => { if (playing) endGame(); });

        // Der Hinweis kommt erst, wenn man lange genug zugesehen hat.
        const hintWatcher = setInterval(() => {
            if (playing || hint.classList.contains('is-visible')) return;
            if (falls >= 3) {
                showHint('<kbd>Leertaste</kbd>, wenn du ihn auffangen willst');
                clearInterval(hintWatcher);
            }
        }, 1000);

    })();
</script>
</body>
</html>
