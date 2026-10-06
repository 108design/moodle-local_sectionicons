<?php
namespace local_sectionicons\local;

use context_course;

/** Build a title-based, hierarchical editor view while retaining stable section ids internally. */
final class section_tree {
    public static function for_course(\stdClass $course): array {
        $modinfo = get_fast_modinfo($course);
        $format = course_get_format($course);
        $context = context_course::instance((int) $course->id);
        $sections = [];
        $children = [];
        $sectioninfos = array_filter($modinfo->get_section_info_all());
        $sectionidsbynumber = [];

        foreach ($sectioninfos as $section) {
            if (!empty($section->id)) {
                $sectionidsbynumber[(int) $section->section] = (int) $section->id;
            }
        }

        foreach ($sectioninfos as $section) {
            if (!$section || empty($section->id)) {
                continue;
            }
            $parentid = self::parent_id($section, $format, $sectionidsbynumber);
            $formatted = format_string($format->get_section_name($section), true, ['context' => $context]);
            $title = html_entity_decode(strip_tags($formatted), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $sections[(int) $section->id] = [
                'id' => (int) $section->id,
                'number' => (int) $section->section,
                'title' => $title,
                'visible' => (bool) $section->visible,
                'parentid' => $parentid,
            ];
            $children[$parentid][] = (int) $section->id;
        }

        $result = [];
        $visited = [];
        $append = function(int $parentid, int $depth) use (&$append, &$result, &$visited, $children, $sections): void {
            foreach ($children[$parentid] ?? [] as $sectionid) {
                if (isset($visited[$sectionid]) || !isset($sections[$sectionid])) {
                    continue;
                }
                $visited[$sectionid] = true;
                $item = $sections[$sectionid];
                $item['depth'] = $depth;
                $result[] = $item;
                $append($sectionid, $depth + 1);
            }
        };
        $append(0, 0);
        // A third-party delegate may expose incomplete parent metadata. Keep those sections manageable as roots.
        foreach ($sections as $sectionid => $item) {
            if (!isset($visited[$sectionid])) {
                $item['depth'] = 0;
                $result[] = $item;
            }
        }
        return $result;
    }

    /** Accept both the legacy 4.5/5.0 and namespaced 5.1+ section-info objects. */
    private static function parent_id(
        object $section,
        \core_courseformat\base $format,
        array $sectionidsbynumber
    ): int {
        try {
            if (method_exists($section, 'get_component_instance')) {
                $delegate = $section->get_component_instance();
                if ($delegate && method_exists($delegate, 'get_parent_section')) {
                    return (int) ($delegate->get_parent_section()?->id ?? 0);
                }
            }

            // format_flexsections exposes its cached `parent` option directly on section_info. Reading it there
            // avoids a stale course-format options cache after sections have been reordered in the same request.
            // Translate that presentation number immediately to the stable section record id used by this plugin.
            if ($format->get_format() === 'flexsections') {
                $parentnumber = (int) ($section->parent ?? 0);
                return $parentnumber > 0 ? ($sectionidsbynumber[$parentnumber] ?? 0) : 0;
            }
        } catch (\Throwable) {
            // An unavailable delegate or third-party format must not make the editor unusable.
        }
        return 0;
    }
}
