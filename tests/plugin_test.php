<?php
/**
 * Smoke test: the plugin installs and its metadata is well formed.
 *
 * @package local_weekopener
 * @covers  \local_weekopener
 */

namespace local_weekopener;

defined('MOODLE_INTERNAL') || die();

final class plugin_test extends \advanced_testcase {

    public function test_plugin_is_installed(): void {
        $info = \core_plugin_manager::instance()->get_plugin_info('local_weekopener');

        $this->assertNotNull($info);
        $this->assertMatchesRegularExpression('/^\d{10}$/', (string)$info->versiondisk);
    }

    public function test_pluginname_string_resolves(): void {
        $this->assertSame('Week opener', get_string('pluginname', 'local_weekopener'));
    }

    public function test_teachers_get_the_page_and_students_do_not(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $student = $generator->create_and_enrol($course, 'student');
        $context = \context_course::instance($course->id);

        $this->assertTrue(has_capability('local/weekopener:manage', $context, $teacher));
        $this->assertFalse(has_capability('local/weekopener:manage', $context, $student));
    }
}
