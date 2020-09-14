<?php

namespace Sunnysideup\Ecommerce\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Validation\ProductSearchFormValidator;
use Sunnysideup\Ecommerce\Model\Config\EcommerceDBConfig;
use Sunnysideup\Ecommerce\Model\Search\SearchHistory;
use Sunnysideup\Ecommerce\Model\Search\SearchReplacement;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPage;

use Psr\SimpleCache\CacheInterface;


/**
 * @description: Allows user to specifically search products
 **/
class ProductSearchForm extends Form
{
    /**
     * set to TRUE to show the search logic.
     *
     * @var bool
     */
    protected $debug = true;

    /**
     * Fields are:
     * - Keyword
     * - MinimumPrice
     * - MaximumPrice
     *
     * @var array
     */
    protected $rawData = [];

    /**
     * processed keyword
     * @var int
     */
    protected $priceMax = 0;

    /**
     * raw keyword
     * @var string
     */
    protected $rawKeyword = '';

    /**
     * processed keyword
     * @var string
     */
    protected $keywordPhrase = '';

    /**
     * array of IDs of the results found so far.
     *
     * @var array
     */
    protected $productIds = [];

    /**
     * product groups found.
     *
     * @var array
     */
    protected $productGroupIds = [];

    /**
     * array of IDs of the results found so far.
     *
     * @var array
     */
    protected $resultArrayPerIternalItemID = [];

    /**
     * Number of results found so far.
     *
     * @var int
     */
    protected $resultArrayPos = 0;

    /**
     *
     * @var SiteTree
     */
    protected $immediateRedirectPage = null;

    /**
     *
     * @var DataList
     */
    protected $baseList = null;

    /**
     * @var array
     *            List of words to be replaced.
     */
    private const DEBUG_SQL = [
        "\r\n SELECT" => 'SELECT',
        "\r\n FROM" => 'FROM',
        "\r\n WHERE" => 'WHERE',
        "\r\n HAVING" => 'HAVING',
        "\r\n GROUP" => 'GROUP',
        "\r\n ORDER BY" => 'ORDER BY',
        "\r\n INNER JOIN" => 'INNER JOIN',
        "\r\n LEFT JOIN" => 'LEFT JOIN',
    ];

    /**
     * class name of the buyables to search
     * at this stage, you can only search one type of buyable at any one time
     * e.g. only products or only mydataobject.
     *
     * @var string
     */
    protected $baseClassNameForBuyables = Product::class;

    /**
     * class name of the buyables to search
     * at this stage, you can only search one type of buyable at any one time
     * e.g. only products or only mydataobject.
     *
     * @var string
     */
    protected $baseClassNameForGroups = ProductGroup::class;

    // /**
    //  * this is mysql specific, see: https://dev.mysql.com/doc/refman/5.0/en/fulltext-boolean.html.
    //  * not used at the moment!
    //  * @var bool
    //  */
    // protected $useBooleanSearch = true;

    /**
     * get parameters added to the link
     * you dont need to start them with & or ?
     * e.g.
     * a=23&b=234.
     *
     * @var string
     */
    protected $additionalGetParameters = '';

    /**
     * List of additional fields that should be searched full text.
     * We are matching this against the buyable class name.
     *
     * @var array
     */
    protected $extraBuyableFieldsToSearchFullText = [
        Product::class => ['Title', 'MenuTitle', 'Content', 'MetaDescription'],
        ProductGroup::class => ['Title', 'MenuTitle', 'Content', 'MetaDescription'],
        ProductVariation::class => ['FullTitle', 'Description'],
    ];

    /**
     * Maximum number of results to return
     * we limit this because otherwise the system will choke
     * the assumption is that no user is really interested in looking at
     * tons of results.
     * It defaults to: EcommerceConfig::get("ProductGroup", "maximum_number_of_products_to_list").
     *
     * @var int
     */
    protected $maximumNumberOfResults = 1000;

    /**
     * @var bool
     */
    private static $include_price_filters = false;


    public function getLastSearchPhrase()
    {
        return $this->rawData['Keyword'] ?? '';
    }

    public function getProductIds() : array
    {
        is_array($this->productIds) ? $this->productIds : [];
    }

    public function getProductGroupIds() : array
    {
        is_array($this->productGroupIds) ? $this->productGroupIds : [];
    }

