<?php

namespace App\Helpers;

class InputHelper
{
    public static function sanitize(string $raw, int $maxLength = 50): string
    {
        // Remove HTML tags
        $stripped = strip_tags($raw);

        // Replace comment markers, excessive dashes, and unsafe characters
        $cleaned = preg_replace(
            ["/\/\*.*?\*\//", "/--+/", '/[;`\'"<>]/', "/\s+/"],
            ["", " ", " ", " "],
            $stripped,
        );

        // Trim and limit length
        return trim(mb_substr($cleaned, 0, $maxLength));
    }

    /**
     * Filter a list of colleagues by sanitized search input.
     * Returns matches where the name contains the search term (case-insensitive).
     */
    public static function filterColleagues(
        array $colleagues,
        string $search,
        bool $strict = false,
    ): array {
        $filter = strtolower(self::sanitize($search));

        if ($filter === "") {
            return $colleagues;
        }

        return array_filter($colleagues, function ($colleague) use ($filter, $strict) {
            $name = strtolower($colleague["Name"] ?? "");

            return $strict ? $name === $filter : str_contains($name, $filter);
        });
    }
}
