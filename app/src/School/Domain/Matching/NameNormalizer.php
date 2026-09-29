<?php

declare(strict_types=1);

namespace App\School\Domain\Matching;

final class NameNormalizer
{
    private const array TRANSLITERATION = [
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
        'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
    ];

    /** Order matters: "liceum ogolnoksztalcace" must be collapsed before anything else touches it. */
    private const array CANONICAL_PHRASES = [
        '/\bliceum(?: ogolnoksztalca\w*)?\b/' => 'lo',
        '/\bzespo\w* szkol\w*\b/' => 'zs',
        '/\btech\b/' => 'technikum',
    ];

    private const string ROMAN_PATTERN = '/^(x{0,3})(ix|iv|v?i{0,3})$/';

    private const array ROMAN_VALUES = ['i' => 1, 'v' => 5, 'x' => 10];

    private const string ORDINAL_PATTERN = '/^(pierwsz|drug|trzec|czwart|piat|szost|siodm|osm|dziewiat|dziesiat)(y|a|e|i|ie|iego|ego|ej|iej)$/';

    private const array ORDINAL_STEMS = [
        'pierwsz' => 1, 'drug' => 2, 'trzec' => 3, 'czwart' => 4, 'piat' => 5,
        'szost' => 6, 'siodm' => 7, 'osm' => 8, 'dziewiat' => 9, 'dziesiat' => 10,
    ];

    /** Tokens after which a standalone "i" is a numeral, not a conjunction. */
    private const array NUMERAL_I_NEXT = ['lo', 'technikum', 'zs'];
    private const array NUMERAL_I_PREVIOUS = ['nr', 'numer'];

    private const array STOPWORDS = ['im', 'imienia', 'nr', 'numer', 'w', 'we', 'szkola', 'i'];

    /** First matching suffix wins, so longer suffixes come first. */
    private const array SUFFIXES = ['iego', 'ego', 'iej', 'ych', 'ej', 'a', 'i', 'y', 'e'];

    public function normalize(string $raw): NormalizedName
    {
        $text = trim(mb_strtolower($raw));
        $text = strtr($text, self::TRANSLITERATION);
        $text = trim((string) preg_replace('/ {2,}/', ' ', (string) preg_replace('/[^a-z0-9 ]/', ' ', $text)));
        $text = (string) preg_replace(array_keys(self::CANONICAL_PHRASES), array_values(self::CANONICAL_PHRASES), $text);

        $tokens = '' === $text ? [] : explode(' ', $text);
        $tokens = $this->convertNumbers($tokens);
        $tokens = array_values(array_filter($tokens, static fn (string $t): bool => !\in_array($t, self::STOPWORDS, true)));

        return new NormalizedName(array_map($this->stem(...), $tokens));
    }

    private function convertNumbers(array $tokens): array
    {
        $result = [];
        foreach ($tokens as $i => $token) {
            if ('i' === $token) {
                $isNumeral = \in_array($tokens[$i + 1] ?? null, self::NUMERAL_I_NEXT, true)
                    || \in_array($tokens[$i - 1] ?? null, self::NUMERAL_I_PREVIOUS, true);
                $result[] = $isNumeral ? '1' : $token;
                continue;
            }

            if (preg_match(self::ROMAN_PATTERN, $token)) {
                $result[] = (string) $this->romanToInt($token);
                continue;
            }

            if (preg_match(self::ORDINAL_PATTERN, $token, $m)) {
                $result[] = (string) self::ORDINAL_STEMS[$m[1]];
                continue;
            }

            $result[] = $token;
        }

        return $result;
    }

    private function romanToInt(string $roman): int
    {
        $total = 0;
        $length = \strlen($roman);
        for ($i = 0; $i < $length; ++$i) {
            $value = self::ROMAN_VALUES[$roman[$i]];
            $next = $i + 1 < $length ? self::ROMAN_VALUES[$roman[$i + 1]] : 0;
            $total += $value < $next ? -$value : $value;
        }

        return $total;
    }

    private function stem(string $token): string
    {
        if (ctype_digit($token) || \strlen($token) < 5) {
            return $token;
        }

        foreach (self::SUFFIXES as $suffix) {
            if (str_ends_with($token, $suffix)) {
                return \strlen($token) - \strlen($suffix) >= 3 ? substr($token, 0, -\strlen($suffix)) : $token;
            }
        }

        return $token;
    }
}