    public function setExtraBuyableFieldsToSearchFullText(array $a)
    {
        $this->extraBuyableFieldsToSearchFullText = $a;
        return $this;
    }

    public function setBaseClassNameForBuyables(string $s)
    {
        $this->baseClassNameForBuyables = $s;

        return $this;
    }

    public function setMaximumNumberOfResults(int $i)
    {
        $this->maximumNumberOfResults = $i;

        return $this;
    }

    public function setAdditionalGetParameters(string $s)
    {
        $this->additionalGetParameters = $s;

        return $this;
    }

    /**
     * ProductsToSearch can be left blank to search all products.
     *
     * @param Controller              $controller                  - associated controller
     * @param string                  $name                        - name of form
     */
    public function __construct($controller, string $name, ?string $hash = '')
    {
        //turn of security to allow caching of the form:
        if (Config::inst()->get(ProductSearchForm::class, 'include_price_filters')) {
            $fields = FieldList::create(
                $keywordField = TextField::create('Keyword', _t('ProductSearchForm.KEYWORDS', 'Keywords')),
                NumericField::create('MinimumPrice', _t('ProductSearchForm.MINIMUM_PRICE', 'Minimum Price'))->setScale(2),
                NumericField::create('MaximumPrice', _t('ProductSearchForm.MAXIMUM_PRICE', 'Maximum Price'))->setScale(2)
            );
        } else {
            $fields = FieldList::create(
                $keywordField = TextField::create('Keyword', _t('ProductSearchForm.KEYWORDS', 'Keywords'))
            );
        }
        $actions = FieldList::create(
            FormAction::create('doProductSearchForm', 'Search')
        );

        if (Director::isDev() || Permission::check('ADMIN')) {
            $fields->push(CheckboxField::create('DebugSearch', 'Debug Search'));
        }
        $keywordField->setAttribute('placeholder', _t('ProductSearchForm.KEYWORD_PLACEHOLDER', 'search products ...'));
        $requiredFields = [];
        $validator = ProductSearchFormValidator::create($requiredFields);
        $this->extend('updateFields', $fields);
        $this->extend('updateActions', $actions);
        $this->extend('updateValidator', $validator);
        parent::__construct($controller, $name, $fields, $actions, $validator);
        //make it an easily accessible form  ...
        $this->setFormMethod('get');
        $this->disableSecurityToken();
        if($hash) {
            $oldData = $this->applyCacheFromHash($hash);
        }
        if (! empty($oldData['rawData'])) {
            $this->loadDataFrom($oldData['rawData']);
        }
        //extensions need to be set after __construct
        //extension point
        $this->extend('updateProductSearchForm', $this);

        return $this;
    }


    protected function setVars($data)
    {
        $this->rawData = $data;
        if (! $this->maximumNumberOfResults) {
            $this->maximumNumberOfResults = EcommerceConfig::get(ProductGroup::class, 'maximum_number_of_products_to_list_for_search');
        }
        //what is the baseclass?
        $this->baseClassNameForBuyables;
        if (! $this->baseClassNameForBuyables) {
            $this->baseClassNameForBuyables = EcommerceConfig::get(ProductGroup::class, 'base_buyable_class');
        }
        if (isset($data['DebugSearch'])) {
            $this->debug = $data['DebugSearch'] ? true : false;
        }
        if ($this->debug) {
            $this->debugOutput('<hr /><hr /><hr /><h2>Debugging Search Results</h2>');
            $this->debugOutput('<p>Base Class Name: '.$this->baseClassNameForBuyables.'</p>');
        }

    }

