<?php

namespace App\Mcp\Support;

/**
 * Translates between Markdown - which a model writes comfortably - and the
 * Editor.js block array that sections actually store.
 *
 * Only the block types an author writes by hand round-trip (header, paragraph,
 * list, code). Rich blocks like interactive, quiz, image and alert survive a
 * read as a readable placeholder and are never invented from Markdown; they are
 * added through their own tools so their data stays well formed.
 */
class MarkdownBlocks
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function toBlocks(string $markdown): array
    {
        $lines = preg_split('/\R/', $markdown) ?: [];
        $blocks = [];
        $paragraph = [];
        $listItems = [];
        $listStyle = 'unordered';

        $flushParagraph = function () use (&$paragraph, &$blocks): void {
            if ($paragraph === []) {
                return;
            }

            $blocks[] = ['type' => 'paragraph', 'data' => [
                'text' => self::inlineToHtml(implode(' ', $paragraph)),
            ]];

            $paragraph = [];
        };

        $flushList = function () use (&$listItems, &$listStyle, &$blocks): void {
            if ($listItems === []) {
                return;
            }

            $blocks[] = ['type' => 'list', 'data' => [
                'style' => $listStyle,
                'items' => array_map(fn (string $item) => [
                    'content' => self::inlineToHtml($item),
                    'meta' => new \stdClass(),
                    'items' => [],
                ], $listItems),
            ]];

            $listItems = [];
        };

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $trimmed = trim($line);

            // Fenced code block
            if (preg_match('/^```(\w*)\s*$/', $trimmed, $m)) {
                $flushParagraph();
                $flushList();

                $language = $m[1] !== '' ? $m[1] : 'plaintext';
                $code = [];

                while (++$i < count($lines) && ! preg_match('/^```\s*$/', trim($lines[$i]))) {
                    $code[] = $lines[$i];
                }

                $blocks[] = ['type' => 'code', 'data' => [
                    'code' => implode("\n", $code),
                    'language' => $language,
                ]];

                continue;
            }

            if ($trimmed === '') {
                $flushParagraph();
                $flushList();

                continue;
            }

            // Headings - h1 is the section title, so it maps onto h2 like the editor's own levels
            if (preg_match('/^(#{1,4})\s+(.*)$/', $trimmed, $m)) {
                $flushParagraph();
                $flushList();

                $blocks[] = ['type' => 'header', 'data' => [
                    'text' => self::inlineToHtml($m[2]),
                    'level' => max(2, min(4, strlen($m[1]))),
                ]];

                continue;
            }

            // List items
            if (preg_match('/^[-*]\s+(.*)$/', $trimmed, $m)) {
                $flushParagraph();

                if ($listItems !== [] && $listStyle !== 'unordered') {
                    $flushList();
                }

                $listStyle = 'unordered';
                $listItems[] = $m[1];

                continue;
            }

            if (preg_match('/^\d+[.)]\s+(.*)$/', $trimmed, $m)) {
                $flushParagraph();

                if ($listItems !== [] && $listStyle !== 'ordered') {
                    $flushList();
                }

                $listStyle = 'ordered';
                $listItems[] = $m[1];

                continue;
            }

            $flushList();
            $paragraph[] = $trimmed;
        }

        $flushParagraph();
        $flushList();

        return $blocks;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public static function toMarkdown(array $blocks): string
    {
        $out = [];

        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';
            $data = $block['data'] ?? [];

            $out[] = match ($type) {
                'header' => str_repeat('#', max(2, min(4, (int) ($data['level'] ?? 2))))
                    .' '.self::htmlToInline($data['text'] ?? ''),
                'paragraph' => self::htmlToInline($data['text'] ?? ''),
                'list' => self::listToMarkdown($data),
                'code' => "```".($data['language'] ?? '')."\n".($data['code'] ?? '')."\n```",
                'alert' => self::alertToMarkdown($data),
                'interactive' => '> [interaktive Grafik: '.($data['url'] ?? '?')
                    .($data['caption'] ?? '' ? ' - '.$data['caption'] : '').']',
                'image' => '> [Bild: '.($data['url'] ?? $data['file']['url'] ?? '?').']',
                'youtube' => '> [YouTube: '.($data['videoId'] ?? '?').']',
                'lottie' => '> [Lottie: '.($data['url'] ?? '?').']',
                'quiz' => '> [Quiz mit '.count($data['questions'] ?? []).' Fragen]',
                default => '> ['.$type.'-Block]',
            };
        }

        return implode("\n\n", array_filter($out, fn ($line) => $line !== ''));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function listToMarkdown(array $data): string
    {
        $ordered = ($data['style'] ?? 'unordered') === 'ordered';
        $lines = [];

        foreach (($data['items'] ?? []) as $index => $item) {
            $content = is_string($item) ? $item : ($item['content'] ?? '');
            $lines[] = ($ordered ? ($index + 1).'.' : '-').' '.self::htmlToInline($content);
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function alertToMarkdown(array $data): string
    {
        $inner = $data['contentBlocks']['blocks'] ?? [];
        $body = $inner !== []
            ? self::toMarkdown($inner)
            : self::htmlToInline($data['content'] ?? '');

        $label = strtoupper((string) ($data['type'] ?? 'info'));

        return "> **{$label}:** ".str_replace("\n\n", ' ', $body);
    }

    private static function inlineToHtml(string $text): string
    {
        $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');

        // Code spans first, so emphasis markers inside them are left alone.
        $text = preg_replace('/`([^`]+)`/', '<code class="inline-code">$1</code>', $text) ?? $text;
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<b>$1</b>', $text) ?? $text;
        $text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<i>$1</i>', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text) ?? $text;

        return $text;
    }

    private static function htmlToInline(string $html): string
    {
        $text = preg_replace('#<code[^>]*>(.*?)</code>#s', '`$1`', $html) ?? $html;
        $text = preg_replace('#<(b|strong)>(.*?)</\1>#s', '**$2**', $text) ?? $text;
        $text = preg_replace('#<(i|em)>(.*?)</\1>#s', '*$2*', $text) ?? $text;
        $text = preg_replace('#<a[^>]*href="([^"]*)"[^>]*>(.*?)</a>#s', '[$2]($1)', $text) ?? $text;
        $text = str_replace(['<br>', '<br/>', '<br />'], "\n", $text);

        return html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
