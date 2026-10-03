<?php
defined('MOODLE_INTERNAL') || die();

/** Restore section icon assignments with Moodle's stable course-section mapping. */
class restore_local_sectionicons_plugin extends restore_local_plugin {
    protected function define_course_plugin_structure(): array {
        return [
            new restore_path_element(
                'local_sectioniconsettings',
                $this->get_pathfor('/sectioniconsettings')
            ),
        ];
    }

    protected function define_section_plugin_structure(): array {
        return [
            new restore_path_element('local_sectionicon', $this->get_pathfor('/sectionicon')),
        ];
    }

    public function process_local_sectioniconsettings(array $data): void {
        global $DB;

        $courseid = (int) $this->task->get_courseid();
        $haspreference = $DB->record_exists(
            \local_sectionicons\local\repository::COURSE_TABLE,
            ['courseid' => $courseid]
        );
        if (!empty($data['showcontent']) && !$haspreference) {
            (new \local_sectionicons\local\repository())->set_show_in_content($courseid, true);
        }
    }

    public function process_local_sectionicon(array $data): void {
        global $DB;

        $oldsectionid = (int) $data['sectionid'];
        $sectionid = (int) $this->get_mappingid('course_section', $oldsectionid, 0);
        $courseid = (int) $this->task->get_courseid();
        if (!$sectionid || !$DB->record_exists('course_sections', ['id' => $sectionid, 'course' => $courseid])) {
            return;
        }
        try {
            $icon = \local_sectionicons\local\icon_catalog::normalise((string) $data['icon'], true);
        } catch (\invalid_parameter_exception) {
            return;
        }
        if ($icon === '') {
            return;
        }
        try {
            $size = \local_sectionicons\local\repository::normalise_size(
                (string) ($data['size'] ?? \local_sectionicons\local\repository::DEFAULT_SIZE)
            );
        } catch (\invalid_parameter_exception) {
            $size = \local_sectionicons\local\repository::DEFAULT_SIZE;
        }
        $now = time();
        $record = $DB->get_record('local_si_icon', ['courseid' => $courseid, 'sectionid' => $sectionid]);
        if ($record) {
            $record->icon = $icon;
            $record->size = $size;
            $record->revision = max(1, (int) $record->revision + 1);
            $record->timemodified = $now;
            $DB->update_record('local_si_icon', $record);
        } else {
            $DB->insert_record('local_si_icon', (object) [
                'courseid' => $courseid,
                'sectionid' => $sectionid,
                'icon' => $icon,
                'size' => $size,
                'revision' => max(1, (int) ($data['revision'] ?? 1)),
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
    }

    protected function after_execute_section(): void {
        $this->add_related_files('local_sectionicons', 'sectionicon', 'course_section');
    }
}
