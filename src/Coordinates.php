<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

use JsonSerializable;

/**
 * A WGS84 latitude/longitude pair.
 *
 * This value object carries the point only. How it was derived (for example an
 * approximate center computed from current BeST address points) is a property of
 * the data provenance, documented on the source, not of this type.
 */
final readonly class Coordinates implements JsonSerializable
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {}

    /**
     * Weighted mean of coordinate points, each carried with a positive count of
     * contributing points. Returns null when no point contributes.
     *
     * @param iterable<array{0:Coordinates,1:int}> $points
     */
    public static function weightedMean(iterable $points): ?self
    {
        $sumLat = 0.0;
        $sumLon = 0.0;
        $weight = 0;
        foreach ($points as $point) {
            [$coordinates, $count] = $point;
            if ($count < 1) {
                continue;
            }
            $sumLat += $coordinates->latitude * $count;
            $sumLon += $coordinates->longitude * $count;
            $weight += $count;
        }
        if ($weight < 1) {
            return null;
        }

        return new self(round($sumLat / $weight, 5), round($sumLon / $weight, 5));
    }

    /** @return array{latitude:float,longitude:float} */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    /** @return array{latitude:float,longitude:float} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
