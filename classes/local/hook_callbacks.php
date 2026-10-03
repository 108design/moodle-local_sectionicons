<?php
namespace local_sectionicons\local;

use context_course;
use core\hook\output\before_standard_top_of_body_html_generation;

/** Runtime hook integration. */
final class hook_callbacks {
    public static function before_standard_top_of_body_html(before_standard_top_of_body_html_generation $hook): void {
        global $PAGE;

        if (during_initial_install() || (defined('CLI_SCRIPT') && CLI_SCRIPT)
                || !get_config('local_sectionicons', 'version')) {
            return;
        }
        if ($PAGE->url->get_path() === '/local/sectionicons/index.php') {
            return;
        }
        $courseid = (int) ($PAGE->course->id ?? 0);
        if ($courseid <= 0 || $courseid === SITEID) {
            return;
        }
        $context = context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return;
        }
        $hasmanagecapability = has_capability('local/sectionicons:manage', $context);
        $inlineediting = $hasmanagecapability && $PAGE->user_is_editing();
        $repository = new repository();
        if (!$hasmanagecapability && !$repository->records_for_course($courseid)) {
            return;
        }
        $PAGE->requires->js_call_amd('local_sectionicons/sectionicons', 'init', [
            client_config::get($courseid, $hasmanagecapability, false, $inlineediting),
        ]);
    }
}
