<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

use JsonSerializable;

final readonly class LocalizedName implements JsonSerializable
{
    /** @var array<string, string> */
    private array $names;

    /** @param array<string, string|null> $names */
    public function __construct(array $names)
    {
        $clean = [];
        foreach ($names as $locale => $name) {
            $name = is_string($name) ? trim($name) : '';
            if ($name !== '') {
                $clean[strtolower($locale)] = $name;
            }
        }
        $this->names = $clean;
    }

    public function get(string $locale): ?string
    {
        return $this->names[strtolower($locale)] ?? null;
    }

    /**
     * Strict lookup with an explicit fallback chain: only the requested locale
     * and the given fallbacks are considered. Returns null when none of them is
     * available, so no implicit locale policy leaks into the result.
     */
    public function displayName(string $locale, string ...$fallbacks): ?string
    {
        foreach ([$locale, ...$fallbacks] as $candidate) {
            $name = $this->names[strtolower($candidate)] ?? null;
            if ($name !== null) {
                return $name;
            }
        }

        return null;
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->names;
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return $this->names;
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** @return list<string> */
    public function aliases(): array
    {
        return array_values(array_unique($this->names));
    }

    public function matches(string $value): bool
    {
        $key = Normalizer::key($value);
        foreach ($this->names as $name) {
            if (Normalizer::key($name) === $key) {
                return true;
            }
        }

        return false;
    }
}
