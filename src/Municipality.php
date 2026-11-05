<?php

declare(strict_types=1);

namespace LeKoala\BelgianGeography;

use JsonSerializable;

final readonly class Municipality implements JsonSerializable
{
    /** The nine municipalities of the German-speaking language area (all in Wallonia). */
    public const array GERMAN_SPEAKING = [
        '63001', // Amel
        '63012', // Büllingen
        '63013', // Bütgenbach
        '63023', // Eupen
        '63040', // Kelmis
        '63048', // Lontzen
        '63061', // Raeren
        '63067', // Sankt Vith
        '63087', // Burg-Reuland
    ];

    /**
     * @param list<LocalizedName> $formerNames names of the former municipalities
     *   merged into this one (Tongeren and Borgloon for Tongeren-Borgloon). They
     *   are not current names: name()/displayName() never return them, but
     *   Belgium::municipalityByName() resolves them. A former municipality whose
     *   name was kept is not listed.
     */
    public function __construct(
        public string $nisCode,
        public string $region,
        public LocalizedName $names,
        public ?Coordinates $coordinates = null,
        public array $formerNames = [],
    ) {}

    public function name(string $locale): ?string
    {
        return $this->names->get($locale);
    }

    public function displayName(string $locale, string ...$fallbacks): ?string
    {
        return $this->names->displayName($locale, ...$fallbacks);
    }

    /**
     * Locale of the municipality's language area: nl in Flanders, fr in
     * Wallonia, de in the nine German-speaking municipalities. Null in Brussels,
     * which is bilingual: the caller chooses between fr and nl.
     */
    public function localLocale(): ?string
    {
        return match ($this->region) {
            'BE-VLG' => 'nl',
            'BE-WAL' => in_array($this->nisCode, self::GERMAN_SPEAKING, true) ? 'de' : 'fr',
            default => null,
        };
    }

    /**
     * Name in the language of the municipality's language area (its endonym):
     * Koksijde, Flobecq, Kelmis. Null in Brussels (see localLocale()) or when
     * that name is missing.
     */
    public function localName(): ?string
    {
        $locale = $this->localLocale();

        return $locale === null ? null : $this->names->get($locale);
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
