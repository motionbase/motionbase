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
        .code-block pre[class*="language-"],
        .code-block pre,
        pre[class*="language-"] {
            background: transparent !important;
            margin: 0 !important;
            padding: 1rem !important;
            border-radius: 0 !important;
            overflow-x: auto !important;
            white-space: pre !important;
            word-wrap: normal !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }

        .code-block code[class*="language-"],
        .code-block code,
        code[class*="language-"] {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
            font-size: 0.875rem !important;
            background: transparent !important;
            white-space: pre !important;
            word-wrap: normal !important;
        }

        .code-block {
            max-width: 100%;
            overflow: hidden;
        }

        .code-block > div {
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

    {{-- iframe-resizer library for LMS platforms that support it --}}
    <script src="https://cdn.jsdelivr.net/npm/iframe-resizer@4.3.9/js/iframeResizer.contentWindow.min.js"></script>

    <script>
        (function() {
            // Track last sent height to prevent duplicates
            let lastSentHeight = 0;
            let debounceTimer = null;

            // Calculate content height
            function getContentHeight() {
                const body = document.body;
                const html = document.documentElement;

                // Get the actual content height
                const height = Math.max(
                    body.scrollHeight,
                    body.offsetHeight,
                    html.scrollHeight
                );

                return height;
            }

            // Send height to parent (debounced)
            function sendHeight(force) {
                if (debounceTimer) {
                    clearTimeout(debounceTimer);
                }

                debounceTimer = setTimeout(function() {
                    const height = getContentHeight();

                    // Only send if height changed significantly (more than 5px)
                    if (!force && Math.abs(height - lastSentHeight) < 5) {
                        return;
                    }

                    lastSentHeight = height;

                    // Send to parent via postMessage
                    window.parent.postMessage(JSON.stringify({
                        subject: 'lti.frameResize',
                        height: height
                    }), '*');

                    // Also send as object for Canvas LMS
                    window.parent.postMessage({
                        subject: 'lti.frameResize',
                        height: height
                    }, '*');
                }, 100);
            }

            // Make sendHeight globally available
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

            // Initialize on DOM ready
            document.addEventListener('DOMContentLoaded', function() {
                highlightCode();
                // Send height after content is rendered
                setTimeout(function() { sendHeight(true); }, 200);
            });

            // Also send on window load (after all resources loaded)
            window.addEventListener('load', function() {
                highlightCode();
                sendHeight(true);
                // Final send after fonts/styles fully loaded
                setTimeout(function() { sendHeight(true); }, 500);
            });

            // Send height after images load
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

            // Send ready message
            window.parent.postMessage(JSON.stringify({ subject: 'lti.ready' }), '*');
        })();
    </script>
</body>
</html>
