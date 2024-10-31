<?php

namespace Sunnysideup\Ecommerce\Pages;

use PageController;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Permission;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\Ecommerce\Api\EcommerceCache;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\ProductSearchForm;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\BaseApplyer;
use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductSearchFilter;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\FinalProductList;
use Sunnysideup\Vardump\Vardump;

/**
 * Class \Sunnysideup\Ecommerce\Pages\ProductGroupController
 *
 * @property \Sunnysideup\Ecommerce\Pages\ProductGroup $dataRecord
 * @method \Sunnysideup\Ecommerce\Pages\ProductGroup data()
 * @mixin \Sunnysideup\Ecommerce\Pages\ProductGroup
 */
class ProductGroupController extends PageController
{
    /**
     * the exact list of products that is going to be shown (excluding pagination).
     *
     * @var DataList
     */
    protected $productList;

    /**
     * the final product list that we use to collect products.
     *
     * @var FinalProductList
     */
    protected $finalProductList;

    /**
     * The original Title of this page before filters, etc...
     *
     * @var string
     */
    protected $originalTitle = '';

    protected $userPreferencesObject;

    /**
     * form for searching.
     *
     * @var ProductSearchForm
     */
    protected $searchForm;

    /**
     * Is this a product search?
     *
     * @var bool
     */
    protected $isSearchResults = false;

    protected $secondaryTitle = '';

    protected $hasManyProductsCache;

    protected $totalRawCountCache;

    private static $minimum_number_of_pages_to_show_filters_and_sort = 3;

    private static $allowed_actions = [
        'debug' => 'ADMIN',
        'ProductSearchForm' => true,
    ];

    //#######################################
    // actions
    //#######################################

    public function index()
    {
        return $this->defaultReturn();
    }

    //##################################
    // template methods
    //##################################

    /**
     * adds Javascript to the page to make it work when products are cached.
     */
    public function CachingRelatedJavascript()
    {
        if ($this->ProductGroupListAreAjaxified()) {
            Requirements::customScript(
                "
                    if(typeof EcomCartOptions === 'undefined') {
                        var EcomCartOptions = {};
                    }
                    EcomCartOptions.ajaxifyProductList = true;
                    EcomCartOptions.ajaxifiedListHolderSelector = '#" . $this->AjaxDefinitions()->ProductListHolderID() . "';
                    EcomCartOptions.ajaxifiedListAdjusterSelectors = '." . $this->AjaxDefinitions()->ProductListAjaxifiedLinkClassName() . "';
                    EcomCartOptions.hiddenPageTitleID = '#" . $this->AjaxDefinitions()->HiddenPageTitleID() . "';
                ",
                'cachingRelatedJavascript_AJAXlist'
            );
        } else {
            Requirements::customScript(
                "
                    if(typeof EcomCartOptions === 'undefined') {
                        var EcomCartOptions = {};
                    }
                    EcomCartOptions.ajaxifyProductList = false;
                ",
                'cachingRelatedJavascript_AJAXlist'
            );
        }
        $currentOrder = ShoppingCart::current_order();
        if ($currentOrder->TotalItems(true)) {
            $responseClass = EcommerceConfig::get(ShoppingCart::class, 'response_class');
            $obj = new $responseClass();
            $obj->setIncludeHeaders(false);
            $json = $obj->ReturnCartData();
            Requirements::customScript(
                "
                    if(typeof EcomCartOptions === 'undefined') {
                        var EcomCartOptions = {};
                    }
                    EcomCartOptions.initialData= " . $json . ';
                ',
                'cachingRelatedJavascript_JSON'
            );
        }
    }

