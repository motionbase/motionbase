<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MotionBase')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    {{-- Prism.js for syntax highlighting (light theme to match main app) --}}
    <link href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism.min.css" rel="stylesheet" />

    @vite(['resources/css/app.css'])

    <style>
        /* Ensure content fills the iframe - no viewport constraints for iframe embedding */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            height: auto;
            min-height: auto;
            overflow: hidden;
        }

        /* Remove any default scrollbars when in iframe */
        html {
            overflow-y: visible;
        }

        /* Prevent last element margins from causing scrollbar */
        body > div:last-child,
        main > *:last-child,
        article > *:last-child,
        .prose > *:last-child {
            margin-bottom: 0 !important;
        }

        /* Code block styling - override Prism defaults */
        .code-block {
            max-width: 100%;
            overflow: hidden;
            width: 100%;
        }

        .code-block > div:last-child {
            overflow-x: auto;
            max-width: 100%;
        }

        .code-block pre[class*="language-"],
        .code-block pre,
        pre[class*="language-"] {
            background: transparent !important;
            margin: 0 !important;
            padding: 1rem !important;
            border-radius: 0 !important;
            white-space: pre !important;
            word-wrap: normal !important;
            overflow: visible !important;
        }

        .code-block code[class*="language-"],
        .code-block code,
        code[class*="language-"] {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
            font-size: 0.875rem !important;
            background: transparent !important;
            white-space: pre !important;
            word-wrap: normal !important;
            display: block;
        }

        /* Ensure main content area constrains width */
        main {
            min-width: 0;
            overflow: hidden;
        }

        article {
            min-width: 0;
            overflow: hidden;
        }

        article > * {
            max-width: 100%;
        }

        /* Copy button success state */
        .copy-btn.copied {
            background-color: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        /* Alert styles */
        .alert-info { background-color: #f0f9ff; border-color: #bae6fd; color: #0c4a6e; }
        .alert-warning { background-color: #fffbeb; border-color: #fde68a; color: #78350f; }
        .alert-danger { background-color: #fff1f2; border-color: #fecdd3; color: #881337; }
        .alert-neutral { background-color: #ffffff; border-color: #e4e4e7; color: #18181b; }

        /* Quiz styles */
        .quiz-container {
            background: #fafafa;
            border: 1px solid #e4e4e7;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        .quiz-header {
            background: #f4f4f5;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e4e4e7;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .quiz-progress {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #71717a;
        }
        .quiz-progress-bar {
            width: 100px;
            height: 4px;
            background: #e4e4e7;
            border-radius: 2px;
            overflow: hidden;
        }
        .quiz-progress-fill {
            height: 100%;
            background: #18181b;
            transition: width 0.3s ease;
        }
        .quiz-body {
            padding: 1.5rem;
        }
        .quiz-question {
            font-size: 1.125rem;
            font-weight: 600;
            color: #18181b;
            margin-bottom: 1rem;
        }
        .quiz-question-image {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .quiz-answers {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .quiz-answer {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: white;
            border: 2px solid #e4e4e7;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .quiz-answer:hover:not(.quiz-answer--disabled) {
            border-color: #a1a1aa;
            background: #fafafa;
        }
        .quiz-answer--selected {
            border-color: #18181b;
            background: #f4f4f5;
        }
        .quiz-answer--correct {
            border-color: #22c55e !important;
            background: #f0fdf4 !important;
        }
        .quiz-answer--incorrect {
            border-color: #ef4444 !important;
            background: #fef2f2 !important;
        }
        .quiz-answer--disabled {
            cursor: default;
            opacity: 0.7;
        }
        .quiz-answer-indicator {
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 50%;
            border: 2px solid #d4d4d8;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .quiz-answer--selected .quiz-answer-indicator {
            border-color: #18181b;
            background: #18181b;
        }
        .quiz-answer--correct .quiz-answer-indicator {
            border-color: #22c55e;
            background: #22c55e;
        }
        .quiz-answer--incorrect .quiz-answer-indicator {
            border-color: #ef4444;
            background: #ef4444;
        }
        .quiz-answer-text {
            flex: 1;
            color: #3f3f46;
        }
        .quiz-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e4e4e7;
            display: flex;
            justify-content: flex-end;
        }
        .quiz-btn {
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .quiz-btn--primary {
            background: #18181b;
            color: white;
            border: none;
        }
        .quiz-btn--primary:hover {
            background: #27272a;
        }
        .quiz-btn--primary:disabled {
            background: #a1a1aa;
            cursor: not-allowed;
        }
        .quiz-btn--secondary {
            background: white;
            color: #18181b;
            border: 1px solid #e4e4e7;
        }
        .quiz-btn--secondary:hover {
            background: #f4f4f5;
        }
        .quiz-result {
            text-align: center;
            padding: 2rem 1.5rem;
        }
        .quiz-result-icon {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .quiz-result-icon--success {
            background: #dcfce7;
            color: #22c55e;
        }
        .quiz-result-icon--warning {
            background: #fef3c7;
            color: #f59e0b;
        }
        .quiz-result-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 0.5rem;
        }
        .quiz-result-score {
            font-size: 1rem;
            color: #71717a;
            margin-bottom: 1.5rem;
        }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-white text-zinc-900">
    @yield('content')

    @stack('scripts')

    {{-- Prism.js for syntax highlighting (order matters for dependencies!) --}}
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-markup.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-css.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-clike.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-markup-templating.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-typescript.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-jsx.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-tsx.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-python.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-bash.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-json.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-sql.min.js"></script>

    {{-- iframe-resizer library for platforms that support it --}}
    <script src="https://cdn.jsdelivr.net/npm/iframe-resizer@4.3.9/js/iframeResizer.contentWindow.min.js"></script>

    <script>
        (function() {
            // Track last sent height to prevent duplicates
            let lastSentHeight = 0;
            let debounceTimer = null;

            // Calculate content height by measuring actual content
            function getContentHeight() {
                const children = document.body.children;
                let maxBottom = 0;

                for (let i = 0; i < children.length; i++) {
                    const child = children[i];
                    if (child.tagName === 'SCRIPT') continue;

                    const rect = child.getBoundingClientRect();
                    const style = window.getComputedStyle(child);
                    const marginBottom = parseInt(style.marginBottom) || 0;

                    const bottom = rect.bottom + marginBottom;
                    if (bottom > maxBottom) {
                        maxBottom = bottom;
                    }
                }

                return Math.ceil(maxBottom) + 2;
            }

            // Send height to parent (debounced)
            function sendHeight(force) {
                if (debounceTimer) {
                    clearTimeout(debounceTimer);
                }

                debounceTimer = setTimeout(function() {
                    const height = getContentHeight();

                    if (!force && Math.abs(height - lastSentHeight) < 5) {
                        return;
                    }

                    lastSentHeight = height;

                    // Send to parent via postMessage
                    window.parent.postMessage(JSON.stringify({
                        subject: 'lti.frameResize',
                        height: height
                    }), '*');

                    window.parent.postMessage({
                        subject: 'lti.frameResize',
                        height: height
                    }, '*');
                }, 100);
            }

            window.sendHeight = sendHeight;

            // Highlight code blocks with Prism.js
            function highlightCode() {
                if (typeof Prism !== 'undefined') {
                    try {
                        Prism.highlightAll();
                    } catch (e) {
                        console.warn('Prism highlighting failed:', e);
                    }
                }
            }

            // Request parent to scroll iframe into view
            function scrollToTop() {
                window.scrollTo(0, 0);
                window.parent.postMessage(JSON.stringify({
                    subject: 'lti.scrollToTop'
                }), '*');
                window.parent.postMessage({
                    subject: 'lti.scrollToTop'
                }, '*');
            }

            // Initialize on DOM ready
            document.addEventListener('DOMContentLoaded', function() {
                highlightCode();
                scrollToTop();
                setTimeout(function() { sendHeight(true); }, 200);
            });

            window.addEventListener('load', function() {
                highlightCode();
                sendHeight(true);
                setTimeout(function() { sendHeight(true); }, 500);
            });

            document.querySelectorAll('img').forEach(function(img) {
                if (!img.complete) {
                    img.addEventListener('load', function() { sendHeight(); });
                }
            });

            // Copy code to clipboard
            window.copyCode = function(blockId) {
                const codeBlock = document.getElementById(blockId);
                if (!codeBlock) return;

                const code = codeBlock.textContent;
                const button = codeBlock.closest('.code-block').querySelector('.copy-btn');

                navigator.clipboard.writeText(code).then(function() {
                    button.classList.add('copied');
                    button.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="hidden sm:inline">Kopiert!</span>';
                    setTimeout(function() {
                        button.classList.remove('copied');
                        button.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect width="14" height="14" x="8" y="8" rx="2" ry="2" stroke-width="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" stroke-width="2"/></svg><span class="hidden sm:inline">Kopieren</span>';
                    }, 2000);
                }).catch(function() {
                    const textArea = document.createElement('textarea');
                    textArea.value = code;
                    textArea.style.position = 'fixed';
                    textArea.style.left = '-999999px';
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    button.classList.add('copied');
                    button.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="hidden sm:inline">Kopiert!</span>';
                    setTimeout(function() {
                        button.classList.remove('copied');
                        button.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect width="14" height="14" x="8" y="8" rx="2" ry="2" stroke-width="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" stroke-width="2"/></svg><span class="hidden sm:inline">Kopieren</span>';
                    }, 2000);
                });
            };

            // Quiz Renderer
            function initQuizzes() {
                document.querySelectorAll('.quiz-container').forEach(function(container) {
                    if (container.dataset.initialized) return;
                    container.dataset.initialized = 'true';

                    var questions = JSON.parse(container.dataset.questions);
                    if (!questions || questions.length === 0) return;

                    // Shuffle answers for each question
                    questions = questions.map(function(q) {
                        var shuffled = q.answers.slice();
                        for (var i = shuffled.length - 1; i > 0; i--) {
                            var j = Math.floor(Math.random() * (i + 1));
                            var temp = shuffled[i];
                            shuffled[i] = shuffled[j];
                            shuffled[j] = temp;
                        }
                        return { ...q, answers: shuffled };
                    });

                    var state = {
                        currentQuestion: 0,
                        selectedAnswer: null,
                        answered: false,
                        score: 0,
                        finished: false
                    };

                    function render() {
                        if (state.finished) {
                            renderResult();
                        } else {
                            renderQuestion();
                        }
                        setTimeout(function() { sendHeight(true); }, 50);
                    }

                    function renderQuestion() {
                        var q = questions[state.currentQuestion];
                        var total = questions.length;
                        var current = state.currentQuestion + 1;
                        var progress = (current / total) * 100;

                        var html = '<div class="quiz-header">';
                        html += '<div class="quiz-progress">';
                        html += '<span>Frage ' + current + ' von ' + total + '</span>';
                        html += '<div class="quiz-progress-bar"><div class="quiz-progress-fill" style="width: ' + progress + '%"></div></div>';
                        html += '</div></div>';

                        html += '<div class="quiz-body">';
                        html += '<div class="quiz-question">' + q.question + '</div>';

                        if (q.imageUrl) {
                            html += '<img src="' + q.imageUrl + '" class="quiz-question-image" alt="Frage Bild">';
                        }

                        html += '<div class="quiz-answers">';
                        q.answers.forEach(function(answer, idx) {
                            var classes = 'quiz-answer';
                            if (state.answered) {
                                classes += ' quiz-answer--disabled';
                                if (answer.isCorrect) {
                                    classes += ' quiz-answer--correct';
                                } else if (state.selectedAnswer === idx) {
                                    classes += ' quiz-answer--incorrect';
                                }
                            } else if (state.selectedAnswer === idx) {
                                classes += ' quiz-answer--selected';
                            }
                            html += '<div class="' + classes + '" data-index="' + idx + '">';
                            html += '<div class="quiz-answer-indicator">';
                            if (state.answered && (answer.isCorrect || state.selectedAnswer === idx)) {
                                html += '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">';
                                if (answer.isCorrect) {
                                    html += '<polyline points="20 6 9 17 4 12"/>';
                                } else {
                                    html += '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>';
                                }
                                html += '</svg>';
                            }
                            html += '</div>';
                            html += '<span class="quiz-answer-text">' + answer.text + '</span>';
                            html += '</div>';
                        });
                        html += '</div></div>';

                        html += '<div class="quiz-footer">';
                        if (!state.answered) {
                            html += '<button class="quiz-btn quiz-btn--primary" data-action="check" ' + (state.selectedAnswer === null ? 'disabled' : '') + '>Prüfen</button>';
                        } else if (state.currentQuestion < questions.length - 1) {
                            html += '<button class="quiz-btn quiz-btn--primary" data-action="next">Weiter</button>';
                        } else {
                            html += '<button class="quiz-btn quiz-btn--primary" data-action="finish">Ergebnis anzeigen</button>';
                        }
                        html += '</div>';

                        container.innerHTML = html;
                        attachEventListeners();
                    }

                    function renderResult() {
                        var percentage = Math.round((state.score / questions.length) * 100);
                        var iconClass = percentage >= 70 ? 'quiz-result-icon--success' : 'quiz-result-icon--warning';
                        var title = percentage === 100 ? 'Perfekt!' : (percentage >= 70 ? 'Gut gemacht!' : 'Weiter üben!');

                        var html = '<div class="quiz-result">';
                        html += '<div class="quiz-result-icon ' + iconClass + '">';
                        if (percentage >= 70) {
                            html += '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
                        } else {
                            html += '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>';
                        }
                        html += '</div>';
                        html += '<div class="quiz-result-title">' + title + '</div>';
                        html += '<div class="quiz-result-score">' + state.score + ' von ' + questions.length + ' richtig (' + percentage + '%)</div>';
                        html += '<button class="quiz-btn quiz-btn--secondary" data-action="restart">Quiz wiederholen</button>';
                        html += '</div>';

                        container.innerHTML = html;
                        attachEventListeners();
                    }

                    function attachEventListeners() {
                        container.querySelectorAll('.quiz-answer:not(.quiz-answer--disabled)').forEach(function(el) {
                            el.addEventListener('click', function() {
                                state.selectedAnswer = parseInt(this.dataset.index);
                                render();
                            });
                        });

                        var checkBtn = container.querySelector('[data-action="check"]');
                        if (checkBtn) {
                            checkBtn.addEventListener('click', function() {
                                var q = questions[state.currentQuestion];
                                var selected = q.answers[state.selectedAnswer];
                                if (selected && selected.isCorrect) {
                                    state.score++;
                                }
                                state.answered = true;
                                render();
                            });
                        }

                        var nextBtn = container.querySelector('[data-action="next"]');
                        if (nextBtn) {
                            nextBtn.addEventListener('click', function() {
                                state.currentQuestion++;
                                state.selectedAnswer = null;
                                state.answered = false;
                                render();
                            });
                        }

                        var finishBtn = container.querySelector('[data-action="finish"]');
                        if (finishBtn) {
                            finishBtn.addEventListener('click', function() {
                                state.finished = true;
                                render();
                            });
                        }

                        var restartBtn = container.querySelector('[data-action="restart"]');
                        if (restartBtn) {
                            restartBtn.addEventListener('click', function() {
                                state.currentQuestion = 0;
                                state.selectedAnswer = null;
                                state.answered = false;
                                state.score = 0;
                                state.finished = false;
                                questions = questions.map(function(q) {
                                    var shuffled = q.answers.slice();
                                    for (var i = shuffled.length - 1; i > 0; i--) {
                                        var j = Math.floor(Math.random() * (i + 1));
                                        var temp = shuffled[i];
                                        shuffled[i] = shuffled[j];
                                        shuffled[j] = temp;
                                    }
                                    return { ...q, answers: shuffled };
                                });
                                render();
                            });
                        }
                    }

                    render();
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                initQuizzes();
            });

            window.parent.postMessage(JSON.stringify({ subject: 'embed.ready' }), '*');
        })();
    </script>
</body>
</html>
