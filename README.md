# Belgian Geography

Small, framework-agnostic PHP reference library for current Belgian municipalities,
postal codes and multilingual place names.

It is intentionally **not** an address/geocoder library. Use Google Maps, BeST or
another geocoder when you need street-level addresses. This package answers the
smaller questions applications repeatedly need to solve locally:

- Which municipality has this NIS code?
- What is its current French, Dutch or German name?
- Does `Namen` mean the same municipality as `Namur`?
- Which municipalities can occur under postal code `1040`?
- Which postal place corresponds to `Jambes`?

## Design

The runtime package has no framework, database or HTTP dependency. It ships a compact
snapshot generated from the weekly **FPS BOSA BeST Address** exports.

Municipality identity is the **NIS code**, never a localized name or a postal code.
French/Dutch/German BeST names are indexed as equivalent lookup names.

On top of the generated snapshot, `resources/aliases.php` holds a minimal,
versioned alias layer for common exonyms the BeST snapshot does not contain:
English usage that differs from the native name (`Brussels`, `Antwerp`,
`Ghent`), plus French/Dutch/German alternate names attested on Wikipedia
(`Aerschot`, `Arel`, `Tongres`, …). An entry is kept only when attested *and*
missing from BeST (verified with `tools/check-alias-coverage.php`); at load
time an alias never shadows a current BeST name. Archaic spellings stay out:
historical SEO slugs such as an obsolete spelling belong to the application
that served them, not to a current Belgian reference library.

## Install

```bash
composer require lekoala/belgian-geography
```

## Usage

```php
use LeKoala\BelgianGeography\Belgium;

$be = Belgium::load();

$liege = $be->municipalityByName('Luik');
echo $liege?->nisCode;    // 62063
echo $liege?->name('fr'); // Liège

echo $be->municipalityByName('Liege')?->name('de'); // accent-insensitive lookup

$namur = $be->municipalityByName('Namen');
echo $namur?->name('fr'); // Namur

$postcode = $be->postalCode('1040');
foreach ($postcode?->places() ?? [] as $place) {
    echo $place->municipalityNisCode;
}

foreach ($be->postalPlacesByName('Jambes') as $place) {
    echo $place->postalCode; // 5100
}

foreach ($be->postalPlacesByName('Koolkerke') as $place) {
    echo $place->postalCode; // 8000, sub-municipality of Brugge
}

foreach ($be->postalCodesForRegion('BE-BRU') as $postalCode) {
    echo $postalCode->code; // 1000, 1020, 1030, …
}

echo $be->provinceForMunicipality('62063')?->isoCode; // BE-WLG
echo $be->province('BE-VWV')?->name('en'); // West Flanders

echo $be->regionForMunicipality('21004')?->name('fr'); // Bruxelles
echo $be->provinceForMunicipality('21004');            // null: Brussels is a region, not a province

// Fallback-aware label for UI (name() stays strict):
echo $be->municipality('92094')?->displayName('de', 'fr'); // Namur

echo json_encode($be->municipality('92094'));
// {"nisCode":"92094","region":"BE-WAL","names":{"nl":"Namen","fr":"Namur","de":"Namur"}}
```

### Searching names: one result or several

For user-facing search, resolve against `municipalitiesByName()` and handle the
three cases — none, one, several. `municipalityByName()` is a convenience that
returns `null` on **ambiguity**, which is not the same as "not found", so don't
present it as an empty result:

```php
$matches = $be->municipalitiesByName('Saint-Nicolas');

// Ambiguous: 46021 (Sint-Niklaas, FR exonym) and 62093 (Liège).
foreach ($matches as $municipality) {
    $province = $be->provinceForMunicipality($municipality->nisCode);
    echo $municipality->displayName('fr')
        . ' (' . $municipality->name('nl') . ') — ' . $province?->name('fr') . PHP_EOL;
}
// Saint-Nicolas (Sint-Niklaas) — Flandre orientale
// Saint-Nicolas (Saint-Nicolas) — Liège

echo $be->municipalityByName('Namen')?->nisCode; // 92094 (single match)
```

### Display labels

Search is case- and accent-insensitive. For presentation, `placeLabel()` reuses
the municipality spelling when the locality name is the same word as its
municipality in the same locale; otherwise the source spelling is kept. The raw
source label stays available through `name()` / `displayName()`:

```php
$halle = $be->postalPlacesByName('Halle')[0]; // postal code 1500
echo $halle->name('nl');                     // HALLE — source spelling
echo $be->placeLabel($halle, 'nl');          // Halle — municipality spelling

// A locality whose name differs from its municipality keeps the source graphy:
// 2980 "Halle" is a locality of the municipality of Zoersel.
```

### Resolving URL slugs

`Normalizer::key()` is the canonical slug function: it lowercases, folds accents
and drops spaces/punctuation. Generate slugs with it and resolve them with the
name lookups — so `/city/liege` finds `Liège`, and `/city/luik` finds the same
municipality:

```php
use LeKoala\BelgianGeography\Normalizer;

Normalizer::key('Liège');               // "liege"
Normalizer::key('La Roche-en-Ardenne'); // "larocheenardenne"

$be->municipalityByName('liege')?->nisCode; // 62063
$be->municipalityByName('luik')?->nisCode;  // 62063
```

