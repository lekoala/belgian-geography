<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

/**
 * Internal index of approximate municipality coordinates, each weighted by the
 * number of contributing BeST address points. Province and region centers are
 * recomposed from this index as the barycenter of the underlying addresses, so
 * no aggregate point has to be persisted.
 *
 * @internal
 */
final readonly class MunicipalityCoordinates
{
    /**
     * @param array<string, array{0:Coordinates,1:int,2:string}> $municipalities NIS => [coordinates, weight, region]
     */
    public function __construct(
        private array $municipalities,
    ) {}

    /** @param list<string> $prefixes */
    public function forPrefixes(array $prefixes): ?Coordinates
    {
        $lookup = array_fill_keys($prefixes, true);

        return Coordinates::weightedMean(array_map(
            static fn(array $entry): array => [$entry[0], $entry[1]],
            array_filter(
                $this->municipalities,
                static fn(int|string $nisCode): bool => array_key_exists(substr((string) $nisCode, 0, 2), $lookup),
                ARRAY_FILTER_USE_KEY,
            ),
        ));
    }

    public function forRegion(string $region): ?Coordinates
    {
        return Coordinates::weightedMean(array_map(
            static fn(array $entry): array => [$entry[0], $entry[1]],
            array_filter($this->municipalities, static fn(array $entry): bool => $entry[2] === $region),
        ));
    }
}
