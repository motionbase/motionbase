{{-- Full screen celebration for a perfect quiz. One implementation for all
     three surfaces: the course view, the public embed and the LTI embed. --}}
<style>
    .mb-celebrate {
        position: fixed;
        inset: 0;
        z-index: 2147483000;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(9, 9, 11, 0.82);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        opacity: 0;
        transition: opacity 350ms cubic-bezier(0, 0, 0.58, 1);
        cursor: pointer;
        font-family: 'Inter', 'Inter var', ui-sans-serif, system-ui, -apple-system, sans-serif;
        font-feature-settings: 'ss01', 'ss02', 'cv01', 'cv02';
        -webkit-font-smoothing: antialiased;
    }
    .mb-celebrate--in { opacity: 1; }

    .mb-celebrate__canvas {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }

    .mb-celebrate__center {
        position: relative;
        text-align: center;
        padding: 0 1.5rem;
        pointer-events: none;
    }

    .mb-celebrate__score {
        margin: 0;
        font-size: clamp(4rem, 16vw, 8rem);
        font-weight: 700;
        letter-spacing: -0.055em;
        line-height: 0.9;
        color: #ff0055;
        transform: scale(0.6);
        opacity: 0;
    }
    .mb-celebrate__score--in {
        animation: mb-celebrate-pop 640ms cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    .mb-celebrate__title,
    .mb-celebrate__sub {
        opacity: 0;
        transform: translateY(12px);
    }
    .mb-celebrate__title--in,
    .mb-celebrate__sub--in {
        animation: mb-celebrate-rise 520ms cubic-bezier(0, 0, 0.35, 1) forwards;
    }

    .mb-celebrate__title {
        margin: 1rem 0 0;
        font-size: clamp(1.5rem, 5vw, 2.25rem);
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #ffffff;
    }

    .mb-celebrate__sub {
        margin: 0.625rem 0 0;
        font-size: 0.9375rem;
        color: #a1a1aa;
    }

    .mb-celebrate__hint {
        position: absolute;
        left: 50%;
        bottom: 2rem;
        transform: translateX(-50%);
        font-size: 0.75rem;
        color: #71717a;
        opacity: 0;
        transition: opacity 400ms ease;
    }
    .mb-celebrate__hint--in { opacity: 1; }

    /* Easter egg: the "six seven" gesture, two open palms held out towards the
       viewer, weighing nothing against nothing. The right palm is mirrored so
       the pair is symmetrical. */
    .mb-celebrate__hands {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: clamp(1.25rem, 5.5vw, 3.75rem);
        font-size: clamp(3.5rem, 14vw, 7rem);
        line-height: 1;
    }

    /* The palms are held out in front, not raised, so the emoji is the palm-up
       hand and the pair tips up and down in opposite phase - the weighing
       motion the meme is built on. Mirroring lives inside the keyframes
       because the bob animates the same transform property. */
    .mb-celebrate__hand-svg {
        display: block;
        width: 1.15em;
        height: 1.15em;
        overflow: visible;
    }
    .mb-celebrate__hand {
        display: inline-block;
        transform-origin: 70% 50%;
        filter: drop-shadow(0 12px 18px rgba(0, 0, 0, 0.45));
        animation: mb-celebrate-bob-left 620ms cubic-bezier(0.45, 0, 0.55, 1) infinite alternate;
    }
    .mb-celebrate__hand--right {
        transform-origin: 30% 50%;
        animation-name: mb-celebrate-bob-right;
        animation-delay: 310ms;
    }

    .mb-celebrate__digit {
        font-size: clamp(3rem, 12vw, 6rem);
        font-weight: 700;
        letter-spacing: -0.04em;
        color: #ff0055;
        animation: mb-celebrate-beat 620ms cubic-bezier(0.45, 0, 0.55, 1) infinite alternate;
    }
    .mb-celebrate__digit--seven { animation-delay: 310ms; }

    @keyframes mb-celebrate-bob-left {
        from { transform: scaleX(-1) translateY(-16%) rotate(10deg); }
        to   { transform: scaleX(-1) translateY(16%) rotate(-8deg); }
    }
    @keyframes mb-celebrate-bob-right {
        from { transform: translateY(16%) rotate(8deg); }
        to   { transform: translateY(-16%) rotate(-10deg); }
    }
    @keyframes mb-celebrate-beat {
        from { opacity: 0.45; transform: scale(0.94); }
        to   { opacity: 1; transform: scale(1.06); }
    }

    @keyframes mb-celebrate-pop {
        to { transform: scale(1); opacity: 1; }
    }
    @keyframes mb-celebrate-rise {
        to { transform: translateY(0); opacity: 1; }
    }

    @media (prefers-reduced-motion: reduce) {
        .mb-celebrate,
        .mb-celebrate__score,
        .mb-celebrate__title,
        .mb-celebrate__sub,
        .mb-celebrate__hint,
        .mb-celebrate__hand,
        .mb-celebrate__digit { transition: none; animation: none; }
        .mb-celebrate__digit { opacity: 1; transform: none; }
        .mb-celebrate__hand { transform: scaleX(-1); }
        .mb-celebrate__hand--right { transform: none; }
        .mb-celebrate__score,
        .mb-celebrate__title,
        .mb-celebrate__sub { transform: none; opacity: 1; }
    }
</style>
<script>
    (function () {
        var TOTAL = 6500;      // Gesamtdauer
        var FADE_AT = 5600;    // ab hier ausblenden
        var SIX_SEVEN_TOTAL = 5200;
        var running = false;

        // Eine offene Hand, die Fläche zur Betrachterin. Der Daumen sitzt auf
        // der Fläche und zeigt nach innen - bei nach aussen gedrehtem Daumen
        // sähe man den Handrücken. Die Finger liegen dahinter und sind kurz,
        // weil sie perspektivisch verkürzt sind. Gezeichnet statt als Emoji,
        // weil jede Plattform ein anderes liefert und keines die Hand nach
        // vorne streckt.
        var HAND_SVG =
            '<svg class="mb-celebrate__hand-svg" viewBox="0 0 120 120" aria-hidden="true" focusable="false">' +
                '<g fill="#f2b62f">' +
                    '<rect x="30" y="25" width="15" height="46" rx="7.5" transform="rotate(-9 37.5 48)"/>' +
                    '<rect x="46" y="20" width="15" height="51" rx="7.5"/>' +
                    '<rect x="62" y="24" width="15" height="47" rx="7.5" transform="rotate(8 69.5 48)"/>' +
                    '<rect x="77" y="33" width="14" height="38" rx="7" transform="rotate(16 84 52)"/>' +
                '</g>' +
                '<rect x="27" y="52" width="68" height="50" rx="23" fill="#ffcf55"/>' +
                '<path d="M35 88 L16 67" fill="none" stroke="#ffd66b" stroke-width="22" stroke-linecap="round"/>' +
                '<ellipse cx="41" cy="83" rx="13" ry="16" fill="#ffd66b" transform="rotate(-10 41 83)"/>' +
                '<ellipse cx="66" cy="82" rx="16" ry="12" fill="#ffdc84" opacity="0.45"/>' +
                '<g fill="none" stroke="#e0a428" stroke-width="3" stroke-linecap="round" opacity="0.4">' +
                    '<path d="M43 65 q-4 15 2 26"/>' +
                    '<path d="M52 69 q13 5 25 2"/>' +
                '</g>' +
            '</svg>';

        var COLORS = ['#ff0055', '#ff4d84', '#ffffff', '#e4e4e7', '#a1a1aa'];

        function burst(particles, width, height, count, spread, speed) {
            var cx = width / 2;
            var cy = height * 0.42;

            for (var i = 0; i < count; i++) {
                var angle = (-Math.PI / 2) + (Math.random() - 0.5) * spread;
                var velocity = speed * (0.45 + Math.random() * 0.75);

                particles.push({
                    x: cx + (Math.random() - 0.5) * 120,
                    y: cy + (Math.random() - 0.5) * 40,
                    vx: Math.cos(angle) * velocity,
                    vy: Math.sin(angle) * velocity,
                    size: 5 + Math.random() * 7,
                    color: COLORS[(Math.random() * COLORS.length) | 0],
                    spin: (Math.random() - 0.5) * 12,
                    angle: Math.random() * Math.PI * 2,
                    round: Math.random() < 0.35,
                    life: 0,
                });
            }
        }

        window.motionbaseCelebrate = function (options) {
            if (running) return;

            var opts = options || {};
            var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            running = true;

            var overlay = document.createElement('div');
            overlay.className = 'mb-celebrate';
            overlay.setAttribute('role', 'status');
            overlay.setAttribute('aria-live', 'polite');

            var canvas = document.createElement('canvas');
            canvas.className = 'mb-celebrate__canvas';
            canvas.setAttribute('aria-hidden', 'true');

            var center = document.createElement('div');
            center.className = 'mb-celebrate__center';

            var sixSeven = opts.variant === 'six-seven';

            var score = document.createElement('p');
            score.className = 'mb-celebrate__score';
            score.textContent = opts.score || '100%';

            // Zwei Handflächen mit den Ziffern dazwischen
            var hands = document.createElement('div');
            hands.className = 'mb-celebrate__hands';
            hands.setAttribute('aria-hidden', 'true');
            hands.innerHTML =
                '<span class="mb-celebrate__hand">' + HAND_SVG + '</span>' +
                '<span class="mb-celebrate__digit">6</span>' +
                '<span class="mb-celebrate__digit mb-celebrate__digit--seven">7</span>' +
                '<span class="mb-celebrate__hand mb-celebrate__hand--right">' + HAND_SVG + '</span>';

            var title = document.createElement('p');
            title.className = 'mb-celebrate__title';
            title.textContent = opts.title || 'Alles richtig.';

            var sub = document.createElement('p');
            sub.className = 'mb-celebrate__sub';
            sub.textContent = opts.subtitle || '';

            var hint = document.createElement('p');
            hint.className = 'mb-celebrate__hint';
            hint.textContent = 'Klicken zum Schließen';

            center.appendChild(sixSeven ? hands : score);
            center.appendChild(title);
            if (sub.textContent) center.appendChild(sub);
            overlay.appendChild(canvas);
            overlay.appendChild(center);
            overlay.appendChild(hint);
            document.body.appendChild(overlay);

            var ctx = canvas.getContext('2d');
            var particles = [];
            var dpr = Math.min(window.devicePixelRatio || 1, 2);
            var width = 0;
            var height = 0;

            function resize() {
                var rect = overlay.getBoundingClientRect();
                width = rect.width;
                height = rect.height;
                canvas.width = width * dpr;
                canvas.height = height * dpr;
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            }

            resize();
            window.addEventListener('resize', resize);

            var closed = false;
            var timers = [];

            function later(fn, ms) { timers.push(setTimeout(fn, ms)); }

            function close() {
                if (closed) return;
                closed = true;

                timers.forEach(clearTimeout);
                window.removeEventListener('resize', resize);
                document.removeEventListener('keydown', onKey);

                overlay.classList.remove('mb-celebrate--in');
                setTimeout(function () {
                    if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
                    running = false;
                }, reduced ? 0 : 360);
            }

            function onKey() { close(); }

            overlay.addEventListener('click', close);
            document.addEventListener('keydown', onKey);

            // Auftakt
            requestAnimationFrame(function () { overlay.classList.add('mb-celebrate--in'); });

            if (reduced) {
                // Kein Partikelflug, nur die Aussage - und kürzer, weil eine
                // stehende Einblendung nicht sieben Sekunden braucht.
                // Nicht auf requestAnimationFrame verlassen: ein Tab ohne
                // Frames würde das Overlay sonst unsichtbar stehen lassen.
                overlay.classList.add('mb-celebrate--in');
                score.style.opacity = '1';
                title.style.opacity = '1';
                sub.style.opacity = '1';
                hint.classList.add('mb-celebrate__hint--in');
                later(close, 2600);
                return;
            }

            var total = sixSeven ? SIX_SEVEN_TOTAL : TOTAL;
            var fadeAt = total - 900;

            later(function () { title.classList.add('mb-celebrate__title--in'); }, sixSeven ? 700 : 900);
            later(function () { sub.classList.add('mb-celebrate__sub--in'); }, sixSeven ? 880 : 1080);
            later(function () { hint.classList.add('mb-celebrate__hint--in'); }, sixSeven ? 1600 : 2200);

            if (sixSeven) {
                // Die Hände tragen die Bewegung, Konfetti wäre hier zu viel -
                // und der Witz lebt davon, dass gerade NICHTS gefeiert wird.
                hands.style.opacity = '1';
            } else {
                later(function () { score.classList.add('mb-celebrate__score--in'); }, 220);
                later(function () { burst(particles, width, height, 90, 1.5, 15); }, 160);
                later(function () { burst(particles, width, height, 60, 2.6, 12); }, 700);
                later(function () { burst(particles, width, height, 45, 3.0, 10); }, 1800);
                later(function () { burst(particles, width, height, 45, 3.0, 10); }, 2700);
            }

            later(function () {
                overlay.style.transition = 'opacity 900ms cubic-bezier(0.42, 0, 1, 1)';
                overlay.classList.remove('mb-celebrate--in');
            }, fadeAt);

            later(close, total);

            var last = performance.now();

            function frame(now) {
                if (closed) return;

                var dt = Math.min(0.032, (now - last) / 1000);
                last = now;

                ctx.clearRect(0, 0, width, height);

                for (var i = particles.length - 1; i >= 0; i--) {
                    var p = particles[i];

                    p.life += dt;
                    p.vy += 26 * dt;          // Schwerkraft
                    p.vx *= 1 - 0.7 * dt;     // Luftwiderstand
                    p.vy *= 1 - 0.2 * dt;
                    p.x += p.vx;
                    p.y += p.vy;
                    p.angle += p.spin * dt;

                    if (p.y - p.size > height || p.life > 8) {
                        particles.splice(i, 1);
                        continue;
                    }

                    ctx.save();
                    ctx.globalAlpha = Math.max(0, Math.min(1, 1 - (p.life - 3.5) / 2));
                    ctx.translate(p.x, p.y);
                    ctx.rotate(p.angle);
                    ctx.fillStyle = p.color;

                    if (p.round) {
                        ctx.beginPath();
                        ctx.arc(0, 0, p.size / 2, 0, Math.PI * 2);
                        ctx.fill();
                    } else {
                        ctx.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2);
                    }

                    ctx.restore();
                }

                requestAnimationFrame(frame);
            }

            requestAnimationFrame(frame);
        };
    })();
</script>
