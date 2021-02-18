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

use Sunnysideup\Vardump\Vardump;

/**
 * Product search form
 */
class ProductSearchForm extends Form
{
    private const FIELDS_TO_CACHE = [
        'rawData',
        'keywordPhrase',
        'productIds',
        'productGroupIds',
        'resultArrayPos',
        'immediateRedirectPage',
        'baseListOwner',
        'baseClassNameForBuyables',
        'baseClassNameForGroups',
        'additionalGetParameters',
        'maximumNumberOfResults',
    ];

    protected $nameOfProductsBeingSearched = '';

    protected $productsToSearch = null;

    /**
     * set to TRUE to show the search logic.
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * @var bool
     */
    protected $hasCache = false;

    /**
     * Fields are:
     * - Keyword
     * - MinimumPrice
     * - MaximumPrice
     * - OnlyThisSection
     *
     * @var array
     */
    protected $rawData = [];

    /**
     * processed keyword
     * @var string
     */
    protected $keywordPhrase = '';

    /**
     * processed keyword
     * @var string
     */
    protected $minPrice = 0;

    /**
     * processed keyword
     * @var string
     */
    protected $maxPrice = 0;

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
    protected $immediateRedirectPage = null;

    /**
     * a custom base list
     * @var DataList
     */
    protected $baseList = null;

    /**
     * a custom base list for ProductGroups
     * @var DataList
     */
    protected $baseListForGroups = null;

    /**
     * a product group that creates the base list.
     * @var ProductGroup
     */
    protected $baseListOwner = null;

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
     * do we use the cache at all.
     * @var bool
     */
    private static $use_cache = false;

    /**
     * when we do not know the relevance then sort like this.
     * @var array
     */
    private static $in_group_sort_sql = ['Price' => 'DESC'];

    /**
     * List of additional fields that should be searched full text.
     * We are matching this against the buyable class name.
     *
     * @var array
     */
    private static $extra_buyable_fields_to_search_full_text_default = [
        Product::class => ['Title', 'MenuTitle', 'Content', 'MetaDescription'],
        ProductGroup::class => ['Title', 'MenuTitle', 'Content', 'MetaDescription'],
    ];

    /**
     * @var bool
     */
    private static $include_price_filters = true;

    /**
     * ProductsToSearch can be left blank to search all products.
     *
     * @param Controller              $controller                  - associated controller
     * @param string                  $name                        - name of form
     */
    public function __construct($controller, string $name)
    {
        $this->extraBuyableFieldsToSearchFullText = Config::inst()->get(self::class, 'extra_buyable_fields_to_search_full_text_default');
        $request = $controller->getRequest();
        $defaults = [
            'Keyword' => $request->getVar('Keyword'),
            'MinimumPrice' => floatval($request->getVar('MinimumPrice')),
            'MaximumPrice' => floatval($request->getVar('MaximumPrice')),
            'OnlyThisSection' => (intval($request->getVar('OnlyThisSection')) - 0 ? 1 : 0),
        ];
        //fields
        $fields = FieldList::create();
        //turn of security to allow caching of the form:
        if ($this->config()->get('include_price_filters')) {
            $fields->push(
                NumericField::create('MinimumPrice', _t('ProductSearchForm.MINIMUM_PRICE', 'Minimum Price'), $defaults['MinimumPrice'])->setScale(2),
            );
            $fields->push(
                NumericField::create('MaximumPrice', _t('ProductSearchForm.MAXIMUM_PRICE', 'Maximum Price'), $defaults['MaximumPrice'])->setScale(2)
            );
        }
        $fields->push(
            $keywordField = TextField::create('Keyword', _t('ProductSearchForm.KEYWORDS', 'Keywords'), $defaults['Keyword'])
        );
        $fields->push(
            HiddenField::create('OnlyThisSection', $defaults['OnlyThisSection'])
        );
        $keywordField->setAttribute('placeholder', _t('ProductSearchForm.KEYWORD_PLACEHOLDER', 'search products ...'));

        if (Director::isDev() || Permission::check('ADMIN')) {
            $fields->push(CheckboxField::create('DebugSearch', 'Debug Search'));
        }
        // actions
        $actions = FieldList::create(
            FormAction::create('doProductSearchForm', 'Search')
        );

        // required fields
        $requiredFields = [];
        $validator = ProductSearchFormValidator::create($requiredFields);

        $this->extend('updateFields', $fields);
        $this->extend('updateActions', $actions);
        $this->extend('updateValidator', $validator);
        parent::__construct($controller, $name, $fields, $actions, $validator);
        //make it an easily accessible form  ...
        $this->setFormMethod('get');
        $this->disableSecurityToken();
        //extensions need to be set after __construct
        //extension point
        $this->extend('updateProductSearchForm', $this);

        return $this;
    }

