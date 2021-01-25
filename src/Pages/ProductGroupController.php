<?php

namespace Sunnysideup\Ecommerce\Pages;

use PageController;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\EcommerceCache;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\ProductSearchForm;

use Sunnysideup\Ecommerce\ProductsAndGroups\Applyers\ProductFilter;

class ProductGroupController extends PageController
{
    protected $searchForm = null;

    /**
     * The original Title of this page before filters, etc...
     *
     * @var string
     */
    protected $originalTitle = '';

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

    protected $secondaryTitleHasBeenAdded = false;

    protected $userPreferencesObject = null;

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
        $otherProductGroup = ProductFilter::get_group_from_url_segment($request->param('ID'));
        if ($otherProductGroup) {
            $this->saveUserPreferences(
                [
                    'FILTER' => [
                        'type' => 'filterforgroup',
                        'value' =>  $otherProductGroup->URLSegment . ',' . $otherProductGroup->ID
                    ]
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
     * Return the products for this group.
     *
     * This is the call that is made from the template and has the actual final
     * products being shown.
     *
     * @return \SilverStripe\ORM\PaginatedList
     */
    public function Products(): PaginatedList
    {
        $this->addSecondaryTitle();
        $list = $this->getCachedProductList();
        if (! $list) {
            $list = $this->getFinalProductList()->getProducts();
            EcommerceCache::inst()->save($this->ProductGroupListCachingKey(), $list->column('ID'));
        }
        $this->getFinalProductList()
            ->applyFilter($this->getCurrentUserPreferences('FILTER'))
            ->applySorter($this->getCurrentUserPreferences('SORT'))
            ->applyDisplayer($this->getCurrentUserPreferences('DISPLAY'));

        $this->cachingRelatedJavascript();

        return $this->paginateList($list);
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
    public function MenuChildGroups(): ?DataList
    {
        return $this->ChildGroups(
            2,
            ['ShowInMenus' => 1]
        );
    }

    public function ShowFiltersLinks(): bool
    {
        return $this->getProductList()->CountGreaterThanOne(3) && $this->HasFilters() ? true : false;
    }

    public function ShowSortLinks(): bool
    {
        return $this->getProductList()->CountGreaterThanOne(3) && $this->HasSorts() ? true : false;
    }

    public function ShowDisplayLinks(): bool
    {
        return $this->getProductList()->CountGreaterThanOne(3) && $this->HasDisplays() ? true : false;
    }

    public function HasFilter(): bool
    {
        return $this->getCurrentUserPreferences('FILTER') !== $this->getListConfigCalculated('FILTER');
    }

    public function HasSort(): bool
    {
        return $this->getCurrentUserPreferences('SORT') !== $this->getListConfigCalculated('SORT');
    }

    public function HasDisplay(): bool
    {
        return $this->getCurrentUserPreferences('DISPLAY') !== $this->getListConfigCalculated('DISPLAY');
    }

    public function HasFilterOrSort(): bool
    {
        return $this->HasFilter() || $this->HasSort();
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
        return $this->SorterLinks()->count() > 1;
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

    public function getCurrentPageNumber(): int
    {
        if ($pageStart = intval($this->request->getVar('start'))) {
            return ($pageStart / $this->getProductsPerPage()) + 1;
        }

        return 1;
    }

    public function getUserPreferencesTitle(string $type, $value): string
    {
        return $this->getFinalProductList()->getUserPreferencesTitle($type, $value);
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
            return $this->getUserPreferencesTitle('FILTER', $this->getCurrentUserPreferences('FILTER'));
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
            return $this->getUserPreferencesTitle('SORT', $this->getCurrentUserPreferences('SORT'));
        }

        return '';
    }

    /**
     * @return string
     */
    public function getCurrentDisplayTitle(): string
    {
        if ($this->HasDisplay()) {
            return $this->getUserPreferencesTitle('DISPLAY', $this->getCurrentUserPreferences('DISPLAY'));
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
     * Number of entries per page limited by total number of pages available...
     *
     * @return int
     */
    public function MaxNumberOfProductsPerPage(): int
    {
        $perPage = $this->getNumberOfProductsPerPage();
        $total = $this->getFinalProductList()->getRawCount();

        return $perPage > $total ? $total : $perPage;
    }

    /**
     * Provides a ArrayList of links for filters products.
     *
     * @return \SilverStripe\ORM\ArrayList( ArrayData(Name, Link, SelectKey, Current (boolean), LinkingMode))
     */
    public function FilterLinks(): ArrayList
    {
        return $this->getFinalProductList()
            ->getDefaultFilterList(
                $this->getLinkTemplate('FILTER'),
                $this->getCurrentUserPreferences('FILTER'),
                true
            );
    }

    /**
     * Provides a ArrayList of links for sorting products.
     */
    public function SortLinks(): ArrayList
    {
        return $this->getFinalProductList()
            ->getDefaultFilterList(
                $this->getLinkTemplate('SORT'),
                $this->getCurrentUserPreferences('SORT'),
                true
            );
    }

    /**
     * Provides a ArrayList for displaying display links.
     */
    public function DisplayLinks(): ArrayList
    {
        return $this->getFinalProductList()
            ->getDefaultFilterList(
                $this->getLinkTemplate('DISPLAY'),
                $this->getCurrentUserPreferences('DISPLAY'),
                false
            );
    }

    public function getLink($action = null): string
    {
        return $this->Link($action);
    }

    public function Link($action = null): string
    {
        return $this->getLinkTemplate('', $action);
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
            if ($this->dataRecord instanceof ProductGroupSearchPage) {
                if ($this->HasSearchResults()) {
                    $onlySearchTitle = 'Last Search Results';
                }
            }
            $defaultKey = $this->getListConfigCalculated('FILTER');
            $this->searchForm = ProductSearchForm::create(
                $this,
                'ProductSearchForm',
                $onlySearchTitle,
                $this->getProductList(null, $defaultKey)
            );
            // $sortGetVariable = $this->getSortFilterDisplayNames('SORT', 'getVariable');
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

    public function saveUserPreferences($data)
    {
        return $this->getUserPreferencesClass()->saveUserPreferences($data);
    }

    public function getCurrentUserPreferences()
    {
        return $this->getUserPreferencesClass()->getCurrentUserPreferences();
    }

    protected function getCachedProductList(): ? DataList
    {
        $key = $this->ProductGroupListCachingKey(false);
        if (EcommerceCache::inst()->hasCache($key)) {
            $ids = EcommerceCache::inst()->retrieve($key);
            return Product::get()
                ->filter(['ID' => $ids])
                ->sort(ArrayMethods::create_sort_statement_from_id_array($ids, Product::class));
        }

        return null;
    }

    /**
     * returns the current page with get variables. If a type is specified then
     * instead of the value for that type, we add: '[[INSERT_HERE]]'
     * @param  string $type    OPTIONAL: FILTER|SORT|DISPLAY
     * @return string  OPTIONAL: action - e.g. searchresults
     */
    protected function getLinkTemplate(?string $type = '', ?string $action = null): string
    {
        return $this->getUserPreferencesClass()->getLinkTemplate($type, $action);
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
        return EcommerceConfig::inst()->OnlyShowProductsThatCanBePurchased ? false : true;
    }

    /**
     * turns full list into paginated list.
     *
     * @param SS_List $list
     *
     * @return PaginatedList
     */
    protected function paginateList($list): ?PaginatedList
    {
        if ($list && $list->count()) {
            $obj = PaginatedList::create($list, $this->request);
            if ($this->IsShowFullList()) {
                $obj->setPageLength(EcommerceConfig::get('ProductGroup', 'maximum_number_of_products_to_list') + 1);
            } else {
                $obj->setPageLength($this->MyNumberOfProductsPerPage());
            }
            return $obj;
        }
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
                ->setController($this)
                ->setDataErecord($this->dataRecord)
                ->setRequest($this->getRequest());
        }

        return $this->userPreferencesObject;
    }
}
