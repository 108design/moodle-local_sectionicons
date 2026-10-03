<?php
namespace local_sectionicons\external;

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_sectionicons\local\repository;

/** AJAX endpoint for the course-level content-heading display preference. */
final class set_content_display extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'enabled' => new external_value(PARAM_BOOL, 'Whether icons are also shown in course content'),
        ]);
    }

    public static function execute(int $courseid, bool $enabled): array {
        $params = self::validate_parameters(self::execute_parameters(), compact('courseid', 'enabled'));
        $context = context_course::instance((int) $params['courseid']);
        self::validate_context($context);
        require_capability('local/sectionicons:manage', $context);

        return [
            'enabled' => (new repository())->set_show_in_content(
                (int) $params['courseid'],
                (bool) $params['enabled']
            ),
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'enabled' => new external_value(PARAM_BOOL, 'Saved content display preference'),
        ]);
    }
}
