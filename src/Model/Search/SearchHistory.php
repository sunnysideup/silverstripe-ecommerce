<?php

namespace Sunnysideup\Ecommerce\Model\Search;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

class SearchHistory extends DataObject
{
    private static $table_name = 'SearchHistory';

    private static $db = [
        'Title' => 'Varchar(255)',
        'ProductCount' => 'Int',
        'GroupCount' => 'Int',
    ];

    private static $default_sort = [
        'Created' => 'DESC',
    ];

    private static $searchable_fields = [
        'Title' => 'PartialMatchFilter',
        'ProductCount' => 'GreaterThanOrEqualFilter',
        'GroupCount' => 'GreaterThanOrEqualFilter',
    ];

    private static $summary_fields = [
        'Created' => 'When',
        'Title' => 'Keyword',
        'ProductCount' => 'Products Found',
        'GroupCount' => 'Categories Found',
    ];

    private static $indexes = [
        'Title' => true,
        'Created' => true,
    ];

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Search History Entry';

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Search History Entries';

    public function i18n_singular_name()
    {
        return $this->Config()->get('singular_name');
    }

    public function i18n_plural_name()
    {
        return $this->Config()->get('plural_name');
    }

    /**
     * creates a new entry if you are not a shop admin.
     *
     * @param string $keywordString
     *
     * @return int
     */
    public static function add_entry($keywordString, $productCount = 0, $groupCount = 0): ?SearchHistory
    {
        $member = Security::currentUser();
        if ($member) {
            if ($member->IsShopAdmin()) {
                return null;
            }
        }
        $obj = new SearchHistory();
        $obj->Title = $keywordString;
        $obj->ProductCount = $productCount;
        $obj->GroupCount = $groupCount;
        $obj->write();

        return $obj;
    }

    /**
     * remove excessive spaces.
     */
    public function onBeforeWrite()
    {
        $this->Title = trim(preg_replace('!\s+!', ' ', $this->Title));
        parent::onBeforeWrite();
    }

    /**
     * standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    /**
     * standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get(EcommerceRole::class, 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canEdit($member = null, $context = [])
    {
        return false;
    }

    /**
     * standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     *
     * @return bool
     */
    public function canDelete($member = null, $context = [])
    {
        return false;
    }
}
