<?php
// This file is part of the MotionBase plugin for Moodle.

namespace filter_motionbase\privacy;

/**
 * The plugin stores nothing about users, and sends MotionBase nothing about
 * them - requests carry only this site's identity.
 *
 * @package    filter_motionbase
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Reason for storing no data.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
