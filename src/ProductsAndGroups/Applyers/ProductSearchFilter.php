<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\EcommerceCache;
use Sunnysideup\Ecommerce\Api\KeywordSearchBuilder;
use Sunnysideup\Ecommerce\Api\Sanitizer;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Validation\ProductSearchFormValidator;
use Sunnysideup\Ecommerce\Model\Search\SearchHistory;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPage;
use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPageController;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\RelatedProductGroups;
use Sunnysideup\Ecommerce\Traits\PartialObjectCache;
use Sunnysideup\Vardump\Vardump;

/**
 * provides data on the user.
 */
class ProductSearchFilter extends BaseApplyer
{
    use PartialObjectCache;

    /**
     * @var string[]
     */
    private const FIELDS_TO_CACHE = [
        'rawData',
        'productIds',
        'productGroupIds',
        'baseListOwner',
    ];

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
     * - OnlyThisSection.
     *
     * @var array
     */
    protected $rawData = [];

    /**
     * processed keyword.
     *
     * @var string
     */
    protected $keywordPhrase = '';

    /**
     * @var array
     */
    protected $extraBuyableFieldsToSearchFullText = [];

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
     * Number of results found so far.
     *
     * @var int
     */
    protected $resultArrayPos = 0;

    /**
     * @var SiteTree
     */
    protected $immediateRedirectPage;

    /**
     * a product group that creates the base list.
     *
     * @var ProductGroup
     */
    protected $baseListOwner;

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
     * Maximum number of results to return
     * we limit this because otherwise the system will choke
     * the assumption is that no user is really interested in looking at
     * tons of results.
     * It defaults to: EcommerceConfig::get("ProductGroup", "maximum_number_of_products_to_list").
     *
     * @var int
     */
    protected $maximumNumberOfResults = 1000;


    protected $productsForGroups = null;

    /**
     * make sure that these do not exist as a URLSegment.
     *
     * @var array
     */
    private static $options = [
        BaseApplyer::DEFAULT_NAME => [
            'Title' => 'Search Results',
            'SQL' => [
                'ShowInSearch' => 1,
            ],
            'UsesParamData' => true,
            'IsShowFullList' => false,
        ],
    ];

    /**
     * do we use the cache at all.
     *
     * @var bool
     */
    private static $use_cache = true;

    /**
     * Default selection criteria for products
     *
     * @var array
     */
    private static $default_product_filter = ['ShowInSearch' => true];

    /**
     * when we do not know the relevance then sort like this.
     *
     * @var array
     */
    private static $in_group_sort_sql = ['Price' => 'DESC'];

    /**
     * List of additional fields that should be searched full text.
     * We are matching this against the buyable class name.
     *
     * Order matters!
     *
     * @var array
     */
    private static $fields_to_search_full_text_default_per_class = [
        // Product::class => ['Title', 'MenuTitle', 'Content', 'MetaDescription'],
        // ProductGroup::class => ['Title', 'MenuTitle', 'Content', 'MetaDescription'],
    ];

    /**
     * List of additional fields that should be searched full text.
     * We are matching this against the buyable class name.
     *
     * Order matters!
     *
     * @var array
     */
    private static $fields_to_search_full_text_default = ['Title', 'MenuTitle', 'Content', 'MetaDescription'];

    public static function keyword_sanitised(?string $string = '') : string
    {
        $string = Convert::raw2sql($string);
        $string = strtolower($string);
        $string = substr($string, 0 , SearchHistory::KEYWORD_LENGTH_LIMIT);

        return $string;
    }


    //#######################################
    // key methods
    //#######################################


    /**
     * @param string       $key    optional key
     * @param array|string $params optional params to go with key
     */
    public function apply(?string $key = null, $params = null): self
    {
        $this->applyStart($key, $params);
        if(is_array($params) && count($params)) {
            $this->rawData = $params;
            $this->runFullProcess();
        }
        if($this->debug) {
            die('debug');
        }
        $this->applyEnd($key, $params);

        return $this;
    }

    public function getTitle(?string $key = '', $params = null): string
    {
        return 'to be completed';
    }

    //#######################################
    // getters
    //#######################################

    /**
     * they search phrase used.
     */
    public function getLastSearchPhrase(): string
    {
        return $this->rawData['Keyword'] ?? '';
    }

    public function getProductIds(): array
    {
        return ArrayMethods::filter_array($this->productIds);
    }

    public function getProductGroupIds(): array
    {
        return ArrayMethods::filter_array($this->productGroupIds);
    }

    public function getHasResults(): bool
    {
        return count($this->getProductIds) && count($this->getProductGroupIds) > 2;
    }

    //#######################################
    // setters
    //#######################################

