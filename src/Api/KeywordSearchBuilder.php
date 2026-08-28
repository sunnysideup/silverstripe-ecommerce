<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Model\Search\SearchReplacement;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductSearchFilter;

class KeywordSearchBuilder
{
    use Injectable;
    use Configurable;

    private static $price_adjustments = [
        50 => 1,
        120 => 2,
        300 => 3,
        700 => 4,
        1500 => 5,
    ];

    protected $keywordPhrase = '';

    protected $ifStatement = '';

    protected $debug = false;

    public function setDebug(bool $bool = true): static
    {
        $this->debug = $bool;

        return $this;
    }

    public function getProductResults($phrase, string $where, ?int $limit = 9999): array
    {
        $this->createIfStatements($phrase, 'Title', 'Data');
        $sql = $this->createSql('ProductSearchTable', 'ProductID', $phrase, $where, $limit);
        $result = $this->addPriceAdjustment(DB::query($sql));
        if ($this->debug) {
            print_r($sql);
            echo $this->arrayToHtmlTable($result);
        }
        return array_column($result, 'ProductID');
    }

    public function getProductGroupResults($phrase, string $where, ?int $limit = 99): array
    {
        $this->createIfStatements($phrase, 'Title', 'Data');
        $sql = $this->createSql('ProductGroupSearchTable', 'ProductGroupID', $phrase, $where, $limit);
        if ($this->debug) {
            $rows = DB::query($sql);
            echo $this->arrayToHtmlTable($rows);
        }
        return DB::query($sql)->keyedColumn();
    }

    public function processKeyword(string $keywordPhrase)
    {
        $this->keywordPhrase = $keywordPhrase;
        $this->replaceSearchPhraseOrWord();
        //now we are going to look for synonyms
        $words = explode(' ', trim(preg_replace('#\s+#', ' ', (string) $this->keywordPhrase)));
        foreach ($words as $word) {
            //todo: why are we looping through words?
            $this->replaceSearchPhraseOrWord($word);
        }

        return $this->keywordPhrase;
    }

    /**
     * creates three levels of searches that
     * can be executed one after the other, each
     * being less specific than the one before...
     *
     * @param string $phrase         - keywordphrase
     * @param mixed  $primaryField
     * @param mixed  $secondaryField
     */
    protected function createIfStatements(string $phrase, $primaryField = 'Title', $secondaryField = 'Data'): void
    {
        $phrase = $this->cleanPhrase($phrase);
        $this->resetIfStatement();
        //make three levels of search
        if (strlen($phrase) < 2) {
            return;
        }
        $this->startIfStatement();
        $wordAsArray = array_filter(explode(' ', $phrase));
        // create Field LIKE %AAAA% AND Field LIKE %BBBBB
        $searchStringAND = '';
        $hasWordArray = false;
        if (count($wordAsArray) > 1) {
            $searchStringArray = [];
            foreach ($wordAsArray as $word) {
                $word = trim($word);
                if (strlen($word) < 2) {
                    continue;
                }
                $searchStringArray[] = "\"_FF_FIELD_GOES_HERE_\" LIKE '%{$word}%'";
            }
            $searchStringAND = '(' . implode(' AND ', $searchStringArray) . ')';
            $hasWordArray = count($searchStringArray) > 0;
        }

        $count = 0;

        foreach ([$primaryField, $secondaryField] as $field) {
            $strPosition = $this->strPositionPhrase($phrase, $field);
            if ($field === $primaryField) {
                // exact match with Field, e.g. Title equals "AAAA BBBB"
                $this->addIfStatement(++$count, '"' . $field . "\" = '{$phrase}'");
            }

            // starts with full string and then space, e.g. Title equals "AAAA BBBB *" (note space!)
            $this->addIfStatement(++$count, '"' . $field . "\" LIKE '{$phrase} %'");

            // contains full string with spaces around it, e.g. Title equals "* AAAA BBBB *" (note space!)
            $this->addIfStatement(++$count, '"' . $field . "\" LIKE '% {$phrase} %'", $strPosition);

            // contains full string without space around it "*AAAA BBBB*"
            $this->addIfStatement(++$count, '"' . $field . "\" LIKE '%{$phrase}%'", $strPosition);
            if ($hasWordArray) {
                $this->addIfStatement(
                    ++$count,
                    str_replace('_FF_FIELD_GOES_HERE_', $field, $searchStringAND),
                    $strPosition
                );
            }
        }

        $this->addEndIfStatement($count);
    }

