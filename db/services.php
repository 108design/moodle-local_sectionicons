<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for db/services.php.
 *
 * @package   local_sectionicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_sectionicons_set_icon' => [
        'classname' => 'local_sectionicons\\external\\set_icon',
        'methodname' => 'execute',
        'description' => 'Set or remove the icon assigned to one course section.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/sectionicons:manage',
    ],
    'local_sectionicons_upload_image' => [
        'classname' => 'local_sectionicons\\external\\upload_image',
        'methodname' => 'execute',
        'description' => 'Upload and assign a validated image to one course section.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/sectionicons:manage',
    ],
    'local_sectionicons_set_size' => [
        'classname' => 'local_sectionicons\\external\\set_size',
        'methodname' => 'execute',
        'description' => 'Change the display size of an assigned course-section icon.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/sectionicons:manage',
    ],
    'local_sectionicons_set_content_display' => [
        'classname' => 'local_sectionicons\\external\\set_content_display',
        'methodname' => 'execute',
        'description' => 'Set whether assigned icons are also shown in course-content section headings.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/sectionicons:manage',
    ],
];
