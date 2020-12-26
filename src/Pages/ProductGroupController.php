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
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\ProductSearchForm;
use Sunnysideup\Ecommerce\Helpers\CachingHelper;

class ProductGroupController extends PageController
{
    /**
     * The original Title of this page before filters, etc...
     *
     * @var string
     */
    protected $originalTitle = '';

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

    /**
     * variable to make sure secondary title only gets
     * added once.
     *
     * @var bool
     */
    protected $secondaryTitleHasBeenAdded = false;

    private static $allowed_actions = [
        'debug' => 'ADMIN',
        'filterforgroup' => true,
        'ProductSearchForm' => true,
        'searchresults' => true,
        'resetfilter' => true,
    ];

    //@todo: why not use trait?
    private static $extensions = [
        CachingHelper::class,
    ];

    public function index()
    {
        $this->addSecondaryTitle();

        if ($this->returnAjaxifiedProductList()) {
            return $this->renderWith('Sunnysideup\Ecommerce\Includes\AjaxProductList');
        }

        return [];
    }

    /**
     * Cross filter with another product group..
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
                $defaultKey = $this->getProductListConfigDefaultValue('FILTER');
                $arrayOfIDs = $otherProductGroup->currentInitialProductsAsCachedArray($defaultKey);
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
        return [];
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
        if ($keyword) {
            $keyword = _t('Ecommerce.SEARCH_FOR', 'search for: ') . substr($keyword, 0, 25);
        }
        //filters are irrelevant right now
        $this->resetfilter();
        $this->addSecondaryTitle($keyword);
        //@todo!
        // $this->products = $this->paginateList(
        //     $this->ProductsShowable(
        //         ['ID' => $resultArray],
        //         $this->getSearchResultsDefaultSort($this->searchResultsArrayFromSession())
        //     )
        // );

        return [];
    }

    /**
     * Resets the filter only.
     */
    public function resetfilter()
    {
        $defaultKey = $this->getProductListConfigDefaultValue('FILTER');
        $filterGetVariable = $this->getSortFilterDisplayNames('FILTER', 'getVariable');

        $this->saveUserPreferences([$filterGetVariable => $defaultKey,]);

        return [];
    }

    /**
     * resets the sort only.
     */
    public function resetsort()
    {
        $defaultKey = $this->getProductListConfigDefaultValue('SORT');
        $sortGetVariable = $this->getSortFilterDisplayNames('SORT', 'getVariable');
        $this->saveUserPreferences([$sortGetVariable => $defaultKey,]);

        return [];
    }

    /**
     * Return the products for this group.
     *
     * This is the call that is made from the template and has the actual final
     * products being shown.
     *
     * @return \SilverStripe\ORM\PaginatedList
     */
    public function Products()
    {
        $this->addSecondaryTitle();
        $this->cachingRelatedJavascript();

        return $this->getProductList()->getPaginatedList();
    }

