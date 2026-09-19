<?php
// This file is part of the MotionBase plugin for Moodle.

/**
 * Caches.
 *
 * @package    filter_motionbase
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    // The last content fetched per lesson, shown while MotionBase is unreachable.
    'lessons' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
    ],
    // When each book last compared its chapters with MotionBase.
    'books' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
    ],
];