    /**
     * @param DataList $baseListForGroups
     */
    public function setBaseListForGroups($baseListForGroups): self
    {
        $this->productsForGroups = $baseListForGroups;

        return $this;
    }

    public function setSearchKeyword(string $keyword): self
    {
        $this->rawData['Keyword'] = urldecode($keyword);

        return $this;
    }

    public function setExtraBuyableFieldsToSearchFullText(array $a): self
    {
        $this->extraBuyableFieldsToSearchFullText = $a;

        return $this;
    }

    public function setBaseClassNameForBuyables(string $s): self
    {
        $this->baseClassNameForBuyables = $s;

        return $this;
    }

    public function setBaseClassNameForGroups(string $s): self
    {
        $this->baseClassNameForGroups = $s;

        return $this;
    }

    public function setMaximumNumberOfResults(int $i): self
    {
        $this->maximumNumberOfResults = $i;

        return $this;
    }

    //#######################################
    // Runners
    //#######################################

    /**
     * run process from ProductGroup
     */
    protected function runFullProcess()
    {

        $this->doProcessSetup();

        //basic get

        $this->createBaseList();
        $this->doPriceSearch();
        $this->doAdvancedSearch();

        //defining some variables

        //KEYWORD SEARCH - only bother if we have any keywords and results at all ...
        if ($this->products->count()) {
            if (! empty($this->rawData['Keyword']) && strlen($this->rawData['Keyword']) > 1) {
                $this->keywordPhrase = $this->rawData['Keyword'];
                $this->doKeywordCleanup();
                $this->doInternalItemSearch();
                $this->doKeywordReplacements();
                $this->doProductSearch();
                $this->doGroupSearch();
            } else {
                $this->addToResults($this->products, true);
            }
        }

    }

    /**
     * set up basics, using data.
     */
    protected function doProcessSetup()
    {
        if (! $this->maximumNumberOfResults) {
            $this->maximumNumberOfResults = EcommerceConfig::get(ProductGroupSearchPage::class, 'maximum_number_of_products_to_list_for_search');
        }

        if (! $this->baseClassNameForBuyables) {
            $this->baseClassNameForBuyables = EcommerceConfig::get(ProductGroup::class, 'base_buyable_class');
        }

        if ($this->debug) {
            $this->debugOutput('<h2>Debugging Search Results in '.get_class($this).'</h2>');
            $this->debugOutput('<p>Base Class Name: ' . $this->baseClassNameForBuyables . '</p>');
            $this->debugOutput('<p style="color: red">data: ' . print_r($this->rawData, 1) . '</p>');
        }
        $this->rawData['MinimumPrice'] = $this->rawData['MinimumPrice'] ?? 0;
        $this->rawData['MaximumPrice'] = $this->rawData['MaximumPrice'] ?? 0;
        $this->rawData['MinimumPrice'] = floatval(str_replace(',', '', $this->rawData['MinimumPrice']));
        $this->rawData['MaximumPrice'] = floatval(str_replace(',', '', $this->rawData['MaximumPrice']));

        $this->rawData['OnlyThisSection'] = (bool) (int) ($this->rawData['OnlyThisSection'] ?? 0);
        if ($this->rawData['MinimumPrice'] > $this->rawData['MaximumPrice']) {
            $oldMin = $this->rawData['MinimumPrice'];
            $this->rawData['MinimumPrice'] = $this->rawData['MaximumPrice'];
            $this->rawData['MaximumPrice'] = $oldMin;
        }
    }

    /**
     * cleanup keyword phrase.
     */
    protected function doKeywordCleanup()
    {
        if ($this->debug) {
            $this->debugOutput('<h3>RAW KEYWORD</h3><p>' . $this->keywordPhrase . '</p>');
        }
        $this->keywordPhrase = self::keyword_sanitised($this->keywordPhrase);
    }


    /**
     * look for internalItemID.
     */
    protected function doInternalItemSearch()
    {
        if (true === $this->hasCache) {
            return;
        }
        if ($this->debug) {
            $this->debugOutput('<hr />');
            $this->debugOutput('<h2>SEARCH BY CODE</h2>');
        }
        $list1 = $this->products->filter(['InternalItemID' => $this->keywordPhrase]);
        $this->addToResults($list1, $allowOne = true);
        if ($this->debug) {
            $this->debugOutput('<h3>SEARCH BY CODE RESULT: ' . $list1->count() . '</h3>');
        }
    }

    /**
     * replace keywords with better ones
     * we also need them for groups!
     */
    protected function doKeywordReplacements()
    {
        if (true === $this->hasCache) {
            return;
        }
        if (! $this->weHaveEnoughResults()) {
            $this->keywordPhrase = $this->getSearchApi()->processKeyword($this->keywordPhrase);
            if ($this->debug) {
                $this->debugOutput('<pre>WORD ARRAY: ' . print_r($this->keywordPhrase, 1) . '</pre>');
            }
        }
    }

