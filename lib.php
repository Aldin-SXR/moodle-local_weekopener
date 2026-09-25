<?php
/**
 * Course navigation.
 *
 * @package local_weekopener
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context_course $context
 */
function local_weekopener_extend_navigation_course($navigation, $course, $context): void {
    if (!has_capability('local/weekopener:manage', $context)) {
        return;
    }

    $navigation->add(
        get_string('nav', 'local_weekopener'),
        new moodle_url('/local/weekopener/index.php', ['id' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'local_weekopener',
        new pix_icon('i/calendar', '')
    );
}
