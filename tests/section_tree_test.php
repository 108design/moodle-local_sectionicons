<?php
namespace local_sectionicons;

use local_sectionicons\local\section_tree;

/** Tests for the section hierarchy used by the management page. */
final class section_tree_test extends \advanced_testcase {
    public function test_core_subsection_is_nested_below_its_parent(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(['format' => 'topics', 'numsections' => 2]);
        $module = $this->getDataGenerator()->create_module('subsection', (object) [
            'course' => $course->id,
            'section' => 1,
            'name' => 'Nested core subsection',
        ]);
        rebuild_course_cache($course->id, true);

        $modinfo = get_fast_modinfo($course);
        $parent = $modinfo->get_section_info(1);
        $nested = $modinfo->get_section_info_by_component('mod_subsection', $module->id);
        $tree = section_tree::for_course($course);
        $byid = [];
        foreach ($tree as $item) {
            $byid[(int) $item['id']] = $item;
        }

        $this->assertSame(0, $byid[(int) $parent->id]['depth']);
        $this->assertSame((int) $parent->id, $byid[(int) $nested->id]['parentid']);
        $this->assertSame(1, $byid[(int) $nested->id]['depth']);
    }

    public function test_flexible_sections_hierarchy_uses_format_parent_option(): void {
        $this->resetAfterTest(true);
        if (!class_exists('\format_flexsections')) {
            $this->markTestSkipped('format_flexsections is not installed.');
        }

        $course = $this->getDataGenerator()->create_course(
            ['format' => 'flexsections', 'numsections' => 3],
            ['createsections' => true]
        );
        $format = course_get_format($course);
        $initialmodinfo = get_fast_modinfo($course);
        $parentrecord = $initialmodinfo->get_section_info(1);
        $childrecord = $initialmodinfo->get_section_info(2);
        $grandchildrecord = $initialmodinfo->get_section_info(3);
        $format->update_section_format_options(['id' => $childrecord->id, 'parent' => 1]);
        $format->update_section_format_options(['id' => $grandchildrecord->id, 'parent' => 2]);
        rebuild_course_cache($course->id, true);

        $modinfo = get_fast_modinfo($course);
        $parent = $modinfo->get_section_info(1);
        $child = $modinfo->get_section_info(2);
        $grandchild = $modinfo->get_section_info(3);
        $this->assertSame((int) $parent->section, (int) $child->parent);
        $this->assertSame((int) $child->section, (int) $grandchild->parent);
        $tree = section_tree::for_course($course);
        $byid = [];
        foreach ($tree as $item) {
            $byid[(int) $item['id']] = $item;
        }

        $this->assertSame(0, $byid[(int) $parent->id]['depth']);
        $this->assertSame((int) $parent->id, $byid[(int) $child->id]['parentid']);
        $this->assertSame(1, $byid[(int) $child->id]['depth']);
        $this->assertSame((int) $child->id, $byid[(int) $grandchild->id]['parentid']);
        $this->assertSame(2, $byid[(int) $grandchild->id]['depth']);
    }
}
