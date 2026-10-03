<?php
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/sectionicons:manage', $context);

$url = new moodle_url('/local/sectionicons/index.php', ['courseid' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('manageicons', 'local_sectionicons'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(get_string('manageicons', 'local_sectionicons'), $url);

$repository = new \local_sectionicons\local\repository();
$assignments = $repository->descriptors_for_course($courseid);
$showcontent = $repository->show_in_content($courseid);
$sections = \local_sectionicons\local\section_tree::for_course($course);
$PAGE->requires->js_call_amd('local_sectionicons/sectionicons', 'init', [
    \local_sectionicons\local\client_config::get($courseid, true, true),
]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageicons', 'local_sectionicons'));
echo $OUTPUT->notification(get_string('manageiconsintro', 'local_sectionicons'), 'info', false);

echo html_writer::start_div('local-sectionicons-display-setting');
echo html_writer::start_div('form-check form-switch');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'class' => 'form-check-input',
    'id' => 'local-sectionicons-show-content',
    'role' => 'switch',
    'checked' => $showcontent ? 'checked' : null,
    'data-local-sectionicons-show-content' => '1',
]);
echo html_writer::tag('label', get_string('showincontent', 'local_sectionicons'), [
    'class' => 'form-check-label fw-semibold',
    'for' => 'local-sectionicons-show-content',
]);
echo html_writer::end_div();
echo html_writer::div(get_string('showincontent_help', 'local_sectionicons'), 'form-text');
echo html_writer::end_div();

if (!$sections) {
    echo $OUTPUT->notification(get_string('emptycourse', 'local_sectionicons'), 'info');
} else {
    echo html_writer::start_div('local-sectionicons-manager', [
        'data-local-sectionicons-manager' => '1',
    ]);
    echo html_writer::start_tag('ul', ['class' => 'list-unstyled local-sectionicons-section-list']);
    foreach ($sections as $section) {
        $sectionid = (int) $section['id'];
        $title = (string) $section['title'];
        $depth = max(0, (int) $section['depth']);
        $indent = number_format($depth * 1.5, 2, '.', '') . 'rem';
        $descriptor = $assignments[(string) $sectionid] ?? null;
        $classes = 'local-sectionicons-section-row' . (!$section['visible'] ? ' is-hidden' : '');
        echo html_writer::start_tag('li', [
            'class' => $classes,
            'data-local-sectionicons-section-row' => '1',
            'data-section-id' => $sectionid,
            'style' => '--local-sectionicons-depth:' . $depth,
        ]);
        echo html_writer::start_tag('button', [
            'type' => 'button',
            'class' => 'btn local-sectionicons-section-button',
            'data-local-sectionicons-edit' => '1',
            'data-section-id' => $sectionid,
            'data-section-title' => $title,
            'aria-label' => get_string('editsectionicon', 'local_sectionicons', $title),
        ]);
        echo html_writer::start_span('local-sectionicons-section-content', [
            'style' => 'margin-inline-start:' . $indent . ' !important',
        ]);
        echo html_writer::span('', 'local-sectionicons-manager-preview', [
            'data-local-sectionicons-preview' => '1',
            'data-has-icon' => $descriptor ? '1' : '0',
        ]);
        echo html_writer::span(s($title), 'local-sectionicons-section-name');
        if (!$section['visible']) {
            echo html_writer::span(get_string('sectionhidden', 'local_sectionicons'),
                'badge bg-secondary text-white local-sectionicons-hidden-badge');
        }
        echo html_writer::end_span();
        echo html_writer::end_tag('button');
        echo html_writer::end_tag('li');
    }
    echo html_writer::end_tag('ul');
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
