<?php
// This file is part of the MotionBase plugin for Moodle.

/**
 * Uninstallation.
 *
 * @package    filter_motionbase
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Give the foot of the activity chooser back.
 *
 * @return bool
 */
function xmldb_filter_motionbase_uninstall() {
    if (get_config('core', 'activitychooseractivefooter') === 'filter_motionbase') {
        set_config('activitychooseractivefooter', 'hidden');
    }

    return true;
}
