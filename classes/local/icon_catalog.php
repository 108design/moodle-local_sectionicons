<?php
namespace local_sectionicons\local;

use invalid_parameter_exception;

/** Validated icons presented by the section-icon picker. */
final class icon_catalog {
    /** Moodle-native identifiers and their Font Awesome fallback presentation. */
    private const ICONS = [
        'i/home' => ['home', 'fa-house'],
        'i/dashboard' => ['dashboard', 'fa-gauge'],
        'i/course' => ['course', 'fa-graduation-cap'],
        'i/users' => ['users', 'fa-user-group'],
        'i/user' => ['user', 'fa-user'],
        'i/portfolio' => ['portfolio', 'fa-briefcase'],
        'i/news' => ['news', 'fa-newspaper'],
        'i/files' => ['files', 'fa-file'],
        'i/folder' => ['folder', 'fa-folder'],
        'i/contentbank' => ['contentbank', 'fa-laptop-file'],
        'i/email' => ['email', 'fa-envelope'],
        'i/notifications' => ['notifications', 'fa-bell'],
        'i/link' => ['link', 'fa-link'],
        'i/location' => ['location', 'fa-location-dot'],
        'i/language' => ['language', 'fa-language'],
        'i/search' => ['search', 'fa-magnifying-glass'],
        'i/settings' => ['settings', 'fa-gear'],
        'i/stats' => ['stats', 'fa-chart-line'],
        'i/report' => ['report', 'fa-chart-column'],
        'i/payment' => ['payment', 'fa-credit-card'],
        'i/star' => ['star', 'fa-star'],
        'i/tip' => ['tip', 'fa-lightbulb'],
        'i/info' => ['info', 'fa-info'],
        'i/questions' => ['questions', 'fa-question'],
        'i/rss' => ['rss', 'fa-rss'],
        'i/calendar' => ['calendar', 'fa-calendar-days'],
    ];

    /** Return picker choices, including all Font Awesome icons shipped by Boost. */
    public static function options(): array {
        global $CFG;

        $options = [[
            'value' => '',
            'label' => get_string('noicon', 'local_sectionicons'),
            'class' => 'fa fa-ban',
            'group' => '',
        ]];
        foreach (self::ICONS as $identifier => [$labelkey, $fontawesome]) {
            $options[] = [
                'value' => $identifier,
                'label' => get_string('icon' . $labelkey, 'local_sectionicons'),
                'class' => 'fa ' . $fontawesome,
                'group' => 'Moodle',
            ];
        }

        $paths = [
            $CFG->dirroot . '/theme/boost/scss/fontawesome/_variables.scss',
            $CFG->dirroot . '/public/theme/boost/scss/fontawesome/_variables.scss',
        ];
        $path = null;
        foreach ($paths as $candidate) {
            if (is_readable($candidate)) {
                $path = $candidate;
                break;
            }
        }
        if ($path === null || ($scss = file_get_contents($path)) === false) {
            return $options;
        }
        foreach ([
            ['map' => 'fa-icons', 'prefix' => 'fa:solid:', 'class' => 'fa', 'group' => 'Font Awesome'],
            ['map' => 'fa-brand-icons', 'prefix' => 'fa:brand:', 'class' => 'fab', 'group' => 'Font Awesome Brands'],
        ] as $source) {
            if (!preg_match('/\\$' . preg_quote($source['map'], '/') . '\\s*:\\s*\\((.*?)\\)\\s*(?:!default\\s*)?;/s',
                    $scss, $match)) {
                continue;
            }
            preg_match_all('/"([a-z0-9-]+)"\\s*:/', $match[1], $names);
            foreach (array_unique($names[1]) as $name) {
                $options[] = [
                    'value' => $source['prefix'] . $name,
                    'label' => str_replace('-', ' ', $name),
                    'class' => $source['class'] . ' fa-' . $name,
                    'group' => $source['group'],
                ];
            }
        }
        return $options;
    }

    /** Validate a persisted value. Empty means removal; image is only set by the upload service. */
    public static function normalise(string $value, bool $allowimage = false): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if ($allowimage && $value === repository::IMAGE_VALUE) {
            return $value;
        }
        if (!isset(self::ICONS[$value])
                && !preg_match('/^fa:(solid|brand):[a-z0-9][a-z0-9-]{0,79}$/', $value)) {
            throw new invalid_parameter_exception(get_string('invalidicon', 'local_sectionicons'));
        }
        return $value;
    }

    /** Convert a validated icon value to a client-side descriptor. */
    public static function descriptor(string $value): array {
        $value = self::normalise($value);
        if (isset(self::ICONS[$value])) {
            return ['kind' => 'font', 'value' => $value, 'class' => 'fa ' . self::ICONS[$value][1] . ' icon'];
        }
        if (preg_match('/^fa:(solid|brand):([a-z0-9][a-z0-9-]{0,79})$/', $value, $match)) {
            return [
                'kind' => 'font',
                'value' => $value,
                'class' => ($match[1] === 'brand' ? 'fab' : 'fa') . ' fa-' . $match[2] . ' icon',
            ];
        }
        return ['kind' => 'none', 'value' => '', 'class' => ''];
    }
}
