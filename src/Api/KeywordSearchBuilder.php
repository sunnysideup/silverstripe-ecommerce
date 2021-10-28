<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\Injector\Injectable;
use Sunnysideup\Ecommerce\Model\Search\SearchReplacement;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\CmsEditLinkField\Api\CMSEditLinkAPI;
use Sunnysideup\Ecommerce\Interfaces\EditableEcommerceObject;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

use Sunnysideup\Ecommerce\Pages\Product;

use Sunnysideup\Ecommerce\Api\ArrayMethods;

class KeywordSearchBuilder
{
    use Injectable;

    protected $keywordPhrase = '';

    public function getProductResults($phrase, string $where, ?int $limit = 9999) : array
    {
        $this->createIfStatements($phrase, 'Title', 'Data');
        $sql = $this->createSql('ProductSearchTable', 'ProductID', 'Data', $phrase, $where, $limit);
        $list = DB::query($sql)->keyedColumn();
        return $list;
    }

    public function getProductGroupResults($phrase, string $where, ?int $limit = 9999) : array
    {
        return [0 => 0];
        $this->createIfStatements($phrase, 'Title', 'Content');
        $sql = $this->createSql('SiteTree', 'ID', 'Title', $phrase, $where, $limit);
        return DB::query($sql)->keyedColumn();
    }


    /**
     * creates three levels of searches that
     * can be executed one after the other, each
     * being less specific than the one before...
     *
     * @param string $phrase - keywordphrase
     * @param array  $fields - fields being searched
     */
    protected function createIfStatements(string $phrase, $primaryField = 'Title', $secondaryField = 'Data')
    {
        $this->ifStatement = '';
        //make three levels of search
        $fullPhrase = preg_replace('#\s+#', ' ', $phrase);
        $wordAsArray = explode(' ', $fullPhrase);

        // create Field LIKE %AAAA% AND Field LIKE %BBBBB
        $searchStringAND = '';
        $hasWordArray = false;
        if (count($wordAsArray) > 1) {
            $hasWordArray = true;
            $searchStringArray = [];
            foreach ($wordAsArray as $word) {
                $searchStringArray[] = "\"_FF_FIELD_GOES_HERE_\" LIKE '%{$word}%'";
            }
            $searchStringAND = '(' . implode(' AND ', $searchStringArray) . ')';
        }

        $count = 0;
        // Title: exact match with Field
        $this->addIfStatement(++$count, '"'.$primaryField."\" = '{$fullPhrase}'");
        // Title: contains full string
        $this->addIfStatement(++$count, '"'.$primaryField."\" LIKE '%{$fullPhrase}%'");
        // Data: contains full string
        $this->addIfStatement(++$count, '"'.$secondaryField."\" LIKE '%{$fullPhrase}%'");
        if ($hasWordArray) {
            foreach ([$primaryField, $secondaryField] as $field) {
                $this->addIfStatement(
                    ++$count,
                    str_replace('_FF_FIELD_GOES_HERE_', $field, $searchStringAND),
                );
            }
        }
        $this->addEndIfStatement($count);
    }

    protected $ifStatement = '';

    protected function addIfStatement( int $count, string $where)
    {
        $this->ifStatement .= ' IF('.$where . ', '.$count.', ';
    }

    protected function addEndIfStatement($count)
    {
        $this->ifStatement .= ($count + 1) . str_repeat(')', $count) . ' AS gp';
    }

    protected function createSql(string $table, string $idField, string $matchField, string $phrase, string $where, $limit) : string
    {
        return '
            SELECT
                "'.$idField.'",
                '.$this->ifStatement.',
                MATCH ("'.$matchField.'") AGAINST (\''.Convert::raw2sql($phrase).'\' IN NATURAL LANGUAGE MODE) AS score
            FROM "'.$table.'"
            '.$where.'
            ORDER BY gp ASC, score DESC
            LIMIT '.$limit.';';
    }

    public function processKeyword(string $keywordPhrase)
    {
        $this->keywordPhrase = $keywordPhrase;
        $this->replaceSearchPhraseOrWord();
        //now we are going to look for synonyms
        $words = explode(' ', trim(preg_replace('#\s+#', ' ', $this->keywordPhrase)));
        foreach ($words as $word) {
            //todo: why are we looping through words?
            $this->replaceSearchPhraseOrWord($word);
        }

        return $this->keywordPhrase;
    }

    /**
     * @param string $word (optional word within keywordPhrase)
     */
    protected function replaceSearchPhraseOrWord(?string $word = '')
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
            )
        ;
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
}
