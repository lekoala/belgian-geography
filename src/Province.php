<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

use JsonSerializable;

final readonly class Province implements JsonSerializable
{
    /** @param array{nl:string,fr:string,en:string} $names */
    public function __construct(
        public string $isoCode,
        public string $region,
        public array $names,
    ) {}

    public function name(string $locale): ?string
    {
        return $this->names[strtolower($locale)] ?? null;
    }

    public function displayName(string $locale, string ...$fallbacks): string
    {
        foreach ([$locale, ...$fallbacks] as $candidate) {
            $name = $this->names[strtolower($candidate)] ?? null;
            if ($name !== null) {
                return $name;
            }
        }

        return $this->names['nl'];
    }

    /** @return array{isoCode:string,region:string,names:array{nl:string,fr:string,en:string}} */
    public function toArray(): array
    {
        return [
            'isoCode' => $this->isoCode,
            'region' => $this->region,
            'names' => $this->names,
        ];
    }

    /** @return array{isoCode:string,region:string,names:array{nl:string,fr:string,en:string}} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
