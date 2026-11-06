<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for lang/en/local_sectionicons.php.
 *
 * @package   local_sectionicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Section Icons';
$string['manageicons'] = 'Section icons';
$string['manageiconsintro'] = 'Assign an icon or uploaded image to each section. The course hierarchy and titles are read directly from Moodle; section numbers are never used as identifiers.';
$string['editsectionicon'] = 'Edit icon for {$a}';
$string['chooseicon'] = 'Choose an icon for {$a}';
$string['closepicker'] = 'Close';
$string['searchicons'] = 'Search icons';
$string['noicon'] = 'No icon';
$string['uploadimage'] = 'Upload image';
$string['uploadimagehelp'] = 'SVG, transparent PNG or transparent WebP; maximum 1 MB and 2048 × 2048 pixels.';
$string['uploading'] = 'Uploading image…';
$string['iconsaved'] = 'Section icon saved.';
$string['iconremoved'] = 'Section icon removed.';
$string['emptycourse'] = 'This course has no sections that can be configured.';
$string['unknownsection'] = 'The selected section does not belong to this course.';
$string['unknowncourse'] = 'The selected course does not exist.';
$string['invalidicon'] = 'The selected icon is not valid.';
$string['invalidsize'] = 'The selected icon size is not valid.';
$string['invalidimage'] = 'The uploaded image is not a supported or valid icon image.';
$string['imagefilesize'] = 'The image may not exceed 1 MB.';
$string['imagedimensions'] = 'The image may not exceed 2048 × 2048 pixels.';
$string['imagealpha'] = 'PNG and WebP icons must contain a transparent alpha channel.';
$string['imageformat'] = 'Only SVG, transparent PNG and transparent WebP images are supported.';
$string['imagealt'] = 'Custom section icon';
$string['iconsize'] = 'Icon size';
$string['sizesmall'] = 'Small';
$string['sizenormal'] = 'Normal';
$string['sizelarge'] = 'Large';
$string['sizemax'] = 'Maximum';
$string['moreicons'] = 'Type in the search field to find more icons.';
$string['sectionhidden'] = 'Hidden';
$string['showincontent'] = 'Also show icons in the course content';
$string['showincontent_help'] = 'Displays assigned icons before the corresponding section headings on the course page.';
$string['contentdisplaysaved'] = 'Course content display updated.';
$string['privacy:metadata'] = 'Section Icons stores course-level section configuration but no personal data.';
$string['iconhome'] = 'Home';
$string['icondashboard'] = 'Dashboard';
$string['iconcourse'] = 'Course';
$string['iconusers'] = 'Users';
$string['iconuser'] = 'User';
$string['iconportfolio'] = 'Portfolio';
$string['iconnews'] = 'News';
$string['iconfiles'] = 'Files';
$string['iconfolder'] = 'Folder';
$string['iconcontentbank'] = 'Content bank';
$string['iconemail'] = 'Email';
$string['iconnotifications'] = 'Notifications';
$string['iconlink'] = 'Link';
$string['iconlocation'] = 'Location';
$string['iconlanguage'] = 'Language';
$string['iconsearch'] = 'Search';
$string['iconsettings'] = 'Settings';
$string['iconstats'] = 'Statistics';
$string['iconreport'] = 'Report';
$string['iconpayment'] = 'Payment';
$string['iconstar'] = 'Star';
$string['icontip'] = 'Tip';
$string['iconinfo'] = 'Information';
$string['iconquestions'] = 'Question';
$string['iconrss'] = 'RSS';
$string['iconcalendar'] = 'Calendar';

$string['sectionicons:manage'] = 'Manage course section icons';


$string['updateguard_heading'] = 'Update stopped';
$string['updateguard_component'] = 'The package for {$a} was rejected.';
$string['updateguard_hint'] = 'Use an approved package for the installed edition. You can review the operation again.';
$string['updateguard_unchanged'] = 'This request did not replace plugin files.';
$string['updateguard_help'] = 'The installed plugin checks the publisher signature, package contents, permitted update channel and compatibility before replacement. A valid signature does not authorise a different edition. If one package is rejected, the whole batch stops.';
$string['updateguard_signature'] = 'The publisher signature or package contents could not be verified.';
$string['updateguard_policy'] = 'This package does not match the installed edition or permitted package profile.';
$string['updateguard_channel'] = 'Updates through this channel are not enabled for this plugin.';
$string['updateguard_metadata'] = 'The version or dependency information is inconsistent.';
$string['updateguard_dependency'] = 'A required plugin version is missing. Update the dependency together with this plugin or beforehand.';
$string['updateguard_approval'] = 'The packages or installed state have changed since the preview. Review the operation again.';
$string['updateguard_busy'] = 'Another installation is running. Try again after it finishes.';
$string['updateguard_installed'] = 'The installed update protection is incomplete. Restore the files of the last approved installed version.';
$string['updateguard_operation'] = 'This combination of installation actions is not supported. Start a single update operation again.';
$string['updateguard_failed'] = 'The update could not be verified. No installation was started.';
$string['updateguard_back'] = 'Return to plugin overview';
