<?php
namespace local_sectionicons;

use advanced_testcase;
use local_sectionicons\external\set_content_display;
use local_sectionicons\external\set_size;
use local_sectionicons\local\repository;

/** Persistence tests for section icon assignments. */
final class repository_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_assignment_uses_stable_section_id_after_reordering(): void {
        $course = $this->getDataGenerator()->create_course(['format' => 'topics', 'numsections' => 3]);
        $format = course_get_format($course);
        $sections = $format->get_modinfo()->get_section_info_all();
        $sectionid = (int) $sections[1]->id;
        $repository = new repository();

        $repository->set_icon((int) $course->id, $sectionid, 'i/course', 'large');
        $this->assertTrue($format->move_section_after($sections[1], $sections[2]));

        $moved = course_get_format($course)->get_modinfo()->get_section_info_by_id($sectionid);
        $this->assertNotNull($moved);
        $this->assertSame(2, (int) $moved->section);
        $this->assertSame('i/course', $repository->records_for_course((int) $course->id)[$sectionid]->icon);
        $this->assertSame('large', $repository->records_for_course((int) $course->id)[$sectionid]->size);
        $this->assertSame('font', $repository->descriptors_for_course((int) $course->id)[(string) $sectionid]['kind']);
    }

    public function test_uploaded_svg_is_sanitised_stored_and_removed(): void {
        $course = $this->getDataGenerator()->create_course(['format' => 'topics', 'numsections' => 1]);
        $section = course_get_format($course)->get_modinfo()->get_section_info(1);
        $repository = new repository();
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">'
            . '<path fill="currentColor" d="M0 0h64v64H0z"/></svg>';

        $descriptor = $repository->set_image((int) $course->id, (int) $section->id, 'section.svg', $svg, 'max');

        $this->assertSame('image', $descriptor['kind']);
        $this->assertSame('max', $descriptor['size']);
        $this->assertStringContainsString('pluginfile.php', $descriptor['url']);
        $storedfile = $repository->image_file((int) $course->id, (int) $section->id);
        $this->assertNotNull($storedfile);
        $this->assertSame('icon.svg', $storedfile->get_filename());
        $this->assertStringNotContainsString('width="64"', $storedfile->get_content());
        $this->assertStringContainsString('aria-hidden="true"', $storedfile->get_content());

        $resized = $repository->set_size((int) $course->id, (int) $section->id, 'small');
        $this->assertSame('small', $resized['size']);

        $removed = $repository->set_icon((int) $course->id, (int) $section->id, '');
        $this->assertSame('none', $removed['kind']);
        $this->assertNull($repository->image_file((int) $course->id, (int) $section->id));
        $this->assertSame([], $repository->records_for_course((int) $course->id));
    }

    public function test_section_from_another_course_is_rejected(): void {
        $course = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $othercourse = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $section = course_get_format($othercourse)->get_modinfo()->get_section_info(1);

        $this->expectException(\invalid_parameter_exception::class);
        (new repository())->set_icon((int) $course->id, (int) $section->id, 'i/course');
    }

    public function test_size_service_updates_an_existing_assignment(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $section = course_get_format($course)->get_modinfo()->get_section_info(1);
        (new repository())->set_icon((int) $course->id, (int) $section->id, 'i/course', 'normal');

        $response = set_size::execute((int) $course->id, (int) $section->id, 'max');
        $descriptor = json_decode($response['assignmentjson'], true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('font', $descriptor['kind']);
        $this->assertSame('max', $descriptor['size']);
        $this->assertSame('max', (new repository())->records_for_course((int) $course->id)[(int) $section->id]->size);
    }

    public function test_content_display_preference_is_course_scoped_and_removable(): void {
        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $repository = new repository();

        $this->assertFalse($repository->show_in_content((int) $course->id));
        $this->assertTrue($repository->set_show_in_content((int) $course->id, true));
        $this->assertTrue($repository->show_in_content((int) $course->id));
        $this->assertFalse($repository->show_in_content((int) $othercourse->id));
        $this->assertFalse($repository->set_show_in_content((int) $course->id, false));
        $this->assertFalse($repository->show_in_content((int) $course->id));
    }

    public function test_content_display_service_updates_the_course_preference(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        $response = set_content_display::execute((int) $course->id, true);

        $this->assertTrue($response['enabled']);
        $this->assertTrue((new repository())->show_in_content((int) $course->id));
    }
}
