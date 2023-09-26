<?php

namespace Sunnysideup\Ecommerce\Api\Converters;

class CsvFunctionality
{
    public static function removeBadCharacters($item)
    {
        $item = str_replace(';', ',', $item);
        $item = str_replace("\r", ' ', $item);
        $item = str_replace("\n", ' ', $item);
        $item = str_replace("\t", ' ', $item);

        return trim((string) $item);
    }

    public static function convertToCSV($rows, $delimiter = ';', $enclosure = '"', $encloseAll = false)
    {
        $delimiter_esc = preg_quote($delimiter, '/');
        $enclosure_esc = preg_quote($enclosure, '/');
        $string = '';
        foreach ($rows as $row) {
            $output = [];
            foreach ($row as $field) {
                if (!$field) {
                    $output[] = $enclosure . $field . $enclosure;
                } else {
                    // Enclose fields containing $delimiter, $enclosure or whitespace
                    if ($encloseAll || preg_match("/(?:{$delimiter_esc}|{$enclosure_esc}|\\s)/", $field)) {
                        $output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
                    } else {
                        $output[] = $field;
                    }
                }
            }
            $string .= implode($delimiter, $output);
            unset($output);

            if ($string) {
                $string .= "\r\n";
            }
        }

        return $string;
    }
}