    /**
     * get the unpaginated list. Only set once.
     *
     * @return DataList
     */
    public function getProductList()
    {
        if (! $this->productList) {
            $this->productList = $this->getCachedProductList();
            if (! $this->productList) {
                // make sure to apply search filter first.
                $this->productList = $this->getFinalProductList()
                    ->applySearchFilter($this->getCurrentUserPreferencesKey('SEARCHFILTER'), $this->getCurrentUserPreferencesParams('SEARCHFILTER'))
                    ->applyGroupFilter($this->getCurrentUserPreferencesKey('GROUPFILTER'), $this->getCurrentUserPreferencesParams('GROUPFILTER'))
                    ->applyFilter($this->getCurrentUserPreferencesKey('FILTER'), $this->getCurrentUserPreferencesParams('FILTER'))
                    ->applySorter($this->getCurrentUserPreferencesKey('SORT'), $this->getCurrentUserPreferencesParams('SORT'))
                    ->applyDisplayer($this->getCurrentUserPreferencesKey('DISPLAY'), $this->getCurrentUserPreferencesParams('DISPLAY'))
                    ->getProducts()
                ;
                $this->setCachedProductList($this->productList);
            }
        }
        // @return SS_List
        return $this->productList;
    }

    /**
     * Return the products for this group.
     *
     * This is the call that is made from the template and has the actual final
     * products being shown.
     *
     * @return \SilverStripe\ORM\PaginatedList
     */
    public function Products(): ?PaginatedList
    {
        //get the list first, so that everything is calculated
        $list = $this->getProductList();

        $this->addSecondaryTitle();

        $this->cachingRelatedJavascript();

        return $this->paginateList($list);
    }

    public function ProductsPaginated(): ?PaginatedList
    {
        $list = $this->getProductList();

        return $this->paginateList($list);
    }

    /**
     * Unique caching key for the product list...
     */
    public function ProductGroupListCachingKey(?bool $withPageNumber = false): string
    {
        if ($this->ProductGroupListAreCacheable()) {
            return $this->getUserPreferencesClass()->ProductGroupListCachingKey($withPageNumber);
        }

        return '';
    }

    /**
     * Important
     * Unique caching key for the product list...
     */
    public function ProductGroupListCachingKeyForTemplate(?bool $withPageNumber = false): string
    {
        return EcommerceCache::inst()->cacheKeyRefiner($this->ProductGroupListCachingKey($withPageNumber));
    }

    /**
     * Is the product list cache-able?
     */
    public function ProductGroupListAreCacheable(): bool
    {
        if ($this->productListsHTMLCanBeCached()) {
            $currentOrder = ShoppingCart::current_order();

            return ! $currentOrder->getHasAlternativeCurrency();
        }

        return false;
    }

    /**
     * is the product list ajaxified.
     */
    public function ProductGroupListAreAjaxified(): bool
    {
        return true;
    }

    /**
     * title without additions.
     */
    public function OriginalTitle(): string
    {
        return $this->originalTitle;
    }

    /**
     * This method can be extended to show products in the side bar.
     */
    public function SidebarProducts(): ?SS_List
    {
        return null;
    }

    /**
     * Returns child product groups for use in 'in this section'. For example
     * the vegetable Product Group may have listed here: Carrot, Cabbage, etc...
     */
    public function MenuChildGroups(): ?DataList
    {
        if ($this->IsSearchResults()) {
            return null;
        }

        return $this->ChildCategories();
    }

    public function ShowGroupFilterLinks(): bool
    {
        return $this->HasManyProducts() && $this->HasGroupFilters();
    }

    public function ShowSearchFilterLinks(): bool
    {
        return $this->HasManyProducts() && $this->HasSearchFilters();
    }

    public function ShowFilterLinks(): bool
    {
        return $this->HasManyProducts() && $this->HasFilters();
    }

    public function ShowSortLinks(): bool
    {
        return $this->HasManyProducts() && $this->HasSorts();
    }

    public function ShowDisplayLinks(): bool
    {
        return $this->HasManyProducts() && $this->HasDisplays();
    }

    public function ShowGroupFilterSortDisplayLinks(): bool
    {
        return $this->ShowSearchFilterLinks() || $this->ShowGroupFilterLinks() || $this->ShowFilterLinks() || $this->ShowSortLinks() || $this->ShowDisplayLinks();
    }

