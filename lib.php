<?php
defined('MOODLE_INTERNAL') || die();

/** Add the course-level management page to Moodle's course navigation. */
function local_sectionicons_extend_navigation_course(
        navigation_node $navigation,
        stdClass $course,
        context $context
): void {
    if (!has_capability('local/sectionicons:manage', $context)) {
        return;
    }
    $navigation->add_node(navigation_node::create(
        get_string('manageicons', 'local_sectionicons'),
        new moodle_url('/local/sectionicons/index.php', ['courseid' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'local_sectionicons_manage',
        new pix_icon('i/course', '')
    ));
}

/** Serve a course-scoped section icon to users who can access the course. */
function local_sectionicons_pluginfile(
        $course,
        $cm,
        context $context,
        string $filearea,
        array $args,
        bool $forcedownload,
        array $options = []
): bool {
    global $DB;

    if ($context->contextlevel !== CONTEXT_COURSE
            || $filearea !== \local_sectionicons\local\repository::FILEAREA || count($args) < 2) {
        return false;
    }
    $sectionid = (int) array_shift($args);
    $filename = array_pop($args);
    if ($args || !$DB->record_exists(\local_sectionicons\local\repository::TABLE, [
            'courseid' => $context->instanceid,
            'sectionid' => $sectionid,
            'icon' => \local_sectionicons\local\repository::IMAGE_VALUE,
        ])) {
        return false;
    }
    $courserecord = get_course($context->instanceid);
    require_login($courserecord);
    $file = get_file_storage()->get_file(
        $context->id,
        'local_sectionicons',
        $filearea,
        $sectionid,
        '/',
        $filename
    );
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, DAYSECS, 0, false, ['dontdie' => false]);
    return true;
}
