<?php
/*
 * Wolf CMS - Content Management Simplified. <http://www.wolfcms.org>
 * Copyright (C) 2009-2010 Martijn van der Kleijn <martijn.niji@gmail.com>
 *
 * This file is part of Wolf CMS. Wolf CMS is licensed under the GNU GPLv3 license.
 * Please see license.txt for the full license text.
 */

/**
 * @package Wolf_CMS
 */

/*
 * This file contains some usefull utility functions which we would like to be
 * available outside the Framework.
 */

/**
 * Formats a date string using IntlDateFormatter if available, otherwise falls back to PHP's native date().
 *
 * If IntlDateFormatter is available, it assumes the pattern is an ICU pattern (e.g., 'MMMM yyyy').
 * If not, it assumes the pattern is a PHP date() format string (e.g., 'F Y').
 *
 * @param string      $date     The input date string (e.g., '2024/01/31' or '2024-01-31').
 * @param string      $pattern  ICU format pattern if Intl is available, or PHP date() format as fallback.
 * @param string|null $locale   Optional. Locale identifier (e.g., 'en_US', 'fr_FR'). Defaults to system locale.
 * @param string|null $timezone Optional. Timezone identifier. Defaults to system timezone.
 *
 * @return string The formatted date, or the original input if parsing fails.
 */
function format_date(string $date, string $pattern = 'MMMM yyyy', ?string $locale = null, ?string $timezone = null): string
{
    $normalized = strtr($date, '/', '-');

    try {
        $dateTime = new DateTime($normalized, new DateTimeZone($timezone ?? date_default_timezone_get()));
    } catch (Exception $e) {
        return $date;
    }

    if (class_exists(IntlDateFormatter::class)) {
        $formatter = new IntlDateFormatter(
            $locale ?? \Locale::getDefault(),
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            $timezone ?? date_default_timezone_get(),
            null,
            $pattern
        );

        $formatted = $formatter->format($dateTime);
        if ($formatted !== false) {
            return $formatted;
        }
    }

    // Fallback to PHP date() formatting (assuming pattern is a PHP format)
    return $dateTime->format($pattern);
}

/**
 * Tests if a text starts with an given string.
 *
 * @param     string
 * @param     string
 * @return    bool
 */
function startsWith($haystack, $needle) {
    return strpos($haystack, $needle) === 0;
}

/**
 * Tests whether a text ends with the given string or not.
 *
 * @param     string
 * @param     string
 * @return    bool
 */
function endsWith($haystack, $needle) {
    return strrpos($haystack, $needle) === strlen($haystack)-strlen($needle);
}

/**
 * Tests whether a file is writable for anyone.
 *
 * @param string $file
 * @return boolean
 */
function isWritable($file) {
    if (!file_exists($file))
        return false;

    $perms = fileperms($file);

    if (is_writable($file) || ($perms & 0x0080) || ($perms & 0x0010) || ($perms & 0x0002))
        return true;
}

?>