    public function doProductSearchForm($data, $form)
    {
        $this->doProcessSetup($data);

        //basic get


        $this->createBaseList();

        //defining some variables
        $isKeywordSearch = false;
        if ($this->debug) {
            $this->debugOutput('<hr /><h3>BASE LIST</h3><pre>' . str_replace(self::DEBUG_SQL, array_flip(self::DEBUG_SQL), $this->baseList->sql()) . '</pre>');
            $this->debugOutput('<hr /><h3>PRODUCTS IN BASELIST</h3><pre>' . $this->baseList->count() . '</pre>');
        }
        //KEYWORD SEARCH - only bother if we have any keywords and results at all ...
        if ($this->baseList->count()) {
            if (! empty($data['Keyword']) && strlen($this->keywordPhrase) > 1) {
                $this->keywordPhrase = $data['Keyword'];
                $isKeywordSearch = true;
                $this->doKeywordCleanup();
                $this->doAddToSearchHistory();
                $this->doInternalItemSearch();
                $this->doKeywordReplacements();
                $this->doProductSearch();
                $this->doGroupSearch();
            } else {
                $this->addToResults($this->baseList);
            }
        }

        if ($this->immediateRedirectPage) {
            $link = $this->immediateRedirectPage->Link();
            if($this->debug) {
                $this->debugOutput(
                    '<p style="color: red">Found one answer for potential immediate redirect: ' . $link. '</p>'
                );
            }
        } else {
            $redirectToPage = $this->getResultsPage();
            $hash = $this->setCacheForHash();
            $data['searchcode'] = $hash;
            $link = $redirectToPage->LinkForSearchResults($hash);
            unset($data['action_doProductSearchForm']);
            $link .= '?';
            $link .= http_build_query($data);
            $link .= $this->additionalGetParameters;
        }
        if ($this->debug) {
            die('<a href="' . $link . '">see results</a>');
        }
        $this->controller->redirect($link);
    }

    protected function doKeywordCleanup()
    {
        $isKeywordSearch = true;

        if ($this->debug) {
            $this->debugOutput('<hr /><h3>Raw Keyword ' . $this->keywordPhrase . '</h3><pre>');
        }
        $this->keywordPhrase = Convert::raw2sql($this->keywordPhrase);
        $this->keywordPhrase = strtolower($this->keywordPhrase);
    }

    protected function doAddToSearchHistory()
    {
        SearchHistory::add_entry($this->keywordPhrase);
    }

    protected function doInternalItemSearch()
    {

        ###############################################################
        // 1) Exact search by code
        ###############################################################
        $count = 0;
        if ($this->debug) {
            $this->debugOutput('<hr /><h2>SEARCH BY CODE</h2>');
        }
        $list1 = $this->baseList->filter(['InternalItemID' => $this->keywordPhrase]);
        $count = $list1->count();
        if ($count === 1) {
            // $this->immediateRedirectPage = $list1->First()->getRequestHandler()->Link();
            $this->immediateRedirectPage = $list1->First();
            if($this->debug) {
                $this->debugOutput('<p style="color: red">Found one answer for potential immediate redirect: ' . $this->immediateRedirectPage->Link() . '</p>');
            }
        }
        if ($count > 0) {
            if ($this->addToResults($list1)) {
                //break;
            }
        }
        if ($this->debug) {
            $this->debugOutput("<h3>SEARCH BY CODE RESULT: ${count}</h3>");
        }
    }



    protected function doKeywordReplacements()
    {
        if($this->immediateRedirectPage === null) {
            if ($this->resultArrayPos < $this->maximumNumberOfResults) {
                $this->replaceSearchPhraseOrWord();
                //now we are going to look for synonyms
                $words = explode(' ', trim(preg_replace('!\s+!', ' ', $this->keywordPhrase)));
                foreach ($words as $word) {
                    //todo: why are we looping through words?
                    $this->replaceSearchPhraseOrWord($word);
                }
                if ($this->debug) {
                    $this->debugOutput('<pre>WORD ARRAY: ' . print_r($this->keywordPhrase, 1) . '</pre>');
                }
            }
        }
    }

    protected function doProductSearch()
    {
        ###############################################################
        // 2) Search for the entire keyword phrase and its replacements
        ###############################################################
        if($this->immediateRedirectPage  === null) {
            $count = 0;
            if ($this->debug) {
                $this->debugOutput('<hr /><h3>FULL KEYWORD SEARCH</h3>');
            }
            if ($this->resultArrayPos < $this->maximumNumberOfResults) {
                $fieldArray = [];
                //work out searches
                $singleton = Injector::inst()->get($this->baseClassNameForBuyables);
                foreach ($this->extraBuyableFieldsToSearchFullText as $tempClassName => $fieldArrayTemp) {
                    if ($singleton instanceof $tempClassName) {
                        $fieldArray = $fieldArrayTemp;
                        break;
                    }
                }
                if ($this->debug) {
                    $this->debugOutput('<pre>FIELD ARRAY: ' . print_r($fieldArray, 1) . '</pre>');
                }

                $searches = $this->getSearchArrays($fieldArray);
                //if($this->debug) { $this->debugOutput("<pre>SEARCH ARRAY: ".print_r($searches, 1)."</pre>");}

                //we search exact matches first then other matches ...
                foreach ($searches as $search) {
                    $list2 = $this->baseList->where($search);
                    $count = $list2->count();
                    if ($this->debug) {
                        $this->debugOutput("<p>${search}: ${count}</p>");
                    }
                    if ($count > 0) {
                        if ($this->addToResults($list2)) {
                            break;
                        }
                    }
                    if ($this->resultArrayPos >= $this->maximumNumberOfResults) {
                        break;
                    }
                }
            }
            if ($this->debug) {
                $this->debugOutput("<h3>FULL KEYWORD SEARCH: ${count}</h3>");
            }
        }
    }


