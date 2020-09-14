<?php

namespace Sunnysideup\Ecommerce\Pages;

use PageController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\SS_List;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\ProductSearchForm;
use Sunnysideup\Ecommerce\Tasks\EcommerceTaskDebugCart;

class ProductGroupController extends PageController
{
    /**
     * The original Title of this page before filters, etc...
     *
     * @var string
     */
    protected $originalTitle = '';

    /**
     * list of products that are going to be shown.
     *
     * @var DataList
     */
    protected $products = null;

    /**
     * Show all products on one page?
     *
     * @var bool
     */
    protected $showFullList = false;

    /**
     * The group filter that is applied to this page.
     *
     * @var ProductGroup
     */
    protected $filterForGroupObject = null;

    /**
     * Is this a product search?
     *
     * @var bool
     */
    protected $isSearchResults = false;

    /****************************************************
     *  INTERNAL PROCESSING: TITLES
    /****************************************************/

    /**
     * variable to make sure secondary title only gets
     * added once.
     *
     * @var bool
     */
    protected $secondaryTitleHasBeenAdded = false;

    /****************************************************
     *  Search Form Related controllers
    /****************************************************/

    protected $searchForm = null;

    protected $searchKeyword = '';

    private static $product_search_session_variable = 'EcomProductSearch';

    /**
     * standard SS variable.
     *
     * @var array
     */
    private static $allowed_actions = [
        'debug' => 'ADMIN',
        'filterforgroup' => true,
        'ProductSearchForm' => true,
        'searchresults' => true,
        'resetfilter' => true,
    ];

    /****************************************************
     *  ACTIONS
    /****************************************************/

    /**
     * standard selection of products.
     */
    public function index()
    {
        //set the filter and the sort...
        $this->addSecondaryTitle();
        $this->products = $this->paginateList($this->ProductsShowable(null));
        if ($this->returnAjaxifiedProductList()) {
            return $this->RenderWith('Sunnysideup\Ecommerce\Includes\AjaxProductList');
        }
        return [];
    }

    /**
     * cross filter with another product group..
     *
     * e.g. socks (current product group) for brand A or B (the secondary product group)
     *
     * @return \SilverStripe\Control\HTTPRequest
     */
    public function filterforgroup($request)
    {
        $this->resetfilter();
        $otherGroupURLSegment = Convert::raw2sql($request->param('ID'));
        $arrayOfIDs = [0 => 0];
        if ($otherGroupURLSegment) {
            $otherProductGroup = DataObject::get_one(
                ProductGroup::class,
                ['URLSegment' => $otherGroupURLSegment]
            );
            if ($otherProductGroup) {
                $this->filterForGroupObject = $otherProductGroup;
                $arrayOfIDs = $otherProductGroup->currentInitialProductsAsCachedArray($this->getMyUserPreferencesDefault('FILTER'));
            }
        }
        $this->addSecondaryTitle();
        $this->products = $this->paginateList($this->ProductsShowable(['ID' => $arrayOfIDs]));
        if ($this->returnAjaxifiedProductList()) {
            return $this->RenderWith('Sunnysideup\Ecommerce\Includes\AjaxProductList');
        }

        return [];
    }

    /**
     * name for session variable where we store the last search results for this page.
     * @return string
     */
    public function SearchResultsSessionVariable(): string
    {
        $idString = '_' . $this->ID;

        return $this->Config()->get('product_search_session_variable') . $idString;
    }

    /**
     * @return array
     */
    public function searchResultsArrayFromSession(): array
    {
        return $this->ProductSearchForm()->getProductIds();
    }

    /**
     * @return array
     */
    public function searchResultsProductGroupsArrayFromSession(): array
    {
        return $this->ProductSearchForm()->getProductGroupIds();
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
        if ($this->searchHash) {
            $this->getRequest()->getSession()->set(
                $this->SearchResultsSessionVariable(),
                $this->searchHash
            );
        }
        //get results array
        $keyword = $this->ProductSearchForm()->getSearchPhrase();
        if ($title) {
            $title = _t('Ecommerce.SEARCH_FOR', 'search for: ') . substr($keyword, 0, 25);
        }
        //filters are irrelevant right now
        $this->resetfilter();
        $this->addSecondaryTitle($title);
        $this->products = $this->paginateList(
            $this->ProductsShowable(
                ['ID' => $resultArray],
                $this->getSearchResultsDefaultSort($this->searchResultsArrayFromSession())
            )
        );

        return [];
    }

    /**
     * resets the filter only.
     */
    public function resetfilter()
    {
        $defaultKey = $this->getMyUserPreferencesDefault('FILTER');
        $filterGetVariable = $this->getSortFilterDisplayNames('FILTER', 'getVariable');
        $this->saveUserPreferences(
            [
                $filterGetVariable => $defaultKey,
            ]
        );

        return [];
    }

    /**
     * resets the filter only.
     */
    public function resetsort()
    {
        $defaultKey = $this->getMyUserPreferencesDefault('SORT');
        $sortGetVariable = $this->getSortFilterDisplayNames('SORT', 'getVariable');
        $this->saveUserPreferences(
            [
                $sortGetVariable => $defaultKey,
            ]
        );

        return [];
    }

