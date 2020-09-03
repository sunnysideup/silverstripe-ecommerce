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
        ]
    ];

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
        return ProductGroupSearchPage::get()->filter([
            'ClassName' => ProductGroupSearchPage::class
        ])->count() ? false : $this->canEdit($member);
    }

    public function childGroups($maxRecursiveLevel, $filter = null, $numberOfRecursions = 0)
    {
        return ArrayList::create();
    }
}
