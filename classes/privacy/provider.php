<?php
/**
 * Privacy API: the plugin stores course settings and nothing about people.
 *
 * @package local_weekopener
 */

namespace local_weekopener\privacy;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\null_provider {

    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
