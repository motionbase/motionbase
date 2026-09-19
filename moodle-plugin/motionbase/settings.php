<?php
// This file is part of the MotionBase plugin for Moodle.

/**
 * Admin settings, under Plugins › Filters › MotionBase.
 *
 * @package    filter_motionbase
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'filter_motionbase/url',
        get_string('url', 'filter_motionbase'),
        get_string('url_desc', 'filter_motionbase'),
        'https://motionbase.ch',
        PARAM_URL
    ));
}