    public function HasManyProducts(): bool
    {
        if (null === $this->hasManyProductsCache) {
            $this->hasManyProductsCache = $this->getBaseProductList()->hasMoreThanOne($this->Config()->get('minimum_number_of_pages_to_show_filters_and_sort'));
        }

        return $this->hasManyProductsCache;
    }

    public function HasSearchFilter(): bool
    {
        return (bool) $this->getCurrentUserPreferencesParams('SEARCHFILTER');
    }

    public function HasGroupFilter(): bool
    {
        return (bool) $this->getCurrentUserPreferencesParams('GROUPFILTER');
    }

    public function HasFilter(): bool
    {
        return $this->getCurrentUserPreferencesKey('FILTER') !== $this->getListConfigCalculated('FILTER');
    }

    public function HasSort(): bool
    {
        if ($this->IsSearchResults()) {
            return BaseApplyer::DEFAULT_NAME !== $this->getCurrentUserPreferencesKey('SORT');
        }

        return $this->getCurrentUserPreferencesKey('SORT') !== $this->getListConfigCalculated('SORT');
    }

    public function HasDisplay(): bool
    {
        return $this->getCurrentUserPreferencesKey('DISPLAY') !== $this->getListConfigCalculated('DISPLAY');
    }

    public function HasGroupFilterSortDisplay(): bool
    {
        return $this->HasSearchFilter() || $this->HasGroupFilter() || $this->HasFilter() || $this->HasSort() || $this->HasDisplay();
    }

    /**
     * we can use this for pre-set search filters.
     */
    public function HasSearchFilters(): bool
    {
        return false;
    }

    /**
     * Are group filters available? we check one at the time so that we do the least
     * amount of DB queries.
     */
    public function HasGroupFilters(): bool
    {
        return $this->GroupFilterLinks()->count() > 1;
    }

    /**
     * Are filters available? we check one at the time so that we do the least
     * amount of DB queries.
     */
    public function HasFilters(): bool
    {
        return $this->FilterLinks()->count() > 1;
    }

    /**
     * Are filters available? we check one at the time so that we do the least
     * amount of DB queries.
     */
    public function HasAnyTypeOfFiltersOrSorts(): bool
    {
        return true;
        // $this->HasFilters() || $this->HasGroupFilters() || $this->HasSearchFilters() || $this->HasSorts() || $this->HasDisplays();
    }

    /**
     * Are filters available? we check one at the time so that we do the least
     * amount of DB queries.
     */
    public function HasSorts(): bool
    {
        return $this->SortLinks()->count() > 1;
    }

    /**
     * Are filters available? we check one at the time so that we do the least
     * amount of DB queries.
     */
    public function HasDisplays(): bool
    {
        return $this->DisplayLinks()->count() > 1;
    }

    /**
     * Number of entries per page limited by total number of pages available...
     */
    public function MaxNumberOfProductsPerPage(): int
    {
        if ($this->IsShowFullList()) {
            return min($this->TotalCount(), $this->MaxNumberOfProductsPerPageAbsolute());
        }
        $perPage = $this->getProductsPerPage();
        $total = $this->TotalCount();

        return $perPage > $total ? $total : $perPage;
    }

    public function TotalCount(): int
    {
        return $this->getFinalProductList()->getRawCountCached();
    }

    public function StartLimit(): int
    {
        return ($this->pageStart()) + 1;
    }

    public function StopLimit(): int
    {
        $v = $this->StartLimit() + $this->Products()?->getPageLength() - 1;
        $totalCount = $this->TotalCount();
        if ($v > $totalCount) {
            $v = $totalCount;
        }
        return (int) $v;
    }

    public function getCurrentPageNumber(): int
    {
        $pageStart = $this->pageStart();
        if ($pageStart) {
            return (int) ($pageStart / $this->getProductsPerPageCalculated()) + 1;
        }

        return 1;
    }