    protected function doGroupSearch()
    {
        ###############################################################
        // 3) Do the same search for Product Group names
        ###############################################################
        if($this->immediateRedirectPage === null) {
            if ($this->debug) {
                $this->debugOutput('<hr /><h3>PRODUCT GROUP SEARCH</h3>');
            }

            $count = 0;
            //work out searches
            $fieldArray = $this->extraBuyableFieldsToSearchFullText[$this->baseClassNameForGroups];
            if ($this->debug) {
                $this->debugOutput('<pre>FIELD ARRAY: ' . print_r($fieldArray, 1) . '</pre>');
            }

            $searches = $this->getSearchArrays($fieldArray);
            if ($this->debug) {
                $this->debugOutput('<pre>SEARCH ARRAY: ' . print_r($searches, 1) . '</pre>');
            }

            foreach ($searches as $search) {
                $productGroups = ProductGroup::get()->where($search)->filter(['ShowInSearch' => 1]);
                $count = $productGroups->count();
                //redirect if we find exactly one match and we have no matches so far...
                if ($count === 1 && ! $this->resultArrayPos) {
                    $this->immediateRedirectPage = $productGroups->First();
                }
                if ($count > 0) {
                    foreach ($productGroups as $productGroup) {
                        //we add them like this because we like to keep them in order!
                        if (! in_array($productGroup->ID, $this->productGroupIds, true)) {
                            $this->productGroupIds[] = $productGroup->ID;
                        }
                    }
                }
            }
            if ($this->debug) {
                $this->debugOutput("<h3>PRODUCT GROUP SEARCH: ${count}</h3>");
            }
        }
    }

    protected function doProcessResults()
    {

        $sessionNameProducts = $redirectToPage->SearchResultsSessionVariable(false);
        $sessionNameGroups = $redirectToPage->SearchResultsSessionVariable(true);

        if ($this->debug) {
            $this->debugOutput(
                '<hr />' .
                '<h3>Previous Search Products: ' . $sessionNameProducts . '</h3><p>' . print_r(Controller::curr()->getRequest()->getSession()->get($sessionNameProducts), 1) . '</p>' .
                '<h3>Previous Search Groups: ' . $sessionNameGroups . '</h3><p>' . print_r(Controller::curr()->getRequest()->getSession()->get($sessionNameGroups), 1) . '</p>'
            );
        }
        $productIdList = implode(',', $this->productIds);
        Controller::curr()->getRequest()->getSession()->set($sessionNameProducts, $productIdList);

        $productGroupIdList = implode(',', $this->productGroupIds);
        Controller::curr()->getRequest()->getSession()->set($sessionNameGroups, $productGroupIdList);

        Controller::curr()->getRequest()->getSession()->save(Controller::curr()->getRequest());
        if ($this->debug) {
            $this->debugOutput(
                '<hr />' .
                '<h3>SAVING Products to session: ' . $sessionNameProducts . '</h3><p>' . print_r(explode(',', Controller::curr()->getRequest()->getSession()->get($sessionNameProducts)), 1) . '</p>' .
                '<h3>SAVING Groups to session: ' . $sessionNameGroups . '</h3><p>' . print_r(explode(',', Controller::curr()->getRequest()->getSession()->get($sessionNameGroups)), 1) . '</p>' .
                '<h3>Internal Item IDs for Products</h3><p>' . print_r($this->resultArrayPerIternalItemID, 1) . '</p>'
            );
        }
    }


