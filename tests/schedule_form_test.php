<?php
/**
 * What the schedule form accepts.
 *
 * @package local_weekopener
 * @covers  \local_weekopener\form\schedule_form
 */

namespace local_weekopener;

use local_weekopener\form\schedule_form;

defined('MOODLE_INTERNAL') || die();

final class schedule_form_test extends \advanced_testcase {

    private function form(): schedule_form {
        return new schedule_form(null, ['courseid' => 2, 'sections' => [11 => '1. A', 12 => '2. B', 13 => '3. C']]);
    }

    private function data(array $overrides = []): array {
        return $overrides + ['id' => 2, 'startsectionid' => 12, 'starttime' => 1791158400,
            'intervaldays' => 7, 'excluded' => [], 'hidden' => 0];
    }

    public function test_a_normal_schedule_is_accepted(): void {
        $this->assertSame([], $this->form()->validation($this->data(['excluded' => [13]]), []));
    }

    public function test_the_starting_section_cannot_be_skipped(): void {
        $errors = $this->form()->validation($this->data(['excluded' => ['12']]), []);

        $this->assertArrayHasKey('excluded', $errors);
    }

    public function test_the_interval_must_be_between_one_day_and_a_year(): void {
        $this->assertArrayHasKey('intervaldays', $this->form()->validation($this->data(['intervaldays' => 0]), []));
        $this->assertArrayHasKey('intervaldays', $this->form()->validation($this->data(['intervaldays' => 366]), []));
    }

    public function test_submitted_values_become_a_schedule(): void {
        $schedule = $this->form()->to_schedule((object)$this->data(['excluded' => ['13'], 'hidden' => 1]));

        $this->assertEquals(new schedule(12, 1791158400, 7, [13], true), $schedule);
    }
}
