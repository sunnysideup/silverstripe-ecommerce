<?php

namespace Sunnysideup\Ecommerce\Model\Search;

use Override;
use SilverStripe\Security\Member;
use SilverStripe\Control\Controller;
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
     * The maximum number of search history entries that are added per minute (for all visitors together).
     * Set to zero for no limit.
     *
     * @var int
     */
    private static $max_entries_per_minute = 120;

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

    #[Override]
    public function i18n_singular_name()
    {
        return $this->Config()->get('singular_name');
    }

    #[Override]
    public function plural_name()
    {
        return $this->Config()->get('plural_name');
    }

    /**
     * creates a new entry if you are not a shop admin.
     */
    public static function add_entry(string $keywordString, ?int $productCount = 0, ?int $groupCount = 0): ?SearchHistory
    {
        $member = Security::getCurrentUser();
        if ($member && $member->IsShopAdmin()) {
            return null;
        }
        $keywordString = mb_substr(trim($keywordString), 0, self::KEYWORD_LENGTH_LIMIT);
        if (mb_strlen($keywordString) < 2) {
            return null;
        }
        // Anyone can search, so we limit the number of rows that can be added:
        // 1. the same keyword is only recorded once per minute per visitor (e.g. page reloads).
        $now = time();
        $session = Controller::curr() instanceof Controller ? Controller::curr()->getRequest()?->getSession() : null;
        if ($session) {
            $last = $session->get('SearchHistoryLastEntry');
            if (is_array($last) && ($last['keyword'] ?? '') === $keywordString && ($now - (int) ($last['time'] ?? 0)) < 60) {
                return null;
            }
            $session->set('SearchHistoryLastEntry', ['keyword' => $keywordString, 'time' => $now]);
        }
        // 2. overall maximum number of entries per minute (visitors can change their keyword and drop their session).
        $max = (int) Config::inst()->get(self::class, 'max_entries_per_minute');
        if ($max > 0) {
            $recent = SearchHistory::get()
                ->filter(['Created:GreaterThan' => date('Y-m-d H:i:s', $now - 60)])
                ->count();
            if ($recent >= $max) {
                return null;
            }
        }
        $obj = SearchHistory::create();
        $obj->Title = $keywordString;
        $obj->ProductCount = $productCount;
        $obj->GroupCount = $groupCount;
        $obj->write();

        return $obj;
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    #[Override]
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    #[Override]
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
     * @param Member $member
     * @param mixed                         $context
     *
     * @return bool
     */
    #[Override]
    public function canEdit($member = null, $context = [])
    {
        return false;
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    #[Override]
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * remove excessive spaces.
     */
    #[Override]
    protected function onBeforeWrite()
    {
        $this->Title = trim((string) preg_replace('#\s+#', ' ', (string) $this->Title));
        parent::onBeforeWrite();
    }
}
