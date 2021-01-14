<?php

namespace Sunnysideup\Ecommerce\Pages;

use PageController;
use SilverStripe\Control\Director;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use Sunnysideup\Ecommerce\Api\ArrayMethods;
use Sunnysideup\Ecommerce\Api\EcommerceCache;
use Sunnysideup\Ecommerce\Api\ShoppingCart;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\ProductSearchForm;

class ProductGroupController extends PageController
{
    public const GET_VAR_VALUE_PLACE_HOLDER = '[[INSERT_VALUE_HERE]]';

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
        'resetsort' => true,
        'resetdisplayer' => true,
    ];

    ########################################
    # actions
    ########################################

    public function index()
    {
        $this->addSecondaryTitle();

        return $this->defaultReturn();
    }

    /**
     * LEGACY METHOD!!!
     *
     * @return \SilverStripe\Control\HTTPRequest
     */
    public function filterforgroup($request)
    {
        $otherGroupURLSegment = Convert::raw2sql($request->param('ID'));
        if ($otherGroupURLSegment) {
            $otherProductGroup = DataObject::get_one(
                ProductGroup::class,
                ['URLSegment' => $otherGroupURLSegment]
            );
            if ($otherProductGroup) {
                $this->saveUserPreferences('FILTER', $otherProductGroup->URLSegment . ',' . $otherProductGroup->ID);
            }
        }
        return $this->index();
    }

    // /**
    //  * name for session variable where we store the last search results for this page.
    //  * @return string
    //  */
    // public function SearchResultsSessionVariable(): string
    // {
    //     $idString = '_' . $this->ID;
    //
    //     return $this->Config()->get('product_search_session_variable') . $idString;
    // }
    //
    // /**
    //  * @return array
    //  */
    // public function searchResultsArrayFromSession(): array
    // {
    //     return [];
    //     // return $this->ProductSearchForm()->getProductIds();
    // }
    //
    // /**
    //  * @return array
    //  */

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
        $this->resetfilter();
        $this->addSecondaryTitle($keyword);

        return [];
    }

    /**
     * Resets the filter only.
     */
    public function resetfilter()
    {
        $this->saveUserPreferences(['FILTER' => '']);

        return $this->defaultReturn();
    }

    /**
     * resets the sort only.
     */
    public function resetsort()
    {
        $this->saveUserPreferences(['SORT' => '']);

        return $this->defaultReturn();
    }

    /**
     * resets the displayer only.
     */
    public function resetdisplayer()
    {
        $this->saveUserPreferences(['DISPLAY' => '']);

        return $this->defaultReturn();
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
     * alias
     * @return
     */
    public function getProductList()
    {
        return $this->getFinalProductList();
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
        $list = $this->getCachedProductList();
        if (! $list) {
            $list = $this->getFinalProductList()->getProducts();
            EcommerceCache::inst()->save($this->ProductGroupListCachingKey(), $list->column('ID'));
        }
        $this->getFinalProductList()
            ->applyFilter($this->getCurrentUserPreferences('FILTER'))
            ->applySorter($this->getCurrentUserPreferences('SORT'))
            ->applyDisplayer($this->getCurrentUserPreferences('DISPLAY'));

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
            $filterKey = $this->getCurrentUserPreferences('FILTER');
            $displayKey = $this->getCurrentUserPreferences('DISPLAY');
            $sortKey = $this->getCurrentUserPreferences('SORT');
            $pageStart = '';
            if ($withPageNumber) {
                $pageStart = $this->getCurrentPageNumber();
            }
            return $this->cacheKey(
                implode(
                    '_',
                    array_filter([
                        $displayKey,
                        $filterKey,
                        $sortKey,
                        $pageStart,
                    ])
                )
            );
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
        return $this->getCurrentUserPreferences('FILTER') !== $this->getProductListConfigDefaultValue('FILTER');
    }

    public function HasSort(): bool
    {
        return $this->getCurrentUserPreferences('SORT') !== $this->getProductListConfigDefaultValue('SORT');
    }

    public function HasDisplay(): bool
    {
        return $this->getCurrentUserPreferences('DISPLAY') !== $this->getProductListConfigDefaultValue('DISPLAY');
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

    public function getCurrentPageNumber()
    {
        if ($pageStart = intval($this->request->getVar('start'))) {
            return ($pageStart / $this->getProductsPerPage()) + 1;
        }

        return 1;
    }

    public function getUserPreferencesTitle(string $type, $value)
    {
        return 'To be completed';
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
     * short-cut for getProductListConfigDefaultValue("DISPLAY")
     * for use in templtes.
     *
     * @return string - key
     */
    public function MyDefaultDisplayStyle(): string
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

    /**
     * Link that returns a list of all the products
     * for this product group as a simple list.
     *
     * @return string
     */
    public function ListAllLink(): string
    {
        return $this->Link() . '&showfulllist=1';
    }

    /**
     * Link that returns a list of all the products
     * for this product group as a simple list.
     *
     * @return string
     */
    public function ListAFewLink(): string
    {
        return str_replace('&showfulllist=1', '', $this->ListAllLink());
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
    public function ShowSearchFormImmediately(): bool
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

    public function saveUserPreferences($filter = [], $sort = [], $display = '')
    {
    }

    public function getCurrentUserPreferences()
    {
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
     * @param  string $type [description]
     * @return string       [description]
     */
    protected function getLinkTemplate(?string $type = '', ?string $action = null): string
    {
        $base = $this->dataRecord->Link($action);
        $getVars = [];
        foreach ($this->getSortFilterDisplayNames() as $key => $values) {
            if ($type && $type === $key) {
                $value = self::GET_VAR_VALUE_PLACE_HOLDER;
            } else {
                $value = $this->getCurrentUserPreferences($type);
            }
            $getVars[$values['getVariable']] = $value;
        }

        return $base . '?' . http_build_query($getVars);
    }

    protected function init()
    {
        parent::init();
        if ($this->request->getVar('reload')) {
            // $this->getRequest()->getSession()->set($this->SearchResultsSessionVariable(false), '');
            //
            // $this->getRequest()->getSession()->set($this->SearchResultsSessionVariable(true), '');

            return $this->redirect($this->Link());
        }
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
                    $this->saveUserPreferences(['SORT' => $suggestion]);
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

    // /**
    //  * counts the total number in the combination....
    //  *
    //  * @param ProductGroup[] $secondaryTitle
    //  * @param array  $secondaryTitle - list of products on the current page
    //  *
    //  * @return array
    //  */
    // protected function productGroupFilterLinksCount($groups, $baseArray, $ajaxify = true) : array
    // {
    //     $array = [];
    //
    //     if ($groups) {
    //         foreach ($groups as $item) {
    //             $arrayOfIDs = $item->currentInitialProductsAsCachedArray($this->getProductListConfigDefaultValue('FILTER'));
    //             $newArray = array_intersect_key(
    //                 $arrayOfIDs,
    //                 $baseArray
    //             );
    //
    //             $count = count($newArray);
    //
    //             if ($count) {
    //                 $array[$item->Title] = [
    //                     'Item' => $item,
    //                     'Count' => $count,
    //                     'Ajaxify' => $ajaxify,
    //                 ];
    //             }
    //         }
    //     }
    //
    //     return $array;
    // }
    //
    // /**
    //  * @param array $secondaryTitle (Item, Count, UserFilterAction)
    //  *
    //  * @return ArrayData
    //  */
    // protected function makeArrayItem($itemInArray)
    // {
    //     $item = $itemInArray['Item'];
    //     $count = $itemInArray['Count'];
    //     $ajaxify = $itemInArray['Ajaxify'];
    //     $filterForGroupObjectID = $this->filterForGroupObject ? $this->filterForGroupObject->ID : 0;
    //     $isCurrent = ($item->ID === $filterForGroupObjectID ? true : false);
    //
    //     if ($ajaxify) {
    //         $link = $this->Link($item->FilterForGroupLinkSegment());
    //     } else {
    //         $link = $item->Link();
    //     }
    //
    //     return [
    //         'ID' => $item->ID,
    //         'ClassName' => $item->ClassName,
    //         'Title' => $item->Title,
    //         'Count' => $count,
    //         'SelectKey' => $item->URLSegment,
    //         'Current' => $isCurrent ? true : false,
    //         'MyLinkingMode' => $isCurrent ? 'current' : 'link',
    //         'FilterLink' => $link,
    //         'Ajaxify' => $ajaxify ? true : false,
    //     ];
    // }

    /**
     * Add a secondary title to the main title in case there is, for example, a
     * filter applied (e.g. Socks | MyBrand).
     *
     * @param string $secondaryTitle
     */
    protected function addSecondaryTitle($secondaryTitle = '')
    {
        //todo: add to config

        if (! $this->secondaryTitleHasBeenAdded) {
            if (trim($secondaryTitle)) {
                $secondaryTitle = $this->prepareForSecondaryTitleAddition($secondaryTitle);
            }

            if ($this->IsSearchResults()) {
                $count = $this->getProductList()->getRawCount();

                if ($count) {
                    $toAdd = $count . ' ' . _t('ProductGroup.PRODUCTS_FOUND', 'Products Found');
                    $secondaryTitle .= $this->prepareForSecondaryTitleAddition($toAdd);
                } else {
                    $toAdd = _t('ProductGroup.SEARCH_RESULTS', 'Search Results');
                    $secondaryTitle .= $this->prepareForSecondaryTitleAddition($toAdd);
                }
            }

            if ($this->hasFilter()) {
                $secondaryTitle .= $this->prepareForSecondaryTitleAddition($this->getCurrentFilterTitle());
            }

            if ($this->HasSort()) {
                $secondaryTitle .= $this->prepareForSecondaryTitleAddition($this->getCurrentSortTitle());
            }

            $currentPageNumber = $this->getCurrentPageNumber();
            if ($currentPageNumber > 1) {
                $secondaryTitle .= $this->prepareForSecondaryTitleAddition(
                    $pipe,
                    _t('ProductGroup.PAGE', 'Page') . ' ' . $page
                );
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
     * @param  string $toAdd
     * @return string
     */
    protected function prepareForSecondaryTitleAddition(string $toAdd): string
    {
        $toAdd = trim($toAdd);
        $length = strlen($toAdd);

        if ($length > 0) {
            $pipe = _t('ProductGroup.TITLE_SEPARATOR', ' | ');
            $toAdd = $pipe . $toAdd;
        }

        return $toAdd;
    }

    /**
     * turns full list into paginated list.
     *
     * @param SS_List $list
     *
     * @return PaginatedList
     */
    protected function paginateList($list)
    {
        if ($list && $list->count()) {
            if ($this->IsShowFullList()) {
                $obj = PaginatedList::create($list, $this->request);
                $obj->setPageLength(EcommerceConfig::get('ProductGroup', 'maximum_number_of_products_to_list') + 1);

                return $obj;
            }
            $obj = PaginatedList::create($list, $this->request);
            $obj->setPageLength($this->MyNumberOfProductsPerPage());

            return $obj;
        }
    }

    protected function defaultReturn()
    {
        if ($this->returnAjaxifiedProductList()) {
            return $this->renderWith('Sunnysideup\Ecommerce\Includes\AjaxProductList');
        }

        return [];
    }
}
