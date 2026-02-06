<?php

namespace Sunnysideup\Ecommerce\Api;

/**
 * usage
 * ```php
 *     $data = Sanitizer::remove_from_data_array($data);
 * ```.
 */
class Sanitizer
{
    /**
     * removes sensitive data from data
     */
    public static function remove_from_data_array(array $data): array
    {
        unset(
            $data['AccountInfo'],
            $data['LoginDetails'],
            $data['LoggedInAsNote'],
            $data['PasswordCheck1'],
            $data['PasswordCheck2'],
            $data['Password'],
        );

        return $data;
    }

    public static function html_array_to_text_limit_words(array $array, ?int $maxChars = 2024, ?string $glue = ' '): string
    {
        $glue = $glue ?? ' ';

        $fromStringToWordArray = static function (string $s): array {
            $s = trim($s);
            return $s === '' ? [] : (preg_split('/\s+/u', $s) ?: []);
        };
        $fromWordArrayToString = static fn (array $w): string => implode(' ', $w);

        // HTML -> words per part, enforce uniqueness per array item
        $parts = [];
        foreach ($array as $key => $value) {
            if (! empty($value)) {
                $words = [];
                $array[$key] = trim(self::html_to_text((string) $value));
                $value = $array[$key];
                $seen = [];
                if ($value !== '' && $value !== '0') {
                    foreach ($fromStringToWordArray($value) as $w) {
                        if (! isset($seen[$w])) {
                            $seen[$w] = true;
                            $words[] = $w;
                        }
                    }
                }
                $parts[] = $words;
            }
        }

        $partLen = static fn (array $w): int => mb_strlen($fromWordArrayToString($w));
        $totalLen = static function (array $parts) use ($fromWordArrayToString): int {
            $chunks = array_values(array_filter(array_map($fromWordArrayToString, $parts), fn ($s) => $s !== ''));
            if ($chunks === []) {
                return 0;
            }
            $sum = 0;
            foreach ($chunks as $c) {
                $sum += mb_strlen($c);
            }
            return $sum + (count($chunks) - 1);
        };

        while ($totalLen($parts, $glue) > ($maxChars ?? 2024)) {
            $idx = null;
            $max = -1;
            foreach ($parts as $i => $w) {
                $len = $partLen($w);
                if ($len > $max) {
                    $max = $len;
                    $idx = $i;
                }
            }
            if ($idx === null || $max <= 0) {
                break;
            }
            array_pop($parts[$idx]); // remove one word from the longest part
        }

        $chunks = array_values(
            array_filter(
                array_map(
                    $fromWordArrayToString,
                    $parts
                ),
                fn ($s) => $s !== ''
            )
        );

        return implode($glue, $chunks);
    }

    public static function html_array_to_text(array $array): string
    {
        foreach ($array as $key => $value) {
            $array[$key] = trim(self::html_to_text($value));
        }
        $array = array_filter($array);
        return implode(' ', $array);
    }

    public static function html_to_text($html): string
    {
        return (string) strtolower(
            trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    strip_tags(
                        str_replace(
                            '<',
                            ' <',
                            (string) $html
                        )
                    )
                )
            )
        );
    }

    public static function unique_words(?string $text, int $maxWords = 9999): string
    {
        if (! $text) {
            return '';
        }
        $text = strip_tags($text);
        $words = preg_split('/\s+/', $text);
        $words = array_unique($words);
        $words = array_slice($words, 0, $maxWords);

        return implode(' ', $words);
    }
}
