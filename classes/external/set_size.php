<?php
namespace local_sectionicons\external;

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_sectionicons\local\repository;

/** AJAX endpoint for changing an assigned icon's display size. */
final class set_size extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'sectionid' => new external_value(PARAM_INT, 'Course section id'),
            'size' => new external_value(PARAM_ALPHA, 'Display size'),
        ]);
    }

    public static function execute(int $courseid, int $sectionid, string $size): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), compact('courseid', 'sectionid', 'size'));
        $courseid = (int) $params['courseid'];
        $sectionid = (int) $params['sectionid'];
        $size = (string) $params['size'];
        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('local/sectionicons:manage', $context);

        // Keep this service self-contained. During a rolling ZIP deployment PHP-FPM can briefly have this endpoint
        // and the preceding repository class generation in memory at the same time. Calling a newly-added repository
        // method would turn that harmless transition into a fatal error.
        if (!in_array($size, ['small', 'normal', 'large', 'max'], true)) {
            throw new \invalid_parameter_exception(get_string('invalidsize', 'local_sectionicons'));
        }
        if (!$DB->record_exists('course_sections', ['id' => $sectionid, 'course' => $courseid])) {
            throw new \invalid_parameter_exception(get_string('unknownsection', 'local_sectionicons'));
        }
        $record = $DB->get_record(repository::TABLE, ['courseid' => $courseid, 'sectionid' => $sectionid]);
        if (!$record) {
            $descriptor = ['kind' => 'none', 'value' => '', 'class' => '', 'sectionid' => $sectionid, 'size' => $size];
        } else {
            $record->size = $size;
            $record->timemodified = time();
            $DB->update_record(repository::TABLE, $record);
            $descriptor = (new repository())->descriptor($record);
            // An older in-memory repository descriptor did not yet include size.
            $descriptor['size'] = $size;
        }
        return ['assignmentjson' => json_encode($descriptor, JSON_THROW_ON_ERROR)];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'assignmentjson' => new external_value(PARAM_RAW, 'JSON-encoded validated assignment descriptor'),
        ]);
    }
}
