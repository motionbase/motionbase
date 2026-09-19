<?php
// This file is part of the MotionBase plugin for Moodle.

namespace local_motionbase;

/**
 * MotionBase's catalogue as the picker shows it: every option with the name
 * and description its activity will get, and whether the course has it yet.
 *
 * @package    local_motionbase
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
            $this->add('topic:' . $topic['id'], $topic['title'], self::lessons($topic['lessons']));
            $this->add('chat:' . $topic['id'], get_string('assistanttitle', 'local_motionbase', $topic['title']),
                get_string('assistantsummary', 'local_motionbase', $topic['title']));

            foreach ($topic['chapters'] as $chapter) {
                $this->add('chapter:' . $chapter['id'], $topic['title'] . ' – ' . $chapter['title'],
                    self::lessons($chapter['lessons']));

                foreach ($chapter['sections'] as $section) {
                    $this->add('section:' . $section['id'], $topic['title'] . ' – ' . $section['title'], self::lessons(1));

                    if (!empty($section['task'])) {
                        $this->add('task:' . $section['id'], $section['title'], '');
                    }
                }
            }
        }

        $this->present = self::present($course, $tool);
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
     * The ID number an assignment made from a task carries.
     *
     * @param int $sectionid
     * @return string
     */
    public static function task_idnumber(int $sectionid): string {
        return 'motionbase-task-' . $sectionid;
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
                        get_string('lessonmeta', 'local_motionbase'),
                        $topic['title'] . ' ' . $chapter['title'] . ' ' . $section['title']);

                    if (!empty($section['task'])) {
                        $tasks[] = $this->item('task:' . $section['id'], $section['title'],
                            get_string('task_' . $section['task'], 'local_motionbase') . ' · ' . $chapter['title'],
                            $topic['title'] . ' ' . $chapter['title'] . ' ' . $section['title'] . ' '
                                . get_string('tasks', 'local_motionbase'));
                    }
                }

                $chapters[] = $this->item('chapter:' . $chapter['id'], $chapter['title'],
                    get_string('chaptermeta', 'local_motionbase', self::lessons($chapter['lessons'])),
                    $topic['title'] . ' ' . $chapter['title'],
                    ['lessons' => $lessons, 'haslessons' => count($lessons) > 1]);
            }

            return [
                'id' => $topic['id'],
                'title' => $topic['title'],
                'meta' => self::lessons($topic['lessons']),
                'tasks' => $tasks,
                'hastasks' => (bool) $tasks,
                'whole' => $this->item('topic:' . $topic['id'], get_string('wholecourse', 'local_motionbase'),
                    get_string('wholecoursemeta', 'local_motionbase'), $topic['title']),
                'chapters' => $chapters,
                'assistant' => $this->item('chat:' . $topic['id'], get_string('assistant', 'local_motionbase'),
                    get_string('assistantmeta', 'local_motionbase', $topic['title']),
                    $topic['title'] . ' ' . get_string('assistant', 'local_motionbase') . ' chat ki ai'),
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
        return $count === 1 ? get_string('lesson', 'local_motionbase') : get_string('lessons', 'local_motionbase', $count);
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
                $custom = [];
                foreach (preg_split('/[\r\n;]+/', (string) $text) as $line) {
                    if (str_contains($line, '=')) {
                        [$key, $value] = array_map('trim', explode('=', $line, 2));
                        $custom[$key] = $value;
                    }
                }

                $type = $custom['content_type'] ?? null;
                $key = ['topic' => 'topic_id', 'chat' => 'topic_id', 'chapter' => 'chapter_id', 'section' => 'section_id'][$type] ?? null;

                if ($key && isset($custom[$key])) {
                    $present[] = $type . ':' . (int) $custom[$key];
                }
            }
        }

        $idnumbers = $DB->get_fieldset_sql(
            "SELECT idnumber FROM {course_modules} WHERE course = ? AND deletioninprogress = 0 AND idnumber LIKE ?",
            [$course->id, 'motionbase-task-%']
        );

        foreach ($idnumbers as $idnumber) {
            $present[] = 'task:' . (int) substr($idnumber, strlen('motionbase-task-'));
        }

        return $present;
    }
}
