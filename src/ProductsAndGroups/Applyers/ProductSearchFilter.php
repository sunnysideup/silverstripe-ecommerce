<?php

namespace Sunnysideup\Ecommerce\ProductsAndGroups\Applyers;

use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\GetVariables;
use Sunnysideup\Ecommerce\Api\KeywordSearchBuilder;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Model\Search\SearchHistory;
use Sunnysideup\Ecommerce\Pages\Product;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPage;
use Sunnysideup\Ecommerce\Traits\PartialObjectCache;
use Sunnysideup\Vardump\Vardump;

/**
 * provides data on the user.
 */
class ProductSearchFilter extends BaseApplyer
{
    use PartialObjectCache;

    /**
     * @var string
     */
    public const KEY_FOR_SORTER = 'relevant';

    /**
     * @var string[]
     */
    private const PARTIAL_CACHE_FIELDS_TO_CACHE = [
        'rawData',
        'productIds',
        'productGroupIds',
        'baseListOwner',
    ];

    /**
     * @var array<string, array<string, array<string, int>|bool|string>>
     */
    private const OPTIONS_FOR_SORT = [
        self::KEY_FOR_SORTER => [
            'Title' => 'Most Relevant',
            'SQL' => [
                'ShowInSearch' => 1,
            ],
            'UsesParamData' => true,
            'IsShowFullList' => false,
        ],
    ];

    /**
     * set to TRUE to show the search logic.
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Is this product list filtered or can we rely on Product::get()->filter(['ShowInSearch'])
     * @var bool
     */
    protected $productListIsFiltered = false;

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
     * class name of the buyables to search
     * at this stage, you can only search one type of buyable at any one time
     * e.g. only products or only mydataobject.
     *
     * leave blank to use the default
     *
     * @var string
     */
    protected $baseClassNameForBuyables = '';

    /**
     * class name of the buyables to search
     * at this stage, you can only search one type of buyable at any one time
     * e.g. only products or only mydataobject.
     *
     * @var string
     */
    protected $baseClassNameForGroups = ProductGroup::class;

    /**
     * this is mysql specific, see: https://dev.mysql.com/doc/refman/5.0/en/fulltext-boolean.html.
     * not used at the moment!
     *
     * @var bool
     */
    protected $useBooleanSearch = true;

    /**
     * Maximum number of results to return
     * we limit this because otherwise the system will choke
     * the assumption is that no user is really interested in looking at
     * tons of results.
     * It defaults to: EcommerceConfig::get("ProductGroup", "maximum_number_of_products_to_list").
     *
     * @var int
     */
    protected $maximumNumberOfResults = 0;

    protected $productsForGroups;

    /**
     * results for groups.
     *
     * @var array
     */
    protected static $groupCache = [];

    /**
     * @var DataList
     */
    protected static $groupListCache;
    /**
     * @var DataList
     */
    protected static $debugString = '';

    /**
     * @var bool
     */
    private static $use_product_search_table = true;

    /**
     * make sure that these do not exist as a URLSegment.
     *
     * @var array
     */
    private static $options = [
        self::KEY_FOR_SORTER => [
            'Title' => 'Most Relevant',
            'SQL' => [
                'ShowInSearch' => 1,
            ],
            'UsesParamData' => true,
            'IsShowFullList' => false,
        ],
    ];

    /**
     * do we use the cache at all.
     * we put this here, even though it is used in the cache!
     *
     * @var bool
     */
    private static $use_partial_cache = true;

    /**
     * e.g. IDForSearchResults.
     *
     * @var string
     */
    private static $custom_id_method_to_retrieve_products = '';

    /**
     * Default selection criteria for products.
     *
     * @var array
     */
    private static $default_product_filter = ['ShowInSearch' => true];

    /**
     * when we do not know the relevance then sort like this.
     *
     * @var array
     */
    private static $in_group_sort_sql = ['Price' => 'DESC', 'ID' => 'DESC'];

