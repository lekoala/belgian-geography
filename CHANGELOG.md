# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
While the version is 0.x, a minor release may contain breaking changes; they are
listed under **Changed** or **Removed**.

## [Unreleased]

### Added

- `resources/former.php`: former municipalities absorbed by the 2019 and 2025
  mergers (Borgloon/Looz and Tongeren/Tongres into Tongeren-Borgloon, Borsbeek
  into Antwerpen, Galmaarden/Gammerages, Gooik and Herne into Pajottegem, …).
  Their names are searchable like aliases, so `municipalityByName('Borgloon')`
  resolves to `73111`, and they never shadow a current BeST name.
- `Municipality::$formerNames`: names of the former municipalities merged into a
  municipality. `name()` / `displayName()` never return them.
- `Municipality::localLocale()` and `Municipality::localName()`: locale and name
  of the municipality's language area (`nl` in Flanders, `fr` in Wallonia, `de`
  in the nine German-speaking municipalities, `null` in bilingual Brussels).
  The German-speaking municipalities are listed in `Municipality::GERMAN_SPEAKING`.
- `Belgium::centerForPostalCode($code, ...$preferredNames)`: approximate center of
  a postal code. For codes shared by several municipalities (1040, 1050, 1060),
  the first municipality matching one of the given names (current name, alias,
  former name or locality name) is used.
- French exonyms of Flemish municipalities as search aliases: `Ardoye`,
  `Beringue`, `Coxyde`, `Hautem-Saint-Liévin`, `Laethem-Saint-Martin`, `Le Coq`,
  `Puers-Saint-Amand`, `Saint-Laurent`, `Thourout`, `Wavre-Sainte-Catherine`,
  `Wervicq`.
- Missing locality names: French names of the Heuvelland localities (Dranoutre,
  Locre, Neuve-Église, Westoutre, Wulverghem, Wytschaete) and Dutch names of
  the Enghien localities (Lettelingen, Mark).

### Changed

- `resources/places.php` can now complete a BeST place: a row that shares a name
  with a BeST place of the same postal code and municipality fills the locales
  BeST leaves empty, and never overrides a BeST name. Before, such a row was
  ignored.
- `Belgium::placeLabel()` also fixes the case of localities BeST publishes in
  capitals when they do not match the municipality name: the spelling of a
  former municipality is reused (1540 `HERNE` → `Herne`), other names are shown
  in title case (`BEVEREN-WAAS` → `Beveren-Waas`), and acronyms such as `SHAPE`
  are kept. `PostalPlace::name()` still returns the source spelling.

## [0.2.1] - 2026-09-22

### Added

- `resources/places.php`: supplementary postal places that BeST does not
  publish, starting with the Brussels localities 1020 Laeken/Laken,
  1120 Neder-Over-Heembeek and 1130 Haren. They are ordinary `PostalPlace`
  objects of the City of Brussels, never municipalities. A supplement can only
  name a locality of an existing BeST postal code / municipality relation.
- Search aliases for the short forms of Brussels municipalities: `Molenbeek`,
  `Saint-Josse`, `Sint-Joost`.

## [0.2.0] - 2026-09-22

### Added

- Approximate municipality centers (`Municipality::$coordinates`), computed as
  the mean of current BeST address points and shipped in the generated
  `resources/centers.php` companion.
- Province and region centers (`Province::$coordinates`, `Region::$coordinates`),
  recomposed as the barycenter of their municipalities weighted by address count.
- `Coordinates` value object.
- The data generator refuses inconsistent BeST data (a municipality in two
  regions, or with two different names in the same locale).
- The snapshot files (`data.php` and `centers.php`) are written as one unit and
  restored if a write fails.

### Changed

- `displayName()` on `LocalizedName`, `Province` and `Region`, and
  `Belgium::placeLabel()`, only use the requested locale and the explicit
  fallbacks, and return `null` when none is available. Before, they fell back to
  the first known name (or to the Dutch name).
- `Province::displayName()` and `Region::displayName()` now return `?string`.
- `centers.php` is only read next to the loaded snapshot, never from the packaged
  resources: a custom snapshot without its own centers has no coordinates.
- Aliases are documented as search aliases, not names of record.
- The generated snapshot no longer contains a `generated_at` timestamp, so
  regenerating unchanged data gives an identical file.

### Removed

- `LocalizedName::first()`.

## [0.1.0] - 2026-09-22

### Added

- First release: current Belgian municipalities (NIS code, region, French, Dutch
  and German names), postal codes and their localities, generated from the
  FPS BOSA BeST Address exports.
- Accent- and case-insensitive lookups by name (`municipalityByName()`,
  `municipalitiesByName()`, `postalPlacesByName()`) and by postal code.
- Provinces and regions with trilingual names, and lookups between
  municipalities, postal codes, provinces and regions.
- `resources/aliases.php`: search aliases missing from BeST (`Brussels`,
  `Antwerp`, `Ghent`, `Aerschot`, `Arel`, `Tongres`, …).
- `Belgium::placeLabel()` to display a locality with its municipality's spelling.
- `Normalizer::key()` to build URL slugs that resolve with the name lookups.
- Data update tool (`tools/update-data.php`).

[Unreleased]: https://github.com/lekoala/belgian-geography/compare/0.2.1...HEAD
[0.2.1]: https://github.com/lekoala/belgian-geography/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/lekoala/belgian-geography/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/lekoala/belgian-geography/releases/tag/0.1.0
