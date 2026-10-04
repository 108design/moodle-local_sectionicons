<?php
namespace local_sectionicons\local;

use context_course;
use invalid_parameter_exception;
use moodle_url;

/** Persistence and File API boundary for section icons. */
final class repository {
    public const TABLE = 'local_sectionicons_icon';
    public const COURSE_TABLE = 'local_sectionicons_course';
    public const FILEAREA = 'sectionicon';
    public const IMAGE_VALUE = 'image';
    public const DEFAULT_SIZE = 'normal';
    public const SIZES = ['small', 'normal', 'large', 'max'];

    /** Return records keyed by section id. */
    public function records_for_course(int $courseid): array {
        global $DB;
        $result = [];
        foreach ($DB->get_records(self::TABLE, ['courseid' => $courseid], '', '*') as $record) {
            $result[(int) $record->sectionid] = $record;
        }
        return $result;
    }

    /** Return client descriptors keyed by section id. */
    public function descriptors_for_course(int $courseid): array {
        $result = [];
        foreach ($this->records_for_course($courseid) as $record) {
            $descriptor = $this->descriptor($record);
            if ($descriptor['kind'] !== 'none') {
                $result[(string) $record->sectionid] = $descriptor;
            }
        }
        return $result;
    }

    /** Whether assigned icons should also be rendered in course-content section headings. */
    public function show_in_content(int $courseid): bool {
        global $DB;
        return $DB->record_exists(self::COURSE_TABLE, ['courseid' => $courseid, 'showcontent' => 1]);
    }

