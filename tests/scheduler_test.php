<?php
/**
 * Writing a schedule into a real course, and what students then see.
 *
 * @package local_weekopener
 * @covers  \local_weekopener\scheduler
 */

namespace local_weekopener;

defined('MOODLE_INTERNAL') || die();

final class scheduler_test extends \advanced_testcase {

    private \stdClass $course;
    private \stdClass $student;
    private scheduler $scheduler;
    private \DateTimeZone $tz;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course(['format' => 'topics', 'numsections' => 6]);
        $this->student = $generator->create_and_enrol($this->course, 'student');
        $this->scheduler = new scheduler();
        $this->tz = new \DateTimeZone('UTC');
    }

    /** @return int Section id of section number $n. */
    private function id(int $n): int {
        return (int)get_fast_modinfo($this->course)->get_section_info($n)->id;
    }

    private function section(int $n, ?int $userid = null): \section_info {
        return get_fast_modinfo($this->course, $userid ?? 0)->get_section_info($n);
    }

    private function opening(int $n): ?array {
        return availability_editor::opening($this->section($n)->availability);
    }

    public function test_section_zero_is_never_offered(): void {
        $numbers = array_map(fn(\section_info $s): int => $s->section, $this->scheduler->sections($this->course));

        $this->assertSame([1, 2, 3, 4, 5, 6], array_values($numbers));
    }

    public function test_each_section_from_the_start_gets_its_date(): void {
        $start = 1791158400;

        $changed = $this->scheduler->apply($this->course, new schedule($this->id(2), $start), $this->tz);

        $this->assertSame(5, $changed);
        $this->assertNull($this->section(1)->availability);
        $this->assertSame($start, $this->opening(2)['time']);
        $this->assertSame($start + 7 * DAYSECS, $this->opening(3)['time']);
        $this->assertSame($start + 28 * DAYSECS, $this->opening(6)['time']);
    }

    public function test_excluded_sections_are_untouched_and_skipped(): void {
        $group = '{"op":"&","c":[{"type":"date","d":">=","t":5}],"showc":[true]}';
        course_update_section($this->course, $this->section(3), ['availability' => $group]);
        $start = 1791158400;

        $this->scheduler->apply($this->course, new schedule($this->id(2), $start, 7, [$this->id(3)]), $this->tz);

        $this->assertSame($group, $this->section(3)->availability);
        $this->assertSame($start + 7 * DAYSECS, $this->opening(4)['time']);
    }

    public function test_sections_before_the_start_lose_an_earlier_date(): void {
        $this->scheduler->apply($this->course, new schedule($this->id(1), 1791158400), $this->tz);

        $this->scheduler->apply($this->course, new schedule($this->id(3), 1791158400), $this->tz);

        $this->assertNull($this->section(1)->availability);
        $this->assertNull($this->section(2)->availability);
        $this->assertNotNull($this->opening(3));
    }

    public function test_reapplying_the_same_schedule_changes_nothing(): void {
        $schedule = new schedule($this->id(2), 1791158400, 7, [], true);
        $this->scheduler->apply($this->course, $schedule, $this->tz);

        $this->assertSame(0, $this->scheduler->apply($this->course, $schedule, $this->tz));
    }

    public function test_other_restrictions_survive(): void {
        $group = $this->getDataGenerator()->create_group(['courseid' => $this->course->id]);
        $json = '{"op":"&","c":[{"type":"group","id":' . $group->id . '}],"showc":[true]}';
        course_update_section($this->course, $this->section(2), ['availability' => $json]);

        $this->scheduler->apply($this->course, new schedule($this->id(2), 1791158400), $this->tz);
        $tree = json_decode($this->section(2)->availability);
        $this->assertSame(['date', 'group'], array_column($tree->c, 'type'));

        $this->scheduler->clear($this->course);
        $this->assertSame($json, $this->section(2)->availability);
    }

    public function test_a_student_sees_the_name_and_date_of_a_shown_section(): void {
        $this->scheduler->apply($this->course, new schedule($this->id(2), time() + WEEKSECS), $this->tz);

        $section = $this->section(2, $this->student->id);
        $this->assertFalse($section->uservisible);
        $this->assertNotEmpty($section->availableinfo);
        $this->assertTrue($this->section(1, $this->student->id)->uservisible);
    }

    public function test_a_student_sees_nothing_of_a_hidden_section(): void {
        $this->scheduler->apply($this->course, new schedule($this->id(2), time() + WEEKSECS, 7, [], true), $this->tz);

        $section = $this->section(2, $this->student->id);
        $this->assertFalse($section->uservisible);
        $this->assertEmpty($section->availableinfo);
    }

    public function test_a_section_whose_date_has_passed_is_open(): void {
        $this->scheduler->apply($this->course, new schedule($this->id(1), time() - DAYSECS, 7, [], true), $this->tz);

        $this->assertTrue($this->section(1, $this->student->id)->uservisible);
        $this->assertFalse($this->section(2, $this->student->id)->uservisible);
    }

    public function test_clear_skips_excluded_sections(): void {
        $this->scheduler->apply($this->course, new schedule($this->id(1), 1791158400), $this->tz);

        $changed = $this->scheduler->clear($this->course, [$this->id(4)]);

        $this->assertSame(5, $changed);
        $this->assertNull($this->section(3)->availability);
        $this->assertNotNull($this->opening(4));
    }

    public function test_subsections_are_left_to_their_parent(): void {
        $this->getDataGenerator()->create_module('subsection', ['course' => $this->course->id, 'section' => 1]);

        $sections = $this->scheduler->sections($this->course);

        $this->assertCount(6, $sections);
        foreach ($sections as $section) {
            $this->assertNull($section->component);
        }
    }
}
