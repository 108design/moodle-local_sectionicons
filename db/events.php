<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for db/events.php.
 *
 * @package   local_sectionicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
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