    public static function keyword_sanitised(?string $string = ''): string
    {
        $string = Convert::raw2sql($string);
        $string = strtolower($string);

        return substr($string, 0, SearchHistory::KEYWORD_LENGTH_LIMIT);
    }

    /**
     * @param string       $key    optional key
     * @param array|string $params optional params to go with key
     */
    public function apply(?string $key = null, $params = null): self
    {
        $this->debug = ! empty($_GET['showdebug']) && (Director::isDev() || Permission::check('ADMIN'));
        if (! $this->applyStart($key, $params)) {
            if (is_array($this->rawData) && count($this->rawData)) {
                // we need to keep this hash
                $hash = $this->getHashBasedOnRawData();
                $outcome = $this->partialCacheApplyVariablesFromCache($hash);
                if ($outcome && ! $this->debug) {
                    $this->runFullProcessFromCache();
                } else {
                    $this->runFullProcess();
                    $this->partialCacheSetCacheForHash($hash);
                }
                //not sure why we need this, but keeping for now.
                self::$groupCache = $this->productGroupIds;
                self::$groupListCache = $this->productsForGroups;
                $this->products = $this->products->filter(['ID' => $this->getProductIds()]);
                $sorter = ArrayMethods::create_sort_statement_from_id_array(
                    $this->getProductIds(),
                    $this->finalProductList->getBuyableClassName()
                );
                $additionalSortOption = self::OPTIONS_FOR_SORT;
                $additionalSortOption[self::KEY_FOR_SORTER]['SQL'] = $sorter;
                ProductSorter::setDefaultSortOrderFromFilter($additionalSortOption);
            }
            $this->applyEnd($key, $this->rawData);
        }

        return $this;
    }

