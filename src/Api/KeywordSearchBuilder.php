<?php

namespace Sunnysideup\Ecommerce\Api;

use SilverStripe\Core\Injector\Injectable;
use Sunnysideup\Ecommerce\Model\Search\SearchReplacement;

class KeywordSearchBuilder
{
    use Injectable;

    protected $keywordPhrase = '';

    /**
     * creates three levels of searches that
     * can be executed one after the other, each
     * being less specific than the last...
     *
     * @param string $phrase - keywordphrase
     * @param array  $fields - fields being searched
     */
    public function getSearchArrays(string $phrase, $fields = ['Title', 'MenuTitle']): array
    {
        //make three levels of search
        $searches = [];
        $wordsAsString = preg_replace('#\s+#', ' ', $phrase);
        $wordAsArray = explode(' ', $wordsAsString);
        $hasWordArray = false;
        $searchStringAND = '';
        if (count($wordAsArray) > 1) {
            $hasWordArray = true;
            $searchStringArray = [];
            foreach ($wordAsArray as $word) {
                $searchStringArray[] = "LOWER(\"FFFFFF\") LIKE '%{$word}%'";
            }
            $searchStringAND = '(' . implode(' AND ', $searchStringArray) . ')';
            // $searchStringOR = '('.implode(' OR ', $searchStringArray).')';
        }
        // $wordsAsLikeString = trim(implode('%', $wordAsArray));
        $completed = [];
        $count = -1;
        foreach ($fields as $field) {
            $searches[++$count][] = "LOWER(\"{$field}\") = '{$wordsAsString}'"; // a) Exact match
        }
        foreach ($fields as $field) {
            $searches[++$count][] = "LOWER(\"{$field}\") LIKE '%{$wordsAsString}%'"; // a) Exact match
        }
        foreach ($fields as $field) {
            if ($hasWordArray) {
                $searches[++$count][] = str_replace('FFFFFF', $field, $searchStringAND); // d) Words matched individually
                // $searches[++$count + 100][] = str_replace('FFFFFF', $field, $searchStringOR); // d) Words matched individually
            }
            /*
             * OR WORD SEARCH
             * OFTEN leads to too many results, so we keep it simple...
            foreach($wordArray as $word) {
                $searches[6][] = "LOWER(\"$field\") LIKE '%$word%'"; // d) One word match within a bigger string
            }
            */
        }
        //$searches[3][] = DB::getconn()->fullTextSearchSQL($fields, $wordsAsString, true);
        ksort($searches);
        $returnArray = [];
        foreach ($searches as $key => $search) {
            $returnArray[$key] = implode(' OR ', $search);
        }

        return $returnArray;
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
        if ($replacements->count()) {
            $replacementsArray = $replacements->map('ID', 'Replace')->toArray();
            foreach ($replacementsArray as $replacementWord) {
                $this->keywordPhrase = str_replace($word, $replacementWord, $this->keywordPhrase);
            }
        }
    }
}
