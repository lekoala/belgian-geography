<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography\Tools;

use LeKoala\BelgianGeography\DataLoader;
use LeKoala\BelgianGeography\Normalizer;
use RuntimeException;

final class Generator
{
    /** @var array<string, array{region:string,names:array<string,string>}> */
    private array $municipalities = [];

    /** @var array<string, array<string, array{municipality:string,region:string,names:array<string,string>}>> */
    private array $postalPlaces = [];

    /** @param resource $stream */
    public function ingestAddressCsv($stream, string $expectedRegion): void
    {
        $header = fgetcsv($stream, null, ',', '"', '\\');
        if ($header === false) {
            throw new RuntimeException('Empty BeST CSV file.');
        }

        $header = array_map(static function (string $name): string {
            return ltrim(trim($name), "\xEF\xBB\xBF");
        }, $header);
        $index = array_flip($header);

        $required = [
            'municipality_id',
            'municipality_name_nl',
            'municipality_name_fr',
            'municipality_name_de',
            'postcode',
            'postname_nl',
            'postname_fr',
            'region_code',
            'status',
        ];
        foreach ($required as $column) {
            if (!array_key_exists($column, $index)) {
                throw new RuntimeException(sprintf('Missing expected BeST OpenAddresses column "%s".', $column));
            }
        }

        while (($row = fgetcsv($stream, null, ',', '"', '\\')) !== false) {
            $value = static fn (string $column): string => trim((string) ($row[$index[$column]] ?? ''));
            if (strtolower($value('status')) !== 'current') {
                continue;
            }

            $region = $value('region_code');
            if ($region === '') {
                $region = $expectedRegion;
            }
            if ($region !== $expectedRegion) {
                throw new RuntimeException(sprintf(
                    'Unexpected BeST region "%s" in archive for "%s".',
                    $region,
                    $expectedRegion,
                ));
            }

            $nisCode = $this->extractNisCode($value('municipality_id'));
            $postalCode = preg_replace('/\D+/', '', $value('postcode')) ?? '';
            if ($nisCode === null || strlen($postalCode) !== 4) {
                continue;
            }

            $municipalityNames = $this->names(
                $value('municipality_name_nl'),
                $value('municipality_name_fr'),
                $value('municipality_name_de'),
            );
            if ($municipalityNames === []) {
                continue;
            }
            $this->mergeMunicipality($nisCode, $region, $municipalityNames);

            // The flat BOSA/OpenAddresses export already folds the regional
            // municipality-part fallback into postname_{fr,nl}. When no postal
            // label exists, the municipality names are the safest current label.
            // Combined labels ("BRUGGE/Koolkerke") are split: every part is a
            // real postal locality, grouped across locales by normalized key.
            foreach ($this->splitPostNames($value('postname_nl'), $value('postname_fr'), $municipalityNames) as $placeNames) {
                $placeKey = $nisCode . '|' . $this->nameKey($placeNames);
                $this->postalPlaces[$postalCode][$placeKey] = [
                    'municipality' => $nisCode,
                    'names' => $placeNames,
                ];
            }
        }
    }

    /**
     * @param array<string, array{url:string,sha256:string}> $sources
     * @return array<string, mixed>
     */
    public function build(array $sources = []): array
    {
        ksort($this->municipalities, SORT_STRING);
        ksort($this->postalPlaces, SORT_STRING);

        $municipalities = [];
        foreach ($this->municipalities as $nisCode => $row) {
            $municipalities[$nisCode] = [$row['region'], $row['names']];
        }

        $postalPlaces = [];
        foreach ($this->postalPlaces as $code => $places) {
            $rows = array_values($places);
            usort($rows, static function (array $a, array $b): int {
                $byMunicipality = strcmp($a['municipality'], $b['municipality']);
                if ($byMunicipality !== 0) {
                    return $byMunicipality;
                }

                return strcmp(
                    json_encode($a['names'], JSON_UNESCAPED_UNICODE) ?: '',
                    json_encode($b['names'], JSON_UNESCAPED_UNICODE) ?: '',
                );
            });
            $postalPlaces[$code] = array_map(
                static fn(array $place): array => [$place['municipality'], $place['names']],
                $rows,
            );
        }

        return [
            'schema_version' => DataLoader::SCHEMA_VERSION,
            'meta' => [
                'generated_at' => gmdate(DATE_ATOM),
                'source' => 'FPS BOSA BeST Address - OpenAddresses CSV exports',
                'license' => 'CC BY 4.0',
                'sources' => $sources,
            ],
            'municipalities' => $municipalities,
            'postal_places' => $postalPlaces,
        ];
    }

    /** @param array<string, string> $names */
    private function mergeMunicipality(string $nisCode, string $region, array $names): void
    {
        if (!isset($this->municipalities[$nisCode])) {
            $this->municipalities[$nisCode] = ['region' => $region, 'names' => $names];
            return;
        }

        foreach ($names as $locale => $name) {
            if (($this->municipalities[$nisCode]['names'][$locale] ?? '') === '') {
                $this->municipalities[$nisCode]['names'][$locale] = $name;
            }
        }
    }

    /** @return list<array<string, string>> */
    private function splitPostNames(string $nl, string $fr, array $municipalityNames): array
    {
        // A single label per locale is a translation pair ("BRUSSEL"/"BRUXELLES"):
        // keep both locales on the same locality instead of splitting by normalized
        // key, which would produce two monolingual objects. Only combined labels
        // ("BRUGGE/Koolkerke") are split into their individual localities.
        if (!str_contains($nl, '/') && !str_contains($fr, '/')) {
            $names = $this->names($nl, $fr, '');

            return $names === [] ? [$municipalityNames] : [$names];
        }

        $groups = [];
        foreach (['nl' => $nl, 'fr' => $fr] as $locale => $label) {
            foreach (explode('/', $label) as $part) {
                $part = trim($part);
                $key = $part === '' ? '' : Normalizer::key($part);
                if ($key === '') {
                    continue;
                }
                $groups[$key][$locale] ??= $part;
            }
        }

        return $groups === [] ? [$municipalityNames] : array_values($groups);
    }

    /** @return array<string, string> */
    private function names(string $nl, string $fr, string $de): array
    {
        $names = [];
        foreach (['nl' => $nl, 'fr' => $fr, 'de' => $de] as $locale => $name) {
            if ($name !== '') {
                $names[$locale] = $name;
            }
        }

        return $names;
    }

    private function extractNisCode(string $municipalityId): ?string
    {
        if (preg_match('/^\d{5}$/', $municipalityId) === 1) {
            return $municipalityId;
        }
        if (preg_match('~(?:^|/)(\d{5})(?:/|$)~', $municipalityId, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /** @param array<string, string> $names */
    private function nameKey(array $names): string
    {
        $keys = [];
        foreach ($names as $locale => $name) {
            $keys[] = $locale . ':' . Normalizer::key($name);
        }

        return implode('|', $keys);
    }
}