    /**
     * creates three levels of searches that
     * can be executed one after the other, each
     * being less specific than the last...
     *
     * returns true when done and false when more are needed
     *
     * @return bool
     */
    protected function addToResults(DataList $listToAdd) : bool
    {
        $internalItemID = 0;
        $listToAdd = $listToAdd->limit($this->maximumNumberOfResults - $this->resultArrayPos);
        $listToAdd = $listToAdd->sort('Price', 'DESC');
        foreach ($listToAdd as $page) {
            $id = $page->IDForSearchResults();
            if ($this->debug) {
                $internalItemID = $page->InternalItemIDForSearchResults();
            }
            if ($id) {
                if (! in_array($id, $this->productIds, true)) {
                    ++$this->resultArrayPos;
                    $this->productIds[$this->resultArrayPos] = $id;
                    if ($this->debug) {
                        $this->resultArrayPerIternalItemID[$this->resultArrayPos] = $internalItemID;
                    }
                    if ($this->resultArrayPos > $this->maximumNumberOfResults) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * creates three levels of searches that
     * can be executed one after the other, each
     * being less specific than the last...
     *
     * @param array $fields - fields being searched
     *
     * @return array
     */
    protected function getSearchArrays($fields = ['Title', 'MenuTitle']) : array
    {
        //make three levels of search
        $searches = [];
        $wordsAsString = preg_replace('!\s+!', ' ', $this->keywordPhrase);
        $wordAsArray = explode(' ', $wordsAsString);
        $hasWordArray = false;
        $searchStringAND = '';
        if (count($wordAsArray) > 1) {
            $hasWordArray = true;
            $searchStringArray = [];
            foreach ($wordAsArray as $word) {
                $searchStringArray[] = "LOWER(\"FFFFFF\") LIKE '%${word}%'";
            }
            $searchStringAND = '(' . implode(' AND ', $searchStringArray) . ')';
            // $searchStringOR = '('.implode(' OR ', $searchStringArray).')';
        }
        // $wordsAsLikeString = trim(implode('%', $wordAsArray));
        $completed = [];
        $count = -1;
        //@todo: make this smarter!
        if (in_array('Title', $fields, true)) {
            //$searches[++$count][] = "LOWER(\"Title\") = '${$wordsAsLikeString}'"; // a) Exact match
            //$searches[++$count][] = "LOWER(\"Title\") LIKE '%${$wordsAsLikeString}%'"; // b) Full match within a bigger string
            if ($hasWordArray) {
                $searches[++$count][] = str_replace('FFFFFF', 'Title', $searchStringAND); // d) Words matched individually
                // $searches[++$count + 100][] = str_replace('FFFFFF', 'Title', $searchStringOR); // d) Words matched individually
            }
            $completed['Title'] = 'Title';
        }
        if (in_array('MenuTitle', $fields, true)) {
            $searches[++$count][] = "LOWER(\"MenuTitle\") = '${wordsAsString}'"; // a) Exact match
            $searches[++$count][] = "LOWER(\"MenuTitle\") LIKE '%${wordsAsString}%'"; // b) Full match within a bigger string
            if ($hasWordArray) {
                $searches[++$count][] = str_replace('FFFFFF', 'MenuTitle', $searchStringAND); // d) Words matched individually
                // $searches[++$count + 100][] = str_replace('FFFFFF', 'MenuTitle', $searchStringOR); // d) Words matched individually
            }
            $completed['MenuTitle'] = 'MenuTitle';
        }
        if (in_array('MetaTitle', $fields, true)) {
            $searches[++$count][] = "LOWER(\"MetaTitle\") = '${wordsAsString}'"; // a) Exact match
            $searches[++$count][] = "LOWER(\"MetaTitle\") LIKE '%${wordsAsString}%'"; // b) Full match within a bigger string
            if ($hasWordArray) {
                $searches[++$count][] = str_replace('FFFFFF', 'MetaTitle', $searchStringAND); // d) Words matched individually
                // $searches[++$count + 100][] = str_replace('FFFFFF', 'MetaTitle', $searchStringOR); // d) Words matched individually
            }
            $completed['MetaTitle'] = 'MetaTitle';
        }
        foreach ($fields as $field) {
            if (! isset($completed[$field])) {
                $searches[++$count][] = "LOWER(\"${field}\") = '${wordsAsString}'"; // a) Exact match
                $searches[++$count][] = "LOWER(\"${field}\") LIKE '%${wordsAsString}%'"; // b) Full match within a bigger string
                if ($hasWordArray) {
                    $searches[++$count][] = str_replace('FFFFFF', $field, $searchStringAND); // d) Words matched individually
                    // $searches[++$count + 100][] = str_replace('FFFFFF', $field, $searchStringOR); // d) Words matched individually
                }
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

    /**
     * @param  string $word (optional word within keywordPhrase)
     *
     * @return string (updated Keyword Phrase)
     */
    protected function replaceSearchPhraseOrWord(?string $word = '')
    {
        if (! $word) {
            $word = $this->keywordPhrase;
        }
        $replacements = SearchReplacement::get()
            ->where(
                "
                LOWER(\"Search\") = '${word}' OR
                LOWER(\"Search\") LIKE '%,${word}' OR
                LOWER(\"Search\") LIKE '${word},%' OR
                LOWER(\"Search\") LIKE '%,${word},%'"
            );
        //if it is a word replacement then we do not want replace whole phrase ones ...
        if ($this->keywordPhrase !== $word) {
            $replacements = $replacements->exclude(['ReplaceWholePhrase' => 1]);
        }
        if ($replacements->count()) {
            $replacementsArray = $replacements->map('ID', 'Replace')->toArray();
            if ($this->debug) {
                $this->debugOutput("found alias for ${word}");
            }
            foreach ($replacementsArray as $replacementWord) {
                $this->keywordPhrase = str_replace($word, $replacementWord, $this->keywordPhrase);
            }
        }
    }

    protected function getResultsPage()
    {
        //if no specific section is being searched then we redirect to search page:
        return DataObject::get_one(ProductGroupSearchPage::class);
    }


    protected function createBaseList()
    {
        $tmpVar = $this->baseClassNameForBuyables;
        $this->baseList = $tmpVar::get()->filter(['ShowInSearch' => 1]);
        $ecomConfig = EcommerceDBConfig::current_ecommerce_db_config();
        if ($ecomConfig->OnlyShowProductsThatCanBePurchased) {
            $this->baseList->filter(['AllowPurchase' => 1]);
        }

        if (isset($data['MinimumPrice']) && $data['MinimumPrice']) {
            $this->baseList = $this->baseList->filter(['Price:GreaterThanOrEqual' => floatval($data['MinimumPrice'])]);
        }
        if (isset($data['MaximumPrice']) && $data['MaximumPrice']) {
            $this->baseList = $this->baseList->filter(['Price:LessThanOrEqual' => floatval($data['MaximumPrice'])]);
        }
    }

    protected function getSerializedObject(?array $data = [])
    {
        $variables = get_object_vars($this);
        foreach($variables as $key => $values) {
            if(is_object($value)) {
                if(empty($object->ClassName) || empty($object->ID)) {
                    unset($variables[$key]);
                } else {
                    $variables[$key] = $object->ClassName . '_' . $object->ID;
                }
            }
        }
        return serialize($variables);
    }

    /**
     *
     * @param  string $data optional
     * @return float
     */
    protected function getHash(?string $data = '')
    {
        if(! $data) {
            $data = $this->getSerializedObject();
        }
        return crc32($data);
    }


    protected function setCacheForHash() : float
    {
        $cache = $this->getCache();
        $data = $this->getSerializedObject();
        $hash = $this->getHash($data);
        $cache->set($hash, $data);

        return $hash;
    }

    protected function getCacheForHash(string $hash) : array
    {
        $array = [];
        $cache = $this->getCache();
        if ($cache->has($hash)) {
            $array = $cache->get($hash);
            if(! is_array($array)) {
                $array = [];
            }
        }

        return $array;
    }

    protected function applyCacheFromHash(string $hash) : array
    {
        $string = $this->getCache($hash);
        $array = unserialize($hash);
        foreach($array as $variable => $value) {
            $this->$variable = $value;
        }
        return $array;
    }

    protected function getCache() : CacheInterface
    {
        return Injector::inst()->get(CacheInterface::class . '.EcomSearchCache');
    }

    protected function debugOutput(string $string)
    {
        echo "<br />${string}";
    }



}
