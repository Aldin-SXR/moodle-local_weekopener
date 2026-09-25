<?php
/**
 * The settings a course's opening dates were last worked out from.
 *
 * @package local_weekopener
 */

namespace local_weekopener;

defined('MOODLE_INTERNAL') || die();

final class schedule {

    public const DEFAULT_INTERVAL_DAYS = 7;

    /**
     * @param int[] $excluded Section ids left untouched.
     * @param bool $hidden Hide a section entirely until it opens, rather than
     *        showing its name greyed out with the date it opens.
     */
    public function __construct(
        public readonly int $startsectionid,
        public readonly int $starttime,
        public readonly int $intervaldays = self::DEFAULT_INTERVAL_DAYS,
        public readonly array $excluded = [],
        public readonly bool $hidden = false,
    ) {
    }
}
