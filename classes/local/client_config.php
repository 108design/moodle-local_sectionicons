<?php
namespace local_sectionicons\local;

/** Build the trusted configuration passed to the Section Icons AMD module. */
final class client_config {
    public static function get(
        int $courseid,
        bool $canmanage,
        bool $manager = false,
        bool $inlineediting = false
    ): array {
        return [
            'courseid' => $courseid,
            'canManage' => $canmanage,
            'manager' => $manager,
            'inlineEditing' => $inlineediting,
            'showContent' => (new repository())->show_in_content($courseid),
            'assignments' => (object) (new repository())->descriptors_for_course($courseid),
            'icons' => $canmanage ? icon_catalog::options() : [],
            'strings' => [
                'choose' => get_string('chooseicon', 'local_sectionicons', '__TITLE__'),
                'edit' => get_string('editsectionicon', 'local_sectionicons', '__TITLE__'),
                'search' => get_string('searchicons', 'local_sectionicons'),
                'noicon' => get_string('noicon', 'local_sectionicons'),
                'upload' => get_string('uploadimage', 'local_sectionicons'),
                'uploadhelp' => get_string('uploadimagehelp', 'local_sectionicons'),
                'uploading' => get_string('uploading', 'local_sectionicons'),
                'saved' => get_string('iconsaved', 'local_sectionicons'),
                'removed' => get_string('iconremoved', 'local_sectionicons'),
                'imagealt' => get_string('imagealt', 'local_sectionicons'),
                'size' => get_string('iconsize', 'local_sectionicons'),
                'sizesmall' => get_string('sizesmall', 'local_sectionicons'),
                'sizenormal' => get_string('sizenormal', 'local_sectionicons'),
                'sizelarge' => get_string('sizelarge', 'local_sectionicons'),
                'sizemax' => get_string('sizemax', 'local_sectionicons'),
                'moreicons' => get_string('moreicons', 'local_sectionicons'),
                'close' => get_string('closepicker', 'local_sectionicons'),
                'contentSaved' => get_string('contentdisplaysaved', 'local_sectionicons'),
            ],
        ];
    }
}
