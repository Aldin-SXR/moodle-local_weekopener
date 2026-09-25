<?php
/**
 * Event observers.
 *
 * @package local_weekopener
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_deleted',
        'callback' => '\local_weekopener\observer::course_deleted',
    ],
];
