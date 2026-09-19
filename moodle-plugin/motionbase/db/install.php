<?php
// This file is part of the MotionBase plugin for Moodle.

/**
 * Installation.
 *
 * @package    filter_motionbase
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Switch the filter on everywhere, as the first one, so that filters after it
 * - media players, code highlighting - work on the fresh content as well.
 *
 * And put "Add from MotionBase" at the foot of the activity chooser. The
 * chooser has room for one plugin there; the place is taken only while it is
 * empty or shows Moodle's own default, the Marketplace link - never over a
 * choice an admin made.
 *
 * @return bool
 */
function xmldb_filter_motionbase_install() {
    global $CFG;
    require_once($CFG->libdir . '/filterlib.php');

    filter_set_global_state('motionbase', TEXTFILTER_ON);
    $position = filter_get_global_states()['motionbase']->sortorder ?? 1;
    for ($i = 1; $i < $position; $i++) {
        filter_set_global_state('motionbase', TEXTFILTER_ON, -1);
    }

    $footer = get_config('core', 'activitychooseractivefooter');
    if (empty($footer) || in_array($footer, ['hidden', 'tool_installaddon'], true)) {
        set_config('activitychooseractivefooter', 'filter_motionbase');
    }

    return true;
}
