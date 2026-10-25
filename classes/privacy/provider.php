<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/privacy/provider.php.
 *
 * @package   local_sectionicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
namespace local_sectionicons\privacy;

use core_privacy\local\metadata\null_provider;

/** Privacy provider: this plugin stores no personal data. */
final class provider implements null_provider {
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
