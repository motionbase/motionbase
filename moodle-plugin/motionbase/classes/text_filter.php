<?php
// This file is part of the MotionBase plugin for Moodle.

namespace filter_motionbase;

/**
 * Shows MotionBase lessons as they are now, every time.
 *
 * A book chapter, page or assignment made from a lesson stores it as it was,
 * inside a marked block:
 *
 *     <div class="motionbase-live motionbase-section-61">…</div>
 *
 * Each time the text is shown, this filter puts the lesson's current content
 * into that block. What a teacher wrote around it stays. If MotionBase cannot
 * be reached, the last content fetched is shown, or else the stored one.
 *
 * Runs after Moodle has cleaned the text: the content comes from MotionBase,
 * which sanitises it, and may embed its interactive graphics.
 *
 * @package    filter_motionbase
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \core_filters\text_filter {
    /** @var array Lessons fetched during this request, by id */
    private static array $fetched = [];

    #[\Override]
    public function filter($text, array $options = []) {
        if (!is_string($text) || strpos($text, 'motionbase-live') === false) {
            return $text;
        }

        $offset = 0;

        while (preg_match('/<div[^>]*\bclass="[^"]*\bmotionbase-live\b[^"]*\bmotionbase-section-(\d+)\b[^"]*"[^>]*>/i',
                $text, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $open = $match[0][1] + strlen($match[0][0]);
            $close = self::closing_div($text, $open);

            if ($close === null) {
                break;
            }

            $html = self::lesson((int) $match[1][0]);

            if ($html !== null && str_contains($html, 'motionbase-frame')) {
                self::size_frames();
            }

            if ($html !== null) {
                $text = substr($text, 0, $open) . $html . substr($text, $close);
                $close = $open + strlen($html);
            }

            $offset = $close;
        }

        return $text;
    }

    /**
     * The block that marks a lesson, holding its content as stored.
     *
     * @param int $sectionid
     * @param string $html
     * @return string
     */
    public static function block(int $sectionid, string $html): string {
        return '<div class="motionbase-live motionbase-section-' . $sectionid . '">' . $html . '</div>';
    }

    /**
     * Have the page size embedded graphics to the height they report - once
     * per page, and only where there is a page to add a script to.
     */
    private static function size_frames(): void {
        global $PAGE;
        static $added = false;

        if ($added || !$PAGE || (defined('AJAX_SCRIPT') && AJAX_SCRIPT) || (defined('WS_SERVER') && WS_SERVER)) {
            return;
        }

        $PAGE->requires->js_call_amd('filter_motionbase/frames', 'init');
        $added = true;
    }

    /**
     * Where the div opened just before $from ends: the offset of its "</div>".
     *
     * @param string $text
     * @param int $from
     * @return int|null
     */
    private static function closing_div(string $text, int $from): ?int {
        $depth = 1;

        while (preg_match('#<(/?)div\b[^>]*>#i', $text, $tag, PREG_OFFSET_CAPTURE, $from)) {
            $depth += $tag[1][0] === '/' ? -1 : 1;

            if ($depth === 0) {
                return $tag[0][1];
            }

            $from = $tag[0][1] + strlen($tag[0][0]);
        }

        return null;
    }

    /**
     * The lesson's current content, or the last one fetched.
     *
     * @param int $id
     * @return string|null
     */
    private static function lesson(int $id): ?string {
        if (array_key_exists($id, self::$fetched)) {
            return self::$fetched[$id];
        }

        $cache = \cache::make('filter_motionbase', 'lessons');

        try {
            // Somebody is waiting for the page: better the last known content
            // than a long wait.
            $html = client::get('lessons/' . $id . '?embed=1', 3)['html'] ?? null;

            if (is_string($html)) {
                $cache->set($id, $html);
            }
        } catch (\moodle_exception $e) {
            $html = $cache->get($id) ?: null;
        }

        return self::$fetched[$id] = $html;
    }
}