    /**
     * search for products.
     */
    protected function doProductSearch()
    {
        // @todo: consider using
        // DB::get_conn()->searchEngine(SiteTre::get(), $keywords, $start, $pageLength, "\"Relevance\" DESC", "", $booleanSearch);
        if (true === $this->hasCache) {
            return;
        }
        if (! $this->weHaveEnoughResults()) {
            $count = 0;
            if ($this->debug) {
                $this->debugOutput('<hr />');
                $this->debugOutput('<h3>FULL KEYWORD SEARCH</h3>');
            }

            // work out fields to search
            $fieldArray = $this->workOutFieldsToSearch($this->baseClassNameForBuyables);
            if ($this->debug) {
                $this->debugOutput('<pre>FIELD ARRAY: ' . print_r($fieldArray, 1) . '</pre>');
            }

            //work out searches
            $searches = $this->getSearchApi()->getSearchArrays($this->keywordPhrase, $fieldArray);
            //if($this->debug) { $this->debugOutput("<pre>SEARCH ARRAY: ".print_r($searches, 1)."</pre>");}

            //we search exact matches first then other matches ...
            foreach ($searches as $search) {
                $list2 = $this->products->where($search);
                $count += $list2->count();
                if ($this->debug) {
                    $this->debugOutput("<p>{$search} from (".$this->products->count()."): " . $list2->count() . '</p>');
                }
                $this->addToResults($list2);
                if ($this->weHaveEnoughResults()) {
                    break;
                }
            }
            if ($this->debug) {
                $this->debugOutput("<h3>FULL KEYWORD SEARCH: {$count}</h3>");
            }
        }
    }

    protected function workOutFieldsToSearch(string $classNameToSearch) : array
    {
        $fieldArrayAll = $this->Config()->get('fields_to_search_full_text_default_per_class');
        $fieldsArray = $fieldArrayAll[$classNameToSearch] ?? $this->Config()->get('fields_to_search_full_text_default');
        $extraFields = $this->extraBuyableFieldsToSearchFullText[$classNameToSearch] ?? [];
        return array_merge($fieldsArray, $extraFields);
    }

    /**
     * search for groups.
     */
    protected function doGroupSearch()
    {
        if (true === $this->hasCache) {
            return;
        }
        if (null === $this->immediateRedirectPage) {
            if ($this->debug) {
                $this->debugOutput('<hr />');
                $this->debugOutput('<h3>PRODUCT GROUP SEARCH</h3>');
            }

            $count = 0;
            // work out fields to search
            $fieldArray = $this->workOutFieldsToSearch($this->baseClassNameForGroups);

            // work out searches
            $searches = $this->getSearchApi()->getSearchArrays($this->keywordPhrase, $fieldArray);
            if ($this->debug) {
                $this->debugOutput('<pre>SEARCH ARRAY: ' . print_r($searches, 1) . '</pre>');
            }

            foreach ($searches as $search) {
                $this->productsForGroups = $this->productsForGroups->where($search);
                $count = $this->productsForGroups->count();
                //redirect if we find exactly one match and we have no matches so far...
                if (1 === $count && ! $this->resultArrayPos) {
                    $this->immediateRedirectPage = $productGroups->First();
                } elseif ($count > 0) {
                    foreach ($this->productsForGroups as $productGroup) {
                        //we add them like this because we like to keep them in order!
                        if (! in_array($productGroup->ID, $this->productGroupIds, true)) {
                            $this->productGroupIds[] = $productGroup->ID;
                        }
                    }
                }
            }
            if ($this->debug) {
                $this->debugOutput("<h3>PRODUCT GROUP SEARCH: {$count}</h3>");
            }
        }
    }

    //###########################################
    // results management: add / count
    //###########################################

