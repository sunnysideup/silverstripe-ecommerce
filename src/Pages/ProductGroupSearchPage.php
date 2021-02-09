<?php

namespace Sunnysideup\Ecommerce\Pages;

use SilverStripe\ORM\ArrayList;

/**
 * This page manages searching for products.
 *
 * @author Nicolaas [at] Sunny Side Up .co.nz
 * @package ecommerce
 * @subpackage Pages
 */
class ProductGroupSearchPage extends ProductGroup
{
    /**
     * Can product list (and related) be cached at all?
     *
     * @var bool
     */
    protected $allowCaching = false;

    protected static $main_search_page = null;

    /**
     * @var int
     */
    private static $maximum_number_of_products_to_list_for_search = 100;

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
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return ProductGroupSearchPage::get()->count() ? false : $this->canEdit($member);
    }

    public function childGroups(?int $maxRecursiveLevel = 99, ?string $filter = null): ArrayList
    {
        return ArrayList::create();
    }

    /**
     * @return ProductGroupSearchPage|null
     */
    public static function main_search_page()
    {
        if (! self::$main_search_page) {
            self::$_main_search_page = ProductGroupSearchPage::get()->first();
        }
        return self::$_main_search_page;
    }

    /**
     * return ID of the only ProductGroupSearchPage
     * @return int
     */
    public static function main_search_page_id(): int
    {
        return self::main_search_page() ? self::main_search_page()->ID : 0;
    }
}