`municipalityByName()` is an exact normalized match (not a prefix) and returns
`null` on ambiguity; a slug route should distinguish the three cases:

```php
$matches = $be->municipalitiesByName($slug);

if ($matches === []) {
    // 404
} elseif (count($matches) === 1) {
    // single city page
} else {
    // disambiguate, e.g. "saint-nicolas" -> 46021 / 62093
}
```

Locality slugs resolve through `postalPlacesByName()`. If the route must also
accept sub-municipalities, fall back to it and use the locality's municipality:

```php
$city = $be->municipalityByName($slug); // null when unknown or ambiguous
if ($city === null) {
    $place = $be->postalPlacesByName($slug)[0] ?? null;
    $city = $place === null ? null : $be->municipality($place->municipalityNisCode);
}
```

Archaic or renamed slugs (`blankenberghe`, `rethy`, …) are out of scope and stay
in the application's own redirect map (see Scope).

### Important: postal codes are not municipality IDs

The model intentionally keeps postal codes and municipalities separate. Their relation
is not globally one-to-one; Brussels contains real exceptions where the exact address
matters. `municipalitiesForPostalCode()` therefore returns a list.

## Data model

The public model is deliberately small:

- `Municipality`: NIS code, region code, multilingual names
- `PostalCode`: a four-digit code and its current postal places
- `PostalPlace`: postal code + municipality + multilingual locality/postal name
- `Province`: ISO 3166-2 code, region code, trilingual names (`nl`, `fr`, `en`)
- `Region`: ISO 3166-2 code and trilingual names (`BE-VLG`, `BE-BRU`, `BE-WAL`)
- `LocalizedName`: current names keyed by `nl`, `fr`, `de`

The snapshot stores a region once per municipality. A locality carries no region
of its own: `PostalPlace::$region` is derived from the referenced municipality at
load time, and a locality that references an unknown municipality is rejected.

`resources/data.php` is an internal, versioned storage format (`schema_version`).
Its records are compact tuples — a municipality is `[region, names]`, a locality is
`[municipality NIS code, names]` — and the layout is **not** a public contract.
Always read the snapshot through the `Belgium` API, whose objects and JSON are stable.

Regions are represented by `BE-VLG`, `BE-BRU` and `BE-WAL`. There are exactly
**ten provinces** (`BE-VAN`, `BE-VWV`, …) derived from NIS prefix ranges; the
Brussels-Capital Region is a region, **not** a province, so
`provinceForMunicipality('21004')` returns `null` while
`regionForMunicipality('21004')` returns `BE-BRU`. Every model exposes
`toArray()` / `JsonSerializable`, plus a fallback-aware `displayName()` next
to the strict `name()`.
Combined BeST postal labels (`BRUGGE/Koolkerke`) are split so every locality —
including sub-municipalities — resolves to its municipality and postal code.
Locality label casing is preserved from the source: BeST publishes some Flemish
`postname_*` values in all caps (`HALLE` for postal code `1500`, whose
municipality is `Halle`). The library does not rewrite proper names; all lookups
are case- and accent-insensitive, and `placeLabel()` offers a cleaned display
label when the locality name matches its municipality (see Display labels).
Streets, coordinates and full addresses are deliberately out of scope for v0.x.

### Partial / custom snapshots

`Belgium::load($path, usePackagedReferences: false)` loads a snapshot without
falling back to the packaged `aliases.php` / `provinces.php` / `regions.php`.
Companion files placed next to the snapshot are still loaded. This keeps
fixtures and partial datasets (which may not contain every municipality the
packaged alias layer references) loadable; with the default `true`, those
packaged references apply.

## Updating the bundled data

Source checkouts can regenerate `resources/data.php` directly from the three weekly
BOSA CSV archives:

```bash
composer data:update
```

The updater downloads:

- `openaddress-bevlg.zip`
- `openaddress-bebru.zip`
- `openaddress-bewal.zip`

It scans only current address rows from `openaddress-be*.csv` to derive the compact municipality/postal-place relation, then discards street, house-number and coordinate data. Large BeST source
files never become part of the Composer package.

`ext-zip` is needed only for this maintainer command, not at runtime.

To regenerate from archives already downloaded locally:

```bash
php tools/update-data.php --source-dir=/path/to/archives
```

## Why derive from addresses?

BeST correctly models municipality and postal information as separate objects. In
particular, the relation is many-to-many in some Brussels cases. Reading the current
address relations at build time preserves that reality while allowing the published
runtime dataset to remain tiny.

For locality labels, the generator consumes the current flat BOSA/OpenAddresses `postname_*` fields. Those fields already incorporate the regional fallback used by BeST (including municipality parts where applicable); when no postal label is present, the municipality names are used. Combined labels such as `BRUGGE/Koolkerke` are split into individual localities.

## Data provenance

The source exports are published by FPS BOSA from the authentic regional address
registers and are refreshed weekly. Generated data records its generation timestamp,
source URLs and SHA-256 hashes.

See [DATA-LICENSE.md](DATA-LICENSE.md) for attribution and data licensing.

## Scope

This package intentionally does **not** provide:

- street/address autocomplete,
- geocoding,
- map coordinates,
- a manually curated list of old/archaic spellings (only current alternate
  names attested on Wikipedia are aliased, see Design).

Those concerns can be added by separate packages or application code if a real use case
appears. The goal here is a boring, reliable Belgian reference layer.
