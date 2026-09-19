<?php
// This file is part of the MotionBase plugin for Moodle.

/**
 * Where teachers find "Add from MotionBase".
 *
 * @package    local_motionbase
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_course\local\entity\activity_chooser_footer;

/**
 * The foot of the activity chooser: one link to the MotionBase picker, for
 * the section the chooser was opened in.
 *
 * Moodle calls the second argument a section id but passes the section's
 * number - the same number course/modedit.php takes.
 *
 * @param int $courseid
 * @param int $sectionid The section number
 * @return activity_chooser_footer
 */
function local_motionbase_custom_chooser_footer(int $courseid, int $sectionid): activity_chooser_footer {
    global $OUTPUT;

    $url = new moodle_url('/local/motionbase/add.php', ['course' => $courseid, 'section' => $sectionid]);

    return new activity_chooser_footer(
        'local_motionbase/footer',
        $OUTPUT->render_from_template('local_motionbase/chooser_footer', ['url' => $url->out(false)])
    );
}

/**
 * "Add from MotionBase" in the course's own menu, for teachers who never open
 * the activity chooser - and for sites whose chooser footer belongs to
 * another plugin.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context $context
 */
function local_motionbase_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context) {
    if ($course->id == SITEID || !has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    $navigation->add(
        get_string('addfrommotionbase', 'local_motionbase'),
        new moodle_url('/local/motionbase/add.php', ['course' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'local_motionbase',
        new pix_icon('i/externallink', '')
    );
}