    /****************************************************
     *  TEMPLATE METHODS PRODUCTS
    /****************************************************/

    /**
     * Return the products for this group.
     * This is the call that is made from the template...
     * The actual final products being shown.
     *
     * @return \SilverStripe\ORM\DataList
     **/
    public function Products()
    {
        //IMPORTANT!
        //two universal actions!
        $this->addSecondaryTitle();
        $this->cachingRelatedJavascript();

        //save products to session for later use
        $stringOfIDs = '';
        $array = $this->getProductsThatCanBePurchasedArray();
        if (is_array($array)) {
            $stringOfIDs = implode(',', $array);
        }
        //save list for future use

        $this->getRequest()->getSession()->set(EcommerceConfig::get(ProductGroup::class, 'session_name_for_product_array'), $stringOfIDs);

        return $this->products;
    }

    /**
     * is the product list cache-able?
     *
     * @return bool
     */
    public function ProductGroupListAreCacheable()
    {
        if ($this->productListsHTMLCanBeCached()) {
            //exception 1
            if ($this->IsSearchResults()) {
                return false;
            }
            //exception 2
            $currentOrder = ShoppingCart::current_order();
            if ($currentOrder->getHasAlternativeCurrency()) {
                return false;
            }
            //can be cached...
            return true;
        }

        return false;
    }

    /**
     * is the product list ajaxified.
     *
     * @return bool
     */
    public function ProductGroupListAreAjaxified()
    {
        return $this->IsSearchResults() ? false : true;
    }

    /**
     * Unique caching key for the product list...
     *
     * @return string | Null
     */
    public function ProductGroupListCachingKey()
    {
        if ($this->ProductGroupListAreCacheable()) {
            $displayKey = $this->getCurrentUserPreferences('DISPLAY');
            $filterKey = $this->getCurrentUserPreferences('FILTER');
            $filterForGroupKey = $this->filterForGroupObject ? $this->filterForGroupObject->ID : 0;
            $sortKey = $this->getCurrentUserPreferences('SORT');
            $pageStart = $this->request->getVar('start') ? intval($this->request->getVar('start')) : 0;
            $isFullList = $this->IsShowFullList() ? 'Y' : 'N';
            return $this->cacheKey(
                implode(
                    '_',
                    [
                        $displayKey,
                        $filterKey,
                        $filterForGroupKey,
                        $sortKey,
                        $pageStart,
                        $isFullList,
                    ]
                )
            );
        }

        return;
    }

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

    /*****************************************************
     * DATALIST: totals, number per page, etc..
     *****************************************************/

    /**
     * returns the total numer of products (before pagination).
     *
     * @return bool
     **/
    public function TotalCountGreaterThanOne($greaterThan = 1)
    {
        return $this->TotalCount() > $greaterThan;
    }

    /**
     * have the ProductsShowable been limited.
     *
     * @return bool
     **/
    public function TotalCountGreaterThanMax()
    {
        return $this->RawCount() > $this->TotalCount();
    }

    /****************************************************
     *  TEMPLATE METHODS MENUS AND SIDEBARS
    /****************************************************/

    /**
     * title without additions.
     *
     * @return string
     */
    public function OriginalTitle()
    {
        return $this->originalTitle;
    }

    /**
     * This method can be extended to show products in the side bar.
     */
    public function SidebarProducts()
    {
        return;
    }

    /**
     * returns child product groups for use in
     * 'in this section'. For example the vegetable Product Group
     * May have listed here: Carrot, Cabbage, etc...
     *
     * @return \SilverStripe\ORM\ArrayList (ProductGroups)
     */
    public function MenuChildGroups()
    {
        return $this->ChildGroups(2, '"ShowInMenus" = 1');
    }

