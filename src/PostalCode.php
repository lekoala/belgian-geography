<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

use JsonSerializable;

final readonly class PostalCode implements JsonSerializable
{
    /** @param list<PostalPlace> $places */
    public function __construct(
        public string $code,
        private array $places,
    ) {}

    /** @return list<PostalPlace> */
    public function places(): array
    {
        return $this->places;
    }

    /** @return list<string> */
    public function municipalityNisCodes(): array
    {
        return array_values(array_unique(array_map(
            static fn(PostalPlace $place): string => $place->municipalityNisCode,
            $this->places,
        )));
    }

    /** @return array{code:string,places:list<array<string,mixed>>} */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'places' => array_map(static fn(PostalPlace $place): array => $place->toArray(), $this->places),
        ];
    }

    /** @return array{code:string,places:list<array<string,mixed>>} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
