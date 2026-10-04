<?php
defined('MOODLE_INTERNAL') || die();

/** Include a section's icon assignment and image in course backups. */
class backup_local_sectionicons_plugin extends backup_local_plugin {
    /** Include the course-level content display preference. */
    protected function define_course_plugin_structure(): backup_nested_element {
        $plugin = $this->get_plugin_element(null);
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);
        $settings = new backup_nested_element('sectioniconsettings', ['id'], ['showcontent']);
        $wrapper->add_child($settings);
        $settings->set_source_table('local_sectionicons_course', ['courseid' => backup::VAR_COURSEID]);
        return $plugin;
    }

    protected function define_section_plugin_structure(): backup_nested_element {
        $plugin = $this->get_plugin_element(null);
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);
        $icon = new backup_nested_element('sectionicon', ['id'], [
            'sectionid', 'icon', 'size', 'revision', 'timecreated', 'timemodified',
        ]);
        $wrapper->add_child($icon);
        $icon->set_source_table('local_sectionicons_icon', ['sectionid' => backup::VAR_SECTIONID]);
        $icon->annotate_files('local_sectionicons', 'sectionicon', 'sectionid');
        return $plugin;
    }
}