    /**
     * After a search is conducted you may end up with a bunch
     * of recommended product groups. They will be returned here...
     * We sort the list in the order that it is provided.
     *
     * @return \SilverStripe\ORM\DataList | Null (ProductGroups)
     */
    public function SearchResultsChildGroups()
    {
        $groupArray = $this->searchResultsProductGroupsArrayFromSession();
        if (! empty($groupArray)) {
            $sortStatement = $this->createSortStatementFromIDArray($groupArray, ProductGroup::class);

            return ProductGroup::get()
                ->filter(['ID' => $groupArray, 'ShowInSearch' => 1])
                ->sort($sortStatement);
        }

        return;
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
                $form = ProductSearchForm::create(
                    $this,
                    'ProductSearchForm',
                );
            }
            // $sortGetVariable = $this->getSortFilterDisplayNames('SORT', 'getVariable');
            // $additionalGetParameters = $sortGetVariable . '=' . Config::inst()->get(ProductGroupSearchPage::class, 'best_match_key');
            $form->setAdditionalGetParameters($additionalGetParameters);
            $form->setSearchHash($this->searchKeyword);
        }

        return $this->searchForm;
    }

    /**
     * Does this page have any search results?
     * If search was carried out without returns
     * then it returns zero (false).
     *
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
    public function ShowSearchFormImmediately()
    {
        if ($this->IsSearchResults()) {
            return true;
        }
        if (! $this->products || ($this->products && $this->products->count())) {
            return false;
        }

        return true;
    }

    /**
     * Show a search form on this page?
     *
     * @return bool
     */
    public function ShowSearchFormAtAll()
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
    public function IsSearchResults()
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

    /****************************************************
     *  Filter / Sort / Display related controllers
    /****************************************************/

    /**
     * Do we show all products on one page?
     *
     * @return bool
     */
    public function ShowFiltersAndDisplayLinks()
    {
        if ($this->TotalCountGreaterThanOne()) {
            if ($this->HasFilters()) {
                return true;
            }
            if ($this->DisplayLinks()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Do we show the sort links.
     *
     * A bit arbitrary to say three,
     * but there is not much point to sort three or less products
     *
     * @return bool
     */
    public function ShowSortLinks($minimumCount = 3)
    {
        if ($this->TotalCountGreaterThanOne($minimumCount)) {
            return true;
        }

        return false;
    }

    /**
     * Is there a special filter operating at the moment?
     * Is the current filter the default one (return inverse!)?
     *
     * @return bool
     */
    public function HasFilter()
    {
        return $this->getCurrentUserPreferences('FILTER') !== $this->getMyUserPreferencesDefault('FILTER')
            ||
            $this->filterForGroupObject;
    }

    /**
     * Is there a special sort operating at the moment?
     * Is the current sort the default one (return inverse!)?
     *
     * @return bool
     */
    public function HasSort()
    {
        $sort = $this->getCurrentUserPreferences('SORT');
        if ($sort !== $this->getMyUserPreferencesDefault('SORT')) {
            return true;
        }
    }

    /**
     * @return bool
     */
    public function HasFilterOrSort()
    {
        return $this->HasFilter() || $this->HasSort();
    }

    /**
     * @return bool
     */
    public function HasFilterOrSortFullList()
    {
        return $this->HasFilterOrSort() || $this->IsShowFullList();
    }

    /**
     * are filters available?
     * we check one at the time so that we do the least
     * amount of DB queries.
     *
     * @return bool
     */
    public function HasFilters()
    {
        $countFilters = $this->FilterLinks()->count();
        if ($countFilters > 1) {
            return true;
        }
        $countGroupFilters = $this->ProductGroupFilterLinks()->count();
        if ($countGroupFilters > 1) {
            return true;
        }
        if ($countFilters + $countGroupFilters > 1) {
            return true;
        }

        return false;
    }

    /**
     * Do we show all products on one page?
     *
     * @return bool
     */
    public function IsShowFullList()
    {
        return $this->showFullList;
    }

    /**
     * returns the current filter applied to the list
     * in a human readable string.
     *
     * @return string
     */
    public function CurrentDisplayTitle()
    {
        $displayKey = $this->getCurrentUserPreferences('DISPLAY');
        if ($displayKey !== $this->getMyUserPreferencesDefault('DISPLAY')) {
            return $this->getUserPreferencesTitle('DISPLAY', $displayKey);
        }
    }

    /**
     * returns the current filter applied to the list
     * in a human readable string.
     *
     * @return string
     */
    public function CurrentFilterTitle()
    {
        $filterKey = $this->getCurrentUserPreferences('FILTER');
        $filters = [];
        if ($filterKey !== $this->getMyUserPreferencesDefault('FILTER')) {
            $filters[] = $this->getUserPreferencesTitle('FILTER', $filterKey);
        }
        if ($this->filterForGroupObject) {
            $filters[] = $this->filterForGroupObject->MenuTitle;
        }
        if (count($filters)) {
            return implode(', ', $filters);
        }
    }

    /**
     * returns the current sort applied to the list
     * in a human readable string.
     *
     * @return string
     */
    public function CurrentSortTitle()
    {
        $sortKey = $this->getCurrentUserPreferences('SORT');
        if ($sortKey !== $this->getMyUserPreferencesDefault('SORT')) {
            return $this->getUserPreferencesTitle('SORT', $sortKey);
        }
    }

    /**
     * short-cut for getMyUserPreferencesDefault("DISPLAY")
     * for use in templtes.
     *
     * @return string - key
     */
    public function MyDefaultDisplayStyle()
    {
        return $this->getMyUserPreferencesDefault('DISPLAY');
    }

    /**
     * Number of entries per page limited by total number of pages available...
     *
     * @return int
     */
    public function MaxNumberOfProductsPerPage()
    {
        return $this->MyNumberOfProductsPerPage() > $this->TotalCount() ? $this->TotalCount() : $this->MyNumberOfProductsPerPage();
    }

    /****************************************************
     *  TEMPLATE METHODS FILTER LINK
    /****************************************************/

    /**
     * Provides a ArrayList of links for filters products.
     *
     * @return \SilverStripe\ORM\ArrayList( ArrayData(Name, Link, SelectKey, Current (boolean), LinkingMode))
     */
    public function FilterLinks()
    {
        $cacheKey = 'FilterLinks_' . ($this->filterForGroupObject ? $this->filterForGroupObject->ID : 0);
        if ($list = $this->retrieveObjectStore($cacheKey)) {
            //do nothing
        } else {
            $list = $this->userPreferencesLinks('FILTER');
            foreach ($list as $obj) {
                $key = $obj->SelectKey;
                if ($key !== $this->getMyUserPreferencesDefault('FILTER')) {
                    $count = count($this->currentInitialProductsAsCachedArray($key));
                    if ($count === 0) {
                        $list->remove($obj);
                    } else {
                        $obj->Count = $count;
                    }
                }
            }
            $this->saveObjectStore($list, $cacheKey);
        }
        $selectedItem = $this->getCurrentUserPreferences('FILTER');
        foreach ($list as $obj) {
            $canHaveCurrent = true;
            if ($this->filterForGroupObject) {
                $canHaveCurrent = false;
            }
            $obj->Current = $selectedItem === $obj->SelectKey && $canHaveCurrent ? true : false;
            $obj->LinkingMode = $obj->Current ? 'current' : 'link';
            $obj->Ajaxify = true;
        }

        return $list;
    }

    /**
     * returns a list of items (with links).
     *
     * @return \SilverStripe\ORM\ArrayList( ArrayData(Name, FilterLink,  SelectKey, Current (boolean), LinkingMode))
     */
    public function ProductGroupFilterLinks()
    {
        if ($array = $this->retrieveObjectStore('ProductGroupFilterLinks')) {
            //do nothing
        } else {
            $arrayOfItems = [];

            $baseArray = $this->currentInitialProductsAsCachedArray($this->getMyUserPreferencesDefault('FILTER'));

            //also show
            $items = $this->ProductGroupsFromAlsoShowProducts();
            $arrayOfItems = array_merge($arrayOfItems, $this->productGroupFilterLinksCount($items, $baseArray, true));
            //also show inverse
            $items = $this->ProductGroupsFromAlsoShowProductsInverse();
            $arrayOfItems = array_merge($arrayOfItems, $this->productGroupFilterLinksCount($items, $baseArray, true));

            //parent groups
            $items = $this->ProductGroupsParentGroups();
            $arrayOfItems = array_merge($arrayOfItems, $this->productGroupFilterLinksCount($items, $baseArray, true));

            //child groups
            $items = $this->MenuChildGroups();
            $arrayOfItems = array_merge($arrayOfItems, $this->productGroupFilterLinksCount($items, $baseArray, true));

            ksort($arrayOfItems);
            $array = [];
            foreach ($arrayOfItems as $arrayOfItem) {
                $array[] = $this->makeArrayItem($arrayOfItem);
            }
            $this->saveObjectStore($array, 'ProductGroupFilterLinks');
        }
        $arrayList = ArrayList::create();
        foreach ($array as $item) {
            $arrayList->push(ArrayData::create($item));
        }

        return $arrayList;
    }

    /**
     * @see ProductGroupFilterLinks
     * same as ProductGroupFilterLinks, but with originating Object...
     *
     * @return \SilverStripe\ORM\ArrayList
     */
    public function ProductGroupFilterOriginalObjects()
    {
        $links = $this->ProductGroupFilterLinks();
        // /print_r($links);
        foreach ($links as $linkItem) {
            $className = $linkItem->ClassName;
            $id = $linkItem->ID;
            if ($className && $id) {
                $object = $className::get()->byID($id);
                $linkItem->Object = $object;
            }
        }

        return $links;
    }

    /**
     * Provides a ArrayList of links for sorting products.
     */
    public function SortLinks()
    {
        $list = $this->userPreferencesLinks('SORT');
        $selectedItem = $this->getCurrentUserPreferences('SORT');
        if ($list) {
            foreach ($list as $obj) {
                $obj->Current = $selectedItem === $obj->SelectKey ? true : false;
                $obj->LinkingMode = $obj->Current ? 'current' : 'link';
                $obj->Ajaxify = true;
            }

            return $list;
        }
    }

    /**
     * Provides a ArrayList for displaying display links.
     */
    public function DisplayLinks()
    {
        $list = $this->userPreferencesLinks('DISPLAY');
        $selectedItem = $this->getCurrentUserPreferences('DISPLAY');
        if ($list) {
            foreach ($list as $obj) {
                $obj->Current = $selectedItem === $obj->SelectKey ? true : false;
                $obj->LinkingMode = $obj->Current ? 'current' : 'link';
                $obj->Ajaxify = true;
            }

            return $list;
        }
    }

    /**
     * The link that Google et al. need to index.
     * @return string
     */
    public function CanonicalLink()
    {
        $link = $this->ListAllLink();
        $this->extend('UpdateCanonicalLink', $link);

        return $link;
    }

    /**
     * Link that returns a list of all the products
     * for this product group as a simple list.
     *
     * @return string
     */
    public function ListAllLink()
    {
        if ($this->filterForGroupObject) {
            return $this->Link('filterforgroup/' . $this->filterForGroupObject->URLSegment) . '?showfulllist=1';
        }
        return $this->Link() . '?showfulllist=1';
    }

    /**
     * Link that returns a list of all the products
     * for this product group as a simple list.
     *
     * @return string
     */
    public function ListAFewLink()
    {
        return str_replace('?showfulllist=1', '', $this->ListAllLink());
    }

    /**
     * Link that returns a list of all the products
     * for this product group as a simple list.
     *
     * It resets everything - not just filter....
     *
     * @return string
     */
    public function ResetPreferencesLink($escapedAmpersands = true)
    {
        $ampersand = '&';
        if ($escapedAmpersands) {
            $ampersand = '&amp;';
        }
        $getVariableNameFilter = $this->getSortFilterDisplayNames('FILTER', 'getVariable');
        $getVariableNameSort = $this->getSortFilterDisplayNames('SORT', 'getVariable');

        return $this->Link() . '?' .
            $getVariableNameFilter . '=' . $this->getMyUserPreferencesDefault('FILTER') . $ampersand .
            $getVariableNameSort . '=' . $this->getMyUserPreferencesDefault('SORT') . $ampersand .
            'reload=1';
    }

    /**
     * Link to the search results.
     *
     * @return string
     */
    public function SearchResultLink(): string
    {
        if ($this->HasSearchResults() && ! $this->isSearchResults) {
            return $this->Link('searchresults/' . $this->lastSearchHash());
        }
        return '';
    }

    /****************************************************
     *  DEBUG
    /****************************************************/

    public function debug()
    {
        $member = Member::currentUser();
        if (! $member || ! $member->IsShopAdmin()) {
            $messages = [
                'default' => 'You must login as an admin to use debug functions.',
            ];
            Security::permissionFailure($this, $messages);
        }
        $this->ProductsShowable();
        $html = EcommerceTaskDebugCart::debug_object($this->dataRecord);
        $html .= '<ul>';

        $html .= '<li><hr /><h3>Available options</h3><hr /></li>';
        $html .= '<li><b>Sort Options for Dropdown:</b><pre> ' . print_r($this->getUserPreferencesOptionsForDropdown('SORT'), 1) . '</pre> </li>';
        $html .= '<li><b>Filter Options for Dropdown:</b><pre> ' . print_r($this->getUserPreferencesOptionsForDropdown('FILTER'), 1) . '</pre></li>';
        $html .= '<li><b>Display Styles for Dropdown:</b><pre> ' . print_r($this->getUserPreferencesOptionsForDropdown('DISPLAY'), 1) . '</pre> </li>';

        $html .= '<li><hr /><h3>Selection Setting (what is set as default for this page)</h3><hr /></li>';
        $html .= '<li><b>MyDefaultFilter:</b> ' . $this->getMyUserPreferencesDefault('FILTER') . ' </li>';
        $html .= '<li><b>MyDefaultSortOrder:</b> ' . $this->getMyUserPreferencesDefault('SORT') . ' </li>';
        $html .= '<li><b>MyDefaultDisplayStyle:</b> ' . $this->getMyUserPreferencesDefault('DISPLAY') . ' </li>';
        $html .= '<li><b>MyNumberOfProductsPerPage:</b> ' . $this->MyNumberOfProductsPerPage() . ' </li>';
        $html .= '<li><b>MyLevelOfProductsToshow:</b> ' . $this->MyLevelOfProductsToShow() . ' = ' . (isset($this->showProductLevels[$this->MyLevelOfProductsToShow()]) ? $this->showProductLevels[$this->MyLevelOfProductsToShow()] : 'ERROR!!!! $this->showProductLevels not set for ' . $this->MyLevelOfProductsToShow()) . ' </li>';

        $html .= '<li><hr /><h3>Current Settings</h3><hr /></li>';
        $html .= '<li><b>Current Sort Order:</b> ' . $this->getCurrentUserPreferences('SORT') . ' </li>';
        $html .= '<li><b>Current Filter:</b> ' . $this->getCurrentUserPreferences('FILTER') . ' </li>';
        $html .= '<li><b>Current display style:</b> ' . $this->getCurrentUserPreferences('DISPLAY') . ' </li>';

        $html .= '<li><hr /><h3>DATALIST: totals, numbers per page etc</h3><hr /></li>';
        $html .= '<li><b>Total number of products:</b> ' . $this->TotalCount() . ' </li>';
        $html .= '<li><b>Is there more than one product:</b> ' . ($this->TotalCountGreaterThanOne() ? 'YES' : 'NO') . ' </li>';
        $html .= '<li><b>Number of products per page:</b> ' . $this->MyNumberOfProductsPerPage() . ' </li>';

        $html .= '<li><hr /><h3>SQL Factors</h3><hr /></li>';
        $html .= '<li><b>Default sort SQL:</b> ' . print_r($this->getUserSettingsOptionSQL('SORT'), 1) . ' </li>';
        $html .= '<li><b>User sort SQL:</b> ' . print_r($this->getUserSettingsOptionSQL('SORT', $this->getCurrentUserPreferences('SORT')), 1) . ' </li>';
        $html .= '<li><b>Default Filter SQL:</b> <pre>' . print_r($this->getUserSettingsOptionSQL('FILTER'), 1) . '</pre> </li>';
        $html .= '<li><b>User Filter SQL:</b> <pre>' . print_r($this->getUserSettingsOptionSQL('FILTER', $this->getCurrentUserPreferences('FILTER')), 1) . '</pre> </li>';
        $html .= '<li><b>Buyable Class name:</b> ' . $this->getBuyableClassName() . ' </li>';
        $html .= '<li><b>allProducts:</b> ' . print_r(str_replace('"', '`', $this->allProducts->sql()), 1) . ' </li>';

        $html .= '<li><hr /><h3>Search</h3><hr /></li>';
        $resultArray = $this->searchResultsArrayFromSession();
        $productGroupArray = $this->searchResultsProductGroupsArrayFromSession();
        $html .= '<li><b>Is Search Results:</b> ' . ($this->IsSearchResults() ? 'YES' : 'NO') . ' </li>';
        $html .= '<li><b>Products In Search:</b> ' . print_r($resultArray, 1) . ' </li>';
        $html .= '<li><b>Product Groups In Search:</b> ' . print_r($productGroupArray, 1) . ' </li>';

        $html .= '<li><hr /><h3>Other</h3><hr /></li>';
        if ($image = $this->BestAvailableImage()) {
            $html .= '<li><b>Best Available Image:</b> <img src="' . $image->Link . '" /> </li>';
        }
        $html .= '<li><b>BestAvailableImage:</b> ' . ($this->BestAvailableImage() ? $this->BestAvailableImage()->Link : 'no image available') . ' </li>';
        $html .= '<li><b>Is this an ecommerce page:</b> ' . ($this->IsEcommercePage() ? 'YES' : 'NO') . ' </li>';
        $html .= '<li><hr /><h3>Related Groups</h3><hr /></li>';
        $html .= '<li><b>Parent product group:</b> ' . ($this->ParentGroup() ? $this->ParentGroup()->Title : '[NO PARENT GROUP]') . '</li>';

        $childGroups = $this->ChildGroups(99);
        if ($childGroups->count()) {
            $childGroups = $childGroups->map('ID', 'MenuTitle');
            $html .= '<li><b>Child Groups (all):</b><pre> ' . print_r($childGroups, 1) . ' </pre></li>';
        } else {
            $html .= '<li><b>Child Groups (full tree): </b>NONE</li>';
        }
        $html .= '<li><b>a list of Product Groups that have the products for the CURRENT product group listed as part of their AlsoShowProducts list:</b><pre>' . print_r($this->ProductGroupsFromAlsoShowProducts()->map('ID', 'Title')->toArray(), 1) . ' </pre></li>';
        $html .= '<li><b>the inverse of ProductGroupsFromAlsoShowProducts:</b><pre> ' . print_r($this->ProductGroupsFromAlsoShowProductsInverse()->map('ID', 'Title')->toArray(), 1) . ' </pre></li>';
        $html .= '<li><b>all product parent groups:</b><pre> ' . print_r($this->ProductGroupsParentGroups()->map('ID', 'Title')->toArray(), 1) . ' </pre></li>';

        $html .= '<li><hr /><h3>Product Example and Links</h3><hr /></li>';
        $product = DataObject::get_one(
            Product::class,
            ['ParentID' => $this->ID]
        );
        if ($product) {
            $html .= '<li><b>Product View:</b> <a href="' . $product->Link() . '">' . $product->Title . '</a> </li>';
            $html .= '<li><b>Product Debug:</b> <a href="' . $product->Link('debug') . '">' . $product->Title . '</a> </li>';
            $html .= '<li><b>Product Admin Page:</b> <a href="' . '/admin/pages/edit/show/' . $product->ID . '">' . $product->Title . '</a> </li>';
            $html .= '<li><b>ProductGroup Admin Page:</b> <a href="' . '/admin/pages/edit/show/' . $this->ID . '">' . $this->Title . '</a> </li>';
        } else {
            $html .= '<li>this page has no products of its own</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    protected function lastSearchHash(): string
    {
        return (string) $this->getRequest()->getSession()->get($this->SearchResultsSessionVariable());
    }

    /**
     * standard SS method.
     */
    protected function init()
    {
        parent::init();
        $this->originalTitle = $this->Title;
        Requirements::themedCSS('ProductGroup');
        Requirements::themedCSS('ProductGroupPopUp');
        Requirements::javascript('sunnysideup/ecommerce: client/javascript/EcomProducts.js');
        //we save data from get variables...
        $this->saveUserPreferences();
        //makes sure best match only applies to search -i.e. reset otherwise.
        if ($this->request->param('Action') !== 'searchresults') {
            $sortKey = $this->getCurrentUserPreferences('SORT');
            if ($sortKey === Config::inst()->get(ProductGroupSearchPage::class, 'best_match_key')) {
                $this->resetsort();
            }
        }
    }

    protected function getSearchResultsDefaultSort($idArray, $alternativeSort = null)
    {
        if (! $alternativeSort) {
            $sortGetVariable = $this->getSortFilterDisplayNames('SORT', 'getVariable');
            if (! $this->request->getVar($sortGetVariable)) {
                $suggestion = Config::inst()->get(ProductGroupSearchPage::class, 'best_match_key');
                if ($suggestion) {
                    $this->saveUserPreferences(
                        [
                            $sortGetVariable => $suggestion,
                        ]
                    );
                    $alternativeSort = $this->createSortStatementFromIDArray($idArray);
                }
            }
        }
        return $alternativeSort;
    }

    /**
     * you can overload this function of ProductGroup Extensions.
     *
     * @return bool
     */
    protected function returnAjaxifiedProductList()
    {
        return Director::is_ajax() ? true : false;
    }

    /**
     * you can overload this function of ProductGroup Extensions.
     *
     * @return bool
     */
    protected function productListsHTMLCanBeCached()
    {
        return Config::inst()->get(ProductGroup::class, 'actively_check_for_can_purchase') ? false : true;
    }

    /**
     * counts the total number in the combination....
     *
     * @param \SilverStripe\ORM\DataList $items     - list of
     * @param array    $baseArray - list of products on the current page
     *
     * @return array
     */
    protected function productGroupFilterLinksCount($items, $baseArray, $ajaxify = true)
    {
        $array = [];
        if ($items && $items->count()) {
            foreach ($items as $item) {
                $arrayOfIDs = $item->currentInitialProductsAsCachedArray($this->getMyUserPreferencesDefault('FILTER'));
                $newArray = array_intersect_key(
                    $arrayOfIDs,
                    $baseArray
                );
                $count = count($newArray);
                if ($count) {
                    $array[$item->Title] = [
                        'Item' => $item,
                        'Count' => $count,
                        'Ajaxify' => $ajaxify,
                    ];
                }
            }
        }

        return $array;
    }

    /**
     * @param array $itemInArray (Item, Count, UserFilterAction)
     *
     * @return ArrayData
     */
    protected function makeArrayItem($itemInArray)
    {
        $item = $itemInArray['Item'];
        $count = $itemInArray['Count'];
        $ajaxify = $itemInArray['Ajaxify'];
        $filterForGroupObjectID = $this->filterForGroupObject ? $this->filterForGroupObject->ID : 0;
        $isCurrent = ($item->ID === $filterForGroupObjectID ? true : false);
        if ($ajaxify) {
            $link = $this->Link($item->FilterForGroupLinkSegment());
        } else {
            $link = $item->Link();
        }
        return [
            'ID' => $item->ID,
            'ClassName' => $item->ClassName,
            'Title' => $item->Title,
            'Count' => $count,
            'SelectKey' => $item->URLSegment,
            'Current' => $isCurrent ? true : false,
            'MyLinkingMode' => $isCurrent ? 'current' : 'link',
            'FilterLink' => $link,
            'Ajaxify' => $ajaxify ? true : false,
        ];
    }

    /****************************************************
     *  INTERNAL PROCESSING: PRODUCT LIST
    /****************************************************/

    /**
     * turns full list into paginated list.
     *
     * @param SS_List $list
     *
     * @return PaginatedList
     */
    protected function paginateList(SS_List $list)
    {
        if ($list && $list->count()) {
            if ($this->IsShowFullList()) {
                $obj = PaginatedList::create($list, $this->request);
                $obj->setPageLength(EcommerceConfig::get(ProductGroup::class, 'maximum_number_of_products_to_list') + 1);

                return $obj;
            }
            $obj = PaginatedList::create($list, $this->request);
            $obj->setPageLength($this->MyNumberOfProductsPerPage());

            return $obj;
        }
    }

    /****************************************************
     *  INTERNAL PROCESSING: USER PREFERENCES
    /****************************************************/

    /**
     * Checks out a bunch of $_GET variables
     * that are used to work out user preferences
     * Some of these are saved to session.
     *
     * @param array $overrideArray - override $_GET variable settings
     */
    protected function saveUserPreferences($overrideArray = [])
    {
        //save sort - filter - display
        $sortFilterDisplayNames = $this->getSortFilterDisplayNames();
        foreach ($sortFilterDisplayNames as $type => $oneTypeArray) {
            $getVariableName = $oneTypeArray['getVariable'];
            $sessionName = $oneTypeArray['sessionName'];
            if (isset($overrideArray[$getVariableName])) {
                $newPreference = $overrideArray[$getVariableName];
            } else {
                $newPreference = $this->request->getVar($getVariableName);
            }
            if ($newPreference) {
                $optionsVariableName = $oneTypeArray['configName'];
                $options = EcommerceConfig::get($this->ClassName, $optionsVariableName);
                if (isset($options[$newPreference])) {
                    $this->getRequest()->getSession()->set('ProductGroup_' . $sessionName, $newPreference);
                    //save in model as well...
                }
            } else {
                $newPreference = $this->getRequest()->getSession()->get('ProductGroup_' . $sessionName);
            }
            //save data in model...
            if ($newPreference) {
                $this->setCurrentUserPreference($type, $newPreference);
            }
        }
        /* save URLSegments in model
        $this->setCurrentUserPreference(
            "URLSegments",
            array(
                "Action" => $this->request->param("Action"),
                "ID" => $this->request->param("ID")
            )
        );
        */

        //clearing data..
        if ($this->request->getVar('reload')) {
            //reset other session variables...

            $this->getRequest()->getSession()->set($this->SearchResultsSessionVariable(), '');

            return $this->redirect($this->Link());
        }

        //full list ....
        if ($this->request->getVar('showfulllist')) {
            $this->showFullList = true;
        }
    }

    /**
     * Checks for the most applicable user preferences for this user:
     * 1. session value
     * 2. getMyUserPreferencesDefault.
     *
     * @param string $type - FILTER | SORT | DISPLAY
     *
     * @return string
     *
     * @todo: move to controller?
     */
    protected function getCurrentUserPreferences($type)
    {
        $sessionName = $this->getSortFilterDisplayNames($type, 'sessionName');
        if ($sessionValue = $this->getRequest()->getSession()->get('ProductGroup_' . $sessionName)) {
            $key = Convert::raw2sql($sessionValue);
        } else {
            $key = $this->getMyUserPreferencesDefault($type);
        }
        return $this->getBestKeyAndValidateKey($type, $key);
    }

    /**
     * Provides a dataset of links for a particular user preference.
     *
     * @param string $type SORT | FILTER | DISPLAY - e.g. sort_options
     *
     * @return \SilverStripe\ORM\ArrayList( ArrayData(Name, Link,  SelectKey, Current (boolean), LinkingMode))
     */
    protected function userPreferencesLinks($type)
    {
        //get basics
        $sortFilterDisplayNames = $this->getSortFilterDisplayNames();
        $options = $this->getConfigOptions($type);

        //if there is only one option then do not bother
        if (count($options) < 2) {
            return;
        }

        //get more config names
        $translationCode = $sortFilterDisplayNames[$type]['translationCode'];
        $getVariableName = $sortFilterDisplayNames[$type]['getVariable'];
        $arrayList = ArrayList::create();
        if (count($options)) {
            foreach ($options as $key => $array) {
                //$isCurrent = ($key == $selectedItem) ? true : false;

                $link = '?' . $getVariableName . "=${key}";
                if ($type === 'FILTER') {
                    $link = $this->Link() . $link;
                } else {
                    $link = $this->request->getVar('url') . $link;
                }
                $arrayList->push(ArrayData::create([
                    'Name' => _t('ProductGroup.' . $translationCode . strtoupper(str_replace(' ', '', $array['Title'])), $array['Title']),
                    'Link' => $link,
                    'SelectKey' => $key,
                    //we add current at runtime, so we can store the object without current set...
                    //'Current' => $isCurrent,
                    //'LinkingMode' => $isCurrent ? "current" : "link"
                ]));
            }
        }

        return $arrayList;
    }

    /**
     * add a secondary title to the main title
     * in case there is, for example, a filter applied
     * e.g. Socks | MyBrand.
     *
     * @param string $secondaryTitle
     */
    protected function addSecondaryTitle($secondaryTitle = '')
    {
        $pipe = _t('ProductGroup.TITLE_SEPARATOR', ' | ');
        if (! $this->secondaryTitleHasBeenAdded) {
            if (trim($secondaryTitle)) {
                $secondaryTitle = $pipe . $secondaryTitle;
            }
            if ($this->IsSearchResults()) {
                $array = $this->searchResultsArrayFromSession();
                $count = count($array);
                if ($count > 4) {
                    if ($count < EcommerceConfig::get(ProductGroup::class, 'maximum_number_of_products_to_list_for_search')) {
                        $toAdd = $count . ' ' . _t('ProductGroup.PRODUCTS_FOUND', 'Products Found');
                        $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
                    }
                } else {
                    $toAdd = _t('ProductGroup.SEARCH_RESULTS', 'Search Results');
                    $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
                }
            }
            if (is_object($this->filterForGroupObject)) {
                $toAdd = $this->filterForGroupObject->Title;
                $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
            }
            $pagination = true;
            if ($this->IsShowFullList()) {
                $toAdd = _t('ProductGroup.LIST_VIEW', 'List View');
                $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
                $pagination = false;
            }
            $filter = $this->getCurrentUserPreferences('FILTER');
            if ($filter !== $this->getMyUserPreferencesDefault('FILTER')) {
                $toAdd = $this->getUserPreferencesTitle('FILTER', $this->getCurrentUserPreferences('FILTER'));
                $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
            }
            if ($this->HasSort()) {
                $toAdd = $this->getUserPreferencesTitle('SORT', $this->getCurrentUserPreferences('SORT'));
                $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
            }
            if ($pagination) {
                if ($pageStart = intval($this->request->getVar('start'))) {
                    if ($pageStart > 0) {
                        $page = ($pageStart / $this->MyNumberOfProductsPerPage()) + 1;
                        $toAdd = _t('ProductGroup.PAGE', 'Page') . ' ' . $page;
                        $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
                    }
                }
            }
            if ($secondaryTitle) {
                $this->Title .= $secondaryTitle;
                if (isset($this->MetaTitle)) {
                    $this->MetaTitle .= $secondaryTitle;
                }
                if (isset($this->MetaDescription)) {
                    $this->MetaDescription .= $secondaryTitle;
                }
            }
            //dont update menu title, because the entry in the menu
            //should stay the same as it links back to the unfiltered
            //page (in some cases).

            $this->secondaryTitleHasBeenAdded = true;
        }
    }

    /**
     * removes any spaces from the 'toAdd' bit and adds the pipe if there is
     * anything to add at all.  Through the lang files, you can change the pipe
     * symbol to anything you like.
     *
     * @param  string $pipe
     * @param  string $toAdd
     * @return string
     */
    protected function cleanSecondaryTitleForAddition($pipe, $toAdd)
    {
        $toAdd = trim($toAdd);
        $length = strlen($toAdd);
        if ($length > 0) {
            $toAdd = $pipe . $toAdd;
        }
        return $toAdd;
    }
}
