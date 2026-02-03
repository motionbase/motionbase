<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MotionBase')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css'])

    <style>
        /* Ensure content fills the iframe */
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            height: auto;
        }

        /* Remove any default scrollbars when in iframe */
        html {
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Code highlighting */
        pre[class*="language-"] {
            background: #1e1e1e;
            border-radius: 0.75rem;
            padding: 1rem;
            overflow-x: auto;
            margin: 1.5rem 0;
        }

        code[class*="language-"] {
            color: #d4d4d4;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.875rem;
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

    <script>
        // Send content height to parent iframe for auto-resize (LTI standard)
        function sendHeight() {
            const height = document.documentElement.scrollHeight;

            // Try LTI standard message format
            window.parent.postMessage(JSON.stringify({
                subject: 'lti.frameResize',
                height: height
            }), '*');

            // Also send custom format as fallback
            window.parent.postMessage({
                type: 'lti-resize',
                height: height
            }, '*');
        }

        // Send height on load and resize
        window.addEventListener('load', sendHeight);
        window.addEventListener('resize', sendHeight);

        // Also send after images load
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('load', sendHeight);
        });

        // Send periodically for dynamic content
        setInterval(sendHeight, 1000);
    </script>
</body>
</html>
