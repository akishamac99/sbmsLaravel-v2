<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Philippine mobile numbers. Accepted when typed: 09171234567, +639171234567, 639171234567,
 * with spaces, dashes or brackets. Saved in one form: 09171234567. Empty means "not given".
 */
final class PhoneNumber
{
    public const EXAMPLE = '09171234567';

    /** The saved form, or null when the value is empty or not a valid mobile number. */
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/[\s\-().]/', '', trim((string) $value));

        if ($digits !== '' && preg_match('/^(?:\+?63|0)(9\d{9})$/', $digits, $match)) {
            return '0'.$match[1];
        }

        return null;
    }

    /**
     * The first four digits (09XX) of numbers issued in the Philippines (Globe, TM, Smart, TNT, Sun, DITO).
     * Edit this list if a network adds a new prefix.
     */
    public const PREFIXES = [
        '0817', '0895', '0896', '0897', '0898', '0905', '0906', '0907', '0908', '0909', '0910', '0911', '0912', '0913', '0914',
        '0915', '0916', '0917', '0918', '0919', '0920', '0921', '0922', '0923', '0924', '0925', '0926', '0927', '0928', '0929',
        '0930', '0931', '0932', '0933', '0934', '0935', '0936', '0937', '0938', '0939', '0940', '0941', '0942', '0943', '0944',
        '0945', '0946', '0947', '0948', '0949', '0950', '0951', '0953', '0954', '0955', '0956', '0961', '0965', '0966', '0967',
        '0968', '0969', '0970', '0973', '0974', '0975', '0976', '0977', '0978', '0979', '0981', '0989', '0991', '0992', '0993',
        '0994', '0995', '0996', '0997', '0998', '0999',
    ];

    /** True when a saved-form number (09XXXXXXXXX) starts with a prefix that a Philippine network really uses. */
    public static function hasKnownPrefix(string $normalized): bool
    {
        return in_array(substr($normalized, 0, 4), self::PREFIXES, true);
    }

    /** Obvious made-up numbers: the last seven digits are all the same, like 09170000000. */
    public static function looksMadeUp(string $normalized): bool
    {
        return (bool) preg_match('/^(\d)\1{6}$/', substr($normalized, 4));
    }

    public static function isBlank(?string $value): bool
    {
        return trim((string) $value) === '';
    }

    /** Put a valid number into the request in its saved form, so uniqueness checks compare like with like. */
    public static function mergeNormalized(Request $request, string $key): void
    {
        $raw = $request->input($key);
        $normalized = self::normalize(is_string($raw) ? $raw : null);

        if ($normalized !== null) {
            $request->merge([$key => $normalized]);
        } elseif (is_string($raw) && self::isBlank($raw)) {
            $request->merge([$key => null]);
        }
    }
}
