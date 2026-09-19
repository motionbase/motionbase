<?php
// This file is part of the MotionBase plugin for Moodle.

/**
 * Admin settings.
 *
 * @package    local_motionbase
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_motionbase', get_string('pluginname', 'local_motionbase'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_motionbase/url',
        get_string('url', 'local_motionbase'),
        get_string('url_desc', 'local_motionbase'),
        'https://motionbase.ch',
        PARAM_URL
    ));
}
