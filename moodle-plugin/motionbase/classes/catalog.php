<?php
// This file is part of the MotionBase plugin for Moodle.

namespace filter_motionbase;

/**
 * MotionBase's catalogue as the picker shows it: every option with the name
 * and description its activity will get, and whether the course has it yet.
 *
 * @package    filter_motionbase
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalog {
    /** @var array Options by value, e.g. "chapter:3" */
    private array $options = [];

    /** @var array Values already in the course */
    private array $present = [];

    /**
     * @param array $topics As returned by MotionBase
     * @param \stdClass $course
     * @param \stdClass|null $tool The MotionBase tool type
     */
    public function __construct(private array $topics, \stdClass $course, ?\stdClass $tool) {
        foreach ($topics as $topic) {
            $this->add('topic:' . $topic['id'], $topic['title'], '');
            $this->add('chat:' . $topic['id'], get_string('assistanttitle', 'filter_motionbase', $topic['title']),
                get_string('assistantsummary', 'filter_motionbase', $topic['title']));

            foreach ($topic['chapters'] as $chapter) {
                $this->add('chapter:' . $chapter['id'], $chapter['title'], '');

                foreach ($chapter['sections'] as $section) {
                    $this->add('section:' . $section['id'], $section['title'], '');

                    if (!empty($section['task'])) {
                        $this->add('task:' . $section['id'], $section['title'], '');
                    }
                }
            }
        }

        $this->present = self::present($course, $tool);
    }

    /**
     * The chapters of a topic, for "whole course": one book each.
     *
     * @param int $topicid
     * @return int[]
     */
    public function chapters(int $topicid): array {
        foreach ($this->topics as $topic) {
            if ((int) $topic['id'] === $topicid) {
                return array_map(fn($chapter) => (int) $chapter['id'], $topic['chapters']);
            }
        }

        return [];
    }

    /**
     * The option behind a value, if MotionBase offers it.
     *
     * @param string $value
     * @return array|null title, summary
     */
    public function option(string $value): ?array {
        return $this->options[$value] ?? null;
    }

    /**
     * The ID number of an activity made from MotionBase, e.g. motionbase-chapter-39.
     *
     * @param string $source "task:67", "section:63" or "chapter:39"
     * @return string
     */
    public static function idnumber(string $source): string {
        return 'motionbase-' . str_replace(':', '-', $source);
    }

    /**
     * Template data for the picker.
     *
     * @return array
     */
    public function export(): array {
        return array_map(function(array $topic) {
            $tasks = [];
            $chapters = [];

            foreach ($topic['chapters'] as $chapter) {
                $lessons = [];

                foreach ($chapter['sections'] as $section) {
                    $lessons[] = $this->item('section:' . $section['id'], $section['title'],
                        get_string('pagemeta', 'filter_motionbase'),
                        $topic['title'] . ' ' . $chapter['title'] . ' ' . $section['title']);

                    if (!empty($section['task'])) {
                        $tasks[] = $this->item('task:' . $section['id'], $section['title'],
                            get_string('task_' . $section['task'], 'filter_motionbase') . ' · ' . $chapter['title'],
                            $topic['title'] . ' ' . $chapter['title'] . ' ' . $section['title'] . ' '
                                . get_string('tasks', 'filter_motionbase'));
                    }
                }

                $chapters[] = $this->item('chapter:' . $chapter['id'], $chapter['title'],
                    get_string('bookmeta', 'filter_motionbase', self::lessons($chapter['lessons'])),
                    $topic['title'] . ' ' . $chapter['title'],
                    ['lessons' => $lessons, 'haslessons' => count($lessons) > 1]);
            }

            return [
                'id' => $topic['id'],
                'title' => $topic['title'],
                'meta' => self::lessons($topic['lessons']),
                'tasks' => $tasks,
                'hastasks' => (bool) $tasks,
                // A whole course is there when a book for each of its chapters is.
                'whole' => ['present' => $topic['chapters'] && !array_diff(
                    array_map(fn($chapter) => 'chapter:' . $chapter['id'], $topic['chapters']), $this->present),
                ] + $this->item('topic:' . $topic['id'], get_string('wholecourse', 'filter_motionbase'),
                    get_string('wholecoursemeta', 'filter_motionbase', count($topic['chapters'])), $topic['title']),
                'chapters' => $chapters,
                'assistant' => $this->item('chat:' . $topic['id'], get_string('assistant', 'filter_motionbase'),
                    get_string('assistantmeta', 'filter_motionbase', $topic['title']),
                    $topic['title'] . ' ' . get_string('assistant', 'filter_motionbase') . ' chat ki ai'),
            ];
        }, $this->topics);
    }

    /**
     * @param string $value
     * @param string $title Activity name
     * @param string $summary Activity description, plain text
     */
    private function add(string $value, string $title, string $summary): void {
        $this->options[$value] = ['title' => $title, 'summary' => $summary];
    }

    /**
     * One checkbox in the picker.
     *
     * @param string $value
     * @param string $name
     * @param string $meta
     * @param string $search Words it is found by
     * @param array $extra
     * @return array
     */
    private function item(string $value, string $name, string $meta, string $search, array $extra = []): array {
        return [
            'value' => $value,
            'name' => $name,
            'meta' => $meta,
            'label' => $this->options[$value]['title'] ?? $name,
            'search' => \core_text::strtolower($search),
            'present' => in_array($value, $this->present, true),
        ] + $extra;
    }

    /**
     * "1 lesson", "12 lessons".
     *
     * @param int $count
     * @return string
     */
    private static function lessons(int $count): string {
        return $count === 1 ? get_string('lesson', 'filter_motionbase') : get_string('lessons', 'filter_motionbase', $count);
    }

    /**
     * What the course already has from MotionBase, as picker values.
     *
     * @param \stdClass $course
     * @param \stdClass|null $tool
     * @return string[]
     */
    private static function present(\stdClass $course, ?\stdClass $tool): array {
        global $DB;

        $present = [];

        $idnumbers = $DB->get_fieldset_sql(
            "SELECT idnumber FROM {course_modules} WHERE course = ? AND deletioninprogress = 0 AND idnumber LIKE ?",
            [$course->id, 'motionbase-%']
        );

        foreach ($idnumbers as $idnumber) {
            if (preg_match('/^motionbase-(task|section|chapter)-(\d+)$/', $idnumber, $m)) {
                $present[] = $m[1] . ':' . $m[2];
            }
        }

        // The assistant is an activity of the external tool, found by what it launches.
        if ($tool) {
            $params = $DB->get_fieldset_sql(
                "SELECT l.instructorcustomparameters
                   FROM {lti} l
                   JOIN {course_modules} cm ON cm.instance = l.id AND cm.deletioninprogress = 0
                   JOIN {modules} m ON m.id = cm.module AND m.name = 'lti'
                  WHERE l.course = ? AND l.typeid = ?",
                [$course->id, $tool->id]
            );

            foreach ($params as $text) {
                if (preg_match('/content_type=chat\b/', (string) $text) && preg_match('/topic_id=(\d+)/', (string) $text, $m)) {
                    $present[] = 'chat:' . $m[1];
                }
            }
        }

        return $present;
    }
}
