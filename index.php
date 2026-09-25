<?php
/**
 * Sets the dates a course's sections open on.
 *
 * @package local_weekopener
 */

use local_weekopener\availability_editor;
use local_weekopener\form\schedule_form;
use local_weekopener\schedule;
use local_weekopener\schedule_store;
use local_weekopener\scheduler;

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);
$context = context_course::instance($course->id);

require_login($course);
require_capability('local/weekopener:manage', $context);

$url = new moodle_url('/local/weekopener/index.php', ['id' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('nav', 'local_weekopener'));
$PAGE->set_heading($course->fullname);

$output = $PAGE->get_renderer('core');
$scheduler = new scheduler();
$store = new schedule_store();
$saved = $store->get($course->id);

$fail = static function (string $message) use ($output): void {
    echo $output->header();
    echo $output->heading(get_string('nav', 'local_weekopener'));
    echo $output->notification($message, 'error');
    echo $output->footer();
    exit;
};

// The dates are ordinary restrictions, so they need restrictions switched on and
// the date condition available, or there is nothing for them to go into.
$enabled = core_plugin_manager::instance()->get_enabled_plugins('availability') ?? [];
if (empty($CFG->enableavailability) || !array_key_exists('date', $enabled)) {
    $fail(get_string('availabilityoff', 'local_weekopener'));
}

$sections = $scheduler->sections($course);
if (!$sections) {
    $fail(get_string('nosections', 'local_weekopener'));
}

if (optional_param('clear', 0, PARAM_INT)) {
    require_sesskey();
    $changed = $scheduler->clear($course, $saved->excluded ?? []);
    redirect($url, get_string('cleared', 'local_weekopener', $changed), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Moodle names every new section "New section", so the number is what tells
// them apart.
$names = [];
foreach ($sections as $sectionid => $section) {
    $names[$sectionid] = get_string('sectionlabel', 'local_weekopener', [
        'number' => $section->section,
        'name' => get_section_name($course, $section),
    ]);
}

$form = new schedule_form($url, ['courseid' => $course->id, 'sections' => $names]);

if ($saved && !array_key_exists($saved->startsectionid, $sections)) {
    // The section it started from has since been deleted.
    $saved = null;
}
$defaults = $saved ?? new schedule(
    array_key_first($sections),
    $course->startdate ?: usergetmidnight(time())
);
$form->set_data(['id' => $course->id] + schedule_form::from_schedule($defaults));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
}

if ($data = $form->get_data()) {
    $schedule = $form->to_schedule($data);
    $changed = $scheduler->apply($course, $schedule, core_date::get_user_timezone_object());
    $store->save($course->id, $schedule);
    redirect($url, get_string('applied', 'local_weekopener', $changed), null, \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->requires->js_call_amd('local_weekopener/keep_scroll', 'init', ['id_excluded']);

echo $output->header();
echo $output->heading(get_string('nav', 'local_weekopener'));
echo html_writer::div(get_string('intro', 'local_weekopener'), 'mb-3');

$form->display();

echo $output->heading(get_string('current', 'local_weekopener'), 3, 'mt-5');

$excluded = $saved->excluded ?? [];
$table = new html_table();
$table->head = [
    get_string('section'),
    get_string('opens', 'local_weekopener'),
    get_string('whilelocked', 'local_weekopener'),
];
$table->attributes['class'] = 'generaltable local-weekopener-current';
foreach ($sections as $sectionid => $section) {
    $opening = availability_editor::opening($section->availability);
    $when = $opening
        ? userdate($opening['time'], get_string('strftimedaydatetime', 'langconfig'))
        : get_string('opens_now', 'local_weekopener');
    $mode = $opening
        ? get_string($opening['hidden'] ? 'whilelocked_hide' : 'whilelocked_show', 'local_weekopener')
        : '';
    if (in_array($sectionid, $excluded, true)) {
        $when .= ' ' . html_writer::span(get_string('skipped', 'local_weekopener'), 'badge bg-secondary text-dark');
    }
    // Hidden with the eye icon: the opening date is still set, but students
    // will not see the section until someone shows it again.
    if (!$section->visible) {
        $when .= ' ' . html_writer::span(get_string('hiddensection', 'local_weekopener'), 'badge bg-warning text-dark', [
            'title' => get_string('hiddensection_desc', 'local_weekopener'),
        ]);
    }
    $table->data[] = [s($names[$sectionid]), $when, $mode];
}
echo html_writer::table($table);

echo html_writer::div(
    $output->action_link(
        new moodle_url($url, ['clear' => 1, 'sesskey' => sesskey()]),
        get_string('clear', 'local_weekopener'),
        new confirm_action(get_string('clear_confirm', 'local_weekopener')),
        ['class' => 'btn btn-outline-danger']
    ) . html_writer::div(get_string('clear_desc', 'local_weekopener'), 'text-muted small mt-2'),
    'mb-3'
);

echo $output->footer();
