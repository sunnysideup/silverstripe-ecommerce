<?php

namespace Sunnysideup\Ecommerce\Pages;

use SilverStripe\ORM\DataObject;

/**
 * This page manages searching for products.
 */
class ProductGroupSearchPage extends ProductGroup
{
    protected static $main_search_page;

    /**
     * @var ProductGroupSearchPage
     */
    protected static $mainSearchPageCache;

    /**
     * @var int
     */
    private static $maximum_number_of_products_to_list_for_search = 1000;

    /**
     * @var string
     */
    private static $best_match_key = 'bestmatch';

    private static $table_name = 'ProductGroupSearchPage';

    private static $icon = 'sunnysideup/ecommerce:client/images/icons/productgroupsearchpage-file.gif';

    private static $description = 'This page allowing the user to search for products.';

    private static $singular_name = 'Product Search Page';

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
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return ProductGroupSearchPage::get()->exists() ? false : $this->canEdit($member);
    }

    /**
     * @return null|ProductGroupSearchPage
     */
    public static function main_search_page()
    {
        // @return null|ProductGroupSearchPage
        return DataObject::get_one(ProductGroupSearchPage::class);
    }

    /**
     * return ID of the only ProductGroupSearchPage.
     */
    public static function main_search_page_id(): int
    {
        $page = self::main_search_page();

        return $page ? $page->ID : 0;
    }

    public function getMyLevelOfProductsToShow(?int $defauult = 99): int
    {
        return -2;
    }
}