    /**
     * add items to list.
     *
     * returns
     * - TRUE when done and
     * - FALSE when more results are needed
     *
     */
    protected function addToResults(DataList $listToAdd, ?bool $allowOneAnswer = false): bool
    {
        if ($this->weHaveEnoughResults()) {
            return true;
        }
        $count = $listToAdd->count();
        if ($allowOneAnswer && 1 === $count && 0 === $this->resultArrayPos) {
            // $this->immediateRedirectPage = $list1->First()->getRequestHandler()->Link();
            $this->immediateRedirectPage = $listToAdd->First();
            if ($this->debug) {
                $this->debugOutput(
                    '<p style="color: red">Found one answer for potential immediate redirect: ' . $this->immediateRedirectPage->Link() . '</p>'
                );
            }

            return true;
        }
        if ($count > 0) {
            $listToAdd = $listToAdd->limit($this->maximumNumberOfResults - $this->resultArrayPos);
            $sort = $this->Config()->get('in_group_sort_sql');
            $listToAdd = $listToAdd->sort($sort);
            foreach ($listToAdd as $page) {
                $id = $page->IDForSearchResults();
                // if ($this->debug) {
                //     $internalItemID = $page->InternalItemIDForSearchResults();
                // }
                if ($id) {
                    if (! in_array($id, $this->productIds, true)) {
                        ++$this->resultArrayPos;
                        $this->productIds[$this->resultArrayPos] = $id;
                        if ($this->weHaveEnoughResults()) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * do we need more results?
     *
     * @return bool returns true if more results are needed
     */
    protected function weHaveEnoughResults(): bool
    {
        if ($this->immediateRedirectPage) {
            return true;
        }

        return $this->resultArrayPos >= $this->maximumNumberOfResults;
    }

    //###########################################
    // create lists
    //###########################################

    protected function createBaseList()
    {
        if (true === $this->hasCache) {
            $tmpVar = $this->baseClassNameForBuyables;
            $filter = ['ID' => ArrayMethods::filter_array($this->productIds)];
            $this->products = $tmpVar::get()->filter($filter);
        } else {
            if (! $this->products instanceof DataList) {
                if ($this->rawData['OnlyThisSection']) {
                    // do nothing!
                } else {
                    $tmpVar = $this->baseClassNameForBuyables;
                    $defaultProductFilter = $this->Config()->get('default_product_filter');
                    $this->products = $tmpVar::get()->filter($defaultProductFilter);
                    if (EcommerceConfig::inst()->OnlyShowProductsThatCanBePurchased) {
                        $this->products = $this->products->filter(['AllowPurchase' => 1]);
                    }

                }
            }
            if (! $this->productsForGroups instanceof DataList) {
                if ($this->rawData['OnlyThisSection']) {
                    $this->productsForGroups = $this->finalProductList->getParentGroups();
                } else {
                    $tmpVar = $this->baseClassNameForGroups;
                    $defaultGroupFilter = Config::inst()->get(RelatedProductGroups::class, 'default_product_group_filter');
                    $this->productsForGroups = $tmpVar::get()->filter($defaultGroupFilter);
                }
            }
            if ($this->debug) {
                $this->debugOutput('<hr />');
                $this->debugOutput('<h3>BASE LIST</h3><pre>' . Vardump::inst()->mixedToUl($this->products) . '</pre>');
                $this->debugOutput('<h3>BASE GROUP LIST</h3><pre>' . Vardump::inst()->mixedToUl($this->productsForGroups) . '</pre>');
            }
        }
    }


    /**
     * filter baselist for price min and max.
     */
    protected function doPriceSearch()
    {
        if (true === $this->hasCache) {
            return;
        }
        if ($this->hasMinMaxSearch()) {
            $min = $this->rawData['MinimumPrice'];
            if ($min) {
                $this->products = $this->products->filter(['Price:GreaterThanOrEqual' => $min]);
                if ($this->debug) {
                    $this->debugOutput('<h3>MIN PRICE</h3><pre>' . $min . '</pre>');
                }
            }
            $max = $this->rawData['MaximumPrice'];
            if ($max) {
                $this->products = $this->products->filter(['Price:LessThanOrEqual' => $max]);
                if ($this->debug) {
                    $this->debugOutput('<h3>MAX PRICE</h3><pre>' . $max . '</pre>');
                }
            }
            if ($this->debug) {
                $this->debugOutput('<hr />');
                $this->debugOutput('<h3>BASE LIST AFTER PRICE SEARCH</h3><pre>' . Vardump::inst()->mixedToUl($this->products->sql()) . '</pre>');
                $this->debugOutput('<h3>PRODUCTS AFTER PRICE SEARCH</h3><pre>' . $this->products->count() . '</pre>');
            }
        } elseif ($this->debug) {
            $this->debugOutput('<h3>BASE LIST AFTER PRICE SEARCH</h3><p>Not required</p>');
        }
    }

    protected function doAdvancedSearch()
    {
        $this->extend('doAdvancedSearchExtended', $this->products);
    }



    //#######################################
    // DEBUG
    //#######################################

    protected function debugOutput($mixed)
    {
        if($this->debug) {
            echo Vardump::inst()->mixedToUl($mixed);
        }
    }

    protected function hasMinMaxSearch(): bool
    {
        return $this->rawData['MinimumPrice'] < $this->rawData['MaximumPrice'];
    }

    protected function getSearchApi()
    {
        return Injector::inst()->get(KeywordSearchBuilder::class);
    }





}
