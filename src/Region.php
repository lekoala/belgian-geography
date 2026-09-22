<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

use JsonSerializable;

final readonly class Region implements JsonSerializable
{
    /** @param array{nl:string,fr:string,en:string} $names */
    public function __construct(
        public string $isoCode,
        public array $names,
        public ?Coordinates $coordinates = null,
    ) {}

    public function name(string $locale): ?string
    {
        return $this->names[strtolower($locale)] ?? null;
    }

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

    /** @return array{isoCode:string,names:array{nl:string,fr:string,en:string},coordinates?:array{latitude:float,longitude:float}} */
    public function toArray(): array
    {
        $data = [
            'isoCode' => $this->isoCode,
            'names' => $this->names,
        ];
        if ($this->coordinates !== null) {
            $data['coordinates'] = $this->coordinates->toArray();
        }

        return $data;
    }

    /** @return array{isoCode:string,names:array{nl:string,fr:string,en:string},coordinates?:array{latitude:float,longitude:float}} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
