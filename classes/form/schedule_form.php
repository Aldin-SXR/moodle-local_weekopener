<?php
/**
 * Where the opening dates start, how far apart they are, and what they skip.
 *
 * @package local_weekopener
 */

namespace local_weekopener\form;

use local_weekopener\schedule;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class schedule_form extends \moodleform {

    protected function definition(): void {
        $form = $this->_form;
        /** @var array<int, string> $sections Section id => name, in course order. */
        $sections = $this->_customdata['sections'];

        $form->addElement('hidden', 'id', $this->_customdata['courseid']);
        $form->setType('id', PARAM_INT);

        $form->addElement('select', 'startsectionid', get_string('startsection', 'local_weekopener'), $sections);
        $form->setType('startsectionid', PARAM_INT);
        $form->addHelpButton('startsectionid', 'startsection', 'local_weekopener');

        $form->addElement('date_time_selector', 'starttime', get_string('starttime', 'local_weekopener'));
        $form->addHelpButton('starttime', 'starttime', 'local_weekopener');

        $form->addElement('text', 'intervaldays', get_string('intervaldays', 'local_weekopener'), ['size' => 4]);
        $form->setType('intervaldays', PARAM_INT);
        $form->setDefault('intervaldays', schedule::DEFAULT_INTERVAL_DAYS);
        $form->addRule('intervaldays', null, 'required', null, 'client');

        $form->addElement('autocomplete', 'excluded', get_string('excluded', 'local_weekopener'), $sections, [
            'multiple' => true,
            'noselectionstring' => get_string('excluded_none', 'local_weekopener'),
        ]);
        $form->setType('excluded', PARAM_INT);
        $form->addHelpButton('excluded', 'excluded', 'local_weekopener');

        $form->addElement('select', 'hidden', get_string('whilelocked', 'local_weekopener'), [
            0 => get_string('whilelocked_show', 'local_weekopener'),
            1 => get_string('whilelocked_hide', 'local_weekopener'),
        ]);
        $form->setType('hidden', PARAM_INT);
        $form->addHelpButton('hidden', 'whilelocked', 'local_weekopener');

        $this->add_action_buttons(true, get_string('apply', 'local_weekopener'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $interval = (int)($data['intervaldays'] ?? 0);
        if ($interval < 1 || $interval > 365) {
            $errors['intervaldays'] = get_string('intervaldays_invalid', 'local_weekopener');
        }

        $excluded = array_map('intval', (array)($data['excluded'] ?? []));
        if (in_array((int)$data['startsectionid'], $excluded, true)) {
            $errors['excluded'] = get_string('excluded_start', 'local_weekopener');
        }
        return $errors;
    }

    public function to_schedule(\stdClass $data): schedule {
        return new schedule(
            (int)$data->startsectionid,
            (int)$data->starttime,
            (int)$data->intervaldays,
            array_values(array_map('intval', (array)($data->excluded ?? []))),
            (bool)$data->hidden
        );
    }

    public static function from_schedule(schedule $schedule): array {
        return [
            'startsectionid' => $schedule->startsectionid,
            'starttime' => $schedule->starttime,
            'intervaldays' => $schedule->intervaldays,
            'excluded' => $schedule->excluded,
            'hidden' => (int)$schedule->hidden,
        ];
    }
}
