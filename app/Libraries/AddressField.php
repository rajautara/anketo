<?php

namespace App\Libraries;

class AddressField
{
    public const PARTS = [
        'street_address',
        'street_address_2',
        'city',
        'state_province',
        'postal_zip_code',
        'country',
    ];

    public const REQUIRED_PARTS = [
        'street_address',
        'city',
        'state_province',
        'postal_zip_code',
        'country',
    ];

    public const COUNTRIES = [
        'Afghanistan',
        'Albania',
        'Algeria',
        'Andorra',
        'Angola',
        'Antigua and Barbuda',
        'Argentina',
        'Armenia',
        'Australia',
        'Austria',
        'Azerbaijan',
        'Bahamas',
        'Bahrain',
        'Bangladesh',
        'Barbados',
        'Belarus',
        'Belgium',
        'Belize',
        'Benin',
        'Bhutan',
        'Bolivia',
        'Bosnia and Herzegovina',
        'Botswana',
        'Brazil',
        'Brunei',
        'Bulgaria',
        'Burkina Faso',
        'Burundi',
        'Cabo Verde',
        'Cambodia',
        'Cameroon',
        'Canada',
        'Central African Republic',
        'Chad',
        'Chile',
        'China',
        'Colombia',
        'Comoros',
        'Congo',
        'Costa Rica',
        'Cote d\'Ivoire',
        'Croatia',
        'Cuba',
        'Cyprus',
        'Czechia',
        'Democratic Republic of the Congo',
        'Denmark',
        'Djibouti',
        'Dominica',
        'Dominican Republic',
        'Ecuador',
        'Egypt',
        'El Salvador',
        'Equatorial Guinea',
        'Eritrea',
        'Estonia',
        'Eswatini',
        'Ethiopia',
        'Fiji',
        'Finland',
        'France',
        'Gabon',
        'Gambia',
        'Georgia',
        'Germany',
        'Ghana',
        'Greece',
        'Grenada',
        'Guatemala',
        'Guinea',
        'Guinea-Bissau',
        'Guyana',
        'Haiti',
        'Honduras',
        'Hungary',
        'Iceland',
        'India',
        'Indonesia',
        'Iran',
        'Iraq',
        'Ireland',
        'Israel',
        'Italy',
        'Jamaica',
        'Japan',
        'Jordan',
        'Kazakhstan',
        'Kenya',
        'Kiribati',
        'Kuwait',
        'Kyrgyzstan',
        'Laos',
        'Latvia',
        'Lebanon',
        'Lesotho',
        'Liberia',
        'Libya',
        'Liechtenstein',
        'Lithuania',
        'Luxembourg',
        'Madagascar',
        'Malawi',
        'Malaysia',
        'Maldives',
        'Mali',
        'Malta',
        'Marshall Islands',
        'Mauritania',
        'Mauritius',
        'Mexico',
        'Micronesia',
        'Moldova',
        'Monaco',
        'Mongolia',
        'Montenegro',
        'Morocco',
        'Mozambique',
        'Myanmar',
        'Namibia',
        'Nauru',
        'Nepal',
        'Netherlands',
        'New Zealand',
        'Nicaragua',
        'Niger',
        'Nigeria',
        'North Korea',
        'North Macedonia',
        'Norway',
        'Oman',
        'Pakistan',
        'Palau',
        'Palestine',
        'Panama',
        'Papua New Guinea',
        'Paraguay',
        'Peru',
        'Philippines',
        'Poland',
        'Portugal',
        'Qatar',
        'Romania',
        'Russia',
        'Rwanda',
        'Saint Kitts and Nevis',
        'Saint Lucia',
        'Saint Vincent and the Grenadines',
        'Samoa',
        'San Marino',
        'Sao Tome and Principe',
        'Saudi Arabia',
        'Senegal',
        'Serbia',
        'Seychelles',
        'Sierra Leone',
        'Singapore',
        'Slovakia',
        'Slovenia',
        'Solomon Islands',
        'Somalia',
        'South Africa',
        'South Korea',
        'South Sudan',
        'Spain',
        'Sri Lanka',
        'Sudan',
        'Suriname',
        'Sweden',
        'Switzerland',
        'Syria',
        'Taiwan',
        'Tajikistan',
        'Tanzania',
        'Thailand',
        'Timor-Leste',
        'Togo',
        'Tonga',
        'Trinidad and Tobago',
        'Tunisia',
        'Turkey',
        'Turkmenistan',
        'Tuvalu',
        'Uganda',
        'Ukraine',
        'United Arab Emirates',
        'United Kingdom',
        'United States',
        'Uruguay',
        'Uzbekistan',
        'Vanuatu',
        'Vatican City',
        'Venezuela',
        'Vietnam',
        'Yemen',
        'Zambia',
        'Zimbabwe',
    ];

    public static function sanitize($submitted): array
    {
        $submitted = is_array($submitted) ? $submitted : [];
        $out = [];

        foreach (self::PARTS as $part) {
            $out[$part] = trim((string) ($submitted[$part] ?? ''));
        }

        return $out;
    }

    public static function isBlank(array $parts): bool
    {
        foreach (self::PARTS as $part) {
            if (trim((string) ($parts[$part] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    public static function validationError(array $field, array $parts): ?string
    {
        if ((bool) ($field['is_required'] ?? false)) {
            foreach (self::REQUIRED_PARTS as $part) {
                if (($parts[$part] ?? '') === '') {
                    return $field['label'] . ' is required.';
                }
            }
        }

        if (($parts['country'] ?? '') !== '' && ! in_array($parts['country'], self::COUNTRIES, true)) {
            return 'Invalid country for ' . $field['label'] . '.';
        }

        return null;
    }

    public static function storedValue(array $parts): ?string
    {
        return self::isBlank($parts) ? null : json_encode($parts);
    }

    public static function formatJson(?string $value, string $separator = "\n"): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded) || ! self::looksLikeAddress($decoded)) {
            return null;
        }

        return self::formatParts(self::sanitize($decoded), $separator);
    }

    public static function formatParts(array $parts, string $separator = "\n"): string
    {
        $line1 = trim((string) ($parts['street_address'] ?? ''));
        $line2 = trim((string) ($parts['street_address_2'] ?? ''));

        $cityLineParts = array_values(array_filter([
            trim((string) ($parts['city'] ?? '')),
            trim((string) ($parts['state_province'] ?? '')),
            trim((string) ($parts['postal_zip_code'] ?? '')),
        ], static fn (string $value): bool => $value !== ''));

        $lines = array_values(array_filter([
            $line1,
            $line2,
            implode(', ', $cityLineParts),
            trim((string) ($parts['country'] ?? '')),
        ], static fn (string $value): bool => $value !== ''));

        return implode($separator, $lines);
    }

    private static function looksLikeAddress(array $value): bool
    {
        foreach (self::PARTS as $part) {
            if (array_key_exists($part, $value)) {
                return true;
            }
        }

        return false;
    }
}
