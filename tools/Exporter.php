<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tools;

use LeKoala\BelgianGeography\DataLoader;

/**
 * Compact PHP exporter for the generated snapshot: short array syntax with
 * one record per line for municipalities and postal places (meta stays
 * multiline). Records are tuples (positional) and list indices are omitted;
 * associative keys such as locale names stay explicit. Scalars reuse
 * var_export() so escaping and numeric-key semantics match.
 */
final class Exporter
{
    /** @param array<string, mixed> $data */
    public static function export(array $data): string
    {
        $out = "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n";
        $out .= "'schema_version' => " . self::scalar($data['schema_version'] ?? DataLoader::SCHEMA_VERSION) . ",\n";
        $out .= "'meta' => " . self::block($data['meta'] ?? [], 1) . ",\n";
        foreach (['municipalities', 'postal_places'] as $section) {
            $out .= "'{$section}' => [\n";
            foreach ($data[$section] ?? [] as $key => $row) {
                $out .= '  ' . self::scalar($key) . ' => ' . self::inline($row) . ",\n";
            }
            $out .= "],\n";
        }

        return $out . "];\n";
    }

    private static function block(mixed $value, int $depth): string
    {
        if (!is_array($value) || $value === []) {
            return self::inline($value);
        }
        if (array_is_list($value)) {
            return self::inline($value);
        }

        $pad = str_repeat('  ', $depth);
        $out = "[\n";
        foreach ($value as $key => $item) {
            $out .= $pad . self::scalar($key) . ' => ' . self::block($item, $depth + 1) . ",\n";
        }

        return $out . str_repeat('  ', $depth - 1) . ']';
    }

    private static function inline(mixed $value): string
    {
        if (!is_array($value)) {
            return self::scalar($value);
        }
        if ($value === []) {
            return '[]';
        }

        $parts = [];
        if (array_is_list($value)) {
            foreach ($value as $item) {
                $parts[] = self::inline($item);
            }
        } else {
            foreach ($value as $key => $item) {
                $parts[] = self::scalar($key) . ' => ' . self::inline($item);
            }
        }

        return '[' . implode(', ', $parts) . ']';
    }

    private static function scalar(mixed $value): string
    {
        return var_export($value, true);
    }
}
