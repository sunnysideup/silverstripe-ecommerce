<?php

namespace Sunnysideup\Ecommerce\Pages;



use Sunnysideup\Ecommerce\Pages\ProductGroupSearchPage;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ArrayList;


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
     * standard SS variable.
     *
     * @static String | Array
     */
    private static $icon = 'ecommerce/images/icons/productgroupsearchpage';

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
     * @param Member $member
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