    public function forTemplate()
    {
        if ($this->hasOnlyThisSection()) {
            $title = _t('ProductSearchForm.ONLY_SHOW', 'Only search in');
            if ($this->baseListOwner) {
                $title .= ' <em>' . $this->baseListOwner->Title . '</em> ';
            }
            $title = DBField::create_field('HTMLText', $title);
            $this->Fields()->replaceField(
                'OnlyThisSection',
                CheckboxField::create(
                    'OnlyThisSection',
                    $title,
                    1
                )
            );
        }

        return parent::forTemplate();
    }

    ########################################
    # getters
    ########################################

    /**
     * they search phrase used.
     * @return string
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

    ########################################
    # setters
    ########################################

    public function setSearchHash(?string $hash = ''): self
    {
        if ($hash) {
            $oldData = $this->applyCacheFromHash($hash);
            if (! empty($this->rawData['rawData'])) {
                $this->loadDataFrom($oldData['rawData']);
            }
        }

        return $this;
    }

    /**
     * @param  DataList $baseList
     * @return self
     */
    public function setBaseList($baseList): self
    {
        $this->baseList = $baseList;

        return $this;
    }
    /**
     * @param  DataList $baseList
     * @return self
     */
    public function setBaseListForGroups($baseListForGroups): self
    {
        $this->baseListForGroups = $baseListForGroups;

        return $this;
    }

