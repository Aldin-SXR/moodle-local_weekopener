<?php
/**
 * Working out when each section opens.
 *
 * @package local_weekopener
 * @covers  \local_weekopener\planner
 */

namespace local_weekopener;

defined('MOODLE_INTERNAL') || die();

final class planner_test extends \basic_testcase {

    private \DateTimeZone $tz;

    protected function setUp(): void {
        parent::setUp();
        $this->tz = new \DateTimeZone('Europe/Sarajevo');
    }

    private function time(string $local): int {
        return (new \DateTimeImmutable($local, $this->tz))->getTimestamp();
    }

    /** @return array<int, ?string> Section id => local date, for readable assertions. */
    private function readable(array $plan): array {
        return array_map(
            fn(?int $t): ?string => $t === null
                ? null
                : (new \DateTimeImmutable('@' . $t))->setTimezone($this->tz)->format('Y-m-d H:i'),
            $plan
        );
    }

    /** Fifteen sections, the second opening on 5 October, a week apart. */
    public function test_sections_open_a_week_apart_from_the_starting_one(): void {
        $plan = planner::plan(range(1, 15), 2, [], $this->time('2026-10-05 00:00'), 7, $this->tz);

        $readable = $this->readable($plan);
        $this->assertNull($readable[1]);
        $this->assertSame('2026-10-05 00:00', $readable[2]);
        $this->assertSame('2026-10-12 00:00', $readable[3]);
        $this->assertSame('2026-10-19 00:00', $readable[4]);
        $this->assertSame('2027-01-04 00:00', $readable[15]);
        $this->assertCount(15, $plan);
    }

    public function test_an_excluded_section_is_left_out_and_takes_no_slot(): void {
        $plan = planner::plan(range(1, 6), 2, [4], $this->time('2026-10-05 00:00'), 7, $this->tz);

        $this->assertSame([
            1 => null,
            2 => '2026-10-05 00:00',
            3 => '2026-10-12 00:00',
            5 => '2026-10-19 00:00',
            6 => '2026-10-26 00:00',
        ], $this->readable($plan));
    }

    public function test_an_excluded_section_before_the_start_is_not_opened(): void {
        $plan = planner::plan(range(1, 4), 3, [1], $this->time('2026-10-05 00:00'), 7, $this->tz);

        $this->assertArrayNotHasKey(1, $plan);
        $this->assertNull($plan[2]);
    }

    public function test_sections_follow_course_order_not_id_order(): void {
        $plan = planner::plan([30, 10, 20], 10, [], $this->time('2026-10-05 00:00'), 1, $this->tz);

        $this->assertSame([30 => null, 10 => '2026-10-05 00:00', 20 => '2026-10-06 00:00'], $this->readable($plan));
    }

    public function test_the_local_time_holds_across_a_daylight_saving_change(): void {
        // Clocks go back in Sarajevo on 25 October 2026.
        $plan = planner::plan([1, 2], 1, [], $this->time('2026-10-20 08:00'), 7, $this->tz);

        $this->assertSame('2026-10-27 08:00', $this->readable($plan)[2]);
        $this->assertSame(7 * DAYSECS + HOURSECS, $plan[2] - $plan[1]);
    }

    public function test_a_custom_interval_is_used(): void {
        $plan = planner::plan([1, 2, 3], 1, [], $this->time('2026-10-05 09:30'), 14, $this->tz);

        $this->assertSame('2026-11-02 09:30', $this->readable($plan)[3]);
    }

    public function test_the_starting_section_cannot_be_excluded(): void {
        $this->expectException(\coding_exception::class);
        planner::plan([1, 2], 2, [2], 0, 7, $this->tz);
    }

    public function test_the_starting_section_must_be_in_the_course(): void {
        $this->expectException(\coding_exception::class);
        planner::plan([1, 2], 9, [], 0, 7, $this->tz);
    }

    public function test_the_interval_must_be_positive(): void {
        $this->expectException(\coding_exception::class);
        planner::plan([1, 2], 1, [], 0, 0, $this->tz);
    }
}