    public function getTitle(?string $key = '', $params = null): string
    {
        return 'Search Results Title (to be completed)';
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

    /**
     * @return DataList
     */
    public function getProductGroupAsList()
    {
        return self::$groupListCache;
    }

    public function getProductGroupIds(): array
    {
        return ArrayMethods::filter_array(self::$groupCache);
    }

    public function getHasResults(): bool
    {
        return count($this->productIds) > 1 || count($this->productGroupIds) > 1;
    }

    public function getDebugOutputString(): string
    {
        return self::$debugString;
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
    // key methods
    //#######################################

    protected function applyStart(?string $key = null, $params = null): bool
    {
        if (! is_array($params)) {
            $this->rawData = GetVariables::url_string_to_array((string) $params);
        } else {
            $this->rawData = $params;
        }
        if (! $this->baseClassNameForBuyables) {
            $this->baseClassNameForBuyables = EcommerceConfig::get(ProductGroup::class, 'base_buyable_class');
        }

        return parent::applyStart($key, $this->rawData);
    }

    protected function runFullProcessFromCache()
    {
        // products
        $tmpVar = $this->baseClassNameForBuyables;
        $filter = ['ID' => ArrayMethods::filter_array($this->productIds)];
        $this->products = $tmpVar::get()->filter($filter);

        //product groups
        $tmpVar = $this->baseClassNameForGroups;
        $filter = ['ID' => ArrayMethods::filter_array($this->productGroupIds)];
        $this->productsForGroups = $tmpVar::get()->filter($filter);
    }

    //#######################################
    // Runners
    //#######################################

    /**
     * run process from ProductGroup.
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
        if ($this->products->exists()) {
            if (! empty($this->rawData['Keyword']) && strlen($this->rawData['Keyword']) > 1) {
                $this->runKeywordSearch();
            } else {
                // add directly to results
                $this->addToResults($this->products);
            }
        }
    }

    protected function runKeywordSearch()
    {
        $this->keywordPhrase = $this->rawData['Keyword'];
        $this->doKeywordCleanup();
        $this->doKeywordReplacements();
        $this->doProductSearch();
        $this->doGroupSearch();
    }

    /**
     * set up basics, using data.
     */
    protected function doProcessSetup()
    {
        if (! $this->maximumNumberOfResults) {
            $this->maximumNumberOfResults = (int) EcommerceConfig::get(ProductGroupSearchPage::class, 'maximum_number_of_products_to_list_for_search');
        }

        if ($this->debug) {
            $this->debugOutput('<h2>Debugging Search Results in ' . static::class . '</h2>');
            $this->debugOutput('<p>Base Class Name: ' . $this->baseClassNameForBuyables . '</p>');
            $this->debugOutput('<p style="color: red">data: ' . print_r($this->rawData, 1) . '</p>');
        }
        $this->rawData['MinimumPrice'] = $this->rawData['MinimumPrice'] ?? 0;
        $this->rawData['MaximumPrice'] = $this->rawData['MaximumPrice'] ?? 0;
        $this->rawData['MinimumPrice'] = floatval(str_replace(',', '', $this->rawData['MinimumPrice']));
        $this->rawData['MaximumPrice'] = floatval(str_replace(',', '', $this->rawData['MaximumPrice']));

        $this->rawData['OnlyThisSection'] = (bool) (int) ($this->rawData['OnlyThisSection'] ?? 0);
        //swapsies!
        if ($this->rawData['MinimumPrice'] > $this->rawData['MaximumPrice']) {
            if ($this->rawData['MaximumPrice'] > 0) {
                $oldMin = $this->rawData['MinimumPrice'];
                $this->rawData['MinimumPrice'] = $this->rawData['MaximumPrice'];
                $this->rawData['MaximumPrice'] = $oldMin;
            }
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
     * replace keywords with better ones
     * we also need them for groups!
     */
    protected function doKeywordReplacements()
    {
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
        if (! $this->weHaveEnoughResults()) {
            $count = 0;
            if ($this->debug) {
                $this->debugOutput('<hr />');
                $this->debugOutput('<h3>FULL KEYWORD SEARCH</h3>');
            }

            // work out fields to search
            $where = '';
            if($this->getProductListIsFiltered()) {
                $where = 'ProductID IN (' . implode(', ', $this->products->columnUnique()) . ')';
            }
            $ids = $this->getSearchApi()->getProductResults(
                $this->keywordPhrase,
                $where,
                $this->maxToAdd()
            );

            foreach ($ids as $id) {
                if ($this->addToResultsInner($id)) {
                    break;
                }
            }
            // } else {
            //     //work out searches
            //     $searches = $this->getSearchApi()->getSearchArrays($this->keywordPhrase, $fieldArray);
            //     //if($this->debug) { $this->debugOutput("<pre>SEARCH ARRAY: ".print_r($searches, 1)."</pre>");}
            //
            //     //we search exact matches first then other matches ...
            //     foreach ($searches as $search) {
            //         $list2 = $this->products
            //             ->where($search);
            //         if ($this->debug) {
            //             $count += $list2->count();
            //             $this->debugOutput("<p>{$search} from (" . $this->products->count() . '): ' . $list2->count() . '</p>');
            //         }
            //         $this->addToResults($list2, false);
            //         if ($this->weHaveEnoughResults()) {
            //             break;
            //         }
            //     }
            // }
            if ($this->debug) {
                $this->debugOutput("<h3>FULL KEYWORD SEARCH: {$count}</h3>");
            }
        }
    }

    /**
     * how many more can we add?
     */
    protected function maxToAdd(): int
    {
        return $this->maximumNumberOfResults - $this->resultArrayPos;
    }

    /**
     * search for groups.
     */
    protected function doGroupSearch()
    {
        if ($this->debug) {
            $this->debugOutput('<hr />');
            $this->debugOutput('<h3>PRODUCT GROUP SEARCH ' . $this->productsForGroups->count() . '</h3>');
        }

        $count = 0;
        // work out fields to search

        // work out searches
        $filterIds = $this->productsForGroups->columnUnique();
        $where = '';
        if($this->getProductListIsFiltered()) {
            $where = 'ProductID IN (' . implode(', ', $this->products->columnUnique()) . ')';
            if (! empty($filterIds)) {
                $where = 'ProductGroupID IN (' . implode(', ', $filterIds) . ')';
            }
        }
        $ids = $this->getSearchApi()->getProductGroupResults(
            $this->keywordPhrase,
            $where
        );
        if ($this->debug) {
            $this->debugOutput('<pre>ID ARRAY: ' . print_r($ids, 1) . ' using where of ' . $where . '</pre>');
        }
        $sortStatement = ArrayMethods::create_sort_statement_from_id_array($ids, ProductGroup::class);
        $this->productsForGroups = $this->productsForGroups
            ->filter(['ID' => ArrayMethods::filter_array($ids)])
            ->sort($sortStatement)
        ;
        $this->productGroupIds = $ids;
        if ($this->debug) {
            $this->debugOutput('<h3>PRODUCT GROUP SEARCH: ' . count($this->productGroupIds) . '</h3>');
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
     */
    protected function addToResults(DataList $listToAdd, ?bool $allowOneAnswer = false): bool
    {
        if ($this->weHaveEnoughResults()) {
            return true;
        }
        // immediate redirect?
        if ($listToAdd->exists()) {
            $sort = $this->Config()->get('in_group_sort_sql');
            $listToAdd = $listToAdd
                ->limit($this->maxToAdd())
                ->sort($sort)
                ->exclude(['ID' => ArrayMethods::filter_array($this->productIds)])
            ;
            $customMethod = $this->Config()->get('custom_id_method_to_retrieve_products');
            if (! $customMethod) {
                //check that this is the right order!
                $listToAdd = $listToAdd->columnUnique('ID');
            }
            foreach ($listToAdd as $pageIdOrObject) {
                if ($customMethod) {
                    $id = $pageIdOrObject->{$customMethod}();
                } elseif (is_int($pageIdOrObject)) {
                    $id = $pageIdOrObject;
                }
                if ($this->addToListInner($id)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * returns false if we can add more.
     */
    protected function addToResultsInner(int $id): bool
    {
        if ($id) {
            if (! in_array($id, $this->productIds, true)) {
                ++$this->resultArrayPos;
                $this->productIds[$this->resultArrayPos] = $id;
                if ($this->weHaveEnoughResults()) {
                    return true;
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
        return $this->resultArrayPos >= $this->maximumNumberOfResults;
    }

    //###########################################
    // create lists
    //###########################################

    protected function createBaseList()
    {
        if (! $this->productsForGroups instanceof DataList) {
            $this->productsForGroups = $this->finalProductList->getParentGroups();
        }
        if (! $this->productsForGroups->exists()) {
            $tmpVar = $this->baseClassNameForGroups;
            $this->productsForGroups = $tmpVar::get();
        }
        if ($this->debug) {
            $this->debugOutput('<hr />');
            $this->debugOutput('<h3>BASE LIST</h3><pre>' . Vardump::inst()->mixedToUl($this->products) . '</pre>');
            $this->debugOutput('<h3>BASE GROUP LIST</h3><pre>' . Vardump::inst()->mixedToUl($this->productsForGroups) . '</pre>');
        }
    }

    /**
     * filter baselist for price min and max.
     */
    protected function doPriceSearch()
    {
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
        if ($this->debug) {
            self::$debugString .= Vardump::inst()->mixedToUl($mixed);
        }
    }

    protected function hasMinMaxSearch(): bool
    {
        return ($this->rawData['MinimumPrice'] <= $this->rawData['MaximumPrice']) || $this->rawData['MinimumPrice'] > 0 && ! $this->rawData['MaximumPrice'];
    }

    protected function getSearchApi()
    {
        return Injector::inst()->get(KeywordSearchBuilder::class);
    }

    protected function getHashBasedOnRawData(): string
    {
        //important we add this so that we can add it to hash
        return serialize($this->rawData + ['baseListOwnerID' => $this->baseListOwner->ID]);
    }

    protected function getProductListIsFiltered() : bool
    {
        return $this->hasMinMaxSearch() || $this->;
    }

}
