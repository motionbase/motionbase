<?php
// This file is part of the MotionBase plugin for Moodle.

namespace local_motionbase;

/**
 * Turns a choice from MotionBase into a Moodle activity.
 *
 * A task becomes a native assignment: its lesson is the description, the
 * submission is set up the way the author chose, grading is Moodle's alone.
 * Everything else - a whole course, a chapter, a lesson, the assistant -
 * becomes an activity of the MotionBase external tool, pointed at its content.
 *
 * @package    local_motionbase
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class creator {
    /**
     * A native Moodle assignment from a MotionBase task.
     *
     * @param \stdClass $course
     * @param int $section Section number
     * @param array $task As returned by MotionBase: title, html, submission
     * @return \stdClass The created module info
     */
    public static function assignment(\stdClass $course, int $section, array $task): \stdClass {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/modlib.php');

        $submission = $task['submission'] ?? 'none';
        $defaults = get_config('assign');

        $info = self::common($course, $section, 'assign', $task['title'], $task['html']);

        // Marks the assignment as this task's, so the picker can say it is
        // already in the course. An ID number is unique within a course: a
        // second copy of the same task goes without.
        $idnumber = catalog::task_idnumber((int) $task['id']);
        if (!$DB->record_exists('course_modules', ['course' => $course->id, 'idnumber' => $idnumber])) {
            $info->cmidnumber = $idnumber;
        }

        // The site's own defaults for assignments, so this one behaves like
        // any other the teacher would have made by hand.
        foreach ([
            'submissiondrafts' => 0, 'requiresubmissionstatement' => 0, 'sendnotifications' => 0,
            'sendlatenotifications' => 0, 'sendstudentnotifications' => 1, 'teamsubmission' => 0,
            'requireallteammemberssubmit' => 0, 'blindmarking' => 0, 'markingworkflow' => 0,
            'markingallocation' => 0, 'maxattempts' => -1, 'preventsubmissionnotingroup' => 0,
        ] as $name => $fallback) {
            $info->$name = isset($defaults->$name) ? $defaults->$name : $fallback;
        }

        $info->alwaysshowdescription = 1;
        $info->attemptreopenmethod = $defaults->attemptreopenmethod ?? 'untilpass';
        $info->teamsubmissiongroupingid = 0;
        $info->markinganonymous = 0;
        $info->markercount = 1;
        $info->activityformat = 0;
        $info->timelimit = 0;
        $info->submissionattachments = 0;
        // No dates: the teacher sets a deadline if the course needs one.
        $info->duedate = 0;
        $info->cutoffdate = 0;
        $info->gradingduedate = 0;
        $info->allowsubmissionsfromdate = 0;
        $info->grade = 100;
        $info->gradepenalty = 0;
        $info->hidegrader = 0;
        $info->multimarkmethod = null;
        $info->multimarkrounding = null;

        $info->assignsubmission_file_enabled = in_array($submission, ['file', 'both'], true) ? 1 : 0;
        $info->assignsubmission_file_maxfiles = (int) get_config('assignsubmission_file', 'maxfiles') ?: 20;
        $info->assignsubmission_file_maxsizebytes = 0;
        $info->assignsubmission_file_filetypes = '';
        $info->assignsubmission_onlinetext_enabled = in_array($submission, ['text', 'both'], true) ? 1 : 0;
        $info->assignsubmission_onlinetext_wordlimit_enabled = 0;
        $info->assignsubmission_onlinetext_wordlimit = 0;
        $info->assignfeedback_comments_enabled = 1;

        return create_module($info);
    }

    /**
     * An activity of the MotionBase external tool, showing the chosen content.
     *
     * @param \stdClass $course
     * @param int $section Section number
     * @param \stdClass $tool The MotionBase tool type
     * @param string $choice "topic:12", "chapter:3", "section:7" or "chat:12"
     * @param string $title
     * @param string $summary
     * @return \stdClass The created module info
     */
    public static function activity(\stdClass $course, int $section, \stdClass $tool, string $choice,
            string $title, string $summary): \stdClass {
        global $CFG;
        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot . '/mod/lti/locallib.php');

        [$type, $id] = explode(':', $choice, 2);
        $idkey = ['topic' => 'topic_id', 'chat' => 'topic_id', 'chapter' => 'chapter_id', 'section' => 'section_id'][$type];

        $info = self::common($course, $section, 'lti', $title, '<p>' . s($summary) . '</p>');
        $info->typeid = $tool->id;
        $info->toolurl = '';
        $info->securetoolurl = '';
        // What MotionBase shows on launch - the same parameters "Inhalt
        // auswählen" would have stored.
        $info->instructorcustomparameters = "content_type={$type}\n{$idkey}=" . (int) $id;
        $info->launchcontainer = LTI_LAUNCH_CONTAINER_DEFAULT;
        $info->showtitlelaunch = 1;
        $info->showdescriptionlaunch = 0;
        $info->grade = 0;
        $info->instructorchoiceacceptgrades = LTI_SETTING_NEVER;
        $info->instructorchoicesendname = LTI_SETTING_NEVER;
        $info->instructorchoicesendemailaddr = LTI_SETTING_NEVER;
        $info->resourcekey = '';
        $info->password = '';
        $info->icon = '';
        $info->secureicon = '';
        $info->debuglaunch = 0;

        return create_module($info);
    }

    /**
     * What every new activity needs, whatever its type.
     *
     * @param \stdClass $course
     * @param int $section
     * @param string $modname
     * @param string $name
     * @param string $intro HTML
     * @return \stdClass
     */
    private static function common(\stdClass $course, int $section, string $modname, string $name, string $intro): \stdClass {
        global $DB;

        $module = $DB->get_record('modules', ['name' => $modname], '*', MUST_EXIST);

        // Completion as the course would set it for a new activity of this kind.
        $info = \core_completion\manager::get_default_completion($course, $module);
        $info->completionview = $info->completionview ?? 0;
        $info->completionexpected = $info->completionexpected ?? 0;
        $info->completiongradeitemnumber = !empty($info->completionusegrade) ? 0 : null;

        $info->modulename = $modname;
        $info->module = $module->id;
        $info->course = $course->id;
        $info->section = $section;
        $info->name = \core_text::substr($name, 0, 255);
        $info->introeditor = ['text' => $intro, 'format' => FORMAT_HTML, 'itemid' => file_get_unused_draft_itemid()];
        $info->showdescription = 0;
        $info->visible = 1;
        $info->visibleoncoursepage = 1;
        $info->cmidnumber = '';
        $info->groupmode = 0;
        $info->groupingid = 0;

        return $info;
    }
}
