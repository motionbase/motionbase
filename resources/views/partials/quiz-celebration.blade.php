{{-- Full screen celebration for a quiz result. One implementation for all
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

    .mb-celebrate__tally {
        margin: 0.5rem 0 0;
        font-size: 0.9375rem;
        color: #ffffff;
        opacity: 0;
        transition: opacity 300ms ease;
    }
    .mb-celebrate__tally--in { opacity: 1; }
    .mb-celebrate__tally b {
        color: #ff0055;
        font-weight: 600;
    }

    .mb-celebrate__hint {
        position: absolute;
        left: 50%;
        bottom: 2rem;
        transform: translateX(-50%);
        padding: 0 1.5rem;
        text-align: center;
        font-size: 0.75rem;
        color: #71717a;
        opacity: 0;
        transition: opacity 400ms ease;
    }
    .mb-celebrate__hint--in { opacity: 1; }

    /* Die Figur. Eigenes Variablen-Präfix, weil dieses Overlay im LTI-Embed in
       einer fremden Seite läuft und dort nichts überschreiben darf - die
       Vorlage setzte die Farben noch auf :root. */
    .mb-celebrate__figure {
        --mbf-ink: #111113;
        --mbf-skin: #ffffff;
        --mbf-accent: #ff0055;
        width: min(80vw, 46vh);
        max-width: 420px;
        aspect-ratio: 1 / 1;
        margin: 0 auto;
        border-radius: 24px;
        outline: none;
        touch-action: manipulation;
        -webkit-tap-highlight-color: transparent;
        user-select: none;
        opacity: 0;
        transform: scale(0.88);
        transition: opacity 420ms cubic-bezier(0, 0, 0.58, 1),
                    transform 520ms cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .mb-celebrate__figure--in {
        opacity: 1;
        transform: scale(1);
    }
    .mb-celebrate__figure--live {
        cursor: pointer;
        pointer-events: auto;
    }
    .mb-celebrate__figure:focus-visible {
        box-shadow: 0 0 0 4px rgba(9, 9, 11, 0.9), 0 0 0 7px #ffffff;
    }
    .mb-celebrate__figure svg {
        width: 100%;
        height: 100%;
        display: block;
        overflow: visible;
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
        .mb-celebrate__tally,
        .mb-celebrate__figure,
        .mb-celebrate__hint { transition: none; animation: none; }
        .mb-celebrate__score,
        .mb-celebrate__title,
        .mb-celebrate__sub { transform: none; opacity: 1; }
        .mb-celebrate__figure { transform: none; }
    }
</style>
<script>
    (function () {
        var TOTAL = 6500;      // Gesamtdauer der Variante ohne Figur
        var NS = 'http://www.w3.org/2000/svg';
        var running = false;

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

        /* ---------------------------------------------------------------
           Die Figur

           Jede ID trägt ein Präfix: clip-path und fill greifen über
           url(#...) auf das erste Element dieser ID im ganzen Dokument zu,
           und das Overlay hängt im Embed in einer fremden Seite.
           --------------------------------------------------------------- */

        var FIGURE_SVG = [
            '<svg viewBox="0 0 560 560" aria-hidden="true">',
              '<defs>',
                '<clipPath id="mbf-clip-blob"><path id="mbf-blob-a"/></clipPath>',
                // Arme sind innerhalb der Form oder oberhalb der Schultern sichtbar
                '<clipPath id="mbf-clip-arm">',
                  '<path id="mbf-blob-b"/>',
                  '<rect x="-600" y="-600" width="1760" height="1040"/>',
                '</clipPath>',
              '</defs>',

              '<path id="mbf-blob" fill="var(--mbf-accent)"/>',

              '<g id="mbf-figure">',
                // Körper
                '<g clip-path="url(#mbf-clip-blob)" stroke="var(--mbf-ink)" stroke-linecap="round" stroke-linejoin="round">',
                  '<path d="M252 280 L320 280 L326 392 L244 392 Z" fill="var(--mbf-skin)" stroke="none"/>',
                  '<path d="M140 480 C150 412 190 382 244 352 Q285 390 326 352 C382 380 414 412 424 480 Z" fill="var(--mbf-accent)" stroke-width="5"/>',
                  '<path d="M236 408 L231 480" fill="none" stroke-width="5"/>',
                  '<path d="M332 408 L337 480" fill="none" stroke-width="5"/>',
                '</g>',

                // Kopf
                '<g id="mbf-head">',
                  '<g id="mbf-ear">',
                    '<path d="M-2 -26 C-22 -30 -30 -8 -22 10 C-16 24 -2 26 6 20 Z" fill="var(--mbf-skin)" stroke="var(--mbf-ink)" stroke-width="5" stroke-linejoin="round"/>',
                    '<path d="M-6 -10 C-14 -10 -16 2 -8 8" fill="none" stroke="var(--mbf-ink)" stroke-width="4" stroke-linecap="round"/>',
                  '</g>',

                  '<path d="M234 196 C234 150 258 136 288 136 C322 136 342 158 342 196 L342 250 C342 296 318 316 288 316 C256 316 234 294 234 252 Z" fill="var(--mbf-skin)"/>',

                  '<g id="mbf-face" stroke="var(--mbf-ink)" stroke-linecap="round" stroke-linejoin="round" fill="none">',
                    // Profil: Nase, Wange, Kinn
                    '<path d="M316 190 C334 192 343 202 344 216 C356 230 366 244 362 258 C359 267 352 270 348 273 C353 284 355 297 349 306 C340 318 318 322 292 316 L300 230 Z" fill="var(--mbf-skin)" stroke="none"/>',
                    '<path d="M262 300 C282 318 316 322 342 312" stroke-width="5"/>',
                    '<path id="mbf-brow-l" d="M266 190 Q276 180 288 188" stroke-width="4.5"/>',
                    '<path id="mbf-brow-r" d="M304 186 Q315 177 326 185" stroke-width="4.5"/>',

                    '<g id="mbf-eyes-open">',
                      '<g id="mbf-eye-l"><ellipse id="mbf-pupil-l" cx="278" cy="211" rx="4" ry="5.5" fill="var(--mbf-ink)" stroke="none"/></g>',
                      '<g id="mbf-eye-r"><ellipse id="mbf-pupil-r" cx="315" cy="208" rx="4" ry="5.5" fill="var(--mbf-ink)" stroke="none"/></g>',
                    '</g>',
                    '<g id="mbf-eyes-happy" opacity="0">',
                      '<path d="M268 214 Q278 202 288 213" stroke-width="4.5"/>',
                      '<path d="M305 211 Q315 199 325 210" stroke-width="4.5"/>',
                    '</g>',

                    '<path d="M308 214 C320 230 334 238 340 250 C346 264 330 272 318 262" stroke-width="5" fill="var(--mbf-skin)"/>',

                    '<path id="mbf-mouth-idle" d="M284 274 Q302 292 326 276" stroke-width="5"/>',
                    '<g id="mbf-mouth-open" opacity="0">',
                      '<path d="M282 270 Q304 274 330 266 Q328 304 304 305 Q284 304 282 270 Z" fill="var(--mbf-ink)" stroke-width="3"/>',
                      '<path d="M288 274 Q305 277 324 272 L323 281 Q305 285 289 283 Z" fill="var(--mbf-skin)" stroke="none"/>',
                      '<path d="M294 298 Q306 290 318 296 Q312 302 304 302 Q298 302 294 298 Z" fill="#ff8fb1" stroke="none"/>',
                    '</g>',
                  '</g>',

                  '<path id="mbf-hair" fill="var(--mbf-ink)" d="M230 214 C222 196 222 168 236 148 C252 124 282 116 310 120 C336 124 350 142 348 166 C347 176 342 180 336 176 C324 178 306 172 294 166 C278 170 260 174 252 186 C248 194 248 206 246 222 C240 226 234 222 230 214 Z"/>',
                '</g>',

                // Arme
                '<g clip-path="url(#mbf-clip-arm)">',
                  '<g id="mbf-arm-l"></g>',
                  '<g id="mbf-arm-r"></g>',
                '</g>',

                '<g id="mbf-sparkles" fill="none" stroke="var(--mbf-ink)" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"></g>',
              '</g>',
            '</svg>'
        ].join('');

        // Handfläche, vier Finger, Daumen - zweimal gezeichnet, einmal als
        // dicke Kontur und einmal gefüllt, daher die gemeinsame Liste.
        var HAND_PARTS = [
            ['rect', { x: -23, y: -50, width: 46, height: 52, rx: 15 }],
            ['rect', { x: -23, y: -84, width: 12, height: 46, rx: 6 }],
            ['rect', { x: -11.5, y: -93, width: 12, height: 54, rx: 6 }],
            ['rect', { x: 0, y: -89, width: 12, height: 50, rx: 6 }],
            ['rect', { x: 11.5, y: -76, width: 11.5, height: 38, rx: 5.75 }],
            ['rect', { x: -6.5, y: -36, width: 13, height: 36, rx: 6.5, transform: 'translate(-19,-12) rotate(-42)' }]
        ];

        var SHAPES = ['circle', 'star', 'tri', 'dot', 'plus', 'circle'];

        function el(tag, attrs, parent) {
            var node = document.createElementNS(NS, tag);
            for (var k in attrs) node.setAttribute(k, attrs[k]);
            if (parent) parent.appendChild(node);
            return node;
        }

        function starPath(r) {
            var d = '';
            for (var i = 0; i < 10; i++) {
                var rr = i % 2 ? r * 0.45 : r;
                var t = -Math.PI / 2 + i * Math.PI / 5;
                d += (i ? 'L' : 'M') + (Math.cos(t) * rr).toFixed(2) + ' ' + (Math.sin(t) * rr).toFixed(2);
            }
            return d + 'Z';
        }

        function createFigure(mode, reduced, onHighFive) {
            var sixSeven = mode === 'six-seven';

            var root = document.createElement('div');
            root.className = 'mb-celebrate__figure';
            root.innerHTML = FIGURE_SVG;

            var svg = root.querySelector('svg');
            var q = function (id) { return root.querySelector('#mbf-' + id); };

            // Hintergrundform: Superellipse, oben eine Spur breiter
            (function () {
                var cx = 280, cy = 272, rx = 180, ry = 178, n = 2.9;
                var d = '';
                for (var i = 0; i <= 72; i++) {
                    var t = (i / 72) * Math.PI * 2;
                    var c = Math.cos(t), s = Math.sin(t);
                    var x = cx + rx * Math.sign(c) * Math.pow(Math.abs(c), 2 / n);
                    var y = cy + ry * Math.sign(s) * Math.pow(Math.abs(s), 2 / n) * (s < 0 ? 1.0 : 0.98);
                    d += (i ? 'L' : 'M') + x.toFixed(1) + ' ' + y.toFixed(1);
                }
                d += 'Z';
                q('blob').setAttribute('d', d);
                q('blob-a').setAttribute('d', d);
                q('blob-b').setAttribute('d', d);
            })();

            function buildArm(group, mirror) {
                var arm = el('path', { fill: 'none', stroke: 'var(--mbf-skin)', 'stroke-width': 30, 'stroke-linecap': 'round' }, group);
                var sleeveOut = el('path', { fill: 'none', stroke: 'var(--mbf-ink)', 'stroke-width': 46, 'stroke-linecap': 'round' }, group);
                var sleeveIn = el('path', { fill: 'none', stroke: 'var(--mbf-accent)', 'stroke-width': 36, 'stroke-linecap': 'round' }, group);

                var hand = el('g', {}, group);
                var inner = el('g', {}, hand);
                var outline = el('g', { fill: 'var(--mbf-ink)', stroke: 'var(--mbf-ink)', 'stroke-width': 10 }, inner);
                var fill = el('g', { fill: 'var(--mbf-skin)' }, inner);

                HAND_PARTS.forEach(function (part) {
                    el(part[0], part[1], outline);
                    el(part[0], part[1], fill);
                });
                el('rect', { x: -14, y: -14, width: 28, height: 30 }, fill);

                var lines = el('g', { fill: 'none', stroke: 'var(--mbf-ink)', 'stroke-width': 3.5, 'stroke-linecap': 'round' }, inner);
                el('path', { d: 'M-11.5 -44 L-11.5 -74' }, lines);
                el('path', { d: 'M0 -44 L0 -80' }, lines);
                el('path', { d: 'M11.5 -44 L11.5 -68' }, lines);
                el('path', { d: 'M-4 -18 Q4 -12 12 -20' }, lines);
                el('path', { d: 'M-12 -30 Q-8 -24 -12 -16' }, lines);

                return { arm: arm, sleeveOut: sleeveOut, sleeveIn: sleeveIn, hand: hand, inner: inner, mirror: mirror };
            }

            var arms = {
                L: buildArm(q('arm-l'), true),
                R: buildArm(q('arm-r'), false)
            };
            arms.L.S = { x: 226, y: 506 };
            arms.R.S = { x: 334, y: 506 };

            for (var side in arms) {
                arms[side].p = 0;
                arms[side].v = 0;
                arms[side].goal = 0;
                arms[side].contact = false;
                arms[side].holdUntil = 0;
                arms[side].squash = 0;
            }

            // Beim Sechs-Sieben stehen die Ziele fest: beide Unterarme nach
            // aussen, ungefähr waagerecht, damit die Handflächen zur Seite
            // zeigen statt nach oben.
            arms.L.T = sixSeven ? { x: 128, y: 344 } : { x: 150, y: 150 };
            arms.R.T = sixSeven ? { x: 432, y: 344 } : { x: 410, y: 150 };

            var HEAD_C = { x: 288, y: 222 };
            var look = { x: 0.35, y: -0.05 };
            var lookGoal = { x: 0.35, y: -0.05 };
            var flip = 1, flipGoal = 1;
            var happy = 0, happyGoal = sixSeven ? 1 : 0;
            var pointerInside = false;
            var nextBlink = performance.now() + 2200;
            var blinkT = -1;
            var particles = [];
            var startedAt = performance.now();
            var lastNow = startedAt;

            var lerp = function (a, b, t) { return a + (b - a) * t; };
            var ease = function (cur, goal, rate, dt) { return cur + (goal - cur) * (1 - Math.exp(-rate * dt)); };
            var fmt = function (n) { return n.toFixed(2); };

            function toSvg(clientX, clientY) {
                var pt = svg.createSVGPoint();
                pt.x = clientX;
                pt.y = clientY;
                var m = svg.getScreenCTM();
                return m ? pt.matrixTransform(m.inverse()) : { x: 280, y: 280 };
            }

            function setLookAt(p) {
                var dx = (p.x - HEAD_C.x) / 230;
                var dy = (p.y - HEAD_C.y) / 230;
                var len = Math.hypot(dx, dy);
                if (len > 1) { dx /= len; dy /= len; }
                lookGoal.x = dx;
                lookGoal.y = dy;
            }

            function sparkle(pos, dir) {
                var layer = q('sparkles');
                var base = Math.atan2(dir.y, dir.x);

                SHAPES.forEach(function (type, i) {
                    var g = el('g', {}, layer);
                    if (type === 'circle') el('circle', { r: 5.5 }, g);
                    if (type === 'dot') el('circle', { r: 3, fill: 'var(--mbf-ink)', stroke: 'none' }, g);
                    if (type === 'star') el('path', { d: starPath(8) }, g);
                    if (type === 'tri') el('path', { d: 'M0 -7 L6.5 5 L-6.5 5 Z' }, g);
                    if (type === 'plus') el('path', { d: 'M0 -6 L0 6 M-6 0 L6 0' }, g);

                    var ang = base + (i - (SHAPES.length - 1) / 2) * 0.62 + (Math.random() - 0.5) * 0.3;
                    var speed = 140 + Math.random() * 90;

                    particles.push({
                        g: g,
                        x: pos.x + Math.cos(ang) * 30,
                        y: pos.y + Math.sin(ang) * 30,
                        vx: Math.cos(ang) * speed,
                        vy: Math.sin(ang) * speed,
                        rot: Math.random() * 360,
                        vr: (Math.random() - 0.5) * 360,
                        age: 0,
                        life: 0.55 + Math.random() * 0.2
                    });
                });
            }

            function drawArm(a, now) {
                if (a.p < 0.001 && a.goal === 0) {
                    a.hand.setAttribute('visibility', 'hidden');
                    a.arm.setAttribute('d', '');
                    a.sleeveOut.setAttribute('d', '');
                    a.sleeveIn.setAttribute('d', '');
                    return;
                }
                a.hand.setAttribute('visibility', 'visible');

                var side = a.mirror ? -1 : 1;
                var rest = { x: a.S.x + side * 70, y: a.S.y + 260 };
                var H = { x: lerp(rest.x, a.T.x, a.p), y: lerp(rest.y, a.T.y, a.p) };

                var dx = H.x - a.S.x, dy = H.y - a.S.y;
                var len = Math.max(1, Math.hypot(dx, dy));
                var ux = dx / len, uy = dy / len;
                var W = { x: H.x - ux * 40, y: H.y - uy * 40 };

                var px = -uy * side, py = ux * side;
                var bend = 22;
                var C = { x: (a.S.x + W.x) / 2 + px * bend, y: (a.S.y + W.y) / 2 + py * bend };
                a.arm.setAttribute('d', 'M' + fmt(a.S.x) + ' ' + fmt(a.S.y) + ' Q' + fmt(C.x) + ' ' + fmt(C.y) + ' ' + fmt(W.x) + ' ' + fmt(W.y));

                var tx = C.x - a.S.x, ty = C.y - a.S.y;
                var tl = Math.hypot(tx, ty) || 1;
                tx /= tl; ty /= tl;
                var sleeve = 'M' + fmt(a.S.x) + ' ' + fmt(a.S.y) + ' L' + fmt(a.S.x + tx * 92) + ' ' + fmt(a.S.y + ty * 92);
                a.sleeveOut.setAttribute('d', sleeve);
                a.sleeveIn.setAttribute('d', sleeve);

                var ang = Math.atan2(W.y - C.y, W.x - C.x) * 180 / Math.PI + 90;
                a.hand.setAttribute('transform', 'translate(' + fmt(W.x) + ' ' + fmt(W.y) + ') rotate(' + fmt(ang) + ')');

                a.squash = Math.max(0, a.squash - (now - lastNow) / 160);
                var s = a.squash;
                var sx = (a.mirror ? -1 : 1) * (1 + 0.14 * s);
                var sy = 1 - 0.12 * s;
                a.inner.setAttribute('transform', 'translate(0 ' + fmt(-40 * (1 - sy)) + ') scale(' + fmt(sx * 1.15) + ' ' + fmt(sy * 1.15) + ')');

                a.handPos = H;
                a.dir = { x: ux, y: uy };
            }

            function contact(a, now) {
                happyGoal = 1;
                a.squash = 1;
                a.holdUntil = now + 480;
                if (!reduced) sparkle(a.handPos, a.dir);
                if (onHighFive) onHighFive();
            }

            return {
                node: root,

                // Hand hoch in Richtung Zeiger - die Seite ergibt sich daraus,
                // auf welcher Hälfte geklickt wurde.
                highFive: function (clientX, clientY) {
                    if (sixSeven) return;

                    var p = clientX === undefined ? { x: 405, y: 150 } : toSvg(clientX, clientY);
                    var key = p.x < HEAD_C.x - 10 ? 'L' : 'R';
                    var a = arms[key];
                    var other = arms[key === 'L' ? 'R' : 'L'];

                    var T = { x: Math.max(40, Math.min(520, p.x)), y: Math.max(72, Math.min(p.y, 360)) };
                    var dx = T.x - a.S.x, dy = T.y - a.S.y;
                    var d = Math.hypot(dx, dy);
                    var k = Math.max(210, Math.min(470, d)) / d;
                    a.T = { x: a.S.x + dx * k, y: Math.max(72, a.S.y + dy * k) };

                    a.goal = 1;
                    a.contact = false;
                    a.holdUntil = Infinity;
                    if (a.p > 0.9) a.p = 0.75;   // erneuter Klick: kurz zurück, dann nochmals klatschen
                    other.goal = 0;
                    other.holdUntil = 0;

                    setLookAt(a.T);
                },

                pointerAt: function (clientX, clientY) {
                    pointerInside = true;
                    setLookAt(toSvg(clientX, clientY));
                },

                pointerAway: function () {
                    pointerInside = false;
                    lookGoal.x = 0.35;
                    lookGoal.y = -0.05;
                },

                tick: function (now, dt) {
                    var key, a;

                    if (sixSeven) {
                        // Direkt getrieben statt über die Feder: beide Arme
                        // wippen im Gegentakt, eingeblendet über eine Rampe,
                        // damit sie nicht aus dem Stand hochschnellen.
                        var ramp = Math.min(1, (now - startedAt) / 520);
                        ramp = ramp * ramp * (3 - 2 * ramp);
                        var bob = reduced ? 0 : Math.sin((now - startedAt) / 1000 * 5);

                        arms.L.goal = 1;
                        arms.R.goal = 1;
                        arms.L.p = ramp * (0.88 + 0.12 * bob);
                        arms.R.p = ramp * (0.88 - 0.12 * bob);
                        drawArm(arms.L, now);
                        drawArm(arms.R, now);
                    } else {
                        for (key in arms) {
                            a = arms[key];
                            if (a.goal === 1 && now > a.holdUntil) a.goal = 0;

                            if (reduced) {
                                a.p = ease(a.p, a.goal, 14, dt);
                                a.v = 0;
                            } else {
                                var stiffness = a.goal ? 170 : 90;
                                var damping = a.goal ? 15 : 17;
                                a.v += (stiffness * (a.goal - a.p) - damping * a.v) * dt;
                                a.p += a.v * dt;
                            }

                            if (a.goal === 0 && a.p < 0.002 && Math.abs(a.v) < 0.01) { a.p = 0; a.v = 0; }
                            if (a.goal === 1 && !a.contact && a.p > 0.96) {
                                a.contact = true;
                                drawArm(a, now);
                                contact(a, now);
                            }
                            drawArm(a, now);
                        }

                        var armUp = Math.max(arms.L.p, arms.R.p);
                        if (armUp < 0.45 && happyGoal === 1 && arms.L.goal === 0 && arms.R.goal === 0) happyGoal = 0;
                        if (!pointerInside && armUp < 0.05) { lookGoal.x = 0.35; lookGoal.y = -0.05; }
                    }

                    lastNow = now;

                    look.x = ease(look.x, lookGoal.x, 9, dt);
                    look.y = ease(look.y, lookGoal.y, 9, dt);
                    if (lookGoal.x < -0.12) flipGoal = -1;
                    else if (lookGoal.x > 0.12) flipGoal = 1;
                    flip = ease(flip, flipGoal, 16, dt);
                    happy = ease(happy, happyGoal, 18, dt);

                    var breathe = reduced ? 0 : Math.sin(now / 900) * 1.6;
                    var lx = look.x, ly = look.y;

                    q('figure').setAttribute('transform', 'translate(0 ' + fmt(breathe * 0.4) + ')');
                    q('head').setAttribute('transform',
                        'translate(' + fmt(lx * 5) + ' ' + fmt(ly * 4 + breathe * 0.6 - happy * 3) + ') rotate(' + fmt(lx * 4 - happy * 3 * flip) + ' 288 300)');

                    // Das Gesicht wandert stärker als der Kopf und dreht mit
                    var fx = 300;
                    var fScale = Math.sign(flip) * Math.max(0.08, Math.abs(flip));
                    q('face').setAttribute('transform',
                        'translate(' + fmt(lx * 14 + fx) + ' ' + fmt(ly * 10) + ') scale(' + fmt(fScale) + ' 1) translate(' + (-fx) + ' 0)');
                    q('hair').setAttribute('transform', 'translate(' + fmt(lx * 4) + ' ' + fmt(ly * 3) + ')');
                    q('ear').setAttribute('transform',
                        'translate(' + fmt(288 - flip * 52 + lx * -3) + ' ' + fmt(240 + ly * 4) + ') scale(' + fmt(flip) + ' 1)');

                    var ppx = lx * 3 * Math.sign(flip), ppy = ly * 3;
                    q('pupil-l').setAttribute('transform', 'translate(' + fmt(ppx) + ' ' + fmt(ppy) + ')');
                    q('pupil-r').setAttribute('transform', 'translate(' + fmt(ppx) + ' ' + fmt(ppy) + ')');

                    var eyeScale = 1;
                    if (!reduced) {
                        if (now > nextBlink && blinkT < 0) blinkT = 0;
                        if (blinkT >= 0) {
                            blinkT += dt;
                            var b = blinkT / 0.14;
                            eyeScale = b < 0.5 ? 1 - b * 1.8 : 0.1 + (b - 0.5) * 1.8;
                            if (b >= 1) { blinkT = -1; eyeScale = 1; nextBlink = now + 2200 + Math.random() * 3000; }
                        }
                    }
                    q('eye-l').setAttribute('transform', 'translate(0 ' + fmt(211 * (1 - eyeScale)) + ') scale(1 ' + fmt(eyeScale) + ')');
                    q('eye-r').setAttribute('transform', 'translate(0 ' + fmt(208 * (1 - eyeScale)) + ') scale(1 ' + fmt(eyeScale) + ')');

                    q('eyes-open').setAttribute('opacity', fmt(1 - happy));
                    q('eyes-happy').setAttribute('opacity', fmt(happy));
                    q('mouth-idle').setAttribute('opacity', fmt(1 - happy));
                    q('mouth-open').setAttribute('opacity', fmt(happy));
                    q('mouth-open').setAttribute('transform', 'translate(304 270) scale(1 ' + fmt(0.4 + 0.6 * happy) + ') translate(-304 -270)');
                    q('brow-l').setAttribute('transform', 'translate(0 ' + fmt(-happy * 4) + ')');
                    q('brow-r').setAttribute('transform', 'translate(0 ' + fmt(-happy * 4) + ')');

                    for (var i = particles.length - 1; i >= 0; i--) {
                        var pt = particles[i];
                        pt.age += dt;
                        var k2 = pt.age / pt.life;
                        if (k2 >= 1) { pt.g.remove(); particles.splice(i, 1); continue; }
                        pt.vx *= Math.pow(0.02, dt);
                        pt.vy *= Math.pow(0.02, dt);
                        pt.x += pt.vx * dt;
                        pt.y += pt.vy * dt;
                        pt.rot += pt.vr * dt;
                        var sc = k2 < 0.25 ? k2 / 0.25 : 1 - (k2 - 0.25) / 0.75 * 0.6;
                        pt.g.setAttribute('transform', 'translate(' + fmt(pt.x) + ' ' + fmt(pt.y) + ') rotate(' + fmt(pt.rot) + ') scale(' + fmt(sc) + ')');
                        pt.g.setAttribute('opacity', fmt(k2 < 0.7 ? 1 : 1 - (k2 - 0.7) / 0.3));
                    }
                }
            };
        }

        /* --------------------------------------------------------------- */

        window.motionbaseCelebrate = function (options) {
            if (running) return;

            var opts = options || {};
            var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            running = true;

            var mode = opts.variant === 'six-seven' ? 'six-seven'
                : (opts.variant === 'high-five' ? 'high-five' : null);
            var interactive = mode === 'high-five';

            var overlay = document.createElement('div');
            overlay.className = 'mb-celebrate';
            overlay.setAttribute('role', 'status');
            overlay.setAttribute('aria-live', 'polite');

            var canvas = document.createElement('canvas');
            canvas.className = 'mb-celebrate__canvas';
            canvas.setAttribute('aria-hidden', 'true');

            var center = document.createElement('div');
            center.className = 'mb-celebrate__center';

            var score = document.createElement('p');
            score.className = 'mb-celebrate__score';
            score.textContent = opts.score || '100%';

            var title = document.createElement('p');
            title.className = 'mb-celebrate__title';
            title.textContent = opts.title || 'Alles richtig.';

            var sub = document.createElement('p');
            sub.className = 'mb-celebrate__sub';
            sub.textContent = opts.subtitle || '';

            var tally = document.createElement('p');
            tally.className = 'mb-celebrate__tally';

            var hint = document.createElement('p');
            hint.className = 'mb-celebrate__hint';
            hint.textContent = interactive
                ? 'Klick die Figur für ein High Five · Escape schließt'
                : 'Klicken zum Schließen';

            var highFives = 0;

            var figure = mode ? createFigure(mode, reduced, function () {
                highFives++;
                tally.innerHTML = 'High Fives: <b></b>';
                tally.querySelector('b').textContent = highFives;
                tally.classList.add('mb-celebrate__tally--in');
            }) : null;

            if (figure) {
                center.appendChild(figure.node);
                if (interactive) {
                    figure.node.classList.add('mb-celebrate__figure--live');
                    figure.node.setAttribute('role', 'button');
                    figure.node.setAttribute('tabindex', '0');
                    figure.node.setAttribute('aria-label', 'Figur. Klicken oder Enter drücken für ein High Five.');
                }
            } else {
                center.appendChild(score);
            }

            center.appendChild(title);
            if (sub.textContent) center.appendChild(sub);
            if (interactive) center.appendChild(tally);
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

            function onPointerMove(e) { if (figure) figure.pointerAt(e.clientX, e.clientY); }

            function close() {
                if (closed) return;
                closed = true;

                timers.forEach(clearTimeout);
                window.removeEventListener('resize', resize);
                window.removeEventListener('pointermove', onPointerMove);
                document.removeEventListener('keydown', onKey);

                overlay.classList.remove('mb-celebrate--in');
                setTimeout(function () {
                    if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
                    running = false;
                }, reduced ? 0 : 360);
            }

            // Escape schließt immer. Solange die Figur mitspielt, schließt
            // sonst keine Taste - man soll mit Tab hinfinden und mit Enter
            // abklatschen können, ohne dass dabei alles verschwindet.
            function onKey(e) {
                if (e.key === 'Escape') { close(); return; }
                if (!interactive) close();
            }

            overlay.addEventListener('click', close);
            document.addEventListener('keydown', onKey);
            if (figure) window.addEventListener('pointermove', onPointerMove);

            if (interactive) {
                figure.node.addEventListener('pointerdown', function (e) {
                    if (e.button !== undefined && e.button !== 0) return;
                    e.stopPropagation();          // sonst schließt der Klick das Overlay
                    figure.highFive(e.clientX, e.clientY);
                });
                figure.node.addEventListener('click', function (e) { e.stopPropagation(); });
                figure.node.addEventListener('keydown', function (e) {
                    if (e.key !== 'Enter' && e.key !== ' ') return;
                    e.preventDefault();
                    e.stopPropagation();
                    figure.highFive();
                });
            }

            // Auftakt
            requestAnimationFrame(function () { overlay.classList.add('mb-celebrate--in'); });

            if (reduced && !figure) {
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

            if (figure) {
                // Nicht über requestAnimationFrame einblenden: ein Tab ohne
                // Frames ließe die Figur sonst unsichtbar stehen.
                later(function () { figure.node.classList.add('mb-celebrate__figure--in'); }, 60);
            }

            later(function () { title.classList.add('mb-celebrate__title--in'); }, figure ? 700 : 900);
            later(function () { sub.classList.add('mb-celebrate__sub--in'); }, figure ? 880 : 1080);
            later(function () { hint.classList.add('mb-celebrate__hint--in'); }, figure ? 1600 : 2200);

            if (!figure) {
                later(function () { score.classList.add('mb-celebrate__score--in'); }, 220);
                later(function () { burst(particles, width, height, 90, 1.5, 15); }, 160);
                later(function () { burst(particles, width, height, 60, 2.6, 12); }, 700);
                later(function () { burst(particles, width, height, 45, 3.0, 10); }, 1800);
                later(function () { burst(particles, width, height, 45, 3.0, 10); }, 2700);

                // Ohne Figur gibt es nichts zu tun: das Overlay geht von selbst zu.
                later(function () {
                    overlay.style.transition = 'opacity 900ms cubic-bezier(0.42, 0, 1, 1)';
                    overlay.classList.remove('mb-celebrate--in');
                }, TOTAL - 900);

                later(close, TOTAL);
            } else if (!reduced && interactive) {
                // Konfetti zum Auftakt, das High Five bringt danach seine
                // eigenen Funken mit.
                later(function () { burst(particles, width, height, 80, 1.5, 14); }, 200);
                later(function () { burst(particles, width, height, 50, 2.6, 11); }, 760);
            }

            var last = performance.now();

            function frame(now) {
                if (closed) return;

                var dt = Math.min(0.032, (now - last) / 1000);
                last = now;

                if (figure) figure.tick(now, dt);

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
