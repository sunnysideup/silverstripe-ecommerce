<?php

namespace Sunnysideup\Ecommerce\Model\Search;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Model\Extensions\EcommerceRole;

/**
 * Class \Sunnysideup\Ecommerce\Model\Search\SearchHistory
 *
 * @property string $Title
 * @property int $ProductCount
 * @property int $GroupCount
 */
class SearchHistory extends DataObject
{
    /**
     * @var int
     */
    public const KEYWORD_LENGTH_LIMIT = 80;

    private static $table_name = 'SearchHistory';

    /**
     * we limit keyword searchres to 80 characters ...
     */
    private static $db = [
        'Title' => 'Varchar(' . self::KEYWORD_LENGTH_LIMIT . ')',
        'ProductCount' => 'Int',
        'GroupCount' => 'Int',
    ];

    private static $default_sort = [
        'ID' => 'DESC',
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
     * @var string
     */
    private static $singular_name = 'Search History Entry';

    /**
     * standard SS variable.
     *
     * @var string
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
     */
    public static function add_entry(string $keywordString, ?int $productCount = 0, ?int $groupCount = 0): ?SearchHistory
    {
        $member = Security::getCurrentUser();
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
     * standard SS method.
     *
     * @param \SilverStripe\Security\Member $member
     * @param mixed                         $context
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
     * @param mixed                         $context
     *
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        if (! $member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if (null !== $extended) {
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
     * @param mixed                         $context
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
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * remove excessive spaces.
     */
    protected function onBeforeWrite()
    {
        $this->Title = trim(preg_replace('#\s+#', ' ', (string) $this->Title));
        parent::onBeforeWrite();
    }
}