    public function getProductsPerPageCalculated(): int
    {
        if ($this->IsShowFullList()) {
            // there is still pagination, but we show more of them.
            return $this->getProductsPerPage() * 4;
        } else {
            return $this->getProductsPerPage();
        }
    }

    protected function pageStart(): int
    {
        return (int) $this->request->getVar('start');
    }

    public function getUserPreferencesTitle(string $type, ?string $key): string
    {
        return $this->getProductGroupSchema()->getSortFilterDisplayValues($type, 'Title');
    }

    /**
     * returns the current searcj filter applied to the list
     * in a human readable string.
     */
    public function getCurrentSearchFilterTitle(): string
    {
        if ($this->hasSearchFilter()) {
            return $this->getUserPreferencesClass()->getSearchFilterTitle($this->getCurrentUserPreferencesKey('SEARCHFILTER'));
        }

        return '';
    }

    /**
     * returns the current filter applied to the list
     * in a human readable string.
     */
    public function getCurrentGroupFilterTitle(): string
    {
        if ($this->hasGroupFilter()) {
            return $this->getUserPreferencesClass()->getGroupFilterTitle($this->getCurrentUserPreferencesKey('GROUPFILTER'));
        }

        return '';
    }

    /**
     * returns the current filter applied to the list
     * in a human readable string.
     */
    public function getCurrentFilterTitle(): string
    {
        if ($this->hasFilter()) {
            return $this->getUserPreferencesClass()->getFilterTitle($this->getCurrentUserPreferencesKey('FILTER'));
        }

        return '';
    }

    /**
     * returns the current sort applied to the list
     * in a human readable string.
     */
    public function getCurrentSortTitle(): string
    {
        if ($this->HasSort()) {
            return $this->getUserPreferencesClass()->getSortTitle($this->getCurrentUserPreferencesKey('SORT'));
        }

        return '';
    }

    public function getCurrentDisplayTitle(): string
    {
        if ($this->HasDisplay()) {
            return $this->getUserPreferencesClass()->getDisplayTitle($this->getCurrentUserPreferencesKey('DISPLAY'));
        }

        return '';
    }

    public function getSearchFilterHeader(): string
    {
        return _t('Ecommerce.SEARCH_PRODUCTS', 'Search in ') . $this->originalTitle;
    }

    public function getGroupFilterHeader(): string
    {
        return _t('Ecommerce.FILTER_BY_CATEGORY', 'Filter by Category');
    }

    public function getFilterHeader(): string
    {
        return _t('Ecommerce.FILTER', 'Filter');
    }

    public function getSortHeader(): string
    {
        return _t('Ecommerce.SORT', 'SORT');
    }

    public function getDisplayHeader(): string
    {
        return _t('Ecommerce.DISPLAY', 'Presentation');
    }

    /**
     * short-cut for getListConfigCalculated("DISPLAY")
     * for use in templtes.
     *
     * @return string - key
     */
    public function MyDefaultDisplayStyle(): string
    {
        return $this->getListConfigCalculated('DISPLAY');
    }

    /**
     * Provides a ArrayList of links for filters products.
     *
     * @return \SilverStripe\ORM\ArrayList( ArrayData(ID, ClassName, Title, Current, Link, LinkingMode, Ajaxify, Object, Key))
     */
    public function SearchFilterLinks(): ArrayList
    {
        return $this->getUserPreferencesClass()->getLinksPerType('SEARCHFILTER');
    }

    /**
     * Provides an ArrayList of links for filters products.
     *
     * @return \SilverStripe\ORM\ArrayList( ArrayData(ID, ClassName, Title, Current, Link, LinkingMode, Ajaxify, Object, Key))
     */
    public function GroupFilterLinks(): SS_List
    {
        return $this->getUserPreferencesClass()->getLinksPerType('GROUPFILTER');
    }

