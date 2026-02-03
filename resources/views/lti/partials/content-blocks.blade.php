@php
    use Illuminate\Support\Str;

    function renderEditorBlock($block, $index = 0) {
        $type = $block['type'] ?? 'paragraph';
        $data = $block['data'] ?? [];

        switch ($type) {
            case 'header':
                $level = min(max($data['level'] ?? 2, 2), 4);
                $text = $data['text'] ?? '';
                $classes = match($level) {
                    2 => 'text-2xl font-bold mt-8 mb-4',
                    3 => 'text-xl font-semibold mt-6 mb-3',
                    4 => 'text-lg font-semibold mt-4 mb-2',
                    default => 'text-xl font-bold mt-6 mb-3',
                };
                return "<h{$level} class=\"{$classes}\">{$text}</h{$level}>";

            case 'paragraph':
                $text = $data['text'] ?? '';
                return "<p class=\"mb-4 text-zinc-600 leading-relaxed\">{$text}</p>";

            case 'list':
                $items = $data['items'] ?? [];
                $style = $data['style'] ?? 'unordered';
                $listItems = renderListItems($items, $style);
                return "<ul class=\"mb-6 space-y-2 list-none\">{$listItems}</ul>";

            case 'code':
                $code = htmlspecialchars($data['code'] ?? '', ENT_QUOTES, 'UTF-8');
                $language = $data['language'] ?? 'text';
                return "<pre class=\"language-{$language} rounded-xl overflow-x-auto\"><code class=\"language-{$language}\">{$code}</code></pre>";

            case 'image':
                $url = $data['url'] ?? $data['file']['url'] ?? '';
                $caption = $data['caption'] ?? '';
                $withBorder = $data['withBorder'] ?? false;
                $stretched = $data['stretched'] ?? false;
                $borderClass = $withBorder ? 'border border-zinc-200' : '';
                $stretchedClass = $stretched ? '-mx-4 lg:-mx-8' : '';
                $html = "<figure class=\"mb-6 {$stretchedClass}\">";
                $html .= "<img src=\"{$url}\" alt=\"" . htmlspecialchars($caption ?: 'Bild', ENT_QUOTES) . "\" class=\"w-full h-auto rounded-xl {$borderClass}\" loading=\"lazy\">";
                if ($caption) {
                    $html .= "<figcaption class=\"mt-2 text-center text-sm text-zinc-500\">{$caption}</figcaption>";
                }
                $html .= "</figure>";
                return $html;

            case 'youtube':
                $videoId = $data['videoId'] ?? '';
                $caption = $data['caption'] ?? '';
                if (!$videoId) return '';
                $html = "<figure class=\"mb-6\">";
                $html .= "<div class=\"relative w-full overflow-hidden rounded-xl bg-zinc-900\" style=\"padding-bottom: 56.25%\">";
                $html .= "<iframe src=\"https://www.youtube-nocookie.com/embed/{$videoId}\" class=\"absolute inset-0 w-full h-full border-0\" allowfullscreen loading=\"lazy\"></iframe>";
                $html .= "</div>";
                if ($caption) {
                    $html .= "<figcaption class=\"mt-2 text-center text-sm text-zinc-500\">{$caption}</figcaption>";
                }
                $html .= "</figure>";
                return $html;

            case 'alert':
                $alertType = $data['type'] ?? 'info';
                $content = $data['content'] ?? '';
                $innerBlocks = $data['contentBlocks']['blocks'] ?? [];
                $alertClass = match($alertType) {
                    'warning' => 'alert-warning',
                    'danger' => 'alert-danger',
                    'neutral' => 'alert-neutral',
                    default => 'alert-info',
                };
                $html = "<div class=\"mb-6 rounded-xl border p-4 {$alertClass}\">";
                if (!empty($innerBlocks)) {
                    foreach ($innerBlocks as $i => $innerBlock) {
                        $html .= renderEditorBlock($innerBlock, $i);
                    }
                } else {
                    $html .= "<div>" . nl2br($content) . "</div>";
                }
                $html .= "</div>";
                return $html;

            case 'lottie':
                $url = $data['url'] ?? '';
                $caption = $data['caption'] ?? '';
                if (!$url) return '';
                // For Lottie, we'll show a placeholder with a link
                $html = "<figure class=\"mb-6 p-8 bg-zinc-50 rounded-xl text-center\">";
                $html .= "<div class=\"text-zinc-400 mb-2\">Lottie Animation</div>";
                if ($caption) {
                    $html .= "<figcaption class=\"text-sm text-zinc-500\">{$caption}</figcaption>";
                }
                $html .= "</figure>";
                return $html;

            case 'quiz':
                // Quiz placeholder - interactive quizzes need JS
                $questions = $data['questions'] ?? [];
                $count = count($questions);
                return "<div class=\"mb-6 p-6 bg-zinc-50 rounded-xl border border-zinc-200 text-center\">
                    <div class=\"text-lg font-semibold text-zinc-900 mb-2\">Quiz</div>
                    <div class=\"text-sm text-zinc-500\">{$count} Fragen</div>
                    <div class=\"mt-3 text-xs text-zinc-400\">Interaktive Quizze sind in der Vollversion verfügbar.</div>
                </div>";

            default:
                return '';
        }
    }

    function renderListItems($items, $style = 'unordered', $depth = 0) {
        $html = '';
        foreach ($items as $index => $item) {
            $content = is_string($item) ? $item : ($item['content'] ?? '');
            $children = is_array($item) ? ($item['items'] ?? []) : [];
            $bullet = $style === 'ordered' ? ($index + 1) . '.' : '–';

            $html .= "<li class=\"flex gap-3 text-zinc-600\">";
            $html .= "<span class=\"shrink-0\">{$bullet}</span>";
            $html .= "<div class=\"flex-1\">{$content}";
            if (!empty($children)) {
                $html .= "<ul class=\"mt-2 space-y-2\">" . renderListItems($children, $style, $depth + 1) . "</ul>";
            }
            $html .= "</div></li>";
        }
        return $html;
    }
@endphp

@if(empty($blocks))
    <div class="flex flex-col items-center justify-center py-12 text-zinc-400 border border-dashed border-zinc-200 rounded-xl bg-zinc-50">
        <p>Dieser Abschnitt ist noch leer.</p>
    </div>
@else
    @foreach($blocks as $index => $block)
        {!! renderEditorBlock($block, $index) !!}
    @endforeach
@endif