    /** Persist the course-level content rendering preference. */
    public function set_show_in_content(int $courseid, bool $enabled): bool {
        global $DB;

        if (!$DB->record_exists('course', ['id' => $courseid])) {
            throw new invalid_parameter_exception(get_string('unknowncourse', 'local_sectionicons'));
        }
        $record = $DB->get_record(self::COURSE_TABLE, ['courseid' => $courseid]);
        if (!$enabled) {
            if ($record) {
                $DB->delete_records(self::COURSE_TABLE, ['id' => $record->id]);
            }
            return false;
        }
        $now = time();
        if ($record) {
            $record->showcontent = 1;
            $record->timemodified = $now;
            $DB->update_record(self::COURSE_TABLE, $record);
        } else {
            $DB->insert_record(self::COURSE_TABLE, (object) [
                'courseid' => $courseid,
                'showcontent' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
        return true;
    }

    /** Set a built-in icon, or remove the assignment when empty. */
    public function set_icon(int $courseid, int $sectionid, string $icon, string $size = self::DEFAULT_SIZE): array {
        global $DB;
        $this->require_section($courseid, $sectionid);
        $icon = icon_catalog::normalise($icon);
        $size = self::normalise_size($size);
        $context = context_course::instance($courseid);
        $transaction = $DB->start_delegated_transaction();
        get_file_storage()->delete_area_files($context->id, 'local_sectionicons', self::FILEAREA, $sectionid);
        if ($icon === '') {
            $DB->delete_records(self::TABLE, ['courseid' => $courseid, 'sectionid' => $sectionid]);
            $transaction->allow_commit();
            return ['kind' => 'none', 'value' => '', 'class' => '', 'sectionid' => $sectionid];
        }
        $record = $this->upsert($courseid, $sectionid, $icon, $size);
        $transaction->allow_commit();
        return $this->descriptor($record);
    }

    /** Validate, store and assign one uploaded image. */
    public function set_image(
        int $courseid,
        int $sectionid,
        string $filename,
        string $content,
        string $size = self::DEFAULT_SIZE
    ): array {
        global $DB;
        $this->require_section($courseid, $sectionid);
        $image = image_validator::sanitise($filename, $content);
        $size = self::normalise_size($size);
        $context = context_course::instance($courseid);
        $transaction = $DB->start_delegated_transaction();
        $record = $this->upsert($courseid, $sectionid, self::IMAGE_VALUE, $size);
        get_file_storage()->delete_area_files($context->id, 'local_sectionicons', self::FILEAREA, $sectionid);
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'local_sectionicons',
            'filearea' => self::FILEAREA,
            'itemid' => $sectionid,
            'filepath' => '/',
            'filename' => 'icon.' . $image['extension'],
            'mimetype' => $image['mimetype'],
        ], $image['content']);
        $transaction->allow_commit();
        return $this->descriptor($record);
    }

    /** Convert one stored assignment to data consumed by the AMD renderer. */
    public function descriptor(\stdClass $record): array {
        $size = self::normalise_size((string) ($record->size ?? self::DEFAULT_SIZE));
        if ((string) $record->icon !== self::IMAGE_VALUE) {
            return ['sectionid' => (int) $record->sectionid, 'size' => $size]
                + icon_catalog::descriptor((string) $record->icon);
        }
        $file = $this->image_file((int) $record->courseid, (int) $record->sectionid);
        if (!$file) {
            return ['kind' => 'none', 'value' => '', 'class' => '', 'sectionid' => (int) $record->sectionid];
        }
        $url = moodle_url::make_pluginfile_url(
            context_course::instance((int) $record->courseid)->id,
            'local_sectionicons',
            self::FILEAREA,
            (int) $record->sectionid,
            '/',
            $file->get_filename(),
            false
        );
        $url->param('v', (int) $record->revision);
        return [
            'kind' => 'image',
            'value' => self::IMAGE_VALUE,
            'class' => '',
            'url' => $url->out(false),
            'sectionid' => (int) $record->sectionid,
            'size' => $size,
        ];
    }

    /** Change display size without changing the assigned icon or uploaded image. */
    public function set_size(int $courseid, int $sectionid, string $size): array {
        global $DB;

        $this->require_section($courseid, $sectionid);
        $size = self::normalise_size($size);
        $record = $DB->get_record(self::TABLE, ['courseid' => $courseid, 'sectionid' => $sectionid]);
        if (!$record) {
            return ['kind' => 'none', 'value' => '', 'class' => '', 'sectionid' => $sectionid, 'size' => $size];
        }
        $record->size = $size;
        $record->timemodified = time();
        $DB->update_record(self::TABLE, $record);
        return $this->descriptor($record);
    }

    public static function normalise_size(string $size): string {
        $size = trim($size);
        if (!in_array($size, self::SIZES, true)) {
            throw new invalid_parameter_exception(get_string('invalidsize', 'local_sectionicons'));
        }
        return $size;
    }

    public function image_file(int $courseid, int $sectionid): ?\stored_file {
        $files = get_file_storage()->get_area_files(
            context_course::instance($courseid)->id,
            'local_sectionicons',
            self::FILEAREA,
            $sectionid,
            'id DESC',
            false
        );
        return $files ? reset($files) : null;
    }

    public function delete_section(int $courseid, int $sectionid): void {
        global $DB;
        $context = context_course::instance($courseid, IGNORE_MISSING);
        if ($context) {
            get_file_storage()->delete_area_files($context->id, 'local_sectionicons', self::FILEAREA, $sectionid);
        }
        $DB->delete_records(self::TABLE, ['courseid' => $courseid, 'sectionid' => $sectionid]);
    }

    public function delete_course(int $courseid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['courseid' => $courseid]);
        $DB->delete_records(self::COURSE_TABLE, ['courseid' => $courseid]);
    }

    private function require_section(int $courseid, int $sectionid): void {
        global $DB;
        if (!$DB->record_exists('course_sections', ['id' => $sectionid, 'course' => $courseid])) {
            throw new invalid_parameter_exception(get_string('unknownsection', 'local_sectionicons'));
        }
    }

    private function upsert(int $courseid, int $sectionid, string $icon, string $size): \stdClass {
        global $DB;
        $now = time();
        $record = $DB->get_record(self::TABLE, ['courseid' => $courseid, 'sectionid' => $sectionid]);
        if ($record) {
            $record->icon = $icon;
            $record->size = $size;
            $record->revision = (int) $record->revision + 1;
            $record->timemodified = $now;
            $DB->update_record(self::TABLE, $record);
            return $record;
        }
        $record = (object) [
            'courseid' => $courseid,
            'sectionid' => $sectionid,
            'icon' => $icon,
            'size' => $size,
            'revision' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = $DB->insert_record(self::TABLE, $record);
        return $record;
    }
}
