<?php

namespace Dakshraman\AIDebugger\Helpers;

class LogParser
{
    /**
     * Extract error/exception entries from a Laravel log file.
     *
     * @return string[]
     */
    public static function extractErrors(string $log): array
    {
        // Matches a complete Laravel log entry from its opening timestamp bracket
        // "[YYYY-MM-DD HH:MM:SS]" up to (but not including) the next entry's timestamp or end of string.
        $pattern = '/\[\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*\]\s+\w+\.\w+:.*?(?=\[\d{4}-\d{2}-\d{2}|$)/s';

        preg_match_all($pattern, $log, $matches);

        $entries = $matches[0] ?? [];

        return array_values(array_filter(
            array_map('trim', $entries),
            fn (string $entry) => stripos($entry, 'error') !== false
                || stripos($entry, 'exception') !== false
                || stripos($entry, 'critical') !== false
                || stripos($entry, 'alert') !== false
                || stripos($entry, 'emergency') !== false
        ));
    }
}
