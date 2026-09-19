<?php
// This file is part of the MotionBase plugin for Moodle.

/**
 * "Add from MotionBase": tick what the class should get, pick the section,
 * done. Tasks become assignments, chapters books, lessons pages - Moodle's
 * own activities, showing MotionBase's current content whenever opened.
 *
 * @package    filter_motionbase
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

use filter_motionbase\catalog;
use filter_motionbase\client;
use filter_motionbase\creator;

$courseid = required_param('course', PARAM_INT);
$sectionnum = optional_param('section', 0, PARAM_INT);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_url(new moodle_url('/filter/motionbase/add.php', ['course' => $course->id, 'section' => $sectionnum]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('addfrommotionbase', 'filter_motionbase'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('addfrommotionbase', 'filter_motionbase'));

$tool = client::tool();
$error = null;
$topics = [];

try {
    $topics = client::get('catalog')['topics'] ?? [];
} catch (moodle_exception $e) {
    $error = $e->getMessage();
}

$catalog = new catalog($topics, $course, $tool);

if ($tool && !$error && data_submitted() && confirm_sesskey()) {
    $items = array_values(array_unique(array_filter(
        optional_param_array('items', [], PARAM_RAW_TRIMMED),
        fn($item) => (bool) preg_match('/^(topic|chapter|section|chat|task):\d+$/', $item)
    )));
    $target = required_param('target', PARAM_INT);

    $added = [];
    $failed = [];

    foreach ($items as $item) {
        [$type, $id] = explode(':', $item);
        $option = $catalog->option($item);

        // Only what the catalogue offered: an id that isn't in it is gone
        // from MotionBase, or was never there.
        if (!$option) {
            continue;
        }

        try {
            switch ($type) {
                case 'task':
                    require_capability('mod/assign:addinstance', $context);
                    creator::assignment($course, $target, client::get('tasks/' . (int) $id));
                    $added[] = $option['title'];
                    break;

                case 'section':
                    require_capability('mod/page:addinstance', $context);
                    creator::page($course, $target, client::get('lessons/' . (int) $id));
                    $added[] = $option['title'];
                    break;

                case 'chapter':
                case 'topic':
                    require_capability('mod/book:addinstance', $context);
                    foreach ($type === 'topic' ? $catalog->chapters((int) $id) : [(int) $id] as $chapterid) {
                        $chapter = client::get('chapters/' . $chapterid . '?html=1');
                        creator::book($course, $target, $chapter);
                        $added[] = $chapter['title'];
                    }
                    break;

                case 'chat':
                    require_capability('mod/lti:addpreconfiguredinstance', $context);
                    creator::activity($course, $target, $tool, $item, $option['title'], $option['summary']);
                    $added[] = $option['title'];
                    break;
            }
        } catch (moodle_exception $e) {
            $failed[] = $option['title'] . ' (' . $e->getMessage() . ')';
        }
    }

    if ($failed) {
        \core\notification::warning(get_string('failed', 'filter_motionbase', implode(', ', $failed)));
    }

    if ($added) {
        $message = count($added) === 1
            ? get_string('addedone', 'filter_motionbase', reset($added))
            : get_string('added', 'filter_motionbase', count($added));
        redirect(course_get_url($course, $target), $message, null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if (!$failed) {
        \core\notification::info(get_string('nothingselected', 'filter_motionbase'));
    }
}

$sections = [];
foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
    // A subsection lives inside an activity; new activities go into real sections.
    if (method_exists($section, 'is_delegated') && $section->is_delegated()) {
        continue;
    }

    $sections[] = [
        'num' => $section->section,
        'name' => get_section_name($course, $section),
        'selected' => $section->section == $sectionnum,
    ];
}

// Unnamed sections share a name ("New section"); their number tells them apart.
$names = array_count_values(array_column($sections, 'name'));
foreach ($sections as &$section) {
    if ($names[$section['name']] > 1) {
        $section['name'] .= ' (' . $section['num'] . ')';
    }
}
unset($section);

$data = [
    'action' => (new moodle_url('/filter/motionbase/add.php'))->out(false),
    'courseid' => $course->id,
    'sesskey' => sesskey(),
    'sections' => $sections,
    'cancelurl' => course_get_url($course, $sectionnum)->out(false),
    'error' => $error,
    'topics' => $error ? [] : $catalog->export(),
    'empty' => !$error && !$topics,
];

$PAGE->requires->js_call_amd('filter_motionbase/picker', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('filter_motionbase/picker', $data);
echo $OUTPUT->footer();
