<?php
// This file is part of the MotionBase plugin for Moodle.

/**
 * Installation.
 *
 * @package    local_motionbase
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Put "Add from MotionBase" at the foot of the activity chooser. The chooser
 * has room for one plugin there; the place is taken only while it is empty or
 * shows Moodle's own default, the Marketplace link - never over a choice an
 * admin made.
 *
 * @return bool
 */
function xmldb_local_motionbase_install() {
    $current = get_config('core', 'activitychooseractivefooter');

    if (empty($current) || in_array($current, ['hidden', 'tool_installaddon'], true)) {
        set_config('activitychooseractivefooter', 'local_motionbase');
    }

    return true;
}
