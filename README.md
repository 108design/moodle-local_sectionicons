# 108design Section Icons

Make course sections easier to recognise with Moodle icons, Font Awesome icons or
your own images. Icons appear in the Course Index and can also be shown beside
section headings in the main course content.

## Installation

Requires Moodle 4.5 or later. Install as `local/sectionicons` below Moodle's plugin
directory and complete installation through **Site administration → Notifications**.
For Moodle installations using the split web directory, use `public/local/sectionicons`.

## Choosing section icons

Open **Section icons** from the course navigation, or turn editing on and use the
inline picker beside a section in the Course Index. Search for an icon, choose it,
and select one of the four display sizes. Changes save immediately.

The course manager includes a switch for displaying the same icons beside section
headings in the course content. This is optional and is disabled by default.
Renaming or reordering a section preserves its icon.

## Uploading images

Upload a sanitised SVG or a transparent PNG/WebP image through the picker. Each
image may be up to 1 MB and 2048 × 2048 pixels. The selected size applies to uploaded
images as well as font icons.

## Permissions and backups

Course editors with `local/sectionicons:manage` can change icons and upload images.
Uploaded files require access to the course. Course backup and restore include
icon assignments, uploaded images and the course's heading-display setting.

## License

This is a 108design source-available commercial software license, not an open-source license. See [LICENSE.md](https://github.com/108design/moodle-local_sectionicons/blob/main/LICENSE.md) for the full terms.
