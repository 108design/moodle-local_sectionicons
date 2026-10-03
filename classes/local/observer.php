<?php
namespace local_sectionicons\local;

/** Remove configuration whose owning Moodle object has been deleted. */
final class observer {
    public static function section_deleted(\core\event\course_section_deleted $event): void {
        (new repository())->delete_section((int) $event->courseid, (int) $event->objectid);
    }

    public static function course_deleted(\core\event\course_deleted $event): void {
        (new repository())->delete_course((int) $event->courseid);
    }
}
