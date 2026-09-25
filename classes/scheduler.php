<?php
/**
 * Writes a schedule into a course's section restrictions.
 *
 * @package local_weekopener
 */

namespace local_weekopener;

use core_courseformat\formatactions;

defined('MOODLE_INTERNAL') || die();

final class scheduler {

    /**
     * The sections a schedule can cover: every one in the course outline except
     * section 0, which is always open, and subsections, which open with the
     * section that holds them. A subsection is recognised by its component
     * rather than through get_listed_section_info_all(), which lists it as an
     * ordinary section while the subsection module is disabled.
     *
     * @return \section_info[] In course order, keyed by section id.
     */
    public function sections(\stdClass $course): array {
        $sections = [];
        foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
            if ($section->section > 0 && empty($section->component)) {
                $sections[(int)$section->id] = $section;
            }
        }
        return $sections;
    }

    /** @return int How many sections changed. */
    public function apply(\stdClass $course, schedule $schedule, \DateTimeZone $timezone): int {
        $sections = $this->sections($course);
        $plan = planner::plan(
            array_keys($sections),
            $schedule->startsectionid,
            $schedule->excluded,
            $schedule->starttime,
            $schedule->intervaldays,
            $timezone
        );

        $changed = 0;
        foreach ($plan as $sectionid => $time) {
            $current = $sections[$sectionid]->availability;
            $availability = $time === null
                ? availability_editor::without_opening($current)
                : availability_editor::with_opening($current, $time, $schedule->hidden);
            $changed += (int)$this->write($course, $sections[$sectionid], $availability);
        }
        return $changed;
    }

    /**
     * Removes the opening date from every section but the excluded ones.
     *
     * @param int[] $excluded
     * @return int How many sections changed.
     */
    public function clear(\stdClass $course, array $excluded = []): int {
        $changed = 0;
        foreach ($this->sections($course) as $sectionid => $section) {
            if (!in_array($sectionid, $excluded, true)) {
                $availability = availability_editor::without_opening($section->availability);
                $changed += (int)$this->write($course, $section, $availability);
            }
        }
        return $changed;
    }

    private function write(\stdClass $course, \section_info $section, ?string $availability): bool {
        if ($availability === $section->availability) {
            return false;
        }
        // Through the course format rather than straight into the table, so the
        // course cache is rebuilt and the section update is logged as any other.
        return formatactions::section($course)->update($section, ['availability' => $availability]);
    }
}
