<?php
/**
 * Remembering a course's schedule, and forgetting it with the course.
 *
 * @package local_weekopener
 * @covers  \local_weekopener\schedule_store
 * @covers  \local_weekopener\observer
 */

namespace local_weekopener;

defined('MOODLE_INTERNAL') || die();

final class schedule_store_test extends \advanced_testcase {

    public function test_a_schedule_round_trips(): void {
        $this->resetAfterTest();
        $store = new schedule_store();
        $schedule = new schedule(12, 1791158400, 14, [13, 15], true);

        $store->save(3, $schedule);

        $this->assertEquals($schedule, $store->get(3));
        $this->assertNull($store->get(4));
    }

    public function test_saving_again_replaces_the_schedule(): void {
        global $DB;
        $this->resetAfterTest();
        $store = new schedule_store();

        $store->save(3, new schedule(12, 1, 7, [13]));
        $store->save(3, new schedule(14, 2));

        $this->assertEquals(new schedule(14, 2), $store->get(3));
        $this->assertSame(1, $DB->count_records('local_weekopener'));
    }

    public function test_deleting_the_course_deletes_its_schedule(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $store = new schedule_store();
        $store->save($course->id, new schedule(1, 1));

        delete_course($course, false);

        $this->assertNull($store->get($course->id));
    }
}
