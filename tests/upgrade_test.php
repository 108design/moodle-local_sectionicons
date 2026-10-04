<?php
namespace local_sectionicons;

use local_sectionicons\local\repository;

/** Existing installations retain assignments, preferences and image files after table renaming. */
final class upgrade_test extends \advanced_testcase {
    public function test_rename_preserves_records_files_and_supports_resuming(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        $this->preventResetByRollback();
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/local/sectionicons/db/upgrade.php');

        $course = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $section = course_get_format($course)->get_modinfo()->get_section_info(1);
        $repository = new repository();
        $repository->set_image((int) $course->id, (int) $section->id, 'test.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><path d="M0 0h10v10H0z"/></svg>', 'large');
        $repository->set_show_in_content((int) $course->id, true);
        $assignment = $DB->get_record(repository::TABLE, ['sectionid' => $section->id], '*', MUST_EXIST);
        $preference = $DB->get_record(repository::COURSE_TABLE, ['courseid' => $course->id], '*', MUST_EXIST);
        $file = $repository->image_file((int) $course->id, (int) $section->id);
        $dbman = $DB->get_manager();

        // Simulate the old schema; the second pass simulates a previously completed first rename.
        foreach ([false, true] as $partiallyrenamed) {
            $dbman->rename_table(new \xmldb_table(repository::COURSE_TABLE), 'local_si_course');
            if (!$partiallyrenamed) {
                $dbman->rename_table(new \xmldb_table(repository::TABLE), 'local_si_icon');
            }
            set_config('version', 2026100400, 'local_sectionicons');
            try {
                $this->assertTrue(xmldb_local_sectionicons_upgrade(2026100400));
                $this->assertFalse($dbman->table_exists(new \xmldb_table('local_si_icon')));
                $this->assertFalse($dbman->table_exists(new \xmldb_table('local_si_course')));
                $this->assertEquals($assignment, $DB->get_record(repository::TABLE,
                    ['sectionid' => $section->id], '*', MUST_EXIST));
                $this->assertEquals($preference, $DB->get_record(repository::COURSE_TABLE,
                    ['courseid' => $course->id], '*', MUST_EXIST));
                $this->assertTrue($repository->show_in_content((int) $course->id));
                $this->assertSame('large', $repository->records_for_course((int) $course->id)[$section->id]->size);
                $preservedfile = $repository->image_file((int) $course->id, (int) $section->id);
                $this->assertSame($file->get_id(), $preservedfile->get_id());
                $this->assertSame($file->get_contenthash(), $preservedfile->get_contenthash());
            } finally {
                // Restore the test schema even when an assertion or upgrade fails.
                foreach (['local_si_icon' => repository::TABLE, 'local_si_course' => repository::COURSE_TABLE] as $old => $new) {
                    if ($dbman->table_exists(new \xmldb_table($old)) && !$dbman->table_exists(new \xmldb_table($new))) {
                        $dbman->rename_table(new \xmldb_table($old), $new);
                    }
                }
            }
        }
    }
}
