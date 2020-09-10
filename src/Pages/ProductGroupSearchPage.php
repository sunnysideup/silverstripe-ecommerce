<?php

namespace Sunnysideup\Ecommerce\Pages;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;

/**
 * This page manages searching for products.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: Pages
 **/
class ProductGroupSearchPage extends ProductGroup
{
    /**
     * Can product list (and related) be cached at all?
     *
     * @var bool
     */
    protected $allowCaching = false;

    /**
     * @var int
     */
    private static $maximum_number_of_products_to_list_for_search = 100;

    /**
     * @var string
     */
    private static $best_match_key = 'bestmatch';

    /**
     * @var array
     */
    private static $sort_options = [
        'bestmatch' => [
            'Title' => 'Best Match',
            'SQL' => '"Price" DESC',
        ],
    ];

    private static $table_name = 'ProductGroupSearchPage';

    /**
     * standard SS variable.
     *
     * @static String | Array
     */
    private static $icon = 'sunnysideup/ecommerce: client/images/icons/productgroupsearchpage-file.gif';

    /**
     * Standard SS variable.
     *
     * @var string
     */
    private static $description = 'This page allowing the user to search for products.';

    /**
     * Standard SS variable.
     */
    private static $singular_name = 'Product Search Page';

    /**
     * Standard SS variable.
     */
    private static $plural_name = 'Product Search Pages';

    public function i18n_singular_name()
    {
        return _t('ProductGroupSearchPage.SINGULARNAME', 'Product Search Page');
    }

    public function i18n_plural_name()
    {
        return _t('ProductGroupSearchPage.PLURALNAME', 'Product Search Pages');
    }

    /**
     * Standard SS function, we only allow for one Product Search Page to exist
     * but we do allow for extensions to exist at the same time.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return ProductGroupSearchPage::get()->filter(['ClassName' => ProductGroupSearchPage::class])->Count() ? false : $this->canEdit($member);
    }

    /**
     * Setter for all products
     * @param DataList $dataList List of products
     */
    public function setAllProducts(DataList $dataList)
    {
        $this->allProducts = $dataList;

        return $this;
    }

    /**
     * This is a KEY method that overrides the standard method!
     * @return [type] [description]
     */
    public function getGroupFilter()
    {
        $resultArray = $this->searchResultsArrayFromSession();
        $this->allProducts = $this->allProducts->filter(['ID' => $resultArray]);

        return $this->allProducts;
    }

    public function childGroups($maxRecursiveLevel, $filter = null, $numberOfRecursions = 0)
    {
        return ArrayList::create();
    }

    public function ProductsShowable($extraFilter = null, $alternativeSort = null, $alternativeFilterKey = '')
    {
        // $alternativeSort = $this->getSearchResultsDefaultSort($this->searchResultsArrayFromSession(), $alternativeSort);

        $this->allProducts = parent::ProductsShowable($extraFilter, $alternativeSort, $alternativeFilterKey);

        return $this->allProducts;
    }

    /**
     * returns the SORT part of the final selection of products.
     *
     * @return string | Array
     */
    protected function currentSortSQL()
    {
        $sortKey = $this->getCurrentUserPreferences('SORT');

        return $this->getUserSettingsOptionSQL('SORT', $sortKey);
    }
}
