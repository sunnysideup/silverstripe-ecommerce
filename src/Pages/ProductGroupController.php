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

use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductGroupFilter;
use Sunnysideup\Ecommerce\ProductsAndGroups\Builders\FinalProductList;

use Sunnysideup\Vardump\Vardump;

class ProductGroupController extends PageController
{
    /**
     * the exact list of products that is going to be shown (excluding pagination)
     * @var SS_List
     */
    protected $productList = null;

    /**
     * the final product list that we use to collect products
     * @var FinalProductList
     */
    protected $finalProductList;

    /**
     * The original Title of this page before filters, etc...
     *
     * @var string
     */
    protected $originalTitle = '';

    protected $hasGroupFilter = false;

    protected $secondaryTitleHasBeenAdded = false;

    protected $userPreferencesObject = null;

    /**
     * form for searching
     * @var ProductSearchForm
     */
    protected $searchForm = null;

    /**
     * Is this a product search?
     *
     * @var bool
     */
    protected $isSearchResults = false;

    /**
     * Is this a product search?
     *
     * @var string
     */
    protected $searchResultHash = '';

    private static $minimum_number_of_pages_to_show_filters_and_sort = 3;

    private static $allowed_actions = [
        'debug' => 'ADMIN',
        'filterforgroup' => true,
        'ProductSearchForm' => true,
        'searchresults' => true,
    ];

    ########################################
    # actions
    ########################################

    public function index()
    {
        return $this->defaultReturn();
    }

    /**
     * LEGACY METHOD!!!
     * This allows you to do a cross filter (e.g. Category Watches, Brand Swatch)
     *
     * @return \SilverStripe\Control\HTTPRequest
     */
    public function filterforgroup($request)
    {
        $otherProductGroup = ProductGroupFilter::get_group_from_url_segment($request->param('ID'));
        if ($otherProductGroup) {
            $this->hasGroupFilter = true;
            $this->saveUserPreferences(
                [
                    'GROUPFILTER' => [
                        'type' => 'default',
                        'params' => $otherProductGroup->URLSegment . ',' . $otherProductGroup->ID,
                        'title' => $otherProductGroup->MenuTitle,
                    ],
                ]
            );
        }
        return $this->defaultReturn();
    }

    /**
     * get the search results.
     *
     * @param \SilverStripe\Control\HTTPRequest $request
     * @param array
     */
    public function searchresults($request)
    {
        $this->isSearchResults = true;
        $this->searchHash = $this->request->param('ID');
        $this->ProductSearchForm(true);
        //set last search results
        //get results array
        $keyword = $this->ProductSearchForm()->getSearchPhrase();
        if ($keyword) {
            $keyword = _t('Ecommerce.SEARCH_FOR', 'search for: ') . substr($keyword, 0, 25);
        }
        //filters are irrelevant right now
        $this->addSecondaryTitle($keyword);

        return [];
    }

    ###################################
    # template methods
    ###################################

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
     * @return SS_List
     */
    public function getProductList()
    {
        if ($this->productList === null) {
            $this->productList = $this->getCachedProductList();
            if (! $this->productList) {
                $this->productList = $this->getFinalProductList()
                    ->applyGroupFilter($this->getCurrentUserPreferencesKey('GROUPFILTER'), $this->getCurrentUserPreferencesParams('GROUPFILTER'))
                    ->applyFilter($this->getCurrentUserPreferencesKey('FILTER'), $this->getCurrentUserPreferencesParams('FILTER'))
                    ->applySorter($this->getCurrentUserPreferencesKey('SORT'), $this->getCurrentUserPreferencesParams('SORT'))
                    ->applyDisplayer($this->getCurrentUserPreferencesKey('DISPLAY'), $this->getCurrentUserPreferencesParams('DISPLAY'))
                    ->getProducts();
                $this->setCachedProductList($this->productList);
            }
        }

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
        $this->addSecondaryTitle();

        $this->cachingRelatedJavascript();

        return $this->paginateList($this->getProductList());
    }

    /**
     * Unique caching key for the product list...
     *
     * @return string | Null
     */
    public function ProductGroupListCachingKey(?bool $withPageNumber = false): string
    {
        if ($this->ProductGroupListAreCacheable()) {
            $searchKey = $this->IsSearchResult() ? 'search' : 'non-search';
            return $this->getUserPreferencesClass()->ProductGroupListCachingKey($withPageNumber, $searchKey);
        }

        return '';
    }

