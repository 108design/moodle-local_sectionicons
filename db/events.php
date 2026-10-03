<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\\core\\event\\course_section_deleted',
        'callback' => '\\local_sectionicons\\local\\observer::section_deleted',
    ],
    [
        'eventname' => '\\core\\event\\course_deleted',
        'callback' => '\\local_sectionicons\\local\\observer::course_deleted',
    ],
];