    /**
     * Provides a DataList of links for filters products.
     * Note that this loses the special link values!
     *
     * @return DataList
     */
    public function GroupFilterLinksAsDataList(): DataList
    {
        return ProductGroup::get()->filter(['ID' => $this->GroupFilterLinks()->column('ID')]);
    }

    /**
     * Provides a ArrayList of links for filters products.
     * Note that this loses the special link values!
     *
     * @return DataList
     */
    public function GroupFilterLinksUsingFilteredObjects(array $filter): ArrayList
    {
        $list = $this->GroupFilterLinks();
        $listIds = ArrayMethods::filter_array($list->column('ID'));
        if (isset($filter['ID'])) {
            if (! is_array($filter['ID'])) {
                $filter['ID'] = [$filter['ID']];
            }
            $filter['ID'] = array_merge($filter['ID'], $listIds);
        } else {
            $filter += ['ID' => $listIds];
        }
        $filterList = ProductGroup::get()->filter($filter)->columnUnique();
        $list = $list->filter(['ID' => $filterList]);
        return $list;
    }

    /**
     * Provides a ArrayList of links for filters products.
     *
     * @return \SilverStripe\ORM\ArrayList( ArrayData(ID, ClassName, Title, Current, Link, LinkingMode, Ajaxify, Object, Key))
     */
    public function FilterLinks(): ArrayList
    {
        return $this->getUserPreferencesClass()->getLinksPerType('FILTER');
    }

    /**
     * Provides a ArrayList of links for sorting products.
     */
    public function SortLinks(): ArrayList
    {
        return $this->getUserPreferencesClass()->getLinksPerType('SORT');
    }

    /**
     * Provides a ArrayList for displaying display links.
     */
    public function DisplayLinks(): ArrayList
    {
        return $this->getUserPreferencesClass()->getLinksPerType('DISPLAY');
    }

    public function getLink($action = null): string
    {
        return $this->Link($action);
    }

    public function Link($action = null): string
    {
        return $this->getLinkTemplate($action);
    }

    public function ResetPreferencesLink($action = null): string
    {
        return parent::link() . '?reload=1';
    }

    public function ListAllLink(): string
    {
        return $this->getLinkTemplate('', 'DISPLAY', 'all');
    }

    public function ListAFewLink(): string
    {
        return $this->getLinkTemplate('', 'DISPLAY', 'default');
    }

    /**
     * After a search is conducted you may end up with a bunch
     * of recommended product groups. They will be returned here...
     * We sort the list in the order that it is provided.
     *
     * @return null|\SilverStripe\ORM\DataList (ProductGroups)
     */
    public function SearchResultsChildGroups(): ?DataList
    {
        return $this->getSearchApplyer()->getProductGroupAsList();
    }

    /**
     * returns a search form to search current products ready to search.
     *
     * @return ProductSearchForm object
     */
    public function ProductSearchForm()
    {
        if (null === $this->searchForm) {
            // $onlySearchTitle = $this->originalTitle;
            // if (ClassHelpers::check_for_instance_of($this->dataRecord, ProductGroupSearchPage::class, false)) {
            //     if ($this->HasSearchResults()) {
            //         $onlySearchTitle = 'Last Search Results';
            //     }
            // }
            $this->searchForm = ProductSearchForm::create(
                $this,
                'ProductSearchForm',
            );
            //load previous data.
            $this->searchForm->setBaseListOwner($this->dataRecord);
            // $sortGetVariable = $this->getSortFilterDisplayValues('SORT', 'getVariable');
            // $additionalGetParameters = $sortGetVariable . '=' . Config::inst()->get(ProductGroupSearchPage::class, 'best_match_key');
            // $form->setAdditionalGetParameters($additionalGetParameters);
            // $form->setSearchHash($this->searchKeyword);
        }

        return $this->searchForm;
    }

    /**
     * Does this page have any search results?
     * If search was carried out without returns
     * then it returns zero (false).
     *
     * @todo: to cleanup
     */
    public function HasSearchResults(): bool
    {
        return $this->getSearchApplyer()->getHasResults();
    }


