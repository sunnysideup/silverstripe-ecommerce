<?php

class SearchHistory extends DataObject
{
    private static $db = array(
        'Title' => 'Varchar(255)',
        'ProductCount' => 'Int',
        'GroupCount' => 'Int',
    );

    private static $default_sort = [
        'Created' => 'DESC'
    ];

    private static $searchable_fields = array(
        'Title' => 'PartialMatchFilter',
        'ProductCount' => 'GreaterThanOrEqualFilter',
        'GroupCount' => 'GreaterThanOrEqualFilter',
    );

    private static $summary_fields = array(
        'Created' => 'When',
        'Title' => 'Keyword',
        'ProductCount' => 'Products Found',
        'GroupCount' => 'Categories Found',
    );

    private static $indexes = [
        'Title' => true,
        'Created' => true
    ];


    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $singular_name = 'Search History Entry';
    public function i18n_singular_name()
    {
        return $this->Config()->get('singular_name');
    }

    /**
     * standard SS variable.
     *
     * @Var String
     */
    private static $plural_name = 'Search History Entries';
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
    public static function add_entry($keywordString, $productCount = 0, $groupCount = 0)
    {
        if ($member = Member::currentUser()) {
            if ($member->IsShopAdmin()) {
                return -1;
            }
        }
        $obj = new self();
        $obj->Title = $keywordString;
        $obj->ProductCount = $productCount;
        $obj->GroupCount = $groupCount;

        return $obj->write();
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
     * @param Member $member
     *
     * @return bool
     */
    public function canCreate($member = null)
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
    public function canView($member = null)
    {
        if (! $member) {
            $member = Member::currentUser();
        }
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        if (Permission::checkMember($member, Config::inst()->get('EcommerceRole', 'admin_permission_code'))) {
            return true;
        }

        return parent::canEdit($member);
    }

    /**
     * standard SS method.
     *
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
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
    public function canDelete($member = null)
    {
        return false;
    }
}
