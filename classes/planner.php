<?php
/**
 * Works out when each section opens.
 *
 * @package local_weekopener
 */

namespace local_weekopener;

defined('MOODLE_INTERNAL') || die();

final class planner {

    /**
     * Sections before the starting one open straight away; from the starting one
     * on, each opens one interval after the last. Excluded sections are left out
     * of the plan and take no slot, so the section after one opens when the
     * excluded one would have.
     *
     * Days are added on the calendar in $timezone rather than as multiples of
     * 86400 seconds, so a section opens at the same local time on either side
     * of a daylight saving change.
     *
     * @param int[] $sectionids Section ids in course order, without section 0.
     * @param int[] $excluded Section ids left untouched.
     * @return array<int, ?int> Section id => opening time, or null to open straight away.
     */
    public static function plan(
        array $sectionids,
        int $startsectionid,
        array $excluded,
        int $starttime,
        int $intervaldays,
        \DateTimeZone $timezone
    ): array {
        if (!in_array($startsectionid, $sectionids, true)) {
            throw new \coding_exception('The starting section is not one of the course sections');
        }
        if (in_array($startsectionid, $excluded, true)) {
            throw new \coding_exception('The starting section cannot be excluded');
        }
        if ($intervaldays < 1) {
            throw new \coding_exception('The interval must be at least one day');
        }

        $start = (new \DateTimeImmutable('@' . $starttime))->setTimezone($timezone);
        $plan = [];
        $started = false;
        $slot = 0;

        foreach ($sectionids as $sectionid) {
            $started = $started || $sectionid === $startsectionid;
            if (in_array($sectionid, $excluded, true)) {
                continue;
            }
            if (!$started) {
                $plan[$sectionid] = null;
                continue;
            }
            $plan[$sectionid] = $start->modify('+' . ($slot * $intervaldays) . ' days')->getTimestamp();
            $slot++;
        }
        return $plan;
    }
}