    protected function createLinkFromProductList($link): string
    {
        /** @var DataList $list */
        $list = $this->getProductList();
        if ($list && $list->exists()) {
            $vars =  implode(',', $list->column('InternalItemID'));
        } else {
            $vars = '';
        }
        if (strpos($link, '?') === false) {
            $glue = '?';
        } else {
            $glue = '&';
        }
        return $link . $glue . 'codes=' . $vars;
    }

    /**
     * Should the product search form be shown immediately?
     */
    public function ShowSearchFormImmediately(): bool
    {
        if ($this->ShowSearchFormAtAll()) {
            if ($this->IsSearchResults()) {
                return true;
            }

            /** @var DataList $list */
            $list = $this->getProductList();
            return ! (bool) $list->exists();
        }

        return false;
    }

    /**
     * Show a search form on this page?
     */
    public function ShowSearchFormAtAll(): bool
    {
        return true;
    }

    /**
     * Is the current page a display of search results.
     */
    public function IsSearchResults(): bool
    {
        return $this->HasSearchFilter();
    }

    public function getCurrentUserPreferencesKey(?string $type = '')
    {
        return $this->getUserPreferencesClass()->getCurrentUserPreferencesKey($type);
    }

    public function getCurrentUserPreferencesParams(?string $type = '')
    {
        return $this->getUserPreferencesClass()->getCurrentUserPreferencesParams($type);
    }

    /**
     * Retrieve a list of products, based on the given parameters.
     *
     * This method is usually called by the various controller methods.
     *
     * The extraFilter helps you to select different products depending on the
     * method used in the controller.
     *
     * To paginate this
     *
     * @param array|string $extraFilter     OPTIONAL Additional SQL filters to apply to the Product retrieval
     * @param array|string $alternativeSort OPTIONAL Additional SQL for sorting
     *
     * @return FinalProductList
     */
    public function getFinalProductList($extraFilter = null, $alternativeSort = null)
    {
        if (! $this->finalProductList) {
            $className = $this->getProductGroupSchema()->getFinalProductListClassName();
            $this->finalProductList = $className::inst($this, $this->dataRecord);
            ClassHelpers::check_for_instance_of($this->finalProductList, FinalProductList::class, true);
        }
        if ($extraFilter) {
            $this->finalProductList->setExtraFilter($extraFilter);
        }
        if ($alternativeSort) {
            $this->finalProductList->setAlternativeSort($alternativeSort);
        }

        return $this->finalProductList;
    }

    public function DebugSearchString(): string
    {
        return $this->getSearchApplyer()->getDebugOutputString();
    }

    public function VardumpMe(string $method)
    {
        return Vardump::inst()->vardumpMe($this->{$method}(), $method, static::class);
    }

    /**
     *
     * must be public!
     * @param string $v
     * @return void
     */
    public function setSecondaryTitle(string $v): static
    {
        $this->secondaryTitle = $v;
        return $this;
    }

    public function getSecondaryTitle(): string
    {
        return $this->secondaryTitle;
    }

    protected function saveUserPreferences(?array $data = [])
    {
        return $this->getUserPreferencesClass()->saveUserPreferences($data);
    }

    protected function setSearchString()
    {
        //do nothing here, but on ProductGroupSearchPage, we set it as the baselist....
    }

    protected function afterHandleRequest()
    {
        if ($this->request->getVar('showdebug') && (Permission::check('ADMIN') || Director::isDev())) {
            $this->getProductGroupSchema()->getDebugProviderAsObject($this, $this->dataRecord)->print();
            die();
        }
        parent::afterHandleRequest();
    }

    protected function getCachedProductList(): ?DataList
    {
        $key = $this->ProductGroupListCachingKey();
        if ($key && EcommerceCache::inst()->hasCache($key)) {
            $ids = EcommerceCache::inst()->retrieve($key);
            $ids = ArrayMethods::filter_array($ids);
            $buyableClassName = $this->getBuyableClassName();
            return $buyableClassName::get()
                ->filter(['ID' => $ids])
                ->orderBy(ArrayMethods::create_sort_statement_from_id_array($ids, Product::class))
            ;
        }

        return null;
    }

