<?php
// This file is part of the MotionBase plugin for Moodle.

namespace filter_motionbase;

/**
 * Keeps a book made from a MotionBase chapter in line with it: one book
 * chapter per lesson, in MotionBase's order, with MotionBase's titles.
 *
 * The content itself is not copied here - the filter shows it live. Chapters
 * a teacher added stay where they are. A lesson that is gone from MotionBase
 * (deleted or unpublished) hides its chapter rather than deleting it, and
 * shows it again when the lesson is back.
 *
 * @package    filter_motionbase
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class books {
    /** Seconds between two checks of the same book - many learners open it at once. */
    private const INTERVAL = 30;

    /**
     * Compare the book with its chapter in MotionBase and adjust it.
     *
     * @param \stdClass|\cm_info $cm A book whose ID number is motionbase-chapter-N
     */
    public static function sync($cm): void {
        if (!preg_match('/^motionbase-chapter-(\d+)$/', (string) $cm->idnumber, $match)) {
            return;
        }

        $cache = \cache::make('filter_motionbase', 'books');
        if ((int) $cache->get($cm->id) > time() - self::INTERVAL) {
            return;
        }
        $cache->set($cm->id, time());

        try {
            $remote = client::get('chapters/' . (int) $match[1], 3);
        } catch (\moodle_exception $e) {
            return; // Unreachable, or the chapter is gone: the book stays as it is.
        }

        self::apply((int) $cm->instance, $remote['lessons'] ?? []);
    }

    /**
     * Make the book's chapters match the lessons.
     *
     * @param int $bookid
     * @param array $lessons [{id, title}] in MotionBase's order
     */
    public static function apply(int $bookid, array $lessons): void {
        global $DB;

        $chapters = array_values($DB->get_records('book_chapters', ['bookid' => $bookid], 'pagenum, id'));
        $original = [];
        foreach ($chapters as $chapter) {
            $original[$chapter->id] = clone $chapter;
        }
        $wanted = array_column($lessons, null, 'id');

        // The book's chapters for lessons, by lesson id
        $ours = [];
        foreach ($chapters as $chapter) {
            if (preg_match('/\bmotionbase-section-(\d+)\b/', (string) $chapter->content, $m)) {
                $ours[(int) $m[1]] = $chapter;
            }
        }

        // The new order: each place a lesson chapter held gets the next lesson
        // in MotionBase's order; the teacher's own chapters keep their places;
        // new lessons follow the last lesson chapter; gone ones go to the end.
        $queue = array_keys($wanted);
        $order = [];
        $lastours = -1;
        foreach ($chapters as $chapter) {
            if (!in_array($chapter, $ours, true)) {
                $order[] = $chapter;
                continue;
            }
            if ($queue) {
                $order[] = self::chapter($bookid, $wanted[array_shift($queue)], $ours);
                $lastours = count($order) - 1;
            }
        }
        $new = array_map(fn($id) => self::chapter($bookid, $wanted[$id], $ours), $queue);
        array_splice($order, $lastours + 1, 0, $new);
        foreach ($ours as $id => $chapter) {
            if (!isset($wanted[$id])) {
                $chapter->hidden = 1;
                $order[] = $chapter;
            }
        }

        $changed = false;
        foreach ($order as $index => $chapter) {
            $before = $original[$chapter->id] ?? null;

            $chapter->pagenum = $index + 1;
            $chapter->subchapter = 0;

            if (!$chapter->id) {
                $chapter->id = $DB->insert_record('book_chapters', $chapter);
                $changed = true;
            } else if ($before && ($before->pagenum != $chapter->pagenum || $before->title !== $chapter->title
                    || $before->hidden != $chapter->hidden || $before->subchapter != $chapter->subchapter)) {
                $chapter->timemodified = time();
                $DB->update_record('book_chapters', $chapter);
                $changed = true;
            }
        }

        if ($changed) {
            // Tells the book's print and export views their copies are stale.
            $DB->execute('UPDATE {book} SET revision = revision + 1 WHERE id = ?', [$bookid]);
        }
    }

    /**
     * The chapter for a lesson: the existing one, brought up to date, or a new one.
     *
     * @param int $bookid
     * @param array $lesson {id, title, html?}
     * @param array $ours Existing lesson chapters by lesson id
     * @return \stdClass Not saved yet
     */
    private static function chapter(int $bookid, array $lesson, array $ours): \stdClass {
        $chapter = $ours[$lesson['id']] ?? null;

        if ($chapter) {
            $chapter->title = \core_text::substr($lesson['title'], 0, 255);
            $chapter->hidden = 0;
            return $chapter;
        }

        return (object) [
            'id' => 0,
            'bookid' => $bookid,
            'title' => \core_text::substr($lesson['title'], 0, 255),
            'content' => text_filter::block((int) $lesson['id'], $lesson['html'] ?? ''),
            'contentformat' => FORMAT_HTML,
            'hidden' => 0,
            'importsrc' => '',
            'timecreated' => time(),
            'timemodified' => time(),
        ];
    }
}
