<?php
/**
 * Remembers each course's schedule, so the form opens as it was last applied.
 *
 * @package local_weekopener
 */

namespace local_weekopener;

defined('MOODLE_INTERNAL') || die();

final class schedule_store {

    private const TABLE = 'local_weekopener';

    public function get(int $courseid): ?schedule {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['courseid' => $courseid]);
        if (!$record) {
            return null;
        }
        return new schedule(
            (int)$record->startsectionid,
            (int)$record->starttime,
            (int)$record->intervaldays,
            array_map('intval', array_filter(explode(',', (string)$record->excluded))),
            (bool)$record->hidden
        );
    }

    public function save(int $courseid, schedule $schedule): void {
        global $DB;

        $record = (object)[
            'courseid' => $courseid,
            'startsectionid' => $schedule->startsectionid,
            'starttime' => $schedule->starttime,
            'intervaldays' => $schedule->intervaldays,
            'excluded' => implode(',', $schedule->excluded),
            'hidden' => (int)$schedule->hidden,
            'timemodified' => time(),
        ];

        $id = $DB->get_field(self::TABLE, 'id', ['courseid' => $courseid]);
        if ($id) {
            $record->id = $id;
            $DB->update_record(self::TABLE, $record);
        } else {
            $DB->insert_record(self::TABLE, $record);
        }
    }

    public function delete(int $courseid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['courseid' => $courseid]);
    }
}