    /**
     * Is the product list cache-able?
     *
     * @return bool
     */
    public function ProductGroupListAreCacheable(): bool
    {
        if ($this->productListsHTMLCanBeCached()) {
            if ($this->IsSearchResults()) {
                return false;
            }

            $currentOrder = ShoppingCart::current_order();

            if ($currentOrder->getHasAlternativeCurrency()) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * is the product list ajaxified.
     *
     * @return bool
     */
    public function ProductGroupListAreAjaxified(): bool
    {
        return $this->IsSearchResults() ? false : true;
    }

    /**
     * title without additions.
     *
     * @return string
     */
    public function OriginalTitle(): string
    {
        return $this->originalTitle;
    }

    /**
     * This method can be extended to show products in the side bar.
     * @return SS_List|null
     */
    public function SidebarProducts()
    {
        return;
    }

    /**
     * Returns child product groups for use in 'in this section'. For example
     * the vegetable Product Group may have listed here: Carrot, Cabbage, etc...
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function MenuChildGroups(?int $levels = 2): ?DataList
    {
        return $this->ChildGroups($levels);
    }

    public function ShowGroupFilterLinks(): bool
    {
        return $this->HasManyProducts() && $this->HasGroupFilters() ? true : false;
    }

    public function ShowFilterLinks(): bool
    {
        return $this->HasManyProducts() && $this->HasFilters() ? true : false;
    }

    public function ShowSortLinks(): bool
    {
        return $this->HasManyProducts() && $this->HasSorts() ? true : false;
    }

    public function ShowDisplayLinks(): bool
    {
        return $this->HasManyProducts() && $this->HasDisplays() ? true : false;
    }

    public function ShowGroupFilterSortDisplayLinks(): bool
    {
        return $this->ShowGroupFilterLinks() || $this->ShowFilterLinks() || $this->ShowSortLinks() || $this->ShowDisplayLinks();
    }

    public function HasManyProducts(): bool
    {
        return $this->getFinalProductList()->hasMoreThanOne($this->Config()->get('minimum_number_of_pages_to_show_filters_and_sort'));
    }

    public function HasGroupFilter(): bool
    {
        return $this->hasGroupFilter;
    }

    public function HasFilter(): bool
    {
        return $this->getCurrentUserPreferencesKey('FILTER') !== $this->getListConfigCalculated('FILTER');
    }

    public function HasSort(): bool
    {
        return $this->getCurrentUserPreferencesKey('SORT') !== $this->getListConfigCalculated('SORT');
    }

    public function HasDisplay(): bool
    {
        return $this->getCurrentUserPreferencesKey('DISPLAY') !== $this->getListConfigCalculated('DISPLAY');
    }

    public function HasGroupFilterSortDisplay(): bool
    {
        return $this->HasGroupFilter() || $this->HasFilter() || $this->HasSort() || $this->HasDisplay();
    }

    /**
     * Are group filters available? we check one at the time so that we do the least
     * amount of DB queries.
     *
     * @return bool
     */
    public function HasGroupFilters(): bool
    {
        return $this->GroupFilterLinks()->count() > 1;
    }
    /**
     * Are filters available? we check one at the time so that we do the least
     * amount of DB queries.
     *
     * @return bool
     */
    public function HasFilters(): bool
    {
        return $this->FilterLinks()->count() > 1;
    }

    /**
     * Are filters available? we check one at the time so that we do the least
     * amount of DB queries.
     *
     * @return bool
     */
    public function HasSorts(): bool
    {
        return $this->SortLinks()->count() > 1;
    }

    /**
     * Are filters available? we check one at the time so that we do the least
     * amount of DB queries.
     *
     * @return bool
     */
    public function HasDisplays(): bool
    {
        return $this->DisplayLinks()->count() > 1;
    }

    /**
     * Number of entries per page limited by total number of pages available...
     *
     * @return int
     */
    public function MaxNumberOfProductsPerPage(): int
    {
        $perPage = $this->getProductsPerPage();
        $total = $this->getFinalProductList()->getRawCount();

        return $perPage > $total ? $total : $perPage;
    }

    public function getCurrentPageNumber(): int
    {
        if ($pageStart = intval($this->request->getVar('start'))) {
            return ($pageStart / $this->getProductsPerPage()) + 1;
        }

        return 1;
    }

    public function getUserPreferencesTitle(string $type, ?string $key): string
    {
        return $this->getTemplateForProductsAndGroups()->getUserPreferencesTitle($type, $key);
    }

    /**
     * returns the current filter applied to the list
     * in a human readable string.
     *
     * @return string
     */
    public function getCurrentGroupFilterTitle(): string
    {
        if ($this->hasGroupFilter()) {
            return $this->getUserPreferencesTitle('GROUPFILTER', $this->getCurrentUserPreferencesKey('GROUPFILTER'));
        }
        return '';
    }
    /**
     * returns the current filter applied to the list
     * in a human readable string.
     *
     * @return string
     */
    public function getCurrentFilterTitle(): string
    {
        if ($this->hasFilter()) {
            return $this->getUserPreferencesTitle('FILTER', $this->getCurrentUserPreferencesKey('FILTER'));
        }
        return '';
    }

    /**
     * returns the current sort applied to the list
     * in a human readable string.
     *
     * @return string
     */
    public function getCurrentSortTitle(): string
    {
        if ($this->HasSort()) {
            return $this->getUserPreferencesTitle('SORT', $this->getCurrentUserPreferencesKey('SORT'));
        }

        return '';
    }

    /**
     * @return string
     */
    public function getCurrentDisplayTitle(): string
    {
        if ($this->HasDisplay()) {
            return $this->getUserPreferencesTitle('DISPLAY', $this->getCurrentUserPreferencesKey('DISPLAY'));
        }

        return '';
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
     * @return \SilverStripe\ORM\ArrayList( ArrayData(Name, Link, SelectKey, Current (boolean), LinkingMode))
     */
    public function GroupFilterLinks(): ArrayList
    {
        return $this->getUserPreferencesClass()->getLinksPerType('GROUPFILTER');
    }

    /**
     * Provides a ArrayList of links for filters products.
     *
     * @return \SilverStripe\ORM\ArrayList( ArrayData(Name, Link, SelectKey, Current (boolean), LinkingMode))
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

    /**
     * Link to the search results.
     *
     * @return string
     */
    public function SearchResultLink(): string
    {
        return $this->Link('searchresults');
    }

    public function searchResultsProductGroupsArrayFromSession(): array
    {
        return $this->ProductSearchForm()->getProductGroupIds();
    }

    /**
     * After a search is conducted you may end up with a bunch
     * of recommended product groups. They will be returned here...
     * We sort the list in the order that it is provided.
     *
     * @return \SilverStripe\ORM\DataList | Null (ProductGroups)
     */
    public function SearchResultsChildGroups(): ?DataList
    {
        $groupArray = $this->searchResultsProductGroupsArrayFromSession();
        if (! empty($groupArray)) {
            $sortStatement = ArrayMethods::create_sort_statement_from_id_array($groupArray, ProductGroup::class);

            return ProductGroup::get()
                ->filter(['ID' => $groupArray, 'ShowInSearch' => 1])
                ->sort($sortStatement);
        }

        return null;
    }

    /**
     * returns a search form to search current products.
     * @param bool $forceInit optional - force to be reinitialised.
     * @return ProductSearchForm object
     */
    public function ProductSearchForm(?bool $forceInit = false)
    {
        if ($this->searchForm === null || $forceInit) {
            $onlySearchTitle = $this->originalTitle;
            if (ClassHelpers::check_for_instance_of($this->dataRecord, ProductGroupSearchPage::class, false)) {
                if ($this->HasSearchResults()) {
                    $onlySearchTitle = 'Last Search Results';
                }
            }
            $this->searchForm = ProductSearchForm::create(
                $this,
                'ProductSearchForm',
                $onlySearchTitle,
                $this->getProductList()
            );
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
     * @todo: to cleanup
     * @return bool
     */
    public function HasSearchResults(): bool
    {
        $resultArray = $this->searchResultsArrayFromSession();
        if (! empty($resultArray)) {
            $count = count($resultArray) - 1;

            return $count ? true : false;
        }

        return false;
    }

    /**
     * Should the product search form be shown immediately?
     *
     * @return bool
     */
    public function ShowSearchFormImmediately(): bool
    {
        if ($this->ShowSearchFormAtAll()) {
            if ($this->IsSearchResults()) {
                return true;
            }

            if (! $this->products || ($this->products && $this->products->count())) {
                return false;
            }

            return true;
        }
        return false;
    }

    /**
     * Show a search form on this page?
     *
     * @return bool
     */
    public function ShowSearchFormAtAll(): bool
    {
        return true;
    }

    /**
     * Is the current page a display of search results.
     *
     * This does not mean that something is actively being search for,
     * it could also be just "showing the search results"
     *
     * @return bool
     */
    public function IsSearchResults(): bool
    {
        return $this->isSearchResults;
    }

    /**
     * Is there something actively being searched for?
     *
     * This is different from IsSearchResults.
     *
     * @return bool
     */
    public function ActiveSearchTerm(): bool
    {
        return $this->request->getVar('Keyword') || $this->request->getVar('searchcode') ? true : false;
    }

    public function saveUserPreferences(?array $data = [])
    {
        return $this->getUserPreferencesClass()->saveUserPreferences($data);
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
     * @param array|string $extraFilter          OPTIONAL Additional SQL filters to apply to the Product retrieval
     * @param array|string $alternativeSort      OPTIONAL Additional SQL for sorting
     *
     * @return FinalProductList
     */
    public function getFinalProductList($extraFilter = null, $alternativeSort = null)
    {
        if ($this->finalProductList === null) {
            $className = $this->getTemplateForProductsAndGroups()->getFinalProductListClassName();
            $this->finalProductList = $className::inst($this, $this->dataRecord);
            ClassHelpers::check_for_instance_of($this->finalProductList, FinalProductList::class, true);
        }
        return $this->finalProductList;
    }

    protected function getCachedProductList(): ? DataList
    {
        $key = $this->ProductGroupListCachingKey(false);
        if (EcommerceCache::inst()->hasCache($key)) {
            $ids = EcommerceCache::inst()->retrieve($key);
            $ids = ArrayMethods::filter_array($ids);
            return Product::get()
                ->filter(['ID' => $ids])
                ->sort(ArrayMethods::create_sort_statement_from_id_array($ids, Product::class));
        }

        return null;
    }

    protected function setCachedProductList($productList)
    {
        $key = $this->ProductGroupListCachingKey(false);
        $ids = ArrayMethods::filter_array($productList->columnUnique());
        EcommerceCache::inst()->save($key, $ids);
    }

    /**
     * returns the current page with get variables. If a type is specified then
     * instead of the value for that type, we add: '[[INSERT_HERE]]'
     * @return string   OPTIONAL: action - e.g. searchresults
     * @param  string $action     OPTIONAL: FILTER|SORT|DISPLAY
     */
    protected function getLinkTemplate(?string $action = null): string
    {
        return $this->getUserPreferencesClass()->getLinkTemplate($action);
    }

    protected function init()
    {
        parent::init();
        if ($this->request->getVar('reload')) {
            return $this->redirect($this->Link());
        }
        $this->originalTitle = $this->Title;
        Requirements::themedCSS('client/css/ProductGroup');
        Requirements::themedCSS('client/css/ProductGroupPopUp');
        Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomProducts.js');
        //we save data from get variables...
        $this->saveUserPreferences();
        if ($this->request->getVar('showdebug') && (Permission::check('ADMIN') || Director::isDev())) {
            $this->getTemplateForProductsAndGroups()->getDebugProviderAsObject($this, $this->dataRecord)->print();
            die();
        }
        //makes sure best match only applies to search -i.e. reset otherwise.
    }

    protected function getSearchResultsDefaultSort($idArray, $alternativeSort = null)
    {
        return $this->getUserPreferencesClass()->getSearchResultsDefaultSort($idArray, $alternativeSort);
    }

    /**
     * Overload this function of ProductGroup Extensions.
     *
     * @return bool
     */
    protected function returnAjaxifiedProductList(): bool
    {
        return Director::is_ajax() ? true : false;
    }

    /**
     * Overload this function of ProductGroup Extensions.
     *
     * @return bool
     */
    protected function productListsHTMLCanBeCached(): bool
    {
        //todo: FIX!
        return EcommerceConfig::inst()->OnlyShowProductsThatCanBePurchased ? false : true;
    }

    /**
     * turns full list into paginated list.
     *
     * @param SS_List $list
     *
     * @return PaginatedList|null
     */
    protected function paginateList($list): ?PaginatedList
    {
        $obj = null;
        if ($list && $list->count()) {
            $obj = PaginatedList::create($list, $this->request);
            if ($this->IsShowFullList()) {
                $obj->setPageLength(EcommerceConfig::get('ProductGroup', 'maximum_number_of_products_to_list') + 1);
            } else {
                $obj->setPageLength($this->getProductsPerPage());
            }
        }
        return $obj;
    }

    protected function IsShowFullList(): bool
    {
        return $this->getUserPreferencesClass()->IsShowFullList();
    }

    protected function defaultReturn()
    {
        if ($this->returnAjaxifiedProductList()) {
            return $this->renderWith('Sunnysideup\Ecommerce\Includes\AjaxProductList');
        }

        return [];
    }

    protected function getUserPreferencesClass()
    {
        if ($this->userPreferencesObject === null) {
            $className = $this->getTemplateForProductsAndGroups()->getUserPreferencesClassName();
            $this->userPreferencesObject = Injector::inst()->get($className)
                ->setRootGroup($this->dataRecord)
                ->setRootGroupController($this)
                ->setRequest($this->getRequest());
        }

        return $this->userPreferencesObject;
    }

    protected function addSecondaryTitle(?string $secondaryTitle = '')
    {
        $this->getUserPreferencesClass()->addSecondaryTitle($secondaryTitle);
    }


    public function DebugMe(string $method)
    {
        if (Vardump::inst()->isSafe()) {
            return Vardump::inst()->vardumpMe($this->{$method}(), $method, get_called_class());
        }
    }
}
