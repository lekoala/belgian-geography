<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

use JsonSerializable;

final readonly class PostalPlace implements JsonSerializable
{
    public function __construct(
        public string $postalCode,
        public string $municipalityNisCode,
        public string $region,
        public LocalizedName $names,
    ) {}

    public function name(string $locale): ?string
    {
        return $this->names->get($locale);
    }

    public function displayName(string $locale, string ...$fallbacks): ?string
    {
        return $this->names->displayName($locale, ...$fallbacks);
    }

    /** @return array{postalCode:string,municipalityNisCode:string,region:string,names:array<string,string>} */
    public function toArray(): array
    {
        return [
            'postalCode' => $this->postalCode,
            'municipalityNisCode' => $this->municipalityNisCode,
            'region' => $this->region,
            'names' => $this->names->toArray(),
        ];
    }

    /** @return array{postalCode:string,municipalityNisCode:string,region:string,names:array<string,string>} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
