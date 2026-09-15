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

    /* Easter egg: the "six seven" gesture, two palms held out and turned up,
       weighing nothing against nothing. The right hand is the same drawing
       mirrored. */
    .mb-celebrate__hands {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: clamp(1.25rem, 5.5vw, 3.75rem);
        font-size: clamp(3.5rem, 14vw, 7rem);
        line-height: 1;
    }

    /* The pair tips up and down in opposite phase - the weighing motion the
       meme is built on. It pivots near the wrist, at the top, so the fingers
       carry the swing. Mirroring lives inside the keyframes because the bob
       animates the same transform property and would otherwise drop it. */
    .mb-celebrate__hand-svg {
        display: block;
        width: 1.5em;
        height: auto;
    }
    .mb-celebrate__hand {
        display: inline-block;
        transform-origin: 50% 14%;
        filter: drop-shadow(0 8px 14px rgba(0, 0, 0, 0.4));
        animation: mb-celebrate-bob-left 620ms cubic-bezier(0.45, 0, 0.55, 1) infinite alternate;
    }
    .mb-celebrate__hand--right {
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
        from { transform: translateY(-15%) rotate(-6deg); }
        to   { transform: translateY(15%) rotate(5deg); }
    }
    @keyframes mb-celebrate-bob-right {
        from { transform: scaleX(-1) translateY(15%) rotate(5deg); }
        to   { transform: scaleX(-1) translateY(-15%) rotate(-6deg); }
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
        .mb-celebrate__hand { transform: none; }
        .mb-celebrate__hand--right { transform: scaleX(-1); }
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

        // Eine offene Hand nach der Vorlage: Fläche nach oben, nach vorne
        // gestreckt, Finger gefächert nach unten, Daumen nach aussen - bei
        // einer Fläche nach oben liegt der Daumen aussen, nicht innen. Die
        // Proportionen stammen aus der Vorlage: die Fläche etwas breiter als
        // hoch, Finger gut ein Fünftel davon dick, sodass sie sich an der
        // Wurzel berühren, und von innen nach aussen kürzer. Fläche und Daumen
        // sind ein einziger Pfad, damit der Daumen aus der Fläche herauswächst
        // statt danebenzukleben. Gezeichnet statt als Emoji, weil jede
        // Plattform ein anderes liefert und keines diese Haltung hat. Die
        // Verläufe brauchen je Hand eigene IDs, sonst greifen beide auf
        // dieselbe Definition zu.
        function handSvg(id) {
            var ref = function (name) { return 'url(#mb-' + name + '-' + id + ')'; };
            // Fläche und Daumen in einem Zug, damit der Daumen aus der Fläche
            // herauswächst statt danebenzukleben. Derselbe Pfad beschneidet
            // die Schattierung - sonst malen die Glanzlichter über die
            // Silhouette hinaus und werden auf dem dunklen Grund zu Schleiern.
            var palm = 'M8 34 C18 26 42 23 66 24 C80 25 84 26 90 25' +
                ' C112 13 158 12 192 30 C212 41 216 62 214 82' +
                ' C212 104 200 118 172 124 C140 131 108 128 92 120' +
                ' C78 113 70 100 64 84 C58 70 50 60 36 56' +
                ' C20 51 0 48 8 34 Z';
            return '<svg class="mb-celebrate__hand-svg" viewBox="0 8 220 206" aria-hidden="true" focusable="false">' +
                '<defs>' +
                    '<linearGradient id="mb-palm-' + id + '" x1="0.25" y1="0" x2="0.7" y2="1">' +
                        '<stop offset="0" stop-color="#f8c5a6"/>' +
                        '<stop offset="0.45" stop-color="#e2a37f"/>' +
                        '<stop offset="1" stop-color="#bb7a59"/>' +
                    '</linearGradient>' +
                    '<linearGradient id="mb-finger-' + id + '" x1="0.35" y1="0" x2="0.65" y2="1">' +
                        '<stop offset="0" stop-color="#935c40"/>' +
                        '<stop offset="0.3" stop-color="#e0a37f"/>' +
                        '<stop offset="1" stop-color="#c58764"/>' +
                    '</linearGradient>' +
                    '<radialGradient id="mb-dip-' + id + '">' +
                        '<stop offset="0" stop-color="#9c5e42" stop-opacity="0.4"/>' +
                        '<stop offset="1" stop-color="#9c5e42" stop-opacity="0"/>' +
                    '</radialGradient>' +
                    '<radialGradient id="mb-mound-' + id + '">' +
                        '<stop offset="0" stop-color="#ffdfc6" stop-opacity="0.7"/>' +
                        '<stop offset="1" stop-color="#ffdfc6" stop-opacity="0"/>' +
                    '</radialGradient>' +
                    '<clipPath id="mb-clip-' + id + '"><path d="' + palm + '"/></clipPath>' +
                '</defs>' +
                // Finger, hinter der Fläche, damit die Ansätze verdeckt sind
                '<g fill="none" stroke="' + ref('finger') + '" stroke-linecap="round">' +
                    '<path d="M96 106 C84 128 66 156 52 182" stroke-width="30"/>' +
                    '<path d="M126 114 C122 142 114 170 104 196" stroke-width="31"/>' +
                    '<path d="M156 114 C156 140 152 168 146 192" stroke-width="29"/>' +
                    '<path d="M184 106 C192 128 194 152 190 172" stroke-width="25"/>' +
                '</g>' +
                '<path d="' + palm + '" fill="' + ref('palm') + '"/>' +
                // Mulde, Daumenballen, Fingerwurzeln, Glanzkanten
                '<g clip-path="url(#mb-clip-' + id + ')">' +
                    '<ellipse cx="150" cy="72" rx="44" ry="30" fill="' + ref('dip') + '"/>' +
                    '<ellipse cx="88" cy="88" rx="24" ry="30" fill="' + ref('mound') + '" transform="rotate(-25 88 88)"/>' +
                    '<g fill="#ffdcc0" opacity="0.26">' +
                        '<ellipse cx="98" cy="104" rx="15" ry="8"/>' +
                        '<ellipse cx="127" cy="112" rx="15" ry="8"/>' +
                        '<ellipse cx="157" cy="112" rx="15" ry="8"/>' +
                        '<ellipse cx="183" cy="102" rx="12" ry="7"/>' +
                    '</g>' +
                    '<ellipse cx="150" cy="26" rx="44" ry="11" fill="#ffffff" opacity="0.22"/>' +
                    '<ellipse cx="42" cy="33" rx="30" ry="8" fill="#ffffff" opacity="0.2" transform="rotate(-4 42 33)"/>' +
                '</g>' +
                // Fingerglieder, kurz genug um im Finger zu bleiben
                '<g fill="none" stroke="#95593d" stroke-width="3" stroke-linecap="round" opacity="0.25">' +
                    '<path d="M67 135 L85 145"/><path d="M55 156 L73 166"/>' +
                    '<path d="M106 148 L126 154"/><path d="M100 170 L120 176"/>' +
                    '<path d="M142 148 L162 150"/><path d="M139 169 L159 171"/>' +
                    '<path d="M178 137 L196 135"/><path d="M179 155 L197 153"/>' +
                '</g>' +
            '</svg>';
        }

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
                '<span class="mb-celebrate__hand">' + handSvg('a') + '</span>' +
                '<span class="mb-celebrate__digit">6</span>' +
                '<span class="mb-celebrate__digit mb-celebrate__digit--seven">7</span>' +
                '<span class="mb-celebrate__hand mb-celebrate__hand--right">' + handSvg('b') + '</span>';

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
