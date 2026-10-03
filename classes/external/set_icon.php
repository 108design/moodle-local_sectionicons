<?php
namespace local_sectionicons\external;

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_sectionicons\local\repository;

/** AJAX endpoint for built-in icon assignment and removal. */
final class set_icon extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'sectionid' => new external_value(PARAM_INT, 'Course section id'),
            'icon' => new external_value(PARAM_RAW_TRIMMED, 'Validated icon identifier; empty removes the icon'),
            'size' => new external_value(PARAM_ALPHA, 'Display size'),
        ]);
    }

    public static function execute(int $courseid, int $sectionid, string $icon, string $size): array {
        $params = self::validate_parameters(
            self::execute_parameters(),
            compact('courseid', 'sectionid', 'icon', 'size')
        );
        $context = context_course::instance((int) $params['courseid']);
        self::validate_context($context);
        require_capability('local/sectionicons:manage', $context);
        $descriptor = (new repository())->set_icon(
            (int) $params['courseid'],
            (int) $params['sectionid'],
            (string) $params['icon'],
            (string) $params['size']
        );
        return ['assignmentjson' => json_encode($descriptor, JSON_THROW_ON_ERROR)];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'assignmentjson' => new external_value(PARAM_RAW, 'JSON-encoded validated assignment descriptor'),
        ]);
    }
}
