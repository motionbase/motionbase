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
        }
        ::selection { background: var(--brand); color: #fff; }

        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            padding: 2rem 1.25rem;
        }

        /* ---------- Karte ---------- */
        .card {
            width: min(38rem, 100%);
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 1rem;
            overflow: hidden;
        }

        .card__body {
            padding: clamp(2rem, 6vw, 3rem) clamp(1.5rem, 5vw, 3rem) clamp(1.75rem, 5vw, 2.5rem);
            text-align: center;
        }

        .code {
            margin: 0;
            font-size: clamp(3.75rem, 13vw, 5.5rem);
            font-weight: 700;
            letter-spacing: -0.055em;
            line-height: 0.9;
        }
        .code span { color: var(--brand); }

        h1 {
            margin: 1rem 0 0;
            font-size: clamp(1.125rem, 3.4vw, 1.375rem);
            font-weight: 700;
            letter-spacing: -0.015em;
            line-height: 1.3;
        }

        .joke {
            /* Feste Höhe: der Text wechselt, das Layout darf nicht springen. */
            display: flex;
            align-items: center;
            justify-content: center;
            height: 3.25rem;
            margin: 0.5rem auto 0;
            max-width: 26rem;
            font-size: 0.9375rem;
            line-height: 1.55;
            color: var(--muted);
            transition: opacity 0.35s ease;
        }

        .actions {
            margin-top: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5625rem 1rem;
            border-radius: 0.75rem;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }
        .btn--primary { background: var(--ink); color: #fff; }
        .btn--primary:hover { background: #000; }
        .btn--ghost { background: var(--surface); border-color: var(--line); color: var(--ink-soft); }
        .btn--ghost:hover { background: var(--line-soft); border-color: #d4d4d8; }
        .btn:focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }

        /* ---------- Bühne ---------- */
        .stage {
            position: relative;
            height: 132px;
            border-top: 1px solid var(--line);
            background: var(--surface-muted);
            /* Der Ball fällt hier heraus und ist weg - kein Loch im Layout. */
            overflow: hidden;
            transition: height 0.4s cubic-bezier(0, 0, 0.35, 1), background-color 0.3s ease;
        }

        .track {
            position: absolute;
            left: 0;
            right: 34%;
            top: 62px;
            height: 4px;
            background: var(--line);
            transition: opacity 0.3s ease;
        }
        .track::after {
            content: '';
            position: absolute;
            right: -1px; top: -3px;
            width: 10px; height: 10px;
            background: var(--line);
            clip-path: polygon(0 30%, 60% 0, 100% 55%, 45% 100%);
        }

        /* Wort und Ticklinie werden von der Flexbox auf dieselbe Achse
           gelegt, die selbst auf dem Streckenende sitzt. Mit auto-Rändern in
           einem shrink-to-fit-Kasten stand die Linie am linken Wortrand. */
        .sign {
            position: absolute;
            left: 66%;
            top: 26px;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.625rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            color: var(--muted);
            transition: opacity 0.3s ease;
        }
        /* Die Sperrung hängt hinter dem letzten Buchstaben nach und würde das
           Wort sonst nach links versetzt wirken lassen. */
        .sign span { margin-right: -0.12em; }
        .sign::after {
            content: '';
            width: 1px;
            height: 22px;
            margin-top: 4px;
            background: var(--line);
        }

        .ball {
            position: absolute;
            top: 64px;
            left: 0;
            width: 40px; height: 40px;
            margin: -20px 0 0 -20px;
            border-radius: 50%;
            background: var(--brand);
            box-shadow: 0 6px 16px rgba(255, 0, 85, 0.25);
            will-change: transform;
        }
        .ball::after {
            content: '';
            position: absolute;
            inset: 32% 28% auto auto;
            width: 6px; height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.85);
        }

        .pad {
            position: absolute;
            bottom: 10px;
            left: 0;
            width: 88px; height: 9px;
            margin-left: -44px;
            border-radius: 999px;
            background: var(--ink);
            will-change: transform;
        }

        /* Zähler und Punktestand teilen sich eine Zeile am unteren Rand */
        .readout {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            padding: 0 clamp(1.5rem, 5vw, 3rem) 0.875rem;
            font-family: var(--mono);
            font-size: 0.6875rem;
            color: var(--muted);
            transition: opacity 0.3s ease;
        }
        .readout b { color: var(--ink); font-weight: 600; }

        .score {
            position: absolute;
            left: clamp(1.5rem, 5vw, 3rem);
            top: 1rem;
            font-family: var(--mono);
            font-size: 0.6875rem;
            color: var(--muted);
            line-height: 1.6;
        }
        .score b { display: block; font-size: 1.5rem; color: var(--ink); font-weight: 700; line-height: 1.1; }

        /* ---------- Spielmodus ---------- */
        .stage.is-playing {
            height: 300px;
            background: var(--surface);
            cursor: none;
        }
        .stage.is-playing .track,
        .stage.is-playing .sign,
        .stage.is-playing .readout { opacity: 0; }

        .hint {
            margin: 0;
            font-size: 0.8125rem;
            color: var(--muted);
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

        @media (prefers-reduced-motion: reduce) {
            .stage, .joke, .hint { transition: none; }
        }
    </style>
</head>
<body>
<div class="page">

    <main class="card">
        <div class="card__body">
            <p class="code">4<span>0</span>4</p>
            <h1>Du hast das Ende des Internets erreicht.</h1>
            <p class="joke" id="joke">Einen Moment, wir schauen noch kurz nach.</p>

            <div class="actions">
                <a class="btn btn--primary" href="{{ url('/') }}">Zurück auf festen Boden</a>
            </div>
        </div>

        <div class="stage" id="stage">
            <div class="track"></div>
            <div class="sign"><span>ENDE</span></div>
            <div class="ball" id="ball"></div>
            <div class="pad" id="pad" hidden></div>

            <div class="readout" id="readout">
                <span>Abstürze: <b id="falls">0</b></span>
                <span>Gefundene Seiten: <b>0</b></span>
            </div>

            <div class="score" id="score" hidden>
                <b id="scoreValue">0</b>
                <span id="scoreBest"></span>
            </div>
        </div>
    </main>

    <p class="hint" id="hint" aria-live="polite"></p>
</div>

<script>
    (() => {
        const stage = document.getElementById('stage');
        const ball = document.getElementById('ball');
        const pad = document.getElementById('pad');
        const jokeEl = document.getElementById('joke');
        const fallsEl = document.getElementById('falls');
        const scoreEl = document.getElementById('score');
        const scoreValue = document.getElementById('scoreValue');
        const scoreBest = document.getElementById('scoreBest');
        const hint = document.getElementById('hint');

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
            if (reduced) { jokeEl.textContent = JOKES[jokeIndex]; return; }
            jokeEl.style.opacity = '0';
            setTimeout(() => {
                jokeEl.textContent = JOKES[jokeIndex];
                jokeEl.style.opacity = '1';
            }, 350);
        }

        const box = () => stage.getBoundingClientRect();
        const edgeX = () => box().width * 0.66;

        if (reduced) {
            ball.style.transform = `translate3d(${edgeX()}px, 0, 0)`;
            nextJoke();
            setInterval(nextJoke, 6000);
            return;
        }

        /* ---------------- Leerlauf ---------------- */
        const easeOut = (t) => 1 - Math.pow(1 - t, 3);
        const easeIn = (t) => t * t * t;
        const ROLL = 2200, TEETER = 900, FALL = 900, PAUSE = 500;

        let phase = 'roll';
        let phaseStart = performance.now();

        function idleFrame(now) {
            const t = now - phaseStart;
            const edge = edgeX();

            if (phase === 'roll') {
                const p = Math.min(1, t / ROLL);
                const x = easeOut(p) * edge;
                ball.style.transform = `translate3d(${x}px, 0, 0) rotate(${(x / 40) * 180}deg)`;
                if (p === 1) { phase = 'teeter'; phaseStart = now; }

            } else if (phase === 'teeter') {
                const p = Math.min(1, t / TEETER);
                const wobble = Math.sin(p * Math.PI * 5) * (1 - p) * 6;
                ball.style.transform =
                    `translate3d(${edge + wobble}px, ${Math.abs(wobble) * 0.2}px, 0) rotate(${(edge / 40) * 180 + wobble}deg)`;
                if (p === 1) { phase = 'fall'; phaseStart = now; }

            } else if (phase === 'fall') {
                const p = Math.min(1, t / FALL);
                const drop = easeIn(p) * (box().height + 80);
                ball.style.transform =
                    `translate3d(${edge + p * 22}px, ${drop}px, 0) rotate(${(edge / 40) * 180 + p * 720}deg)`;
                if (p === 1) {
                    falls += 1;
                    fallsEl.textContent = falls;
                    nextJoke();
                    maybeHint();
                    phase = 'pause';
                    phaseStart = now;
                }

            } else {
                ball.style.transform = 'translate3d(-56px, 0, 0)';
                if (t > PAUSE) { phase = 'roll'; phaseStart = now; }
            }
        }

        /* ---------------- Spiel ---------------- */
        const RADIUS = 20, PAD_HALF = 44, PAD_TOP = 19, GRAVITY = 1500;

        let playing = false;
        let padX = 0, score = 0, lastTick = 0, spin = 0;
        let pos = { x: 0, y: 0 };
        let vel = { x: 0, y: 0 };

        let best = 0;
        try { best = parseInt(localStorage.getItem('motionbase.404.best') || '0', 10) || 0; } catch { best = 0; }

        // Drehwinkel aus dem aktuellen transform lesen, damit der Übergang in
        // das Spiel keinen Sprung in der Rotation macht.
        function currentSpin() {
            const m = /rotate\(([-\d.]+)deg\)/.exec(ball.style.transform || '');
            return m ? parseFloat(m[1]) : 0;
        }

        function showHint(html) {
            hint.innerHTML = html;
            hint.classList.add('is-visible');
        }

        function maybeHint() {
            if (!playing && falls >= 3 && !hint.classList.contains('is-visible')) {
                showHint('<kbd>Leertaste</kbd>, wenn du ihn auffangen willst');
            }
        }

        function startGame() {
            if (playing) return;
            playing = true;
            score = 0;
            // Dort weitermachen, wo der Ball gerade ist. Ihn an den oberen Rand
            // zu setzen war ein Sprung von rund 100px - genau das, was sich
            // beim Starten unrund angefühlt hat.
            const b = box();
            const r = ball.getBoundingClientRect();
            pos = { x: r.left + r.width / 2 - b.left, y: r.top + r.height / 2 - b.top };
            pos.x = Math.max(RADIUS, Math.min(b.width - RADIUS, pos.x));
            pos.y = Math.max(RADIUS, pos.y);

            padX = pos.x;
            vel = { x: 70, y: 40 };
            spin = currentSpin();
            lastTick = performance.now();

            stage.classList.add('is-playing');
            pad.hidden = false;
            scoreEl.hidden = false;
            scoreValue.textContent = '0';
            scoreBest.textContent = best ? 'Beste ' + best : '';
            hint.classList.remove('is-visible');
            jokeEl.textContent = 'Nicht runterfallen lassen.';
            showHint('<kbd>Esc</kbd> beendet');
        }

        function endGame() {
            playing = false;
            stage.classList.remove('is-playing');
            pad.hidden = true;
            scoreEl.hidden = true;

            if (score > best) {
                best = score;
                try { localStorage.setItem('motionbase.404.best', String(best)); } catch { /* Privatmodus */ }
                jokeEl.textContent = 'Bestleistung: ' + score + '. Niemand sonst weiß davon.';
            } else {
                jokeEl.textContent = score === 0
                    ? 'Null. Das war schnell.'
                    : score + ' gefangen. Die Bestleistung liegt bei ' + best + '.';
            }

            showHint('<kbd>Leertaste</kbd> für nochmal');
            phase = 'pause';
            phaseStart = performance.now();
        }

        function gameFrame(now) {
            const dt = Math.min(0.032, (now - lastTick) / 1000);
            lastTick = now;

            const b = box();
            const floor = b.height - PAD_TOP;

            vel.y += GRAVITY * dt;
            pos.x += vel.x * dt;
            pos.y += vel.y * dt;

            if (pos.x < RADIUS) { pos.x = RADIUS; vel.x = Math.abs(vel.x); }
            if (pos.x > b.width - RADIUS) { pos.x = b.width - RADIUS; vel.x = -Math.abs(vel.x); }
            if (pos.y < RADIUS) { pos.y = RADIUS; vel.y = Math.abs(vel.y); }

            if (vel.y > 0 && pos.y + RADIUS >= floor && pos.y + RADIUS <= floor + 26) {
                const offset = (pos.x - padX) / PAD_HALF;

                if (Math.abs(offset) <= 1.15) {
                    pos.y = floor - RADIUS;
                    // Der Auftreffpunkt bestimmt den Winkel - daher kommt das Können
                    vel.y = -Math.min(920, 520 + score * 10);
                    vel.x = Math.max(-500, Math.min(500, vel.x + offset * 230));
                    score += 1;
                    scoreValue.textContent = score;
                }
            }

            if (pos.y - RADIUS > b.height) {
                falls += 1;
                fallsEl.textContent = falls;
                endGame();
                return;
            }

            // Abrollen: Winkel folgt der zurückgelegten Strecke, nicht der
            // absoluten Position - sonst kehrt sich die Drehung bei jedem
            // Richtungswechsel schlagartig um und wirkt wie ein Flackern.
            spin += (vel.x * dt) * (180 / (Math.PI * RADIUS));

            pad.style.transform = `translate3d(${padX}px, 0, 0)`;
            ball.style.transform = `translate3d(${pos.x}px, ${pos.y - 64}px, 0) rotate(${spin}deg)`;
        }

        function frame(now) {
            playing ? gameFrame(now) : idleFrame(now);
            requestAnimationFrame(frame);
        }

        function movePad(clientX) {
            const b = box();
            padX = Math.max(PAD_HALF, Math.min(b.width - PAD_HALF, clientX - b.left));
        }

        stage.addEventListener('pointermove', (e) => { if (playing) movePad(e.clientX); });
        stage.addEventListener('touchmove', (e) => {
            if (!playing) return;
            e.preventDefault();
            movePad(e.touches[0].clientX);
        }, { passive: false });

        window.addEventListener('keydown', (e) => {
            if (e.code === 'Space') { e.preventDefault(); if (!playing) startGame(); return; }
            if (!playing) return;
            if (e.key === 'Escape') { endGame(); return; }
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                e.preventDefault();
                const b = box();
                padX = Math.max(PAD_HALF, Math.min(b.width - PAD_HALF, padX + (e.key === 'ArrowLeft' ? -44 : 44)));
            }
        });

        ball.addEventListener('click', startGame);

        nextJoke();
        requestAnimationFrame(frame);
    })();
</script>
</body>
</html>