    /**
     * Is the product list cache-able?
     *
     * @return bool
     */
    public function ProductGroupListAreCacheable()
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
            return $this->cacheKey(
                implode(
                    '_',
                    [
                        $displayKey,
                        $filterKey,
                        $filterForGroupKey,
                        $sortKey,
                        $pageStart,
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
     * Returns child product groups for use in 'in this section'. For example
     * the vegetable Product Group may have listed here: Carrot, Cabbage, etc...
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function MenuChildGroups()
    {
        return $this->ChildGroups(2, [
            'ShowInMenus' => 1,
        ]);
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
            }
            $defaultKey = $this->getProductListConfigDefaultValue('FILTER');
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

    /**
     * Do we show all products on one page?
     *
     * @return bool
     */
    public function ShowFiltersAndDisplayLinks()
    {
        if ($this->getProductList()->CountGreaterThanOne()) {
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
        if ($this->getProductList()->CountGreaterThanOne($minimumCount)) {
            return true;
        }

        return false;
    }

    /**
     * Is there a special filter operating at the moment?
     *
     * Is the current filter the default one (return inverse!)?
     *
     * @return bool
     */
    public function HasFilter()
    {
        return $this->getCurrentUserPreferences('FILTER') !== $this->getProductListConfigDefaultValue('FILTER')
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

        if ($sort !== $this->getProductListConfigDefaultValue('SORT')) {
            return true;
        }
    }

    /**
     * @return boolean
     */
    public function HasFilterOrSort()
    {
        return $this->HasFilter() || $this->HasSort();
    }

    /**
     * Are filters available? we check one at the time so that we do the least
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
     * Returns the current filter applied to the list in a human readable
     *  string.
     *
     * @return string
     */
    public function CurrentDisplayTitle()
    {
        $displayKey = $this->getCurrentUserPreferences('DISPLAY');
        if ($displayKey !== $this->getProductListConfigDefaultValue('DISPLAY')) {
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
        if ($filterKey !== $this->getProductListConfigDefaultValue('FILTER')) {
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
        if ($sortKey !== $this->getProductListConfigDefaultValue('SORT')) {
            return $this->getUserPreferencesTitle('SORT', $sortKey);
        }
    }

    /**
     * short-cut for getProductListConfigDefaultValue("DISPLAY")
     * for use in templtes.
     *
     * @return string - key
     */
    public function MyDefaultDisplayStyle()
    {
        return $this->getProductListConfigDefaultValue('DISPLAY');
    }

    /**
     * Number of entries per page limited by total number of pages available...
     *
     * @return int
     */
    public function MaxNumberOfProductsPerPage(): int
    {
        $perPage = $this->getNumberOfProductsPerPage();
        $total = $this->TotalCount();
        return $perPage > $total ? $total : $perPage;
    }

    /**
     * Provides a ArrayList of links for filters products.
     *
     * @return \SilverStripe\ORM\ArrayList( ArrayData(Name, Link, SelectKey, Current (boolean), LinkingMode))
     */
    public function FilterLinks()
    {
        $list = $this->userPreferencesLinks('FILTER');

        foreach ($list as $obj) {
            $key = $obj->SelectKey;
            if ($key !== $this->getProductListConfigDefaultValue('FILTER')) {
                // @todo
                $count = 1;

                if ($count === 0) {
                    $list->remove($obj);
                } else {
                    $obj->Count = $count;
                }
            }
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
        $arrayOfItems = [];

        $baseArray = $this->getProductList()->getProductIds();

        $items = $this->getProductGroupsFromAlsoShowProducts();

        if ($items) {
            $arrayOfItems = array_merge($arrayOfItems, $this->productGroupFilterLinksCount($items, $baseArray, true));
        }

        $items = $this->getProductGroupsFromAlsoShowProductsInverse();

        if ($items) {
            $arrayOfItems = array_merge($arrayOfItems, $this->productGroupFilterLinksCount($items, $baseArray, true));
        }

        $items = $this->getProductGroupsParentGroups();

        if ($items) {
            $arrayOfItems = array_merge($arrayOfItems, $this->productGroupFilterLinksCount($items, $baseArray, true));
        }

        $items = $this->MenuChildGroups();

        if ($items) {
            $arrayOfItems = array_merge($arrayOfItems, $this->productGroupFilterLinksCount($items, $baseArray, true));
        }

        ksort($arrayOfItems);
        $array = [];

        foreach ($arrayOfItems as $arrayOfItem) {
            $array[] = $this->makeArrayItem($arrayOfItem);
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
     *
     * @return string
     */
    public function CanonicalLink()
    {
        $link = $this->AbsoluteLink();
        $this->extend('updateCanonicalLink', $link);

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
     * Link that returns a list of all the products for this product group as a
     * simple list. It resets everything; not just filter.
     *
     * @param bool $escapedAmpersands
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
            $getVariableNameFilter . '=' . $this->getProductListConfigDefaultValue('FILTER') . $ampersand .
            $getVariableNameSort . '=' . $this->getProductListConfigDefaultValue('SORT') . $ampersand .
            'reload=1';
    }

    /**
     * Link to the search results.
     *
     * @return string
     */
    public function SearchResultLink()
    {
        return $this->Link('searchresults');
    }

    protected function init()
    {
        parent::init();
        $this->originalTitle = $this->Title;
        Requirements::themedCSS('client/css/ProductGroup');
        Requirements::themedCSS('client/css/ProductGroupPopUp');
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
                }
            }
        }
        return $alternativeSort;
    }

    /**
     * Overload this function of ProductGroup Extensions.
     *
     * @return bool
     */
    protected function returnAjaxifiedProductList()
    {
        return Director::is_ajax() ? true : false;
    }

    /**
     * Overload this function of ProductGroup Extensions.
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
     * @param ProductGroup[] $groups
     * @param array  $baseArray - list of products on the current page
     *
     * @return array
     */
    protected function productGroupFilterLinksCount($groups, $baseArray, $ajaxify = true)
    {
        $array = [];

        if ($groups) {
            foreach ($groups as $item) {
                $arrayOfIDs = $item->currentInitialProductsAsCachedArray($this->getProductListConfigDefaultValue('FILTER'));
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

    /**
     * Checks out a bunch of $_GET variables that are used to work out user
     * preferences.
     *
     * Some of these are saved to session.
     *
     * @param array $overrideArray - override $_GET variable settings
     */
    protected function saveUserPreferences($overrideArray = [])
    {
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

            if ($newPreference) {
                $this->setCurrentUserPreference($type, $newPreference);
            }
        }

        if ($this->request->getVar('reload')) {
            // $this->getRequest()->getSession()->set($this->SearchResultsSessionVariable(false), '');
            //
            // $this->getRequest()->getSession()->set($this->SearchResultsSessionVariable(true), '');

            return $this->redirect($this->Link());
        }
    }

    /**
     * Checks for the most applicable user preferences for this user:
     * 1. session value
     * 2. getProductListConfigDefaultValue.
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
            $key = $this->getProductListConfigDefaultValue($type);
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
        // get basics
        $sortFilterDisplayNames = $this->getSortFilterDisplayNames();
        $options = $this->getConfigOptions($type);

        // if there is only one option then do not bother
        if (count($options) < 2) {
            return;
        }

        // get more config names
        $translationCode = $sortFilterDisplayNames[$type]['translationCode'];
        $getVariableName = $sortFilterDisplayNames[$type]['getVariable'];
        $arrayList = ArrayList::create();

        if (count($options)) {
            foreach ($options as $key => $array) {
                $link = '?' . $getVariableName . "=${key}";

                $link = $this->Link() . $link;

                $arrayList->push(ArrayData::create([
                    'Name' => _t('ProductGroup.' . $translationCode . strtoupper(str_replace(' ', '', $array['Title'])), $array['Title']),
                    'Link' => $link,
                    'SelectKey' => $key,
                ]));
            }
        }

        return $arrayList;
    }

    /**
     * Add a secondary title to the main title in case there is, for example, a
     * filter applied (e.g. Socks | MyBrand).
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
                $count = $this->getProductList()->getRawCount();

                if ($count) {
                    $toAdd = $count . ' ' . _t('ProductGroup.PRODUCTS_FOUND', 'Products Found');
                    $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
                } else {
                    $toAdd = _t('ProductGroup.SEARCH_RESULTS', 'Search Results');
                    $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
                }
            }
            if (is_object($this->filterForGroupObject)) {
                $toAdd = $this->filterForGroupObject->Title;
                $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
            }

            $filter = $this->getCurrentUserPreferences('FILTER');

            if ($filter !== $this->getProductListConfigDefaultValue('FILTER')) {
                $toAdd = $this->getUserPreferencesTitle('FILTER', $this->getCurrentUserPreferences('FILTER'));
                $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
            }

            if ($this->HasSort()) {
                $toAdd = $this->getUserPreferencesTitle('SORT', $this->getCurrentUserPreferences('SORT'));
                $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
            }

            if ($pageStart = intval($this->request->getVar('start'))) {
                if ($pageStart > 0) {
                    $page = ($pageStart / $this->getProductsPerPage()) + 1;
                    $toAdd = _t('ProductGroup.PAGE', 'Page') . ' ' . $page;
                    $secondaryTitle .= $this->cleanSecondaryTitleForAddition($pipe, $toAdd);
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

            // dont update menu title, because the entry in the menu
            // should stay the same as it links back to the unfiltered
            // page (in some cases).

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
