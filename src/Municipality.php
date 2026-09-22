<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

use JsonSerializable;

final readonly class Municipality implements JsonSerializable
{
    public function __construct(
        public string $nisCode,
        public string $region,
        public LocalizedName $names,
        public ?Coordinates $coordinates = null,
    ) {}

    public function name(string $locale): ?string
    {
        return $this->names->get($locale);
    }

    public function displayName(string $locale, string ...$fallbacks): ?string
    {
        return $this->names->displayName($locale, ...$fallbacks);
    }

    /** @return array{nisCode:string,region:string,names:array<string,string>,coordinates?:array{latitude:float,longitude:float}} */
    public function toArray(): array
    {
        $data = [
            'nisCode' => $this->nisCode,
            'region' => $this->region,
            'names' => $this->names->toArray(),
        ];
        if ($this->coordinates !== null) {
            $data['coordinates'] = $this->coordinates->toArray();
        }

        return $data;
    }

    /** @return array{nisCode:string,region:string,names:array<string,string>,coordinates?:array{latitude:float,longitude:float}} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
