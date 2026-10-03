# 108design Section Icons

Course-local section icons for Moodle 4.5 and newer. Teachers with course editing rights can assign a Moodle icon,
Font Awesome icon or uploaded image to each course section. Assignments use Moodle's stable `course_sections.id`, so
renaming or reordering sections does not move icons to a different section.

The plugin adds **Section icons** to course navigation. In course edit mode, the same picker is also available
directly from each section in the Course Index. Uploaded images support sanitised SVG and transparent PNG/WebP up to
1 MB and 2048 × 2048 pixels. Four display sizes apply equally to font icons and images. Files remain in Moodle's
course context and are included in course backup and restore.

The manager includes a per-course switch for also rendering the same assignments before section headings in the
main course content. Content icons follow the heading's relative type scale, while Course Index sizing remains compact.

Install the directory as `/local/sectionicons` below Moodle's web/plugin root. For Moodle 5.1 and newer this is
normally `/public/local/sectionicons`; run the normal CLI upgrade from the application root at `admin/cli`.

`local_sectionicons` replaces the former `local_courseindexicons` component. Moodle treats the new component as a
separate plugin: uninstall the predecessor before installing Section Icons. Existing assignments and uploaded icon
files are intentionally not migrated and must be configured again.

## Behavioural contract

- Rendering uses Moodle's semantic Course Index attributes and stable section IDs, never a section number or title.
- Optional course-content rendering uses the same stable section IDs and never creates a second assignment.
- Capable edit-mode users receive a visible inline Plus control. Initialisation covers every Course Index instance and
  uses bounded startup retries plus Moodle's own state/section-refresh events.
- Uploaded images are real plugin-owned image elements placed before the title; pseudo-elements are not used.
- No permanent DOM observer is required. The renderer reacts to Moodle's Course Index state and section-refresh events.
- Only users with `local/sectionicons:manage` can edit assignments or upload images.
- Icon files are private course-context files and require normal course access.
- Deleting a section or course removes its assignments; course backup/restore remaps them to restored section IDs.

## License

This is a 108design source-available commercial software license, not an open-source license. See [LICENSE.md](https://github.com/108design/moodle-local_sectionicons/blob/main/LICENSE.md) for the full terms.
