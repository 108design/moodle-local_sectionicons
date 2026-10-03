<?php
namespace local_sectionicons\privacy;

use core_privacy\local\metadata\null_provider;

/** Privacy provider: this plugin stores no personal data. */
final class provider implements null_provider {
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
