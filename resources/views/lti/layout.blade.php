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
        // Common Moodle iframe IDs to try
        const MOODLE_FRAME_IDS = [
            'contentframe',
            'ltiframe',
            'mod_lti_launch_container',
            'lti_tool_frame',
            'resourceobject',
            'externalcontentframe'
        ];

        // Send content height to parent iframe for auto-resize
        function sendHeight() {
            // Calculate actual content height with some padding
            const body = document.body;
            const html = document.documentElement;
            const height = Math.max(
                body.scrollHeight,
                body.offsetHeight,
                html.clientHeight,
                html.scrollHeight,
                html.offsetHeight
            ) + 2; // +2 to prevent subpixel scrollbar

            // Try to get the iframe ID from the URL
            const urlParams = new URLSearchParams(window.location.search);
            const frameIdFromUrl = urlParams.get('frame_id');

            // Try all common Moodle iframe IDs
            const frameIdsToTry = frameIdFromUrl
                ? [frameIdFromUrl, ...MOODLE_FRAME_IDS]
                : MOODLE_FRAME_IDS;

            // Send to each possible frame ID
            frameIdsToTry.forEach(function(frameId) {
                // Moodle LTI standard format (stringified JSON)
                window.parent.postMessage(JSON.stringify({
                    subject: 'lti.frameResize',
                    height: height,
                    frame_id: frameId
                }), '*');
            });

            // Also send without frame_id (some LMS use this)
            window.parent.postMessage(JSON.stringify({
                subject: 'lti.frameResize',
                height: height
            }), '*');

            // Canvas LMS format (object, not string)
            window.parent.postMessage({
                subject: 'lti.frameResize',
                height: height
            }, '*');

            // H5P / generic format
            window.parent.postMessage({
                context: 'h5p',
                action: 'resize',
                height: height
            }, '*');

            // Generic resize message
            window.parent.postMessage({
                type: 'lti-resize',
                height: height
            }, '*');

            // Also try resizing via window.frameElement if accessible (same-origin only)
            try {
                if (window.frameElement) {
                    window.frameElement.style.height = height + 'px';
                    window.frameElement.style.minHeight = height + 'px';
                }
            } catch (e) {
                // Cross-origin restriction, ignore
            }

            // Moodle-specific: try using Moodle's YUI resize
            try {
                if (window.parent && window.parent.M && window.parent.M.mod_lti) {
                    window.parent.M.mod_lti.resize(height);
                }
            } catch (e) {
                // Not in Moodle or no access
            }

            // Try Moodle's require for AMD modules
            try {
                if (window.parent && window.parent.require) {
                    window.parent.require(['mod_lti/tool'], function(tool) {
                        if (tool && tool.resize) {
                            tool.resize(height);
                        }
                    });
                }
            } catch (e) {
                // Not available
            }

            // Try to find and resize iframe directly from parent (same-origin only)
            try {
                if (window.parent && window.parent.document) {
                    // Find all iframes and check which one contains us
                    const iframes = window.parent.document.querySelectorAll('iframe');
                    iframes.forEach(function(iframe) {
                        try {
                            if (iframe.contentWindow === window) {
                                iframe.style.height = height + 'px';
                                iframe.style.minHeight = height + 'px';
                                iframe.setAttribute('height', height);
                            }
                        } catch (e) {
                            // Cross-origin, skip
                        }
                    });
                }
            } catch (e) {
                // Cross-origin restriction
            }
        }

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

        document.addEventListener('DOMContentLoaded', function() {
            highlightCode();
            // Initial height send after DOM is ready
            setTimeout(sendHeight, 100);
        });

        // Also try on window load as backup
        window.addEventListener('load', function() {
            highlightCode();
        });

        // Copy code to clipboard
        function copyCode(blockId) {
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
        }

        // Send height on load and resize
        window.addEventListener('load', function() {
            sendHeight();
            // Send again after a short delay to account for fonts/styles loading
            setTimeout(sendHeight, 500);
            setTimeout(sendHeight, 1000);
        });
        window.addEventListener('resize', sendHeight);

        // Also send after images load
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('load', sendHeight);
        });

        // Use ResizeObserver for more reliable height detection
        if (typeof ResizeObserver !== 'undefined') {
            const resizeObserver = new ResizeObserver(function() {
                sendHeight();
            });
            resizeObserver.observe(document.body);
        }

        // Send periodically for dynamic content
        setInterval(sendHeight, 2000);

        // Listen for messages from parent (e.g., to get frame ID)
        window.addEventListener('message', function(event) {
            try {
                let data = event.data;
                if (typeof data === 'string') {
                    data = JSON.parse(data);
                }
                // If parent sends us our frame ID, store it and resize
                if (data.subject === 'lti.setFrameId' || data.type === 'setFrameId') {
                    const frameId = data.frame_id || data.frameId;
                    if (frameId) {
                        MOODLE_FRAME_IDS.unshift(frameId);
                        sendHeight();
                    }
                }
                // If parent requests height, send it
                if (data.subject === 'lti.getHeight' || data.type === 'getHeight') {
                    sendHeight();
                }
            } catch (e) {
                // Ignore parsing errors
            }
        });

        // Request frame ID from parent on load
        window.parent.postMessage(JSON.stringify({
            subject: 'lti.getFrameId'
        }), '*');
        window.parent.postMessage({
            subject: 'lti.getFrameId'
        }, '*');

        // Also send a "ready" message to signal we're loaded
        window.parent.postMessage(JSON.stringify({
            subject: 'lti.ready'
        }), '*');
    </script>
</body>
</html>