    protected function setCachedProductList($productList)
    {
        if ($productList) {
            $key = $this->ProductGroupListCachingKey();
            $ids = ArrayMethods::filter_array($productList->columnUnique());
            EcommerceCache::inst()->save($key, $ids);
        }
    }

    /**
     * returns the current page with get variables. If a type is specified then
     * instead of the value for that type, we add: '[[INSERT_HERE]]'.
     *
     * @param string $action             e.g. filterfor
     * @param string $type               e.g. FILTER|SORT|DISPLAY
     * @param string $replacementForType e.g. 'all'
     */
    protected function getLinkTemplate(?string $action = null, ?string $type = '', ?string $replacementForType = ''): string
    {
        return $this->getUserPreferencesClass()->getLinkTemplate($action, $type, $replacementForType);
    }

    protected function init()
    {
        parent::init();
        if ($this->request->getVar('reload')) {
            return $this->redirect($this->Link());
        }
        $this->originalTitle = $this->MenuTitle;
        Requirements::themedCSS('client/css/ProductGroup');
        Requirements::themedCSS('client/css/ProductGroupPopUp');
        Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomProducts.js');
        //we save data from get variables...
        $this->saveUserPreferences();
        $this->setSearchString();
        //makes sure best match only applies to search -i.e. reset otherwise.
    }

    protected function setIdArrayDefaultSort($idArray, $alternativeSort = null)
    {
        return $this->getUserPreferencesClass()->setIdArrayDefaultSort($idArray, $alternativeSort);
    }

    /**
     * Overload this function of ProductGroup Extensions.
     */
    protected function returnAjaxifiedProductList(): bool
    {
        return Director::is_ajax();
    }

    /**
     * Overload this function of ProductGroup Extensions.
     */
    protected function productListsHTMLCanBeCached(): bool
    {
        //todo: FIX!
        return ! (bool) EcommerceConfig::inst()->OnlyShowProductsThatCanBePurchased;
    }

    /**
     * turns full list into paginated list.
     *
     * @param DataList $list
     */
    protected function paginateList($list): ?PaginatedList
    {
        $obj = null;
        if ($list->exists()) {
            $obj = PaginatedList::create($list, $this->request);
            $obj->setPageLength($this->getProductsPerPageCalculated());
        }

        return $obj;
    }

    protected function MaxNumberOfProductsPerPageAbsolute(): int
    {
        return EcommerceConfig::get(ProductGroup::class, 'maximum_number_of_products_to_list') + 1;
    }

    protected function IsShowFullList(): bool
    {
        return $this->getUserPreferencesClass()->IsShowFullList();
    }

    /**
     * @return ProductSearchFilter
     */
    protected function getSearchApplyer()
    {
        return $this->getFinalProductList()->getApplyer('SEARCHFILTER');
    }

    protected function defaultReturn()
    {
        // important - because we want to get all the details loaded before we start with
        // building template
        $this->Products();
        if ($this->returnAjaxifiedProductList()) {
            return $this->renderWith('Sunnysideup\Ecommerce\Includes\AjaxProductList');
        }

        return [];
    }

    protected function getUserPreferencesClass()
    {
        if (null === $this->userPreferencesObject) {
            $className = $this->getProductGroupSchema()->getUserPreferencesClassName();
            $this->userPreferencesObject = Injector::inst()->get($className)
                ->setRootGroup($this->dataRecord)
                ->setRootGroupController($this)
                ->setRequest($this->getRequest())
            ;
        }

        return $this->userPreferencesObject;
    }

    protected function addSecondaryTitle(?string $secondaryTitle = '')
    {
        $this->getUserPreferencesClass()->addSecondaryTitle($secondaryTitle);
    }
}