    /**
     * @param  ProductGroup $baseListOwner
     * @return self
     */
    public function setBaseListOwner($baseListOwner): self
    {
        $this->baseListOwner = $baseListOwner;
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

    public function setMaximumNumberOfResults(int $i): self
    {
        $this->maximumNumberOfResults = $i;

        return $this;
    }

    public function setAdditionalGetParameters(string $s): self
    {
        $this->additionalGetParameters = $s;

        return $this;
    }

    ########################################
    # do-ers
    ########################################

    public function doProductSearchForm($data, $form)
    {
        $this->runFullProcessInner($data);
        $this->doProcessResults();
    }

    ########################################
    # UTILS
    ########################################

    public function runFullProcess($data)
    {
        $this->debug = false;
        $this->runFullProcessInner($data);
    }

    /**
     * saves the form into session.
     */
    public function saveDataToSession()
    {
        $data = $this->getData();
        $data = Sanitizer::remove_from_data_array($data);
        $this->setSessionData($data);
    }

    protected function hasOnlyThisSection(): bool
    {
        if ($this->controller instanceof ProductGroupSearchPageController) {
            return false;
        }
        if ($this->baseListOwner instanceof ProductGroupSearchPage) {
            return false;
        }
        if ($this->baseListOwner) {
            return $this->baseListOwner->getProducts()->count() > 0;
        }
        if ($this->baseList) {
            return $this->baseList->count() > 0;
        }
        return true;
    }

    protected function runFullProcessInner($data)
    {
        $this->doProcessSetup($data);

        //basic get

        $this->createBaseList();
        $this->doPriceSearch();

        //defining some variables
        // die('xxx');

        //KEYWORD SEARCH - only bother if we have any keywords and results at all ...
        if ($this->baseList->count()) {
            if (! empty($data['Keyword']) && strlen($data['Keyword']) > 1) {
                $this->keywordPhrase = $data['Keyword'];
                $this->doKeywordCleanup();
                $this->doAddToSearchHistory();
                $this->doInternalItemSearch();
                $this->doKeywordReplacements();
                $this->doProductSearch();
                $this->doGroupSearch();
            } else {
                $this->addToResults($this->baseList, true);
            }
        }
    }

    /**
     * set up basics, using data.
     * @param  array $data
     */
    protected function doProcessSetup(array $data)
    {
        $this->saveDataToSession($data);
        $this->rawData = $data;
        if (! $this->maximumNumberOfResults) {
            $this->maximumNumberOfResults = EcommerceConfig::get(ProductGroupSearchPage::class, 'maximum_number_of_products_to_list_for_search');
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
            $this->debugOutput('<h2>Debugging Search Results</h2>');
            $this->debugOutput('<p>Base Class Name: ' . $this->baseClassNameForBuyables . '</p>');
            $this->debugOutput('<p style="color: red">data: ' . print_r($data, 1) . '</p>');
        }
        $this->rawData['MinimumPrice'] = floatval($this->rawData['MinimumPrice'] ?? 0);
        $this->rawData['MaximumPrice'] = floatval($this->rawData['MaximumPrice'] ?? 0);
        $this->rawData['OnlyThisSection'] = intval($this->rawData['OnlyThisSection'] ?? 0) ? true : false;
        if ($this->rawData['MinimumPrice'] > $this->rawData['MaximumPrice']) {
            $oldMin = $this->rawData['MinimumPrice'];
            $this->rawData['MinimumPrice'] = $this->rawData['MaximumPrice'];
            $this->rawData['MaximumPrice'] = $oldMin;
        }
        unset($this->rawData['action_doProductSearchForm']);
    }

    /**
     * cleanup keyword phrase
     */
    protected function doKeywordCleanup()
    {
        if ($this->debug) {
            $this->debugOutput('<h3>RAW KEYWORD</h3><p>' . $this->keywordPhrase . '</p>');
        }
        $this->keywordPhrase = Convert::raw2sql($this->keywordPhrase);
        $this->keywordPhrase = strtolower($this->keywordPhrase);
    }

    /**
     * add keywordphrase to search history
     */
    protected function doAddToSearchHistory()
    {
        if ($this->hasCache === true) {
            SearchHistory::add_entry($this->keywordPhrase);
        }
    }

    /**
     * look for internalItemID
     */
    protected function doInternalItemSearch()
    {
        if ($this->hasCache === true) {
            return;
        }
        if ($this->debug) {
            $this->debugOutput('<hr />');
            $this->debugOutput('<h2>SEARCH BY CODE</h2>');
        }
        $list1 = $this->baseList->filter(['InternalItemID' => $this->keywordPhrase]);
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
        if ($this->hasCache === true) {
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
     * search for products
     */
    protected function doProductSearch()
    {
        // @todo: consider using
        // DB::get_conn()->searchEngine(SiteTre::get(), $keywords, $start, $pageLength, "\"Relevance\" DESC", "", $booleanSearch);
        if ($this->hasCache === true) {
            return;
        }
        if (! $this->weHaveEnoughResults()) {
            $count = 0;
            if ($this->debug) {
                $this->debugOutput('<hr />');
                $this->debugOutput('<h3>FULL KEYWORD SEARCH</h3>');
            }
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

            $searches = $this->getSearchApi()->getSearchArrays($this->keywordPhrase, $fieldArray);
            //if($this->debug) { $this->debugOutput("<pre>SEARCH ARRAY: ".print_r($searches, 1)."</pre>");}

            //we search exact matches first then other matches ...
            foreach ($searches as $search) {
                $list2 = $this->baseList->where($search);
                $count += $list2->count();
                if ($this->debug) {
                    $this->debugOutput("<p>${search}: " . $list2->count() . '</p>');
                }
                $this->addToResults($list2);
                if ($this->weHaveEnoughResults()) {
                    break;
                }
            }
            if ($this->debug) {
                $this->debugOutput("<h3>FULL KEYWORD SEARCH: ${count}</h3>");
            }
        }
    }

    /**
     * search for groups
     */
    protected function doGroupSearch()
    {
        if ($this->hasCache === true) {
            return;
        }
        if ($this->immediateRedirectPage === null) {
            if ($this->debug) {
                $this->debugOutput('<hr />');
                $this->debugOutput('<h3>PRODUCT GROUP SEARCH</h3>');
            }

            $count = 0;
            //work out searches
            $fieldArray = $this->extraBuyableFieldsToSearchFullText[$this->baseClassNameForGroups];
            if ($this->debug) {
                $this->debugOutput('<pre>FIELD ARRAY: ' . print_r($fieldArray, 1) . '</pre>');
            }

            $searches = $this->getSearchApi()->getSearchArrays($this->keywordPhrase, $fieldArray);
            if ($this->debug) {
                $this->debugOutput('<pre>SEARCH ARRAY: ' . print_r($searches, 1) . '</pre>');
            }

            foreach ($searches as $search) {
                $productGroups = $this->baseListForGroups->where($search)->filter(['ShowInSearch' => 1]);
                $count = $productGroups->count();
                //redirect if we find exactly one match and we have no matches so far...
                if ($count === 1 && ! $this->resultArrayPos) {
                    $this->immediateRedirectPage = $productGroups->First();
                } elseif ($count > 0) {
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

    protected function getProductGroupBase()
    {

    }

    /**
     * finalise results
     */
    protected function doProcessResults()
    {
        //you can add more details here in extensions of this form.
        $this->extend('updateProcessResults');

        if ($this->immediateRedirectPage) {
            $link = $this->immediateRedirectPage->Link();
            if ($this->debug) {
                $this->debugOutput(
                    '<p style="color: red">Found one answer for potential immediate redirect: ' . $link . '</p>'
                );
            }
        } else {
            $redirectToPage = $this->getResultsPage();
            $hash = '';
            if ($this->hasCache === false && $this->config()->get('use_cache')) {
                $hash = $this->setCacheForHash();
            }
            $link = $redirectToPage->SearchResultLink($hash);
            $link .= '?';
            $link .= http_build_query($this->rawData);
            if ($this->additionalGetParameters) {
                $link .= '&' . $this->additionalGetParameters;
            }
        }
        if ($this->debug) {
            die('<a href="' . $link . '">see results</a>');
        }
        $this->controller->redirect($link);
    }

    ############################################
    # results management: add / count
    ############################################

    /**
     * add items to list.
     *
     * returns
     * - TRUE when done and
     * - FALSE when more results are needed
     *
     * @return bool
     */
    protected function addToResults(DataList $listToAdd, $allowOneAnswer = false): bool
    {
        if ($this->weHaveEnoughResults()) {
            return true;
        }
        $count = $listToAdd->count();
        if ($allowOneAnswer && $count === 1 && $this->resultArrayPos === 0) {
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
     * @return bool returns true if more results are needed.
     */
    protected function weHaveEnoughResults(): bool
    {
        if ($this->immediateRedirectPage) {
            return true;
        }
        if ($this->resultArrayPos >= $this->maximumNumberOfResults) {
            return true;
        }
        return false;
    }

    ############################################
    # results management: add / count
    ############################################

    protected function getResultsPage()
    {
        if (empty($this->rawData['OnlyThisSection'])) {
            return ProductGroupSearchPage::main_search_page();
        }
        //if no specific section is being searched then we redirect to search page:
        return $this->controller->dataRecord;
    }

    protected function createBaseList()
    {
        if ($this->hasCache === true) {
            return;
        }
        if (! $this->baseList instanceof SS_List) {
            if ($this->rawData['OnlyThisSection']) {
                $this->baseList = $this->baseListOwner->getProducts();
                $this->baseListForGroups = $this->baseListOwner->getBaseProductList->getParentGroups();
            } else {
                $tmpVar = $this->baseClassNameForBuyables;
                $this->baseList = $tmpVar::get()->filter(['ShowInSearch' => 1]);
                $ecomConfig = EcommerceConfig::inst();
                if ($ecomConfig->OnlyShowProductsThatCanBePurchased) {
                    $this->baseList->filter(['AllowPurchase' => 1]);
                }
                $this->baseListForGroups = ProductGroup::get();
            }
        }
        if ($this->debug) {
            $this->debugOutput('<hr />');
            $this->debugOutput('<h3>BASE LIST</h3><pre>' . Vardump::inst()->mixedToUl($this->baseList) . '</pre>');
            $this->debugOutput('<h3>BASE GROUP LIST</h3><pre>' . Vardump::inst()->mixedToUl($this->baseListForGroups) . '</pre>');
        }
    }

    /**
     * filter baselist for price min and max
     */
    protected function doPriceSearch()
    {
        if ($this->hasCache === true) {
            return;
        }
        if ($this->hasMinMaxSearch()) {
            $min = $this->rawData['MinimumPrice'];
            if ($min) {
                $this->baseList = $this->baseList->filter(['Price:GreaterThanOrEqual' => $min]);
            }
            $max = $this->rawData['MaximumPrice'];
            if ($max) {
                $this->baseList = $this->baseList->filter(['Price:LessThanOrEqual' => $max]);
            }
            if ($this->debug) {
                $this->debugOutput('<hr />');
                $this->debugOutput('<h3>BASE LIST AFTER PRICE SEARCH</h3><pre>' . Vardump::inst()->mixedToUl($this->baseList->sql()) . '</pre>');
                $this->debugOutput('<h3>PRODUCTS AFTER PRICE SEARCH</h3><pre>' . $this->baseList->count() . '</pre>');
            }
        } else {
            if ($this->debug) {
                $this->debugOutput('<h3>BASE LIST AFTER PRICE SEARCH</h3><p>Not required</p>');
            }
        }
    }

    ########################################
    # CACHING AND SERIALIZING
    ########################################

    protected function getSerializedObject(?array $data = [])
    {
        $variables = [];
        foreach (self::FIELDS_TO_CACHE as $variable) {
            $value = $this->{$variable};
            if (is_object($value) && is_a($value, DataObject::class)) {
                if ($value->ClassName && $value->ID) {
                    $variables[$variable]['ClassName'] = $value->ClassName;
                    $variables[$variable]['ID'] = $value->ID;
                }
            } elseif (is_object($value)) {
                //do nothing
            } else {
                $variables[$variable] = $value;
            }
        }
        return serialize($variables);
    }

    /**
     * @param  string $data optional
     * @return float
     */
    protected function getHash(?string $data = ''): int
    {
        if (! $data) {
            $data = $this->getSerializedObject();
        }
        return crc32($data);
    }

    protected function setCacheForHash(): float
    {
        $data = $this->getSerializedObject();
        $hash = $this->getHash($data);

        EcommerceCache::inst()->save($hash, $data, true);

        return $hash;
    }

    protected function getCacheForHash(string $hash): array
    {
        $array = [];
        $cache = EcommerceCache::inst();
        if ($cache->hasCache($hash)) {
            $array = $cache->retrieve($hash);
            if (! is_array($array)) {
                $array = [];
            }
        }

        return $array;
    }

    protected function applyCacheFromHash(string $hash): array
    {
        $array = $this->getCacheForHash($hash);
        if (! empty($array['productIds']) && $this->config()->get('use_cache')) {
            $this->hasCache = true;
            foreach ($array as $variable => $value) {
                if (in_array($variable, self::FIELDS_TO_CACHE, true)) {
                    $this->{$variable} = $this->arrayToObject($value);
                }
            }
        }
        return $array;
    }

    protected function arrayToObject($value)
    {
        if (is_array($value) && count($value) === 2 && isset($value['ID']) && isset($value['ClassName'])) {
            $className = $value['ClassName'];
            $id = intval($value['ID']);
            if (class_exists($className) && $id) {
                return $className::get()->byId($id);
            }
        }

        return $value;
    }

    ########################################
    # DEBUG
    ########################################

    protected function debugOutput($mixed)
    {
        echo Vardump::inst()->mixedToUl($mixed);
    }

    protected function hasMinMaxSearch(): bool
    {
        if ($this->rawData['MinimumPrice'] < $this->rawData['MaximumPrice']) {
            return true;
        }
        return false;
    }

    protected function getSearchApi()
    {
        return Injector::inst()->get(KeywordSearchBuilder::class);
    }
}
