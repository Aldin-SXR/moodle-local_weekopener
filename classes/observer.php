<?php
/**
 * Keeps the plugin's table from outliving the courses it describes.
 *
 * @package local_weekopener
 */

namespace local_weekopener;

defined('MOODLE_INTERNAL') || die();

final class observer {

    public static function course_deleted(\core\event\course_deleted $event): void {
        (new schedule_store())->delete((int)$event->courseid);
    }
}