    protected function strPositionPhrase(string $phrase, string $field): string
    {

        $divisor = 5;
        return '  +
            (
                1 - (
                    MATCH ("' . $field . '") AGAINST (\'' . Convert::raw2sql($phrase) . '\' IN NATURAL LANGUAGE MODE) ) / ' . $divisor . '
            )';
    }

    protected function resetIfStatement(): void
    {
        $this->ifStatement = '';
    }

    protected function startIfStatement(): void
    {
        $this->ifStatement .= '(';
    }

    protected function addIfStatement(int $count, string $where, string $secondaryCount = ''): void
    {
        $this->ifStatement .= ' IF(' . $where . ', ( ' . $count . ' ' . $secondaryCount . ' ), ';
    }

    protected function addEndIfStatement($count): void
    {
        $this->ifStatement .= '999' . str_repeat(')', $count) . ') - "Boost" AS gp';
    }

    protected function createSql(string $table, string $idField, string $phrase, string $where, $limit): string
    {
        if ($where === '') {
            $where = '1=1';
        }
        $titleField = '';
        if ($this->debug) {
            $titleField = '"Title",';
        }
        $hasGp = $this->ifStatement !== '';
        $gpField = $hasGp ? $this->ifStatement : '0 AS gp';
        $fields = implode(', ', array_filter([$idField, $titleField, $gpField]));
        $orderBy = $hasGp ? 'gp ASC' : "{$idField} DESC";
        return "SELECT {$fields} FROM {$table} WHERE {$where} HAVING gp < 999 ORDER BY {$orderBy} LIMIT {$limit};";
    }

    /**
     * @param string $word (optional word within keywordPhrase)
     */
    protected function replaceSearchPhraseOrWord(?string $word = ''): void
    {
        if (! $word) {
            $word = $this->keywordPhrase;
        }
        $replacements = SearchReplacement::get()
            ->where(
                "
                LOWER(\"Search\") = '{$word}' OR
                LOWER(\"Search\") LIKE '%,{$word}' OR
                LOWER(\"Search\") LIKE '{$word},%' OR
                LOWER(\"Search\") LIKE '%,{$word},%'"
            );
        //if it is a word replacement then we do not want replace whole phrase ones ...
        if ($this->keywordPhrase !== $word) {
            $replacements = $replacements->exclude(['ReplaceWholePhrase' => 1]);
        }
        if ($replacements->exists()) {
            $replacementsArray = $replacements->column('Replace');
            foreach ($replacementsArray as $replacementWord) {
                $this->keywordPhrase = str_replace($word, $replacementWord, $this->keywordPhrase);
            }
        }
    }

    private function arrayToHtmlTable($rows): string
    {
        // use the keys of the first row as headers
        foreach ($rows as $firstRow) {
            $headers = array_keys((array) $firstRow);
            break;
        }
        if (! isset($headers)) {
            return '<p>No results</p>';
        }
        $html = '<table border="1" cellspacing="0" cellpadding="4">';
        $html .= '<thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars((string) $header) . '</th>';
        }
        $html .= '<th>Page</th></tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($headers as $header) {
                $value = $row[$header] ?? '';
                $html .= '<td>' . htmlspecialchars((string) $value) . '</td>';
            }
            $pageID = $row['ProductID'] ?? $row['ProductGroupID'] ?? 0;
            $pageTitle = 'Unknown';
            if ($pageID) {
                $pageTitle = DB::query("SELECT \"Title\" FROM \"SiteTree\" WHERE \"ID\" = {$pageID}")->value();
                if (! $pageTitle) {
                    $pageTitle = 'Unknown';
                }
            }

            $html .= '<td>' . htmlspecialchars((string) $pageTitle) . '</td>';
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }

    protected function addPriceAdjustment($results): array
    {
        $adjustments = $this->config()->get('price_adjustments');
        $prices = DB::query('SELECT "ID", "Price" FROM "Product"');
        foreach ($prices as $priceRow) {
            $priceMap[$priceRow['ID']] = (float) $priceRow['Price'];
        }
        $newRows = [];
        foreach ($results as $row) {
            $price = $priceMap[$row['ProductID']] ?? 0;
            $gp = (float) $row['gp'];
            $myBoost = 0.0;

            foreach ($adjustments as $limit => $boost) {
                $limit = (float) $limit;
                $boost = (float) $boost;
                if ($price > $limit) {
                    $myBoost = $boost;
                } else {
                    break;
                }
            }

            $row['gp'] = $gp - $myBoost;
            $newRows[] = $row;
        }

        // Sort by adjusted gp ascending
        usort($newRows, fn ($a, $b) => $a['gp'] <=> $b['gp']);
        return $newRows;
    }

    protected function cleanPhrase(string $phrase): string
    {
        //remove special characters ...
        // return ProductSearchFilter::keyword_sanitised($phrase);
        // should be already done in ProductSearchFilter...
        return $phrase;
    }
}
